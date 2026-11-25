<?php
/**
 * Aggregated ratings stored in post meta.
 *
 * Totals = imported baseline (e.g. from kk Star Ratings) + votes in the votes table.
 * Aggregates are cached in post meta so reads never touch the votes table.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Class Rating_Repository
 */
class Rating_Repository {

	const META_COUNT      = '_ratingpress_count';
	const META_SUM        = '_ratingpress_sum';
	const META_AVG        = '_ratingpress_avg';
	const META_BASE_COUNT = '_ratingpress_base_count';
	const META_BASE_SUM   = '_ratingpress_base_sum';
	const META_DISABLED   = '_ratingpress_disabled';
	const META_SCHEMA     = '_ratingpress_schema_type';

	/**
	 * Vote repository.
	 *
	 * @var Vote_Repository
	 */
	private $votes;

	/**
	 * Settings model.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Vote_Repository $votes    Vote repository.
	 * @param Settings        $settings Settings model.
	 */
	public function __construct( Vote_Repository $votes, Settings $settings ) {
		$this->votes    = $votes;
		$this->settings = $settings;
	}

	/**
	 * Every meta key owned by the plugin.
	 *
	 * @return string[]
	 */
	public static function meta_keys() {
		return array(
			self::META_COUNT,
			self::META_SUM,
			self::META_AVG,
			self::META_BASE_COUNT,
			self::META_BASE_SUM,
			self::META_DISABLED,
			self::META_SCHEMA,
		);
	}

	/**
	 * Returns the aggregated rating of a post.
	 *
	 * @param int $post_id Post ID.
	 * @return Rating
	 */
	public function find( $post_id ) {
		$post_id = (int) $post_id;

		return new Rating(
			$post_id,
			(int) get_post_meta( $post_id, self::META_COUNT, true ),
			(float) get_post_meta( $post_id, self::META_SUM, true ),
			$this->settings->best()
		);
	}

	/**
	 * Rebuilds the cached aggregates of a post from the baseline and the votes table.
	 *
	 * @param int $post_id Post ID.
	 * @return Rating
	 */
	public function recalculate( $post_id ) {
		$post_id = (int) $post_id;

		// Never write meta for a post that no longer exists; drop its leftover votes instead.
		if ( ! get_post( $post_id ) ) {
			$this->purge( $post_id );

			return new Rating( $post_id, 0, 0.0, $this->settings->best() );
		}

		$totals = $this->votes->totals_for_post( $post_id );
		$count  = $totals['count'] + (int) get_post_meta( $post_id, self::META_BASE_COUNT, true );
		$sum    = $totals['sum'] + (float) get_post_meta( $post_id, self::META_BASE_SUM, true );

		if ( $count > 0 ) {
			update_post_meta( $post_id, self::META_COUNT, $count );
			update_post_meta( $post_id, self::META_SUM, round( $sum, 4 ) );
			update_post_meta( $post_id, self::META_AVG, round( $sum / $count, 4 ) );
		} else {
			delete_post_meta( $post_id, self::META_COUNT );
			delete_post_meta( $post_id, self::META_SUM );
			delete_post_meta( $post_id, self::META_AVG );
		}

		/**
		 * Fires after the aggregated rating of a post changed.
		 *
		 * @param int $post_id Post ID.
		 */
		do_action( 'ratingpress_rating_updated', $post_id );

		return $this->find( $post_id );
	}

	/**
	 * Stores an imported baseline (normalized to a 0-5 scale) and refreshes the aggregates.
	 *
	 * @param int   $post_id Post ID.
	 * @param int   $count   Number of imported votes.
	 * @param float $sum     Sum of imported votes on the 0-5 scale.
	 * @return Rating
	 */
	public function set_baseline( $post_id, $count, $sum ) {
		$count = max( 0, (int) $count );
		$sum   = max( 0.0, min( (float) $sum, $count * Rating::SCALE ) );

		if ( $count > 0 ) {
			update_post_meta( $post_id, self::META_BASE_COUNT, $count );
			update_post_meta( $post_id, self::META_BASE_SUM, round( $sum, 4 ) );
		} else {
			delete_post_meta( $post_id, self::META_BASE_COUNT );
			delete_post_meta( $post_id, self::META_BASE_SUM );
		}

		return $this->recalculate( $post_id );
	}

	/**
	 * Removes every vote and aggregate of a post.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function reset( $post_id ) {
		$this->votes->delete_for_post( $post_id );

		foreach ( array( self::META_COUNT, self::META_SUM, self::META_AVG, self::META_BASE_COUNT, self::META_BASE_SUM ) as $key ) {
			delete_post_meta( $post_id, $key );
		}

		/** This action is documented in app/Models/class-rating-repository.php */
		do_action( 'ratingpress_rating_updated', (int) $post_id );
	}

	/**
	 * Deletes the votes of a post that was deleted. Hooked to `deleted_post`.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function purge( $post_id ) {
		$this->votes->delete_for_post( (int) $post_id );
	}

	/**
	 * Removes all ratings from the site.
	 *
	 * @return void
	 */
	public function reset_all() {
		$this->votes->delete_all();

		foreach ( array( self::META_COUNT, self::META_SUM, self::META_AVG, self::META_BASE_COUNT, self::META_BASE_SUM ) as $key ) {
			delete_post_meta_by_key( $key );
		}
	}

	/**
	 * Post IDs that have rating data (votes or baseline), in ascending order.
	 *
	 * Uses keyset pagination, so recalculating a batch (which may remove rating data)
	 * never shifts the following batches.
	 *
	 * @param int $after_id Return IDs greater than this one.
	 * @param int $limit    Limit.
	 * @return int[]
	 */
	public function rated_post_ids( $after_id, $limit ) {
		global $wpdb;

		$votes = Vote_Repository::table();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Batch maintenance query over plugin data; table name is not user input.
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM (
					SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN (%s, %s) AND post_id > %d
					UNION
					SELECT post_id FROM {$votes} WHERE post_id > %d
				) AS rated
				ORDER BY post_id ASC
				LIMIT %d",
				self::META_COUNT,
				self::META_BASE_COUNT,
				$after_id,
				$after_id,
				$limit
			)
		);
		// phpcs:enable

		return array_map( 'intval', (array) $ids );
	}

	/**
	 * Whether ratings are disabled for a single post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function is_disabled( $post_id ) {
		return (bool) get_post_meta( $post_id, self::META_DISABLED, true );
	}

	/**
	 * Schema type override of a post, empty string when using the default.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function schema_type( $post_id ) {
		return (string) get_post_meta( $post_id, self::META_SCHEMA, true );
	}
}
