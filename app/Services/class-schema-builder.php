<?php
/**
 * Builds the Schema.org JSON-LD AggregateRating graph of a post.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Services;

use RatingPress\Models\Rating_Repository;
use RatingPress\Models\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class Schema_Builder
 */
class Schema_Builder {

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
	 * Constructor.
	 *
	 * @param Settings          $settings Settings model.
	 * @param Rating_Repository $ratings  Rating repository.
	 */
	public function __construct( Settings $settings, Rating_Repository $ratings ) {
		$this->settings = $settings;
		$this->ratings  = $ratings;
	}

	/**
	 * Builds the schema for a post.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $force   Build even if the post has fewer votes than required (used for previews).
	 * @return array<string, mixed> Empty array when no schema should be printed.
	 */
	public function build( $post_id, $force = false ) {
		$post = get_post( $post_id );
		if ( ! $post || $this->ratings->is_disabled( $post->ID ) ) {
			return array();
		}

		$rating = $this->ratings->find( $post->ID );
		if ( ! $force && $rating->count() < (int) $this->settings->get( 'schema_min_votes', 1 ) ) {
			return array();
		}

		$type = $this->ratings->schema_type( $post->ID );
		if ( '' === $type || ! array_key_exists( $type, Settings::schema_types() ) ) {
			$type = (string) $this->settings->get( 'schema_type', 'CreativeWorkSeries' );
		}

		$permalink = get_permalink( $post );
		$count     = max( 1, $rating->count() );

		$schema = array(
			'@context'        => 'https://schema.org/',
			'@type'           => $type,
			'@id'             => $permalink . '#ratingpress',
			'name'            => wp_strip_all_tags( get_the_title( $post ) ),
			'url'             => $permalink,
			'aggregateRating' => array(
				'@type'       => 'AggregateRating',
				'ratingValue' => (string) $rating->rounded( 2 ),
				'bestRating'  => (string) $rating->best(),
				'worstRating' => '1',
				'ratingCount' => (string) $count,
			),
		);

		if ( $this->settings->get( 'schema_extended' ) ) {
			$schema = array_merge( $schema, $this->extended_properties( $post, $type ) );
		}

		/**
		 * Filters the JSON-LD schema of a post. Return an empty array to disable it.
		 *
		 * @param array    $schema Schema data.
		 * @param \WP_Post $post   Post object.
		 * @param \RatingPress\Models\Rating $rating Aggregated rating.
		 */
		return (array) apply_filters( 'ratingpress_schema', $schema, $post, $rating );
	}

	/**
	 * Optional descriptive properties.
	 *
	 * @param \WP_Post $post Post object.
	 * @param string   $type Schema type.
	 * @return array<string, mixed>
	 */
	private function extended_properties( $post, $type ) {
		$props = array();

		$description = has_excerpt( $post ) ? $post->post_excerpt : wp_trim_words( strip_shortcodes( $post->post_content ), 30, '…' );
		$description = trim( wp_strip_all_tags( $description ) );
		if ( '' !== $description ) {
			$props['description'] = $description;
		}

		$image = get_the_post_thumbnail_url( $post, 'full' );
		if ( $image ) {
			$props['image'] = $image;
		}

		$organization_types = array( 'Organization', 'LocalBusiness' );
		if ( ! in_array( $type, $organization_types, true ) ) {
			$author = get_the_author_meta( 'display_name', (int) $post->post_author );
			if ( $author ) {
				$props['author'] = array(
					'@type' => 'Person',
					'name'  => $author,
					'url'   => get_author_posts_url( (int) $post->post_author ),
				);
			}

			$props['datePublished'] = get_the_date( DATE_W3C, $post );
			$props['dateModified']  = get_the_modified_date( DATE_W3C, $post );
		}

		return $props;
	}

	/**
	 * Encodes a schema array as JSON safe to place inside a script tag.
	 *
	 * @param array $schema Schema data.
	 * @param bool  $pretty Pretty print.
	 * @return string
	 */
	public static function encode( array $schema, $pretty = false ) {
		$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP;
		if ( $pretty ) {
			$flags |= JSON_PRETTY_PRINT;
		}

		return (string) wp_json_encode( $schema, $flags );
	}
}
