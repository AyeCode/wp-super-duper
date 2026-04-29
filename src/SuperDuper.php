<?php

namespace AyeCode\SuperDuper;

/**
 * WP Super Duper Base Class
 *
 * The base (non-widget) version of the class. Provides all block and
 * shortcode functionality without the overhead of extending WP_Widget.
 *
 * @version 3.0.4-beta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SuperDuper {

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
	 * This is the equivalent of the WP_Widget property.
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
	 * WP_Widget::get_field_id() is used by the WidgetForm trait when rendering
	 * form inputs. Since this class does not extend WP_Widget, we provide a
	 * compatible implementation so the shortcode inserter can render widget
	 * settings forms without a fatal error.
	 *
	 * @param string $field_name The field (argument) name.
	 * @return string
	 */
	public function get_field_id( string $field_name ): string {
		$field_name = ltrim( str_replace( array( '[]', '[', ']' ), array( '', '-', '' ), $field_name ), '-' );
		return 'widget-' . $this->id_base . '--' . $field_name;
	}

	/**
	 * Fallback for WP_Widget::get_field_name().
	 *
	 * The shortcode inserter JS (sd_build_shortcode) extracts the attribute name
	 * via element.name.substring(indexOf('[') + 1, lastIndexOf(']')), which expects
	 * exactly ONE bracket level: widget-{id_base}[{field_name}].
	 * Multiselect appends [] to this, giving widget-{id_base}[{field_name}][], and
	 * the JS then strips the trailing ][ via fieldName.slice(0, -2).
	 *
	 * WP_Widget::get_field_name() returns TWO levels (widget-{id}[{number}][{name}])
	 * which the shortcode builder JS cannot parse correctly. This override returns
	 * the single-bracket format the JS expects.
	 *
	 * @param string $field_name The field (argument) name.
	 * @return string
	 */
	public function get_field_name( string $field_name ): string {
		return 'widget-' . $this->id_base . '[' . $field_name . ']';
	}
}
