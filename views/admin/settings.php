<?php
/**
 * Settings screen.
 *
 * @package Karina_Star_Ratings
 *
 * @var array $args {
 *     @type array  $values         Current settings.
 *     @type array  $post_types     Public post types (name => label).
 *     @type array  $schema_types   Schema types (type => label).
 *     @type array  $icons          Icon definitions.
 *     @type string $schema_preview Example JSON-LD.
 *     @type int    $kk_count       Posts with kk Star Ratings data.
 *     @type bool   $kk_active      Whether kk Star Ratings is active.
 *     @type int    $kk_imported    Timestamp of the last import, 0 if never.
 *     @type string $dashboard_url  Dashboard URL.
 * }
 */

use RatingPress\Core\Icons;
use RatingPress\Core\View;
use RatingPress\Models\Settings;
use RatingPress\Views\Fields;

defined( 'ABSPATH' ) || exit;

$ratingpress_v        = $args['values'];
$ratingpress_defaults = Settings::defaults();
$ratingpress_texts    = Settings::text_defaults();

$ratingpress_tabs = array(
	'general'    => array( __( 'General', 'karina-star-ratings' ), 'dashicons-admin-settings' ),
	'voting'     => array( __( 'Voting', 'karina-star-ratings' ), 'dashicons-thumbs-up' ),
	'appearance' => array( __( 'Appearance', 'karina-star-ratings' ), 'dashicons-art' ),
	'schema'     => array( __( 'Rich snippets', 'karina-star-ratings' ), 'dashicons-google' ),
	'tools'      => array( __( 'Tools', 'karina-star-ratings' ), 'dashicons-admin-tools' ),
	'help'       => array( __( 'Shortcodes & help', 'karina-star-ratings' ), 'dashicons-editor-code' ),
);
?>
<div class="wrap rp-admin">
	<?php
	View::display(
		'admin/partials/header',
		array(
			'active'   => 'settings',
			'title'    => __( 'Karina Star Ratings', 'karina-star-ratings' ),
			'subtitle' => __( 'Star ratings and rich snippets for every post.', 'karina-star-ratings' ),
		)
	);
	?>

	<?php settings_errors(); ?>

	<form method="post" action="options.php" class="rp-form" id="rp-settings-form" novalidate>
		<?php settings_fields( Settings::GROUP ); ?>

		<div class="rp-layout">
			<nav class="rp-sidenav" aria-label="<?php esc_attr_e( 'Settings sections', 'karina-star-ratings' ); ?>">
				<ul class="rp-tabs" role="tablist">
					<?php foreach ( $ratingpress_tabs as $ratingpress_tab => $ratingpress_meta ) : ?>
						<li role="presentation">
							<a
								href="#<?php echo esc_attr( $ratingpress_tab ); ?>"
								class="rp-tab"
								role="tab"
								id="rp-tab-<?php echo esc_attr( $ratingpress_tab ); ?>"
								aria-controls="rp-panel-<?php echo esc_attr( $ratingpress_tab ); ?>"
								data-rp-tab="<?php echo esc_attr( $ratingpress_tab ); ?>"
							>
								<span class="dashicons <?php echo esc_attr( $ratingpress_meta[1] ); ?>" aria-hidden="true"></span>
								<span><?php echo esc_html( $ratingpress_meta[0] ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>

			<div class="rp-panels">

				<?php /* ------------------------------------------------ General */ ?>
				<section class="rp-panel" id="rp-panel-general" role="tabpanel" aria-labelledby="rp-tab-general" data-rp-panel="general">
					<div class="rp-card">
						<div class="rp-card-head">
							<h2><?php esc_html_e( 'Ratings', 'karina-star-ratings' ); ?></h2>
							<p><?php esc_html_e( 'Choose where star ratings appear on your site.', 'karina-star-ratings' ); ?></p>
						</div>
						<div class="rp-card-body">
							<?php
							Fields::row_start( __( 'Enable ratings', 'karina-star-ratings' ), __( 'Turn every rating widget and rich snippet on or off site-wide.', 'karina-star-ratings' ), 'enabled' );
							Fields::toggle( 'enabled', $ratingpress_v['enabled'] );
							Fields::row_end();

							Fields::row_start( __( 'Post types', 'karina-star-ratings' ), __( 'Ratings are added automatically to these content types. The shortcode, block and Elementor widget work everywhere.', 'karina-star-ratings' ) );
							Fields::chips( 'post_types', (array) $ratingpress_v['post_types'], $args['post_types'] );
							Fields::row_end();

							Fields::row_start( __( 'Automatic placement', 'karina-star-ratings' ), __( 'Where to insert the widget in the content. Choose "Manual" to place it only with the shortcode, block or widget.', 'karina-star-ratings' ) );
							Fields::segmented(
								'position',
								$ratingpress_v['position'],
								array(
									'before' => __( 'Before content', 'karina-star-ratings' ),
									'after'  => __( 'After content', 'karina-star-ratings' ),
									'both'   => __( 'Both', 'karina-star-ratings' ),
									'manual' => __( 'Manual', 'karina-star-ratings' ),
								),
								__( 'Automatic placement', 'karina-star-ratings' )
							);
							Fields::row_end();

							Fields::row_start( __( 'Show on archive pages', 'karina-star-ratings' ), __( 'Display read-only ratings in blog, category and search listings that output the full content.', 'karina-star-ratings' ), 'show_in_archives' );
							Fields::toggle( 'show_in_archives', $ratingpress_v['show_in_archives'] );
							Fields::row_end();

							Fields::row_start( __( 'Exclude posts', 'karina-star-ratings' ), __( 'Comma separated post IDs that should not get the automatic widget. You can also disable ratings per post from the editor sidebar.', 'karina-star-ratings' ), 'exclude_ids' );
							Fields::text( 'exclude_ids', $ratingpress_v['exclude_ids'], '12, 345, 678' );
							Fields::row_end();
							?>
						</div>
					</div>
				</section>

				<?php /* ------------------------------------------------- Voting */ ?>
				<section class="rp-panel" id="rp-panel-voting" role="tabpanel" aria-labelledby="rp-tab-voting" data-rp-panel="voting" hidden>
					<div class="rp-card">
						<div class="rp-card-head">
							<h2><?php esc_html_e( 'Who can vote', 'karina-star-ratings' ); ?></h2>
							<p><?php esc_html_e( 'Control access and protect your ratings from abuse.', 'karina-star-ratings' ); ?></p>
						</div>
						<div class="rp-card-body">
							<?php
							Fields::row_start( __( 'Voting permission', 'karina-star-ratings' ), __( 'Allow every visitor to vote, or only logged-in users.', 'karina-star-ratings' ) );
							Fields::segmented(
								'permission',
								$ratingpress_v['permission'],
								array(
									'everyone'  => __( 'Everyone', 'karina-star-ratings' ),
									'logged_in' => __( 'Logged-in users', 'karina-star-ratings' ),
								),
								__( 'Voting permission', 'karina-star-ratings' )
							);
							Fields::row_end();

							Fields::row_start( __( 'Duplicate protection', 'karina-star-ratings' ), __( 'Logged-in users can always vote only once per post (unless set to "No limit").', 'karina-star-ratings' ) );
							Fields::radio_cards(
								'restriction',
								$ratingpress_v['restriction'],
								array(
									'browser' => array( __( 'One vote per browser', 'karina-star-ratings' ), __( 'Recommended. Uses a cookie and local storage. Friendly to visitors who share a network.', 'karina-star-ratings' ), 'dashicons-desktop' ),
									'ip'      => array( __( 'One vote per IP address', 'karina-star-ratings' ), __( 'Strictest. May block people behind the same mobile carrier or office network.', 'karina-star-ratings' ), 'dashicons-shield' ),
									'none'    => array( __( 'No limit', 'karina-star-ratings' ), __( 'Visitors can vote repeatedly. Useful for testing.', 'karina-star-ratings' ), 'dashicons-unlock' ),
								)
							);
							Fields::row_end();

							Fields::row_start( __( 'Rate limit', 'karina-star-ratings' ), __( 'Maximum votes per minute from one IP address across the whole site. 0 disables the limit.', 'karina-star-ratings' ), 'rate_limit' );
							Fields::number( 'rate_limit', $ratingpress_v['rate_limit'], 0, 100, __( 'votes / minute', 'karina-star-ratings' ) );
							Fields::row_end();
							?>
						</div>
					</div>

					<div class="rp-card">
						<div class="rp-card-head">
							<h2><?php esc_html_e( 'Messages', 'karina-star-ratings' ); ?></h2>
							<p><?php esc_html_e( 'Feedback shown to visitors after they click a star.', 'karina-star-ratings' ); ?></p>
						</div>
						<div class="rp-card-body">
							<?php
							Fields::row_start( __( 'Thank you', 'karina-star-ratings' ), '', 'msg_thanks' );
							Fields::text( 'msg_thanks', $ratingpress_v['msg_thanks'], $ratingpress_texts['msg_thanks'] );
							Fields::row_end();

							Fields::row_start( __( 'Already voted', 'karina-star-ratings' ), '', 'msg_voted' );
							Fields::text( 'msg_voted', $ratingpress_v['msg_voted'], $ratingpress_texts['msg_voted'] );
							Fields::row_end();

							Fields::row_start( __( 'Login required', 'karina-star-ratings' ), '', 'msg_login' );
							Fields::text( 'msg_login', $ratingpress_v['msg_login'], $ratingpress_texts['msg_login'] );
							Fields::row_end();
							?>
						</div>
					</div>
				</section>

				<?php /* --------------------------------------------- Appearance */ ?>
				<section class="rp-panel" id="rp-panel-appearance" role="tabpanel" aria-labelledby="rp-tab-appearance" data-rp-panel="appearance" hidden>
					<div class="rp-split">
						<div class="rp-split-main">
							<div class="rp-card">
								<div class="rp-card-head">
									<h2><?php esc_html_e( 'Icons', 'karina-star-ratings' ); ?></h2>
									<p><?php esc_html_e( 'Shape, scale, size and colors of the rating symbols.', 'karina-star-ratings' ); ?></p>
								</div>
								<div class="rp-card-body">
									<?php Fields::row_start( __( 'Icon style', 'karina-star-ratings' ) ); ?>
									<div class="rp-icon-picker" role="radiogroup" aria-label="<?php esc_attr_e( 'Icon style', 'karina-star-ratings' ); ?>">
										<?php foreach ( $args['icons'] as $ratingpress_icon => $ratingpress_icon_data ) : ?>
											<input type="radio" class="rp-icon-input" id="rp-icon-<?php echo esc_attr( $ratingpress_icon ); ?>" name="<?php echo esc_attr( Fields::name( 'icon' ) ); ?>" value="<?php echo esc_attr( $ratingpress_icon ); ?>" data-rp-key="icon" <?php checked( $ratingpress_v['icon'], $ratingpress_icon ); ?>>
											<label class="rp-icon-option" for="rp-icon-<?php echo esc_attr( $ratingpress_icon ); ?>">
												<?php echo Icons::svg( $ratingpress_icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static SVG markup. ?>
												<span><?php echo esc_html( $ratingpress_icon_data['label'] ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
									<?php
									Fields::row_end();

									Fields::row_start( __( 'Number of stars', 'karina-star-ratings' ), __( 'Also used as "bestRating" in the rich snippet. Existing votes are rescaled automatically.', 'karina-star-ratings' ), 'stars' );
									Fields::range( 'stars', $ratingpress_v['stars'], 3, 10, '' );
									Fields::row_end();

									Fields::row_start( __( 'Icon size', 'karina-star-ratings' ), '', 'size' );
									Fields::range( 'size', $ratingpress_v['size'], 12, 72 );
									Fields::row_end();

									Fields::row_start( __( 'Spacing', 'karina-star-ratings' ), '', 'gap' );
									Fields::range( 'gap', $ratingpress_v['gap'], 0, 24 );
									Fields::row_end();

									Fields::row_start( __( 'Colors', 'karina-star-ratings' ), __( 'Filled, empty and hover colors.', 'karina-star-ratings' ) );
									?>
									<div class="rp-colors">
										<div class="rp-color-field">
											<span class="rp-color-label"><?php esc_html_e( 'Filled', 'karina-star-ratings' ); ?></span>
											<?php Fields::color( 'color_active', $ratingpress_v['color_active'], $ratingpress_defaults['color_active'] ); ?>
										</div>
										<div class="rp-color-field">
											<span class="rp-color-label"><?php esc_html_e( 'Empty', 'karina-star-ratings' ); ?></span>
											<?php Fields::color( 'color_inactive', $ratingpress_v['color_inactive'], $ratingpress_defaults['color_inactive'] ); ?>
										</div>
										<div class="rp-color-field">
											<span class="rp-color-label"><?php esc_html_e( 'Hover', 'karina-star-ratings' ); ?></span>
											<?php Fields::color( 'color_hover', $ratingpress_v['color_hover'], $ratingpress_defaults['color_hover'] ); ?>
										</div>
									</div>
									<?php
									Fields::row_end();

									Fields::row_start( __( 'Animations', 'karina-star-ratings' ), __( 'Subtle hover and vote animations. Visitors who prefer reduced motion never see them.', 'karina-star-ratings' ), 'animations' );
									Fields::toggle( 'animations', $ratingpress_v['animations'] );
									Fields::row_end();
									?>
								</div>
							</div>

							<div class="rp-card">
								<div class="rp-card-head">
									<h2><?php esc_html_e( 'Layout & text', 'karina-star-ratings' ); ?></h2>
									<p><?php esc_html_e( 'Alignment and the text displayed around the stars.', 'karina-star-ratings' ); ?></p>
								</div>
								<div class="rp-card-body">
									<?php
									Fields::row_start( __( 'Alignment', 'karina-star-ratings' ) );
									Fields::segmented(
										'alignment',
										$ratingpress_v['alignment'],
										array(
											'start'  => __( 'Start', 'karina-star-ratings' ),
											'center' => __( 'Center', 'karina-star-ratings' ),
											'end'    => __( 'End', 'karina-star-ratings' ),
										),
										__( 'Alignment', 'karina-star-ratings' )
									);
									Fields::row_end();

									Fields::row_start( __( 'Layout', 'karina-star-ratings' ) );
									Fields::segmented(
										'layout',
										$ratingpress_v['layout'],
										array(
											'inline'  => __( 'Inline', 'karina-star-ratings' ),
											'stacked' => __( 'Stacked', 'karina-star-ratings' ),
										),
										__( 'Layout', 'karina-star-ratings' )
									);
									Fields::row_end();

									Fields::row_start( __( 'Heading', 'karina-star-ratings' ), __( 'Optional text displayed before the stars.', 'karina-star-ratings' ), 'heading' );
									Fields::text( 'heading', $ratingpress_v['heading'], __( 'Did you find this article useful?', 'karina-star-ratings' ) );
									Fields::row_end();

									Fields::row_start( __( 'Show legend', 'karina-star-ratings' ), __( 'Text with the average and number of votes.', 'karina-star-ratings' ), 'show_legend' );
									Fields::toggle( 'show_legend', $ratingpress_v['show_legend'] );
									Fields::row_end();

									Fields::row_start( __( 'Legend format', 'karina-star-ratings' ), __( 'Click a placeholder to insert it.', 'karina-star-ratings' ), 'legend' );
									Fields::text( 'legend', $ratingpress_v['legend'], $ratingpress_texts['legend'], 'rp-input-wide' );
									?>
									<div class="rp-placeholders" data-rp-target="<?php echo esc_attr( Fields::id( 'legend' ) ); ?>">
										<?php
										$ratingpress_placeholders = array(
											'{avg}'     => __( 'Average', 'karina-star-ratings' ),
											'{best}'    => __( 'Max', 'karina-star-ratings' ),
											'{count}'   => __( 'Vote count', 'karina-star-ratings' ),
											'{votes}'   => __( '"vote(s)" label', 'karina-star-ratings' ),
											'{percent}' => __( 'Percent', 'karina-star-ratings' ),
										);
										foreach ( $ratingpress_placeholders as $ratingpress_tag => $ratingpress_tag_label ) :
											?>
											<button type="button" class="rp-placeholder" data-rp-insert="<?php echo esc_attr( $ratingpress_tag ); ?>" title="<?php echo esc_attr( $ratingpress_tag_label ); ?>"><code><?php echo esc_html( $ratingpress_tag ); ?></code></button>
										<?php endforeach; ?>
									</div>
									<?php
									Fields::row_end();

									Fields::row_start( __( 'Text when not rated', 'karina-star-ratings' ), __( 'Shown instead of the legend when there are no votes yet. {type} is replaced with the post type name.', 'karina-star-ratings' ), 'greeting' );
									Fields::text( 'greeting', $ratingpress_v['greeting'], $ratingpress_texts['greeting'], 'rp-input-wide' );
									Fields::row_end();

									Fields::row_start( __( 'Decimal places', 'karina-star-ratings' ), '', 'decimals' );
									Fields::segmented(
										'decimals',
										(string) $ratingpress_v['decimals'],
										array(
											'0' => '4',
											'1' => '4.3',
											'2' => '4.27',
										),
										__( 'Decimal places', 'karina-star-ratings' )
									);
									Fields::row_end();
									?>
								</div>
							</div>
						</div>

						<aside class="rp-split-side">
							<div class="rp-card rp-preview-card">
								<div class="rp-card-head">
									<h2><?php esc_html_e( 'Live preview', 'karina-star-ratings' ); ?></h2>
									<div class="rp-preview-modes" role="group" aria-label="<?php esc_attr_e( 'Preview background', 'karina-star-ratings' ); ?>">
										<button type="button" class="rp-preview-mode is-active" data-rp-preview-bg="light" aria-pressed="true"><span class="dashicons dashicons-lightbulb" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Light background', 'karina-star-ratings' ); ?></span></button>
										<button type="button" class="rp-preview-mode" data-rp-preview-bg="dark" aria-pressed="false"><span class="dashicons dashicons-admin-appearance" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Dark background', 'karina-star-ratings' ); ?></span></button>
									</div>
								</div>
								<div class="rp-preview-stage" data-bg="light">
									<div id="rp-preview" class="ratingpress rp-context-preview is-interactive" data-rp-post="0">
										<div class="rp-heading" hidden></div>
										<div class="rp-stars"></div>
										<div class="rp-legend"></div>
									</div>
								</div>
								<p class="rp-preview-note"><?php esc_html_e( 'Hover the stars to test the hover color. Sample data: 3.7 average from 128 votes.', 'karina-star-ratings' ); ?></p>
							</div>
						</aside>
					</div>
				</section>

				<?php /* ------------------------------------------------- Schema */ ?>
				<section class="rp-panel" id="rp-panel-schema" role="tabpanel" aria-labelledby="rp-tab-schema" data-rp-panel="schema" hidden>
					<div class="rp-card">
						<div class="rp-card-head">
							<h2><?php esc_html_e( 'Rich snippets (Schema.org)', 'karina-star-ratings' ); ?></h2>
							<p><?php esc_html_e( 'Karina Star Ratings prints AggregateRating JSON-LD for every rated post so search engines can show stars in results.', 'karina-star-ratings' ); ?></p>
						</div>
						<div class="rp-card-body">
							<?php
							Fields::row_start( __( 'Output structured data', 'karina-star-ratings' ), __( 'Adds a JSON-LD script to single posts that have ratings, only on pages where the rating widget is displayed.', 'karina-star-ratings' ), 'schema_enabled' );
							Fields::toggle( 'schema_enabled', $ratingpress_v['schema_enabled'] );
							Fields::row_end();

							Fields::row_start( __( 'Default schema type', 'karina-star-ratings' ), __( 'Can be overridden per post in the editor sidebar.', 'karina-star-ratings' ), 'schema_type' );
							Fields::select( 'schema_type', $ratingpress_v['schema_type'], $args['schema_types'] );
							Fields::row_end();

							Fields::row_start( __( 'Minimum votes', 'karina-star-ratings' ), __( 'Only output the snippet once a post has at least this many votes.', 'karina-star-ratings' ), 'schema_min_votes' );
							Fields::number( 'schema_min_votes', $ratingpress_v['schema_min_votes'], 1, 1000, __( 'votes', 'karina-star-ratings' ) );
							Fields::row_end();

							Fields::row_start( __( 'Extended properties', 'karina-star-ratings' ), __( 'Include description, featured image, author and publish dates.', 'karina-star-ratings' ), 'schema_extended' );
							Fields::toggle( 'schema_extended', $ratingpress_v['schema_extended'] );
							Fields::row_end();
							?>
						</div>
					</div>

					<div class="rp-callout rp-callout-info">
						<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
						<div>
							<strong><?php esc_html_e( 'Which type should I use?', 'karina-star-ratings' ); ?></strong>
							<p><?php esc_html_e( 'Google only shows review stars for specific types. "Article" and "BlogPosting" are not eligible, so Karina Star Ratings uses "Creative Work Series" for posts by default. Types such as Event, Recipe or Product may require extra properties that you can add with the ratingpress_schema filter. Google does not show stars for self-serving reviews of a Local Business or Organization.', 'karina-star-ratings' ); ?></p>
						</div>
					</div>

					<?php if ( '' !== $args['schema_preview'] ) : ?>
						<div class="rp-card">
							<div class="rp-card-head rp-card-head-row">
								<div>
									<h2><?php esc_html_e( 'Example output', 'karina-star-ratings' ); ?></h2>
									<p><?php esc_html_e( 'Generated from your latest post (saved settings).', 'karina-star-ratings' ); ?></p>
								</div>
								<div class="rp-card-actions">
									<button type="button" class="button" data-rp-copy="#rp-schema-code"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span> <?php esc_html_e( 'Copy', 'karina-star-ratings' ); ?></button>
									<a class="button" href="https://search.google.com/test/rich-results" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-external" aria-hidden="true"></span> <?php esc_html_e( 'Rich Results Test', 'karina-star-ratings' ); ?></a>
								</div>
							</div>
							<pre class="rp-code" dir="ltr"><code id="rp-schema-code"><?php echo esc_html( $args['schema_preview'] ); ?></code></pre>
						</div>
					<?php endif; ?>
				</section>

				<?php /* -------------------------------------------------- Tools */ ?>
				<section class="rp-panel" id="rp-panel-tools" role="tabpanel" aria-labelledby="rp-tab-tools" data-rp-panel="tools" hidden>
					<div class="rp-card">
						<div class="rp-card-head">
							<h2><?php esc_html_e( 'Import from kk Star Ratings', 'karina-star-ratings' ); ?></h2>
							<p><?php esc_html_e( 'Bring over the vote counts and averages of every post. Safe to run more than once: numbers are never doubled.', 'karina-star-ratings' ); ?></p>
						</div>
						<div class="rp-card-body">
							<div class="rp-import" data-rp-tool="import-kk" data-rp-total="<?php echo esc_attr( $args['kk_count'] ); ?>">
								<div class="rp-import-summary">
									<span class="rp-import-count"><?php echo esc_html( number_format_i18n( $args['kk_count'] ) ); ?></span>
									<span class="rp-import-label"><?php esc_html_e( 'posts with kk Star Ratings data found', 'karina-star-ratings' ); ?></span>
								</div>

								<?php if ( $args['kk_imported'] ) : ?>
									<p class="rp-status rp-status-success">
										<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
										<?php
										/* translators: %s: human readable time difference, e.g. "2 days". */
										echo esc_html( sprintf( __( 'Last imported %s ago.', 'karina-star-ratings' ), human_time_diff( $args['kk_imported'] ) ) );
										?>
									</p>
								<?php endif; ?>

								<?php if ( $args['kk_active'] ) : ?>
									<p class="rp-status rp-status-warning">
										<span class="dashicons dashicons-warning" aria-hidden="true"></span>
										<?php esc_html_e( 'kk Star Ratings is still active. After importing, deactivate it to avoid two rating widgets and duplicated rich snippets.', 'karina-star-ratings' ); ?>
									</p>
								<?php endif; ?>

								<div class="rp-progress" hidden>
									<div class="rp-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span></span></div>
									<p class="rp-progress-text" aria-live="polite"></p>
								</div>

								<button type="button" class="button button-primary rp-btn" data-rp-run="import-kk" <?php disabled( 0 === $args['kk_count'] ); ?>>
									<span class="dashicons dashicons-download" aria-hidden="true"></span>
									<?php esc_html_e( 'Start import', 'karina-star-ratings' ); ?>
								</button>
							</div>
						</div>
					</div>

					<div class="rp-card">
						<div class="rp-card-head">
							<h2><?php esc_html_e( 'Maintenance', 'karina-star-ratings' ); ?></h2>
							<p><?php esc_html_e( 'Compatibility and data options.', 'karina-star-ratings' ); ?></p>
						</div>
						<div class="rp-card-body">
							<?php
							Fields::row_start( __( 'Legacy shortcodes', 'karina-star-ratings' ), __( 'Render old [kkstarratings] and [kkratings] shortcodes with Karina Star Ratings once kk Star Ratings is deactivated.', 'karina-star-ratings' ), 'kk_compat' );
							Fields::toggle( 'kk_compat', $ratingpress_v['kk_compat'] );
							Fields::row_end();

							Fields::row_start( __( 'Recalculate ratings', 'karina-star-ratings' ), __( 'Rebuild cached averages from the votes table. Useful after editing the database manually.', 'karina-star-ratings' ) );
							?>
							<div class="rp-inline-tool" data-rp-tool="recalculate">
								<button type="button" class="button rp-btn" data-rp-run="recalculate"><span class="dashicons dashicons-update" aria-hidden="true"></span> <?php esc_html_e( 'Recalculate', 'karina-star-ratings' ); ?></button>
								<span class="rp-inline-status" aria-live="polite"></span>
							</div>
							<?php
							Fields::row_end();

							Fields::row_start( __( 'Delete data on uninstall', 'karina-star-ratings' ), __( 'Remove all votes, ratings and settings when the plugin is deleted.', 'karina-star-ratings' ), 'delete_data' );
							Fields::toggle( 'delete_data', $ratingpress_v['delete_data'] );
							Fields::row_end();
							?>
						</div>
					</div>

					<div class="rp-card rp-card-danger">
						<div class="rp-card-head">
							<h2><?php esc_html_e( 'Danger zone', 'karina-star-ratings' ); ?></h2>
							<p><?php esc_html_e( 'Permanently delete every vote and rating on this site. Settings are kept.', 'karina-star-ratings' ); ?></p>
						</div>
						<div class="rp-card-body">
							<div class="rp-inline-tool" data-rp-tool="reset">
								<button type="button" class="button rp-btn rp-btn-danger" data-rp-run="reset"><span class="dashicons dashicons-trash" aria-hidden="true"></span> <?php esc_html_e( 'Delete all ratings', 'karina-star-ratings' ); ?></button>
								<span class="rp-inline-status" aria-live="polite"></span>
							</div>
						</div>
					</div>
				</section>

				<?php /* --------------------------------------------------- Help */ ?>
				<section class="rp-panel" id="rp-panel-help" role="tabpanel" aria-labelledby="rp-tab-help" data-rp-panel="help" hidden>
					<?php View::display( 'admin/partials/help' ); ?>
				</section>
			</div>
		</div>

		<div class="rp-savebar">
			<div class="rp-savebar-inner">
				<span class="rp-savebar-status" aria-live="polite">
					<span class="rp-dot" aria-hidden="true"></span>
					<span class="rp-savebar-text"><?php esc_html_e( 'All changes saved', 'karina-star-ratings' ); ?></span>
				</span>
				<?php submit_button( __( 'Save changes', 'karina-star-ratings' ), 'primary rp-btn rp-save', 'submit', false ); ?>
			</div>
		</div>
	</form>
</div>
