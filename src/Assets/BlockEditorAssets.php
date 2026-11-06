<?php
/**
 * WP Super Duper block editor assets.
 *
 * Generates the universal JavaScript and inline styles shared across all
 * Super Duper blocks in the Gutenberg editor: the visibility-conditions modal,
 * the block auto-recovery/helper JS, and the editor label styling.
 *
 * @package AyeCode\SuperDuper\Assets
 */

namespace AyeCode\SuperDuper\Assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides the inline scripts and styles for the block editor screen.
 */
class BlockEditorAssets {

	/**
	 * Returns the inline JavaScript that tells the editor which AyeCode plugins are
	 * inactive or not installed, so a core/missing block from one of them can show
	 * what it needs instead of the generic unsupported block message.
	 *
	 * Active plugins are left out: a block that is still missing while its plugin is
	 * active belongs to something else, and the core placeholder is the right one.
	 * Action links are only included for users who can act on them, and are built with
	 * the same nonce the Plugins screen uses so activation works from a plain link.
	 *
	 * @return string
	 */
	public static function missing_blocks_data() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed    = get_plugins();
		$can_activate = current_user_can( 'activate_plugins' );
		$can_install  = current_user_can( 'install_plugins' );
		$plugins      = array();

		foreach ( \AyeCode\SuperDuper\Utils::missing_block_plugins() as $file => $plugin ) {
			// A plugin installed from a zip can sit in a renamed directory; match on the
			// main file name too so it still counts as installed and activates correctly.
			$installed_file = isset( $installed[ $file ] ) ? $file : '';
			if ( '' === $installed_file ) {
				foreach ( array_keys( $installed ) as $candidate ) {
					if ( basename( $candidate ) === basename( $file ) ) {
						$installed_file = $candidate;
						break;
					}
				}
			}

			if ( '' !== $installed_file && \AyeCode\SuperDuper\Utils::is_plugin_active( $installed_file ) ) {
				continue;
			}

			$status = '' !== $installed_file ? 'inactive' : 'missing';
			$action = '';
			if ( 'inactive' === $status && $can_activate ) {
				$action = add_query_arg(
					array(
						'action'   => 'activate',
						'plugin'   => rawurlencode( $installed_file ),
						'_wpnonce' => wp_create_nonce( 'activate-plugin_' . $installed_file ),
					),
					self_admin_url( 'plugins.php' )
				);
			} elseif ( 'missing' === $status && $can_install ) {
				$action = ! empty( $plugin['slug'] )
					? self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . rawurlencode( $plugin['slug'] ) )
					: (string) $plugin['url'];
			}

