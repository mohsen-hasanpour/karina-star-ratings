<?php
/**
 * Rating widget template.
 *
 * Override by copying to your-theme/ratingpress/rating.php.
 *
 * @package Karina_Star_Ratings
 *
 * @var array $args {
 *     @type int                        $post_id     Post ID.
 *     @type \RatingPress\Models\Rating $rating      Aggregated rating.
 *     @type array                      $stars       List of [value, fill, label].
 *     @type bool                       $interactive Whether voting is possible.
 *     @type bool                       $needs_login Whether the visitor must log in to vote.
 *     @type string[]                   $classes     Wrapper classes.
 *     @type string                     $style       Inline CSS custom properties.
 *     @type string                     $heading     Heading text.
 *     @type bool                       $show_legend Whether to show the legend.
 *     @type string                     $legend      Legend text.
 *     @type string                     $summary     Accessible summary.
 *     @type string                     $group_label Accessible label of the button group.
 * }
 */

defined( 'ABSPATH' ) || exit;

$ratingpress_rating = $args['rating'];
?>
<div
	class="<?php echo esc_attr( implode( ' ', $args['classes'] ) ); ?>"
	data-rp-post="<?php echo esc_attr( $args['post_id'] ); ?>"
	data-rp-best="<?php echo esc_attr( $ratingpress_rating->best() ); ?>"
	<?php if ( $args['needs_login'] ) : ?>
		data-rp-login="1"
	<?php endif; ?>
	<?php if ( '' !== $args['style'] ) : ?>
		style="<?php echo esc_attr( $args['style'] ); ?>"
	<?php endif; ?>
>
	<?php if ( '' !== $args['heading'] ) : ?>
		<div class="rp-heading"><?php echo esc_html( $args['heading'] ); ?></div>
	<?php endif; ?>

	<?php if ( $args['interactive'] ) : ?>
		<div class="rp-stars" role="group" aria-label="<?php echo esc_attr( $args['group_label'] ); ?>">
			<?php foreach ( $args['stars'] as $ratingpress_star ) : ?>
				<button
					type="button"
					class="rp-star"
					data-rp-value="<?php echo esc_attr( $ratingpress_star['value'] ); ?>"
					style="--rp-fill:<?php echo esc_attr( $ratingpress_star['fill'] ); ?>%"
					aria-label="<?php echo esc_attr( $ratingpress_star['label'] ); ?>"
				><span class="rp-star-icon" aria-hidden="true"></span></button>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<div class="rp-stars" role="img" aria-label="<?php echo esc_attr( $args['summary'] ); ?>">
			<?php foreach ( $args['stars'] as $ratingpress_star ) : ?>
				<span class="rp-star" style="--rp-fill:<?php echo esc_attr( $ratingpress_star['fill'] ); ?>%"><span class="rp-star-icon" aria-hidden="true"></span></span>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( $args['show_legend'] ) : ?>
		<div class="rp-legend"><?php echo esc_html( $args['legend'] ); ?></div>
	<?php endif; ?>

	<?php if ( $args['interactive'] ) : ?>
		<div class="rp-message" role="status" aria-live="polite"></div>
		<span class="screen-reader-text rp-summary"><?php echo esc_html( $args['summary'] ); ?></span>
	<?php endif; ?>
</div>
