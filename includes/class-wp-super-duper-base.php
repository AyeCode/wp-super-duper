<?php
/**
 * WP Super Duper Base Class
 *
 * This is the base (non-widget) version of the class. It is loaded by default
 * to provide all block and shortcode functionality without the overhead of
 * extending the WP_Widget class.
 *
 * @version 1.2.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Super_Duper {

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
	 * This is the equivalent of the WP_Widget property.
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
	 * The constructor for the base class.
	 *
	 * @param array $options The configuration options for the instance.
	 */
	public function __construct( $options ) {
		$this->initialize_super_duper( $options, false );
	}

	/**
	 * Placeholder for the widget registration method.
	 */
	public function _register() {
		// This version does not register as a widget.
	}

	/**
	 * Fallback for WP_Widget::get_field_id().
	 *
	 * WP_Widget::get_field_id() is used by the WP_Super_Duper_Widget_Form trait
	 * when rendering form inputs. Since this class does not extend WP_Widget, we
	 * provide a compatible implementation so the shortcode inserter can render
	 * widget settings forms without a fatal error.
	 *
	 * @param string $field_name The field (argument) name.
	 * @return string
	 */
	public function get_field_id( $field_name ) {
		$field_name = ltrim( str_replace( array( '[]', '[', ']' ), array( '', '-', '' ), $field_name ), '-' );
		return 'widget-' . $this->id_base . '--' . $field_name;
	}

	/**
	 * Fallback for WP_Widget::get_field_name().
	 *
	 * The shortcode inserter JS (sd_build_shortcode) expects single-bracket field
	 * names: widget-{id_base}[{field_name}]. WP_Widget::get_field_name() returns
	 * two levels (widget-{id}[{number}][{name}]) which the shortcode builder cannot
	 * parse. This override returns the single-bracket format the JS expects.
	 *
	 * @param string $field_name The field (argument) name.
	 * @return string
	 */
	public function get_field_name( $field_name ) {
		return 'widget-' . $this->id_base . '[' . $field_name . ']';
	}
}
