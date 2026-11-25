<?php
/**
 * Business rules for casting a vote: validation, permissions, duplicate protection
 * and rate limiting.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Services;

use RatingPress\Models\Rating;
use RatingPress\Models\Rating_Repository;
use RatingPress\Models\Settings;
use RatingPress\Models\Vote_Repository;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Class Vote_Service
 */
class Vote_Service {

	/**
	 * Cookie holding the IDs of posts voted on from this browser.
	 */
	const COOKIE = 'ratingpress_voted';

	/**
	 * Maximum number of post IDs remembered in the cookie.
	 */
	const COOKIE_LIMIT = 300;

	/**
	 * Settings model.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Vote repository.
	 *
	 * @var Vote_Repository
	 */
	private $votes;

	/**
	 * Rating repository.
	 *
	 * @var Rating_Repository
	 */
	private $ratings;

	/**
	 * Constructor.
	 *
	 * @param Settings          $settings Settings model.
	 * @param Vote_Repository   $votes    Vote repository.
	 * @param Rating_Repository $ratings  Rating repository.
	 */
	public function __construct( Settings $settings, Vote_Repository $votes, Rating_Repository $ratings ) {
		$this->settings = $settings;
		$this->votes    = $votes;
		$this->ratings  = $ratings;
	}

	/**
	 * Casts a vote for the current visitor.
	 *
	 * @param int $post_id Post ID.
	 * @param int $value   Selected rating (1..best).
	 * @return Rating|WP_Error Updated rating or error.
	 */
	public function cast( $post_id, $value ) {
		$post_id = (int) $post_id;
		$value   = (int) $value;
		$best    = $this->settings->best();
		$user_id = get_current_user_id();
		$ip_hash = $this->ip_hash();

		if ( ! $this->settings->is_enabled() ) {
			return new WP_Error( 'ratingpress_disabled', __( 'Ratings are currently disabled.', 'karina-star-ratings' ), array( 'status' => 403 ) );
		}

		$post = get_post( $post_id );
		if ( ! $post
			|| 'publish' !== $post->post_status
			|| ! is_post_type_viewable( $post->post_type )
			|| post_password_required( $post )
			|| $this->ratings->is_disabled( $post_id )
		) {
			return new WP_Error( 'ratingpress_invalid_post', __( 'This content cannot be rated.', 'karina-star-ratings' ), array( 'status' => 404 ) );
		}

		if ( $value < 1 || $value > $best ) {
			return new WP_Error( 'ratingpress_invalid_rating', __( 'Invalid rating value.', 'karina-star-ratings' ), array( 'status' => 400 ) );
		}

		if ( 'logged_in' === $this->settings->get( 'permission' ) && ! $user_id ) {
			return new WP_Error( 'ratingpress_login_required', (string) $this->settings->get( 'msg_login' ), array( 'status' => 401 ) );
		}

		if ( $this->has_voted( $post_id, $user_id, $ip_hash ) ) {
			return new WP_Error( 'ratingpress_already_voted', (string) $this->settings->get( 'msg_voted' ), array( 'status' => 409 ) );
		}

		// Checked after the duplicate check, so repeated clicks on a rated post do not use up the quota.
		if ( $this->is_rate_limited( $ip_hash ) ) {
			return new WP_Error( 'ratingpress_rate_limited', __( 'Too many votes. Please try again in a minute.', 'karina-star-ratings' ), array( 'status' => 429 ) );
		}

		/**
		 * Filters whether the current visitor may cast a vote.
		 *
		 * Return a WP_Error to reject the vote with a custom message.
		 *
		 * @param true|WP_Error $allowed Whether the vote is allowed.
		 * @param int           $post_id Post ID.
		 * @param int           $value   Rating value.
		 * @param int           $user_id User ID (0 for guests).
		 */
		$allowed = apply_filters( 'ratingpress_can_vote', true, $post_id, $value, $user_id );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		if ( true !== $allowed ) {
			return new WP_Error( 'ratingpress_forbidden', __( 'You are not allowed to rate this content.', 'karina-star-ratings' ), array( 'status' => 403 ) );
		}

		$vote_id = $this->votes->insert( $post_id, $value, $best, $user_id, $ip_hash );
		if ( ! $vote_id ) {
			return new WP_Error( 'ratingpress_db_error', __( 'Your vote could not be saved. Please try again.', 'karina-star-ratings' ), array( 'status' => 500 ) );
		}

		$rating = $this->ratings->recalculate( $post_id );
		$this->remember( $post_id );

		/**
		 * Fires after a vote was recorded.
		 *
		 * @param int    $post_id Post ID.
		 * @param int    $value   Rating value.
		 * @param int    $best    Maximum rating.
		 * @param int    $user_id User ID (0 for guests).
		 * @param Rating $rating  Updated aggregated rating.
		 * @param int    $vote_id Vote ID.
		 */
		do_action( 'ratingpress_vote_recorded', $post_id, $value, $best, $user_id, $rating, $vote_id );

		return $rating;
	}

