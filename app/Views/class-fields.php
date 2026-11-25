<?php
/**
 * View helpers that print settings form controls. Every method escapes its output.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Views;

use RatingPress\Models\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class Fields
 */
final class Fields {

	/**
	 * Field name attribute for a setting key.
	 *
	 * @param string $key Setting key.
	 * @return string
	 */
	public static function name( $key ) {
		return Settings::OPTION . '[' . $key . ']';
	}

	/**
	 * Field ID attribute for a setting key.
	 *
	 * @param string $key Setting key.
	 * @return string
	 */
	public static function id( $key ) {
		return 'rp-field-' . str_replace( '_', '-', $key );
	}

	/**
	 * Opens a settings row.
	 *
	 * @param string $label       Label.
	 * @param string $description Help text.
	 * @param string $key         Setting key the label points to (optional).
	 * @return void
	 */
	public static function row_start( $label, $description = '', $key = '' ) {
		echo '<div class="rp-row"><div class="rp-row-label">';

		if ( $key ) {
			printf( '<label class="rp-label" for="%1$s">%2$s</label>', esc_attr( self::id( $key ) ), esc_html( $label ) );
		} else {
			printf( '<span class="rp-label">%s</span>', esc_html( $label ) );
		}

		if ( $description ) {
			printf( '<p class="rp-help">%s</p>', esc_html( $description ) );
		}

		echo '</div><div class="rp-row-control">';
	}

	/**
	 * Closes a settings row.
	 *
	 * @return void
	 */
	public static function row_end() {
		echo '</div></div>';
	}

	/**
	 * Toggle switch.
	 *
	 * @param string $key     Setting key.
	 * @param bool   $checked Current value.
	 * @param string $label   Accessible label shown next to the switch.
	 * @return void
	 */
	public static function toggle( $key, $checked, $label = '' ) {
		printf(
			'<label class="rp-toggle"><input type="checkbox" role="switch" id="%1$s" name="%2$s" value="1" data-rp-key="%3$s" %4$s><span class="rp-toggle-track" aria-hidden="true"><span class="rp-toggle-thumb"></span></span>%5$s</label>',
			esc_attr( self::id( $key ) ),
			esc_attr( self::name( $key ) ),
			esc_attr( $key ),
			checked( (bool) $checked, true, false ),
			$label ? '<span class="rp-toggle-label">' . esc_html( $label ) . '</span>' : ''
		);
	}

	/**
	 * Segmented control (styled radio group).
	 *
	 * @param string $key     Setting key.
	 * @param string $value   Current value.
	 * @param array  $options Value => label.
	 * @param string $label   Accessible group label.
	 * @return void
	 */
	public static function segmented( $key, $value, array $options, $label = '' ) {
		printf( '<div class="rp-segmented" role="radiogroup" aria-label="%s">', esc_attr( $label ) );

		foreach ( $options as $option => $text ) {
			$id = self::id( $key ) . '-' . sanitize_html_class( $option );
			printf(
				'<input type="radio" class="rp-segmented-input" id="%1$s" name="%2$s" value="%3$s" data-rp-key="%4$s" %5$s><label for="%1$s">%6$s</label>',
				esc_attr( $id ),
				esc_attr( self::name( $key ) ),
				esc_attr( $option ),
				esc_attr( $key ),
				checked( (string) $value, (string) $option, false ),
				esc_html( $text )
			);
		}

		echo '</div>';
	}

	/**
	 * Radio cards with title and description.
	 *
	 * @param string $key     Setting key.
	 * @param string $value   Current value.
	 * @param array  $options Value => [title, description, icon (dashicon slug, optional)].
	 * @return void
	 */
	public static function radio_cards( $key, $value, array $options ) {
		echo '<div class="rp-radio-cards">';

		foreach ( $options as $option => $card ) {
			$id = self::id( $key ) . '-' . sanitize_html_class( $option );
			printf(
				'<label class="rp-radio-card" for="%1$s"><input type="radio" id="%1$s" name="%2$s" value="%3$s" data-rp-key="%4$s" %5$s><span class="rp-radio-card-body">%6$s<span class="rp-radio-card-title">%7$s</span><span class="rp-radio-card-desc">%8$s</span></span></label>',
				esc_attr( $id ),
				esc_attr( self::name( $key ) ),
				esc_attr( $option ),
				esc_attr( $key ),
				checked( (string) $value, (string) $option, false ),
				! empty( $card[2] ) ? '<span class="dashicons ' . esc_attr( $card[2] ) . '" aria-hidden="true"></span>' : '',
				esc_html( $card[0] ),
				esc_html( isset( $card[1] ) ? $card[1] : '' )
			);
		}

		echo '</div>';
	}

