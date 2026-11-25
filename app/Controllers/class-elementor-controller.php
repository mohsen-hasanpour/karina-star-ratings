<?php
/**
 * Elementor integration. Hooks only fire when Elementor is active.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Controllers;

use RatingPress\Elementor\Rating_Widget;

defined( 'ABSPATH' ) || exit;

/**
 * Class Elementor_Controller
 */
class Elementor_Controller {

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );

		if ( defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, '3.5.0', '<' ) ) {
			add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widgets_legacy' ) );
			return;
		}

		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
	}

	/**
	 * Adds a Karina Star Ratings category to the Elementor panel.
	 *
	 * @param \Elementor\Elements_Manager $manager Elements manager.
	 * @return void
	 */
	public function register_category( $manager ) {
		$manager->add_category(
			'ratingpress',
			array(
				'title' => esc_html__( 'Karina Star Ratings', 'karina-star-ratings' ),
				'icon'  => 'eicon-rating',
			)
		);
	}

	/**
	 * Registers widgets (Elementor 3.5+).
	 *
	 * @param \Elementor\Widgets_Manager $manager Widgets manager.
	 * @return void
	 */
	public function register_widgets( $manager ) {
		$manager->register( new Rating_Widget() );
	}

	/**
	 * Registers widgets on Elementor versions older than 3.5.
	 *
	 * @return void
	 */
	public function register_widgets_legacy() {
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Rating_Widget() );
	}
}
