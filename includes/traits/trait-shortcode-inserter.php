<?php
/**
 * WP Super Duper Shortcode Inserter Trait
 *
 * This trait contains all methods for the "Add Shortcode" media button,
 * its popup modal (ThickBox), and the associated JavaScript and AJAX handlers.
 * All methods are static for performance.
 *
 * @version 1.2.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait WP_Super_Duper_Shortcode_Inserter {

	/**
	 * Get the shortcode builder picker HTML via AJAX.
	 *
	 * @param string $editor_id The ID of the editor requesting the picker.
	 * @return string The picker HTML.
	 */
	public static function get_picker( $editor_id = '' ) {
		ob_start();
		if ( isset( $_POST['editor_id'] ) ) {
			$editor_id = esc_attr( $_POST['editor_id'] );
		} elseif ( isset( $_REQUEST['et_fb'] ) ) {
			$editor_id = 'main_content_content_vb_tiny_mce';
		}

		global $sd_widgets;
		?>
		<div class="sd-shortcode-left-wrap">
			<?php
			ksort( $sd_widgets );
			if ( ! empty( $sd_widgets ) ) {
				echo '<select class="widefat" onchange="sd_get_shortcode_options(this);">';
				echo "<option>" . __( 'Select shortcode', 'ayecode-connect' ) . "</option>";
				foreach ( $sd_widgets as $shortcode => $class ) {
					if ( ! empty( $class['output_types'] ) && ! in_array( 'shortcode', $class['output_types'] ) ) { continue; }
					echo "<option value='" . esc_attr( $shortcode ) . "'>" . esc_html( $shortcode ) . " (" . esc_html( $class['name'] ) . ")</option>";
				}
				echo "</select>";
			}
			?>
			<div class="sd-shortcode-settings"></div>
		</div>

		<div class="sd-shortcode-right-wrap">
			<textarea id='sd-shortcode-output' disabled></textarea>
			<div id='sd-shortcode-output-actions'>
				<?php if ( $editor_id != '' ) : ?>
					<button class="button sd-insert-shortcode-button" onclick="sd_insert_shortcode(<?php if ( ! empty( $editor_id ) ) { echo "'" . esc_js( $editor_id ) . "'"; } ?>)"><?php _e( 'Insert shortcode', 'ayecode-connect' ); ?></button>
				<?php endif; ?>
				<button class="button" onclick="sd_copy_to_clipboard()"><?php _e( 'Copy shortcode' ); ?></button>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		if ( wp_doing_ajax() ) {
			echo $html;
			$should_die = true;
			$dont_die = array('parent_tag', 'avia_request');
			foreach ( $dont_die as $request ) {
				if ( isset( $_REQUEST[ $request ] ) ) {
					$should_die = false;
					break;
				}
			}
			if ( $should_die ) {
				wp_die();
			}
		}
		return $html;
	}

	/**
	 * Get widget settings via AJAX for the shortcode inserter.
	 */
	public static function get_widget_settings() {
		global $sd_widgets;
		check_ajax_referer( 'super_duper_output_shortcode', '_ajax_nonce' ); // Nonce check for security

		$shortcode = isset( $_REQUEST['shortcode'] ) ? sanitize_title_with_dashes( $_REQUEST['shortcode'] ) : '';
		if ( ! $shortcode || ! isset( $sd_widgets[ $shortcode ] ) || ! isset( $sd_widgets[ $shortcode ]['class_name'] ) ) {
			wp_die();
		}

		$class_name = $sd_widgets[ $shortcode ]['class_name'];
		if ( ! class_exists( $class_name ) ) {
			wp_die();
		}

		$widget = new $class_name();
		ob_start();
		$widget->form( array() );
		$form = ob_get_clean();

		echo "<form id='" . esc_attr( $shortcode ) . "'>" . $form . "<div class=\"widget-control-save\"></div></form>";
		echo "<style>" . $widget->widget_css() . "</style>";
		echo "<script>" . $widget->widget_js() . "</script>";
		wp_die();
	}

	/**
	 * Insert the shortcode builder button next to the media button.
	 */
	public static function shortcode_insert_button( $editor_id = '', $insert_shortcode_function = '' ) {
		global $shortcode_insert_button_once;
		if ( $shortcode_insert_button_once ) {
			return;
		}
		$shortcode_insert_button_once = true;

		add_thickbox();

		if ( function_exists( 'cornerstone_plugin_init' ) && ! is_admin() ) {
			echo '<span id="insert-media-button">';
		}

		echo self::shortcode_button( 'this', 'true' );

		if ( function_exists( 'cornerstone_plugin_init' ) && ! is_admin() ) {
			echo '</span>';
		}

		if ( ! ( function_exists( 'generate_sections_sections_metabox' ) && did_action( 'generate_sections_metabox' ) ) ) {
			self::shortcode_insert_button_script( $editor_id, $insert_shortcode_function );
		}
	}

	/**
	 * Get the HTML for the shortcode inserter button.
	 *
	 * @return string The button HTML.
	 */
	public static function shortcode_button( $id = '', $search_for_id = '' ) {
		ob_start();
		?>
		<span class="sd-lable-shortcode-inserter">
            <a onclick="sd_ajax_get_picker(<?php echo $id; if ( $search_for_id ) { echo "," . $search_for_id; } ?>);" href="#TB_inline?width=100%&amp;height=550&amp;inlineId=super-duper-content-ajaxed" class="thickbox button super-duper-content-open" title="<?php esc_attr_e( 'Add Shortcode', 'ayecode-connect' ); ?>">
                <span style="vertical-align: middle;line-height: 18px;font-size: 20px;" class="dashicons dashicons-screenoptions"></span>
            </a>
            <div id="super-duper-content-ajaxed" style="display:none;"><span><?php esc_html_e( 'Loading', 'ayecode-connect' ); ?></span></div>
        </span>
		<?php
		$html = ob_get_clean();
		return preg_replace( "/\r|\n/", "", trim( $html ) );
	}

	/**
	 * Output the JS and CSS for the shortcode insert button and modal.
	 */
	public static function shortcode_insert_button_script( $editor_id = '', $insert_shortcode_function = '' ) {
		?>
		<style>
            .sd-shortcode-left-wrap { float: left; width: 60%; }
            .sd-shortcode-right-wrap { float: right; width: 35%; }
            #sd-shortcode-output { height: 250px; width: 100%; background: #f0f0f1; border-color: #ddd; box-shadow: inset 0 1px 2px rgba(0,0,0,.07); }
            /* Add other specific styles from the original class here */
		</style>
		<?php
		if ( class_exists( 'SiteOrigin_Panels' ) ) {
			echo "<script>" . self::siteorigin_js() . "</script>";
		}
		?>
		<script>
			<?php if ( ! empty( $insert_shortcode_function ) ) {
				echo $insert_shortcode_function;
			} else { ?>
            function sd_insert_shortcode($editor_id) {
                const shortcode = jQuery('#TB_ajaxContent #sd-shortcode-output').val();
                if (!shortcode) return;

                if (!$editor_id) {
                    if (typeof(wp) !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                        // Gutenberg / Block Editor
                        const { getSelectedBlock } = wp.data.select('core/block-editor');
                        const { insertBlocks } = wp.data.dispatch('core/block-editor');
                        const shortcodeBlock = wp.blocks.createBlock('core/shortcode', { text: shortcode });
                        insertBlocks(shortcodeBlock);
                    } else {
                        // Classic Editor fallback
                        $editor_id = '#wp-content-editor-container textarea';
                    }
                } else {
                    $editor_id = '#' + $editor_id;
                }

                if (typeof tinyMCE !== 'undefined' && tinyMCE.get($editor_id.replace('#', ''))) {
                    tinyMCE.get($editor_id.replace('#', '')).execCommand('mceInsertContent', false, shortcode);
                } else {
                    const $txt = jQuery($editor_id);
                    if ($txt.length) {
                        const caretPos = $txt[0].selectionStart;
                        const textAreaTxt = $txt.val();
                        $txt.val(textAreaTxt.substring(0, caretPos) + shortcode + textAreaTxt.substring(caretPos));
                    }
                }
                tb_remove();
            }
			<?php } ?>

            function sd_copy_to_clipboard() {
                const copyText = document.querySelector("#TB_ajaxContent #sd-shortcode-output");
                copyText.disabled = false;
                copyText.select();
                document.execCommand("copy");
                copyText.disabled = true;
                alert("<?php echo esc_js( 'Copied the shortcode!', 'ayecode-connect' ); ?>");
            }

            function sd_get_shortcode_options(select) {
                const shortcode = jQuery(select).val();
                if (!shortcode) {
                    jQuery('#TB_ajaxContent .sd-shortcode-settings').html('');
                    return;
                }
                const data = {
                    'action': 'super_duper_get_widget_settings',
                    'shortcode': shortcode,
                    '_ajax_nonce': '<?php echo wp_create_nonce( 'super_duper_output_shortcode' ); ?>'
                };
                jQuery.post(ajaxurl, data, function(response) {
                    const settingsDiv = jQuery('#TB_ajaxContent .sd-shortcode-settings');
                    settingsDiv.html(response);
                    const form = settingsDiv.find('form');
                    form.on('change keyup', 'input, select, textarea', () => sd_build_shortcode(shortcode));
                    sd_build_shortcode(shortcode);
                    jQuery('#TB_window').css('height', 'auto').css('width', '90%').css('max-width', '800px');
                });
            }

            function sd_build_shortcode(id) {
                let shortcode = '[' + id;
                const formData = jQuery("#" + id).serializeArray();
                let content = '';
                let attributes = {};

                formData.forEach(function(element) {
                    if (element.value) {
                        let fieldName = element.name.substring(element.name.indexOf('[') + 1, element.name.lastIndexOf(']'));
                        if (fieldName === 'html') {
                            content = element.value;
                        } else if (element.name.endsWith('[]')) {
                            fieldName = fieldName.slice(0, -2);
                            if (!attributes[fieldName]) attributes[fieldName] = [];
                            attributes[fieldName].push(element.value);
                        } else {
                            attributes[fieldName] = element.value;
                        }
                    }
                });

                for (const key in attributes) {
                    let value = Array.isArray(attributes[key]) ? attributes[key].join(',') : attributes[key];
                    shortcode += ` ${key}="${value.replace(/"/g, '&quot;')}"`;
                }

                shortcode += ']';

                if (content) {
                    shortcode += content + '[/' + id + ']';
                }
                jQuery('#TB_ajaxContent #sd-shortcode-output').val(shortcode);
            }

            function sd_ajax_get_picker(id, search) {
                if (search) {
                    id = jQuery(id).closest('.wp-editor-wrap').find('.wp-editor-container textarea').attr('id');
                }
                const data = {
                    'action': 'super_duper_get_picker',
                    'editor_id': id,
                    '_ajax_nonce': '<?php echo wp_create_nonce( 'super_duper_picker' ); ?>'
                };
                jQuery.post(ajaxurl, data, function(response) {
                    jQuery('#TB_ajaxContent').html(response);
                }).then(() => {
                    jQuery('body').one('thickbox:removed', () => jQuery('#super-duper-content-ajaxed').html(''));
                });
            }

            function sd_shortcode_button(id) {
                return id ? '<?php echo self::shortcode_button( "\\'' + id + '\\'" ); ?>' : '<?php echo self::shortcode_button(); ?>';
            }

            // Logic to dynamically add button to textareas
            jQuery(document).ready(function($) {
                // Simplified logic - you can add back the complex builder-specific selectors if needed
                $('body').on('focus', 'textarea.wp-editor-area', function() {
                    const $toolbar = $(this).closest('.wp-editor-wrap').find('.wp-core-ui.wp-editor-tabs');
                    if ($toolbar.length && !$toolbar.find('.sd-lable-shortcode-inserter').length) {
                        $toolbar.append(sd_shortcode_button($(this).attr('id')));
                    }
                });
            });
		</script>
		<?php
	}
}
