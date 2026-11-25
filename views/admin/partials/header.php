<?php
/**
 * Shared header of the plugin admin screens.
 *
 * @package Karina_Star_Ratings
 *
 * @var array $args {
 *     @type string $active   Active page: dashboard|settings.
 *     @type string $title    Screen title.
 *     @type string $subtitle Screen subtitle.
 * }
 */

defined( 'ABSPATH' ) || exit;

$ratingpress_pages = array(
	'dashboard' => array(
		'label' => __( 'Dashboard', 'karina-star-ratings' ),
		'url'   => admin_url( 'admin.php?page=ratingpress' ),
		'icon'  => 'dashicons-chart-bar',
	),
	'settings'  => array(
		'label' => __( 'Settings', 'karina-star-ratings' ),
		'url'   => admin_url( 'admin.php?page=ratingpress-settings' ),
		'icon'  => 'dashicons-admin-generic',
	),
);
?>
<header class="rp-header">
	<div class="rp-header-inner">
		<div class="rp-brand">
			<span class="rp-logo" aria-hidden="true">
				<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" focusable="false"><path d="M12 2.5l2.94 5.96 6.58.96-4.76 4.64 1.12 6.55L12 17.52 6.12 20.61l1.12-6.55L2.48 9.42l6.58-.96z"/></svg>
			</span>
			<div class="rp-brand-text">
				<h1 class="rp-title">
					<?php echo esc_html( $args['title'] ); ?>
					<span class="rp-version">v<?php echo esc_html( RATINGPRESS_VERSION ); ?></span>
				</h1>
				<p class="rp-subtitle"><?php echo esc_html( $args['subtitle'] ); ?></p>
			</div>
		</div>
		<nav class="rp-topnav" aria-label="<?php esc_attr_e( 'Karina Star Ratings navigation', 'karina-star-ratings' ); ?>">
			<?php foreach ( $ratingpress_pages as $ratingpress_key => $ratingpress_page ) : ?>
				<a
					href="<?php echo esc_url( $ratingpress_page['url'] ); ?>"
					class="rp-topnav-link<?php echo $ratingpress_key === $args['active'] ? ' is-active' : ''; ?>"
					<?php echo $ratingpress_key === $args['active'] ? 'aria-current="page"' : ''; ?>
				>
					<span class="dashicons <?php echo esc_attr( $ratingpress_page['icon'] ); ?>" aria-hidden="true"></span>
					<?php echo esc_html( $ratingpress_page['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
	</div>
</header>
<hr class="wp-header-end">
