<?php
/**
 * Per-post "Karina Star Ratings" meta box.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Controllers;

use RatingPress\Core\View;
use RatingPress\Models\Rating_Repository;
use RatingPress\Models\Settings;
use RatingPress\Models\Vote_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class Metabox_Controller
 */
class Metabox_Controller {

	/**
	 * Nonce action.
	 */
	const NONCE = 'ratingpress_metabox';

	/**
	 * Settings model.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Rating repository.
	 *
	 * @var Rating_Repository
	 */
	private $ratings;

	/**
	 * Vote repository.
	 *
	 * @var Vote_Repository
	 */
	private $votes;

	/**
	 * Constructor.
	 *
	 * @param Settings          $settings Settings model.
	 * @param Rating_Repository $ratings  Rating repository.
	 * @param Vote_Repository   $votes    Vote repository.
	 */
	public function __construct( Settings $settings, Rating_Repository $ratings, Vote_Repository $votes ) {
		$this->settings = $settings;
		$this->ratings  = $ratings;
		$this->votes    = $votes;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Adds the meta box to enabled post types.
	 *
	 * @return void
	 */
	public function add_meta_box() {
		$post_types = $this->settings->post_types();

		if ( ! $post_types ) {
			return;
		}

		add_meta_box(
			'ratingpress',
			__( 'Karina Star Ratings', 'karina-star-ratings' ),
			array( $this, 'render' ),
			$post_types,
			'side',
			'default'
		);
	}

	/**
	 * Renders the meta box.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render( $post ) {
		$rating = $this->ratings->find( $post->ID );

		View::display(
			'admin/metabox',
			array(
				'post'         => $post,
				'rating'       => $rating,
				'distribution' => $this->votes->distribution( $rating->best(), $post->ID ),
				'baseline'     => (int) get_post_meta( $post->ID, Rating_Repository::META_BASE_COUNT, true ),
				'disabled'     => $this->ratings->is_disabled( $post->ID ),
				'schema_type'  => $this->ratings->schema_type( $post->ID ),
				'schema_types' => Settings::schema_types(),
				'default_type' => (string) $this->settings->get( 'schema_type' ),
				'decimals'     => (int) $this->settings->get( 'decimals', 1 ),
				'icon'         => (string) $this->settings->get( 'icon' ),
				'nonce'        => self::NONCE,
			)
		);
	}

	/**
	 * Saves meta box values.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		if ( ! isset( $_POST['ratingpress_metabox_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ratingpress_metabox_nonce'] ) ), self::NONCE ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! in_array( $post->post_type, $this->settings->post_types(), true ) ) {
			return;
		}

		if ( ! empty( $_POST['ratingpress_disabled'] ) ) {
			update_post_meta( $post_id, Rating_Repository::META_DISABLED, 1 );
		} else {
			delete_post_meta( $post_id, Rating_Repository::META_DISABLED );
		}

		$type = isset( $_POST['ratingpress_schema_type'] ) ? sanitize_text_field( wp_unslash( $_POST['ratingpress_schema_type'] ) ) : '';
		if ( '' !== $type && array_key_exists( $type, Settings::schema_types() ) ) {
			update_post_meta( $post_id, Rating_Repository::META_SCHEMA, $type );
		} else {
			delete_post_meta( $post_id, Rating_Repository::META_SCHEMA );
		}

		if ( ! empty( $_POST['ratingpress_reset'] ) && current_user_can( 'manage_options' ) ) {
			$this->ratings->reset( $post_id );
		}
	}
}
