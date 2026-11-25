<?php
/**
 * Privacy tools integration (privacy policy text, personal data export and erasure).
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Controllers;

use RatingPress\Models\Vote_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Privacy_Controller
 */
class Privacy_Controller {

	/**
	 * Items per export/erase page.
	 */
	const PER_PAGE = 100;

	/**
	 * Vote repository.
	 *
	 * @var Vote_Repository
	 */
	private $votes;

	/**
	 * Constructor.
	 *
	 * @param Vote_Repository $votes Vote repository.
	 */
	public function __construct( Vote_Repository $votes ) {
		$this->votes = $votes;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'add_policy_content' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
	}

	/**
	 * Suggests privacy policy text.
	 *
	 * @return void
	 */
	public function add_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = '<p>' . esc_html__( 'When you rate content on this site, we store the rating, the date, your user ID if you are logged in, and a one-way salted hash of your IP address. The hash cannot be turned back into your IP address and is only used to prevent duplicate votes and abuse. To remember which content you have rated, a cookie and a local storage entry (ratingpress_voted) containing only the IDs of the rated content may be stored in your browser.', 'karina-star-ratings' ) . '</p>';

		wp_add_privacy_policy_content( 'Karina Star Ratings', wp_kses_post( $content ) );
	}

	/**
	 * Registers the personal data exporter.
	 *
	 * @param array $exporters Exporters.
	 * @return array
	 */
	public function register_exporter( $exporters ) {
		$exporters['ratingpress'] = array(
			'exporter_friendly_name' => __( 'Karina Star Ratings votes', 'karina-star-ratings' ),
			'callback'               => array( $this, 'export' ),
		);

		return $exporters;
	}

	/**
	 * Registers the personal data eraser.
	 *
	 * @param array $erasers Erasers.
	 * @return array
	 */
	public function register_eraser( $erasers ) {
		$erasers['ratingpress'] = array(
			'eraser_friendly_name' => __( 'Karina Star Ratings votes', 'karina-star-ratings' ),
			'callback'             => array( $this, 'erase' ),
		);

		return $erasers;
	}

	/**
	 * Exports the votes of a user.
	 *
	 * @param string $email_address Email address.
	 * @param int    $page          Page number.
	 * @return array
	 */
	public function export( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );

		if ( ! $user ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$page  = max( 1, (int) $page );
		$votes = $this->votes->for_user( $user->ID, ( $page - 1 ) * self::PER_PAGE, self::PER_PAGE );
		$items = array();

		foreach ( $votes as $vote ) {
			$items[] = array(
				'group_id'    => 'ratingpress-votes',
				'group_label' => __( 'Ratings', 'karina-star-ratings' ),
				'item_id'     => 'ratingpress-vote-' . (int) $vote->id,
				'data'        => array(
					array(
						'name'  => __( 'Content', 'karina-star-ratings' ),
						'value' => get_the_title( (int) $vote->post_id ),
					),
					array(
						'name'  => __( 'Rating', 'karina-star-ratings' ),
						'value' => (int) $vote->rating . '/' . (int) $vote->best,
					),
					array(
						'name'  => __( 'Date', 'karina-star-ratings' ),
						'value' => get_date_from_gmt( $vote->created_at ),
					),
				),
			);
		}

		return array(
			'data' => $items,
			'done' => count( $votes ) < self::PER_PAGE,
		);
	}

	/**
	 * Anonymizes the votes of a user. Totals are kept intact.
	 *
	 * @param string $email_address Email address.
	 * @return array
	 */
	public function erase( $email_address ) {
		$user    = get_user_by( 'email', $email_address );
		$removed = $user ? $this->votes->anonymize_user( $user->ID ) : 0;

		return array(
			'items_removed'  => $removed > 0,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}
