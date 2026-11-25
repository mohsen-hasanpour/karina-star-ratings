<?php
/**
 * Builds the rating widget markup shared by the shortcode, automatic placement,
 * the block, the Elementor widget and the template tags.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Services;

use RatingPress\Core\Icons;
use RatingPress\Core\View;
use RatingPress\Models\Rating;
use RatingPress\Models\Rating_Repository;
use RatingPress\Models\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class Renderer
 */
class Renderer {

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
	 * Shortcode tags that output the rating widget.
	 *
	 * @var string[]
	 */
	private $shortcode_tags = array( 'ratingpress' );

	/**
	 * Posts whose widget was rendered during the current request (post ID => true).
	 *
	 * @var array<int, bool>
	 */
	private $rendered = array();

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
	 * Default render arguments.
	 *
	 * @return array<string, mixed>
	 */
	public function default_args() {
		return array(
			'readonly'    => false,
			'show_legend' => (bool) $this->settings->get( 'show_legend' ),
			'heading'     => (string) $this->settings->get( 'heading' ),
			'icon'        => (string) $this->settings->get( 'icon' ),
			'alignment'   => (string) $this->settings->get( 'alignment' ),
			'layout'      => (string) $this->settings->get( 'layout' ),
			'size'        => 0,
			'class'       => '',
			'context'     => 'shortcode',
		);
	}

	/**
	 * Renders the rating widget for a post.
	 *
	 * @param int   $post_id Post ID, 0 for the current post.
	 * @param array $args    Render arguments, see default_args().
	 * @return string
	 */
	public function render( $post_id = 0, array $args = array() ) {
		if ( ! $this->settings->is_enabled() ) {
			return '';
		}

		$post = get_post( $post_id ? (int) $post_id : null );
		if ( ! $post || $this->ratings->is_disabled( $post->ID ) ) {
			return '';
		}

		$args   = wp_parse_args( $args, $this->default_args() );
		$rating = $this->ratings->find( $post->ID );
		$best   = $rating->best();

		$interactive = ! $args['readonly'] && $this->is_votable( $post );

		$icon      = in_array( $args['icon'], Icons::keys(), true ) ? $args['icon'] : 'star';
		$alignment = in_array( $args['alignment'], array( 'start', 'center', 'end' ), true ) ? $args['alignment'] : 'start';
		$layout    = 'stacked' === $args['layout'] ? 'stacked' : 'inline';

		$classes = array(
			'ratingpress',
			'rp-icon-' . $icon,
			'rp-align-' . $alignment,
			'rp-layout-' . $layout,
			'rp-context-' . sanitize_html_class( $args['context'] ),
			$interactive ? 'is-interactive' : 'is-readonly',
		);
		if ( $this->settings->get( 'animations' ) ) {
			$classes[] = 'has-animations';
		}
		if ( $args['class'] ) {
			$classes = array_merge( $classes, array_map( 'sanitize_html_class', preg_split( '/\s+/', (string) $args['class'] ) ) );
		}

		$stars = array();
		for ( $i = 1; $i <= $best; $i++ ) {
			$stars[] = array(
				'value' => $i,
				'fill'  => $rating->fill( $i ),
				/* translators: 1: rating value, 2: maximum rating. */
				'label' => sprintf( __( 'Rate %1$d out of %2$d', 'karina-star-ratings' ), $i, $best ),
			);
		}

		$size  = absint( $args['size'] );
		$style = $size ? '--rp-size:' . min( 120, $size ) . 'px;' : '';

		$this->enqueue_assets( $interactive );

		$view_args = array(
			'post_id'     => $post->ID,
			'rating'      => $rating,
			'stars'       => $stars,
			'interactive' => $interactive,
			'needs_login' => $interactive && 'logged_in' === $this->settings->get( 'permission' ) && ! is_user_logged_in(),
			'classes'     => array_filter( array_unique( $classes ) ),
			'style'       => $style,
			'heading'     => trim( (string) $args['heading'] ),
			'show_legend' => (bool) $args['show_legend'],
			'legend'      => $this->legend( $rating, $post->ID ),
			'summary'     => $this->summary( $rating ),
			'group_label' => $args['heading'] ? (string) $args['heading'] : __( 'Rate this content', 'karina-star-ratings' ),
			'context'     => $args['context'],
		);

		/**
		 * Filters the data passed to the rating template.
		 *
		 * @param array $view_args View data.
		 * @param int   $post_id   Post ID.
		 * @param array $args      Render arguments.
		 */
		$view_args = (array) apply_filters( 'ratingpress_view_args', $view_args, $post->ID, $args );

		$html = View::render( 'frontend/rating', $view_args, true );

		/**
		 * Filters the final rating HTML.
		 *
		 * @param string $html    Markup.
		 * @param int    $post_id Post ID.
		 * @param array  $args    Render arguments.
		 */
		$html = (string) apply_filters( 'ratingpress_rating_html', $html, $post->ID, $args );

		if ( '' !== trim( $html ) ) {
			$this->rendered[ $post->ID ] = true;
		}

		return $html;
	}

