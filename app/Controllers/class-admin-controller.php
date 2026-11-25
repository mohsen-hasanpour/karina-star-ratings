<?php
/**
 * Admin controller: menu, settings screen, dashboard, assets and notices.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Controllers;

use RatingPress\Core\Icons;
use RatingPress\Core\Plugin;
use RatingPress\Core\View;
use RatingPress\Models\Settings;
use RatingPress\Models\Stats_Repository;
use RatingPress\Services\Kk_Importer;
use RatingPress\Services\Schema_Builder;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin_Controller
 */
class Admin_Controller {

	/**
	 * Capability required to manage the plugin.
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Dashboard page slug.
	 */
	const PAGE_DASHBOARD = 'ratingpress';

	/**
	 * Settings page slug.
	 */
	const PAGE_SETTINGS = 'ratingpress-settings';

	/**
	 * User meta flag for the dismissed import notice.
	 */
	const DISMISS_META = 'ratingpress_dismissed_kk_notice';

	/**
	 * Settings model.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Stats repository.
	 *
	 * @var Stats_Repository
	 */
	private $stats;

	/**
	 * Importer.
	 *
	 * @var Kk_Importer
	 */
	private $importer;

	/**
	 * Schema builder.
	 *
	 * @var Schema_Builder
	 */
	private $schema;

	/**
	 * Hook suffixes of the plugin pages.
	 *
	 * @var string[]
	 */
	private $hooks = array();