			$plugins[ $file ] = array(
				'name'       => (string) $plugin['name'],
				'status'     => $status,
				'action'     => $action,
				'namespaces' => array_values( (array) $plugin['namespaces'] ),
				'prefixes'   => array_values( (array) $plugin['prefixes'] ),
				'blocks'     => (array) $plugin['blocks'],
			);
		}

		$data = array(
			'plugins' => $plugins,
			'i18n'    => array(
				/* translators: 1: block title, 2: plugin name. */
				'needs'    => __( '%1$s needs %2$s.', 'ayecode-connect' ),
				/* translators: %s: plugin name. */
				'returns'  => __( 'It will show here again once %s is active.', 'ayecode-connect' ),
				'reload'   => __( 'Activation opens in a new tab so unsaved changes here are kept. Reload the editor afterwards.', 'ayecode-connect' ),
				/* translators: %s: plugin name. */
				'activate' => __( 'Activate %s', 'ayecode-connect' ),
				/* translators: %s: plugin name. */
				'install'  => __( 'Install %s', 'ayecode-connect' ),
				/* translators: %s: plugin name. */
				'get'      => __( 'Get %s', 'ayecode-connect' ),
				'remove'   => __( 'Remove block', 'ayecode-connect' ),
			),
		);

		return 'window.sdMissingBlockPlugins = ' . wp_json_encode( $data ) . ';';
	}

	/**
	 * Returns the JavaScript for the VisibilityConditionsModal React component.
	 *
	 * @return string
	 */
	public static function visibility_conditions_script() {
    // --- START: Data Configuration ---

    // Rule Options. Built from the same list the frontend validates against, so
    // rule types added with the sd_visibility_rules_options filter can be picked.
    $rule_options = [
            ['label' => 'Select Rule...', 'value' => ''],
    ];
    foreach ( \AyeCode\SuperDuper\Utils::visibility_rules_options() as $rule_key => $rule_label ) {
        $rule_options[] = [
                'label' => $rule_label,
                'value' => $rule_key,
        ];
    }

    // Action Options
    $output_options = [
            ['label' => 'Show Block', 'value' => 'show'],
            ['label' => 'Hide Block', 'value' => 'hide'],
            ['label' => 'Show Custom Message', 'value' => 'message'],
            ['label' => 'Show Page Content', 'value' => 'page'],
            ['label' => 'Show Template Part', 'value' => 'template_part'],
    ];

    // GD Field conditions
    $gd_condition_options = [
            ['label' => 'Select Condition...', 'value' => ''],
            ['label' => 'is empty', 'value' => 'is_empty'],
            ['label' => 'is not empty', 'value' => 'is_not_empty'],
            ['label' => 'is equal', 'value' => 'is_equal'],
            ['label' => 'is not equal', 'value' => 'is_not_equal'],
            ['label' => 'is greater than', 'value' => 'is_greater_than'],
            ['label' => 'is less than', 'value' => 'is_less_than'],
            ['label' => 'contains', 'value' => 'is_contains'],
            ['label' => 'does not contain', 'value' => 'is_not_contains'],
    ];

    // Message type options
    $message_type_options = [
            ['label' => 'Default (none)', 'value' => ''],
            ['label' => 'Success', 'value' => 'success'],
            ['label' => 'Danger', 'value' => 'danger'],
            ['label' => 'Warning', 'value' => 'warning'],
            ['label' => 'Info', 'value' => 'info'],
    ];

    // Get user roles via PHP for reliability
    $user_roles_options = [];
    if (function_exists('wp_roles')) {
        $roles = wp_roles()->get_names();
        foreach ($roles as $role_key => $role_name) {
            $user_roles_options[] = [
                    'key' => $role_key,
                    'label' => esc_html($role_name),
            ];
        }
    }

    // Plugin Active rule conditions
    $plugin_condition_options = [
            ['label' => 'is active', 'value' => 'is_active'],
            ['label' => 'is not active', 'value' => 'is_not_active'],
    ];

    // Installed plugins for the Plugin Active rule. Must-use plugins are keyed under
    // mu-plugins/ so the evaluator can tell them apart from regular plugins.
    $plugin_options = [];
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    foreach ( get_plugins() as $plugin_file => $plugin_data ) {
        $plugin_options[] = [
                'label'  => wp_strip_all_tags( $plugin_data['Name'] ),
                'value'  => $plugin_file,
                'active' => \AyeCode\SuperDuper\Utils::is_plugin_active( $plugin_file ),
        ];
    }
    foreach ( get_mu_plugins() as $plugin_file => $plugin_data ) {
        $plugin_options[] = [
                'label'  => wp_strip_all_tags( $plugin_data['Name'] ),
                'value'  => 'mu-plugins/' . $plugin_file,
                'active' => true,
        ];
    }
    usort( $plugin_options, fn( $a, $b ) => strcasecmp( $a['label'], $b['label'] ) );

    // --- END: Data Configuration ---


    ob_start();
    ?>
    <script>
        (function(wp) {
            if (!wp || !wp.element) return;

            const { element, apiFetch, components } = wp;
            const { createElement: el, useState, useEffect, Fragment, createPortal } = element;
            const { ComboboxControl } = components || {};

            // Cache variable outside the component to persist across re-mounts.
            let apiOptionsCache = null;

            const ruleOptions = <?php echo wp_json_encode($rule_options); ?>;

            // A saved rule type that is no longer offered (a filter-added rule whose plugin is
            // off, or gd_field without GeoDirectory) is kept as an option so the select does not
            // show blank and the rule survives a resave.
            const getRuleOptions = (type) => {
                if (!type || ruleOptions.some(o => o.value === type)) return ruleOptions;
                return [...ruleOptions, { label: type, value: type }];
            };

            // Plugin Active rule. The combobox lists installed plugins and ends with a custom
            // constant entry for code the plugin list cannot see (bundled packages, theme code,
            // feature flags). A rule saved for a plugin not installed here keeps its raw path.
            const CUSTOM_CONSTANT = '__constant__';
            const pluginOptions = <?php echo wp_json_encode($plugin_options); ?>.map(p => ({
                value: p.value,
                label: p.active ? p.label : `${p.label} (inactive)`,
            }));
            const getPluginOptions = (rule) => {
                const options = [...pluginOptions];
                if (rule.plugin && !options.some(o => o.value === rule.plugin)) {
                    options.push({ value: rule.plugin, label: rule.plugin });
                }
                options.push({ value: CUSTOM_CONSTANT, label: 'Custom constant...' });
                return options;
            };
            const isConstantRule = (rule) => typeof rule.constant === 'string';

            const VisibilityConditionsModal = (props) => {
                const { isOpen, onClose, value, onSave } = props;

                const getInitialState = () => {
                    try {
                        const parsed = JSON.parse(value) || {};
                        const rules = Object.keys(parsed).filter(k => k.startsWith('rule')).map(k => ({ ...parsed[k], id: Math.random() }));
                        return {
                            rules: rules.length ? rules : [{ type: '', id: Math.random() }],
                            output: parsed.output || { type: 'show' },
                            outputN: parsed.outputN || { type: 'show' },
                        };
                    } catch (e) {
                        return { rules: [{ type: '', id: Math.random() }], output: { type: 'show' }, outputN: { type: 'show' } };
                    }
                };

                const [rules, setRules] = useState(() => getInitialState().rules);
                const [output, setOutput] = useState(() => getInitialState().output);
                const [outputN, setOutputN] = useState(() => getInitialState().outputN);

                // State for dynamically fetched options
                const [pageOptions, setPageOptions] = useState([{ label: 'Loading...', value: '' }]);
                const [templatePartOptions, setTemplatePartOptions] = useState([{ label: 'Loading...', value: '' }]);
                const [gdFieldOptions, setGdFieldOptions] = useState([{ label: 'Loading...', value: '' }]);

                useEffect(() => {
                    // Only run if the modal is open
                    if (!isOpen) return;

                    if (apiOptionsCache) {
                        setPageOptions(apiOptionsCache.pageOptions);
                        setTemplatePartOptions(apiOptionsCache.templatePartOptions);
                        setGdFieldOptions(apiOptionsCache.gdFieldOptions);
                        return;
                    }

                    if (!apiFetch) {
                        console.error('wp.apiFetch is not available.');
                        return;
                    }

                    const fetchPages = apiFetch({ path: '/wp/v2/pages?per_page=100&orderby=title&order=asc&_fields=id,title' });
                    const fetchTemplateParts = apiFetch({ path: '/wp/v2/template-parts?per_page=100&orderby=title&order=asc&_fields=slug,title' });
                    const fetchGdFields = apiFetch({ path: '/geodir/v3/fields?per_page=100' });

                    Promise.all([fetchPages, fetchTemplateParts, fetchGdFields])
                        .then(([pages, templateParts, gdFields]) => {
                            const formattedPages = [{ label: 'Select Page...', value: '' }, ...pages.map(p => ({ label: `${p.title.rendered} (#${p.id})`, value: p.id.toString() }))];
                            const formattedTemplateParts = [{ label: 'Select Template Part...', value: '' }, ...templateParts.map(p => ({ label: p.title.rendered, value: p.slug }))];

                            const uniqueGdFields = [...new Map(gdFields.map(f => [f.field_type_key, f])).values()];
                            const formattedGdFields = [{ label: 'Select Field...', value: '' }, ...uniqueGdFields.map(f => ({ label: `${f.admin_title} (${f.field_type_key})`, value: f.field_type_key }))];

                            apiOptionsCache = {
                                pageOptions: formattedPages,
                                templatePartOptions: formattedTemplateParts,
                                gdFieldOptions: formattedGdFields,
                            };

                            setPageOptions(apiOptionsCache.pageOptions);
                            setTemplatePartOptions(apiOptionsCache.templatePartOptions);
                            setGdFieldOptions(apiOptionsCache.gdFieldOptions);
                        })
                        .catch(error => {
                            console.error('Error fetching visibility options:', error);
                            setPageOptions([{ label: 'Error loading pages', value: '' }]);
                            setTemplatePartOptions([{ label: 'Error loading parts', value: '' }]);
                            setGdFieldOptions([{ label: 'Error loading fields', value: '' }]);
                        });
                }, [isOpen]);


                const addRule = () => setRules([...rules, { type: '', id: Math.random() }]);
                const removeRule = (id) => setRules(rules.filter(rule => rule.id !== id));

                const updateRule = (id, updates) => {
                    setRules(rules.map(rule => (rule.id === id) ? { ...rule, ...updates } : rule));
                };

                const handleUserRoleChange = (id, role, isChecked) => {
                    const rule = rules.find(r => r.id === id);
                    let currentRoles = rule.user_roles ? rule.user_roles.split(',') : [];
                    currentRoles = isChecked ? [...currentRoles, role] : currentRoles.filter(r => r !== role);
                    updateRule(id, { user_roles: [...new Set(currentRoles)].join(',') });
                };

                const handleRuleTypeChange = (id, newType) => {
                    const updates = { type: newType };
                    if (newType === 'gd_field') {
                        updates.condition = 'is_empty';
                    }
                    if (newType === 'plugin_active') {
                        updates.condition = 'is_active';
                    }
                    updateRule(id, updates);
                };

                const handleSave = () => {
                    const finalState = {};
                    rules.forEach((rule, index) => {
                        if (rule.type) {
                            finalState[`rule${index + 1}`] = {
                                type: rule.type,
                                ...(rule.type === 'user_roles' && { user_roles: rule.user_roles }),
                                ...(rule.type === 'gd_field' && { field: rule.field, condition: rule.condition, search: rule.search }),
                                ...(rule.type === 'plugin_active' && {
                                    condition: rule.condition || 'is_active',
                                    ...(isConstantRule(rule) ? { constant: rule.constant } : { plugin: rule.plugin || '' }),
                                }),
                            };
                        }
                    });
                    if (output.type !== 'show' || Object.keys(output).length > 1) finalState.output = output;
                    if (outputN.type !== 'show' || Object.keys(outputN).length > 1) finalState.outputN = outputN;

                    onSave(JSON.stringify(finalState));
                    onClose();
                };

                if (!isOpen) return null;

                const renderActionControls = (actionState, setActionState) => {
                    return el(Fragment, null,
                        el('select', { className: 'form-select', style: { maxWidth: 'unset' }, value: actionState.type || 'show', onChange: e => setActionState({ type: e.target.value }) },
                        <?php echo wp_json_encode($output_options); ?>.map(opt => el('option', { key: opt.value, value: opt.value }, opt.label))
                    ),
                    actionState.type === 'message' && el('div', { className: 'row g-2 mt-1' },
                        el('div', { className: 'col-12' },
                            el('select', { className: 'form-select', style: { maxWidth: 'unset' }, value: actionState.message_type || '', onChange: e => setActionState({ ...actionState, message_type: e.target.value }) },
                            <?php echo wp_json_encode($message_type_options); ?>.map(opt => el('option', { key: opt.value, value: opt.value }, opt.label))
                        )
                    ),
                        el('div', { className: 'col-12' },
                            el('input', { type: 'text', className: 'form-control', placeholder: 'You must upgrade to see this block', value: actionState.message || '', onChange: e => setActionState({ ...actionState, message: e.target.value }) })
                        )
                ),
                    actionState.type === 'page' && el('div', { className: 'mt-2' },
                        el('select', { className: 'form-select', style: { maxWidth: 'unset' }, value: actionState.page || '', onChange: e => setActionState({ ...actionState, page: e.target.value }) },
                            pageOptions.map(opt => el('option', { key: opt.value, value: opt.value }, opt.label))
                        )
                    ),
                    actionState.type === 'template_part' && el('div', { className: 'mt-2' },
                        el('select', { className: 'form-select', style: { maxWidth: 'unset' }, value: actionState.template_part || '', onChange: e => setActionState({ ...actionState, template_part: e.target.value }) },
                            templatePartOptions.map(opt => el('option', { key: opt.value, value: opt.value }, opt.label))
                        )
                    )
                );
                };

                const modalContent = el('div', { className: 'bsui' },
                    el('div', { className: 'modal fade show', style: { display: 'block', zIndex: 160000 }, tabIndex: '-1' },
                        el('div', { className: 'modal-dialog modal-dialog-centered modal-lg' },
                            el('div', { className: 'modal-content border-0 shadow' },
                                el('div', { className: 'modal-header' },
                                    el('h5', { className: 'modal-title' }, 'Block Visibility'),
                                    el('button', { type: 'button', className: 'btn-close', onClick: onClose })
                                ),
                                el('div', { className: 'modal-body' },
                                    rules.map((rule, index) => el('div', { className: 'p-3 mb-3 border rounded-1 position-relative', key: rule.id },
                                            index > 0 && el('div', { className: 'text-center position-absolute top-0 start-50 translate-middle-x mt-n2' },
                                                el('span', { className: 'badge bg-light text-dark border' }, 'AND')
                                            ),
                                            rules.length > 1 && el('div', { className: 'position-absolute top-0 end-0' }, el('button', { type: 'button', className: 'btn-close text-danger', onClick: () => removeRule(rule.id) })),
                                            el('div', { className: 'input-group mb-3' },
                                                el('span', { className: 'input-group-text' }, 'Rule:'),
                                                el('select', { className: 'form-select', style: { maxWidth: 'unset' }, value: rule.type || '', onChange: e => handleRuleTypeChange(rule.id, e.target.value) },
                                                getRuleOptions(rule.type).map(o => el('option', { key: o.value, value: o.value }, o.label))
                                            )
                                        ),
                                        rule.type === 'user_roles' && el(Fragment, null,
                                            el('label', { className: 'form-label mb-2' }, 'Select User Roles:'),
                                            el('div', { className: 'row' },
                                            <?php echo wp_json_encode($user_roles_options); ?>.map(role => el('div', { className: 'col-sm-6', key: role.key },
                                                el('div', { className: 'form-check form-switch mb-2' },
                                                    el('input', { className: 'form-check-input', type: 'checkbox', role: 'switch', checked: (rule.user_roles || '').includes(role.key), onChange: e => handleUserRoleChange(rule.id, role.key, e.target.checked), id: `check-${rule.id}-${role.key}` }),
                                                    el('label', { className: 'form-check-label', htmlFor: `check-${rule.id}-${role.key}` }, role.label)
                                                )
                                            ))
                                        )
                                    ),
                                    rule.type === 'plugin_active' && el('div', { className: 'row g-2' },
                                        el('div', { className: 'col-md-8' },
                                            ComboboxControl
                                                ? el(ComboboxControl, {
                                                    __next40pxDefaultSize: true,
                                                    __nextHasNoMarginBottom: true,
                                                    placeholder: 'Search plugins...',
                                                    value: isConstantRule(rule) ? CUSTOM_CONSTANT : (rule.plugin || null),
                                                    options: getPluginOptions(rule),
                                                    onChange: v => updateRule(rule.id, v === CUSTOM_CONSTANT ? { plugin: '', constant: rule.constant || '' } : { plugin: v || '', constant: undefined }),
                                                })
                                                : el('select', { className: 'form-select', style: { maxWidth: 'unset' }, value: isConstantRule(rule) ? CUSTOM_CONSTANT : (rule.plugin || ''), onChange: e => updateRule(rule.id, e.target.value === CUSTOM_CONSTANT ? { plugin: '', constant: rule.constant || '' } : { plugin: e.target.value, constant: undefined }) },
                                                    [{ value: '', label: 'Select Plugin...' }, ...getPluginOptions(rule)].map(o => el('option', { key: o.value, value: o.value }, o.label))
                                                )
                                        ),
                                        el('div', { className: 'col-md-4' },
                                            el('select', { className: 'form-select', style: { maxWidth: 'unset' }, value: rule.condition || 'is_active', onChange: e => updateRule(rule.id, { condition: e.target.value }) },
                                            <?php echo wp_json_encode($plugin_condition_options); ?>.map(o => el('option', { key: o.value, value: o.value }, o.label))
                                        )
                                    ),
                                    isConstantRule(rule) && el('div', { className: 'col-12' },
                                        el('input', { type: 'text', className: 'form-control font-monospace', placeholder: 'CONSTANT_NAME', value: rule.constant, onChange: e => updateRule(rule.id, { constant: e.target.value.replace(/[^A-Za-z0-9_\\]/g, '') }) }),
                                        el('div', { className: 'form-text' }, 'The rule passes when this PHP constant is defined. Its value is never read.')
                                    )
                                ),
                                    rule.type === 'gd_field' && el('div', { className: 'row g-2' },
                                        el('div', { className: 'col-md-6' },
                                            el('select', { className: 'form-select', style: { maxWidth: 'unset' }, value: rule.field || '', onChange: e => updateRule(rule.id, { field: e.target.value }) },
                                                gdFieldOptions.map(o => el('option', { key: o.value, value: o.value }, o.label))
                                            )
                                        ),
                                        el('div', { className: 'col-md-6' },
                                            el('select', { className: 'form-select', style: { maxWidth: 'unset' }, value: rule.condition || '', onChange: e => updateRule(rule.id, { condition: e.target.value }) },
                                            <?php echo wp_json_encode($gd_condition_options); ?>.map(o => el('option', { key: o.value, value: o.value }, o.label))
                                        )
                                    ),
                                    // Conditionally render the search input
                                    (rule.condition && rule.condition !== 'is_empty' && rule.condition !== 'is_not_empty') && el('div', { className: 'col-12' },
                                        el('input', { type: 'text', className: 'form-control', placeholder: 'Value to match...', value: rule.search || '', onChange: e => updateRule(rule.id, { search: e.target.value }) })
                                    )
                                )
                            )),
                        el('button', { type: 'button', className: 'btn btn-primary d-block w-100', onClick: addRule }, '+ Add Rule'),
                        el('hr', { className: 'my-4' }),
                        el('div', { className: 'row' },
                            el('div', { className: 'col-md-6 mb-3 mb-md-0' },
                                el('label', { className: 'form-label' }, 'What should happen if rules met.'),
                                renderActionControls(output, setOutput)
                            ),
                            el('div', { className: 'col-md-6' },
                                el('label', { className: 'form-label' }, 'What should happen if rules NOT met.'),
                                renderActionControls(outputN, setOutputN)
                            )
                        )
                    ),
                    el('div', { className: 'modal-footer' },
                        el('button', { type: 'button', className: 'btn btn-secondary', onClick: onClose }, 'Close'),
                        el('button', { type: 'button', className: 'btn btn-primary', onClick: handleSave }, 'Save Rules')
                    )
                )
            )
            )
            );

                const modalWithBackdrop = el(Fragment, null,
                    el('div', { className: 'modal-backdrop fade show', style: { zIndex: 159990 } }),
                    modalContent
                );
                return createPortal(modalWithBackdrop, document.body);
            };

            if (!window.sdBlockTools) window.sdBlockTools = {};
            window.sdBlockTools.VisibilityConditionsModal = VisibilityConditionsModal;

        })(window.wp);
    </script>
    <?php
    return str_replace( [ '<script>', '</script>' ], '', ob_get_clean() );
}

	/**
	 * Returns the universal JavaScript functions for the block editor.
	 *
	 * This contains all the helper JS that is NOT specific to a single block's
	 * registration, but is needed on the editor screen.
	 *
	 * @return string
	 */
	public static function block_editor_global_js() {
    global $wp_version;

    ob_start();
    ?>
    <script>
        <?php


        ?>




        /**
         * Try to auto-recover blocks.
         */
        function sd_auto_recover_blocks() {
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
        function sd_auto_recover_blocks_fallback(editTmpl) {
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
		 * This needs to be kept in sync with the PHP version in includes/functions.php
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

		/**
		 * This needs to be kept in sync with the PHP version in includes/functions.php
		 *
		 * @param $args
		 * @returns {string}
		 */
        function sd_build_aui_class($args) {

            $classes = [];


            $p_ml = 'ms-';
            $p_mr = 'me-';

            $p_pl = 'ps-';
            $p_pr = 'pe-';


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
                if ( $args['border_type'] !== undefined) {
                    $args['border_type'] = $args['border_type'].replace('-left', '-start').replace('-right', '-end');
                }
                $border_class = 'border';
                if ($args['border_type'] !== undefined && $args['border_type'] !== "" && !$args['border_type'].includes('-0')) {
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
					$args['text_align'] = $args['text_align'].replace('-left', '-start').replace('-right', '-end');

                    $classes.push($args['text_align']);
                    $text_align = $args['text_align'];
                } else {
                    $text_align = null;
                }
                if ($args['text_align_md'] !== undefined && $args['text_align_md'] !== '') {
					$args['text_align_md'] = $args['text_align_md'].replace('-left', '-start').replace('-right', '-end');

                    $classes.push($args['text_align_md']);
                    $text_align_md = $args['text_align_md'];
                } else {
                    $text_align_md = null;
                }
                if ($args['text_align_lg'] !== undefined && $args['text_align_lg'] !== '') {
					$args['text_align_lg'] = $args['text_align_lg'].replace('-left', '-start').replace('-right', '-end');

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
            return <?php echo wp_json_encode( sd_get_class_build_keys() );?>;
        }

        <?php


        //}

        ?>
    </script>
    <?php
    return str_replace( [ '<script>', '</script>' ], '', ob_get_clean() );
}

	/**
	 * Outputs an inline style to the main editor stylesheet.
	 *
	 * @return void
	 */
	public static function inline_editor_styles() {
	echo "
    <style>
        .bsui label.components-base-control__label {
            width: 100%;
        }
        /* The visibility modal's plugin combobox is a WP component inside a Bootstrap modal.
           Give its box the form-control look so it lines up with the selects beside it, and
           float the suggestions list over the modal instead of pushing the content down. */
        .bsui .components-combobox-control__suggestions-container {
            position: relative;
            min-height: calc(1.5em + 1rem + 2px);
            padding: 0;
            font-size: 1rem;
            color: var(--bs-body-color);
            background-color: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: var(--bs-border-radius);
            align-items: center;
        }
        .bsui .components-combobox-control input.components-combobox-control__input[type='text'] {
            min-height: 0;
            padding: .5rem .75rem;
            font-size: 1rem;
        }
        .bsui .components-form-token-field__suggestions-list {
            position: absolute;
            top: 100%;
            left: -1px;
            right: -1px;
            z-index: 5;
            max-height: 14rem;
            margin-top: .25rem;
            overflow-y: auto;
            font-size: 1rem;
            color: var(--bs-body-color);
            background-color: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: var(--bs-border-radius);
            box-shadow: var(--bs-box-shadow);
        }
    </style>
    ";
}
}
