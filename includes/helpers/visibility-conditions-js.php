<?php
/**
 * WP Super Duper Visibility Conditions Modal Script
 *
 * This file contains a PHP function that outputs the JavaScript for a modern,
 * React-based modal to manage block visibility conditions.
 *
 * @version 1.2.39
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Outputs the JavaScript for the VisibilityConditionsModal React component.
 */
function sd_get_visibility_conditions_script() {
	// --- START: Data Configuration ---

	// Rule Options
	$rule_options = [
		['label' => 'Select Rule...', 'value' => ''],
		['label' => 'Logged In', 'value' => 'logged_in'],
		['label' => 'Logged Out', 'value' => 'logged_out'],
		['label' => 'Post Author', 'value' => 'post_author'],
		['label' => 'Specific User Roles', 'value' => 'user_roles'],
		['label' => 'GD Field', 'value' => 'gd_field'],
	];

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
		['label' => 'contains', 'value' => 'contains'],
		['label' => 'does not contain', 'value' => 'not_contains'],
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

	// --- END: Data Configuration ---


	ob_start();
	?>
	<script>
		(function(wp) {
			if (!wp || !wp.element) return;

			const { element, apiFetch } = wp;
			const { createElement: el, useState, useEffect, Fragment, createPortal } = element;

			// Cache variable outside the component to persist across re-mounts.
			let apiOptionsCache = null;

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
					const fetchGdFields = apiFetch({ path: '/geodir/v2/fields?per_page=100' });

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
						<?php echo json_encode($output_options); ?>.map(opt => el('option', { key: opt.value, value: opt.value }, opt.label))
					),
					actionState.type === 'message' && el('div', { className: 'row g-2 mt-1' },
						el('div', { className: 'col-12' },
							el('select', { className: 'form-select', style: { maxWidth: 'unset' }, value: actionState.message_type || '', onChange: e => setActionState({ ...actionState, message_type: e.target.value }) },
							<?php echo json_encode($message_type_options); ?>.map(opt => el('option', { key: opt.value, value: opt.value }, opt.label))
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
												<?php echo json_encode($rule_options); ?>.map(o => el('option', { key: o.value, value: o.value }, o.label))
											)
										),
										rule.type === 'user_roles' && el(Fragment, null,
											el('label', { className: 'form-label mb-2' }, 'Select User Roles:'),
											el('div', { className: 'row' },
											<?php echo json_encode($user_roles_options); ?>.map(role => el('div', { className: 'col-sm-6', key: role.key },
												el('div', { className: 'form-check form-switch mb-2' },
													el('input', { className: 'form-check-input', type: 'checkbox', role: 'switch', checked: (rule.user_roles || '').includes(role.key), onChange: e => handleUserRoleChange(rule.id, role.key, e.target.checked), id: `check-${rule.id}-${role.key}` }),
													el('label', { className: 'form-check-label', htmlFor: `check-${rule.id}-${role.key}` }, role.label)
												)
											))
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
											<?php echo json_encode($gd_condition_options); ?>.map(o => el('option', { key: o.value, value: o.value }, o.label))
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
	return ob_get_clean();
}

wp_add_inline_script( 'super-duper-universal-block-editor', sd_get_visibility_conditions_script() );
