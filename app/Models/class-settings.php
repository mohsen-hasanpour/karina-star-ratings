<?php
/**
 * Settings model: defaults, retrieval and sanitization of the plugin options.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Models;

use RatingPress\Core\Icons;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings
 */
class Settings {

	/**
	 * Option name.
	 */
	const OPTION = 'ratingpress_settings';

	/**
	 * Settings API group.
	 */
	const GROUP = 'ratingpress_settings_group';

	/**
	 * Cached option values for the current request.
	 *
	 * @var array|null
	 */
	private $values = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'update_option_' . self::OPTION, array( $this, 'flush' ) );
		add_action( 'add_option_' . self::OPTION, array( $this, 'flush' ) );
	}

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			// General.
			'enabled'          => true,
			'post_types'       => array( 'post' ),
			'position'         => 'after',
			'alignment'        => 'start',
			'layout'           => 'inline',
			'show_in_archives' => false,
			'exclude_ids'      => '',

			// Voting.
			'permission'       => 'everyone',
			'restriction'      => 'browser',
			'rate_limit'       => 10,
			'msg_thanks'       => '',
			'msg_voted'        => '',
			'msg_login'        => '',

			// Appearance.
			'icon'             => 'star',
			'stars'            => 5,
			'size'             => 24,
			'gap'              => 4,
			'color_active'     => '#f5b301',
			'color_inactive'   => '#d9dde3',
			'color_hover'      => '#ff8a00',
			'heading'          => '',
			'show_legend'      => true,
			'legend'           => '',
			'greeting'         => '',
			'decimals'         => 1,
			'animations'       => true,

			// Rich snippets.
			'schema_enabled'   => true,
			'schema_type'      => 'CreativeWorkSeries',
			'schema_min_votes' => 1,
			'schema_extended'  => true,

			// Tools / advanced.
			'kk_compat'        => true,
			'delete_data'      => false,
		);
	}

	/**
	 * Translated fallbacks for the visitor-facing texts.
	 *
	 * These settings are stored empty by default and resolved at runtime, so they
	 * follow the site language instead of freezing the language active at activation.
	 *
	 * @return array<string, string>
	 */
	public static function text_defaults() {
		return array(
			'msg_thanks' => __( 'Thanks for your vote!', 'karina-star-ratings' ),
			'msg_voted'  => __( 'You have already rated this.', 'karina-star-ratings' ),
			'msg_login'  => __( 'Please log in to rate.', 'karina-star-ratings' ),
			/* translators: Keep the placeholders in curly braces unchanged; only reorder them if needed. */
			'legend'     => __( '{avg}/{best} ({count} {votes})', 'karina-star-ratings' ),
			'greeting'   => __( 'Be the first to rate!', 'karina-star-ratings' ),
		);
	}

	/**
	 * Schema.org types that Google supports for review snippets.
	 *
	 * @return array<string, string>
	 */
	public static function schema_types() {
		$types = array(
			'CreativeWorkSeries'  => __( 'Creative Work Series (recommended for articles)', 'karina-star-ratings' ),
			'Book'                => __( 'Book', 'karina-star-ratings' ),
			'Course'              => __( 'Course', 'karina-star-ratings' ),
			'CreativeWorkSeason'  => __( 'Creative Work Season', 'karina-star-ratings' ),
			'Episode'             => __( 'Episode', 'karina-star-ratings' ),
			'Event'               => __( 'Event', 'karina-star-ratings' ),
			'Game'                => __( 'Game', 'karina-star-ratings' ),
			'HowTo'               => __( 'How-To', 'karina-star-ratings' ),
			'LocalBusiness'       => __( 'Local Business', 'karina-star-ratings' ),
			'MediaObject'         => __( 'Media Object', 'karina-star-ratings' ),
			'Movie'               => __( 'Movie', 'karina-star-ratings' ),
			'MusicPlaylist'       => __( 'Music Playlist', 'karina-star-ratings' ),
			'MusicRecording'      => __( 'Music Recording', 'karina-star-ratings' ),
			'Organization'        => __( 'Organization', 'karina-star-ratings' ),
			'Product'             => __( 'Product', 'karina-star-ratings' ),
			'Recipe'              => __( 'Recipe', 'karina-star-ratings' ),
			'SoftwareApplication' => __( 'Software Application', 'karina-star-ratings' ),
		);

		/**
		 * Filters the Schema.org types offered for the AggregateRating snippet.
		 *
		 * @param array<string, string> $types Type => label.
		 */
		return (array) apply_filters( 'ratingpress_schema_types', $types );
	}

	/**
	 * Returns all settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function all() {
		if ( null === $this->values ) {
			$this->values = $this->stored();

			foreach ( self::text_defaults() as $key => $text ) {
				if ( '' === trim( (string) $this->values[ $key ] ) ) {
					$this->values[ $key ] = $text;
				}
			}
		}

		return $this->values;
	}

	/**
	 * Saved settings merged with defaults, without the translated text fallbacks.
	 *
	 * Used by the settings screen so empty texts show as placeholders.
	 *
	 * @return array<string, mixed>
	 */
	public function stored() {
		$stored = get_option( self::OPTION, array() );

		return wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );
	}

	/**
	 * Returns a single setting.
	 *
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Value returned when the key does not exist.
	 * @return mixed
	 */
	public function get( $key, $fallback = null ) {
		$values = $this->all();

		return array_key_exists( $key, $values ) ? $values[ $key ] : $fallback;
	}

	/**
	 * Clears the request cache.
	 *
	 * @return void
	 */
	public function flush() {
		$this->values = null;
	}

	/**
	 * Whether ratings are globally enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) $this->get( 'enabled' );
	}

	/**
	 * Post types that receive ratings automatically.
	 *
	 * @return string[]
	 */
	public function post_types() {
		return (array) $this->get( 'post_types', array() );
	}

	/**
	 * Maximum rating value (number of stars).
	 *
	 * @return int
	 */
	public function best() {
		return max( 3, min( 10, (int) $this->get( 'stars', 5 ) ) );
	}

	/**
	 * Post IDs excluded from automatic placement.
	 *
	 * @return int[]
	 */
	public function excluded_ids() {
		return array_filter( array_map( 'absint', explode( ',', (string) $this->get( 'exclude_ids', '' ) ) ) );
	}

	/**
	 * Sanitizes the settings array submitted from the settings screen.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ) {
		// options.php already unslashes submitted values.
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();
		$clean    = array();

		$bool_keys = array( 'enabled', 'show_in_archives', 'show_legend', 'animations', 'schema_enabled', 'schema_extended', 'kk_compat', 'delete_data' );
		foreach ( $bool_keys as $key ) {
			$clean[ $key ] = ! empty( $input[ $key ] );
		}

		$public_types        = get_post_types( array( 'public' => true ) );
		$clean['post_types'] = array_values(
			array_intersect(
				array_map( 'sanitize_key', isset( $input['post_types'] ) ? (array) $input['post_types'] : array() ),
				$public_types
			)
		);

		$enums = array(
			'position'    => array( 'before', 'after', 'both', 'manual' ),
			'alignment'   => array( 'start', 'center', 'end' ),
			'layout'      => array( 'inline', 'stacked' ),
			'permission'  => array( 'everyone', 'logged_in' ),
			'restriction' => array( 'browser', 'ip', 'none' ),
			'icon'        => Icons::keys(),
			'schema_type' => array_keys( self::schema_types() ),
		);
		foreach ( $enums as $key => $allowed ) {
			$value         = isset( $input[ $key ] ) ? (string) $input[ $key ] : '';
			$clean[ $key ] = in_array( $value, $allowed, true ) ? $value : $defaults[ $key ];
		}

		$ranges = array(
			'rate_limit'       => array( 0, 100 ),
			'stars'            => array( 3, 10 ),
			'size'             => array( 12, 72 ),
			'gap'              => array( 0, 24 ),
			'decimals'         => array( 0, 2 ),
			'schema_min_votes' => array( 1, 1000 ),
		);
		foreach ( $ranges as $key => $range ) {
			$value         = isset( $input[ $key ] ) && is_numeric( $input[ $key ] ) ? (int) $input[ $key ] : $defaults[ $key ];
			$clean[ $key ] = max( $range[0], min( $range[1], $value ) );
		}

		foreach ( array( 'color_active', 'color_inactive', 'color_hover' ) as $key ) {
			$color         = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';
			$clean[ $key ] = $color ? $color : $defaults[ $key ];
		}

		foreach ( array( 'msg_thanks', 'msg_voted', 'msg_login', 'heading', 'legend', 'greeting' ) as $key ) {
			$clean[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : $defaults[ $key ];
		}

		$ids                  = isset( $input['exclude_ids'] ) ? explode( ',', (string) $input['exclude_ids'] ) : array();
		$clean['exclude_ids'] = implode( ',', array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

		return $clean;
	}
}