	/**
	 * Whether the current visitor already voted on a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param int    $user_id User ID.
	 * @param string $ip_hash Hashed IP address.
	 * @return bool
	 */
	public function has_voted( $post_id, $user_id, $ip_hash ) {
		$restriction = (string) $this->settings->get( 'restriction' );

		if ( 'none' === $restriction ) {
			return false;
		}

		if ( $user_id && $this->votes->user_has_voted( $post_id, $user_id ) ) {
			return true;
		}

		if ( 'ip' === $restriction ) {
			return $this->votes->ip_has_voted( $post_id, $ip_hash );
		}

		return in_array( (int) $post_id, $this->cookie_ids(), true );
	}

	/**
	 * Applies a per-IP rate limit across all posts.
	 *
	 * @param string $ip_hash Hashed IP address.
	 * @return bool True when the visitor exceeded the limit.
	 */
	private function is_rate_limited( $ip_hash ) {
		$limit = (int) $this->settings->get( 'rate_limit' );

		if ( $limit <= 0 || '' === $ip_hash ) {
			return false;
		}

		$key    = 'ratingpress_rl_' . substr( $ip_hash, 0, 32 );
		$bucket = get_transient( $key );
		$now    = time();

		if ( ! is_array( $bucket ) || $now - (int) $bucket['start'] >= MINUTE_IN_SECONDS ) {
			$bucket = array(
				'start' => $now,
				'count' => 0,
			);
		}

		if ( (int) $bucket['count'] >= $limit ) {
			return true;
		}

		++$bucket['count'];
		set_transient( $key, $bucket, MINUTE_IN_SECONDS );

		return false;
	}

	/**
	 * Stores the voted post ID in a cookie.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function remember( $post_id ) {
		if ( headers_sent() ) {
			return;
		}

		$ids   = $this->cookie_ids();
		$ids[] = (int) $post_id;
		$ids   = array_slice( array_unique( $ids ), -self::COOKIE_LIMIT );

		setcookie(
			self::COOKIE,
			implode( '.', $ids ),
			array(
				'expires'  => time() + YEAR_IN_SECONDS,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
	}

	/**
	 * Post IDs stored in the browser cookie.
	 *
	 * @return int[]
	 */
	private function cookie_ids() {
		if ( empty( $_COOKIE[ self::COOKIE ] ) ) {
			return array();
		}

		$raw = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) );

		return array_values( array_filter( array_map( 'absint', explode( '.', $raw ) ) ) );
	}

	/**
	 * Returns a salted hash of the visitor IP address. Raw IPs are never stored.
	 *
	 * @return string
	 */
	public function ip_hash() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		/**
		 * Filters the visitor IP address, e.g. to read it from a trusted proxy header.
		 *
		 * @param string $ip IP address from REMOTE_ADDR.
		 */
		$ip = (string) apply_filters( 'ratingpress_client_ip', $ip );

		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return '';
		}

		return hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) );
	}
}
