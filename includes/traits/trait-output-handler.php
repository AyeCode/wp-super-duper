<?php
/**
 * WP Super Duper Output Handler Trait
 *
 * This trait contains all methods responsible for generating the final HTML
 * output for both widgets and shortcodes. It handles argument processing,
 * title rendering, and wrapping the content.
 *
 * @version 1.2.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait WP_Super_Duper_Output_Handler {

	/**
	 * Register the parent shortcode and its AJAX handler for block previews.
	 */
	public function register_shortcode() {
		add_shortcode( $this->base_id, array( $this, 'shortcode_output' ) );
		add_action( 'wp_ajax_super_duper_output_shortcode', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render the shortcode via AJAX for Gutenberg block previews.
	 */
	public function render_shortcode() {
		check_ajax_referer( 'super_duper_output_shortcode', '_ajax_nonce', true );
		if ( ! current_user_can( 'edit_posts' ) ) { // More appropriate capability check
			wp_die();
		}

		global $post;
		if ( isset( $_POST['post_id'] ) && $_POST['post_id'] ) {
			$post_obj = get_post( absint( $_POST['post_id'] ) );
			if ( ! empty( $post_obj ) && empty( $post ) ) {
				$post = $post_obj;
			}
		}

		if ( isset( $_POST['shortcode'] ) && $_POST['shortcode'] ) {
			$is_preview = sd_is_preview();
			$shortcode_name   = sanitize_title_with_dashes( $_POST['shortcode'] );
			$attributes_array = isset( $_POST['attributes'] ) && is_array($_POST['attributes']) ? $_POST['attributes'] : array();
			$attributes       = '';

			if ( ! empty( $attributes_array ) ) {
				foreach ( $attributes_array as $key => $value ) {
					if ( is_array( $value ) ) {
						$value = implode( ",", $value );
					}
					if ( ! empty( $value ) || $value === '0' ) { // Allow '0' as a value
						$value = wp_unslash( $value );
						$value = $is_preview ? sd_encode_shortcodes( $value ) : $value;
						$attributes .= " " . esc_attr( sanitize_key( $key ) ) . "='" . esc_attr( $value ) . "'";
					}
				}
			}

			$shortcode = "[" . esc_attr( $shortcode_name ) . $attributes . "]";
			$content = do_shortcode( $shortcode );

			if ( ! empty( $content ) && $is_preview ) {
				$content = sd_decode_shortcodes( $content );
			}

			echo $content;
		}
		wp_die();
	}

	/**
	 * The main output function for the shortcode.
	 *
	 * @param array  $args    The shortcode attributes.
	 * @param string $content The enclosed content, if any.
	 * @return string The rendered HTML.
	 */
	public function shortcode_output( $args = array(), $content = '' ) {
		$_instance = $args;
		$args = $this->argument_values( $args );
		$args = sd_string_to_bool( $args );

		if ( ! empty( $content ) ) {
			$args['html'] = $content;
		}

		if ( ! sd_is_preview() ) {
			$args = apply_filters( 'wp_super_duper_widget_display_callback', $args, $this, $_instance );
			if ( ! is_array( $args ) ) {
				return $args;
			}
		}

		$class = isset( $this->options['widget_ops']['classname'] ) ? esc_attr( $this->options['widget_ops']['classname'] ) : '';
		$class .= " sdel-" . $this->get_instance_hash();
		$class = apply_filters( 'wp_super_duper_div_classname', $class, $args, $this );
		$class = apply_filters( 'wp_super_duper_div_classname_' . $this->base_id, $class, $args, $this );

		$attrs = apply_filters( 'wp_super_duper_div_attrs', '', $args, $this );
		$attrs = apply_filters( 'wp_super_duper_div_attrs_' . $this->base_id, '', $args, $this );

		$shortcode_args = array();
		$output         = '';
		$no_wrap        = isset( $this->options['no_wrap'] ) && $this->options['no_wrap'] ? true : (isset( $args['no_wrap'] ) && $args['no_wrap']);
		$main_content   = $this->output( $args, $shortcode_args, $content );

		if ( $main_content && ! $no_wrap ) {
			$output .= '<div class="' . esc_attr( $class ) . '" ' . $attrs . '>';
			if ( ! empty( $args['title'] ) ) {
				$output .= $this->output_title( array(), $args );
			}
			$output .= $main_content;
			$output .= '</div>';
		} elseif ( $main_content && $no_wrap ) {
			$output .= $main_content;
		}

		if ( sd_is_preview() && empty($output) ) {
			$output = $this->preview_placeholder_text( "{{" . $this->base_id . "}}" );
		}

		return apply_filters( 'wp_super_duper_widget_output', $output, $args, $shortcode_args, $this );
	}

	/**
	 * Outputs the content of the widget.
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title', 'before_widget', and 'after_widget'.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {
		$args = is_array( $args ) ? $args : array();
		$argument_values = $this->argument_values( $instance );
		$argument_values = sd_string_to_bool( $argument_values );
		$output = $this->output( $argument_values, $args );
		$no_wrap = isset( $argument_values['no_wrap'] ) && $argument_values['no_wrap'];

		ob_start();
		if ( $output && ! $no_wrap ) {
			$class_original = $this->options['widget_ops']['classname'];
			$class = $this->options['widget_ops']['classname'] . " sdel-" . $this->get_instance_hash();

			$before_widget = ! empty( $args['before_widget'] ) ? str_replace( $class_original, $class, $args['before_widget'] ) : '';
			$before_widget = apply_filters( 'wp_super_duper_before_widget', $before_widget, $args, $instance, $this );
			$before_widget = apply_filters( 'wp_super_duper_before_widget_' . $this->base_id, $before_widget, $args, $instance, $this );
			echo $before_widget;

			if ( $this->is_elementor_widget_output() ) {
				$class = apply_filters( 'wp_super_duper_div_classname', $class, $args, $this );
				$attrs = apply_filters( 'wp_super_duper_div_attrs', '', $args, $this );
				echo "<span class='" . esc_attr( $class ) . "' " . $attrs . ">";
			}

			echo $this->output_title( $args, $instance );
			echo $output;

			if ( $this->is_elementor_widget_output() ) {
				echo "</span>";
			}

			$after_widget = ! empty( $args['after_widget'] ) ? $args['after_widget'] : '';
			$after_widget = apply_filters( 'wp_super_duper_after_widget', $after_widget, $args, $instance, $this );
			$after_widget = apply_filters( 'wp_super_duper_after_widget_' . $this->base_id, $after_widget, $args, $instance, $this );
			echo $after_widget;

		} elseif ( sd_is_preview() && empty($output) ) {
			echo $this->preview_placeholder_text( "{{" . $this->base_id . "}}" );
		} elseif ( $output && $no_wrap ) {
			echo $output;
		}
		$final_output = ob_get_clean();
		echo apply_filters( 'wp_super_duper_widget_output', $final_output, $instance, $args, $this );
	}

	/**
	 * Placeholder for the main output logic, to be implemented by the child class.
	 *
	 * @param array  $args        The processed instance/shortcode settings.
	 * @param array  $widget_args The original widget display arguments.
	 * @param string $content     The enclosed shortcode content.
	 * @return string The main content HTML.
	 */
	public function output( $args = array(), $widget_args = array(), $content = '' ) {
		// This method should be overridden by the child class.
		return '';
	}

	/**
	 * Output the widget/shortcode title.
	 *
	 * @param array $args     The widget display arguments.
	 * @param array $instance The instance settings.
	 * @return string The rendered title HTML.
	 */
	public function output_title( $args, $instance = array() ) {
		$output = '';
		if ( ! empty( $instance['title'] ) ) {
			$title  = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
			$before_title = isset( $args['before_title'] ) ? $args['before_title'] : '';
			$after_title  = isset( $args['after_title'] ) ? $args['after_title'] : '';
			$output = $before_title . $title . $after_title;
		}
		return $output;
	}

	/**
	 * Get the argument values for an instance, applying defaults.
	 *
	 * @param array $instance The instance settings.
	 * @return array The processed argument values.
	 */
	public function argument_values( $instance ) {
		$argument_values = array();
		$this->instance = $instance;
		if ( empty( $this->arguments ) ) {
			$this->arguments = $this->get_arguments();
		}

		if ( ! empty( $this->arguments ) ) {
			foreach ( $this->arguments as $key => $args ) {
				$argument_values[ $key ] = isset( $instance[ $key ] ) ? $instance[ $key ] : (isset( $args['default'] ) ? $args['default'] : '');
				if ( $args['type'] == 'checkbox' && ! isset( $instance[ $key ] ) ) {
					// Don't set default for a checkbox that was never saved.
					// The update method handles setting it to '0' on save.
				}
			}
		}
		return $argument_values;
	}

	/**
	 * Get the placeholder text to show if output is empty in a builder preview.
	 *
	 * @param string $name The name of the element.
	 * @return string The placeholder HTML.
	 */
	public function preview_placeholder_text( $name = '' ) {
		return "<div style='background:#0185ba33;padding: 10px;border: 4px #ccc dashed;'>" . sprintf( esc_html__( 'Placeholder for: %s', 'ayecode-connect' ), esc_html( $name ) ) . "</div>";
	}

	/**
	 * Tests if the current output is inside an Elementor widget container.
	 *
	 * @return bool
	 */
	public function is_elementor_widget_output() {
		return defined( 'ELEMENTOR_VERSION' ) && isset( $this->number ) && $this->number == 'REPLACE_TO_ID';
	}
}
