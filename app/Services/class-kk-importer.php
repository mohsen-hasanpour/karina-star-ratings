<?php
/**
 * Imports existing ratings from the kk Star Ratings plugin.
 *
 * The kk Star Ratings plugin stores, per post, the vote count and the sum of votes normalized
 * to a 0-5 scale (`_kksr_count_default` / `_kksr_ratings_default`, or the legacy
 * `_kksr_casts` / `_kksr_ratings` keys). They are imported as a baseline, so running
 * the import again is safe and never doubles the numbers.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Services;

use RatingPress\Models\Rating_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Kk_Importer
 */
class Kk_Importer {

	/**
	 * Option flag set once an import finished.
	 */
	const DONE_OPTION = 'ratingpress_kk_imported';

	/**
	 * Count meta keys, newest first.
	 */
	const COUNT_KEYS = array( '_kksr_count_default', '_kksr_casts' );

	/**
	 * Sum meta keys, newest first.
	 */
	const SUM_KEYS = array( '_kksr_ratings_default', '_kksr_ratings' );

	/**
	 * Rating repository.
	 *
	 * @var Rating_Repository
	 */
	private $ratings;

	/**
	 * Constructor.
	 *
	 * @param Rating_Repository $ratings Rating repository.
	 */
	public function __construct( Rating_Repository $ratings ) {
		$this->ratings = $ratings;
	}

	/**
	 * Number of posts that have kk Star Ratings data.
	 *
	 * @return int
	 */
	public function count() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- One-off migration query.
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key IN (%s, %s) AND meta_value > 0",
				self::COUNT_KEYS[0],
				self::COUNT_KEYS[1]
			)
		);
	}

	/**
	 * Imports one batch of posts.
	 *
	 * @param int $offset Offset.
	 * @param int $limit  Batch size.
	 * @return array{processed: int, imported: int, total: int, done: bool}
	 */
	public function import_batch( $offset, $limit = 50 ) {
		global $wpdb;

		$total = $this->count();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- One-off migration query.
		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN (%s, %s) AND meta_value > 0 ORDER BY post_id ASC LIMIT %d, %d",
				self::COUNT_KEYS[0],
				self::COUNT_KEYS[1],
				$offset,
				$limit
			)
		);

		$imported = 0;
		foreach ( (array) $post_ids as $post_id ) {
			$post_id = (int) $post_id;
			$count   = (int) $this->first_meta( $post_id, self::COUNT_KEYS );
			$sum     = (float) $this->first_meta( $post_id, self::SUM_KEYS );

			if ( $count > 0 && $sum > 0 ) {
				$this->ratings->set_baseline( $post_id, $count, $sum );
				++$imported;
			}
		}

		$processed = $offset + count( (array) $post_ids );
		$done      = $processed >= $total || empty( $post_ids );

		if ( $done ) {
			update_option( self::DONE_OPTION, time(), false );
		}

		return array(
			'processed' => $processed,
			'imported'  => $imported,
			'total'     => $total,
			'done'      => $done,
		);
	}

	/**
	 * Whether the kk Star Ratings plugin is active.
	 *
	 * @return bool
	 */
	public function is_kk_active() {
		return defined( 'KK_STAR_RATINGS' ) || function_exists( 'kk_star_ratings' );
	}

	/**
	 * Returns the first non-empty meta value among several keys.
	 *
	 * @param int      $post_id Post ID.
	 * @param string[] $keys    Meta keys in priority order.
	 * @return string
	 */
	private function first_meta( $post_id, array $keys ) {
		foreach ( $keys as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			if ( '' !== $value && is_numeric( $value ) && (float) $value > 0 ) {
				return (string) $value;
			}
		}

		return '';
	}
}
