<?php
/**
 * REST API endpoints (namespace ratingpress/v1).
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Controllers;

use RatingPress\Models\Rating;
use RatingPress\Models\Rating_Repository;
use RatingPress\Models\Settings;
use RatingPress\Services\Kk_Importer;
use RatingPress\Services\Renderer;
use RatingPress\Services\Vote_Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Class Rest_Controller
 */
class Rest_Controller {

	/**
	 * REST namespace.
	 */
	const NAMESPACE_V1 = 'ratingpress/v1';

	/**
	 * Posts processed per maintenance request.
	 */
	const BATCH_SIZE = 50;

	/**
	 * Settings model.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Vote service.
	 *
	 * @var Vote_Service
	 */
	private $votes;

	/**
	 * Rating repository.
	 *
	 * @var Rating_Repository
	 */
	private $ratings;

	/**
	 * Renderer service.
	 *
	 * @var Renderer
	 */
	private $renderer;

	/**
	 * Importer for kk Star Ratings data.
	 *
	 * @var Kk_Importer
	 */
	private $importer;

	/**
	 * Constructor.
	 *
	 * @param Settings          $settings Settings model.
	 * @param Vote_Service      $votes    Vote service.
	 * @param Rating_Repository $ratings  Rating repository.
	 * @param Renderer          $renderer Renderer service.
	 * @param Kk_Importer       $importer Importer.
	 */
	public function __construct( Settings $settings, Vote_Service $votes, Rating_Repository $ratings, Renderer $renderer, Kk_Importer $importer ) {
		$this->settings = $settings;
		$this->votes    = $votes;
		$this->ratings  = $ratings;
		$this->renderer = $renderer;
		$this->importer = $importer;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers the REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE_V1,
			'/rate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rate' ),
				// Voting is intentionally public; it is validated, deduplicated and rate limited in Vote_Service.
				'permission_callback' => '__return_true',
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
					'rating'  => array(
						'required'          => true,
						'type'              => 'integer',
						'minimum'           => 1,
						'maximum'           => 10,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/ratings/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'show' ),
				'permission_callback' => array( $this, 'can_read_post' ),
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		$cursor = array(
			'type'              => 'integer',
			'default'           => 0,
			'minimum'           => 0,
			'sanitize_callback' => 'absint',
		);

		// Route => [callback, batch cursor argument]. The import pages with an offset,
		// the recalculation with the last processed post ID.
		$tools = array(
			'import-kk'   => array( 'import_kk', 'offset' ),
			'recalculate' => array( 'recalculate', 'after' ),
			'reset'       => array( 'reset', '' ),
		);

		foreach ( $tools as $route => $tool ) {
			register_rest_route(
				self::NAMESPACE_V1,
				'/tools/' . $route,
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, $tool[0] ),
					'permission_callback' => array( $this, 'can_manage' ),
					'args'                => $tool[1] ? array( $tool[1] => $cursor ) : array(),
				)
			);
		}
	}

	/**
	 * POST /rate — casts a vote.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rate( WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'post_id' );
		$result  = $this->votes->cast( $post_id, (int) $request->get_param( 'rating' ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$data            = $this->prepare( $result );
		$data['message'] = (string) $this->settings->get( 'msg_thanks' );

		$response = rest_ensure_response( $data );
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * GET /ratings/{id} — returns the aggregated rating of a post.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function show( WP_REST_Request $request ) {
		return rest_ensure_response( $this->prepare( $this->ratings->find( (int) $request->get_param( 'id' ) ) ) );
	}

	/**
	 * POST /tools/import-kk — imports a batch from kk Star Ratings.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function import_kk( WP_REST_Request $request ) {
		$result         = $this->importer->import_batch( (int) $request->get_param( 'offset' ), self::BATCH_SIZE );
		$result['next'] = $result['processed'];

		return rest_ensure_response( $result );
	}

	/**
	 * POST /tools/recalculate — rebuilds aggregates for a batch of posts.
	 *
	 * Pass the returned `next` value as `after` to process the following batch.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function recalculate( WP_REST_Request $request ) {
		$after = (int) $request->get_param( 'after' );
		$ids   = $this->ratings->rated_post_ids( $after, self::BATCH_SIZE );

		foreach ( $ids as $post_id ) {
			$this->ratings->recalculate( $post_id );
		}

		return rest_ensure_response(
			array(
				'processed' => count( $ids ),
				'next'      => $ids ? (int) end( $ids ) : $after,
				'done'      => count( $ids ) < self::BATCH_SIZE,
			)
		);
	}

	/**
	 * POST /tools/reset — deletes every rating.
	 *
	 * @return WP_REST_Response
	 */
	public function reset() {
		$this->ratings->reset_all();

		return rest_ensure_response( array( 'done' => true ) );
	}

	/**
	 * Permission callback for public reads.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function can_read_post( WP_REST_Request $request ) {
		$post = get_post( (int) $request->get_param( 'id' ) );

		if ( ! $post || ! is_post_publicly_viewable( $post ) || post_password_required( $post ) ) {
			return new WP_Error( 'rest_post_invalid_id', __( 'Invalid post ID.', 'karina-star-ratings' ), array( 'status' => 404 ) );
		}

		return true;
	}

	/**
	 * Permission callback for maintenance tools.
	 *
	 * @return bool
	 */
	public function can_manage() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Formats a rating for a response.
	 *
	 * @param Rating $rating Rating.
	 * @return array<string, mixed>
	 */
	private function prepare( Rating $rating ) {
		$data            = $rating->to_array( (int) $this->settings->get( 'decimals', 1 ) );
		$data['legend']  = $this->renderer->legend( $rating, $rating->post_id() );
		$data['summary'] = $this->renderer->summary( $rating );

		return $data;
	}
}
