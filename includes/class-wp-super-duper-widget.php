<?php
/**
 * WP Super Duper Widget Class
 *
 * This is the widget-enabled version of the class. It is loaded conditionally
 * when the 'SUPER_DUPER_LOAD_WIDGET' constant is true. It extends WP_Widget
 * to provide full widget functionality.
 *
 * @version 1.2.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Super_Duper extends WP_Widget {

	/**
	 * Import all shared logic from the traits.
	 */
	use WP_Super_Duper_Initializer,
		WP_Super_Duper_Gutenberg_Block,
		WP_Super_Duper_Page_Builders,
		WP_Super_Duper_Shortcode_Inserter,
		WP_Super_Duper_Widget_Form,
		WP_Super_Duper_Output_Handler,
		WP_Super_Duper_Utilities;

	// --- Class Properties ---

	public $version = SUPER_DUPER_VER;
	public $font_awesome_icon_version = "5.11.2";
	public $block_code;
	public $options;
	public $base_id;

	/**
	 * FIX: Explicitly declared to prevent undefined property errors.
	 * This shadows the parent WP_Widget property but ensures consistency.
	 * @var string
	 */
	public $id_base;

	public $settings_hash;
	public $id;
	public $arguments = array();
	public $instance = array();
	private $class_name;
	public $dynamic_fields = array();
	public $url = '';

	/**
	 * The constructor for the widget class.
	 *
	 * @param array $options The configuration options for the instance.
	 */
	public function __construct( $options ) {
		$this->initialize_super_duper( $options, true );
	}

	/**
	 * Registers the widget with WordPress.
	 */
	public function _register() {
		if ( ! empty( $this->options['output_types'] ) && in_array( 'widget', $this->options['output_types'], true ) ) {
			parent::_register();
		}
	}

	/**
	 * Override WP_Widget::get_field_name() to use single-bracket format.
	 *
	 * WP_Widget::get_field_name() returns widget-{id}[{number}][{name}] (two
	 * levels). The shortcode inserter JS expects single-bracket format:
	 * widget-{id_base}[{field_name}].
	 *
	 * @param string $field_name The field (argument) name.
	 * @return string
	 */
	public function get_field_name( $field_name ) {
		return 'widget-' . $this->id_base . '[' . $field_name . ']';
	}

	/**
	 * Override WP_Widget::get_field_id() to use a consistent format.
	 *
	 * @param string $field_name The field (argument) name.
	 * @return string
	 */
	public function get_field_id( $field_name ) {
		$field_name = ltrim( str_replace( array( '[]', '[', ']' ), array( '', '-', '' ), $field_name ), '-' );
		return 'widget-' . $this->id_base . '--' . $field_name;
	}
}
