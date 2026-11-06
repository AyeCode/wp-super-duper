# Changelog

## [3.0.10-beta] — 2026-09-TBD
- sd_build_aui_class_attr(), sd_build_aui_style_attr() and sd_build_aui_wrapper_attrs() (Utils statics with global wrappers) return finished, escaped class and style attributes, or nothing when empty, so blocks no longer stitch the attribute together or pick an escaper themselves - ADDED
- Inline style values from the custom color, gradient, background image, line height and max height settings are validated as CSS values before output, so a stored value can no longer append its own declarations to the style attribute - FIXED
- Hover color and hover gradient settings were written unvalidated into the <style> element the block prints, so a stored value could close the tag and inject markup; they now go through the same CSS value validation - FIXED
- The sd_* global wrappers that block authors call from output() (sd_build_attributes_string_escaped, sd_parse_custom_attributes, sd_replace_variables, sd_build_shortcode, sd_get_shortcode_slug, the template page and part helpers, sd_pagenow_exclude, sd_widget_exclude) are supported public API again and have moved from functions-deprecated.php to functions.php - CHANGED
- Title row actions: a block calls set_title_actions() from output() to put a view all link, pagination or carousel arrows inline on the end side of its title row, on blocks, shortcodes and widgets alike, with the wp_super_duper_title_actions filter for addons - ADDED
- CarouselFields: the standard carousel settings group (arrows with an offset that can push them outside the carousel, dots, autoplay stepped or scrolling left or right, loop, move by, per view, gap, items per column, peek with an edge fade, speed), the mapping from saved values to aui()->carousel() args and a stack() helper that groups slides into columns - ADDED
- Block editor element_require now evaluates like the shortcode builder: nested brackets, mixed && and || with precedence, ! negation and loose compares, so checkboxes tested with =="1" and groups such as A && (B || C) show and hide correctly instead of being dropped - FIXED
- Blocks from an inactive AyeCode plugin show what they need, with an activate or install link, instead of the generic unsupported block message - ADDED
- Plugin Active visibility rule added: pick an installed plugin from a searchable list, or name a PHP constant, and show or hide the block by whether it is active - ADDED
- GD Field visibility conditions "contains" and "does not contain" were saved by the new editor modal under names the evaluator did not recognise; the modal now saves is_contains and is_not_contains as before, and the evaluator also accepts the short names - FIXED
- GD Field visibility rule was unavailable and never evaluated with GeoDirectory v3, which no longer defines a global GeoDirectory class; detection now uses the GEODIRECTORY_VERSION constant - FIXED
- Visibility conditions rule picker now lists rules added with the sd_visibility_rules_options filter, and keeps a saved rule type that is no longer offered so it survives a resave - FIXED
- Utils::is_preview() is true while the dynamic data picker renders a preview, so tags can fall back to demo content as blocks do - ADDED
- Values the block editor fills in by itself (shortcode tag, styleid, cached preview) no longer mark a post or template changed on open or add undo steps; they are still saved with the next real change - FIXED
- Block settings whose field declared a widget 'value', such as BS Gallery's aspect ratio, stayed stuck on that value in the sidebar even though the change applied - FIXED
- StyleFields::color_mode() field added for pinning a block to Bootstrap light or dark mode via data-bs-theme - ADDED
- block-output props with hyphenated names, such as if_data-bs-theme, produced invalid editor JS; their keys are now quoted - FIXED
- Dynamic data tags rebuilt around a registry: addons register tags, groups and option sources on sd_dynamic_data_register - ADDED
- Dynamic data syntax is now {name:primary @key:'value' @flag}; date formats with colons, spaces, commas and slashes work, and fallback is @fallback:'text' - CHANGED
- {post_meta} tag added; protected and non-scalar meta keys are refused unless allowed with sd_dynamic_data_meta_key_allowed - ADDED
- Text that is not a registered tag, such as inline CSS or JSON, is no longer blanked by dynamic data replacement - FIXED
- {post_content} no longer double escapes its HTML and cannot render itself recursively - FIXED
- REST endpoints ayecode/v3/dynamic-data and ayecode/v3/dynamic-data/options/{source} added for the editor picker - ADDED
- REST endpoint ayecode/v3/dynamic-data/preview renders a tag against the post being edited, for users who can edit that post - ADDED
- Dynamic data button on text fields reopens the tag at the caret for editing and replaces it on insert - ADDED
- Dynamic data modal on RichText blocks reopens the tag at the caret, exposed to edit components as dynamicDataInitialTag - ADDED
- {author_meta}, {user_meta} and {term_meta} tags added; user meta is limited to an allowlist (first_name, last_name, nickname, description) extended with sd_dynamic_data_meta_key_allowed - ADDED
- Typing { in a RichText dynamic data block opens the picker, and the chosen tag replaces the brace - ADDED
- Blocks whose plugin is deactivated printed their raw shortcode text on the frontend; they now render nothing, and nested blocks keep their children - FIXED
- docs/dev/roadmap.md added recording deferred work and the measurements behind each decision - ADDED
- Stale minified block editor JS removed; it was referenced by nothing and was missing the dependent-fields subsystem - REMOVED
- Unused icons/placeholder.png removed - REMOVED
- Release archives no longer ship developer tooling (.github, .claude, .aiassistant, CLAUDE.md, docs, phpcs config) or the example files - CHANGED
- sd-plugin.php excluded from release archives; its second plugin header made a phantom "Super Duper - Examples" entry appear in the Plugins list - FIXED
- .gitattributes no longer lists four paths that do not exist (.scrutinizer.yml, phpunit.xml, CONTRIBUTING.md, tests/) - FIXED
- Background field group now participates in the block editor component dedupe, collapsing 298 repeated field configs into 9 shared components - CHANGED
- Admin screens with no use for blocks (Dashboard, Users, Media, Comments, post list and similar) no longer instantiate every registered block, reusing the existing sd_pagenow_exclude list - CHANGED
- admin-ajax requests no longer instantiate every registered block; WooCommerce fragments, Heartbeat and other plugins' AJAX calls save ~14MB and ~37ms each - CHANGED
- ayecode_sd_eager_on_ajax filter added to force eager block loading on AJAX if a handler needs it - ADDED
- $sd_widgets global removed; the block registry is now the single source of truth - REMOVED
- Per-request guard flags moved from globals to static properties on a new State class - CHANGED
- Shortcode picker and widget settings now read from the block registry instead of the $sd_widgets global, so they list every registered block rather than only those already instantiated - FIXED
- Generator meta tag and the two AJAX handlers now register at plugins_loaded instead of from a block constructor, so they no longer depend on a block having rendered - FIXED
- Nested blocks of the same type overwrote each other's instance state, producing a duplicate sdel- style hash - FIXED
- Legacy global-name trait copies in includes/traits/ no longer loaded; ~105KB less PHP parsed per request - CHANGED
- Dead legacy files (includes/loader.php, class-wp-super-duper-base/-widget, bricks element, empty helper) no longer referenced - REMOVED
- Shortcode picker AJAX endpoint now verifies its nonce and checks edit_posts - FIXED
- Widget settings AJAX endpoint now checks edit_posts as well as the nonce - FIXED
- Block preview AJAX proxy now authenticates before instantiating a block class - FIXED
- Deprecation notices for three internal editor-asset helpers named a class that was never created - FIXED
- Common field labels were using the geodirectory text domain instead of ayecode-connect - FIXED
- Request parameter dynamic value now runs wp_unslash() before sanitizing - FIXED
- Template page query now runs through $wpdb->prepare() on both branches - FIXED
- Class build key list is memoised instead of rebuilt and filtered on every block render - CHANGED
- Widget admin JS buffer built once instead of twice per request - CHANGED
- Block editor deprecation notices from the unfinished BlockEditorAssets migration - FIXED
- Added AI schema API endpoints (ayecode/v3/ai): /catalog and /schemas - ADDED
- Added ai-hint, ai-context and ai-nesting block options for AI schema metadata - ADDED
- Added AI HTML-to-blocks endpoint (ayecode/v3/ai/convert) that maps Bootstrap 5.3 HTML to a native block tree with a loss report - ADDED
- Added AI vocabulary endpoint (ayecode/v3/ai/vocabulary) listing authoring rules and custom-element blocks - ADDED
- Added dev-gated AI render endpoint (ayecode/v3/ai/render) for the conversion fidelity harness - ADDED
- Added class_prefix metadata to token-enum style attributes in the AI schema - ADDED
- Added ai-html block option so blocks can claim native HTML elements for conversion - ADDED
- AI convert depth limit raised to 12 so ordinary nested sections are not truncated - FIXED
- AI render endpoint renders blocks via shortcode output instead of do_blocks, which produced empty HTML - FIXED
- AI convert keeps a class in css_class instead of dropping it when its block condition cannot be met - FIXED
- AI convert keeps spacing shorthand and lg-only responsive classes verbatim so they render as authored - FIXED
- AI convert now maps text-bearing inline elements and unmapped wrappers instead of dropping their content and classes - FIXED
- AI convert decomposes icon+text items into an icon-box, splitting classes across icon/title attributes and forcing link type none - FIXED
- AI render separates sibling blocks with whitespace so adjacent text no longer fuses - FIXED
- AI convert pins neutral values for block styling defaults so conversions no longer inject margins, container widths, or icon-box scale the design never specified - FIXED
- AI convert decomposes button variant classes (btn-*, btn-lg/sm) into the block's link fields instead of doubling the btn class - FIXED
- AI convert expands spacing shorthands (p-4, py-5, px-4) to per-side attributes - CHANGED
- bs_container renders its html_tag (defaulting to div) instead of a hardcoded section - CHANGED
- bs_iconbox no longer forces h-100 or a duplicate physical margin on the icon wrapper - FIXED
- bs_button no longer double-prints css_class - FIXED
- AI convert wraps loose text nodes and keeps empty-but-styled elements (decorative dots) instead of dropping them - FIXED
- AI convert preserves an icon-item's inner text-element classes via the title attributes and css_class - FIXED
- AI convert maps hr to a border-top divider and decomposes span.badge into bs_badge - ADDED
- AI convert decomposes icon chips (a coloured/rounded wrapper around an icon) into a single icon-box - ADDED
- AI convert infers the column role from any col-* breakpoint variant, not only a bare col - FIXED
- Added ai-html-fallback block option and form to bs_contact safety-net claim; bs_contact authoring args promoted to content tier - ADDED
- Added class_prefix bg- to badge_bg and included badge styling defaults in AI convert neutralization - FIXED
- AI convert folds the media-object pattern (icon chip + heading + paragraph) into one bs_iconbox with title and description populated - ADDED
- Promoted the bs_iconbox description argument to content tier in the AI schema - CHANGED
- bs_iconbox output separates its icon, title and description with whitespace so adjacent copy no longer fuses - FIXED
- AI convert maps a media object's text alignment (text-start/center/end) to the icon-box icon/title/desc text-align attributes so left-aligned cards no longer render centered - FIXED
- AI convert maps an icon chip's fs-* to icon_size, and bs_iconbox renders icon_size for filled chips - FIXED
- AI convert maps flex utilities (align-items-*, justify-content-*) to the flex attributes when display is set on the same element, instead of spilling them to css_class - FIXED
- AI convert maps responsive variants (mt-lg-3, text-lg-start, d-lg-flex) to their _md/_lg attributes instead of keeping them verbatim in css_class - CHANGED
- AI convert absorbs an icon chip's p-* into the icon-box per-side padding, maps an explicit mb-0 to mb/title_pb/desc_pb "0", and maps a bare small to the target font-size attribute as fs-sm - FIXED
- Added body-emphasis to the shared AUI emphasis colour options - ADDED
- AI convert folds the media object for any adjacent caption (inline span/small/strong labels and div caption rows, and a bare icon), not only a heading/paragraph stack - CHANGED
- AI convert preserves trailing content (a feature list, a CTA) after a folded caption instead of abandoning the fold, so feature cards no longer strand their copy beside an icon-only box - FIXED
- AI convert decomposes an inline icon+text row whose icon is not the first child (a status pill led by a spinner) into a single titled icon-box - FIXED
- AI convert folds a br inside a text run or caption into the copy (space or line break) and never reports it as dropped - FIXED
- AI convert maps the border border-{token} combo to the container border attribute and absorbs the bare border marker instead of spilling it - FIXED
- AI convert never folds interactive or claimed elements (a.btn, button, img, badges, custom elements) into an icon composite, so a CTA button group converts as separate bs_button blocks instead of one fused icon-box - FIXED
- AI convert no longer folds a multi-run inline row (a trust line of bullet-separated spans) into one fused-title icon-box - FIXED
- AI convert counts a label unit that carries its own icon as a text run, so a two-label justify row is not folded into one icon-box - FIXED
- AI convert strips any stray Font Awesome identity class from css_class, so an icon never leaks into a block's class list - FIXED
- Added a bs-post-content vocabulary element that converts to core/post-content, the first core-block exception, so a single-post template can render the post body - ADDED
- AI convert treats an icon-only a.btn/button as a bs_button with icon_class instead of an icon-box, even with a rounded-circle marker - FIXED
- AI convert splits a bold-lead caption (strong/b prefix) into title and description instead of fusing it into one over-long title - FIXED
- AI convert absorbs a folded icon item's own flex/gap layout classes like chip internals instead of spilling them to css_class - FIXED
- AI convert folds a heading followed by a trailing decorative icon (a verified badge) into one titled icon-box instead of stranding the icon - FIXED
- AI convert keeps a border-radius (rounded/rounded_size) as an attribute on a borderless element instead of spilling it, since the radius renders independent of the border - FIXED
- AI vocabulary marks a container widget (a block that accepts inner blocks) as leaf false with a derived children list, replacing the blanket leaf rule that made the model avoid the navbar and accordion families - CHANGED
- AI convert recurses into container custom elements (navbar, nav, accordion, tabs, modal, offcanvas), converting listed child elements into inner blocks so a nested bs-navbar becomes the proper nested block tree - ADDED
- AI convert maps the bare .small class to the fs-sm font-size attribute universally, not only on badge and composite paths - FIXED
- Added flex_direction, flex_wrap, gap and ratio field families to the container so the flex/gap/aspect-ratio utilities map to attributes instead of spilling - ADDED
- Added the fw-medium token to the shared font_weight enum - ADDED
- Fixed the flex_align_self enum, which listed align-items-* values instead of align-self-*, and mapped it independent of the editor container gate - FIXED
- Enabled the font_size control on the container so container font-size utilities map to an attribute - CHANGED
- AI convert absorbs a chip's align-self-* utility when folding an icon composite - FIXED
- AI convert consumes background declarations from inline style (gradient, custom color, image, and the gradient-text combo) into the bg attribute family instead of dropping them, mapping only where the block declares the field and rejecting unsafe values - ADDED
- AI convert consumes a non-transparent inline color into the text_color custom pair, ordered after the gradient-text combo so a combo's transparent color stays a companion - ADDED
- AI convert repairs invalid flex gap utilities on a row by rewriting them to the gutter family (gap to g, row-gap to gy, column-gap to gx), reported under a repairs key instead of as loss - ADDED

## [3.0.9-beta] — 2026-09-14
- enqueue_scripts() method added so a block loads its assets only when it actually renders - ADDED
- ayecode_sd_registered_blocks filter added to allow any registered block to be disabled globally - ADDED
- ayecode_sd_register() output_types now defaults to block and shortcode, widget support is opt-in - CHANGED
- Standard block settings tab and field-group structure documented in docs/dev/block-building.md - ADDED
- Block building reference moved from .claude/commands to docs/dev/block-building.md - CHANGED
- Documentation moved under docs/dev/ so the AI docs scanner picks it up - CHANGED
- hello-world.php and the doc examples now register via ayecode_sd_register() instead of register_widget()/new - CHANGED
- Additional CSS field added by WP is breaking out of our tabs - FIXED
- Bug with some select items rendering as an array in JSON instead of object - FIXED
- Added API endpoint for discovering blocks and getting block details - ADDED

## [3.0.8-beta] — 2026-05-18
- font_italic field removed from add_typography_group() as it is part for font_weight field - CHANGED
- added helper function ayecode_get_current_url( $with_query_string = true ) - ADDED
- add_responsive_margins() and add_responsive_paddings() functions third param changed to allow individual overrides - CHANGED
- Update the super-duper-block command with new options - CHANGED
- Several improvements for BlockArgument group methods - CHANGED

## [3.0.7-beta] — 2026-05-11
- Added 'is_slug' param for text inputs to force slug format - ADDED
- Package loader is not respecting load order due to AI slop - FIXED
- Added new fields to the block arguments builder - ADDED
- Deprecation notices for deprecated functions - ADDED
- Claude command added for converting blocks to use new BlockArguments() class - ADDED
- New function added for getting colors  ayecode_get_sd_colors() - ADDED

## [3.0.6-beta] — 2026-04-30
- Field builder refactor to new standards - CHANGED
- ayecode_sd_register() function added to register blocks for better efficiency - ADDED
- 
## [3.0.5-beta] — 2026-04-29
- Large refactor to AyeCode Package standards - CHANGED

## [3.0.3] — 2025-TBD
- Huge improvements in all aspects; complete rewrite for speed and efficiency - CHANGED
- Empty params now removed from output - CHANGED
- Blocks no longer contain full shortcode; sizes significantly reduced - CHANGED
- Block now created by JS from JSON config - CHANGED
- Transforms feature added to allow blocks to transform to and from other blocks - ADDED
- Block block_group_tabs settings changed to use a group ID and title to prevent broken arguments when translated - FIXED
- Block arguments "group" should now use the group ID string and not a translatable string - CHANGED

## [1.2.23] — 2025-06-18
- Single quote in setting default attribute causes JS error - FIXED

## [1.2.22] — 2025-04-17
- Extra sanitation for widget titles - ADDED

## [1.2.21] — 2025-03-27
- Added more container class options for creating list groups - ADDED

## [1.2.20] — 2025-03-20
- Advance Search Filters options are not available in Bricks GD > Search element - FIXED
- GD > Map bricks element don't render map zoom control - FIXED

## [1.2.19] — 2025-02-20
- BS > Nav Item hide block condition is not working - FIXED

## [1.2.18] — 2025-02-06
- Fixed processing of rules with missing validators - FIXED

## [1.2.17] — 2025-01-09
- Added extensibility for block visibility field settings via hooks, filters and jQuery triggers - ADDED

## [1.2.16] — 2024-12-12
- Changes for Bricks output class to add AyeCode UI class and show icons as icon picker - ADDED

## [1.2.15] — 2024-11-28
- Class added for Bricks element conversion - ADDED

## [1.2.14] — 2024-11-12
- widget_title_tag param now filtered by allowed tags list - CHANGED

## [1.2.13] — 2024-10-10
- sd_template_page_options() not allowing child page selection - FIXED
- Filter added `sd_template_page_options_limit` to adjust the 500 pages limit - ADDED
- Multiple blocks with taxonomy linking feature can cause ajax query to loop and freeze browser - FIXED
- __experimentalGetPreviewDeviceType is deprecated - FIXED

## [1.2.10] — 2024-10-08
- Selecting multiple similar blocks with different settings can cause editor to freeze - FIXED
- Blocks with content already set will not call a refresh on load - CHANGED
- New shortcode param could cause some blocks to call the render callback twice on each change - FIXED

## [1.2.9] — 2024-09-12
- Template get_pages call changed to custom query for better memory management - CHANGED

## [1.2.7] — 2024-08-29
- Fix conflicts with UpSolution Core plugin - FIXED

## [1.2.6] — 2024-07-18
- render_block hook only had 2 args prior to WP 5.9 which could cause fatal error when running WP < 5.9 - FIXED

## [1.2.5] — 2024-07-17
- Non FSE themes can fail to render templates with blocks - FIXED

## [1.2.4] — 2024-07-16
- Some blocks not being wrapped inside the block wrapper - FIXED
- Issue with blocks with no arguments not adding shortcode argument - FIXED

## [1.2.2] — 2024-07-12
- Blocks with `block-save-return` set should not have a sd_shortcode argument - FIXED

## [1.2.1] — 2024-07-10
- `html` argument not wrapped correctly in shortcode with new system for blocks - FIXED
- Hide advanced border options when border color selected to none - FIXED

## [1.2.0] — 2024-07-09
- Added color options for upcoming dark mode feature - ADDED
- Improvements to blocks to help prevent broken blocks - CHANGED
- Blocks now store shortcodes in block param and not in the content - CHANGED

## [1.1.45] — 2024-06-20
- Yoast SEO conflict causes JS error on post edit pages - FIXED

## [1.1.44] — 2024-06-13
- Width and Height helper functions added - ADDED
- Undefined array key warning - FIXED

## [1.1.43] — 2024-06-05
- example block attribute now works for block previews - ADDED
- Block previews now enabled by default (set example as false to disable) - CHANGED
- viewportWidth can be set in example attribute to set the preview viewport width - ADDED
- example argument can be set on individual arguments to pass as the example - ADDED
- `parent` and `allowedBlocks` block arguments now supported - ADDED

## [1.1.41] — 2024-05-22
- placeholder.png replaced with inline SVG for better block template compatibility - CHANGED

## [1.1.40] — 2024-05-16
- Block Visibility condition not working for CPT non-supported fields - FIXED
- Block label dynamic name not working for containers - FIXED

## [1.1.39] — 2024-04-18
- Better block recovery - CHANGED

## [1.1.38] — 2024-04-16
- Escape shortcode param names - ADDED
- Better block recovery - CHANGED

## [1.1.37] — 2024-04-11
- Font size option as inherit could output a 0 as a class - FIXED
- Block rename functionality removed by default and added back with new function metadata_name = sd_get_custom_name_input() - CHANGED
- Show eye icon in block list view when visibility conditions are set - ADDED

## [1.1.36] — 2024-03-26
- Added helper functions for link attributes - ADDED

## [1.1.35] — 2024-03-13
- Add style multiselect field for Elementor editor - CHANGED

## [1.1.34] — 2024-02-15
- Post Author support added in Block Visibility - ADDED

## [1.1.33] — 2024-01-25
- Escape new line characters from option label - FIXED

## [1.1.32] — 2023-12-14
- Update textdomain - CHANGED
- Escape placeholder to prevent JS error - FIXED
- Site editor sidebar navigation auto closed - FIXED

## [1.1.31] — 2023-11-DD
- Detect Bricks builder preview - ADDED

## [1.1.30] — 2023-10-DD
- element_require condition is not working with checkbox - FIXED

## [1.1.29] — 2023-10-DD
- Show device type name with title for multi-device option setting - CHANGED
- element_require condition is not working with checkbox - FIXED

## [1.1.28] — 2023-09-DD
- Shortcode button added twice in the page content editor - FIXED

## [1.1.27] — 2023-08-DD
- Tablet padding option values are not working properly - FIXED

## [1.1.26] — 2023-07-DD
- Attempt auto recovery block on non edit template mode - CHANGED

## [1.1.25] — 2023-06-DD
- Undefined array key "title" thrown for some blocks when using Avada - FIXED

## [1.1.24] — 2023-05-DD
- Gallery block shows image when requested image size doesn't exist - FIXED
- Gallery block shows JS error when image title contains multiple single quote - FIXED

## [1.1.23] — 2023-04-DD
- Type missing ; causing error - FIXED

## [1.1.22] — 2023-03-DD
- Avada converts checkbox to select dropdown; don't save empty value - CHANGED
- Check preview for Kallyas theme Zion builder editor - ADDED
- PHP notice of argument must be of type array, null given - FIXED

## [1.1.21] — 2023-02-DD
- New line character in translation results in JS error - FIXED
- Background Gradient picker broken after WP update - FIXED

## [1.1.20] — 2023-01-DD
- WP installs less than 5.9 can throw fatal error for render_block filter - FIXED

## [1.1.19] — 2022-12-DD
- Block Visibility feature added - ADDED

## [1.1.18] — 2022-11-DD
- Added helper function for float inputs - ADDED

## [1.1.17] — 2022-10-DD
- Block tab groups mismatch causes JS error - FIXED

## [1.1.16] — 2022-09-DD
- Better block auto-recovery - ADDED
- Functions added to support BS5 changes and new features - ADDED

## [1.1.15] — 2022-08-DD
- Prevent duplicate CPT sort options API requests - FIXED

## [1.1.14] — 2022-07-DD
- Helper functions for BS5 added - ADDED
- Will try to auto-recover blocks in editor - ADDED
- Single apostrophe will break block input settings - FIXED

## [1.1.11] — 2022-06-DD
- Functions added to build CSS for some hover args - ADDED
- Template parts auto recover blocks not working - FIXED
- Multiple taxonomies API requests can cause a loop - FIXED

## [1.1.10] — 2022-05-DD
- styleid argument now auto added to class list - ADDED

## [1.1.9] — 2022-04-DD
- Device type switcher not working on multiline items - FIXED
- Spacing option increased from 5 to 12 - ADDED
- MultiSelect inputs now show a clear button - ADDED

## [1.1.8] — 2022-03-DD
- Prevent JS error that breaks the GD block - FIXED
- Bug with justify content between helper function - FIXED

## [1.1.5] — 2022-02-DD
- Customize > Widgets section don't show advanced settings toggle button - FIXED
- Flex positioning helper functions - ADDED

## [1.1.4] — 2022-01-DD
- Hover animations helper functions - ADDED
- Flex layout helper classes - ADDED
- Constant added for version number SUPER_DUPER_VER - ADDED

## [1.1.3] — 2021-12-DD
- Preview mode can now be changed from block setting icon - ADDED
- text_color_custom now implemented for inline custom colors - ADDED

## [1.1.2] — 2021-11-DD
- BlockStrap theme color pickers will now add custom colors from FSE - ADDED

## [1.1.1] — 2021-10-DD
- props.id deprecated and replaced with props.clientId - FIXED
- Will now attempt to auto-recover all blocks - ADDED
- Nested blocks support - ADDED
- New input types supported for blocks: 'image' and 'images' - ADDED
- Many helper functions added for input arguments - ADDED
- Font Awesome icons for block icons updated to 5.15.4 - CHANGED

## [1.0.30] — 2021-09-DD
- Block icon not loading when WordPress installed on sub-directory - FIXED

## [1.0.29] — 2021-08-DD
- Shortcode button not working in editor on Divi theme builder - FIXED

## [1.0.28] — 2021-07-DD
- Some extra escaping block parameters - CHANGED
- Block editor multi-select dropdown style issue - FIXED
- If tinyMCE is undefined the inserter can fail - FIXED
- block_show_advanced() sometimes has no arguments - FIXED

## [1.0.27] — 2021-06-DD
- Category settings loads only 10 categories on CPT change - FIXED
- Hook added to filter class & attributes for Elementor widget output - ADDED
- Functions file added for individual function calls - ADDED

## [1.0.26] — 2021-05-DD
- Error on setting array option value in block editor - FIXED

## [1.0.25] — 2021-04-DD
- PHP Warning if default field value not set - FIXED

## [1.0.24] — 2021-03-DD
- WP 5.7 Gutenberg widgets screen has JS error which breaks page saving - FIXED
- WP 5.7 Gutenberg widgets screen legacy widgets have no `show advanced` button - FIXED

## [1.0.23] — 2021-02-DD
- Rows argument now available to group arguments to the one row - ADDED

## [1.0.22] — 2021-01-DD
- Sometimes arguments are not set early enough when building block JS - FIXED

## [1.0.21] — 2020-12-DD
- Block Category can now update on post_type change - FIXED
- Non static method 'argument_values' should not be called statically - FIXED

## [1.0.20] — 2020-11-DD
- On change content don't compare the actual content, only attributes - CHANGED
- Call AUI init JS function if defined on each block edit - ADDED
- is_preview() now included block preview - CHANGED
- Empty block previews can show broken class input - FIXED
- color input for block now uses colorPicker - CHANGED

## [1.0.19] — 2020-10-DD
- Put non grouped block settings in to a "settings" group - CHANGED

## [1.0.18] — 2020-09-DD
- Conflicts with fusion builder elements - FIXED
- Oxygen builder preview detection - ADDED
- Elementor editor widget section types have no styling - FIXED

## [1.0.17] — 2020-08-DD
- Title & description in block editor shows escaped special characters - FIXED
- Widget advanced settings button line-height too tall - FIXED

## [1.0.16] — 2020-07-DD
- wp.editor is deprecated, updated to wp.blockEditor - CHANGED
- textarea field type now supported - ADDED
- `html` special field name added that will allow HTML by making it an enclosing shortcode - ADDED
- `no_wrap` special field name added that will prevent the wrapping div being added - ADDED
