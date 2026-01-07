<?php
/**
 * WP Super Duper Initializer Trait
 *
 * This trait handles the complex construction and initialization logic for the
 * WP_Super_Duper class. It is used by both shell classes to ensure consistent
 * setup and hook registration.
 *
 * @version 1.2.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait WP_Super_Duper_Initializer {

	/**
	 * The main initialization method for the framework.
	 *
	 * This replaces the original __construct method and is called by the shell classes.
	 *
	 * @param array $options The configuration options for the instance.
	 * @param bool  $is_widget_context True if the class is being used as a widget.
	 */
	public function initialize_super_duper( $options, $is_widget_context ) {
		global $sd_widgets;

		$sd_widgets[ $options['base_id'] ] = array(
			'name'       => $options['name'],
			'class_name' => $options['class_name'],
			'output_types' => !empty($options['output_types']) ? $options['output_types'] : array()
		);
		$this->base_id = $options['base_id'];

		// FIX: Set the id_base property to prevent undefined property errors.
		$this->id_base = $options['base_id'];

		// Manually set the 'id' property for backwards compatibility.
		$this->id = $options['base_id'];

		// Filter the options before processing.
		$options = apply_filters( "wp_super_duper_options", $options );
		$options = apply_filters( "wp_super_duper_options_{$this->base_id}", $options );
		$options = $this->add_name_from_key( $options );
		$this->options = $options;

		$this->arguments = isset( $options['arguments'] ) ? $options['arguments'] : array();

		// Nested blocks cannot function as widgets.
		if ( ! empty( $this->options['nested-block'] ) ) {
			if ( empty( $this->options['output_types'] ) ) {
				$this->options['output_types'] = array('shortcode','block');
			} elseif ( ( $key = array_search( 'widget', $this->options['output_types'] ) ) !== false ) {
				unset( $this->options['output_types'][ $key ] );
			}
		}

		// Call the parent WP_Widget constructor ONLY when in a widget context.
		if ( $is_widget_context && ( empty( $this->options['output_types'] ) || in_array( 'widget', $this->options['output_types'] ) ) ) {
			parent::__construct( $options['base_id'], $options['name'], $options['widget_ops'] );
		}

		if ( isset( $options['class_name'] ) ) {
			$this->class_name = $options['class_name'];
			$this->register_shortcode();

			if ( function_exists( 'fusion_builder_map' ) ) {
				add_action( 'init', array( $this, 'register_fusion_element' ) );
			}
			if ( class_exists( '\Bricks\Elements', false ) ) {
				add_action( 'init', array( $this, 'load_bricks_element_class' ) );
			}

//			global $oooonce;
//			if ( !empty($this->options['base_id']) && (
//				$this->options['base_id'] === 'bs_text'
//				|| $this->options['base_id'] === 'super_card'
//				|| $this->options['base_id'] === 'bs_search'
//				|| $this->options['base_id'] === 'bs_container'
//				)
//			) {
//				// @todo testing
////				echo '@@@'.$this->options['base_id'];exit;
////				add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_block_assets' ] );
//				add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_assets' ] );
//			}elseif ( empty( $this->options['output_types'] ) || in_array( 'block', $this->options['output_types'] ) ) {
//				if(! $oooonce) add_action( 'admin_enqueue_scripts', array( $this, 'register_block' ) );
//				$oooonce = true;
//			}

			add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_assets' ] );


		}

		// Add global scripts and styles only once.
		global $sd_widget_scripts;
		if ( ! $sd_widget_scripts ) {
			$sd_widget_scripts = true;

			wp_add_inline_script( 'admin-widgets', $this->widget_js() );
			wp_add_inline_script( 'customize-controls', $this->widget_js() );
			wp_add_inline_style( 'widgets', $this->widget_css() );

			add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'elementor_editor_styles' ) );

			// Shortcode inserter button hooks.
			add_action( 'media_buttons', array( $this, 'wp_media_buttons' ), 1 );
			add_action( 'media_buttons', array( __CLASS__, 'shortcode_insert_button' ) );

			if ( function_exists( 'generate_sections_sections_metabox' ) ) {
				add_action( 'generate_sections_metabox', array( __CLASS__, 'shortcode_insert_button_script' ) );
			}
			if ( function_exists( 'et_builder_is_tb_admin_screen' ) && et_builder_is_tb_admin_screen() ) {
				add_thickbox();
				add_action( 'admin_footer', array( __CLASS__, 'shortcode_insert_button_script' ) );
			}

			if ( sd_is_preview() ) {
				add_action( 'wp_footer', array( __CLASS__, 'shortcode_insert_button_script' ) );
				add_action( 'elementor/editor/after_enqueue_scripts', array( __CLASS__, 'shortcode_insert_button_script' ) );
			}

			add_action( 'wp_print_footer_scripts', array( __CLASS__, 'maybe_cornerstone_builder' ) );

			// AJAX hooks.
			add_action( 'wp_ajax_super_duper_get_widget_settings', array( __CLASS__, 'get_widget_settings' ) );
			add_action( 'wp_ajax_super_duper_get_picker', array( __CLASS__, 'get_picker' ) );

			// Generator meta tag.
			add_action( 'admin_head', array( $this, 'generator' ), 99 );
			add_action( 'wp_head', array( $this, 'generator' ), 99 );
		}

		do_action( 'wp_super_duper_widget_init', $options, $this );
	}
}
