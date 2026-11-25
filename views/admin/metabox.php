<?php
/**
 * Post editor meta box.
 *
 * @package Karina_Star_Ratings
 *
 * @var array $args {
 *     @type \WP_Post                   $post         Post.
 *     @type \RatingPress\Models\Rating $rating       Aggregated rating.
 *     @type array<int, int>            $distribution Star => votes.
 *     @type int                        $baseline     Imported votes.
 *     @type bool                       $disabled     Ratings disabled for this post.
 *     @type string                     $schema_type  Schema type override.
 *     @type array                      $schema_types Available schema types.
 *     @type string                     $default_type Default schema type.
 *     @type int                        $decimals     Decimal places.
 *     @type string                     $icon         Icon slug.
 *     @type string                     $nonce        Nonce action.
 * }
 */

defined( 'ABSPATH' ) || exit;

$ratingpress_rating = $args['rating'];
$ratingpress_max    = max( 1, max( $args['distribution'] ) );
$ratingpress_best   = $ratingpress_rating->best();

wp_nonce_field( $args['nonce'], 'ratingpress_metabox_nonce' );
?>
<div class="rp-metabox">
	<div class="rp-mb-score">
		<span class="rp-mb-average"><?php echo esc_html( number_format_i18n( $ratingpress_rating->rounded( $args['decimals'] ), $args['decimals'] ) ); ?></span>
		<div>
			<span class="ratingpress rp-icon-<?php echo esc_attr( $args['icon'] ); ?> rp-mb-stars" aria-hidden="true">
				<span class="rp-stars">
					<?php for ( $ratingpress_i = 1; $ratingpress_i <= $ratingpress_best; $ratingpress_i++ ) : ?>
						<span class="rp-star" style="--rp-fill:<?php echo esc_attr( $ratingpress_rating->fill( $ratingpress_i ) ); ?>%"><span class="rp-star-icon"></span></span>
					<?php endfor; ?>
				</span>
			</span>
			<span class="rp-mb-count">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: number of votes. */
						_n( '%s vote', '%s votes', $ratingpress_rating->count(), 'karina-star-ratings' ),
						number_format_i18n( $ratingpress_rating->count() )
					)
				);
				?>
			</span>
		</div>
	</div>

	<?php if ( array_sum( $args['distribution'] ) > 0 ) : ?>
		<ul class="rp-mb-dist">
			<?php foreach ( $args['distribution'] as $ratingpress_star => $ratingpress_total ) : ?>
				<li>
					<span class="rp-mb-dist-label"><?php echo esc_html( number_format_i18n( $ratingpress_star ) ); ?></span>
					<span class="rp-mb-dist-track"><span style="width:<?php echo esc_attr( round( $ratingpress_total / $ratingpress_max * 100, 2 ) ); ?>%"></span></span>
					<span class="rp-mb-dist-value"><?php echo esc_html( number_format_i18n( $ratingpress_total ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $args['baseline'] > 0 ) : ?>
		<p class="rp-mb-note">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: number of imported votes. */
					_n( 'Includes %s imported vote.', 'Includes %s imported votes.', $args['baseline'], 'karina-star-ratings' ),
					number_format_i18n( $args['baseline'] )
				)
			);
			?>
		</p>
	<?php endif; ?>

	<hr>

	<p>
		<label class="rp-mb-check">
			<input type="checkbox" name="ratingpress_disabled" value="1" <?php checked( $args['disabled'] ); ?>>
			<?php esc_html_e( 'Disable ratings for this post', 'karina-star-ratings' ); ?>
		</label>
	</p>

	<p>
		<label for="ratingpress-schema-type" class="rp-mb-label"><?php esc_html_e( 'Schema type', 'karina-star-ratings' ); ?></label>
		<select id="ratingpress-schema-type" name="ratingpress_schema_type" class="widefat">
			<option value="">
				<?php
				/* translators: %s: default schema type. */
				echo esc_html( sprintf( __( 'Default (%s)', 'karina-star-ratings' ), $args['default_type'] ) );
				?>
			</option>
			<?php foreach ( $args['schema_types'] as $ratingpress_type => $ratingpress_label ) : ?>
				<option value="<?php echo esc_attr( $ratingpress_type ); ?>" <?php selected( $args['schema_type'], $ratingpress_type ); ?>><?php echo esc_html( $ratingpress_label ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>

	<?php if ( $ratingpress_rating->has_votes() && current_user_can( 'manage_options' ) ) : ?>
		<p>
			<label class="rp-mb-check rp-mb-danger">
				<input type="checkbox" name="ratingpress_reset" value="1">
				<?php esc_html_e( 'Reset all ratings on save', 'karina-star-ratings' ); ?>
			</label>
		</p>
	<?php endif; ?>
</div>
