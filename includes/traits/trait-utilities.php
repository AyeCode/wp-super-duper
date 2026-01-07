<?php
/**
 * WP Super Duper Utilities Trait
 *
 * This trait contains a collection of general-purpose helper and utility methods
 * used by the WP_Super_Duper class.
 *
 * @version 1.2.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait WP_Super_Duper_Utilities {

	/**
	 * Output the version in the header as a meta tag.
	 */
	public function generator() {
		$file = str_replace( array( "/", "\\" ), "/", realpath( __FILE__ ) );
		$plugins_dir = str_replace( array( "/", "\\" ), "/", realpath( WP_PLUGIN_DIR ) );

		// Find source plugin/theme of SD
		$source = array();
		if ( strpos( $file, $plugins_dir ) !== false ) {
			$source = explode( "/", plugin_basename( $file ) );
		} else if ( function_exists( 'get_theme_root' ) ) {
			$themes_dir = str_replace( array( "/", "\\" ), "/", realpath( get_theme_root() ) );

			if ( strpos( $file, $themes_dir ) !== false ) {
				$source = explode( "/", ltrim( str_replace( $themes_dir, "", $file ), "/" ) );
			}
		}

		echo '<meta name="generator" content="WP Super Duper v' . esc_attr( $this->version ) . '"' . ( ! empty( $source[0] ) ? ' data-sd-source="' . esc_attr( $source[0] ) . '"' : '' ) . ' />';
	}

	/**
	 * Set the 'name' key for each argument from its array key.
	 *
	 * @param array $options The options array containing arguments.
	 * @param bool  $arguments_only True if the passed array is just the arguments.
	 * @return array The modified options array.
	 */
	private function add_name_from_key( $options, $arguments_only = false ) {
		if ( ! $arguments_only && ! empty( $options['arguments'] ) ) {
			foreach ( $options['arguments'] as $key => $val ) {
				$options['arguments'][ $key ]['name'] = $key;
				if ( ! empty( $val['dynamic_data'] ) ) {
					$this->dynamic_fields[ $key ] = true;
				}
			}
		} elseif ( $arguments_only && is_array( $options ) && ! empty( $options ) ) {
			foreach ( $options as $key => $val ) {
				$options[ $key ]['name'] = $key;
				if ( ! empty( $val['dynamic_data'] ) ) {
					$this->dynamic_fields[ $key ] = true;
				}
			}
		}
		return $options;
	}

	/**
	 * Replace any dynamic data variables in fields marked as dynamic.
	 *
	 * @param array $args The instance arguments.
	 * @return array The arguments with dynamic data replaced.
	 */
	public function render_dynamic_fields( $args ) {
		if ( ! sd_is_preview() && ! empty( $this->dynamic_fields ) ) {
			foreach ( $this->dynamic_fields as $key => $val ) {
				if ( isset( $args[ $key ] ) ) {
					$args[ $key ] = function_exists('sd_replace_variables') ? sd_replace_variables( $args[ $key ] ) : $args[ $key ];
				}
			}
		}
		return $args;
	}

	/**
	 * Get the arguments for the instance, applying filters.
	 *
	 * @return array The filtered arguments.
	 */
	public function get_arguments() {
		if ( empty( $this->arguments ) ) {
			$this->arguments = $this->set_arguments();
		}

		$this->arguments = apply_filters( 'wp_super_duper_arguments', $this->arguments, $this->options, $this->instance );
		$this->arguments = $this->add_name_from_key( $this->arguments, true );

		if ( ! empty( $this->arguments['title']['value'] ) ) {
			$this->arguments['title']['value'] = wp_kses_post( $this->arguments['title']['value'] );
		}

		return $this->arguments;
	}

	/**
	 * Placeholder for setting arguments in the child class.
	 *
	 * @return array An empty array by default.
	 */
	public function set_arguments() {
		return $this->arguments;
	}

	/**
	 * Get the URL path to the current folder.
	 *
	 * @return string The calculated URL.
	 */
	public function get_url() {
		if ( ! $this->url ) {
			$content_dir = wp_normalize_path( untrailingslashit( WP_CONTENT_DIR ) );
			$content_url = untrailingslashit( content_url() );

			$file_dir = str_replace( "/includes/traits", "", wp_normalize_path( dirname( __FILE__ ) ) );
			$this->url = trailingslashit( str_replace( $content_dir, $content_url, $file_dir ) );
		}
		return $this->url;
	}

	/**
	 * Get an instance hash that will be unique to the type and settings.
	 *
	 * @return string A crc32b hash of the instance settings.
	 */
	public function get_instance_hash() {
		$instance_string = $this->base_id . serialize( $this->instance );
		return hash( 'crc32b', $instance_string );
	}

	/**
	 * Generate and return inline styles from CSS rules that will match the unique class of the instance.
	 *
	 * @param array $rules An array of CSS rules.
	 * @return string The generated <style> block.
	 */
	public function get_instance_style( $rules = array() ) {
		$css = '';
		if ( ! empty( $rules ) ) {
			$rules = array_unique( $rules );
			$instance_hash = $this->get_instance_hash();
			$css .= "<style>";
			foreach ( $rules as $rule ) {
				$css .= ".sdel-$instance_hash $rule";
			}
			$css .= "</style>";
		}
		return $css;
	}

	/**
	 * Convert an array of attributes to a string for use in HTML or JS.
	 *
	 * @param array $custom_attributes The attributes to convert.
	 * @param bool  $html              True to format for HTML, false for JS object.
	 * @return string The formatted attributes string.
	 */
	public function array_to_attributes( $custom_attributes, $html = false ) {
		$attributes = '';
		if ( ! empty( $custom_attributes ) ) {
			foreach ( $custom_attributes as $key => $val ) {
				if ( is_array( $val ) ) {
					$attributes .= $key . ': {' . $this->array_to_attributes( $val, $html ) . '},';
				} else {
					$attributes .= $html ? " $key='$val' " : "'$key': '$val',";
				}
			}
		}
		return $attributes;
	}

	/**
	 * Replace block attributes placeholders with the proper JS naming.
	 *
	 * @param string $string The string with placeholders.
	 * @param bool   $no_wrap True to avoid wrapping in quotes.
	 * @return string The processed string.
	 */
	public function block_props_replace( $string, $no_wrap = false ) {
		if ( $no_wrap ) {
			$string = str_replace( array( "[%", "%]", "%:checked]" ), array( "props.attributes.", "", "" ), $string );
		} else {
			$string = str_replace( array( "![%", "[%", "%]", "%:checked]" ), array( "'+!props.attributes.", "'+props.attributes.", "+'", "+'" ), $string );
		}
		return $string;
	}

	/**
	 * Checks if the current call is an AJAX call to get the block content.
	 *
	 * @return bool True if it's a block content AJAX call.
	 */
	public function is_block_content_call() {
		return wp_doing_ajax() && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'super_duper_output_shortcode';
	}

	/**
	 * Handle media_buttons hook to prevent conflicts.
	 */
	public function wp_media_buttons() {
		global $shortcode_insert_button_once;
		if ( defined( 'US_CORE_DIR' ) && ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'us_ajax_hb_get_ebuilder_html' ) {
			$shortcode_insert_button_once = true;
		}
	}

	/**
	 * Generates block transformation rules for the Gutenberg editor.
	 *
	 * @param string $block_name The name of the block.
	 * @param array  $config     The transformation configuration.
	 * @return string The generated JS for the transforms property.
	 */
	public function generate_block_transforms( $block_name, array $config ) {
		$js = "transforms: {\n";
		foreach ( ['from', 'to'] as $direction ) {
			if ( empty( $config[ $direction ] ) ) {
				continue;
			}
			$entry   = $config[ $direction ];
			$blocks  = $entry['blocks'];
			$arg_map = $entry['args'];
			$destructure = '{ ' . implode( ', ', array_keys( $arg_map ) ) . ' }';

			$js .= "    $direction: [\n";
			$js .= "        {\n";
			$js .= "            type: 'block',\n";
			$js .= "            blocks: [ '" . implode( "', '", $blocks ) . "' ],\n";
			$js .= "            transform: ( $destructure ) => {\n";
			$js .= "                return wp.blocks.createBlock( '{$block_name}', {\n";
			foreach ( $arg_map as $src => $tgt ) {
				$js .= "                    " . ( ( $src === $tgt ) ? $src : "$tgt: $src" ) . ",\n";
			}
			$js .= "                } );\n";
			$js .= "            },\n";
			$js .= "        },\n";
			$js .= "    ],\n";
		}
		$js .= "},\n";
		return $js;
	}
}
