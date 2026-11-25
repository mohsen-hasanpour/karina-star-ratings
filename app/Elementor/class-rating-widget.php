<?php
/**
 * Elementor widget: Karina Star Rating.
 *
 * Only loaded through the Elementor hooks, so Elementor classes are always available.
 *
 * @package Karina_Star_Ratings
 */

namespace RatingPress\Elementor;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use RatingPress\Core\Icons;
use RatingPress\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Rating_Widget
 */
class Rating_Widget extends Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'ratingpress-rating';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Star Rating', 'karina-star-ratings' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-rating';
	}

	/**
	 * Widget categories.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( 'ratingpress' );
	}

	/**
	 * Search keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'rating', 'stars', 'review', 'vote', 'schema', 'ratingpress' );
	}

	/**
	 * Stylesheets required by the widget.
	 *
	 * @return string[]
	 */
	public function get_style_depends() {
		return array( 'ratingpress' );
	}

	/**
	 * Scripts required by the widget.
	 *
	 * @return string[]
	 */
	public function get_script_depends() {
		return array( 'ratingpress' );
	}

	/**
	 * The output changes with every vote, so Elementor must not cache it.
	 *
	 * @return bool
	 */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/**
	 * Use the optimized markup without an inner wrapper (Elementor 3.24+).
	 *
	 * @return bool
	 */
	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/**
	 * Registers the widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$settings = Plugin::instance()->settings();

		$this->start_controls_section(
			'section_rating',
			array(
				'label' => esc_html__( 'Rating', 'karina-star-ratings' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'source',
			array(
				'label'   => esc_html__( 'Source', 'karina-star-ratings' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'current',
				'options' => array(
					'current' => esc_html__( 'Current post', 'karina-star-ratings' ),
					'custom'  => esc_html__( 'Specific post ID', 'karina-star-ratings' ),
				),
			)
		);

		$this->add_control(
			'post_id',
			array(
				'label'     => esc_html__( 'Post ID', 'karina-star-ratings' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'condition' => array( 'source' => 'custom' ),
			)
		);

		$icons = array( '' => esc_html__( 'Default', 'karina-star-ratings' ) );
		foreach ( Icons::all() as $key => $icon ) {
			$icons[ $key ] = $icon['label'];
		}

		$this->add_control(
			'icon',
			array(
				'label'   => esc_html__( 'Icon', 'karina-star-ratings' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $icons,
			)
		);

		$this->add_control(
			'heading',
			array(
				'label'       => esc_html__( 'Heading', 'karina-star-ratings' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => (string) $settings->get( 'heading' ),
				'placeholder' => esc_html__( 'Rate this article', 'karina-star-ratings' ),
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'show_legend',
			array(
				'label'        => esc_html__( 'Show legend', 'karina-star-ratings' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => $settings->get( 'show_legend' ) ? 'yes' : '',
			)
		);

		$this->add_control(
			'readonly',
			array(
				'label'        => esc_html__( 'Read only', 'karina-star-ratings' ),
				'description'  => esc_html__( 'Display the rating without allowing votes.', 'karina-star-ratings' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => esc_html__( 'Layout', 'karina-star-ratings' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''        => esc_html__( 'Default', 'karina-star-ratings' ),
					'inline'  => esc_html__( 'Inline', 'karina-star-ratings' ),
					'stacked' => esc_html__( 'Stacked', 'karina-star-ratings' ),
				),
			)
		);

		$this->add_responsive_control(
			'alignment',
			array(
				'label'     => esc_html__( 'Alignment', 'karina-star-ratings' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array(
						'title' => esc_html__( 'Start', 'karina-star-ratings' ),
						'icon'  => is_rtl() ? 'eicon-text-align-right' : 'eicon-text-align-left',
					),
					'center'     => array(
						'title' => esc_html__( 'Center', 'karina-star-ratings' ),
						'icon'  => 'eicon-text-align-center',
					),
					'flex-end'   => array(
						'title' => esc_html__( 'End', 'karina-star-ratings' ),
						'icon'  => is_rtl() ? 'eicon-text-align-left' : 'eicon-text-align-right',
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .ratingpress' => '--rp-justify: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_icons',
			array(
				'label' => esc_html__( 'Icons', 'karina-star-ratings' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'size',
			array(
				'label'      => esc_html__( 'Size', 'karina-star-ratings' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array(
					'px' => array(
						'min' => 10,
						'max' => 96,
					),
					'em' => array(
						'min'  => 0.5,
						'max'  => 6,
						'step' => 0.1,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ratingpress' => '--rp-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => esc_html__( 'Spacing', 'karina-star-ratings' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 32,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ratingpress' => '--rp-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$colors = array(
			'color_active'   => array( esc_html__( 'Active color', 'karina-star-ratings' ), '--rp-active' ),
			'color_inactive' => array( esc_html__( 'Inactive color', 'karina-star-ratings' ), '--rp-inactive' ),
			'color_hover'    => array( esc_html__( 'Hover color', 'karina-star-ratings' ), '--rp-hover' ),
		);

		foreach ( $colors as $control_id => $color ) {
			$this->add_control(
				$control_id,
				array(
					'label'     => $color[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array(
						'{{WRAPPER}} .ratingpress' => $color[1] . ': {{VALUE}};',
					),
				)
			);
		}

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_text',
			array(
				'label' => esc_html__( 'Text', 'karina-star-ratings' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'heading_color',
			array(
				'label'     => esc_html__( 'Heading color', 'karina-star-ratings' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .rp-heading' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'heading_typography',
				'label'    => esc_html__( 'Heading typography', 'karina-star-ratings' ),
				'selector' => '{{WRAPPER}} .rp-heading',
			)
		);

		$this->add_control(
			'legend_color',
			array(
				'label'     => esc_html__( 'Legend color', 'karina-star-ratings' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .rp-legend' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'legend_typography',
				'label'    => esc_html__( 'Legend typography', 'karina-star-ratings' ),
				'selector' => '{{WRAPPER}} .rp-legend',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Renders the widget.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$post_id  = ( isset( $settings['source'] ) && 'custom' === $settings['source'] ) ? absint( $settings['post_id'] ) : get_the_ID();
		$editing  = \Elementor\Plugin::$instance->editor->is_edit_mode();

		$args = array(
			'context'     => 'elementor',
			'readonly'    => $editing || 'yes' === $settings['readonly'],
			'show_legend' => 'yes' === $settings['show_legend'],
			'heading'     => isset( $settings['heading'] ) ? sanitize_text_field( $settings['heading'] ) : '',
		);

		if ( ! empty( $settings['icon'] ) ) {
			$args['icon'] = sanitize_key( $settings['icon'] );
		}
		if ( ! empty( $settings['layout'] ) ) {
			$args['layout'] = sanitize_key( $settings['layout'] );
		}

		$html = Plugin::instance()->renderer()->render( (int) $post_id, $args );

		if ( '' === $html && $editing ) {
			echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'Karina Star Ratings: ratings are disabled for this content or globally.', 'karina-star-ratings' ) . '</div>';
			return;
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the rating template.
	}
}
