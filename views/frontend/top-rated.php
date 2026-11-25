<?php
/**
 * Top rated list template ([ratingpress_top]).
 *
 * Override by copying to your-theme/ratingpress/top-rated.php.
 *
 * @package Karina_Star_Ratings
 *
 * @var array $args {
 *     @type array  $items     List of [post, rating, summary, legend].
 *     @type bool   $thumbnail Whether to show thumbnails.
 *     @type string $icon      Icon slug.
 * }
 */

defined( 'ABSPATH' ) || exit;

// Attributes kept on the thumbnail markup (wp_kses_post() would drop srcset and sizes).
$ratingpress_img_tags = array(
	'img' => array_fill_keys( array( 'src', 'srcset', 'sizes', 'alt', 'width', 'height', 'class', 'loading', 'decoding', 'fetchpriority' ), true ),
);
?>
<ol class="ratingpress-top rp-icon-<?php echo esc_attr( $args['icon'] ); ?>">
	<?php foreach ( $args['items'] as $ratingpress_item ) : ?>
		<?php
		$ratingpress_post   = $ratingpress_item['post'];
		$ratingpress_rating = $ratingpress_item['rating'];
		$ratingpress_best   = $ratingpress_rating->best();
		?>
		<li class="rp-top-item">
			<a class="rp-top-link" href="<?php echo esc_url( get_permalink( $ratingpress_post ) ); ?>">
				<?php if ( $args['thumbnail'] && has_post_thumbnail( $ratingpress_post ) ) : ?>
					<span class="rp-top-thumb"><?php echo wp_kses( get_the_post_thumbnail( $ratingpress_post, 'thumbnail', array( 'loading' => 'lazy' ) ), $ratingpress_img_tags ); ?></span>
				<?php endif; ?>
				<span class="rp-top-body">
					<span class="rp-top-title"><?php echo esc_html( get_the_title( $ratingpress_post ) ); ?></span>
					<span class="rp-top-meta">
						<span class="rp-stars rp-stars-sm" role="img" aria-label="<?php echo esc_attr( $ratingpress_item['summary'] ); ?>">
							<?php for ( $ratingpress_i = 1; $ratingpress_i <= $ratingpress_best; $ratingpress_i++ ) : ?>
								<span class="rp-star" style="--rp-fill:<?php echo esc_attr( $ratingpress_rating->fill( $ratingpress_i ) ); ?>%"><span class="rp-star-icon" aria-hidden="true"></span></span>
							<?php endfor; ?>
						</span>
						<span class="rp-legend"><?php echo esc_html( $ratingpress_item['legend'] ); ?></span>
					</span>
				</span>
			</a>
		</li>
	<?php endforeach; ?>
</ol>
