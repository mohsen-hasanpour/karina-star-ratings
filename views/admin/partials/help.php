<?php
/**
 * "Shortcodes & help" tab content.
 *
 * @package Karina_Star_Ratings
 */

defined( 'ABSPATH' ) || exit;

$ratingpress_shortcodes = array(
	array(
		'code' => '[ratingpress]',
		'desc' => __( 'Rating widget for the current post.', 'karina-star-ratings' ),
	),
	array(
		'code' => '[ratingpress id="123"]',
		'desc' => __( 'Rating widget for a specific post.', 'karina-star-ratings' ),
	),
	array(
		'code' => '[ratingpress readonly="true" legend="false" size="18"]',
		'desc' => __( 'Read-only stars without text, 18px icons.', 'karina-star-ratings' ),
	),
	array(
		'code' => '[ratingpress heading="Rate this article" align="center" layout="stacked" icon="heart"]',
		'desc' => __( 'Custom heading, centered and stacked, with hearts.', 'karina-star-ratings' ),
	),
	array(
		'code' => '[ratingpress_top count="5" post_type="post" min_votes="3"]',
		'desc' => __( 'List of the top rated posts.', 'karina-star-ratings' ),
	),
);

$ratingpress_attributes = array(
	'id'       => __( 'Post ID. Defaults to the current post.', 'karina-star-ratings' ),
	'readonly' => __( 'true to display without voting.', 'karina-star-ratings' ),
	'legend'   => __( 'true or false to show the text after the stars.', 'karina-star-ratings' ),
	'heading'  => __( 'Text displayed before the stars.', 'karina-star-ratings' ),
	'icon'     => __( 'star, heart or circle.', 'karina-star-ratings' ),
	'align'    => __( 'start, center or end.', 'karina-star-ratings' ),
	'layout'   => __( 'inline or stacked.', 'karina-star-ratings' ),
	'size'     => __( 'Icon size in pixels.', 'karina-star-ratings' ),
	'class'    => __( 'Extra CSS classes.', 'karina-star-ratings' ),
);

$ratingpress_php = "<?php\nif ( function_exists( 'ratingpress_the_rating' ) ) {\n\tratingpress_the_rating();\n}\n\n// Data only: average, count, best, percent.\n\$rating = ratingpress_get_rating( get_the_ID() );";

$ratingpress_hooks = array(
	'ratingpress_schema'        => __( 'Filter the JSON-LD array of a post (add properties or return an empty array to disable).', 'karina-star-ratings' ),
	'ratingpress_auto_insert'   => __( 'Filter whether the widget is added to a post automatically.', 'karina-star-ratings' ),
	'ratingpress_can_vote'      => __( 'Return a WP_Error to reject a vote with your own message.', 'karina-star-ratings' ),
	'ratingpress_vote_recorded' => __( 'Action fired after a vote is saved.', 'karina-star-ratings' ),
	'ratingpress_rating_html'   => __( 'Filter the final widget HTML.', 'karina-star-ratings' ),
	'ratingpress_client_ip'     => __( 'Filter the visitor IP, e.g. behind Cloudflare or a trusted proxy.', 'karina-star-ratings' ),
	'ratingpress_print_schema'  => __( 'Filter whether the JSON-LD snippet is printed on the current page.', 'karina-star-ratings' ),
	'ratingpress_schema_types'  => __( 'Filter the list of schema types offered in the settings.', 'karina-star-ratings' ),
);
?>
<div class="rp-card">
	<div class="rp-card-head">
		<h2><?php esc_html_e( 'Shortcodes', 'karina-star-ratings' ); ?></h2>
		<p><?php esc_html_e( 'Paste these into any post, page, widget or page builder text module.', 'karina-star-ratings' ); ?></p>
	</div>
	<div class="rp-card-body rp-snippets">
		<?php foreach ( $ratingpress_shortcodes as $ratingpress_index => $ratingpress_shortcode ) : ?>
			<div class="rp-snippet">
				<code id="rp-sc-<?php echo esc_attr( $ratingpress_index ); ?>" dir="ltr"><?php echo esc_html( $ratingpress_shortcode['code'] ); ?></code>
				<span class="rp-snippet-desc"><?php echo esc_html( $ratingpress_shortcode['desc'] ); ?></span>
				<button type="button" class="button button-small rp-copy" data-rp-copy="#rp-sc-<?php echo esc_attr( $ratingpress_index ); ?>">
					<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Copy shortcode', 'karina-star-ratings' ); ?></span>
				</button>
			</div>
		<?php endforeach; ?>

		<details class="rp-details">
			<summary><?php esc_html_e( 'All [ratingpress] attributes', 'karina-star-ratings' ); ?></summary>
			<table class="rp-table">
				<tbody>
					<?php foreach ( $ratingpress_attributes as $ratingpress_attr => $ratingpress_attr_desc ) : ?>
						<tr>
							<th scope="row"><code><?php echo esc_html( $ratingpress_attr ); ?></code></th>
							<td><?php echo esc_html( $ratingpress_attr_desc ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</details>
	</div>
</div>

<div class="rp-grid-2">
	<div class="rp-card">
		<div class="rp-card-head">
			<h2><span class="dashicons dashicons-welcome-widgets-menus" aria-hidden="true"></span> <?php esc_html_e( 'Elementor & block editor', 'karina-star-ratings' ); ?></h2>
		</div>
		<div class="rp-card-body rp-prose">
			<p><?php esc_html_e( 'Elementor: search for "Star Rating" in the widget panel (Karina Star Ratings category). Style sizes, colors and typography per widget, including in Theme Builder single templates.', 'karina-star-ratings' ); ?></p>
			<p><?php esc_html_e( 'Block editor: add the "Karina Star Rating" block. Inside a Query Loop it shows the rating of each post.', 'karina-star-ratings' ); ?></p>
		</div>
	</div>

	<div class="rp-card">
		<div class="rp-card-head rp-card-head-row">
			<h2><span class="dashicons dashicons-editor-code" aria-hidden="true"></span> <?php esc_html_e( 'Theme developers', 'karina-star-ratings' ); ?></h2>
			<button type="button" class="button button-small rp-copy" data-rp-copy="#rp-php-snippet">
				<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Copy code', 'karina-star-ratings' ); ?></span>
			</button>
		</div>
		<pre class="rp-code" dir="ltr"><code id="rp-php-snippet"><?php echo esc_html( $ratingpress_php ); ?></code></pre>
		<div class="rp-card-body rp-prose">
			<p>
				<?php
				printf(
					/* translators: %s: template path. */
					esc_html__( 'Override the markup by copying views/frontend/rating.php to %s in your theme.', 'karina-star-ratings' ),
					'<code>ratingpress/rating.php</code>'
				);
				?>
			</p>
		</div>
	</div>
</div>

<div class="rp-card">
	<div class="rp-card-head">
		<h2><?php esc_html_e( 'Hooks', 'karina-star-ratings' ); ?></h2>
	</div>
	<div class="rp-card-body">
		<table class="rp-table">
			<tbody>
				<?php foreach ( $ratingpress_hooks as $ratingpress_hook => $ratingpress_hook_desc ) : ?>
					<tr>
						<th scope="row"><code><?php echo esc_html( $ratingpress_hook ); ?></code></th>
						<td><?php echo esc_html( $ratingpress_hook_desc ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
