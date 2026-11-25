<?php
/**
 * Icon shapes used for rating symbols.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Icons
 */
final class Icons {

	/**
	 * Returns the available icon shapes keyed by slug.
	 *
	 * @return array<string, array{label: string, svg: string}>
	 */
	public static function all() {
		return array(
			'star'   => array(
				'label' => __( 'Star', 'karina-star-ratings' ),
				'svg'   => '<path d="M12 2.5l2.94 5.96 6.58.96-4.76 4.64 1.12 6.55L12 17.52 6.12 20.61l1.12-6.55L2.48 9.42l6.58-.96z"/>',
			),
			'heart'  => array(
				'label' => __( 'Heart', 'karina-star-ratings' ),
				'svg'   => '<path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54z"/>',
			),
			'circle' => array(
				'label' => __( 'Circle', 'karina-star-ratings' ),
				'svg'   => '<circle cx="12" cy="12" r="9.5"/>',
			),
		);
	}

	/**
	 * Icon slugs.
	 *
	 * @return string[]
	 */
	public static function keys() {
		return array( 'star', 'heart', 'circle' );
	}

	/**
	 * Returns an inline SVG for an icon (used in the admin UI).
	 *
	 * @param string $icon Icon slug.
	 * @return string
	 */
	public static function svg( $icon ) {
		$icons = self::all();
		$shape = isset( $icons[ $icon ] ) ? $icons[ $icon ]['svg'] : $icons['star']['svg'];

		return '<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor" aria-hidden="true" focusable="false">' . $shape . '</svg>';
	}
}
