<?php
/**
 * Main plugin class. Wires models, services and controllers together.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Core;

use RatingPress\Controllers\Admin_Controller;
use RatingPress\Controllers\Block_Controller;
use RatingPress\Controllers\Columns_Controller;
use RatingPress\Controllers\Elementor_Controller;
use RatingPress\Controllers\Frontend_Controller;
use RatingPress\Controllers\Metabox_Controller;
use RatingPress\Controllers\Privacy_Controller;
use RatingPress\Controllers\Rest_Controller;
use RatingPress\Controllers\Schema_Controller;
use RatingPress\Models\Rating_Repository;
use RatingPress\Models\Settings;
use RatingPress\Models\Stats_Repository;
use RatingPress\Models\Vote_Repository;
use RatingPress\Services\Kk_Importer;
use RatingPress\Services\Renderer;
use RatingPress\Services\Schema_Builder;
use RatingPress\Services\Vote_Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Lazily created shared objects.
	 *
	 * @var array<string, object>
	 */
	private $services = array();

	/**
	 * Whether boot() already ran.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Returns the plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks every controller into WordPress.
	 *
	 * @return void
	 */
	public function boot() {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		// Runs on init: default settings are translatable and must not load translations earlier.
		add_action( 'init', array( Installer::class, 'maybe_upgrade' ), 1 );

		// Votes of a deleted post are useless; its rating meta is removed by core.
		add_action( 'deleted_post', array( $this->ratings(), 'purge' ) );

		$controllers = array(
			new Frontend_Controller( $this->settings(), $this->renderer(), $this->ratings() ),
			new Rest_Controller( $this->settings(), $this->vote_service(), $this->ratings(), $this->renderer(), $this->importer() ),
			new Schema_Controller( $this->settings(), $this->schema_builder(), $this->renderer() ),
			new Block_Controller( $this->renderer() ),
			new Elementor_Controller(),
			new Privacy_Controller( $this->votes() ),
		);

		if ( is_admin() ) {
			$controllers[] = new Admin_Controller( $this->settings(), $this->stats(), $this->importer(), $this->schema_builder() );
			$controllers[] = new Metabox_Controller( $this->settings(), $this->ratings(), $this->votes() );
			$controllers[] = new Columns_Controller( $this->settings(), $this->ratings() );
		}

		foreach ( $controllers as $controller ) {
			$controller->register();
		}

		/**
		 * Fires once Karina Star Ratings has been fully loaded.
		 *
		 * @param Plugin $plugin Plugin instance.
		 */
		do_action( 'ratingpress_loaded', $this );
	}

	/**
	 * Settings model.
	 *
	 * @return Settings
	 */
	public function settings() {
		return $this->share(
			'settings',
			static function () {
				return new Settings();
			}
		);
	}

	/**
	 * Vote repository.
	 *
	 * @return Vote_Repository
	 */
	public function votes() {
		return $this->share(
			'votes',
			static function () {
				return new Vote_Repository();
			}
		);
	}

	/**
	 * Rating repository.
	 *
	 * @return Rating_Repository
	 */
	public function ratings() {
		return $this->share(
			'ratings',
			function () {
				return new Rating_Repository( $this->votes(), $this->settings() );
			}
		);
	}

	/**
	 * Stats repository.
	 *
	 * @return Stats_Repository
	 */
	public function stats() {
		return $this->share(
			'stats',
			function () {
				return new Stats_Repository( $this->settings() );
			}
		);
	}

	/**
	 * Renderer service.
	 *
	 * @return Renderer
	 */
	public function renderer() {
		return $this->share(
			'renderer',
			function () {
				return new Renderer( $this->settings(), $this->ratings() );
			}
		);
	}

	/**
	 * Vote service.
	 *
	 * @return Vote_Service
	 */
	public function vote_service() {
		return $this->share(
			'vote_service',
			function () {
				return new Vote_Service( $this->settings(), $this->votes(), $this->ratings() );
			}
		);
	}

	/**
	 * Schema builder service.
	 *
	 * @return Schema_Builder
	 */
	public function schema_builder() {
		return $this->share(
			'schema_builder',
			function () {
				return new Schema_Builder( $this->settings(), $this->ratings() );
			}
		);
	}

	/**
	 * Importer for kk Star Ratings data.
	 *
	 * @return Kk_Importer
	 */
	public function importer() {
		return $this->share(
			'importer',
			function () {
				return new Kk_Importer( $this->ratings() );
			}
		);
	}

	/**
	 * Returns a shared object, creating it on first use.
	 *
	 * @param string   $key     Service key.
	 * @param callable $factory Factory callback.
	 * @return mixed
	 */
	private function share( $key, $factory ) {
		if ( ! isset( $this->services[ $key ] ) ) {
			$this->services[ $key ] = $factory();
		}

		return $this->services[ $key ];
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}
}
