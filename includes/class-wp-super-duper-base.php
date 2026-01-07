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
}
