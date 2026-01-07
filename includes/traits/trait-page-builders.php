<?php
/**
 * WP Super Duper Page Builders Trait
 *
 * This trait contains all methods for integrating with third-party page builders,
 * such as Elementor, Fusion Builder, Bricks, Cornerstone, and SiteOrigin.
 *
 * @version 1.2.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait WP_Super_Duper_Page_Builders {

	/**
	 * Load the Bricks conversion class if we are running Bricks.
	 */
	public function load_bricks_element_class() {
		$bricks_file = SUPER_DUPER_INCLUDES_PATH . 'class-super-duper-bricks-element.php';
		if ( file_exists( $bricks_file ) ) {
			include_once $bricks_file;
		}
	}

	/**
	 * Add our widget CSS to the Elementor editor.
	 */
	public function elementor_editor_styles() {
		wp_add_inline_style( 'elementor-editor', $this->widget_css( false ) );
	}

	/**
	 * Register the element with Fusion Builder (Avada).
	 */
	public function register_fusion_element() {
		$options = $this->options;
		if ( $this->base_id ) {
			$params = $this->get_fusion_params();
			$args = array(
				'name'            => $options['name'],
				'shortcode'       => $this->base_id,
				'icon'            => isset($options['block-icon']) ? $options['block-icon'] : 'far fa-square',
				'allow_generator' => true,
			);
			if ( ! empty( $params ) ) {
				$args['params'] = $params;
			}
			if ( function_exists('fusion_builder_map') ) {
				fusion_builder_map( $args );
			}
		}
	}

	/**
	 * Get the parameters for Fusion Builder in the correct format.
	 *
	 * @return array The formatted parameters.
	 */
	public function get_fusion_params() {
		$params    = array();
		$arguments = $this->get_arguments();

		if ( ! empty( $arguments ) ) {
			foreach ( $arguments as $key => $val ) {
				$param = array();
				$param['type'] = str_replace(
					array("text", "number", "email", "color", "checkbox"),
					array("textfield", "textfield", "textfield", "colorpicker", "select"),
					$val['type']
				);

				if ( $val['type'] == 'multiselect' || ( ( $param['type'] == 'select' || $val['type'] == 'select' ) && ! empty( $val['multiple'] ) ) ) {
					$param['type']     = 'multiple_select';
					$param['multiple'] = true;
				}

				$param['heading']     = isset( $val['title'] ) ? $val['title'] : '';
				$param['description'] = isset( $val['desc'] ) ? $val['desc'] : '';
				$param['param_name']  = $key;
				$param['default']     = isset( $val['default'] ) ? $val['default'] : '';

				if ( isset( $val['group'] ) ) {
					$param['group'] = $val['group'];
				}

				if ( $val['type'] == 'checkbox' ) {
					if ( isset( $val['default'] ) && $val['default'] == '0' ) {
						unset( $param['default'] );
					}
					$param['value'] = array( '0' => __( "No", 'ayecode-connect' ), '1' => __( "Yes", 'ayecode-connect' ) );
				} elseif ( $param['type'] == 'select' || $param['type'] == 'multiple_select' ) {
					$param['value'] = isset( $val['options'] ) ? $val['options'] : array();
				} else {
					$param['value'] = isset( $val['default'] ) ? $val['default'] : '';
				}

				$params[] = $param;
			}
		}
		return $params;
	}

	/**
	 * Maybe insert the shortcode inserter button in the footer if we are in the Cornerstone builder.
	 */
	public static function maybe_cornerstone_builder() {
		if ( did_action( 'cornerstone_before_boot_app' ) ) {
			self::shortcode_insert_button_script();
		}
	}

	/**
	 * Get the JS needed for SiteOrigin Page Builder compatibility.
	 *
	 * @return string The SiteOrigin-specific JavaScript.
	 */
	public static function siteorigin_js() {
		ob_start();
		?>
		<script>
			function sd_so_show_hide(form) {
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
						} catch (e) { console.error('SuperDuper eval error:', e); }
					}
				});
			}
			function sd_so_toggle_advanced($this) {
				var form = jQuery($this).parents('form,.form,.so-content');
				form.find('.sd-advanced-setting').toggleClass('sd-adv-show');
				return false;
			}
			function sd_so_init_widget($this, $selector) {
				if (!$selector) { $selector = 'form'; }
				if (jQuery($this).data('sd-widget-enabled')) { return; }
				jQuery($this).data('sd-widget-enabled', true);
				var $button = '<button title="<?php echo esc_js( __( 'Advanced Settings', 'ayecode-connect' ) );?>" class="button button-primary right sd-advanced-button" onclick="sd_so_toggle_advanced(this);return false;"><i class="fas fa-sliders-h" aria-hidden="true"></i></button>';
				var form = jQuery($this).parents($selector);
				if (jQuery($this).val() == '1' && form.find('.sd-advanced-button').length == 0) {
					form.append($button);
				}
				form.on("change", function () { sd_so_show_hide(form); });
				sd_so_show_hide(form);
			}
			jQuery(function () {
				jQuery(document).on('open_dialog', function (w, e) {
					setTimeout(function () {
						var $advancedInput = jQuery('.so-panels-dialog-wrapper:visible .so-content.panel-dialog .sd-show-advanced');
						if ($advancedInput.length && $advancedInput.val() == '1') {
							sd_so_init_widget($advancedInput, 'div');
						}
					}, 200);
				});
			});
		</script>
		<?php
		$output = ob_get_clean();
		return str_replace( array('<script>', '</script>'), '', $output );
	}

	/**
	 * General function to check if we are in any builder's preview situation.
	 * This now acts as a wrapper for the global sd_is_preview() helper function.
	 *
	 * @return bool
	 */
	public function is_preview() {
		return sd_is_preview();
	}

	/**
	 * FIX: Restored for backwards compatibility.
	 * Tests if the current output is inside an Elementor preview.
	 * @return bool
	 */
	public function is_elementor_preview() {
		return sd_is_preview();
	}

	/**
	 * FIX: Restored for backwards compatibility.
	 * Tests if the current output is inside a Divi preview.
	 * @return bool
	 */
	public function is_divi_preview() {
		return sd_is_preview();
	}

	/**
	 * FIX: Restored for backwards compatibility.
	 * Tests if the current output is inside a Beaver builder preview.
	 * @return bool
	 */
	public function is_beaver_preview() {
		return sd_is_preview();
	}

	/**
	 * FIX: Restored for backwards compatibility.
	 * Tests if the current output is inside a siteorigin builder preview.
	 * @return bool
	 */
	public function is_siteorigin_preview() {
		return sd_is_preview();
	}

	/**
	 * FIX: Restored for backwards compatibility.
	 * Tests if the current output is inside a cornerstone builder preview.
	 * @return bool
	 */
	public function is_cornerstone_preview() {
		return sd_is_preview();
	}

	/**
	 * FIX: Restored for backwards compatibility.
	 * Tests if the current output is inside a fusion builder preview.
	 * @return bool
	 */
	public function is_fusion_preview() {
		return sd_is_preview();
	}

	/**
	 * FIX: Restored for backwards compatibility.
	 * Tests if the current output is inside an Oxygen builder preview.
	 * @return bool
	 */
	public function is_oxygen_preview() {
		return sd_is_preview();
	}

	/**
	 * FIX: Restored for backwards compatibility.
	 * Check for Kallyas theme Zion builder preview.
	 * @return bool
	 */
	public function is_kallyas_zion_preview() {
		return sd_is_preview();
	}

	/**
	 * FIX: Restored for backwards compatibility.
	 * Check for Bricks theme builder preview.
	 * @return bool
	 */
	public function is_bricks_preview() {
		return sd_is_preview();
	}
}
