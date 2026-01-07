<?php
/**
 * WP Super Duper Widget Form Trait
 *
 * This trait contains all methods for rendering the widget settings form in the
 * WordPress admin area (Widgets screen and Customizer), handling the update
 * process, and providing the necessary CSS and JavaScript for interactivity.
 *
 * @version 1.2.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait WP_Super_Duper_Widget_Form {

	/**
	 * Outputs the options form for the widget.
	 *
	 * @param array $instance The widget options.
	 */
	public function form( $instance ) {
		$this->instance = $instance;
		echo $this->widget_advanced_toggle();
		echo "<p>" . esc_html( $this->options['widget_ops']['description'] ) . "</p>";
		$arguments_raw = $this->get_arguments();

		if ( is_array( $arguments_raw ) ) {
			$arguments = $this->group_arguments( $arguments_raw );
			$has_sections = ( $arguments !== $arguments_raw );

			if ( $has_sections ) {
				$panel_count = 0;
				foreach ( $arguments as $key => $args ) {
					$hide       = $panel_count ? ' style="display:none;" ' : '';
					$icon_class = $panel_count ? 'fas fa-chevron-up' : 'fas fa-chevron-down';
					echo "<button onclick='jQuery(this).find(\"i\").toggleClass(\"fas fa-chevron-up fas fa-chevron-down\");jQuery(this).next().slideToggle();' type='button' class='sd-toggle-group-button'>" . esc_html( $key ) . " <i style='float:right;' class='" . esc_attr( $icon_class ) . "'></i></button>";
					echo "<div class='sd-toggle-group' " . $hide . ">";
					foreach ( $args as $k => $a ) {
						$this->widget_inputs_row_start( $k, $a );
						$this->widget_inputs( $a, $instance );
						$this->widget_inputs_row_end( $k, $a );
					}
					echo "</div>";
					$panel_count++;
				}
			} else {
				foreach ( $arguments as $key => $args ) {
					$this->widget_inputs_row_start( $key, $args );
					$this->widget_inputs( $args, $instance );
					$this->widget_inputs_row_end( $key, $args );
				}
			}
		}
	}

	/**
	 * Processing widget options on save.
	 *
	 * @param array $new_instance The new options.
	 * @param array $old_instance The previous options.
	 * @return array The updated options.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array_merge( (array) $old_instance, (array) $new_instance );
		$this->instance = $instance;
		if ( empty( $this->arguments ) ) { $this->get_arguments(); }

		if ( ! empty( $this->arguments ) ) {
			foreach ( $this->arguments as $argument ) {
				if ( isset( $argument['type'] ) && $argument['type'] == 'checkbox' && ! isset( $new_instance[ $argument['name'] ] ) ) {
					$instance[ $argument['name'] ] = '0';
				}
			}
		}
		if ( ! empty( $instance['title'] ) ) {
			$instance['title'] = wp_kses_post( $instance['title'] );
		}
		return $instance;
	}

	/**
	 * Builds the individual inputs for the widget options form.
	 *
	 * @param array $args     The argument configuration.
	 * @param array $instance The current widget instance values.
	 */
	public function widget_inputs( $args, $instance ) {
		$value = isset( $instance[ $args['name'] ] ) ? $instance[ $args['name'] ] : ( isset($args['default']) ? $args['default'] : '' );
		$placeholder = ! empty( $args['placeholder'] ) ? "placeholder='" . esc_attr( $args['placeholder'] ) . "'" : '';
		$class = ( isset($args['advanced']) && $args['advanced'] ) ? " sd-advanced-setting " : "";
		$element_require = isset($args['element_require']) && $args['element_require'] ? $this->convert_element_require($args['element_require']) : '';
		$custom_attributes = isset( $args['custom_attributes'] ) ? $this->array_to_attributes( $args['custom_attributes'], true ) : '';
		?>
	<p class="sd-argument <?php echo esc_attr( $class ); ?>" data-argument='<?php echo esc_attr( $args['name'] ); ?>' data-element_require='<?php echo esc_attr( $element_require ); ?>'>
		<?php
		switch ( $args['type'] ) {
			case "text": case "password": case "number": case "email": case "tel": case "url": case "color":
			?>
			<label for="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>"><?php echo $this->widget_field_title( $args );?><?php echo $this->widget_field_desc( $args ); ?></label>
			<input <?php echo $placeholder; ?> class="widefat" <?php echo $custom_attributes; ?> id="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $args['name'] ) ); ?>" type="<?php echo esc_attr( $args['type'] ); ?>" value="<?php echo esc_attr( $value ); ?>">
			<?php
			break;
			case "select":
				$multiple = isset( $args['multiple'] ) && $args['multiple'];
				$current_value = $multiple ? (array) $value : $value;
				?>
				<label for="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>"><?php echo $this->widget_field_title( $args ); ?><?php echo $this->widget_field_desc( $args ); ?></label>
				<select <?php echo $placeholder; ?> class="widefat" <?php echo $custom_attributes; ?> id="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $args['name'] ) ) . ($multiple ? '[]' : ''); ?>" <?php if ( $multiple ) { echo "multiple"; } ?>>
					<?php
					if ( ! empty( $args['options'] ) ) {
						foreach ( $args['options'] as $val => $label ) {
							if ( $multiple ) {
								$selected = in_array( (string)$val, $current_value, true ) ? 'selected="selected"' : '';
							} else {
								$selected = selected( $current_value, $val, false );
							}
							echo "<option value='" . esc_attr($val) . "' " . $selected . ">" . esc_html($label) . "</option>";
						}
					}
					?>
				</select>
				<?php
				break;
			case "checkbox":
				?>
				<input <?php echo $placeholder; ?> <?php checked( '1', $value, true ) ?> <?php echo $custom_attributes; ?> class="widefat" id="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $args['name'] ) ); ?>" type="checkbox" value="1">
				<label for="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>"><?php echo $this->widget_field_title( $args );?><?php echo $this->widget_field_desc( $args ); ?></label>
				<?php
				break;
			case "textarea":
				?>
				<label for="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>"><?php echo $this->widget_field_title( $args ); ?><?php echo $this->widget_field_desc( $args ); ?></label>
				<textarea <?php echo $placeholder; ?> class="widefat" <?php echo $custom_attributes; ?> id="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $args['name'] ) ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
				<?php
				break;
			case "hidden":
				?>
				<input id="<?php echo esc_attr( $this->get_field_id( $args['name'] ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $args['name'] ) ); ?>" type="hidden" value="<?php echo esc_attr( $value ); ?>">
				<?php
				break;
			default:
				echo "<!-- No input type found for " . esc_html( $args['type'] ) . " -->";
		}
		?></p><?php
	}

	public function widget_inputs_row_start( $key, $args ) {
        if ( ! empty( $args['row'] ) ) {
				// Maybe open
				if ( ! empty( $args['row']['open'] ) ) {
					?>
					<div class='bsui sd-argument' data-argument='<?php echo esc_attr( $args['row']['key'] ); ?>' data-element_require='<?php echo ( ! empty( $args['row']['element_require'] ) ? $this->convert_element_require( $args['row']['element_require'] ) : '' ); ?>'>
					<?php if ( ! empty( $args['row']['title'] ) ) { ?>
					<?php
						if ( isset( $args['row']['icon'] ) ) {
							$args['row']['icon'] = '';
						}

						if ( ! isset( $args['row']['device_type'] ) && isset( $args['device_type'] ) ) {
							$args['row']['device_type'] = $args['device_type'];
						}
					?>
					<label class="mb-0"><?php echo $this->widget_field_title( $args['row'] ); ?><?php echo $this->widget_field_desc( $args['row'] ); ?></label>
					<?php } ?>
					<div class='row<?php echo ( ! empty( $args['row']['class'] ) ? ' ' . esc_attr( $args['row']['class'] ) : '' ); ?>'>
					<div class='col pr-2'>
					<?php
				} else if ( ! empty( $args['row']['close'] ) ) {
					echo "<div class='col pl-0 ps-0'>";
				} else {
					echo "<div class='col pl-0 ps-0 pr-2 pe-2'>";
				}
			}
    }
	public function widget_inputs_row_end( $key, $args ) {
        if ( ! empty( $args['row'] ) ) {
				// Maybe close
				if ( ! empty( $args['row']['close'] ) ) {
					echo "</div></div>";
				}
				echo "</div>";
			}
	 }

	public function widget_advanced_toggle() {
		$val = $this->block_show_advanced() ? 1 : 0;
		return "<input type='hidden' class='sd-show-advanced' value='" . esc_attr($val) . "' />";
	}

	public function convert_element_require( $input ) {
		$input = str_replace( "'", '"', $input );
		return esc_attr( str_replace( array( "[%", "%]", "%:checked]" ), array( "jQuery(form).find('[data-argument=\"", "\"]').find('input,select,textarea').val()", "\"]').find('input:checked').val()" ), $input ) );
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

	public function widget_field_desc( $args ) {
		if ( isset( $args['desc'] ) && $args['desc'] ) {
			if ( isset( $args['desc_tip'] ) && $args['desc_tip'] ) {
				return $this->desc_tip( $args['desc'] );
			}
			return '<span class="description">' . wp_kses_post( $args['desc'] ) . '</span>';
		}
		return '';
	}

	public function widget_field_title( $args ) {
		$title = '';
		if ( isset( $args['title'] ) && $args['title'] ) {
			if ( ! empty( $args['device_type'] ) ) {
				$args['title'] .= ' (' . $args['device_type'] . ')';
			}
			if ( isset( $args['icon'] ) && $args['icon'] ) {
				$title = self::get_widget_icon( $args['icon'], $args['title']  );
			} else {
				$title = esc_html( $args['title'] );
			}
		}
		return $title;
	}

	public function desc_tip( $tip, $allow_html = false ) {
		$tip = $allow_html ? $this->sanitize_tooltip( $tip ) : esc_attr( $tip );
		return '<span class="gd-help-tip dashicons dashicons-editor-help" title="' . $tip . '"></span>';
	}

	public function sanitize_tooltip( $var ) {
		return htmlspecialchars( wp_kses( html_entity_decode( $var ), array( 'br' => array(), 'em' => array(), 'strong' => array(), 'small' => array(), 'span' => array(), 'ul' => array(), 'li' => array(), 'ol' => array(), 'p' => array() ) ) );
	}

	public function widget_css( $advanced = true ) {
		ob_start();
		?>
		<style>
			<?php if( $advanced ){ ?>
            .sd-advanced-setting { display: none; }
            .sd-advanced-setting.sd-adv-show { display: block; }
            .sd-argument.sd-require-hide, .sd-advanced-setting.sd-require-hide { display: none; }
            button.sd-advanced-button { margin-right: 3px !important; font-size: 20px !important; }
			<?php } ?>
            button.sd-toggle-group-button { background-color: #f3f3f3; color: #23282d; cursor: pointer; padding: 10px; width: 100%; border: none; text-align: left; outline: none; font-size: 13px; font-weight: bold; margin-bottom: 1px; }
            .elementor-control .sd-argument select[multiple]{height:100px}
            .elementor-control .sd-argument select[multiple] option{padding:3px}
		</style>
		<?php
		return str_replace( array('<style>', '</style>'), '', ob_get_clean() );
	}

	public function widget_js() {
		ob_start();
		?>
		<script>
            function sd_toggle_advanced($this) {
                var form = jQuery($this).parents('form,.form');
                form.find('.sd-advanced-setting').toggleClass('sd-adv-show');
                return false;
            }
            function sd_show_hide(form) {
                jQuery(form).find(".sd-argument").each(function () {
                    var $element_require = jQuery(this).data('element_require');
                    if ($element_require) {
                        $element_require = $element_require.replace(/&#039;/g, "'").replace(/&quot;/g, '"');
                        try {
                            if (eval($element_require)) {
                                jQuery(this).removeClass('sd-require-hide');
                            } else {
                                jQuery(this).addClass('sd-require-hide');
                            }
                        } catch(e) { console.log('SD eval error', e); }
                    }
                });
            }
            function sd_init_widget($this, $selector, $form) {
                if (!$selector) { $selector = 'form'; }
                if (jQuery($this).data('sd-widget-enabled')) { return; }
                jQuery($this).data('sd-widget-enabled', true);
                var $button = '<button title="<?php echo esc_js( 'Advanced Settings', 'ayecode-connect' );?>" style="line-height: 28px;" class="button button-primary right sd-advanced-button" onclick="sd_toggle_advanced(this);return false;"><span class="dashicons dashicons-admin-settings" style="width: 28px;font-size: 28px;"></span></button>';
                var form = $form ? $form : jQuery($this).parents($selector);
                if (jQuery($this).val() == '1' && form.find('.sd-advanced-button').length == 0) {
                    if (form.find('.widget-control-save').length > 0) {
                        form.find('.widget-control-save').after($button);
                    } else {
                        form.find('.sd-show-advanced').after($button);
                    }
                }
                form.on("change", function() { sd_show_hide(form); });
                sd_show_hide(form);
            }
            function sd_init_customizer_widget(section) {
                if (section.expanded) {
                    section.expanded.bind(function (isExpanding) {
                        if (isExpanding && jQuery(section.container).find('.sd-show-advanced').length) {
                            sd_init_widget(jQuery(section.container).find('.sd-show-advanced'), ".form");
                        }
                    });
                }
            }
            jQuery(function () {
                if (!wp.customize) {
                    jQuery(".sd-show-advanced").each(function (index) { sd_init_widget(this, "form"); });
                }
                jQuery(document).on('widget-added widget-updated', function (e, widget) {
                    if (jQuery(widget).find('.sd-show-advanced').length) {
                        sd_init_widget(jQuery(widget).find('.sd-show-advanced'), "form", jQuery(widget).find('.sd-show-advanced').closest('form'));
                    }
                });
            });
            if (wp.customize) {
                wp.customize.bind('ready', function () {
                    wp.customize.control.each(function (section) { sd_init_customizer_widget(section); });
                    wp.customize.control.bind('add', function (section) { sd_init_customizer_widget(section); });
                });
            }
			<?php do_action( 'wp_super_duper_widget_js', $this ); ?>
		</script>
		<?php
		return str_replace( array('<script>', '</script>'), '', ob_get_clean() );
	}
}
