<?php
/**
 * Data access for individual votes stored in the custom votes table.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Models;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- A custom table is required for vote logging; results are cached at the aggregate level in post meta.
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The table name is built from $wpdb->prefix and is not user input.

/**
 * Class Vote_Repository
 */
class Vote_Repository {

	/**
	 * Returns the votes table name for the current site.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;

		return $wpdb->prefix . 'ratingpress_votes';
	}

	/**
	 * Inserts a vote.
	 *
	 * @param int    $post_id Post ID.
	 * @param int    $rating  Selected rating (1..best).
	 * @param int    $best    Maximum rating at the time of the vote.
	 * @param int    $user_id User ID, 0 for guests.
	 * @param string $ip_hash Hashed IP address.
	 * @return int Inserted row ID, 0 on failure.
	 */
	public function insert( $post_id, $rating, $best, $user_id, $ip_hash ) {
		global $wpdb;

		$inserted = $wpdb->insert(
			self::table(),
			array(
				'post_id'    => (int) $post_id,
				'rating'     => (int) $rating,
				'best'       => (int) $best,
				'user_id'    => (int) $user_id,
				'ip_hash'    => (string) $ip_hash,
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%d', '%d', '%s', '%s' )
		);

		return $inserted ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Returns the vote count and the sum of votes normalized to a 0-5 scale.
	 *
	 * @param int $post_id Post ID.
	 * @return array{count: int, sum: float}
	 */
	public function totals_for_post( $post_id ) {
		global $wpdb;

		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS total, COALESCE(SUM(rating * 5 / best), 0) AS score FROM {$table} WHERE post_id = %d",
				$post_id
			)
		);

		return array(
			'count' => $row ? (int) $row->total : 0,
			'sum'   => $row ? (float) $row->score : 0.0,
		);
	}

	/**
	 * Whether a logged-in user already voted on a post.
	 *
	 * @param int $post_id Post ID.
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public function user_has_voted( $post_id, $user_id ) {
		global $wpdb;

		if ( ! $user_id ) {
			return false;
		}

		$table = self::table();

		return (bool) $wpdb->get_var(
			$wpdb->prepare( "SELECT 1 FROM {$table} WHERE post_id = %d AND user_id = %d LIMIT 1", $post_id, $user_id )
		);
	}

	/**
	 * Whether a vote was cast from an IP address on a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $ip_hash Hashed IP address.
	 * @return bool
	 */
	public function ip_has_voted( $post_id, $ip_hash ) {
		global $wpdb;

		if ( '' === $ip_hash ) {
			return false;
		}

		$table = self::table();

		return (bool) $wpdb->get_var(
			$wpdb->prepare( "SELECT 1 FROM {$table} WHERE post_id = %d AND ip_hash = %s LIMIT 1", $post_id, $ip_hash )
		);
	}

	/**
	 * Vote distribution on the given scale.
	 *
	 * @param int $best    Scale to map votes to.
	 * @param int $post_id Post ID, 0 for all posts.
	 * @return array<int, int> Star value => number of votes, highest first.
	 */
	public function distribution( $best, $post_id = 0 ) {
		global $wpdb;

		$best  = max( 1, (int) $best );
		$table = self::table();

		if ( $post_id ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT GREATEST(1, ROUND(rating * %d / best)) AS star, COUNT(*) AS total FROM {$table} WHERE post_id = %d GROUP BY star",
					$best,
					$post_id
				)
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT GREATEST(1, ROUND(rating * %d / best)) AS star, COUNT(*) AS total FROM {$table} GROUP BY star",
					$best
				)
			);
		}

		$distribution = array_fill_keys( range( $best, 1 ), 0 );
		foreach ( (array) $rows as $row ) {
			$star = min( $best, (int) $row->star );
			if ( isset( $distribution[ $star ] ) ) {
				$distribution[ $star ] += (int) $row->total;
			}
		}

		return $distribution;
	}

	/**
	 * Most recent votes with their post titles.
	 *
	 * @param int $limit Number of rows.
	 * @return array<int, object>
	 */
	public function recent( $limit = 10 ) {
		global $wpdb;

		$table = self::table();

		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT v.id, v.post_id, v.rating, v.best, v.user_id, v.created_at, p.post_title
				FROM {$table} v
				LEFT JOIN {$wpdb->posts} p ON p.ID = v.post_id
				ORDER BY v.id DESC
				LIMIT %d",
				$limit
			)
		);
	}

	/**
	 * Number of votes per day for the last N days (UTC dates).
	 *
	 * @param int $days Number of days.
	 * @return array<string, int> Y-m-d => votes, oldest first, including empty days.
	 */
	public function daily_counts( $days = 30 ) {
		global $wpdb;

		$days  = max( 1, (int) $days );
		$table = self::table();
		$since = gmdate( 'Y-m-d 00:00:00', time() - ( $days - 1 ) * DAY_IN_SECONDS );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) AS day, COUNT(*) AS total FROM {$table} WHERE created_at >= %s GROUP BY day",
				$since
			)
		);

		$series = array();
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$series[ gmdate( 'Y-m-d', time() - $i * DAY_IN_SECONDS ) ] = 0;
		}

		foreach ( (array) $rows as $row ) {
			if ( isset( $series[ $row->day ] ) ) {
				$series[ $row->day ] = (int) $row->total;
			}
		}

		return $series;
	}

	/**
	 * Post IDs that have at least one vote.
	 *
	 * @param int $offset Offset.
	 * @param int $limit  Limit.
	 * @return int[]
	 */
	public function post_ids( $offset, $limit ) {
		global $wpdb;

		$table = self::table();

		return array_map(
			'intval',
			(array) $wpdb->get_col(
				$wpdb->prepare( "SELECT DISTINCT post_id FROM {$table} ORDER BY post_id ASC LIMIT %d, %d", $offset, $limit )
			)
		);
	}

	/**
	 * Votes cast by a user (for personal data export).
	 *
	 * @param int $user_id User ID.
	 * @param int $offset  Offset.
	 * @param int $limit   Limit.
	 * @return array<int, object>
	 */
	public function for_user( $user_id, $offset, $limit ) {
		global $wpdb;

		$table = self::table();

		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, post_id, rating, best, created_at FROM {$table} WHERE user_id = %d ORDER BY id ASC LIMIT %d, %d",
				$user_id,
				$offset,
				$limit
			)
		);
	}

	/**
	 * Detaches all votes from a user while keeping the rating totals intact.
	 *
	 * @param int $user_id User ID.
	 * @return int Number of anonymized votes.
	 */
	public function anonymize_user( $user_id ) {
		global $wpdb;

		return (int) $wpdb->update(
			self::table(),
			array(
				'user_id' => 0,
				'ip_hash' => '',
			),
			array( 'user_id' => (int) $user_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Deletes all votes of a post.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function delete_for_post( $post_id ) {
		global $wpdb;

		$wpdb->delete( self::table(), array( 'post_id' => (int) $post_id ), array( '%d' ) );
	}

	/**
	 * Deletes every vote.
	 *
	 * @return void
	 */
	public function delete_all() {
		global $wpdb;

		$table    = self::table();
		$suppress = $wpdb->suppress_errors();
		$result   = $wpdb->query( "TRUNCATE TABLE {$table}" );
		$wpdb->suppress_errors( $suppress );

		// Some hosts do not grant the DROP privilege that TRUNCATE requires.
		if ( false === $result ) {
			$wpdb->query( "DELETE FROM {$table}" );
		}
	}
}
