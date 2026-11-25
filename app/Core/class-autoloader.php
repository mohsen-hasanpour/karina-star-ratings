<?php
/**
 * PSR-4 style autoloader that follows the WordPress file naming conventions.
 *
 * RatingPress\Models\Vote_Repository => app/Models/class-vote-repository.php
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Autoloader
 */
final class Autoloader {

	/**
	 * Root namespace handled by this autoloader.
	 */
	const PREFIX = 'RatingPress\\';

	/**
	 * Registers the autoloader with SPL.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Loads a class file.
	 *
	 * @param string $class_name Fully qualified class name.
	 * @return void
	 */
	public static function load( $class_name ) {
		if ( 0 !== strpos( $class_name, self::PREFIX ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( self::PREFIX ) );
		$parts    = explode( '\\', $relative );
		$class    = array_pop( $parts );
		$file     = 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
		$path     = RATINGPRESS_PATH . 'app/' . ( $parts ? implode( '/', $parts ) . '/' : '' ) . $file;

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
