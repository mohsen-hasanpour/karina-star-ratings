<?php
/**
 * Site-wide rating statistics for the admin dashboard.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Class Stats_Repository
 */
class Stats_Repository {

	/**
	 * Settings model.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings model.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Overview numbers.
	 *
	 * @return array{votes: int, posts: int, average: float}
	 */
	public function overview() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Aggregate over plugin meta for an admin-only screen.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(c.post_id) AS posts, COALESCE(SUM(c.meta_value), 0) AS votes, COALESCE(SUM(s.meta_value), 0) AS score
				FROM {$wpdb->postmeta} c
				INNER JOIN {$wpdb->posts} p ON p.ID = c.post_id AND p.post_status = 'publish'
				LEFT JOIN {$wpdb->postmeta} s ON s.post_id = c.post_id AND s.meta_key = %s
				WHERE c.meta_key = %s AND c.meta_value > 0",
				Rating_Repository::META_SUM,
				Rating_Repository::META_COUNT
			)
		);

		$votes = $row ? (int) $row->votes : 0;
		$score = $row ? (float) $row->score : 0.0;

		return array(
			'votes'   => $votes,
			'posts'   => $row ? (int) $row->posts : 0,
			'average' => $votes ? round( ( $score / $votes ) * $this->settings->best() / Rating::SCALE, 2 ) : 0.0,
		);
	}

	/**
	 * Top rated published posts.
	 *
	 * @param int    $limit     Number of posts.
	 * @param string $post_type Post type or 'any'.
	 * @param int    $min_votes Minimum number of votes.
	 * @return \WP_Post[]
	 */
	public function top_rated( $limit = 5, $post_type = 'any', $min_votes = 1 ) {
		$query = new \WP_Query(
			array(
				'post_type'           => $post_type,
				'post_status'         => 'publish',
				'posts_per_page'      => max( 1, (int) $limit ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Sorting by rating requires a meta query.
				'meta_query'          => array(
					'relation' => 'AND',
					'rp_avg'   => array(
						'key'  => Rating_Repository::META_AVG,
						'type' => 'DECIMAL(10,4)',
					),
					'rp_count' => array(
						'key'     => Rating_Repository::META_COUNT,
						'value'   => max( 1, (int) $min_votes ),
						'compare' => '>=',
						'type'    => 'NUMERIC',
					),
				),
				'orderby'             => array(
					'rp_avg'   => 'DESC',
					'rp_count' => 'DESC',
				),
			)
		);

		return $query->posts;
	}
}
