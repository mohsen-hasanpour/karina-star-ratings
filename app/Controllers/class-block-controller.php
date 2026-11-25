<?php
/**
 * Registers the dynamic "Karina Star Rating" block.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Controllers;

use RatingPress\Services\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Class Block_Controller
 */
class Block_Controller {

	/**
	 * Renderer service.
	 *
	 * @var Renderer
	 */
	private $renderer;

	/**
	 * Constructor.
	 *
	 * @param Renderer $renderer Renderer service.
	 */
	public function __construct( Renderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Registers the block from its block.json metadata.
	 *
	 * @return void
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			RATINGPRESS_PATH . 'blocks/rating',
			array(
				'render_callback' => array( $this, 'render' ),
			)
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'ratingpress-rating-editor-script', 'karina-star-ratings' );
		}
	}

	/**
	 * Server-side render callback.
	 *
	 * @param array          $attributes Block attributes.
	 * @param string         $content    Block content.
	 * @param \WP_Block|null $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content = '', $block = null ) {
		$post_id = ! empty( $attributes['postId'] ) ? absint( $attributes['postId'] ) : 0;

		if ( ! $post_id && $block instanceof \WP_Block && ! empty( $block->context['postId'] ) ) {
			$post_id = absint( $block->context['postId'] );
		}

		$args = array(
			'context'  => 'block',
			'readonly' => ! empty( $attributes['readonly'] ),
		);

		if ( isset( $attributes['showLegend'] ) ) {
			$args['show_legend'] = (bool) $attributes['showLegend'];
		}
		if ( ! empty( $attributes['heading'] ) ) {
			$args['heading'] = sanitize_text_field( $attributes['heading'] );
		}
		if ( ! empty( $attributes['alignment'] ) ) {
			$args['alignment'] = sanitize_key( $attributes['alignment'] );
		}
		if ( ! empty( $attributes['size'] ) ) {
			$args['size'] = absint( $attributes['size'] );
		}

		$html = $this->renderer->render( $post_id, $args );

		if ( '' === $html ) {
			return '';
		}

		$wrapper = function_exists( 'get_block_wrapper_attributes' ) ? get_block_wrapper_attributes() : 'class="wp-block-ratingpress-rating"';

		return '<div ' . $wrapper . '>' . $html . '</div>';
	}
}
