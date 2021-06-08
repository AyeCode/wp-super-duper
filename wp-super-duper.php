<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Super_Duper' ) ) {

	require_once( 'type/shortcode.php' );
	require_once( 'type/block.php' );

	/**
	 * A Class to be able to create a Widget, Shortcode or Block to be able to output content for WordPress.
	 *
	 * Should not be called direct but extended instead.
	 *
	 * Class WP_Super_Duper
	 * @since 1.0.16 change log moved to file change-log.txt - CHANGED
	 * @ver 1.0.19
	 */
	class WP_Super_Duper extends WP_Widget {
		public $version = "1.0.24";
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

			// init parent
			parent::__construct( $options['base_id'], $options['name'], $options['widget_ops'] );

			if ( isset( $options['class_name'] ) ) {
				// register widget
				$this->class_name = $options['class_name'];

				// register shortcode
				New WP_Super_Duper_Shortcode( $this );

				// register block
				$this->block = New WP_Super_Duper_Block( $this );
			}

			// add the CSS and JS we need ONCE
			global $sd_widget_scripts;

			if ( ! $sd_widget_scripts ) {
				wp_add_inline_script( 'admin-widgets', $this->widget_js() );
				wp_add_inline_script( 'customize-controls', $this->widget_js() );
				wp_add_inline_style( 'widgets', $this->widget_css() );

				// maybe add elementor editor styles
				add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'elementor_editor_styles' ) );

				$sd_widget_scripts = true;

				add_action( 'wp_ajax_super_duper_get_widget_settings', array( __CLASS__, 'get_widget_settings' ) );

				// add generator text to admin head
				add_action( 'admin_head', array( $this, 'generator' ) );
			}

			do_action( 'wp_super_duper_widget_init', $options, $this );
		}

		/**
		 * Add our widget CSS to elementor editor.
		 */
		public function elementor_editor_styles() {
			wp_add_inline_style( 'elementor-editor', $this->widget_css( false ) );
		}

		/**
		 * Output the version in the admin header.
		 */
		public function generator() {
			echo '<meta name="generator" content="WP Super Duper v' . $this->version . '" />';
		}

		/**
		 * Get widget settings.
		 *
		 * @since 1.0.0
		 */
		public static function get_widget_settings() {
			global $sd_widgets;

			$shortcode = isset( $_REQUEST['shortcode'] ) && $_REQUEST['shortcode'] ? sanitize_title_with_dashes( $_REQUEST['shortcode'] ) : '';
			if ( ! $shortcode ) {
				wp_die();
			}
			$widget_args = isset( $sd_widgets[ $shortcode ] ) ? $sd_widgets[ $shortcode ] : '';
			if ( ! $widget_args ) {
				wp_die();
			}
			$class_name = isset( $widget_args['class_name'] ) && $widget_args['class_name'] ? $widget_args['class_name'] : '';
			if ( ! $class_name ) {
				wp_die();
			}

			// invoke an instance method
			$widget = new $class_name;

			ob_start();
			$widget->form( array() );
			$form = ob_get_clean();
			echo "<form id='$shortcode'>" . $form . "<div class=\"widget-control-save\"></div></form>";
			echo "<style>" . $widget->widget_css() . "</style>";
			echo "<script>" . $widget->widget_js() . "</script>";
			?>
			<?php
			wp_die();
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
				 * Check a form to see what items shoudl be shown or hidden.
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
					jQuery(form).change(function () {
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
		 * Gets some CSS for the widgets screen.
		 *
		 * @param bool $advanced If we should include advanced CSS.
		 *
		 * @return mixed
		 */
		public function widget_css( $advanced = true ) {
			ob_start();
			?>
			<style>
				<?php if( $advanced ){ ?>
				.sd-advanced-setting {
					display: none;
				}

				.sd-advanced-setting.sd-adv-show {
					display: block;
				}

				.sd-argument.sd-require-hide,
				.sd-advanced-setting.sd-require-hide {
					display: none;
				}

				button.sd-advanced-button {
					margin-right: 3px !important;
					font-size: 20px !important;
				}

				<?php } ?>

				button.sd-toggle-group-button {
					background-color: #f3f3f3;
					color: #23282d;
					cursor: pointer;
					padding: 10px;
					width: 100%;
					border: none;
					text-align: left;
					outline: none;
					font-size: 13px;
					font-weight: bold;
					margin-bottom: 1px;
				}
			</style>
			<?php
			$output = ob_get_clean();

			/*
			 * We only add the <script> tags for code highlighting, so we strip them from the output.
			 */

			return str_replace( array(
				'<style>',
				'</style>'
			), '', $output );
		}

		/**
		 * Gets some JS for the widgets screen.
		 *
		 * @return mixed
		 */
		public function widget_js() {
			ob_start();
			?>
			<script>

				/**
				 * Toggle advanced settings visibility.
				 */
				function sd_toggle_advanced($this) {
					var form = jQuery($this).parents('form,.form');
					form.find('.sd-advanced-setting').toggleClass('sd-adv-show');
					return false;// prevent form submit
				}

				/**
				 * Check a form to see what items shoudl be shown or hidden.
				 */
				function sd_show_hide(form) {
					console.log('show/hide');
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
				 * Initialise widgets from the widgets screen.
				 */
				function sd_init_widgets($selector) {
					jQuery(".sd-show-advanced").each(function (index) {
						sd_init_widget(this, $selector);
					});
				}

				/**
				 * Initialise a individual widget.
				 */
				function sd_init_widget($this, $selector) {
					console.log($selector);

					if (!$selector) {
						$selector = 'form';
					}
					// only run once.
					if (jQuery($this).data('sd-widget-enabled')) {
						return;
					} else {
						jQuery($this).data('sd-widget-enabled', true);
					}

					var $button = '<button title="<?php _e( 'Advanced Settings' );?>" style="line-height: 28px;" class="button button-primary right sd-advanced-button" onclick="sd_toggle_advanced(this);return false;"><span class="dashicons dashicons-admin-settings" style="width: 28px;font-size: 28px;"></span></button>';
					var form = jQuery($this).parents('' + $selector + '');

					if (jQuery($this).val() == '1' && jQuery(form).find('.sd-advanced-button').length == 0) {
						console.log('add advanced button');
						if(jQuery(form).find('.widget-control-save').length > 0){
							jQuery(form).find('.widget-control-save').after($button);
						}else{
							jQuery(form).find('.sd-show-advanced').after($button);
						}
					} else {
						console.log('no advanced button');
						console.log(jQuery($this).val());
						console.log(jQuery(form).find('.sd-advanced-button').length);

					}

					// show hide on form change
					jQuery(form).change(function () {
						sd_show_hide(form);
					});

					// show hide on load
					sd_show_hide(form);
				}

				/**
				 * Init a customizer widget.
				 */
				function sd_init_customizer_widget(section) {
					if (section.expanded) {
						section.expanded.bind(function (isExpanding) {
							if (isExpanding) {
								// is it a SD widget?
								if (jQuery(section.container).find('.sd-show-advanced').length) {
									// init the widget
									sd_init_widget(jQuery(section.container).find('.sd-show-advanced'), ".form");
								}
							}
						});
					}
				}

				/**
				 * If on widgets screen.
				 */
				jQuery(function () {
					// if not in customizer.
					if (!wp.customize) {
						sd_init_widgets("form");
					}

					// init on widget added
					jQuery(document).on('widget-added', function (e, widget) {
						console.log('widget added');
						// is it a SD widget?
						if (jQuery(widget).find('.sd-show-advanced').length) {
							// init the widget
							sd_init_widget(jQuery(widget).find('.sd-show-advanced'), "form");
						}
					});

					// inint on widget updated
					jQuery(document).on('widget-updated', function (e, widget) {
						console.log('widget updated');

						// is it a SD widget?
						if (jQuery(widget).find('.sd-show-advanced').length) {
							// init the widget
							sd_init_widget(jQuery(widget).find('.sd-show-advanced'), "form");
						}
					});

				});


				/**
				 * We need to run this before jQuery is ready
				 */
				if (wp.customize) {
					wp.customize.bind('ready', function () {

						// init widgets on load
						wp.customize.control.each(function (section) {
							sd_init_customizer_widget(section);
						});

						// init widgets on add
						wp.customize.control.bind('add', function (section) {
							sd_init_customizer_widget(section);
						});

					});

				}
				<?php do_action( 'wp_super_duper_widget_js', $this ); ?>
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
		 * This is the main output class for all 3 items, widget, shortcode and block, it is extended in the calling class.
		 *
		 * @param array $args
		 * @param array $widget_args
		 * @param string $content
		 */
		public function output( $args = array(), $widget_args = array(), $content = '' ) {

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
		 * Outputs the content of the widget
		 *
		 * @param array $args
		 * @param array $instance
		 */
		public function widget( $args, $instance ) {

			// get the filtered values
			$argument_values = $this->argument_values( $instance );
			$argument_values = $this->string_to_bool( $argument_values );
			$output          = $this->output( $argument_values, $args );

			$no_wrap = false;
			if ( isset( $argument_values['no_wrap'] ) && $argument_values['no_wrap'] ) {
				$no_wrap = true;
			}

			ob_start();
			if ( $output && ! $no_wrap ) {

				$class_original = $this->options['widget_ops']['classname'];
				$class = $this->options['widget_ops']['classname']." sdel-".$this->get_instance_hash();

				// Before widget
				$before_widget = $args['before_widget'];
				$before_widget = str_replace($class_original,$class,$before_widget);
				$before_widget = apply_filters( 'wp_super_duper_before_widget', $before_widget, $args, $instance, $this );
				$before_widget = apply_filters( 'wp_super_duper_before_widget_' . $this->base_id, $before_widget, $args, $instance, $this );

				// After widget
				$after_widget = $args['after_widget'];
				$after_widget = apply_filters( 'wp_super_duper_after_widget', $after_widget, $args, $instance, $this );
				$after_widget = apply_filters( 'wp_super_duper_after_widget_' . $this->base_id, $after_widget, $args, $instance, $this );

				echo $before_widget;
				// elementor strips the widget wrapping div so we check for and add it back if needed
				if ( $this->is_elementor_widget_output() ) {
					echo ! empty( $this->options['widget_ops']['classname'] ) ? "<span class='" . esc_attr( $class  ) . "'>" : '';
				}
				echo $this->output_title( $args, $instance );
				echo $output;
				if ( $this->is_elementor_widget_output() ) {
					echo ! empty( $this->options['widget_ops']['classname'] ) ? "</span>" : '';
				}
				echo $after_widget;
			} elseif ( $this->is_preview() && $output == '' ) {// if preview show a placeholder if empty
				$output = $this->preview_placeholder_text( "{{" . $this->base_id . "}}" );
				echo $output;
			} elseif ( $output && $no_wrap ) {
				echo $output;
			}
			$output = ob_get_clean();

			$output = apply_filters( 'wp_super_duper_widget_output', $output, $instance, $args, $this );

			echo $output;
		}

		/**
		 * Tests if the current output is inside a elementor container.
		 *
		 * @since 1.0.4
		 * @return bool
		 */
		public function is_elementor_widget_output() {
			$result = false;
			if ( defined( 'ELEMENTOR_VERSION' ) && isset( $this->number ) && $this->number == 'REPLACE_TO_ID' ) {
				$result = true;
			}

			return $result;
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
		 * Output the super title.
		 *
		 * @param $args
		 * @param array $instance
		 *
		 * @return string
		 */
		public function output_title( $args, $instance = array() ) {
			$output = '';
			if ( ! empty( $instance['title'] ) ) {
				/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
				$title  = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

				if(empty($instance['widget_title_tag'])){
					$output = $args['before_title'] . $title . $args['after_title'];
				}else{
					$title_tag = esc_attr( $instance['widget_title_tag'] );

					// classes
					$title_classes = array();
					$title_classes[] = !empty( $instance['widget_title_size_class'] ) ? sanitize_html_class( $instance['widget_title_size_class'] ) : '';
					$title_classes[] = !empty( $instance['widget_title_align_class'] ) ? sanitize_html_class( $instance['widget_title_align_class'] ) : '';
					$title_classes[] = !empty( $instance['widget_title_color_class'] ) ? "text-".sanitize_html_class( $instance['widget_title_color_class'] ) : '';
					$title_classes[] = !empty( $instance['widget_title_border_class'] ) ? sanitize_html_class( $instance['widget_title_border_class'] ) : '';
					$title_classes[] = !empty( $instance['widget_title_border_color_class'] ) ? "border-".sanitize_html_class( $instance['widget_title_border_color_class'] ) : '';
					$title_classes[] = !empty( $instance['widget_title_mt_class'] ) ? "mt-".absint( $instance['widget_title_mt_class'] ) : '';
					$title_classes[] = !empty( $instance['widget_title_mr_class'] ) ? "mr-".absint( $instance['widget_title_mr_class'] ) : '';
					$title_classes[] = !empty( $instance['widget_title_mb_class'] ) ? "mb-".absint( $instance['widget_title_mb_class'] ) : '';
					$title_classes[] = !empty( $instance['widget_title_ml_class'] ) ? "ml-".absint( $instance['widget_title_ml_class'] ) : '';
					$title_classes[] = !empty( $instance['widget_title_pt_class'] ) ? "pt-".absint( $instance['widget_title_pt_class'] ) : '';
					$title_classes[] = !empty( $instance['widget_title_pr_class'] ) ? "pr-".absint( $instance['widget_title_pr_class'] ) : '';
					$title_classes[] = !empty( $instance['widget_title_pb_class'] ) ? "pb-".absint( $instance['widget_title_pb_class'] ) : '';
					$title_classes[] = !empty( $instance['widget_title_pl_class'] ) ? "pl-".absint( $instance['widget_title_pl_class'] ) : '';

					$class = !empty( $title_classes ) ? implode(" ",$title_classes) : '';
					$output = "<$title_tag class='$class' >$title</$title_tag>";
				}

			}

			return $output;
		}

		/**
		 * Outputs the options form inputs for the widget.
		 *
		 * @param array $instance The widget options.
		 */
		public function form( $instance ) {

			// set widget instance
			$this->instance = $instance;

			// set it as a SD widget
			echo $this->widget_advanced_toggle();

			echo "<p>" . esc_attr( $this->options['widget_ops']['description'] ) . "</p>";
			$arguments_raw = $this->get_arguments();

			if ( is_array( $arguments_raw ) ) {

				$arguments = $this->group_arguments( $arguments_raw );

				// Do we have sections?
				$has_sections = $arguments == $arguments_raw ? false : true;


				if ( $has_sections ) {
					$panel_count = 0;
					foreach ( $arguments as $key => $args ) {

						?>
						<script>
							//							jQuery(this).find("i").toggleClass("fas fa-chevron-up fas fa-chevron-down");jQuery(this).next().toggle();
						</script>
						<?php

						$hide       = $panel_count ? ' style="display:none;" ' : '';
						$icon_class = $panel_count ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
						echo "<button onclick='jQuery(this).find(\"i\").toggleClass(\"fas fa-chevron-up fas fa-chevron-down\");jQuery(this).next().slideToggle();' type='button' class='sd-toggle-group-button sd-input-group-toggle" . sanitize_title_with_dashes( $key ) . "'>" . esc_attr( $key ) . " <i style='float:right;' class='" . $icon_class . "'></i></button>";
						echo "<div class='sd-toggle-group sd-input-group-" . sanitize_title_with_dashes( $key ) . "' $hide>";

						foreach ( $args as $k => $a ) {

							$this->widget_inputs_row_start($k, $a);
							$this->widget_inputs( $a, $instance );
							$this->widget_inputs_row_end($k, $a);

						}

						echo "</div>";

						$panel_count ++;

					}
				} else {
					foreach ( $arguments as $key => $args ) {
						$this->widget_inputs_row_start($key, $args);
						$this->widget_inputs( $args, $instance );
						$this->widget_inputs_row_end($key, $args);
					}
				}

			}
		}

		public function widget_inputs_row_start($key, $args){
			if(!empty($args['row'])){
				// maybe open
				if(!empty($args['row']['open'])){
					?>
					<div class='bsui sd-argument ' data-argument='<?php echo esc_attr( $args['row']['key'] ); ?>' data-element_require='<?php if ( !empty($args['row']['element_require'])) {
						echo $this->convert_element_require( $args['row']['element_require'] );
					} ?>'>
					<?php if(!empty($args['row']['title'])){ ?>
					<label class="mb-0 "><?php echo esc_attr( $args['row']['title'] ); ?><?php echo $this->widget_field_desc( $args['row'] ); ?></label>
					<?php }?>
					<div class='row <?php if(!empty($args['row']['class'])){ echo esc_attr($args['row']['class']);} ?>'>
					<div class='col pr-2'>
					<?php
				}elseif(!empty($args['row']['close'])){
					echo "<div class='col pl-0'>";
				}else{
					echo "<div class='col pl-0 pr-2'>";
				}
			}
		}

		public function widget_inputs_row_end($key, $args){

			if(!empty($args['row'])){
				// maybe close
				if(!empty($args['row']['close'])){
					echo "</div></div>";
				}

				echo "</div>";
			}
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
		 * Builds the inputs for the widget options.
		 *
		 * @param $args
		 * @param $instance
		 */
		public function widget_inputs( $args, $instance ) {

			$class             = "";
			$element_require   = "";
			$custom_attributes = "";

			// get value
			if ( isset( $instance[ $args['name'] ] ) ) {
				$value = $instance[ $args['name'] ];
			} elseif ( ! isset( $instance[ $args['name'] ] ) && ! empty( $args['default'] ) ) {
				$value = is_array( $args['default'] ) ? array_map( "esc_html", $args['default'] ) : esc_html( $args['default'] );
			} else {
				$value = '';
			}

			// get placeholder
			if ( ! empty( $args['placeholder'] ) ) {
				$placeholder = "placeholder='" . esc_html( $args['placeholder'] ) . "'";
			} else {
				$placeholder = '';
			}

			// get if advanced
			if ( isset( $args['advanced'] ) && $args['advanced'] ) {
				$class .= " sd-advanced-setting ";
			}

			// element_require
			if ( isset( $args['element_require'] ) && $args['element_require'] ) {
				$element_require = $args['element_require'];
			}

			// custom_attributes
			if ( isset( $args['custom_attributes'] ) && $args['custom_attributes'] ) {
				$custom_attributes = $this->array_to_attributes( $args['custom_attributes'], true );
			}


			// before wrapper
			?>
			<p class="sd-argument <?php echo esc_attr( $class ); ?>"
			data-argument='<?php echo esc_attr( $args['name'] ); ?>'
			data-element_require='<?php if ( $element_require ) {
				echo $this->convert_element_require( $element_require );
			} ?>'
			>
			<?php


			switch ( $args['type'] ) {
				//array('text','password','number','email','tel','url','color')
				case "text":
				case "password":
				case "number":
				case "email":
				case "tel":
				case "url":
				case "color":
					?>
					<label
						for="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>"><?php echo $this->widget_field_title( $args );?><?php echo $this->widget_field_desc( $args ); ?></label>
					<input <?php echo $placeholder; ?> class="widefat"
						<?php echo $custom_attributes; ?>
						                               id="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>"
						                               name="<?php echo esc_attr( $this->get_field_name( $args['name'] ) ); ?>"
						                               type="<?php echo esc_attr( $args['type'] ); ?>"
						                               value="<?php echo esc_attr( $value ); ?>">
					<?php

					break;
				case "select":
					$multiple = isset( $args['multiple'] ) && $args['multiple'] ? true : false;
					if ( $multiple ) {
						if ( empty( $value ) ) {
							$value = array();
						}
					}
					?>
					<label
						for="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>"><?php echo $this->widget_field_title( $args ); ?><?php echo $this->widget_field_desc( $args ); ?></label>
					<select <?php echo $placeholder; ?> class="widefat"
						<?php echo $custom_attributes; ?>
						                                id="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>"
						                                name="<?php echo esc_attr( $this->get_field_name( $args['name'] ) );
						                                if ( $multiple ) {
							                                echo "[]";
						                                } ?>"
						<?php if ( $multiple ) {
							echo "multiple";
						} //@todo not implemented yet due to gutenberg not supporting it
						?>
					>
						<?php

						if ( ! empty( $args['options'] ) ) {
							foreach ( $args['options'] as $val => $label ) {
								if ( $multiple ) {
									$selected = in_array( $val, $value ) ? 'selected="selected"' : '';
								} else {
									$selected = selected( $value, $val, false );
								}
								echo "<option value='$val' " . $selected . ">$label</option>";
							}
						}
						?>
					</select>
					<?php
					break;
				case "checkbox":
					?>
					<input <?php echo $placeholder; ?>
						<?php checked( 1, $value, true ) ?>
						<?php echo $custom_attributes; ?>
						class="widefat" id="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>"
						name="<?php echo esc_attr( $this->get_field_name( $args['name'] ) ); ?>" type="checkbox"
						value="1">
					<label
						for="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>"><?php echo $this->widget_field_title( $args );?><?php echo $this->widget_field_desc( $args ); ?></label>
					<?php
					break;
				case "textarea":
					?>
					<label
						for="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>"><?php echo $this->widget_field_title( $args ); ?><?php echo $this->widget_field_desc( $args ); ?></label>
					<textarea <?php echo $placeholder; ?> class="widefat"
						<?php echo $custom_attributes; ?>
						                                  id="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>"
						                                  name="<?php echo esc_attr( $this->get_field_name( $args['name'] ) ); ?>"
					><?php echo esc_attr( $value ); ?></textarea>
					<?php

					break;
				case "hidden":
					?>
					<input id="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>"
					       name="<?php echo esc_attr( $this->get_field_name( $args['name'] ) ); ?>" type="hidden"
					       value="<?php echo esc_attr( $value ); ?>">
					<?php
					break;
				default:
					echo "No input type found!"; // @todo we need to add more input types.
			}

			// after wrapper
			?>
			</p>
			<?php


		}

		public function get_widget_icon($icon = 'box-top', $title = ''){
			if($icon=='box-top'){
				return '<svg title="'.esc_attr($title).'" width="20px" height="20px" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414" role="img" aria-hidden="true" focusable="false"><rect x="2.714" y="5.492" width="1.048" height="9.017" fill="#555D66"></rect><rect x="16.265" y="5.498" width="1.023" height="9.003" fill="#555D66"></rect><rect x="5.518" y="2.186" width="8.964" height="2.482" fill="#272B2F"></rect><rect x="5.487" y="16.261" width="9.026" height="1.037" fill="#555D66"></rect></svg>';
			}elseif($icon=='box-right'){
				return '<svg title="'.esc_attr($title).'" width="20px" height="20px" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414" role="img" aria-hidden="true" focusable="false"><rect x="2.714" y="5.492" width="1.046" height="9.017" fill="#555D66"></rect><rect x="15.244" y="5.498" width="2.518" height="9.003" fill="#272B2F"></rect><rect x="5.518" y="2.719" width="8.964" height="0.954" fill="#555D66"></rect><rect x="5.487" y="16.308" width="9.026" height="0.99" fill="#555D66"></rect></svg>';
			}elseif($icon=='box-bottom'){
				return '<svg title="'.esc_attr($title).'" width="20px" height="20px" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414" role="img" aria-hidden="true" focusable="false"><rect x="2.714" y="5.492" width="1" height="9.017" fill="#555D66"></rect><rect x="16.261" y="5.498" width="1.027" height="9.003" fill="#555D66"></rect><rect x="5.518" y="2.719" width="8.964" height="0.968" fill="#555D66"></rect><rect x="5.487" y="15.28" width="9.026" height="2.499" fill="#272B2F"></rect></svg>';
			}elseif($icon=='box-left'){
				return '<svg title="'.esc_attr($title).'" width="20px" height="20px" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414" role="img" aria-hidden="true" focusable="false"><rect x="2.202" y="5.492" width="2.503" height="9.017" fill="#272B2F"></rect><rect x="16.276" y="5.498" width="1.012" height="9.003" fill="#555D66"></rect><rect x="5.518" y="2.719" width="8.964" height="0.966" fill="#555D66"></rect><rect x="5.487" y="16.303" width="9.026" height="0.995" fill="#555D66"></rect></svg>';
			}
		}

		/**
		 * Get the widget input description html.
		 *
		 * @param $args
		 *
		 * @return string
		 * @todo, need to make its own tooltip script
		 */
		public function widget_field_desc( $args ) {

			$description = '';
			if ( isset( $args['desc'] ) && $args['desc'] ) {
				if ( isset( $args['desc_tip'] ) && $args['desc_tip'] ) {
					$description = $this->desc_tip( $args['desc'] );
				} else {
					$description = '<span class="description">' . wp_kses_post( $args['desc'] ) . '</span>';
				}
			}

			return $description;
		}

		/**
		 * Get the widget input title html.
		 *
		 * @param $args
		 *
		 * @return string
		 */
		public function widget_field_title( $args ) {

			$title = '';
			if ( isset( $args['title'] ) && $args['title'] ) {
				if ( isset( $args['icon'] ) && $args['icon'] ) {
					$title = self::get_widget_icon( $args['icon'], $args['title']  );
				} else {
					$title = esc_attr($args['title']);
				}
			}

			return $title;
		}

		/**
		 * Get the tool tip html.
		 *
		 * @param $tip
		 * @param bool $allow_html
		 *
		 * @return string
		 */
		function desc_tip( $tip, $allow_html = false ) {
			if ( $allow_html ) {
				$tip = $this->sanitize_tooltip( $tip );
			} else {
				$tip = esc_attr( $tip );
			}

			return '<span class="gd-help-tip dashicons dashicons-editor-help" title="' . $tip . '"></span>';
		}

		/**
		 * Sanitize a string destined to be a tooltip.
		 *
		 * @param string $var
		 *
		 * @return string
		 */
		public function sanitize_tooltip( $var ) {
			return htmlspecialchars( wp_kses( html_entity_decode( $var ), array(
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
				'small'  => array(),
				'span'   => array(),
				'ul'     => array(),
				'li'     => array(),
				'ol'     => array(),
				'p'      => array(),
			) ) );
		}

		/**
		 * Processing widget options on save
		 *
		 * @param array $new_instance The new options
		 * @param array $old_instance The previous options
		 *
		 * @return array
		 * @todo we should add some sanitation here.
		 */
		public function update( $new_instance, $old_instance ) {

			//save the widget
			$instance = array_merge( (array) $old_instance, (array) $new_instance );

			// set widget instance
			$this->instance = $instance;

			if ( empty( $this->arguments ) ) {
				$this->get_arguments();
			}

			// check for checkboxes
			if ( ! empty( $this->arguments ) ) {
				foreach ( $this->arguments as $argument ) {
					if ( isset( $argument['type'] ) && $argument['type'] == 'checkbox' && ! isset( $new_instance[ $argument['name'] ] ) ) {
						$instance[ $argument['name'] ] = '0';
					}
				}
			}

			return $instance;
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

		/**
		 * Generate and return inline styles from CSS rules that will match the unique class of the instance.
		 *
		 * @param array $rules
		 *
		 * @since 1.0.20
		 * @return string
		 */
		public function get_instance_style($rules = array()){
			$css = '';

			if(!empty($rules)){
				$rules = array_unique($rules);
				$instance_hash = $this->get_instance_hash();
				$css .= "<style>";
				foreach($rules as $rule){
					$css .= ".sdel-$instance_hash $rule";
				}
				$css .= "</style>";
			}

			return $css;
		}

	}

}