	/**
	 * Select box.
	 *
	 * @param string $key     Setting key.
	 * @param string $value   Current value.
	 * @param array  $options Value => label.
	 * @return void
	 */
	public static function select( $key, $value, array $options ) {
		printf( '<select class="rp-select" id="%1$s" name="%2$s" data-rp-key="%3$s">', esc_attr( self::id( $key ) ), esc_attr( self::name( $key ) ), esc_attr( $key ) );

		foreach ( $options as $option => $text ) {
			printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $option ), selected( (string) $value, (string) $option, false ), esc_html( $text ) );
		}

		echo '</select>';
	}

	/**
	 * Text input.
	 *
	 * @param string $key         Setting key.
	 * @param string $value       Current value.
	 * @param string $placeholder Placeholder.
	 * @param string $css_class   Extra class.
	 * @return void
	 */
	public static function text( $key, $value, $placeholder = '', $css_class = '' ) {
		printf(
			'<input type="text" class="rp-input %5$s" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s" data-rp-key="%6$s">',
			esc_attr( self::id( $key ) ),
			esc_attr( self::name( $key ) ),
			esc_attr( $value ),
			esc_attr( $placeholder ),
			esc_attr( $css_class ),
			esc_attr( $key )
		);
	}

	/**
	 * Number input with optional unit suffix.
	 *
	 * @param string $key   Setting key.
	 * @param int    $value Current value.
	 * @param int    $min   Minimum.
	 * @param int    $max   Maximum.
	 * @param string $unit  Unit suffix.
	 * @return void
	 */
	public static function number( $key, $value, $min, $max, $unit = '' ) {
		printf(
			'<span class="rp-number"><input type="number" class="rp-input" id="%1$s" name="%2$s" value="%3$d" min="%4$d" max="%5$d" step="1" data-rp-key="%6$s">%7$s</span>',
			esc_attr( self::id( $key ) ),
			esc_attr( self::name( $key ) ),
			(int) $value,
			(int) $min,
			(int) $max,
			esc_attr( $key ),
			$unit ? '<span class="rp-unit">' . esc_html( $unit ) . '</span>' : ''
		);
	}

	/**
	 * Range slider with a live value read-out.
	 *
	 * @param string $key   Setting key.
	 * @param int    $value Current value.
	 * @param int    $min   Minimum.
	 * @param int    $max   Maximum.
	 * @param string $unit  Unit suffix.
	 * @return void
	 */
	public static function range( $key, $value, $min, $max, $unit = 'px' ) {
		printf(
			'<span class="rp-range"><input type="range" id="%1$s" name="%2$s" value="%3$d" min="%4$d" max="%5$d" step="1" data-rp-key="%6$s" data-rp-unit="%7$s"><output for="%1$s" class="rp-range-value">%3$d%7$s</output></span>',
			esc_attr( self::id( $key ) ),
			esc_attr( self::name( $key ) ),
			(int) $value,
			(int) $min,
			(int) $max,
			esc_attr( $key ),
			esc_attr( $unit )
		);
	}

	/**
	 * Color picker.
	 *
	 * @param string $key           Setting key.
	 * @param string $value         Current value.
	 * @param string $default_color Default color.
	 * @return void
	 */
	public static function color( $key, $value, $default_color ) {
		printf(
			'<input type="text" class="rp-color" id="%1$s" name="%2$s" value="%3$s" data-default-color="%4$s" data-rp-key="%5$s">',
			esc_attr( self::id( $key ) ),
			esc_attr( self::name( $key ) ),
			esc_attr( $value ),
			esc_attr( $default_color ),
			esc_attr( $key )
		);
	}

	/**
	 * Checkbox chips for multi-select.
	 *
	 * @param string $key     Setting key.
	 * @param array  $values  Selected values.
	 * @param array  $options Value => label.
	 * @return void
	 */
	public static function chips( $key, array $values, array $options ) {
		echo '<div class="rp-chips">';

		foreach ( $options as $option => $text ) {
			$id = self::id( $key ) . '-' . sanitize_html_class( $option );
			printf(
				'<input type="checkbox" class="rp-chip-input" id="%1$s" name="%2$s[]" value="%3$s" %4$s><label class="rp-chip" for="%1$s"><span class="dashicons dashicons-yes" aria-hidden="true"></span>%5$s</label>',
				esc_attr( $id ),
				esc_attr( self::name( $key ) ),
				esc_attr( $option ),
				checked( in_array( $option, $values, true ), true, false ),
				esc_html( $text )
			);
		}

		echo '</div>';
	}
}
