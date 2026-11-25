<?php
/**
 * Minimal view renderer.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class View
 */
final class View {

	/**
	 * Renders a template from the views directory and returns the output.
	 *
	 * Templates receive their data in a local `$args` array. Front-end templates may be
	 * overridden by a theme by copying them to `your-theme/ratingpress/{template}.php`.
	 *
	 * @param string $template       Template path relative to /views, without extension.
	 * @param array  $args           Data passed to the template.
	 * @param bool   $theme_override Whether the theme may override the template.
	 * @return string
	 */
	public static function render( $template, array $args = array(), $theme_override = false ) {
		$file = self::locate( $template, $theme_override );

		if ( '' === $file ) {
			return '';
		}

		ob_start();
		include $file;
		return (string) ob_get_clean();
	}

	/**
	 * Echoes a template. Templates are responsible for escaping their own output.
	 *
	 * @param string $template Template path relative to /views.
	 * @param array  $args     Data passed to the template.
	 * @return void
	 */
	public static function display( $template, array $args = array() ) {
		echo self::render( $template, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the template.
	}

	/**
	 * Resolves the absolute path of a template.
	 *
	 * @param string $template       Template name.
	 * @param bool   $theme_override Whether the theme may override the template.
	 * @return string
	 */
	private static function locate( $template, $theme_override ) {
		$template = trim( str_replace( array( '..', '\\' ), array( '', '/' ), $template ), '/' );

		if ( $theme_override ) {
			$theme_file = locate_template( 'ratingpress/' . basename( $template ) . '.php' );

			/**
			 * Filters the template file used to render a front-end view.
			 *
			 * @param string $theme_file Template path found in the theme, or empty string.
			 * @param string $template   Template name.
			 */
			$theme_file = (string) apply_filters( 'ratingpress_template', $theme_file, $template );

			if ( '' !== $theme_file && is_readable( $theme_file ) ) {
				return $theme_file;
			}
		}

		$file = RATINGPRESS_PATH . 'views/' . $template . '.php';

		return is_readable( $file ) ? $file : '';
	}
}
