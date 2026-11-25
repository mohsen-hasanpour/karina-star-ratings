<?php
/**
 * Plugin Name:       Karina Star Ratings & Rich Snippets
 * Plugin URI:        https://karinaco.net/
 * Description:       Fast, accessible star ratings for any post type with automatic Schema.org AggregateRating rich snippets, a shortcode, a block and an Elementor widget.
 * Version:           1.0.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Mohsen Hasanpour
 * Author URI:        https://mohsen-hasanpour.ir
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       karina-star-ratings
 *
 * @package Karina_Star_Ratings
 */

defined( 'ABSPATH' ) || exit;

// The plugin was formerly named "RatingPress" (ratingpress/ratingpress.php) and shares its data.
// While that copy is still active, stay idle and ask the admin to deactivate it.
if ( defined( 'RATINGPRESS_VERSION' ) || in_array( 'ratingpress/ratingpress.php', (array) get_option( 'active_plugins', array() ), true ) ) {
	add_action(
		'admin_notices',
		static function () {
			if ( current_user_can( 'activate_plugins' ) ) {
				echo '<div class="notice notice-warning"><p>' . esc_html__( 'Karina Star Ratings replaces the old RatingPress plugin. Deactivate RatingPress; your ratings and settings are kept. Until then, Karina Star Ratings does nothing.', 'karina-star-ratings' ) . '</p></div>';
			}
		}
	);
	return;
}

define( 'RATINGPRESS_VERSION', '1.0.1' );
define( 'RATINGPRESS_DB_VERSION', '1.0.0' );
define( 'RATINGPRESS_FILE', __FILE__ );
define( 'RATINGPRESS_PATH', plugin_dir_path( __FILE__ ) );
define( 'RATINGPRESS_URL', plugin_dir_url( __FILE__ ) );
define( 'RATINGPRESS_BASENAME', plugin_basename( __FILE__ ) );

require_once RATINGPRESS_PATH . 'app/Core/class-autoloader.php';

RatingPress\Core\Autoloader::register();

require_once RATINGPRESS_PATH . 'app/functions.php';

register_activation_hook( __FILE__, array( 'RatingPress\Core\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'RatingPress\Core\Installer', 'deactivate' ) );

add_action( 'plugins_loaded', array( RatingPress\Core\Plugin::instance(), 'boot' ) );
