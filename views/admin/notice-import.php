<?php
/**
 * Admin notice offering the kk Star Ratings import.
 *
 * @package Karina_Star_Ratings
 *
 * @var array $args {
 *     @type int    $count       Number of posts with kk Star Ratings data.
 *     @type string $import_url  URL of the import tool.
 *     @type string $dismiss_url Dismiss URL.
 * }
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="notice notice-info rp-notice">
	<p>
		<strong><?php esc_html_e( 'Karina Star Ratings', 'karina-star-ratings' ); ?>:</strong>
		<?php
		echo esc_html(
			sprintf(
				/* translators: %s: number of posts. */
				_n( 'We found kk Star Ratings data on %s post. Import it to keep your existing stars and rich snippets.', 'We found kk Star Ratings data on %s posts. Import it to keep your existing stars and rich snippets.', $args['count'], 'karina-star-ratings' ),
				number_format_i18n( $args['count'] )
			)
		);
		?>
	</p>
	<p>
		<a class="button button-primary" href="<?php echo esc_url( $args['import_url'] ); ?>"><?php esc_html_e( 'Import ratings', 'karina-star-ratings' ); ?></a>
		<a class="button-link" href="<?php echo esc_url( $args['dismiss_url'] ); ?>"><?php esc_html_e( 'Dismiss', 'karina-star-ratings' ); ?></a>
	</p>
</div>