	/**
	 * Whether the widget of a post was rendered during the current request.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function was_rendered( $post_id ) {
		return isset( $this->rendered[ (int) $post_id ] );
	}

	/**
	 * Registers an additional shortcode tag that outputs the widget (e.g. legacy tags).
	 *
	 * @param string $tag Shortcode tag.
	 * @return void
	 */
	public function add_shortcode_tag( $tag ) {
		if ( ! in_array( $tag, $this->shortcode_tags, true ) ) {
			$this->shortcode_tags[] = (string) $tag;
		}
	}

	/**
	 * Whether the content of a post embeds the widget with a shortcode or the block.
	 *
	 * @param \WP_Post $post Post object.
	 * @return bool
	 */
	public function has_embed( $post ) {
		foreach ( $this->shortcode_tags as $tag ) {
			if ( has_shortcode( $post->post_content, $tag ) ) {
				return true;
			}
		}

		return function_exists( 'has_block' ) && has_block( 'ratingpress/rating', $post );
	}

	/**
	 * Whether the widget is automatically added to the content of a post.
	 *
	 * @param \WP_Post $post Post object.
	 * @return bool
	 */
	public function auto_insert_allowed( $post ) {
		$allowed = $this->settings->is_enabled()
			&& 'manual' !== $this->settings->get( 'position' )
			&& in_array( $post->post_type, $this->settings->post_types(), true )
			&& ! in_array( (int) $post->ID, $this->settings->excluded_ids(), true )
			&& ! $this->ratings->is_disabled( $post->ID );

		/**
		 * Filters whether the rating widget is automatically added to a post.
		 *
		 * @param bool     $allowed Whether to insert the widget.
		 * @param \WP_Post $post    Post object.
		 */
		return (bool) apply_filters( 'ratingpress_auto_insert', $allowed, $post );
	}

	/**
	 * Builds the legend text below the stars.
	 *
	 * @param Rating $rating  Rating.
	 * @param int    $post_id Post ID.
	 * @return string Plain text (not escaped).
	 */
	public function legend( Rating $rating, $post_id ) {
		if ( ! $rating->has_votes() ) {
			$post_type = get_post_type_object( (string) get_post_type( $post_id ) );
			$type      = $post_type ? $post_type->labels->singular_name : '';

			return str_replace( '{type}', $type, (string) $this->settings->get( 'greeting' ) );
		}

		$decimals = (int) $this->settings->get( 'decimals', 1 );

		$replacements = array(
			'{avg}'     => number_format_i18n( $rating->rounded( $decimals ), $decimals ),
			'{best}'    => number_format_i18n( $rating->best() ),
			'{count}'   => number_format_i18n( $rating->count() ),
			'{votes}'   => _n( 'vote', 'votes', $rating->count(), 'karina-star-ratings' ),
			'{percent}' => number_format_i18n( $rating->percent() ) . '%',
		);

		return strtr( (string) $this->settings->get( 'legend' ), $replacements );
	}

	/**
	 * Accessible text summary of a rating.
	 *
	 * @param Rating $rating Rating.
	 * @return string
	 */
	public function summary( Rating $rating ) {
		if ( ! $rating->has_votes() ) {
			return __( 'Not rated yet', 'karina-star-ratings' );
		}

		$decimals = (int) $this->settings->get( 'decimals', 1 );

		return sprintf(
			/* translators: 1: average rating, 2: maximum rating, 3: number of votes. */
			_n( 'Rated %1$s out of %2$s based on %3$s vote', 'Rated %1$s out of %2$s based on %3$s votes', $rating->count(), 'karina-star-ratings' ),
			number_format_i18n( $rating->rounded( $decimals ), $decimals ),
			number_format_i18n( $rating->best() ),
			number_format_i18n( $rating->count() )
		);
	}

	/**
	 * Whether a post currently accepts votes (regardless of the visitor).
	 *
	 * @param \WP_Post $post Post object.
	 * @return bool
	 */
	public function is_votable( $post ) {
		return 'publish' === $post->post_status
			&& is_post_type_viewable( $post->post_type )
			&& ! post_password_required( $post )
			&& ! $this->ratings->is_disabled( $post->ID );
	}

	/**
	 * Enqueues the front-end assets.
	 *
	 * @param bool $with_script Whether the voting script is needed.
	 * @return void
	 */
	public function enqueue_assets( $with_script = true ) {
		wp_enqueue_style( 'ratingpress' );

		if ( $with_script ) {
			wp_enqueue_script( 'ratingpress' );
		}
	}
}