	/**
	 * Constructor.
	 *
	 * @param Settings         $settings Settings model.
	 * @param Stats_Repository $stats    Stats repository.
	 * @param Kk_Importer      $importer Importer.
	 * @param Schema_Builder   $schema   Schema builder.
	 */
	public function __construct( Settings $settings, Stats_Repository $stats, Kk_Importer $importer, Schema_Builder $schema ) {
		$this->settings = $settings;
		$this->stats    = $stats;
		$this->importer = $importer;
		$this->schema   = $schema;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'import_notice' ) );
		add_action( 'admin_post_ratingpress_dismiss_notice', array( $this, 'dismiss_notice' ) );
		add_filter( 'plugin_action_links_' . RATINGPRESS_BASENAME, array( $this, 'action_links' ) );
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Adds the admin menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		$this->hooks['dashboard'] = (string) add_menu_page(
			__( 'Karina Star Ratings', 'karina-star-ratings' ),
			__( 'Karina Star Ratings', 'karina-star-ratings' ),
			self::CAPABILITY,
			self::PAGE_DASHBOARD,
			array( $this, 'render_dashboard' ),
			'dashicons-star-filled',
			58
		);

		add_submenu_page(
			self::PAGE_DASHBOARD,
			__( 'Karina Star Ratings Dashboard', 'karina-star-ratings' ),
			__( 'Dashboard', 'karina-star-ratings' ),
			self::CAPABILITY,
			self::PAGE_DASHBOARD,
			array( $this, 'render_dashboard' )
		);

		$this->hooks['settings'] = (string) add_submenu_page(
			self::PAGE_DASHBOARD,
			__( 'Karina Star Ratings Settings', 'karina-star-ratings' ),
			__( 'Settings', 'karina-star-ratings' ),
			self::CAPABILITY,
			self::PAGE_SETTINGS,
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Registers the option with the Settings API.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			Settings::GROUP,
			Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Settings::class, 'sanitize' ),
				'default'           => Settings::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Loads admin assets on plugin screens and post screens.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && in_array( $screen->base, array( 'post', 'edit' ), true ) && in_array( $screen->post_type, $this->settings->post_types(), true ) ) {
			wp_enqueue_style( 'ratingpress-admin-post', RATINGPRESS_URL . 'assets/css/admin-post.css', array( 'ratingpress' ), RATINGPRESS_VERSION );
		}

		if ( ! $this->is_plugin_screen( $hook_suffix ) ) {
			return;
		}

		add_action( 'admin_head', array( $this, 'preload_font' ) );

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'ratingpress' );
		wp_enqueue_style( 'ratingpress-admin', RATINGPRESS_URL . 'assets/css/admin.css', array( 'ratingpress' ), RATINGPRESS_VERSION );

		wp_enqueue_script(
			'ratingpress-admin',
			RATINGPRESS_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker', 'wp-api-fetch' ),
			RATINGPRESS_VERSION,
			true
		);

		wp_localize_script(
			'ratingpress-admin',
			'ratingpressAdmin',
			array(
				'sample' => array(
					'average' => 3.7,
					'count'   => 128,
				),
				'i18n'   => array(
					'vote'           => _n( 'vote', 'votes', 1, 'karina-star-ratings' ),
					'votes'          => _n( 'vote', 'votes', 128, 'karina-star-ratings' ),
					'saved'          => __( 'Settings saved.', 'karina-star-ratings' ),
					'copied'         => __( 'Copied to clipboard', 'karina-star-ratings' ),
					'unsaved'        => __( 'You have unsaved changes.', 'karina-star-ratings' ),
					'leave'          => __( 'You have unsaved changes. Leave anyway?', 'karina-star-ratings' ),
					'working'        => __( 'Working…', 'karina-star-ratings' ),
					'importDone'     => __( 'Import finished. Your existing ratings are now managed by Karina Star Ratings.', 'karina-star-ratings' ),
					/* translators: 1: processed posts, 2: total posts. */
					'importProgress' => __( 'Imported %1$s of %2$s posts…', 'karina-star-ratings' ),
					'recalcDone'     => __( 'All ratings were recalculated.', 'karina-star-ratings' ),
					'resetConfirm'   => __( 'This permanently deletes ALL ratings and votes. This cannot be undone. Continue?', 'karina-star-ratings' ),
					'resetDone'      => __( 'All ratings were deleted.', 'karina-star-ratings' ),
					'error'          => __( 'Something went wrong. Please try again.', 'karina-star-ratings' ),
				),
			)
		);
	}

	/**
	 * Preloads the self-hosted admin font so text does not flash in a fallback font.
	 *
	 * The URL matches the one in admin.css exactly; fonts are fetched in CORS mode,
	 * so `crossorigin` is required to reuse the preloaded file.
	 *
	 * @return void
	 */
	public function preload_font() {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( RATINGPRESS_URL . 'assets/fonts/vazirmatn/vazirmatn-variable.woff2' )
		);
	}

	/**
	 * Adds a body class to plugin screens for scoped styling.
	 *
	 * @param string $classes Space separated classes.
	 * @return string
	 */
	public function body_class( $classes ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && $this->is_plugin_screen( $screen->id ) ) {
			$classes .= ' ratingpress-screen';
		}

		return $classes;
	}

	/**
	 * Renders the dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$votes = Plugin::instance()->votes();
		$best  = $this->settings->best();

		View::display(
			'admin/dashboard',
			array(
				'overview'     => $this->stats->overview(),
				'daily'        => $votes->daily_counts( 30 ),
				'distribution' => $votes->distribution( $best ),
				'top'          => $this->stats->top_rated( 8, 'any', 1 ),
				'recent'       => $votes->recent( 8 ),
				'ratings'      => Plugin::instance()->ratings(),
				'best'         => $best,
				'enabled'      => $this->settings->is_enabled(),
				'kk_count'     => $this->kk_pending() ? $this->importer->count() : 0,
				'settings_url' => $this->page_url( self::PAGE_SETTINGS ),
			)
		);
	}

	/**
	 * Renders the settings page.
	 *
	 * @return void
	 */
	public function render_settings() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$post_types = array();
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $post_type ) {
			if ( 'attachment' === $post_type->name ) {
				continue;
			}
			$post_types[ $post_type->name ] = $post_type->labels->name;
		}

		View::display(
			'admin/settings',
			array(
				'values'         => $this->settings->stored(),
				'post_types'     => $post_types,
				'schema_types'   => Settings::schema_types(),
				'icons'          => Icons::all(),
				'schema_preview' => $this->schema_preview(),
				'kk_count'       => $this->importer->count(),
				'kk_active'      => $this->importer->is_kk_active(),
				'kk_imported'    => (int) get_option( Kk_Importer::DONE_OPTION, 0 ),
				'dashboard_url'  => $this->page_url( self::PAGE_DASHBOARD ),
			)
		);
	}

	/**
	 * Example JSON-LD for the most recent post of an enabled post type.
	 *
	 * @return string
	 */
	private function schema_preview() {
		$post_types = $this->settings->post_types();
		$posts      = get_posts(
			array(
				'post_type'      => $post_types ? $post_types : 'post',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'no_found_rows'  => true,
			)
		);

		if ( ! $posts ) {
			return '';
		}

		$schema = $this->schema->build( $posts[0]->ID, true );

		if ( $schema && ! empty( $schema['aggregateRating'] ) && '0' === $schema['aggregateRating']['ratingValue'] ) {
			$best                                     = $this->settings->best();
			$schema['aggregateRating']['ratingValue'] = (string) round( $best * 0.9, 1 );
			$schema['aggregateRating']['ratingCount'] = '24';
		}

		return $schema ? Schema_Builder::encode( $schema, true ) : '';
	}

	/**
	 * Shows a notice offering to import kk Star Ratings data.
	 *
	 * @return void
	 */
	public function import_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins', $this->hooks['dashboard'] ?? '' ), true ) ) {
			return;
		}

		if ( ! current_user_can( self::CAPABILITY ) || ! $this->kk_pending() || get_user_meta( get_current_user_id(), self::DISMISS_META, true ) ) {
			return;
		}

		$count = $this->importer->count();
		if ( ! $count ) {
			return;
		}

		View::display(
			'admin/notice-import',
			array(
				'count'       => $count,
				'import_url'  => $this->page_url( self::PAGE_SETTINGS ) . '#tools',
				'dismiss_url' => wp_nonce_url( admin_url( 'admin-post.php?action=ratingpress_dismiss_notice' ), 'ratingpress_dismiss_notice' ),
			)
		);
	}

	/**
	 * Handles dismissal of the import notice.
	 *
	 * @return void
	 */
	public function dismiss_notice() {
		check_admin_referer( 'ratingpress_dismiss_notice' );

		if ( current_user_can( self::CAPABILITY ) ) {
			update_user_meta( get_current_user_id(), self::DISMISS_META, 1 );
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}

	/**
	 * Adds quick links on the Plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public function action_links( $links ) {
		array_unshift(
			$links,
			sprintf( '<a href="%s">%s</a>', esc_url( $this->page_url( self::PAGE_SETTINGS ) ), esc_html__( 'Settings', 'karina-star-ratings' ) ),
			sprintf( '<a href="%s">%s</a>', esc_url( $this->page_url( self::PAGE_DASHBOARD ) ), esc_html__( 'Dashboard', 'karina-star-ratings' ) )
		);

		return $links;
	}

	/**
	 * Whether kk Star Ratings data exists that has not been imported yet.
	 *
	 * @return bool
	 */
	private function kk_pending() {
		return ! get_option( Kk_Importer::DONE_OPTION );
	}

	/**
	 * Whether the given hook suffix belongs to a plugin page.
	 *
	 * @param string $hook_suffix Hook suffix or screen ID.
	 * @return bool
	 */
	private function is_plugin_screen( $hook_suffix ) {
		return '' !== $hook_suffix && in_array( $hook_suffix, $this->hooks, true );
	}

	/**
	 * Admin URL of a plugin page.
	 *
	 * @param string $page Page slug.
	 * @return string
	 */
	private function page_url( $page ) {
		return admin_url( 'admin.php?page=' . $page );
	}
}
