# Changelog

## [3.0.7-beta] — 2026-TBD
- Added 'is_slug' param for text inputs to force slug format - ADDED
- Package loader is not respecting load order due to AI slop - FIXED
- Added new fields to the block arguments builder - ADDED

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
