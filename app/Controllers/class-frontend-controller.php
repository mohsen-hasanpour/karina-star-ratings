<?php
/**
 * Front-end controller: assets, automatic placement and shortcodes.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Controllers;

use RatingPress\Core\Plugin;
use RatingPress\Core\View;
use RatingPress\Models\Rating_Repository;
use RatingPress\Models\Settings;
use RatingPress\Services\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Class Frontend_Controller
 */
class Frontend_Controller {

	/**
	 * Settings model.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Renderer service.
	 *
	 * @var Renderer
	 */
	private $renderer;

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
	 * @param Renderer          $renderer Renderer service.
	 * @param Rating_Repository $ratings  Rating repository.
	 */
	public function __construct( Settings $settings, Renderer $renderer, Rating_Repository $ratings ) {
		$this->settings = $settings;
		$this->renderer = $renderer;
		$this->ratings  = $ratings;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_assets' ), 5 );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'init', array( $this, 'register_legacy_shortcodes' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'the_content', array( $this, 'auto_insert' ), 20 );
	}

	/**
	 * Registers the front-end stylesheet and script.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style( 'ratingpress', RATINGPRESS_URL . 'assets/css/frontend.css', array(), RATINGPRESS_VERSION );
		wp_add_inline_style( 'ratingpress', $this->css_variables() );

		wp_register_script(
			'ratingpress',
			RATINGPRESS_URL . 'assets/js/frontend.js',
			array(),
			RATINGPRESS_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		$config = array(
			'restUrl'     => esc_url_raw( rest_url( 'ratingpress/v1/' ) ),
			'nonce'       => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
			'restriction' => (string) $this->settings->get( 'restriction' ),
			'loginUrl'    => wp_login_url(),
			'i18n'        => array(
				'thanks'   => (string) $this->settings->get( 'msg_thanks' ),
				'voted'    => (string) $this->settings->get( 'msg_voted' ),
				'login'    => (string) $this->settings->get( 'msg_login' ),
				'loginCta' => __( 'Log in', 'karina-star-ratings' ),
				'error'    => __( 'Something went wrong. Please try again.', 'karina-star-ratings' ),
				'sending'  => __( 'Saving your vote…', 'karina-star-ratings' ),
			),
		);

		wp_add_inline_script( 'ratingpress', 'window.ratingpressConfig = ' . wp_json_encode( $config ) . ';', 'before' );
	}

	/**
	 * Global CSS custom properties built from the appearance settings.
	 *
	 * @return string
	 */
	private function css_variables() {
		return sprintf(
			':root{--rp-size:%1$dpx;--rp-gap:%2$dpx;--rp-active:%3$s;--rp-inactive:%4$s;--rp-hover:%5$s;}',
			(int) $this->settings->get( 'size' ),
			(int) $this->settings->get( 'gap' ),
			sanitize_hex_color( $this->settings->get( 'color_active' ) ),
			sanitize_hex_color( $this->settings->get( 'color_inactive' ) ),
			sanitize_hex_color( $this->settings->get( 'color_hover' ) )
		);
	}

