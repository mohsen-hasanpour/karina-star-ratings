<?php
/**
 * Activation, deactivation and database migrations.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Core;

use RatingPress\Models\Settings;
use RatingPress\Models\Vote_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Installer
 */
final class Installer {

	/**
	 * Option that stores the installed database schema version.
	 */
	const DB_VERSION_OPTION = 'ratingpress_db_version';

	/**
	 * Activation callback.
	 *
	 * @param bool $network_wide Whether the plugin is being network activated.
	 * @return void
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			);

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				self::install();
				restore_current_blog();
			}

			return;
		}

		self::install();
	}

	/**
	 * Deactivation callback.
	 *
	 * Data is intentionally kept; it is removed in uninstall.php when requested.
	 *
	 * @return void
	 */
	public static function deactivate() {
		if ( function_exists( 'wp_cache_flush_group' ) ) {
			wp_cache_flush_group( 'ratingpress' );
		}
	}

	/**
	 * Runs the installer when the stored schema version is outdated.
	 *
	 * Also covers sites that are added to a network after activation.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		if ( wp_installing() ) {
			return;
		}

		if ( get_option( self::DB_VERSION_OPTION ) !== RATINGPRESS_DB_VERSION ) {
			self::install();
		}
	}

	/**
	 * Creates the database table and default options for the current site.
	 *
	 * @return void
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table           = Vote_Repository::table();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			rating tinyint(3) unsigned NOT NULL,
			best tinyint(3) unsigned NOT NULL DEFAULT 5,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			ip_hash char(64) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY post_ip (post_id,ip_hash),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql );

		if ( false === get_option( Settings::OPTION ) ) {
			add_option( Settings::OPTION, Settings::defaults() );
		}

		// Autoloaded: maybe_upgrade() reads it on every request.
		update_option( self::DB_VERSION_OPTION, RATINGPRESS_DB_VERSION, true );
	}
}
