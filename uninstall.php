<?php
/**
 * Uninstall routine. Data is removed only when "Delete data on uninstall" is enabled.
 *
 * @package Karina_Star_Ratings
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// The former "RatingPress" copy shares this data; keep it while that copy is installed.
if ( file_exists( WP_PLUGIN_DIR . '/ratingpress/ratingpress.php' ) ) {
	return;
}

/**
 * Removes every Karina Star Ratings table, option and meta value of the current site.
 *
 * @return void
 */
function ratingpress_uninstall_site() {
	global $wpdb;

	$settings = get_option( 'ratingpress_settings', array() );

	if ( empty( $settings['delete_data'] ) ) {
		return;
	}

	$table = $wpdb->prefix . 'ratingpress_votes';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Dropping the plugin table on uninstall.
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

	$meta_keys = array(
		'_ratingpress_count',
		'_ratingpress_sum',
		'_ratingpress_avg',
		'_ratingpress_base_count',
		'_ratingpress_base_sum',
		'_ratingpress_disabled',
		'_ratingpress_schema_type',
	);

	foreach ( $meta_keys as $meta_key ) {
		delete_post_meta_by_key( $meta_key );
	}

	delete_metadata( 'user', 0, 'ratingpress_dismissed_kk_notice', '', true );

	delete_option( 'ratingpress_settings' );
	delete_option( 'ratingpress_db_version' );
	delete_option( 'ratingpress_kk_imported' );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Removing rate-limit transients on uninstall.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_ratingpress_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_ratingpress_' ) . '%'
		)
	);
}

if ( is_multisite() ) {
	$ratingpress_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $ratingpress_site_ids as $ratingpress_site_id ) {
		switch_to_blog( $ratingpress_site_id );
		ratingpress_uninstall_site();
		restore_current_blog();
	}
} else {
	ratingpress_uninstall_site();
}
