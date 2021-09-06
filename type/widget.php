<?php

class WP_Super_Duper_Widget extends WP_Widget {

	public function __construct() {
	}

	public function set_arguments( $class ) {
		$this->SD = $class;
		$this->options = $class->options;
        do_action( 'wp_super_duper_widget_init', $this->options, $this );
		// add the CSS and JS we need ONCE
		global $sd_widget_scripts;
		if ( ! $sd_widget_scripts ) {
			add_action( 'admin_enqueue_scripts' , function () {
				wp_add_inline_script( 'admin-widgets', $this->widget_js() );
				wp_add_inline_script( 'customize-controls', $this->widget_js() );
				wp_add_inline_style( 'widgets', $this->widget_css() );
			});

			// maybe add elementor editor styles
			add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'elementor_editor_styles' ) );
			$sd_widget_scripts = true;
			add_action( 'wp_ajax_super_duper_get_widget_settings', array( __CLASS__, 'get_widget_settings' ) );
		}
		add_action('widgets_init', function() {
			global $wp_widget_factory;
			parent::__construct( $this->options['base_id'], $this->options['name'], $this->options['widget_ops'] );

			$wp_widget_factory->widgets[ $this->options['class_name'] ] = $this;
		});
	}

	/**
	 * Add our widget CSS to elementor editor.
	 */
	public function elementor_editor_styles() {
		wp_add_inline_style( 'elementor-editor', $this->widget_css( false ) );
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
				 * Check a form to see what items should be shown or hidden.
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
					jQuery(form).on("change", function () {
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

					// init on widget updated
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
		 * Outputs the content of the widget
		 *
		 * @param array $args
		 * @param array $instance
		 */
		public function widget( $args, $instance ) {
			// get the filtered values
			$argument_values = $this->SD->argument_values( $instance );
			$argument_values = $this->SD->string_to_bool( $argument_values );
			$output          = $this->output( $argument_values, $args );

			$no_wrap = false;
			if ( isset( $argument_values['no_wrap'] ) && $argument_values['no_wrap'] ) {
				$no_wrap = true;
			}

			ob_start();
			if ( $output && ! $no_wrap ) {

				$class_original = $this->options['widget_ops']['classname'];
				$class = $this->options['widget_ops']['classname']." sdel-".$this->SD->get_instance_hash();

				// Before widget
				$before_widget = $args['before_widget'];
				$before_widget = str_replace($class_original,$class,$before_widget);
				$before_widget = apply_filters( 'wp_super_duper_before_widget', $before_widget, $args, $instance, $this );
				$before_widget = apply_filters( 'wp_super_duper_before_widget_' . $this->SD->base_id, $before_widget, $args, $instance, $this );

				// After widget
				$after_widget = $args['after_widget'];
				$after_widget = apply_filters( 'wp_super_duper_after_widget', $after_widget, $args, $instance, $this );
				$after_widget = apply_filters( 'wp_super_duper_after_widget_' . $this->SD->base_id, $after_widget, $args, $instance, $this );

				echo $before_widget;
				// elementor strips the widget wrapping div so we check for and add it back if needed
				if ( $this->is_elementor_widget_output() ) {
					// Filter class & attrs for elementor widget output.
					$class = apply_filters( 'wp_super_duper_div_classname', $class, $args, $this );
					$class = apply_filters( 'wp_super_duper_div_classname_' . $this->base_id, $class, $args, $this );

					$attrs = apply_filters( 'wp_super_duper_div_attrs', '', $args, $this );
					$attrs = apply_filters( 'wp_super_duper_div_attrs_' . $this->base_id, '', $args, $this );

					echo "<span class='" . esc_attr( $class  ) . "' " . $attrs . ">";
				}
				echo $this->output_title( $args, $instance );
				echo $output;
				if ( $this->is_elementor_widget_output() ) {
					echo "</span>";
				}
				echo $after_widget;
			} elseif ( $this->SD->is_preview() && $output == '' ) {// if preview show a placeholder if empty
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
			echo $this->SD->widget_advanced_toggle();

			echo "<p>" . esc_attr( $this->options['widget_ops']['description'] ) . "</p>";
			$arguments_raw = $this->SD->get_arguments();

			if ( is_array( $arguments_raw ) ) {

				$arguments = $this->SD->group_arguments( $arguments_raw );

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
						echo $this->SD->convert_element_require( $args['row']['element_require'] );
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
				$custom_attributes = $this->SD->array_to_attributes( $args['custom_attributes'], true );
			}


			// before wrapper
			?>
			<p class="sd-argument <?php echo esc_attr( $class ); ?>"
			data-argument='<?php echo esc_attr( $args['name'] ); ?>'
			data-element_require='<?php if ( $element_require ) {
				echo $this->SD->convert_element_require( $element_require );
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
				$instance_hash = $this->SD->get_instance_hash();
				$css .= "<style>";
				foreach($rules as $rule){
					$css .= ".sdel-$instance_hash $rule";
				}
				$css .= "</style>";
			}

			return $css;
		}

		/**
		 * Get the conditional fields JavaScript.
		 *
		 * @return mixed
		 */
		public function conditional_fields_js() {
			ob_start();
			?>
            <script>
            /**
            * Conditional Fields
            */
            var sd_cf_field_rules = [], sd_cf_field_key_rules = {}, sd_cf_field_default_values = {};

            jQuery(function($) {
                /* Init conditional fields */
                sd_cf_field_init_rules($);
            });

            /**
            * Conditional fields init.
            */
            function sd_cf_field_init_rules($) {
                if (!$('[data-has-rule]').length) {
                    return;
                }

                $('[data-rule-key]').on('change keypress keyup', 'input, textarea', function() {
                    sd_cf_field_apply_rules($(this));
                });

                $('[data-rule-key]').on('change', 'select', function() {
                    sd_cf_field_apply_rules($(this));
                });

                $('[data-rule-key]').on('change.select2', 'select', function() {
                    sd_cf_field_apply_rules($(this));
                });

                /*jQuery(document).on('sd_cf_field_on_change', function() {
                    sd_cf_field_hide_child_elements();
                });*/

                sd_cf_field_setup_rules($);
            }

            /**
            * Setup conditional field rules.
            */
            function sd_cf_field_setup_rules($) {
                var sd_cf_field_keys = [];

                $('[data-rule-key]').each(function() {
                    var key = jQuery(this).data('rule-key'),
                        irule = parseInt(jQuery(this).data('has-rule'));
                    if (key) {
                        sd_cf_field_keys.push(key);
                    }

                    var parse_conds = {};
                    if ($(this).data('rule-fie-0')) {
                        for (var i = 0; i < irule; i++) {
                            var field = $(this).data('rule-fie-' + i);
                            if (typeof parse_conds[i] === 'undefined') {
                                parse_conds[i] = {};
                            }
                            parse_conds[i]['action'] = $(this).data('rule-act-' + i);
                            parse_conds[i]['field'] = $(this).data('rule-fie-' + i);
                            parse_conds[i]['condition'] = $(this).data('rule-con-' + i);
                            parse_conds[i]['value'] = $(this).data('rule-val-' + i);
                        }

                        jQuery.each(parse_conds, function(j, data) {
                            var item = {
                                'field': {
                                    key: key,
                                    action: data.action,
                                    field: data.field,
                                    condition: data.condition,
                                    value: data.value,
                                    rule: {
                                        key: key,
                                        action: data.action,
                                        condition: data.condition,
                                        value: data.value
                                    }
                                }
                            };
                            sd_cf_field_rules.push(item);
                        });
                    }
                    sd_cf_field_default_values[jQuery(this).data('rule-key')] = sd_cf_field_get_default_value(jQuery(this));
                });

                jQuery.each(sd_cf_field_keys, function(i, fkey) {
                    sd_cf_field_key_rules[fkey] = sd_cf_field_get_children(fkey);
                });

                jQuery('[data-rule-key]:visible').each(function() {
                    var conds = sd_cf_field_key_rules[jQuery(this).data('rule-key')];
                    if (conds && conds.length) {
                        var $main_el = jQuery(this), el = sd_cf_field_get_element($main_el);
                        if (jQuery(el).length) {
                            sd_cf_field_apply_rules(jQuery(el));
                        }
                    }
                });
            }

            /**
            * Apply conditional field rules.
            */
            function sd_cf_field_apply_rules($el) {
                if (!$el.parents('[data-rule-key]').length) {
                    return;
                }

                if ($el.data('no-rule')) {
                    return;
                }

                var key = $el.parents('[data-rule-key]').data('rule-key');
                var conditions = sd_cf_field_key_rules[key];
                if (typeof conditions === 'undefined') {
                    return;
                }
                var field_type = sd_cf_field_get_type($el.parents('[data-rule-key]')),
                    current_value = sd_cf_field_get_value($el);

                var $keys = {},
                    $keys_values = {},
                    $key_rules = {};

                jQuery.each(conditions, function(index, condition) {
                    if (typeof $keys_values[condition.key] == 'undefined') {
                        $keys_values[condition.key] = [];
                        $key_rules[condition.key] = {}
                    }

                    $keys_values[condition.key].push(condition.value);
                    $key_rules[condition.key] = condition;
                });

                jQuery.each(conditions, function(index, condition) {
                    if (typeof $keys[condition.key] == 'undefined') {
                        $keys[condition.key] = {};
                    }

                    if (condition.condition === 'empty') {
                        var field_value = Array.isArray(current_value) ? current_value.join('') : current_value;
                        if (!field_value || field_value === '') {
                            $keys[condition.key][index] = true;
                        } else {
                            $keys[condition.key][index] = false;
                        }
                    } else if (condition.condition === 'not empty') {
                        var field_value = Array.isArray(current_value) ? current_value.join('') : current_value;
                        if (field_value && field_value !== '') {
                            $keys[condition.key][index] = true;
                        } else {
                            $keys[condition.key][index] = false;
                        }
                    } else if (condition.condition === 'equals to') {
                        var field_value = (Array.isArray(current_value) && current_value.length === 1) ? current_value[0] : current_value;
                        if (((condition.value && condition.value == condition.value) || (condition.value === field_value)) && sd_cf_field_in_array(field_value, $keys_values[condition.key])) {
                            $keys[condition.key][index] = true;
                        } else {
                            $keys[condition.key][index] = false;
                        }
                    } else if (condition.condition === 'not equals') {
                        var field_value = (Array.isArray(current_value) && current_value.length === 1) ? current_value[0] : current_value;
                        if (jQuery.isNumeric(condition.value) && parseInt(field_value) !== parseInt(condition.value) && field_value && !sd_cf_field_in_array(field_value, $keys_values[condition.key])) {
                            $keys[condition.key][index] = true;
                        } else if (condition.value != field_value && !sd_cf_field_in_array(field_value, $keys_values[condition.key])) {
                            $keys[condition.key][index] = true;
                        } else {
                            $keys[condition.key][index] = false;
                        }
                    } else if (condition.condition === 'greater than') {
                        var field_value = (Array.isArray(current_value) && current_value.length === 1) ? current_value[0] : current_value;
                        if (jQuery.isNumeric(condition.value) && parseInt(field_value) > parseInt(condition.value)) {
                            $keys[condition.key][index] = true;
                        } else {
                            $keys[condition.key][index] = false;
                        }
                    } else if (condition.condition === 'less than') {
                        var field_value = (Array.isArray(current_value) && current_value.length === 1) ? current_value[0] : current_value;
                        if (jQuery.isNumeric(condition.value) && parseInt(field_value) < parseInt(condition.value)) {
                            $keys[condition.key][index] = true;
                        } else {
                            $keys[condition.key][index] = false;
                        }
                    } else if (condition.condition === 'contains') {
                        switch (field_type) {
                            case 'multiselect':
                                if (current_value && ((!Array.isArray(current_value) && current_value.indexOf(condition.value) >= 0) || (Array.isArray(current_value) && sd_cf_field_in_array(condition.value, current_value)))) { //
                                    $keys[condition.key][index] = true;
                                } else {
                                    $keys[condition.key][index] = false;
                                }
                                break;
                            case 'checkbox':
                                if (current_value && ((!Array.isArray(current_value) && current_value.indexOf(condition.value) >= 0) || (Array.isArray(current_value) && sd_cf_field_in_array(condition.value, current_value)))) { //
                                    $keys[condition.key][index] = true;
                                } else {
                                    $keys[condition.key][index] = false;
                                }
                                break;
                            default:
                                if (typeof $keys[condition.key][index] === 'undefined') {
                                    if (current_value && current_value.indexOf(condition.value) >= 0 && sd_cf_field_in_array(current_value, $keys_values[condition.key])) {
                                        $keys[condition.key][index] = true;
                                    } else {
                                        $keys[condition.key][index] = false;
                                    }
                                }
                                break;
                        }
                    }
                });

                jQuery.each($keys, function(index, field) {
                    if (sd_cf_field_in_array(true, field)) {
                        sd_cf_field_apply_action($el, $key_rules[index], true);
                    } else {
                        sd_cf_field_apply_action($el, $key_rules[index], false);
                    }
                });

                /* Trigger field change */
                if ($keys.length) {
                    $el.trigger('sd_cf_field_on_change');
                }
            }

            /**
            * Get the field element.
            */
            function sd_cf_field_get_element($el) {
                var el = $el.find('input,textarea,select'),
                    type = sd_cf_field_get_type($el);
                if (type && window._sd_cf_field_elements && typeof window._sd_cf_field_elements == 'object' && typeof window._sd_cf_field_elements[type] != 'undefined') {
                    el = window._sd_cf_field_elements[type];
                }
                return el;
            }

            /**
            * Get the field type.
            */
            function sd_cf_field_get_type($el) {
                return $el.data('rule-type');
            }

            /**
            * Get the field value.
            */
            function sd_cf_field_get_value($el) {
                var current_value = $el.val();

                if ($el.is(':checkbox')) {
                    current_value = '';
                    if ($el.parents('[data-rule-key]').find('input:checked').length > 1) {
                        $el.parents('[data-rule-key]').find('input:checked').each(function() {
                            current_value = current_value + jQuery(this).val() + ' ';
                        });
                    } else {
                        if ($el.parents('[data-rule-key]').find('input:checked').length >= 1) {
                            current_value = $el.parents('[data-rule-key]').find('input:checked').val();
                        }
                    }
                }

                if ($el.is(':radio')) {
                    current_value = $el.parents('[data-rule-key]').find('input[type=radio]:checked').val();
                }

                return current_value;
            }

            /**
            * Get the field default value.
            */
            function sd_cf_field_get_default_value($el) {
                var value = '',
                    type = sd_cf_field_get_type($el);

                switch (type) {
                    case 'text':
                    case 'number':
                    case 'date':
                    case 'textarea':
                    case 'select':
                        value = $el.find('input:text,input[type="number"],textarea,select').val();
                        break;
                    case 'phone':
                    case 'email':
                    case 'color':
                    case 'url':
                    case 'hidden':
                    case 'password':
                    case 'file':
                        value = $el.find('input[type="' + type + '"]').val();
                        break;
                    case 'multiselect':
                        value = $el.find('select').val();
                        break;
                    case 'radio':
                        if ($el.find('input[type="radio"]:checked').length >= 1) {
                            value = $el.find('input[type="radio"]:checked').val();
                        }
                        break;
                    case 'checkbox':
                        if ($el.find('input[type="checkbox"]:checked').length >= 1) {
                            if ($el.find('input[type="checkbox"]:checked').length > 1) {
                                var values = [];
                                values.push(value);
                                $el.find('input[type="checkbox"]:checked').each(function() {
                                    values.push(jQuery(this).val());
                                });
                                value = values;
                            } else {
                                value = $el.find('input[type="checkbox"]:checked').val();
                            }
                        }
                        break;
                    default:
                        if (window._sd_cf_field_default_values && typeof window._sd_cf_field_default_values == 'object' && typeof window._sd_cf_field_default_values[type] != 'undefined') {
                            value = window._sd_cf_field_default_values[type];
                        }
                        break;
                }
                return {
                    type: type,
                    value: value
                };
            }

            /**
            * Reset field default value.
            */
            function sd_cf_field_reset_default_value($el) {
                var type = sd_cf_field_get_type($el),
                    key = $el.data('rule-key'),
                    field = sd_cf_field_default_values[key];

                switch (type) {
                    case 'text':
                    case 'number':
                    case 'date':
                    case 'textarea':
                        $el.find('input:text,input[type="number"],textarea').val(field.value);
                        break;
                    case 'phone':
                    case 'email':
                    case 'color':
                    case 'url':
                    case 'hidden':
                    case 'password':
                    case 'file':
                        $el.find('input[type="' + type + '"]').val(field.value);
                        break;
                    case 'select':
                        $el.find('select').find('option').prop('selected', false);
                        $el.find('select').val(field.value);
                        $el.find('select').trigger('change');
                        break;
                    case 'multiselect':
                        $el.find('select').find('option').prop('selected', false);
                        if ((typeof field.value === 'object' || typeof field.value === 'array') && !field.value.length && $el.find('select option:first').text() == '') {
                            $el.find('select option:first').remove(); // Clear first option to show placeholder.
                        }
                        jQuery.each(field.value, function(i, v) {
                            $el.find('select').find('option[value="' + v + '"]').attr('selected', true);
                        });
                        $el.find('select').trigger('change');
                        break;
                    case 'checkbox':
                        if ($el.find('input[type="checkbox"]:checked').length >= 1) {
                            $el.find('input[type="checkbox"]:checked').prop('checked', false);
                            if (Array.isArray(field.value)) {
                                jQuery.each(field.value, function(i, v) {
                                    $el.find('input[type="checkbox"][value="' + v + '"]').attr('checked', true);
                                });
                            } else {
                                $el.find('input[type="checkbox"][value="' + field.value + '"]').attr('checked', true);
                            }
                        }
                        break;
                    case 'radio':
                        if ($el.find('input[type="radio"]:checked').length >= 1) {
                            setTimeout(function() {
                                $el.find('input[type="radio"]:checked').prop('checked', false);
                                $el.find('input[type="radio"][value="' + field.value + '"]').attr('checked', true);
                            }, 100);
                        }
                        break;
                    default:
                        jQuery(document.body).trigger('sd_cf_field_reset_default_value', type, $el, field);
                        break;
                }

                if (!$el.hasClass('sd-cf-field-has-changed')) {
                    var el = sd_cf_field_get_element($el);
                    if (type === 'radio' || type === 'checkbox') {
                        el = el.find(':checked');
                    }
                    if (el) {
                        el.trigger('change');
                        $el.addClass('sd-cf-field-has-changed');
                    }
                }
            }

            /**
            * Get the field children.
            */
            function sd_cf_field_get_children(field_key) {
                var rules = [];
                jQuery.each(sd_cf_field_rules, function(j, rule) {
                    if (rule.field.field === field_key) {
                        rules.push(rule.field.rule);
                    }
                });
                return rules;
            }

            /**
            * Check in array field value.
            */
            function sd_cf_field_in_array(find, item, match) {
                var found = false,
                    key;
                match = !!match;

                for (key in item) {
                    if ((match && item[key] === find) || (!match && item[key] == find)) {
                        found = true;
                        break;
                    }
                }
                return found;
            }

            /**
            * App the field condition action.
            */
            function sd_cf_field_apply_action($el, rule, isTrue) {
                var $destEl = jQuery('[data-rule-key="' + rule.key + '"]');

                if (rule.action === 'show' && isTrue) {
                    if ($destEl.is(':hidden')) {
                        sd_cf_field_reset_default_value($destEl);
                    }
                    sd_cf_field_show_element($destEl);
                } else if (rule.action === 'show' && !isTrue) {
                    sd_cf_field_hide_element($destEl);
                } else if (rule.action === 'hide' && isTrue) {
                    sd_cf_field_hide_element($destEl);
                } else if (rule.action === 'hide' && !isTrue) {
                    if ($destEl.is(':hidden')) {
                        sd_cf_field_reset_default_value($destEl);
                    }
                    sd_cf_field_show_element($destEl);
                }
                return $el.removeClass('sd-cf-field-has-changed');
            }

            /**
            * Show field element.
            */
            function sd_cf_field_show_element($el) {
                $el.removeClass('d-none').show();

                if (window && window.navigator.userAgent.indexOf("MSIE") !== -1) {
                    $el.css({
                        "visibility": "visible"
                    });
                }
            }

            /**
            * Hide field element.
            */
            function sd_cf_field_hide_element($el) {
                $el.addClass('d-none').hide();

                if (window && window.navigator.userAgent.indexOf("MSIE") !== -1) {
                    $el.css({
                        "visibility": "hidden"
                    });
                }
            }

            /**
            * Show field child elements.
            */
            function sd_cf_field_hide_child_elements() {
                jQuery.each(sd_cf_field_key_rules, function(i, conds) {
                    if (i && conds && conds.length && (jQuery('[data-rule-key="' + i + '"]:hidden').length >= 1 || jQuery('[data-rule-key="' + i + '"]').css('display') === 'none')) {
                        jQuery.each(conds, function(key, cond) {
                            jQuery('[data-rule-key="' + cond.key + '"]').addClass('d-none').hide();
                        });
                    }
                });
            }
            <?php do_action( 'wp_super_duper_conditional_fields_js', $this ); ?>
            </script>
                        <?php
                        $output = ob_get_clean();

                        return str_replace( array( '<script>', '</script>' ), '', trim( $output ) );
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
					$title = $this->SD->get_widget_icon( $args['icon'], $args['title']  );
				} else {
					$title = esc_attr($args['title']);
				}
			}

			return $title;
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
				$this->SD->get_arguments();
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
}
