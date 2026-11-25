<?php
/**
 * Sortable "Rating" column in the posts list tables.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Controllers;

use RatingPress\Models\Rating_Repository;
use RatingPress\Models\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class Columns_Controller
 */
class Columns_Controller {

	/**
	 * Column key.
	 */
	const COLUMN = 'ratingpress';

	/**
	 * Settings model.
	 *
	 * @var Settings
	 */
	private $settings;

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
	 * @param Rating_Repository $ratings  Rating repository.
	 */
	public function __construct( Settings $settings, Rating_Repository $ratings ) {
		$this->settings = $settings;
		$this->ratings  = $ratings;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'register_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_query' ) );
	}

	/**
	 * Adds the column to each enabled post type.
	 *
	 * @return void
	 */
	public function register_columns() {
		foreach ( $this->settings->post_types() as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_column' ) );
			add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_column' ), 10, 2 );
			add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'sortable' ) );
		}
	}

	/**
	 * Adds the column header.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function add_column( $columns ) {
		$columns[ self::COLUMN ] = '<span class="dashicons dashicons-star-filled" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html__( 'Rating', 'karina-star-ratings' ) . '</span>';

		return $columns;
	}

	/**
	 * Renders a column cell.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_column( $column, $post_id ) {
		if ( self::COLUMN !== $column ) {
			return;
		}

		if ( $this->ratings->is_disabled( $post_id ) ) {
			echo '<span class="rp-col-muted" title="' . esc_attr__( 'Ratings disabled', 'karina-star-ratings' ) . '">—</span>';
			return;
		}

		$rating = $this->ratings->find( $post_id );

		if ( ! $rating->has_votes() ) {
			echo '<span class="rp-col-muted">' . esc_html__( 'No votes', 'karina-star-ratings' ) . '</span>';
			return;
		}

		printf(
			'<span class="rp-col"><span class="rp-col-score"><span class="dashicons dashicons-star-filled" aria-hidden="true"></span>%1$s</span><span class="rp-col-count">%2$s</span></span>',
			esc_html( number_format_i18n( $rating->rounded( 1 ), 1 ) ),
			esc_html(
				sprintf(
					/* translators: %s: number of votes. */
					_n( '%s vote', '%s votes', $rating->count(), 'karina-star-ratings' ),
					number_format_i18n( $rating->count() )
				)
			)
		);
	}

	/**
	 * Marks the column as sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public function sortable( $columns ) {
		$columns[ self::COLUMN ] = array( self::COLUMN, true );

		return $columns;
	}

	/**
	 * Applies the rating sort order to the admin list query.
	 *
	 * @param \WP_Query $query Query.
	 * @return void
	 */
	public function sort_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || self::COLUMN !== $query->get( 'orderby' ) ) {
			return;
		}

		$order = 'ASC' === strtoupper( (string) $query->get( 'order' ) ) ? 'ASC' : 'DESC';

		$query->set(
			'meta_query',
			array(
				'relation'  => 'OR',
				'rp_avg'    => array(
					'key'     => Rating_Repository::META_AVG,
					'compare' => 'EXISTS',
					'type'    => 'DECIMAL(10,4)',
				),
				'rp_no_avg' => array(
					'key'     => Rating_Repository::META_AVG,
					'compare' => 'NOT EXISTS',
				),
			)
		);
		$query->set(
			'orderby',
			array(
				'rp_avg' => $order,
				'date'   => 'DESC',
			)
		);
	}
}
