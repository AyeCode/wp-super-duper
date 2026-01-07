<?php
/**
 * WP Super Duper Gutenberg Block Editor Universal Script Helper
 *
 * This file contains the universal JavaScript logic that is shared across all
 * Super Duper blocks, such as utility functions and auto-recovery scripts.
 *
 * @version 1.2.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Outputs the universal JavaScript functions for the block editor.
 *
 * This contains all the helper JS that is NOT specific to a single block's
 * registration, but is needed on the editor screen.
 */
function sd_get_block_editor_global_js() {
	global $sd_is_js_functions_loaded, $wp_version, $aui_bs5;

	ob_start();
	?>
	<script>
	<?php


			//if ( ! $sd_is_js_functions_loaded ) {
			$sd_is_js_functions_loaded = true;
			?>
			function sd_show_view_options($this) {
				if (jQuery($this).html().length) {
					jQuery($this).html('');
				} else {
					jQuery($this).html('<div class="position-absolute d-flex flex-column bg-white p-1 rounded border shadow-lg " style="top:-80px;left:-5px;"><div class="dashicons dashicons-desktop mb-1" onclick="sd_set_view_type(\'Desktop\');"></div><div class="dashicons dashicons-tablet mb-1" onclick="sd_set_view_type(\'Tablet\');"></div><div class="dashicons dashicons-smartphone" onclick="sd_set_view_type(\'Mobile\');"></div></div>');
				}
			}

			function sd_set_view_type($device) {
				const wpVersion = '<?php global $wp_version; echo esc_attr( $wp_version ); ?>';
				if (parseFloat(wpVersion) < 6.5) {
					wp.data.dispatch('core/edit-site') ? wp.data.dispatch('core/edit-site').__experimentalSetPreviewDeviceType($device) : wp.data.dispatch('core/edit-post').__experimentalSetPreviewDeviceType($device);
				} else {
					const editorDispatch = wp.data.dispatch('core/editor');
					if (editorDispatch) {
						editorDispatch.setDeviceType($device);
					}
				}
			}

			jQuery(function () {
				sd_block_visibility_init();
			});

			function sd_block_visibility_init() {
				jQuery(document).off('change', '.bs-vc-modal-form').on('change', '.bs-vc-modal-form', function () {
					try {
						aui_conditional_fields('.bs-vc-modal-form');
					} catch (err) {
						console.log(err.message);
					}
				});

				jQuery(document).off('click', '.bs-vc-save').on('click', '.bs-vc-save', function () {
					var $bsvcModal = jQuery(this).closest('.bs-vc-modal'),
						$bsvcForm = $bsvcModal.find('.bs-vc-modal-form'),
						vOutput = jQuery('#bsvc_output', $bsvcForm).val(),
						vOutputN = jQuery('#bsvc_output_n', $bsvcForm).val(), rawValue = '', oVal = {}, oOut = {},
						oOutN = {}, iRule = 0;
					jQuery(this).addClass('disabled');
					jQuery('.bs-vc-modal-form .bs-vc-rule-sets .bs-vc-rule').each(function () {
						vRule = jQuery(this).find('.bsvc_rule').val(), oRule = {};
						if (vRule == 'logged_in' || vRule == 'logged_out' || vRule == 'post_author') {
							oRule.type = vRule;
						} else if (vRule == 'user_roles') {
							oRule.type = vRule;
							if (jQuery(this).find('.bsvc_user_roles:checked').length) {
								var user_roles = jQuery(this).find('.bsvc_user_roles:checked').map(function () {
									return jQuery(this).val();
								}).get();
								if (user_roles && user_roles.length) {
									oRule.user_roles = user_roles.join(",");
								}
							}
						} else if (vRule == 'gd_field') {
							if (jQuery(this).find('.bsvc_gd_field ').val() && jQuery(this).find('.bsvc_gd_field_condition').val()) {
								oRule.type = vRule;
								oRule.field = jQuery(this).find('.bsvc_gd_field ').val();
								oRule.condition = jQuery(this).find('.bsvc_gd_field_condition').val();
								if (oRule.condition != 'is_empty' && oRule.condition != 'is_not_empty') {
									oRule.search = jQuery(this).find('.bsvc_gd_field_search').val();
								}
							}
						} else {
							oRule = jQuery(document).triggerHandler('sd_block_visibility_init', [vRule, oRule, jQuery(this)]);
						}

						if (Object.keys(oRule).length > 0) {
							iRule++;
							oVal['rule' + iRule] = oRule;
						}
					});
					if (vOutput == 'hide') {
						oOut.type = vOutput;
					} else if (vOutput == 'message') {
						if (jQuery('#bsvc_message', $bsvcForm).val()) {
							oOut.type = vOutput;
							oOut.message = jQuery('#bsvc_message', $bsvcForm).val();
							if (jQuery('#bsvc_message_type', $bsvcForm).val()) {
								oOut.message_type = jQuery('#bsvc_message_type', $bsvcForm).val();
							}
						}
					} else if (vOutput == 'page') {
						if (jQuery('#bsvc_page', $bsvcForm).val()) {
							oOut.type = vOutput;
							oOut.page = jQuery('#bsvc_page', $bsvcForm).val();
						}
					} else if (vOutput == 'template_part') {
						if (jQuery('#bsvc_tmpl_part', $bsvcForm).val()) {
							oOut.type = vOutput;
							oOut.template_part = jQuery('#bsvc_tmpl_part', $bsvcForm).val();
						}
					}
					if (Object.keys(oOut).length > 0) {
						oVal.output = oOut;
					}
					if (vOutputN == 'hide') {
						oOutN.type = vOutputN;
					} else if (vOutputN == 'message') {
						if (jQuery('#bsvc_message_n', $bsvcForm).val()) {
							oOutN.type = vOutputN;
							oOutN.message = jQuery('#bsvc_message_n', $bsvcForm).val();
							if (jQuery('#bsvc_message_type_n', $bsvcForm).val()) {
								oOutN.message_type = jQuery('#bsvc_message_type_n', $bsvcForm).val();
							}
						}
					} else if (vOutputN == 'page') {
						if (jQuery('#bsvc_page_n', $bsvcForm).val()) {
							oOutN.type = vOutputN;
							oOutN.page = jQuery('#bsvc_page_n', $bsvcForm).val();
						}
					} else if (vOutputN == 'template_part') {
						if (jQuery('#bsvc_tmpl_part_n', $bsvcForm).val()) {
							oOutN.type = vOutputN;
							oOutN.template_part = jQuery('#bsvc_tmpl_part_n', $bsvcForm).val();
						}
					}
					if (Object.keys(oOutN).length > 0) {
						oVal.outputN = oOutN;
					}
					if (Object.keys(oVal).length > 0) {
						rawValue = JSON.stringify(oVal);
					}
					$bsvcModal.find('[name="bsvc_raw_value"]').val(rawValue).trigger('change');
					$bsvcModal.find('.bs-vc-close').trigger('click');
				});
				jQuery(document).off('click', '.bs-vc-add-rule').on('click', '.bs-vc-add-rule', function () {
					var bsvcTmpl = jQuery('.bs-vc-rule-template').html();
					var c = parseInt(jQuery('.bs-vc-modal-form .bs-vc-rule-sets .bs-vc-rule:last').data('bs-index'));
					if (c > 0) {
						c++;
					} else {
						c = 1;
					}
					bsvcTmpl = bsvcTmpl.replace(/BSVCINDEX/g, c);
					jQuery('.bs-vc-modal-form .bs-vc-rule-sets').append(bsvcTmpl);
					jQuery('.bs-vc-modal-form .bs-vc-rule-sets .bs-vc-rule .bs-vc-sep-wrap').removeClass('d-none');
					jQuery('.bs-vc-modal-form .bs-vc-rule-sets .bs-vc-rule:first .bs-vc-sep-wrap').addClass('d-none');
					jQuery('.bs-vc-modal-form .bs-vc-rule-sets .bs-vc-rule:last').find('select').each(function () {
						if (!jQuery(this).hasClass('no-select2')) {
							jQuery(this).addClass('aui-select2');
						}
					});
					if (!jQuery(this).hasClass('bs-vc-rendering')) {
						if (typeof aui_init_select2 == 'function') {
							aui_init_select2();
						}
						if (typeof aui_conditional_fields == 'function') {
							aui_conditional_fields('.bs-vc-modal-form');
						}
					}
				});
				jQuery(document).off('click', '.bs-vc-remove-rule').on('click', '.bs-vc-remove-rule', function () {
					jQuery(this).closest('.bs-vc-rule').remove();
				});
			}

			function sd_block_visibility_render_fields(oValue) {
				console.log(oValue);
				if (typeof oValue == 'object' && oValue.rule1 && typeof oValue.rule1 == 'object') {
					for (k = 1; k <= Object.keys(oValue).length; k++) {
						if (oValue['rule' + k] && oValue['rule' + k].type) {
							var oRule = oValue['rule' + k];
							jQuery('.bs-vc-modal-form .bs-vc-add-rule').addClass('bs-vc-rendering').trigger('click');
							var elRule = jQuery('.bs-vc-modal-form .bs-vc-rule-sets .bs-vc-rule:last');
							jQuery('select.bsvc_rule', elRule).val(oRule.type);
							if (oRule.type == 'user_roles' && oRule.user_roles) {
								var user_roles = oRule.user_roles;
								if (typeof user_roles == 'string') {
									user_roles = user_roles.split(",");
								}
								if (user_roles.length) {
									jQuery.each(user_roles, function (i, role) {
										elRule.find("input[value='" + role + "']").prop('checked', true);
									});
								}
								jQuery('select.bsvc_user_roles', elRule).val(oRule.user_roles);
							} else if (oRule.type == 'gd_field') {
								if (oRule.field) {
									jQuery('select.bsvc_gd_field', elRule).val(oRule.field);
									if (oRule.condition) {
										jQuery('select.bsvc_gd_field_condition', elRule).val(oRule.condition);
										if (typeof oRule.search != 'undefined' && oRule.condition != 'is_empty' && oRule.condition != 'is_not_empty') {
											jQuery('input.bsvc_gd_field_search', elRule).val(oRule.search);
										}
									}
								}
							} else {
								jQuery(document).trigger('sd_block_visibility_render_fields', [oRule, elRule]);
							}

							jQuery('.bs-vc-modal-form .bs-vc-add-rule').removeClass('bs-vc-rendering');
						}
					}

					if (oValue.output && oValue.output.type) {
						jQuery('.bs-vc-modal-form #bsvc_output').val(oValue.output.type);
						if (oValue.output.type == 'message' && typeof oValue.output.message != 'undefined') {
							jQuery('.bs-vc-modal-form #bsvc_message').val(oValue.output.message);
							if (typeof oValue.output.message_type != 'undefined') {
								jQuery('.bs-vc-modal-form #bsvc_message_type').val(oValue.output.message_type);
							}
						} else if (oValue.output.type == 'page' && typeof oValue.output.page != 'undefined') {
							jQuery('.bs-vc-modal-form #bsvc_page').val(oValue.output.page);
						} else if (oValue.output.type == 'template_part' && typeof oValue.output.template_part != 'undefined') {
							jQuery('.bs-vc-modal-form #bsvc_template_part').val(oValue.output.template_part);
						}
					}

					if (oValue.outputN && oValue.outputN.type) {
						jQuery('.bs-vc-modal-form #bsvc_output_n').val(oValue.outputN.type);
						if (oValue.outputN.type == 'message' && typeof oValue.outputN.message != 'undefined') {
							jQuery('.bs-vc-modal-form #bsvc_message_n').val(oValue.outputN.message);
							if (typeof oValue.outputN.message_type != 'undefined') {
								jQuery('.bs-vc-modal-form #bsvc_message_type_n').val(oValue.outputN.message_type);
							}
						} else if (oValue.outputN.type == 'page' && typeof oValue.outputN.page != 'undefined') {
							jQuery('.bs-vc-modal-form #bsvc_page_n').val(oValue.outputN.page);
						} else if (oValue.outputN.type == 'template_part' && typeof oValue.outputN.template_part != 'undefined') {
							jQuery('.bs-vc-modal-form #bsvc_template_part_n').val(oValue.outputN.template_part);
						}
					}
				}
			}

			/**
			 * Try to auto-recover blocks.
			 */
			function sd_auto_recover_blocks() { return; //@todo we need to re-implement this
				var recursivelyRecoverInvalidBlockList = blocks => {
					const _blocks = [...blocks]
					let recoveryCalled = false
					const recursivelyRecoverBlocks = willRecoverBlocks => {
						willRecoverBlocks.forEach(_block => {
							if (!_block.isValid) {
								recoveryCalled = true
								const newBlock = recoverBlock(_block)
								for (const key in newBlock) {
									_block[key] = newBlock[key]
								}
							}
							if (_block.innerBlocks.length) {
								recursivelyRecoverBlocks(_block.innerBlocks)
							}
						})
					}
					recursivelyRecoverBlocks(_blocks)
					return [_blocks, recoveryCalled]
				}
				var recoverBlock = ({
										name,
										attributes,
										innerBlocks
									}) => wp.blocks.createBlock(name, attributes, innerBlocks);
				var recoverBlocks = blocks => {
					return blocks.map(_block => {
						const block = _block;
						// If the block is a reusable block, recover the Stackable blocks inside it.
						if (_block.name === 'core/block') {
							const {attributes: {ref}} = _block
							const parsedBlocks = wp.blocks.parse(wp.data.select('core').getEntityRecords('postType', 'wp_block', {include: [ref]})?.[0]?.content?.raw) || []
							const [recoveredBlocks, recoveryCalled] = recursivelyRecoverInvalidBlockList(parsedBlocks)
							if (recoveryCalled) {
								console.log('Stackable notice: block ' + block.name + ' (' + block.clientId + ') was auto-recovered, you should not see this after saving your page.');
								return {blocks: recoveredBlocks, isReusable: true, ref}
							}
						} else if (_block.name === 'core/template-part' && _block.attributes && _block.attributes.theme) {
							var tmplPart = wp.data.select('core').getEntityRecord('postType', 'wp_template_part', _block.attributes.theme + '//' + _block.attributes.slug);
							var tmplPartBlocks = block.innerBlocks && block.innerBlocks.length ? block.innerBlocks : wp.blocks.parse(tmplPart?.content?.raw) || [];
							if (tmplPartBlocks && tmplPartBlocks.length && tmplPartBlocks.some(block => !block.isValid)) {
								block.innerBlocks = tmplPartBlocks;
								block.tmplPartId = _block.attributes.theme + '//' + _block.attributes.slug;
							}
						}
						if (block.innerBlocks && block.innerBlocks.length) {
							if (block.tmplPartId) {
								console.log('Template part ' + block.tmplPartId + ' block ' + block.name + ' (' + block.clientId + ') starts');
							}
							const newInnerBlocks = recoverBlocks(block.innerBlocks)
							if (newInnerBlocks.some(block => block.recovered)) {
								block.innerBlocks = newInnerBlocks
								block.replacedClientId = block.clientId
								block.recovered = true
							}
							if (block.tmplPartId) {
								console.log('Template part ' + block.tmplPartId + ' block ' + block.name + ' (' + block.clientId + ') ends');
							}
						}
						if (!block.isValid) {
							const newBlock = recoverBlock(block)
							newBlock.replacedClientId = block.clientId
							newBlock.recovered = true
							console.log('Stackable notice: block ' + block.name + ' (' + block.clientId + ') was auto-recovered, you should not see this after saving your page.');
							return newBlock
						}
						return block
					})
				}
				// Recover all the blocks that we can find.
				var sdBlockEditor = wp.data.select('core/block-editor');
				var mainBlocks = sdBlockEditor ? recoverBlocks(sdBlockEditor.getBlocks()) : null;
				// Replace the recovered blocks with the new ones.
				if (mainBlocks) {
					mainBlocks.forEach(block => {
						if (block.isReusable && block.ref) {
							// Update the reusable blocks.
							wp.data.dispatch('core').editEntityRecord('postType', 'wp_block', block.ref, {
								content: wp.blocks.serialize(block.blocks)
							}).then(() => {
								// But don't save them, let the user do the saving themselves. Our goal is to get rid of the block error visually.
							})
						}
						if (block.recovered && block.replacedClientId) {
							wp.data.dispatch('core/block-editor').replaceBlock(block.replacedClientId, block)
						}
					})
				}
			}

			/**
			 * Try to auto-recover OUR blocks if traditional way fails.
			 */
			function sd_auto_recover_blocks_fallback(editTmpl) {return; //@todo we need to find a better way to do this
				console.log('sd_auto_recover_blocks_fallback()');
				var $bsRecoverBtn = jQuery(".edit-site-visual-editor__editor-canvas").contents().find('div[class*=" wp-block-blockstrap-"] .block-editor-warning__actions  .block-editor-warning__action .components-button.is-primary').not(":contains('Keep as HTML')");
				if ($bsRecoverBtn.length) {
					if (jQuery('.edit-site-layout.is-full-canvas').length || jQuery('.edit-site-layout.is-edit-mode').length) {
						$bsRecoverBtn.removeAttr('disabled').trigger('click');
					}
				}
			}

			<?php if( ! isset( $_REQUEST['sd-block-recover-debug'] ) ){ ?>
			// Wait will window is loaded before calling.
			window.onload = function () {
				sd_auto_recover_blocks();
				// fire a second time incase of load delays.
				setTimeout(function () {
					sd_auto_recover_blocks();
				}, 5000);

				setTimeout(function () {
					sd_auto_recover_blocks_fallback();
				}, 6000);

				setTimeout(function () {
					sd_auto_recover_blocks_fallback();
				}, 10000);

				setTimeout(function () {
					sd_auto_recover_blocks_fallback();
				}, 15000);

				setTimeout(function () {
					sd_auto_recover_blocks_fallback();
				}, 20000);

				setTimeout(function () {
					sd_auto_recover_blocks_fallback();
				}, 30000);

				setTimeout(function () {
					sd_auto_recover_blocks_fallback();
				}, 60000);

				jQuery('.edit-site-page-panels__edit-template-button, .edit-site-visual-editor__editor-canvas').on('click', function () {
					setTimeout(function () {
						sd_auto_recover_blocks_fallback(true);
						jQuery('.edit-site-page-panels__edit-template-button, .edit-site-visual-editor__editor-canvas').addClass('bs-edit-tmpl-clicked');
					}, 100);
				});
			};
			<?php } ?>

			// fire when URL changes also.
			let lastUrl = location.href;
			new MutationObserver(() => {
				const url = location.href;
				if (url !== lastUrl) {
					lastUrl = url;
					sd_auto_recover_blocks();
					// fire a second time incase of load delays.
					setTimeout(function () {
						sd_auto_recover_blocks();
						sd_auto_recover_blocks_fallback();
					}, 2000);

					setTimeout(function () {
						sd_auto_recover_blocks_fallback();
					}, 10000);

					setTimeout(function () {
						sd_auto_recover_blocks_fallback();
					}, 15000);

					setTimeout(function () {
						sd_auto_recover_blocks_fallback();
					}, 20000);

				}
			}).observe(document, {
				subtree: true,
				childList: true
			});


			/**
			 *
			 * @param $args
			 * @returns {*|{}}
			 */
			function sd_build_aui_styles($args) {

				$styles = {};
				// background color
				if ($args['bg'] !== undefined && $args['bg'] !== '') {
					if ($args['bg'] == 'custom-color') {
						$styles['background-color'] = $args['bg_color'];
					} else if ($args['bg'] == 'custom-gradient') {
						$styles['background-image'] = $args['bg_gradient'];

						// use background on text
						if ($args['bg_on_text'] !== undefined && $args['bg_on_text']) {
							$styles['backgroundClip'] = "text";
							$styles['WebkitBackgroundClip'] = "text";
							$styles['text-fill-color'] = "transparent";
							$styles['WebkitTextFillColor'] = "transparent";
						}
					}

				}

				let $bg_image = $args['bg_image'] !== undefined && $args['bg_image'] !== '' ? $args['bg_image'] : '';

				// maybe use featured image.
				if ($args['bg_image_use_featured'] !== undefined && $args['bg_image_use_featured']) {
					$bg_image = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiID8+CjxzdmcgYmFzZVByb2ZpbGU9InRpbnkiIGhlaWdodD0iNDAwIiB2ZXJzaW9uPSIxLjIiIHdpZHRoPSI0MDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6ZXY9Imh0dHA6Ly93d3cudzMub3JnLzIwMDEveG1sLWV2ZW50cyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiPjxkZWZzIC8+PHJlY3QgZmlsbD0iI2QzZDNkMyIgaGVpZ2h0PSI0MDAiIHdpZHRoPSI0MDAiIHg9IjAiIHk9IjAiIC8+PGxpbmUgc3Ryb2tlPSJ3aGl0ZSIgc3Ryb2tlLXdpZHRoPSIxMCIgeDE9IjAiIHgyPSI0MDAiIHkxPSIwIiB5Mj0iNDAwIiAvPjxsaW5lIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iMTAiIHgxPSIwIiB4Mj0iNDAwIiB5MT0iNDAwIiB5Mj0iMCIgLz48cmVjdCBmaWxsPSIjZDNkM2QzIiBoZWlnaHQ9IjUwIiB3aWR0aD0iMjE4LjAiIHg9IjkxLjAiIHk9IjE3NS4wIiAvPjx0ZXh0IGZpbGw9IndoaXRlIiBmb250LXNpemU9IjMwIiBmb250LXdlaWdodD0iYm9sZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgeD0iMjAwLjAiIHk9IjIwNy41Ij5QTEFDRUhPTERFUjwvdGV4dD48L3N2Zz4=';
				}

				if ($bg_image !== undefined && $bg_image !== '') {
					var hasImage = true
					if ($styles['background-color'] !== undefined && $args['bg'] == 'custom-color') {
						$styles['background-image'] = "url(" + $bg_image + ")";
						$styles['background-blend-mode'] = "overlay";
					} else if ($styles['background-image'] !== undefined && $args['bg'] == 'custom-gradient') {
						$styles['background-image'] += ",url(" + $bg_image + ")";
					} else if ($args['bg'] !== undefined && $args['bg'] != '' && $args['bg'] != 'transparent') {
						// do nothing as we already have a preset
						hasImage = false;
					} else {
						$styles['background-image'] = "url(" + $bg_image + ")";
					}

					if (hasImage) {
						$styles['background-size'] = "cover";

						if ($args['bg_image_fixed'] !== undefined && $args['bg_image_fixed']) {
							$styles['background-attachment'] = "fixed";
						}
					}

					if (hasImage && $args['bg_image_xy'].x !== undefined && $args['bg_image_xy'].x >= 0) {
						$styles['background-position'] = ($args['bg_image_xy'].x * 100) + "% " + ($args['bg_image_xy'].y * 100) + "%";
					}
				}


				// sticky offset top
				if ($args['sticky_offset_top'] !== undefined && $args['sticky_offset_top'] !== '') {
					$styles['top'] = $args['sticky_offset_top'];
				}

				// sticky offset bottom
				if ($args['sticky_offset_bottom'] !== undefined && $args['sticky_offset_bottom'] !== '') {
					$styles['bottom'] = $args['sticky_offset_bottom'];
				}

				// font size
				if ($args['font_size'] === undefined || $args['font_size'] === 'custom') {
					if ($args['font_size_custom'] !== undefined && $args['font_size_custom'] !== '') {
						$styles['fontSize'] = $args['font_size_custom'] + "rem";
					}
				}

				// font color
				if ($args['text_color'] === undefined || $args['text_color'] === 'custom') {
					if ($args['text_color_custom'] !== undefined && $args['text_color_custom'] !== '') {
						$styles['color'] = $args['text_color_custom'];
					}
				}

				// font line height
				if ($args['font_line_height'] !== undefined && $args['font_line_height'] !== '') {
					$styles['lineHeight'] = $args['font_line_height'];
				}

				// max height
				if ($args['max_height'] !== undefined && $args['max_height'] !== '') {
					$styles['maxHeight'] = $args['max_height'];
				}

				return $styles;

			}

			function sd_build_aui_class($args) {

				$classes = [];

				<?php
				if($aui_bs5){
				?>
				$aui_bs5 = true;
				$p_ml = 'ms-';
				$p_mr = 'me-';

				$p_pl = 'ps-';
				$p_pr = 'pe-';
				<?php
				}else{
				?>
				$aui_bs5 = false;
				$p_ml = 'ml-';
				$p_mr = 'mr-';

				$p_pl = 'pl-';
				$p_pr = 'pr-';
				<?php
				}
				?>

				// margins
				if ($args['mt'] !== undefined && $args['mt'] !== '') {
					$classes.push("mt-" + $args['mt']);
					$mt = $args['mt'];
				} else {
					$mt = null;
				}
				if ($args['mr'] !== undefined && $args['mr'] !== '') {
					$classes.push($p_mr + $args['mr']);
					$mr = $args['mr'];
				} else {
					$mr = null;
				}
				if ($args['mb'] !== undefined && $args['mb'] !== '') {
					$classes.push("mb-" + $args['mb']);
					$mb = $args['mb'];
				} else {
					$mb = null;
				}
				if ($args['ml'] !== undefined && $args['ml'] !== '') {
					$classes.push($p_ml + $args['ml']);
					$ml = $args['ml'];
				} else {
					$ml = null;
				}

				// margins tablet
				if ($args['mt_md'] !== undefined && $args['mt_md'] !== '') {
					$classes.push("mt-md-" + $args['mt_md']);
					$mt_md = $args['mt_md'];
				} else {
					$mt_md = null;
				}
				if ($args['mr_md'] !== undefined && $args['mr_md'] !== '') {
					$classes.push($p_mr + "md-" + $args['mr_md']);
					$mt_md = $args['mr_md'];
				} else {
					$mr_md = null;
				}
				if ($args['mb_md'] !== undefined && $args['mb_md'] !== '') {
					$classes.push("mb-md-" + $args['mb_md']);
					$mt_md = $args['mb_md'];
				} else {
					$mb_md = null;
				}
				if ($args['ml_md'] !== undefined && $args['ml_md'] !== '') {
					$classes.push($p_ml + "md-" + $args['ml_md']);
					$mt_md = $args['ml_md'];
				} else {
					$ml_md = null;
				}

				// margins desktop
				if ($args['mt_lg'] !== undefined && $args['mt_lg'] !== '') {
					if ($mt == null && $mt_md == null) {
						$classes.push("mt-" + $args['mt_lg']);
					} else {
						$classes.push("mt-lg-" + $args['mt_lg']);
					}
				}
				if ($args['mr_lg'] !== undefined && $args['mr_lg'] !== '') {
					if ($mr == null && $mr_md == null) {
						$classes.push($p_mr + $args['mr_lg']);
					} else {
						$classes.push($p_mr + "lg-" + $args['mr_lg']);
					}
				}
				if ($args['mb_lg'] !== undefined && $args['mb_lg'] !== '') {
					if ($mb == null && $mb_md == null) {
						$classes.push("mb-" + $args['mb_lg']);
					} else {
						$classes.push("mb-lg-" + $args['mb_lg']);
					}
				}
				if ($args['ml_lg'] !== undefined && $args['ml_lg'] !== '') {
					if ($ml == null && $ml_md == null) {
						$classes.push($p_ml + $args['ml_lg']);
					} else {
						$classes.push($p_ml + "lg-" + $args['ml_lg']);
					}
				}

				// padding
				if ($args['pt'] !== undefined && $args['pt'] !== '') {
					$classes.push("pt-" + $args['pt']);
					$pt = $args['pt'];
				} else {
					$pt = null;
				}
				if ($args['pr'] !== undefined && $args['pr'] !== '') {
					$classes.push($p_pr + $args['pr']);
					$pr = $args['pt'];
				} else {
					$pr = null;
				}
				if ($args['pb'] !== undefined && $args['pb'] !== '') {
					$classes.push("pb-" + $args['pb']);
					$pb = $args['pt'];
				} else {
					$pb = null;
				}
				if ($args['pl'] !== undefined && $args['pl'] !== '') {
					$classes.push($p_pl + $args['pl']);
					$pl = $args['pt'];
				} else {
					$pl = null;
				}

				// padding tablet
				if ($args['pt_md'] !== undefined && $args['pt_md'] !== '') {
					$classes.push("pt-md-" + $args['pt_md']);
					$pt_md = $args['pt_md'];
				} else {
					$pt_md = null;
				}
				if ($args['pr_md'] !== undefined && $args['pr_md'] !== '') {
					$classes.push($p_pr + "md-" + $args['pr_md']);
					$pr_md = $args['pt_md'];
				} else {
					$pr_md = null;
				}
				if ($args['pb_md'] !== undefined && $args['pb_md'] !== '') {
					$classes.push("pb-md-" + $args['pb_md']);
					$pb_md = $args['pt_md'];
				} else {
					$pb_md = null;
				}
				if ($args['pl_md'] !== undefined && $args['pl_md'] !== '') {
					$classes.push($p_pl + "md-" + $args['pl_md']);
					$pl_md = $args['pt_md'];
				} else {
					$pl_md = null;
				}

				// padding desktop
				if ($args['pt_lg'] !== undefined && $args['pt_lg'] !== '') {
					if ($pt == null && $pt_md == null) {
						$classes.push("pt-" + $args['pt_lg']);
					} else {
						$classes.push("pt-lg-" + $args['pt_lg']);
					}
				}
				if ($args['pr_lg'] !== undefined && $args['pr_lg'] !== '') {
					if ($pr == null && $pr_md == null) {
						$classes.push($p_pr + $args['pr_lg']);
					} else {
						$classes.push($p_pr + "lg-" + $args['pr_lg']);
					}
				}
				if ($args['pb_lg'] !== undefined && $args['pb_lg'] !== '') {
					if ($pb == null && $pb_md == null) {
						$classes.push("pb-" + $args['pb_lg']);
					} else {
						$classes.push("pb-lg-" + $args['pb_lg']);
					}
				}
				if ($args['pl_lg'] !== undefined && $args['pl_lg'] !== '') {
					if ($pl == null && $pl_md == null) {
						$classes.push($p_pl + $args['pl_lg']);
					} else {
						$classes.push($p_pl + "lg-" + $args['pl_lg']);
					}
				}

				// row cols, mobile, tablet, desktop
				if ($args['row_cols'] !== undefined && $args['row_cols'] !== '') {
					$classes.push("row-cols-" + $args['row_cols']);
					$row_cols = $args['row_cols'];
				} else {
					$row_cols = null;
				}
				if ($args['row_cols_md'] !== undefined && $args['row_cols_md'] !== '') {
					$classes.push("row-cols-md-" + $args['row_cols_md']);
					$row_cols_md = $args['row_cols_md'];
				} else {
					$row_cols_md = null;
				}
				if ($args['row_cols_lg'] !== undefined && $args['row_cols_lg'] !== '') {
					if ($row_cols == null && $row_cols_md == null) {
						$classes.push("row-cols-" + $args['row_cols_lg']);
					} else {
						$classes.push("row-cols-lg-" + $args['row_cols_lg']);
					}
				}

				// columns , mobile, tablet, desktop
				if ($args['col'] !== undefined && $args['col'] !== '') {
					$classes.push("col-" + $args['col']);
					$col = $args['col'];
				} else {
					$col = null;
				}
				if ($args['col_md'] !== undefined && $args['col_md'] !== '') {
					$classes.push("col-md-" + $args['col_md']);
					$col_md = $args['col_md'];
				} else {
					$col_md = null;
				}
				if ($args['col_lg'] !== undefined && $args['col_lg'] !== '') {
					if ($col == null && $col_md == null) {
						$classes.push("col-" + $args['col_lg']);
					} else {
						$classes.push("col-lg-" + $args['col_lg']);
					}
				}


				// border
				if ($args['border'] === undefined || $args['border'] == '') {
				} else if ($args['border'] !== undefined && ($args['border'] == 'none' || $args['border'] === '0')) {
					$classes.push("border-0");
				} else if ($args['border'] !== undefined) {
					if ($aui_bs5 && $args['border_type'] !== undefined) {
						$args['border_type'] = $args['border_type'].replace('-left', '-start').replace('-right', '-end');
					}
					$border_class = 'border';
					if ($args['border_type'] !== undefined && !$args['border_type'].includes('-0')) {
						$border_class = '';
					}
					$classes.push($border_class + " border-" + $args['border']);
				}

				// border radius type
				//  if ( $args['rounded'] !== undefined && $args['rounded'] !== '' ) { $classes.push($args['rounded']); }

				// border radius size
				if ($args['rounded_size'] !== undefined && ($args['rounded_size'] === 'sm' || $args['rounded_size'] === 'lg')) {
					if ($args['rounded_size'] !== undefined && $args['rounded_size'] !== '') {
						$classes.push("rounded-" + $args['rounded_size']);
						// if we set a size then we need to remove "rounded" if set
						var index = $classes.indexOf("rounded");
						if (index !== -1) {
							$classes.splice(index, 1);
						}
					}
				} else {
					// rounded_size , mobile, tablet, desktop
					if ($args['rounded_size'] !== undefined && $args['rounded_size'] !== '') {
						$classes.push("rounded-" + $args['rounded_size']);
						$rounded_size = $args['rounded_size'];
					} else {
						$rounded_size = null;
					}
					if ($args['rounded_size_md'] !== undefined && $args['rounded_size_md'] !== '') {
						$classes.push("rounded-md-" + $args['rounded_size_md']);
						$rounded_size_md = $args['rounded_size_md'];
					} else {
						$rounded_size_md = null;
					}
					if ($args['rounded_size_lg'] !== undefined && $args['rounded_size_lg'] !== '') {
						if ($rounded_size == null && $rounded_size_md == null) {
							$classes.push("rounded-" + $args['rounded_size_lg']);
						} else {
							$classes.push("rounded-lg-" + $args['rounded_size_lg']);
						}
					}
				}


				// shadow
				// if ( $args['shadow'] !== undefined && $args['shadow'] !== '' ) { $classes.push($args['shadow']); }

				// background
				if ($args['bg'] !== undefined && $args['bg'] !== '') {
					$classes.push("bg-" + $args['bg']);
				}

				// background image fixed bg_image_fixed
				if ($args['bg_image_fixed'] !== undefined && $args['bg_image_fixed'] !== '') {
					$classes.push("bg-image-fixed");
				}

				// text_color
				if ($args['text_color'] !== undefined && $args['text_color'] !== '') {
					$classes.push("text-" + $args['text_color']);
				}

				// text_align
				if ($args['text_justify'] !== undefined && $args['text_justify']) {
					$classes.push('text-justify');
				} else {
					if ($args['text_align'] !== undefined && $args['text_align'] !== '') {
						if ($aui_bs5) {
							$args['text_align'] = $args['text_align'].replace('-left', '-start').replace('-right', '-end');
						}
						$classes.push($args['text_align']);
						$text_align = $args['text_align'];
					} else {
						$text_align = null;
					}
					if ($args['text_align_md'] !== undefined && $args['text_align_md'] !== '') {
						if ($aui_bs5) {
							$args['text_align_md'] = $args['text_align_md'].replace('-left', '-start').replace('-right', '-end');
						}
						$classes.push($args['text_align_md']);
						$text_align_md = $args['text_align_md'];
					} else {
						$text_align_md = null;
					}
					if ($args['text_align_lg'] !== undefined && $args['text_align_lg'] !== '') {
						if ($aui_bs5) {
							$args['text_align_lg'] = $args['text_align_lg'].replace('-left', '-start').replace('-right', '-end');
						}
						if ($text_align == null && $text_align_md == null) {
							$classes.push($args['text_align_lg'].replace("-lg", ""));
						} else {
							$classes.push($args['text_align_lg']);
						}
					}
				}

				// display
				if ($args['display'] !== undefined && $args['display'] !== '') {
					$classes.push($args['display']);
					$display = $args['display'];
				} else {
					$display = null;
				}
				if ($args['display_md'] !== undefined && $args['display_md'] !== '') {
					$classes.push($args['display_md']);
					$display_md = $args['display_md'];
				} else {
					$display_md = null;
				}
				if ($args['display_lg'] !== undefined && $args['display_lg'] !== '') {
					if ($display == null && $display_md == null) {
						$classes.push($args['display_lg'].replace("-lg", ""));
					} else {
						$classes.push($args['display_lg']);
					}
				}

				// bgtus - background transparent until scroll
				if ($args['bgtus'] !== undefined && $args['bgtus']) {
					$classes.push("bg-transparent-until-scroll");
				}

				// cscos - change color scheme on scroll
				if ($args['bgtus'] !== undefined && $args['bgtus'] && $args['cscos'] !== undefined && $args['cscos']) {
					$classes.push("color-scheme-flip-on-scroll");
				}

				// hover animations
				if ($args['hover_animations'] !== undefined && $args['hover_animations']) {
					$classes.push($args['hover_animations'].toString().replace(',', ' '));
				}

				// hover icon animations
				if ($args['hover_icon_animation'] !== undefined && $args['hover_icon_animation'] !== '') {
					$classes.push($args['hover_icon_animation']);
				}

				// absolute_position
				if ($args['absolute_position'] !== undefined) {
					if ('top-left' === $args['absolute_position']) {
						$classes.push('start-0 top-0');
					} else if ('top-center' === $args['absolute_position']) {
						$classes.push('start-50 top-0 translate-middle');
					} else if ('top-right' === $args['absolute_position']) {
						$classes.push('end-0 top-0');
					} else if ('center-left' === $args['absolute_position']) {
						$classes.push('start-0 bottom-50');
					} else if ('center' === $args['absolute_position']) {
						$classes.push('start-50 top-50 translate-middle');
					} else if ('center-right' === $args['absolute_position']) {
						$classes.push('end-0 top-50');
					} else if ('bottom-left' === $args['absolute_position']) {
						$classes.push('start-0 bottom-0');
					} else if ('bottom-center' === $args['absolute_position']) {
						$classes.push('start-50 bottom-0 translate-middle');
					} else if ('bottom-right' === $args['absolute_position']) {
						$classes.push('end-0 bottom-0');
					}
				}

				// build classes from build keys
				$build_keys = sd_get_class_build_keys();
				if ($build_keys.length) {
					$build_keys.forEach($key => {

						if ($key.endsWith("-MTD")) {

							$k = $key.replace("-MTD", "");

							// Mobile, Tablet, Desktop
							if ($args[$k] !== undefined && $args[$k] !== '') {
								$classes.push($args[$k]);
								$v = $args[$k];
							} else {
								$v = null;
							}
							if ($args[$k + '_md'] !== undefined && $args[$k + '_md'] !== '') {
								$classes.push($args[$k + '_md']);
								$v_md = $args[$k + '_md'];
							} else {
								$v_md = null;
							}
							if ($args[$k + '_lg'] !== undefined && $args[$k + '_lg'] !== '') {
								if ($v == null && $v_md == null) {
									$classes.push($args[$k + '_lg'].replace('-lg', ''));
								} else {
									$classes.push($args[$k + '_lg']);
								}
							}

						} else {
							if ($key == 'font_size' && ($args[$key] == 'custom' || $args[$key] === '0')) {
								return;
							}
							if ($args[$key] !== undefined && $args[$key] !== '') {
								$classes.push($args[$key]);
							}
						}

					});
				}

				return $classes.join(" ");
			}

			function sd_get_class_build_keys() {
				return <?php echo json_encode( sd_get_class_build_keys() );?>;
			}

			<?php


			//}

			?>
	</script>
	<?php
	return ob_get_clean();
}
//echo sd_get_block_editor_global_js();
wp_add_inline_script( 'super-duper-universal-block-editor', sd_get_block_editor_global_js() );
//wp_add_inline_script( 'wp-block-directory', sd_get_block_editor_global_js() );
