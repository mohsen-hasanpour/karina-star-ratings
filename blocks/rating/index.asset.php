<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- index.asset.php is the block asset naming convention.
/**
 * Editor script dependencies for the Karina Star Ratings block.
 *
 * @package Karina_Star_Ratings
 */

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-i18n', 'wp-server-side-render' ),
	'version'      => '1.0.0',
);
