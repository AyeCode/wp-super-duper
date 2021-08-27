<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Super_Duper' ) ) {

	require_once( 'type/shortcode.php' );
	require_once( 'type/block.php' );
	require_once( 'type/widget.php' );

	/**
	 * A Class to be able to create a Widget, Shortcode or Block to be able to output content for WordPress.
	 *
	 * Should not be called direct but extended instead.
	 *
	 * Class WP_Super_Duper
	 * @since 1.0.16 change log moved to file change-log.txt - CHANGED
	 * @ver 1.0.19
	 */
	class WP_Super_Duper {
		public $version = "1.0.27";
		public $font_awesome_icon_version = "5.11.2";
		public $block_code;
		public $options;
		public $base_id;
		public $settings_hash;
		public $arguments = array();
		public $instance = array();
		private $class_name;

		/**
		 * The relative url to the current folder.
		 *
		 * @var string
		 */
		public $url = '';

		/**
		 * Take the array options and use them to build.
		 */
		public function __construct( $options ) {
			global $sd_widgets;

			$sd_widgets[ $options['base_id'] ] = array(
				'name'       => $options['name'],
				'class_name' => $options['class_name']
			);
			$this->base_id                     = $options['base_id'];
			// lets filter the options before we do anything
			$options       = apply_filters( "wp_super_duper_options", $options );
			$options       = apply_filters( "wp_super_duper_options_{$this->base_id}", $options );
			$options       = $this->add_name_from_key( $options );
			$this->options = $options;

			$this->base_id   = $options['base_id'];
			$this->arguments = isset( $options['arguments'] ) ? $options['arguments'] : array();

			if ( isset( $options['class_name'] ) ) {
				// register widget
				$this->class_name = $options['class_name'];
				$widget = New WP_Super_Duper_Widget();
				$widget->set_arguments( $this );

				// register shortcode
				New WP_Super_Duper_Shortcode( $this );

				// register block
				$this->block = New WP_Super_Duper_Block( $this );
			}

				// add generator text to admin head
				add_action( 'admin_head', array( $this, 'generator' ) );

			do_action( 'wp_super_duper_widget_init', $options, $this );
		}

		/**
		 * Output the version in the admin header.
		 */
		public function generator() {
			echo '<meta name="generator" content="WP Super Duper v' . $this->version . '" />';
		}


		/**
		 * Makes SD work with the siteOrigin page builder.
		 *
		 * @since 1.0.6
		 * @return mixed
		 */
		public static function siteorigin_js() {
			ob_start();
			?>
			<script>
				/**
				 * Check a form to see what items should be shown or hidden.
				 */
				function sd_so_show_hide(form) {
					jQuery(form).find(".sd-argument").each(function () {

						var $element_require = jQuery(this).data('element_require');

						if ($element_require) {

							$element_require = $element_require.replace("&#039;", "'"); // replace single quotes
							$element_require = $element_require.replace("&quot;", '"'); // replace double quotes

							if (eval($element_require)) {
								jQuery(this).removeClass('sd-require-hide');
							} else {
								jQuery(this).addClass('sd-require-hide');
							}
						}
					});
				}

				/**
				 * Toggle advanced settings visibility.
				 */
				function sd_so_toggle_advanced($this) {
					var form = jQuery($this).parents('form,.form,.so-content');
					form.find('.sd-advanced-setting').toggleClass('sd-adv-show');
					return false;// prevent form submit
				}

				/**
				 * Initialise a individual widget.
				 */
				function sd_so_init_widget($this, $selector) {
					if (!$selector) {
						$selector = 'form';
					}
					// only run once.
					if (jQuery($this).data('sd-widget-enabled')) {
						return;
					} else {
						jQuery($this).data('sd-widget-enabled', true);
					}

					var $button = '<button title="<?php _e( 'Advanced Settings' );?>" class="button button-primary right sd-advanced-button" onclick="sd_so_toggle_advanced(this);return false;"><i class="fas fa-sliders-h" aria-hidden="true"></i></button>';
					var form = jQuery($this).parents('' + $selector + '');

					if (jQuery($this).val() == '1' && jQuery(form).find('.sd-advanced-button').length == 0) {
						jQuery(form).append($button);
					}

					// show hide on form change
					jQuery(form).on("change", function () {
						sd_so_show_hide(form);
					});

					// show hide on load
					sd_so_show_hide(form);
				}

				jQuery(function () {
					jQuery(document).on('open_dialog', function (w, e) {
						setTimeout(function () {
							if (jQuery('.so-panels-dialog-wrapper:visible .so-content.panel-dialog .sd-show-advanced').length) {
								console.log('exists');
								if (jQuery('.so-panels-dialog-wrapper:visible .so-content.panel-dialog .sd-show-advanced').val() == '1') {
									console.log('true');
									sd_so_init_widget('.so-panels-dialog-wrapper:visible .so-content.panel-dialog .sd-show-advanced', 'div');
								}
							}
						}, 200);
					});
				});
			</script>
			<?php
			$output = ob_get_clean();

			/*
			 * We only add the <script> tags for code highlighting, so we strip them from the output.
			 */

			return str_replace( array(
				'<script>',
				'</script>'
			), '', $output );
		}


		/**
		 * Set the name from the argument key.
		 *
		 * @param $options
		 *
		 * @return mixed
		 */
		private function add_name_from_key( $options, $arguments = false ) {
			if ( ! empty( $options['arguments'] ) ) {
				foreach ( $options['arguments'] as $key => $val ) {
					$options['arguments'][ $key ]['name'] = $key;
				}
			} elseif ( $arguments && is_array( $options ) && ! empty( $options ) ) {
				foreach ( $options as $key => $val ) {
					$options[ $key ]['name'] = $key;
				}
			}

			return $options;
		}

		/**
		 * Placeholder text to show if output is empty and we are on a preview/builder page.
		 *
		 * @param string $name
		 *
		 * @return string
		 */
		public function preview_placeholder_text( $name = '' ) {
			return "<div style='background:#0185ba33;padding: 10px;border: 4px #ccc dashed;'>" . sprintf( __( 'Placeholder for: %s' ), $name ) . "</div>";
		}

		/**
		 * Sometimes booleans values can be turned to strings, so we fix that.
		 *
		 * @param $options
		 *
		 * @return mixed
		 */
		public function string_to_bool( $options ) {
			// convert bool strings to booleans
			foreach ( $options as $key => $val ) {
				if ( $val == 'false' ) {
					$options[ $key ] = false;
				} elseif ( $val == 'true' ) {
					$options[ $key ] = true;
				}
			}

			return $options;
		}

		/**
		 * Get the argument values that are also filterable.
		 *
		 * @param $instance
		 *
		 * @since 1.0.12 Don't set checkbox default value if the value is empty.
		 *
		 * @return array
		 */
		public function argument_values( $instance ) {
			$argument_values = array();

			// set widget instance
			$this->instance = $instance;

			if ( empty( $this->arguments ) ) {
				$this->arguments = $this->get_arguments();
			}

			if ( ! empty( $this->arguments ) ) {
				foreach ( $this->arguments as $key => $args ) {
					// set the input name from the key
					$args['name'] = $key;
					//
					$argument_values[ $key ] = isset( $instance[ $key ] ) ? $instance[ $key ] : '';
					if ( $args['type'] == 'checkbox' && $argument_values[ $key ] == '' ) {
						// don't set default for an empty checkbox
					} elseif ( $argument_values[ $key ] == '' && isset( $args['default'] ) ) {
						$argument_values[ $key ] = $args['default'];
					}
				}
			}

			return $argument_values;
		}

		/**
		 * Set arguments in super duper.
		 *
		 * @since 1.0.0
		 *
		 * @return array Set arguments.
		 */
		public function set_arguments() {
			return $this->arguments;
		}

		/**
		 * Get arguments in super duper.
		 *
		 * @since 1.0.0
		 *
		 * @return array Get arguments.
		 */
		public function get_arguments() {
			if ( empty( $this->arguments ) ) {
				$this->arguments = $this->set_arguments();
			}

			$this->arguments = apply_filters( 'wp_super_duper_arguments', $this->arguments, $this->options, $this->instance );
			$this->arguments = $this->add_name_from_key( $this->arguments, true );

			return $this->arguments;
		}

		/**
		 * Get the url path to the current folder.
		 *
		 * @return string
		 */
		public function get_url() {
			$url = $this->url;

			if ( ! $url ) {
				// check if we are inside a plugin
				$file_dir = str_replace( "/includes", "", dirname( __FILE__ ) );

				$dir_parts = explode( "/wp-content/", $file_dir );
				$url_parts = explode( "/wp-content/", plugins_url() );

				if ( ! empty( $url_parts[0] ) && ! empty( $dir_parts[1] ) ) {
					$url       = trailingslashit( $url_parts[0] . "/wp-content/" . $dir_parts[1] );
					$this->url = $url;
				}
			}

			return $url;
		}

		public function group_arguments( $arguments ) {
//			echo '###';print_r($arguments);
			if ( ! empty( $arguments ) ) {
				$temp_arguments = array();
				$general        = __( "General" );
				$add_sections   = false;
				foreach ( $arguments as $key => $args ) {
					if ( isset( $args['group'] ) ) {
						$temp_arguments[ $args['group'] ][ $key ] = $args;
						$add_sections                             = true;
					} else {
						$temp_arguments[ $general ][ $key ] = $args;
					}
				}

				// only add sections if more than one
				if ( $add_sections ) {
					$arguments = $temp_arguments;
				}
			}

//			echo '###';print_r($arguments);
			return $arguments;
		}

		/**
		 * Convert an array of attributes to block string.
		 *
		 * @todo there is prob a faster way to do this, also we could add some validation here.
		 *
		 * @param $custom_attributes
		 *
		 * @return string
		 */
		public function array_to_attributes( $custom_attributes, $html = false ) {
			$attributes = '';
			if ( ! empty( $custom_attributes ) ) {

				if ( $html ) {
					foreach ( $custom_attributes as $key => $val ) {
						$attributes .= " $key='$val' ";
					}
				} else {
					foreach ( $custom_attributes as $key => $val ) {
						$attributes .= "'$key': '$val',";
					}
				}
			}

			return $attributes;
		}


		/**
		 * Tests if the current output is inside a elementor preview.
		 *
		 * @since 1.0.4
		 * @return bool
		 */
		public function is_elementor_preview() {
			$result = false;
			if ( isset( $_REQUEST['elementor-preview'] ) || ( is_admin() && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'elementor' ) || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'elementor_ajax' ) ) {
				$result = true;
			}

			return $result;
		}

		/**
		 * Tests if the current output is inside a Divi preview.
		 *
		 * @since 1.0.6
		 * @return bool
		 */
		public function is_divi_preview() {
			$result = false;
			if ( isset( $_REQUEST['et_fb'] ) || isset( $_REQUEST['et_pb_preview'] ) || ( is_admin() && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'elementor' ) ) {
				$result = true;
			}

			return $result;
		}

		/**
		 * Tests if the current output is inside a Beaver builder preview.
		 *
		 * @since 1.0.6
		 * @return bool
		 */
		public function is_beaver_preview() {
			$result = false;
			if ( isset( $_REQUEST['fl_builder'] ) ) {
				$result = true;
			}

			return $result;
		}

		/**
		 * Tests if the current output is inside a siteorigin builder preview.
		 *
		 * @since 1.0.6
		 * @return bool
		 */
		public function is_siteorigin_preview() {
			$result = false;
			if ( ! empty( $_REQUEST['siteorigin_panels_live_editor'] ) ) {
				$result = true;
			}

			return $result;
		}

		/**
		 * Tests if the current output is inside a cornerstone builder preview.
		 *
		 * @since 1.0.8
		 * @return bool
		 */
		public function is_cornerstone_preview() {
			$result = false;
			if ( ! empty( $_REQUEST['cornerstone_preview'] ) || basename( $_SERVER['REQUEST_URI'] ) == 'cornerstone-endpoint' ) {
				$result = true;
			}

			return $result;
		}

		/**
		 * Tests if the current output is inside a fusion builder preview.
		 *
		 * @since 1.1.0
		 * @return bool
		 */
		public function is_fusion_preview() {
			$result = false;
			if ( ! empty( $_REQUEST['fb-edit'] ) || ! empty( $_REQUEST['fusion_load_nonce'] ) ) {
				$result = true;
			}

			return $result;
		}

		/**
		 * Tests if the current output is inside a Oxygen builder preview.
		 *
		 * @since 1.0.18
		 * @return bool
		 */
		public function is_oxygen_preview() {
			$result = false;
			if ( ! empty( $_REQUEST['ct_builder'] ) || ( ! empty( $_REQUEST['action'] ) && ( substr( $_REQUEST['action'], 0, 11 ) === "oxy_render_" || substr( $_REQUEST['action'], 0, 10 ) === "ct_render_" ) ) ) {
				$result = true;
			}

			return $result;
		}

		/**
		 * General function to check if we are in a preview situation.
		 *
		 * @since 1.0.6
		 * @return bool
		 */
		public function is_preview() {
			$preview = false;
			if ( $this->is_divi_preview() ) {
				$preview = true;
			} elseif ( $this->is_elementor_preview() ) {
				$preview = true;
			} elseif ( $this->is_beaver_preview() ) {
				$preview = true;
			} elseif ( $this->is_siteorigin_preview() ) {
				$preview = true;
			} elseif ( $this->is_cornerstone_preview() ) {
				$preview = true;
			} elseif ( $this->is_fusion_preview() ) {
				$preview = true;
			} elseif ( $this->is_oxygen_preview() ) {
				$preview = true;
			} elseif( $this->is_block_content_call() ) {
				$preview = true;
			}

			return $preview;
		}

		/**
		* Checks if the current call is a ajax call to get the block content.
		*
		* This can be used in your widget to return different content as the block content.
		*
		* @since 1.0.3
		* @return bool
		*/
		public function is_block_content_call() {
			$result = false;
			if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'super_duper_output_shortcode' ) {
				$result = true;
			}

			return $result;
		}

		/**
		 * Get the hidden input that when added makes the advanced button show on widget settings.
		 *
		 * @return string
		 */
		public function widget_advanced_toggle() {
			$output = '';
			if ( $this->block->block_show_advanced() ) {
				$val = 1;
			} else {
				$val = 0;
			}

			$output .= "<input type='hidden'  class='sd-show-advanced' value='$val' />";

			return $output;
		}

		/**
		 * Convert require element.
		 *
		 * @since 1.0.0
		 *
		 * @param string $input Input element.
		 *
		 * @return string $output
		 */
		public function convert_element_require( $input ) {

			$input = str_replace( "'", '"', $input );// we only want double quotes

			$output = esc_attr( str_replace( array( "[%", "%]" ), array(
				"jQuery(form).find('[data-argument=\"",
				"\"]').find('input,select,textarea').val()"
			), $input ) );

			return $output;
		}

		/**
		 * Get an instance hash that will be unique to the type and settings.
		 *
		 * @since 1.0.20
		 * @return string
		 */
		public function get_instance_hash(){
			$instance_string = $this->base_id.serialize($this->instance);
			return hash('crc32b',$instance_string);
		}
	}

}
