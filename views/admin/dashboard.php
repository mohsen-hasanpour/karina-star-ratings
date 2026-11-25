<?php
/**
 * Dashboard screen.
 *
 * @package Karina_Star_Ratings
 *
 * @var array $args {
 *     @type array                                    $overview     votes, posts, average.
 *     @type array<string, int>                       $daily        Y-m-d => votes for the last 30 days.
 *     @type array<int, int>                          $distribution Star => votes.
 *     @type \WP_Post[]                               $top          Top rated posts.
 *     @type object[]                                 $recent       Recent votes.
 *     @type \RatingPress\Models\Rating_Repository    $ratings      Rating repository.
 *     @type int                                      $best         Maximum rating.
 *     @type bool                                     $enabled      Ratings enabled.
 *     @type int                                      $kk_count     Posts with kk Star Ratings data waiting for import.
 *     @type string                                   $settings_url Settings URL.
 * }
 */

use RatingPress\Core\View;

defined( 'ABSPATH' ) || exit;

$ratingpress_overview  = $args['overview'];
$ratingpress_daily     = $args['daily'];
$ratingpress_month     = array_sum( $ratingpress_daily );
$ratingpress_daily_max = max( 1, max( $ratingpress_daily ) );
$ratingpress_dist_max  = max( 1, max( $args['distribution'] ) );
$ratingpress_dist_sum  = array_sum( $args['distribution'] );

/**
 * Prints small read-only stars.
 *
 * @param float $average Average rating.
 * @param int   $best    Maximum rating.
 */