	/**
	 * Enqueues assets early on pages that will display a rating, avoiding a flash of
	 * unstyled content. Other pages load the assets on demand when a rating renders.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! $this->settings->is_enabled() || ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		// The top rated list only needs the stylesheet.
		if ( has_shortcode( $post->post_content, 'ratingpress_top' ) ) {
			wp_enqueue_style( 'ratingpress' );
		}

		// Enabled post types may also print the widget from a theme template tag,
		// so their assets are loaded even when automatic placement is off.
		$needed = $this->renderer->has_embed( $post )
			|| ( in_array( $post->post_type, $this->settings->post_types(), true ) && ! $this->ratings->is_disabled( $post->ID ) );

		/**
		 * Filters whether the rating assets are enqueued in the head of a singular page.
		 *
		 * @param bool     $needed Whether to enqueue the stylesheet and script early.
		 * @param \WP_Post $post   Queried post.
		 */
		if ( apply_filters( 'ratingpress_enqueue_assets', $needed, $post ) ) {
			$this->renderer->enqueue_assets( true );
		}
	}

	/**
	 * Adds the rating widget to the post content.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function auto_insert( $content ) {
		if ( is_admin() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $content;
		}

		if ( doing_filter( 'get_the_excerpt' ) || doing_filter( 'wp_trim_excerpt' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post = get_post();
		if ( ! $post ) {
			return $content;
		}

		$singular = is_singular();

		if ( $singular && get_queried_object_id() !== $post->ID ) {
			return $content;
		}

		if ( ! $singular && ! $this->settings->get( 'show_in_archives' ) ) {
			return $content;
		}

		if ( ! $this->renderer->auto_insert_allowed( $post ) || $this->renderer->has_embed( $post ) ) {
			return $content;
		}

		$html = $this->renderer->render(
			$post->ID,
			array(
				'context'  => 'auto',
				'readonly' => ! $singular,
			)
		);

		switch ( $this->settings->get( 'position' ) ) {
			case 'before':
				return $html . $content;
			case 'both':
				return $html . $content . $html;
			default:
				return $content . $html;
		}
	}

	/**
	 * Registers the plugin shortcodes.
	 *
	 * @return void
	 */
	public function register_shortcodes() {
		add_shortcode( 'ratingpress', array( $this, 'shortcode_rating' ) );
		add_shortcode( 'ratingpress_top', array( $this, 'shortcode_top' ) );
	}

	/**
	 * Maps the kk Star Ratings shortcodes to Karina Star Ratings when that plugin is gone.
	 *
	 * @return void
	 */
	public function register_legacy_shortcodes() {
		if ( ! $this->settings->get( 'kk_compat' ) ) {
			return;
		}

		foreach ( array( 'kkstarratings', 'kkratings', 'kk-star-ratings' ) as $tag ) {
			if ( ! shortcode_exists( $tag ) ) {
				add_shortcode( $tag, array( $this, 'shortcode_legacy' ) );
				$this->renderer->add_shortcode_tag( $tag );
			}
		}
	}

	/**
	 * [ratingpress] shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_rating( $atts ) {
		$defaults = $this->renderer->default_args();

		$atts = shortcode_atts(
			array(
				'id'       => 0,
				'readonly' => 'false',
				'legend'   => $defaults['show_legend'] ? 'true' : 'false',
				'heading'  => $defaults['heading'],
				'icon'     => $defaults['icon'],
				'align'    => $defaults['alignment'],
				'layout'   => $defaults['layout'],
				'size'     => 0,
				'class'    => '',
			),
			$atts,
			'ratingpress'
		);

		return $this->renderer->render(
			absint( $atts['id'] ),
			array(
				'readonly'    => $this->to_bool( $atts['readonly'] ),
				'show_legend' => $this->to_bool( $atts['legend'] ),
				'heading'     => sanitize_text_field( $atts['heading'] ),
				'icon'        => sanitize_key( $atts['icon'] ),
				'alignment'   => sanitize_key( $atts['align'] ),
				'layout'      => sanitize_key( $atts['layout'] ),
				'size'        => absint( $atts['size'] ),
				'class'       => sanitize_text_field( $atts['class'] ),
				'context'     => 'shortcode',
			)
		);
	}

	/**
	 * Legacy kk Star Ratings shortcodes.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_legacy( $atts ) {
		$atts = is_array( $atts ) ? $atts : array();
		$map  = array( 'id' => isset( $atts['id'] ) ? $atts['id'] : 0 );

		if ( isset( $atts['readonly'] ) ) {
			$map['readonly'] = $atts['readonly'];
		}
		if ( isset( $atts['align'] ) ) {
			$align        = strtolower( (string) $atts['align'] );
			$map['align'] = 'left' === $align ? 'start' : ( 'right' === $align ? 'end' : $align );
		}

		return $this->shortcode_rating( $map );
	}

	/**
	 * [ratingpress_top] shortcode: list of top rated posts.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_top( $atts ) {
		$atts = shortcode_atts(
			array(
				'count'     => 5,
				'post_type' => 'post',
				'min_votes' => 1,
				'thumbnail' => 'true',
			),
			$atts,
			'ratingpress_top'
		);

		// Only public post types may be listed; "any" means all of them.
		$public_types = array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) );
		$post_types   = array_filter( array_map( 'sanitize_key', explode( ',', (string) $atts['post_type'] ) ) );
		$post_types   = in_array( 'any', $post_types, true ) ? $public_types : array_intersect( $post_types, $public_types );

		if ( ! $post_types ) {
			return '';
		}

		$posts = Plugin::instance()->stats()->top_rated( min( 50, absint( $atts['count'] ) ), array_values( $post_types ), absint( $atts['min_votes'] ) );

		if ( ! $posts ) {
			return '';
		}

		wp_enqueue_style( 'ratingpress' );

		$items = array();
		foreach ( $posts as $post ) {
			$rating  = $this->ratings->find( $post->ID );
			$items[] = array(
				'post'    => $post,
				'rating'  => $rating,
				'summary' => $this->renderer->summary( $rating ),
				'legend'  => $this->renderer->legend( $rating, $post->ID ),
			);
		}

		return View::render(
			'frontend/top-rated',
			array(
				'items'     => $items,
				'thumbnail' => $this->to_bool( $atts['thumbnail'] ),
				'icon'      => (string) $this->settings->get( 'icon' ),
			),
			true
		);
	}

	/**
	 * Converts a shortcode attribute to boolean.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private function to_bool( $value ) {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}
}
