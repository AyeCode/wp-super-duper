<?php

namespace AyeCode\SuperDuper;

/**
 * WP Super Duper Widget Class
 *
 * The widget-enabled version of the class. Loaded conditionally when the
 * 'SUPER_DUPER_LOAD_WIDGET' constant is true. Extends WP_Widget to provide
 * full widget functionality.
 *
 * @version 3.0.4-beta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SuperDuperWidget extends \WP_Widget {

	/**
	 * Import all shared logic from the traits.
	 */
	use Traits\Initializer,
		Traits\GutenbergBlock,
		Traits\PageBuilders,
		Traits\ShortcodeInserter,
		Traits\WidgetForm,
		Traits\OutputHandler,
		Traits\Utilities;

	// --- Class Properties ---

	public $version = SUPER_DUPER_VER;
	public $font_awesome_icon_version = '5.11.2';
	public $block_code;
	public $options;
	public $base_id;

	/**
	 * Explicitly declared to prevent undefined property errors.
	 * Shadows the parent WP_Widget property but ensures consistency.
	 *
	 * @var string
	 */
	public $id_base;

	public $settings_hash;
	public $id;
	public $arguments = array();
	public $instance  = array();
	private $class_name;
	public $dynamic_fields = array();
	public $url            = '';

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
		if ( empty( $this->options['output_types'] ) || in_array( 'widget', $this->options['output_types'] ) ) {
			parent::_register();
		}
	}

	/**
	 * Override WP_Widget::get_field_name() to use single-bracket format.
	 *
	 * WP_Widget::get_field_name() returns widget-{id}[{number}][{name}] (two
	 * levels). The shortcode inserter JS (sd_build_shortcode) extracts the
	 * attribute name via indexOf('[') + lastIndexOf(']'), which only works with
	 * a single bracket level: widget-{id_base}[{field_name}].
	 *
	 * @param string $field_name The field (argument) name.
	 * @return string
	 */
	public function get_field_name( $field_name ): string {
		return 'widget-' . $this->id_base . '[' . $field_name . ']';
	}

	/**
	 * Override WP_Widget::get_field_id() to use a consistent format for the
	 * non-registered (shortcode inserter) context where $this->number is false.
	 *
	 * @param string $field_name The field (argument) name.
	 * @return string
	 */
	public function get_field_id( $field_name ): string {
		$field_name = ltrim( str_replace( array( '[]', '[', ']' ), array( '', '-', '' ), $field_name ), '-' );
		return 'widget-' . $this->id_base . '--' . $field_name;
	}
}
