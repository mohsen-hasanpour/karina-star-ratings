<?php
/**
 * Public template tags for themes and other plugins.
 *
 * @package Karina_Star_Ratings
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'ratingpress' ) ) {
	/**
	 * Returns the Karina Star Ratings plugin instance.
	 *
	 * @return RatingPress\Core\Plugin
	 */
	function ratingpress() {
		return RatingPress\Core\Plugin::instance();
	}
}

/**
 * Returns the aggregated rating data of a post.
 *
 * @param int $post_id Post ID. Defaults to the current post.
 * @return array{post_id: int, average: float, count: int, best: int, percent: float, fills: float[]}
 */
function ratingpress_get_rating( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();

	return ratingpress()->ratings()->find( $post_id )->to_array( (int) ratingpress()->settings()->get( 'decimals', 1 ) );
}

/**
 * Returns the rating widget HTML of a post.
 *
 * @param int   $post_id Post ID. Defaults to the current post.
 * @param array $args    {
 *     Optional. Display arguments.
 *
 *     @type bool   $readonly    Display only, without voting.
 *     @type bool   $show_legend Show the text below the stars.
 *     @type string $heading     Heading text.
 *     @type string $icon        star, heart or circle.
 *     @type string $alignment   start, center or end.
 *     @type string $layout      inline or stacked.
 *     @type int    $size        Icon size in pixels.
 *     @type string $class       Extra CSS classes.
 * }
 * @return string
 */
function ratingpress_get_html( $post_id = 0, $args = array() ) {
	$args            = (array) $args;
	$args['context'] = 'template';

	return ratingpress()->renderer()->render( (int) $post_id, $args );
}

/**
 * Prints the rating widget of a post.
 *
 * @param int   $post_id Post ID. Defaults to the current post.
 * @param array $args    Display arguments, see ratingpress_get_html().
 * @return void
 */
function ratingpress_the_rating( $post_id = 0, $args = array() ) {
	echo ratingpress_get_html( $post_id, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the rating template.
}