$ratingpress_mini_stars = static function ( $average, $best ) {
	echo '<span class="ratingpress rp-mini" aria-hidden="true"><span class="rp-stars">';
	for ( $i = 1; $i <= $best; $i++ ) {
		$fill = max( 0, min( 100, ( $average - ( $i - 1 ) ) * 100 ) );
		echo '<span class="rp-star" style="--rp-fill:' . esc_attr( round( $fill, 1 ) ) . '%"><span class="rp-star-icon"></span></span>';
	}
	echo '</span></span>';
};
?>
<div class="wrap rp-admin rp-dashboard">
	<?php
	View::display(
		'admin/partials/header',
		array(
			'active'   => 'dashboard',
			'title'    => __( 'Karina Star Ratings', 'karina-star-ratings' ),
			'subtitle' => __( 'How your audience rates your content.', 'karina-star-ratings' ),
		)
	);
	?>

	<?php if ( ! $args['enabled'] ) : ?>
		<div class="rp-callout rp-callout-warning">
			<span class="dashicons dashicons-warning" aria-hidden="true"></span>
			<div>
				<strong><?php esc_html_e( 'Ratings are turned off', 'karina-star-ratings' ); ?></strong>
				<p><?php esc_html_e( 'Widgets and rich snippets are hidden on your site.', 'karina-star-ratings' ); ?> <a href="<?php echo esc_url( $args['settings_url'] ); ?>"><?php esc_html_e( 'Enable ratings', 'karina-star-ratings' ); ?></a></p>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $args['kk_count'] > 0 ) : ?>
		<div class="rp-callout rp-callout-brand">
			<span class="dashicons dashicons-migrate" aria-hidden="true"></span>
			<div>
				<strong><?php esc_html_e( 'Existing ratings found', 'karina-star-ratings' ); ?></strong>
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: number of posts. */
							_n( '%s post has ratings from kk Star Ratings. Import them to keep your stars and rich snippets.', '%s posts have ratings from kk Star Ratings. Import them to keep your stars and rich snippets.', $args['kk_count'], 'karina-star-ratings' ),
							number_format_i18n( $args['kk_count'] )
						)
					);
					?>
				</p>
			</div>
			<a class="button button-primary rp-btn" href="<?php echo esc_url( $args['settings_url'] . '#tools' ); ?>"><?php esc_html_e( 'Import now', 'karina-star-ratings' ); ?></a>
		</div>
	<?php endif; ?>

	<div class="rp-stats">
		<div class="rp-stat">
			<span class="rp-stat-icon rp-tone-brand" aria-hidden="true"><span class="dashicons dashicons-groups"></span></span>
			<div>
				<span class="rp-stat-label"><?php esc_html_e( 'Total votes', 'karina-star-ratings' ); ?></span>
				<span class="rp-stat-value"><?php echo esc_html( number_format_i18n( $ratingpress_overview['votes'] ) ); ?></span>
			</div>
		</div>
		<div class="rp-stat">
			<span class="rp-stat-icon rp-tone-amber" aria-hidden="true"><span class="dashicons dashicons-star-filled"></span></span>
			<div>
				<span class="rp-stat-label"><?php esc_html_e( 'Average rating', 'karina-star-ratings' ); ?></span>
				<span class="rp-stat-value">
					<?php echo esc_html( number_format_i18n( $ratingpress_overview['average'], 2 ) ); ?>
					<small>/ <?php echo esc_html( number_format_i18n( $args['best'] ) ); ?></small>
				</span>
			</div>
		</div>
		<div class="rp-stat">
			<span class="rp-stat-icon rp-tone-green" aria-hidden="true"><span class="dashicons dashicons-media-document"></span></span>
			<div>
				<span class="rp-stat-label"><?php esc_html_e( 'Rated posts', 'karina-star-ratings' ); ?></span>
				<span class="rp-stat-value"><?php echo esc_html( number_format_i18n( $ratingpress_overview['posts'] ) ); ?></span>
			</div>
		</div>
		<div class="rp-stat">
			<span class="rp-stat-icon rp-tone-sky" aria-hidden="true"><span class="dashicons dashicons-calendar-alt"></span></span>
			<div>
				<span class="rp-stat-label"><?php esc_html_e( 'Votes, last 30 days', 'karina-star-ratings' ); ?></span>
				<span class="rp-stat-value"><?php echo esc_html( number_format_i18n( $ratingpress_month ) ); ?></span>
			</div>
		</div>
	</div>

	<div class="rp-grid-chart">
		<div class="rp-card">
			<div class="rp-card-head">
				<h2><?php esc_html_e( 'Votes in the last 30 days', 'karina-star-ratings' ); ?></h2>
				<p><?php esc_html_e( 'Votes cast through Karina Star Ratings per day. Imported ratings are not included.', 'karina-star-ratings' ); ?></p>
			</div>
			<div class="rp-card-body">
				<?php if ( $ratingpress_month > 0 ) : ?>
					<div class="rp-chart" aria-hidden="true">
						<div class="rp-chart-axis">
							<span><?php echo esc_html( number_format_i18n( $ratingpress_daily_max ) ); ?></span>
							<span><?php echo esc_html( number_format_i18n( 0 ) ); ?></span>
						</div>
						<div class="rp-chart-plot">
							<?php foreach ( $ratingpress_daily as $ratingpress_day => $ratingpress_total ) : ?>
								<?php
								$ratingpress_label = date_i18n( get_option( 'date_format' ), strtotime( $ratingpress_day . ' 12:00:00' ) );
								$ratingpress_tip   = sprintf(
									/* translators: 1: date, 2: number of votes. */
									_n( '%1$s: %2$s vote', '%1$s: %2$s votes', $ratingpress_total, 'karina-star-ratings' ),
									$ratingpress_label,
									number_format_i18n( $ratingpress_total )
								);
								?>
								<div class="rp-chart-col" data-rp-tip="<?php echo esc_attr( $ratingpress_tip ); ?>">
									<span class="rp-chart-bar<?php echo 0 === $ratingpress_total ? ' is-empty' : ''; ?>" style="--h:<?php echo esc_attr( round( $ratingpress_total / $ratingpress_daily_max * 100, 2 ) ); ?>%"></span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="rp-chart-x" aria-hidden="true">
						<?php
						$ratingpress_days = array_keys( $ratingpress_daily );
						foreach ( array( reset( $ratingpress_days ), $ratingpress_days[ (int) floor( count( $ratingpress_days ) / 2 ) ], end( $ratingpress_days ) ) as $ratingpress_tick ) :
							?>
							<span><?php echo esc_html( date_i18n( 'M j', strtotime( $ratingpress_tick . ' 12:00:00' ) ) ); ?></span>
						<?php endforeach; ?>
					</div>
					<table class="screen-reader-text">
						<caption><?php esc_html_e( 'Votes per day', 'karina-star-ratings' ); ?></caption>
						<thead><tr><th scope="col"><?php esc_html_e( 'Date', 'karina-star-ratings' ); ?></th><th scope="col"><?php esc_html_e( 'Votes', 'karina-star-ratings' ); ?></th></tr></thead>
						<tbody>
							<?php foreach ( $ratingpress_daily as $ratingpress_day => $ratingpress_total ) : ?>
								<tr><td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $ratingpress_day . ' 12:00:00' ) ) ); ?></td><td><?php echo esc_html( number_format_i18n( $ratingpress_total ) ); ?></td></tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<div class="rp-empty">
						<span class="dashicons dashicons-chart-bar" aria-hidden="true"></span>
						<p><?php esc_html_e( 'No votes in the last 30 days yet. Votes will appear here as soon as visitors start rating.', 'karina-star-ratings' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="rp-card">
			<div class="rp-card-head">
				<h2><?php esc_html_e( 'Rating distribution', 'karina-star-ratings' ); ?></h2>
				<p><?php esc_html_e( 'All votes cast through Karina Star Ratings.', 'karina-star-ratings' ); ?></p>
			</div>
			<div class="rp-card-body">
				<?php if ( $ratingpress_dist_sum > 0 ) : ?>
					<ul class="rp-dist">
						<?php foreach ( $args['distribution'] as $ratingpress_star => $ratingpress_total ) : ?>
							<li class="rp-dist-row">
								<span class="rp-dist-label"><?php echo esc_html( number_format_i18n( $ratingpress_star ) ); ?> <span class="dashicons dashicons-star-filled" aria-hidden="true"></span></span>
								<span class="rp-dist-track"><span class="rp-dist-bar" style="--w:<?php echo esc_attr( round( $ratingpress_total / $ratingpress_dist_max * 100, 2 ) ); ?>%"></span></span>
								<span class="rp-dist-value">
									<?php echo esc_html( number_format_i18n( $ratingpress_total ) ); ?>
									<small><?php echo esc_html( number_format_i18n( round( $ratingpress_total / $ratingpress_dist_sum * 100 ) ) . '%' ); ?></small>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<div class="rp-empty">
						<span class="dashicons dashicons-star-empty" aria-hidden="true"></span>
						<p><?php esc_html_e( 'No votes yet.', 'karina-star-ratings' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="rp-grid-2">
		<div class="rp-card">
			<div class="rp-card-head">
				<h2><?php esc_html_e( 'Top rated', 'karina-star-ratings' ); ?></h2>
			</div>
			<?php if ( $args['top'] ) : ?>
				<ol class="rp-list">
					<?php foreach ( $args['top'] as $ratingpress_post ) : ?>
						<?php $ratingpress_rating = $args['ratings']->find( $ratingpress_post->ID ); ?>
						<li class="rp-list-item">
							<div class="rp-list-main">
								<a class="rp-list-title" href="<?php echo esc_url( (string) get_edit_post_link( $ratingpress_post->ID ) ); ?>"><?php echo esc_html( get_the_title( $ratingpress_post ) ? get_the_title( $ratingpress_post ) : __( '(no title)', 'karina-star-ratings' ) ); ?></a>
								<span class="rp-list-meta">
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
							<div class="rp-list-side">
								<?php $ratingpress_mini_stars( $ratingpress_rating->average(), $ratingpress_rating->best() ); ?>
								<strong><?php echo esc_html( number_format_i18n( $ratingpress_rating->rounded( 1 ), 1 ) ); ?></strong>
							</div>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php else : ?>
				<div class="rp-card-body">
					<div class="rp-empty">
						<span class="dashicons dashicons-awards" aria-hidden="true"></span>
						<p><?php esc_html_e( 'Your best rated content will be listed here.', 'karina-star-ratings' ); ?></p>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<div class="rp-card">
			<div class="rp-card-head">
				<h2><?php esc_html_e( 'Recent votes', 'karina-star-ratings' ); ?></h2>
			</div>
			<?php if ( $args['recent'] ) : ?>
				<ul class="rp-list">
					<?php foreach ( $args['recent'] as $ratingpress_vote ) : ?>
						<?php
						$ratingpress_user  = $ratingpress_vote->user_id ? get_userdata( (int) $ratingpress_vote->user_id ) : false;
						$ratingpress_value = (int) $ratingpress_vote->rating * $args['best'] / max( 1, (int) $ratingpress_vote->best );
						?>
						<li class="rp-list-item">
							<div class="rp-list-main">
								<a class="rp-list-title" href="<?php echo esc_url( (string) get_edit_post_link( (int) $ratingpress_vote->post_id ) ); ?>"><?php echo esc_html( $ratingpress_vote->post_title ? $ratingpress_vote->post_title : __( '(deleted)', 'karina-star-ratings' ) ); ?></a>
								<span class="rp-list-meta">
									<?php echo esc_html( $ratingpress_user ? $ratingpress_user->display_name : __( 'Guest', 'karina-star-ratings' ) ); ?>
									&middot;
									<?php
									/* translators: %s: human readable time difference. */
									echo esc_html( sprintf( __( '%s ago', 'karina-star-ratings' ), human_time_diff( strtotime( $ratingpress_vote->created_at . ' UTC' ) ) ) );
									?>
								</span>
							</div>
							<div class="rp-list-side">
								<?php $ratingpress_mini_stars( $ratingpress_value, $args['best'] ); ?>
								<span class="screen-reader-text">
									<?php
									/* translators: 1: rating, 2: maximum rating. */
									echo esc_html( sprintf( __( '%1$s out of %2$s', 'karina-star-ratings' ), number_format_i18n( $ratingpress_value, 1 ), number_format_i18n( $args['best'] ) ) );
									?>
								</span>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<div class="rp-card-body">
					<div class="rp-empty">
						<span class="dashicons dashicons-clock" aria-hidden="true"></span>
						<p><?php esc_html_e( 'No votes yet.', 'karina-star-ratings' ); ?></p>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="rp-card rp-quickstart">
		<div class="rp-card-head">
			<h2><?php esc_html_e( 'Quick start', 'karina-star-ratings' ); ?></h2>
		</div>
		<div class="rp-card-body rp-quickstart-grid">
			<div class="rp-quickstart-item">
				<span class="rp-step">1</span>
				<div>
					<strong><?php esc_html_e( 'Automatic', 'karina-star-ratings' ); ?></strong>
					<p><?php esc_html_e( 'Ratings are added to your selected post types automatically.', 'karina-star-ratings' ); ?></p>
					<a href="<?php echo esc_url( $args['settings_url'] ); ?>"><?php esc_html_e( 'Choose post types', 'karina-star-ratings' ); ?></a>
				</div>
			</div>
			<div class="rp-quickstart-item">
				<span class="rp-step">2</span>
				<div>
					<strong><?php esc_html_e( 'Shortcode', 'karina-star-ratings' ); ?></strong>
					<p><code id="rp-qs-shortcode" dir="ltr">[ratingpress]</code> <button type="button" class="button button-small rp-copy" data-rp-copy="#rp-qs-shortcode"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Copy shortcode', 'karina-star-ratings' ); ?></span></button></p>
				</div>
			</div>
			<div class="rp-quickstart-item">
				<span class="rp-step">3</span>
				<div>
					<strong><?php esc_html_e( 'Page builders', 'karina-star-ratings' ); ?></strong>
					<p><?php esc_html_e( 'Use the "Star Rating" Elementor widget or the "Karina Star Rating" block.', 'karina-star-ratings' ); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>
