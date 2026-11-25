<?php
/**
 * Prints the JSON-LD rich snippet of the current post.
 *
 * Structured data must describe content that visitors can see, so the snippet is only
 * printed when the rating widget of the post is displayed on the page:
 *
 * - In the head, when the widget is expected (automatic placement, shortcode or block)
 *   or was already rendered (block themes render the template before the head).
 * - In the footer, when the widget was rendered later in the page, for example by a
 *   theme template tag, a widget area or a page builder.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Controllers;

use RatingPress\Models\Settings;
use RatingPress\Services\Renderer;
use RatingPress\Services\Schema_Builder;

defined( 'ABSPATH' ) || exit;

/**
 * Class Schema_Controller
 */
class Schema_Controller {

	/**
	 * Settings model.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Schema builder.
	 *
	 * @var Schema_Builder
	 */
	private $builder;

	/**
	 * Renderer service.
	 *
	 * @var Renderer
	 */
	private $renderer;

	/**
	 * Whether the snippet was already printed during this request.
	 *
	 * @var bool
	 */
	private $printed = false;

	/**
	 * Constructor.
	 *
	 * @param Settings       $settings Settings model.
	 * @param Schema_Builder $builder  Schema builder.
	 * @param Renderer       $renderer Renderer service.
	 */
	public function __construct( Settings $settings, Schema_Builder $builder, Renderer $renderer ) {
		$this->settings = $settings;
		$this->builder  = $builder;
		$this->renderer = $renderer;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_head', array( $this, 'print_in_head' ), 30 );
		add_action( 'wp_footer', array( $this, 'print_in_footer' ), 5 );
	}

	/**
	 * Prints the snippet in the head when the widget is expected on the page.
	 *
	 * @return void
	 */
	public function print_in_head() {
		$post = $this->current_post();
		if ( ! $post ) {
			return;
		}

		$expected = $this->renderer->was_rendered( $post->ID )
			|| $this->renderer->has_embed( $post )
			|| $this->renderer->auto_insert_allowed( $post );

		$this->maybe_print( $post, $expected, 'head' );
	}

	/**
	 * Prints the snippet in the footer when the widget was rendered after the head.
	 *
	 * @return void
	 */
	public function print_in_footer() {
		$post = $this->current_post();
		if ( ! $post ) {
			return;
		}

		$this->maybe_print( $post, $this->renderer->was_rendered( $post->ID ), 'footer' );
	}

	/**
	 * Queried post when structured data may be printed, null otherwise.
	 *
	 * @return \WP_Post|null
	 */
	private function current_post() {
		if ( $this->printed || ! $this->settings->is_enabled() || ! $this->settings->get( 'schema_enabled' ) || ! is_singular() ) {
			return null;
		}

		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post || post_password_required( $post ) ) {
			return null;
		}

		return $post;
	}

	/**
	 * Outputs the AggregateRating schema when allowed.
	 *
	 * @param \WP_Post $post     Queried post.
	 * @param bool     $shown    Whether the rating widget is displayed on the page.
	 * @param string   $location Output location: head or footer.
	 * @return void
	 */
	private function maybe_print( $post, $shown, $location ) {
		/**
		 * Filters whether the JSON-LD snippet of the queried post is printed.
		 *
		 * By default it is printed only when the rating widget of the post is shown on
		 * the page: in the head when the widget is expected (automatic placement,
		 * shortcode or block) and in the footer when the widget was rendered later, for
		 * example by ratingpress_the_rating() in a theme template.
		 *
		 * @param bool     $shown    Whether to print the snippet at this location.
		 * @param \WP_Post $post     Queried post.
		 * @param string   $location "head" or "footer".
		 */
		if ( ! apply_filters( 'ratingpress_print_schema', $shown, $post, $location ) ) {
			return;
		}

		$schema = $this->builder->build( $post->ID );
		if ( ! $schema ) {
			return;
		}

		$this->printed = true;

		wp_print_inline_script_tag(
			Schema_Builder::encode( $schema ),
			array(
				'type' => 'application/ld+json',
				'id'   => 'ratingpress-schema',
			)
		);
	}
}
