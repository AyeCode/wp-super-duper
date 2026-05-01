<?php

namespace AyeCode\SuperDuper\Traits;
/**
 * WP Super Duper Gutenberg Block Trait
 *
 * This trait contains all methods for creating and managing the dynamic
 * Gutenberg block, including the JavaScript generation for block registration,
 * controls, and previews.
 *
 * @version 1.2.25
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

trait GutenbergBlock {

    /**
     * Add the dynamic block code inline when the wp-blocks script is enqueued.
     */
    public function register_block() {

//		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_assets' ] );


        if ( class_exists( 'SiteOrigin_Panels' ) ) {
            wp_add_inline_script( 'wp-blocks', self::siteorigin_js() );
        }
    }

    /**
     * Enqueues the universal block script and passes this block's specific
     * configuration data to it.
     */
    public function enqueue_block_assets() {
        global $sd_enqueued_block_components;
        // 1. Enqueue the main universal JS file (this will only run once).
        // This assumes your JS file is located at 'assets/js/super-duper-block-editor.js'.
        // You may need to adjust the path.


        wp_enqueue_script(
                'super-duper-universal-block-editor',
                SUPER_DUPER_PLUGIN_URL . 'assets/js/super-duper-block-editor.js',
                [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ], // Common dependencies
                SUPER_DUPER_VER,
                true // Load in footer
        );

        // visibility conditions
        require_once SUPER_DUPER_INCLUDES_PATH . 'helpers/visibility-conditions-js.php';

        // include helper JS functions
        require_once SUPER_DUPER_INCLUDES_PATH . 'helpers/gutenberg-block-helpers.php'; //sd_get_block_editor_global_js();

        // editor styles
        require_once SUPER_DUPER_INCLUDES_PATH . 'helpers/gutenberg-editor-styles.php';;



        // 2. Prepare the configuration array for this specific block instance.
        $class_name =  basename( str_replace( '\\', '/', $this->options['class_name'] ) );
        $block_name = str_replace( "_", "-", sanitize_title_with_dashes( $this->options['textdomain'] ) . '/' . sanitize_title_with_dashes( $class_name  ) );
        $raw_icon = isset( $this->options['block-icon'] ) ? $this->options['block-icon'] : 'shield-alt';


        // account for $this->block_element( $this->options['block-output'] ); //@todo we can just change the below to return instead of echo once we are done with conversion
        if ( empty( $this->options['block-edit-return'] ) && ! empty( $this->options['block-output'] ) ) {
            ob_start();
            $this->block_element( $this->options['block-output'] );
            $this->options['block-edit-return'] = rtrim(sanitize_text_field( ob_get_clean()), ',');

        }
        if ( empty( $this->options['block-save-return'] ) && ! empty( $this->options['block-output'] ) ) {
            ob_start();
            ?>el(
            '',
            {},
            <?php $this->block_element( $this->options['block-output'], true ); ?>
            )<?php
            $this->options['block-save-return'] = rtrim( str_replace(["\r\n", "\r", "\n"], '',ob_get_clean()), ',');


            //unset( $this->options['block-output'] );
        }


        // onChangeContent() no longer used, switch to more efficient react useEffect, previewHtml
        if ( isset( $this->options['block-edit-return'] ) && strpos($this->options['block-edit-return'], 'onChangeContent()') !== false ) {
            $this->options['block-edit-return'] = str_replace( "onChangeContent()", "previewHtml", $this->options['block-edit-return'] );
            $this->options['block-ajax-preview'] = true; // set it to ajax preview
        }


        // maybe define the editComponent and saveComponent
        $edit_component_name = '';
        $save_component_name = '';
        if(!empty($this->options['block-edit-return']) ){
            $edit_component_name = 'super_duper_edit_component_' . $this->base_id;
        }
        if(!empty($this->options['block-save-return']) ){
            $save_component_name = 'super_duper_save_component_' . $this->base_id;
        }

        // parse block arguments
        $arguments = $this->get_arguments();
        $arguments = self::parse_block_components( $arguments );

        $config = [
                'name'        => $block_name,
                'base_id'     => $this->id_base,
                'title'       => $this->options['name'],
                'description' => $this->options['widget_ops']['description'],
                'category'    => isset( $this->options['block-category'] ) ? $this->options['block-category'] : 'common',
                'icon'        => $raw_icon, // Pass the simple string; the JS can handle it if needed.
                'keywords'    => isset( $this->options['block-keywords'] ) ? json_decode(str_replace("'", '"', $this->options['block-keywords']), true) : [],
                'arguments'   => $arguments,
                'options'     => [
                        'supports'           => isset($this->options['block-supports']) ? $this->options['block-supports'] : [],
                        'block_group_tabs'   => isset($this->options['block_group_tabs']) ? $this->options['block_group_tabs'] : [],
                        'block-edit-return'  => isset($this->options['block-edit-return']) ? $this->options['block-edit-return'] : null,
                        'block-save-return'  => isset($this->options['block-save-return']) ? $this->options['block-save-return'] : null,
                        'block-dynamic-field'   => isset($this->options['block-dynamic-field']) ? $this->options['block-dynamic-field'] : null,
                        'block-transforms'   => isset($this->options['transforms']) ? $this->options['transforms'] : null,
                        'block-example'   => isset($this->options['example']) ? $this->options['example'] : null,
                        'nested-block'   => isset($this->options['nested-block']) ? $this->options['nested-block'] : null,
                        'block-label'   => isset($this->options['block-label']) ? self::maybe_convert_legacy_label_to_template( $this->options['block-label'] ) : null,
                        'block-wrap'   => isset($this->options['block-wrap']) ? esc_attr( $this->options['block-wrap'] ) : null,
                        'editHook'   => isset($this->options['block-edit-hook']) ? esc_attr( $this->options['block-edit-hook'] ) : null,
                        'block-api-version'   => isset($this->options['block-api-version']) ? $this->options['block-api-version'] : 3,//default to v3?  //@todo we can default to v2 if any issues shown
                        'editComponent'   => $edit_component_name,
                        'saveComponent'   => $save_component_name,
                        'ajaxPreviewInEditComponent' => isset($this->options['block-ajax-preview']) ? true : null,

                ]
        ];

        // 3. Create the security nonce.
        $ajax_nonce = wp_create_nonce( 'super_duper_output_shortcode' );

        // 4. Create the small inline script to register this specific block.
        $script_data = sprintf(
                'jQuery(function() { if (window.registerSuperDuperBlock) { window.registerSuperDuperBlock(%s, "%s"); } });',
                wp_json_encode( $config ),
                esc_js( $ajax_nonce )
        );

        // 5. Attach the data to the handle of our main script.
        wp_add_inline_script( 'super-duper-universal-block-editor', $script_data );


        // block global JS
        if ( method_exists( $this, 'block_global_js' ) ) {
            wp_add_inline_script( 'super-duper-universal-block-editor',$this->block_global_js());
        }


        // maybe add the edit component
        if ( $edit_component_name) {
            wp_add_inline_script( 'super-duper-universal-block-editor',$this->build_edit_component($edit_component_name));
        }

        if ( $save_component_name) {
            wp_add_inline_script( 'super-duper-universal-block-editor',$this->build_save_component($save_component_name));
        }
    }

    public static function parse_block_components($arguments) {
        global $sd_enqueued_block_components;
        if(!empty($arguments)) {
            foreach($arguments as $key => $arg) {
                if ( ! empty( $arg['block_component'] )  ) {

                    // if not built then build
                    if ( empty( $sd_enqueued_block_components[ $arg['block_component'] ] ) ) {
                        $component = self::build_block_component($arg);
                        wp_add_inline_script( 'super-duper-universal-block-editor', $component );
                        $sd_enqueued_block_components[ $arg['block_component'] ] = true;
                    }

                    $new_args = [
                            'name' => isset($arg['name']) ? $arg['name'] : $key,
                            'template' => $arg['block_component'],
                    ];

                    // maybe set a device type
                    if(!empty($arg['device_type'])){
                        $new_args['device_type'] = $arg['device_type'];
                    }
                    // mabe set the row data
                    if(!empty($arg['row'])){
                        $new_args['row'] = $arg['row'];
                    }
                    // mabe set the row data
                    if(!empty($arg['group'])){
                        $new_args['group'] = $arg['group'];
                    }
                    // mabe set the row data
                    if(!empty($arg['element_require'])){
                        $new_args['element_require'] = $arg['element_require'];
                    }
                    // preserve tab markers on block_component fields
                    if(!empty($arg['tab'])){
                        $new_args['tab'] = $arg['tab'];
                    }
                    $arguments[$key] = $new_args;
                }else{
                    // just make sure the name is set from the key
                    $arguments[$key]['name'] = $arg['name'] ?? $key;
                }
            }
        }

        return array_values( $arguments );
    }

    /**
     * Builds a JavaScript component registration script from a PHP argument array.
     *
     * @param array $arguments The full PHP array defining the input field.
     * @return string The generated JavaScript string to be enqueued.
     */
    // In your PHP class...
    public static function build_block_component($arguments) {
        if (empty($arguments['block_component'])) {
            return '';
        }
        $component_name = esc_js($arguments['block_component']);

        $base_config = $arguments;
        unset($base_config['block_component']);
        $json_config = wp_json_encode($base_config);

        // --- THE FIX ---
        // Generate a JS object with both the config and the renderer function.
        $javascript = <<<JS
(function(wp) {
    if (!wp || !window.sdBlockFunctions || typeof window.sdBlockFunctions.renderControl !== 'function') {
        return;
    }

    window.sdBlockInputComponents = window.sdBlockInputComponents || {};

    // Register an OBJECT for the component, not just a function.
    window.sdBlockInputComponents['{$component_name}'] = {

        // 1. Expose the base configuration so the main script can inspect it.
        config: {$json_config},

        // 2. Provide the renderer function like before.
        renderer: function(props, configOverrides, deviceType) {
            // The logic inside the renderer does not change.
            const finalConfig = { ...this.config, ...configOverrides };
            return window.sdBlockFunctions.renderControl(finalConfig, props, deviceType);
        }
    };

})(window.wp);
JS;

        return $javascript;
    }

    /**
     * Converts a legacy Super Duper 'block-label' JavaScript string
     * into the new, safe 'labelTemplate' format.
     *
     * This function parses a JavaScript ternary expression and replaces
     * JS variables and concatenation with simple placeholders.
     *
     * @param string $legacy_string The old JS expression (e.g., "attributes.heading ? 'Title: ' + attributes.heading : 'Untitled'").
     * @return string The new template string (e.g., "%heading% ? Title: %heading% : Untitled").
     */
    public static function maybe_convert_legacy_label_to_template( $legacy_string ) {

        // A helper function to clean up each part of the expression.
        $process_part = function( $str ) {
            // 1. First, replace all "attributes.variableName" with "%variableName%".
            $processed = preg_replace( '/attributes\.([a-zA-Z0-9_]+)/', '%$1%', $str );

            // 2. Split the string by the JS concatenation operator '+'.
            $pieces = explode( '+', $processed );

            // 3. Clean each individual piece.
            $cleaned_pieces = array_map( function( $piece ) {
                // Trim whitespace and the single quotes used to denote a JS string.
                return trim( $piece, " '" );
            }, $pieces );

            // 4. Join the clean pieces back together.
            return implode( '', $cleaned_pieces );
        };

        // Check if the string contains a ternary operator by splitting it.
        $parts = preg_split( '/\s*\?\s*|\s*:\s*/', $legacy_string );

        if ( count( $parts ) === 3 ) {
            // It's a ternary expression: process each of the three parts.
            $condition  = $process_part( $parts[0] );
            $true_part  = $process_part( $parts[1] );
            $false_part = $process_part( $parts[2] );

            return "{$condition} ? {$true_part} : {$false_part}";
        } else {
            // It's not a ternary, so process the entire string as a single part.
            return $process_part( $legacy_string );
        }
    }


    /**
     * Builds the JavaScript for a dynamic block edit component in the Gutenberg editor.
     *
     * This method creates a WordPress block edit component in JavaScript that supports
     * destructuring of props and includes custom global functions for block editing.
     *
     * @param string $name The name of the edit component to be created.
     *                                    It will be used as the key to register the component
     *                                    in the `sdBlockEditComponents` object.
     *
     * @return string The generated JavaScript code wrapped in a <script> tag.
     *                This script defines and registers the edit component for use in
     *                the WordPress block editor.
     */
    public function build_edit_component($name){
        $component = '';
        $elements = rtrim(rtrim($this->options['block-edit-return']),',');

        ob_start();
        // the props list nees to be kept in sysnc with the super duper block editor const tools
        ?>
        <script>
            const <?php echo esc_js($name); ?> = (props) => {
                // Perform the full, messy destructuring once, right here.
                const {
                    // Core Block Properties
                    attributes,setAttributes,isSelected,clientId,
                    // Element Creation & Block Props
                    el,blockProps,
                    parentBlocks, childBlocks,
                    // WordPress Components
                    RichText,BlockControls,InnerBlocks,ToolbarGroup,ToolbarButton,Fragment,
                    // Internationalization
                    __,
                    // Custom State & Handlers
                    isModalOpen,setModalOpen,handleTextChange,handleSelect,updateCaret,blockWrapperRef,previewHtml,
                    // The rest are your global functions from sdBlockFunctions
                    ...customFunctions
                } = props;

                return <?php echo $elements; ?>;
            }

            window.sdBlockEditComponents['<?php echo esc_js($name); ?>'] = <?php echo esc_js($name); ?>;
        </script>
        <?php
        return str_replace( [ '<script>', '</script>' ], '', ob_get_clean() );
    }

    /**
     * Builds the JavaScript for a dynamic block edit component in the Gutenberg editor.
     *
     * This method creates a WordPress block edit component in JavaScript that supports
     * destructuring of props and includes custom global functions for block editing.
     *
     * @param string $name The name of the save component to be created.
     *                                    It will be used as the key to register the component
     *                                    in the `sdBlockEditComponents` object.
     *
     * @return string The generated JavaScript code wrapped in a <script> tag.
     *                This script defines and registers the edit component for use in
     *                the WordPress block editor.
     */
    public function build_save_component($name){
        $component = '';
        $elements = rtrim(rtrim($this->options['block-save-return']),',');
        ob_start();
        // the props list needs to be kept in sysnc with the super duper block editor const tools
        ?>
        <script>

            const <?php echo esc_js($name); ?> = (props) => {
                const {
                    // Core block attribute
                    attributes,
                    // Core WordPress/React tools
                    el,
                    blockProps, // This is the object from useBlockProps.save()
                    parentBlock,childBlocks,
                    RichText,
                    InnerBlocks,
                    Fragment,
                    __,
                    // Your Legacy Super Duper Helper Functions
                    sd_build_aui_class,
                    sd_build_aui_styles,
                    // All other global functions from `window.sdBlockFunctions` are collected here
                    ...globals

                } = props;
                return <?php echo $elements; ?>;
            }

            window.sdBlockSaveComponents['<?php echo esc_js($name); ?>'] = <?php echo esc_js($name); ?>;

        </script>
        <?php
        return str_replace( [ '<script>', '</script>' ], '', ob_get_clean() );
    }

    /**
     * Check if we need to show advanced options for the block.
     *
     * @return bool True if any argument is marked as advanced.
     */
    public function block_show_advanced() {
        $arguments = $this->get_arguments();
        if ( ! empty( $arguments ) ) {
            foreach ( $arguments as $argument ) {
                if ( isset( $argument['advanced'] ) && $argument['advanced'] ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Generate the block icon, enabling Font Awesome support.
     *
     * @param string $icon The icon class or Dashicon slug.
     *
     * @return string The JS representation of the icon.
     */
    public function get_block_icon( $icon ) {
        $fa_type = '';
        if ( strpos( $icon, 'fas fa-' ) === 0 ) {
            $fa_type = 'solid';
        } elseif ( strpos( $icon, 'far fa-' ) === 0 ) {
            $fa_type = 'regular';
        } elseif ( strpos( $icon, 'fab fa-' ) === 0 ) {
            $fa_type = 'brands';
        } else {
            return "'" . esc_js( $icon ) . "'";
        }

        if ( $fa_type ) {
            $fa_icon  = str_replace( array( "fas fa-", "far fa-", "fab fa-" ), "", $icon );
            $icon_url = $this->get_url() . "icons/" . $fa_type . ".svg#" . $fa_icon;

            return "el('svg',{width: 20, height: 20, viewBox: '0 0 20 20'},el('use', {'xlink:href': '" . esc_js( $icon_url ) . "','href': '" . esc_js( $icon_url ) . "'}))";
        }

        return "'" . esc_js( $icon ) . "'";
    }

    /**
     * Group arguments by the 'group' key for creating panels in the block editor.
     *
     * @param array $arguments The arguments to group.
     *
     * @return array The grouped arguments.
     */
    public function group_arguments( $arguments ) {
        if ( ! empty( $arguments ) ) {
            $temp_arguments = array();
            $general        = __( "General", 'ayecode-connect' );
            $add_sections   = false;
            foreach ( $arguments as $key => $args ) {
                if ( isset( $args['group'] ) ) {
                    $temp_arguments[ $args['group'] ][ $key ] = $args;
                    $add_sections                             = true;
                } else {
                    $temp_arguments[ $general ][ $key ] = $args;
                }
            }
            if ( $add_sections ) {
                return $temp_arguments;
            }
        }

        return $arguments;
    }

    public function group_block_tabs( $tabs, $arguments ) {
        if ( ! empty( $tabs ) && ! empty( $arguments ) ) {
            $has_sections = false;

            foreach ( $this->arguments as $key => $args ) {
                if ( isset( $args['group'] ) ) {
                    $has_sections = true;
                    break;
                }
            }

            if ( ! $has_sections ) {
                return $tabs;
            }

            $new_tabs = array();

            foreach ( $tabs as $tab_key => $tab ) {
                $new_groups = array();

                if ( ! empty( $tab['groups'] ) && is_array( $tab['groups'] ) ) {
                    foreach ( $tab['groups'] as $group ) {
                        if ( isset( $arguments[ $group ] ) ) {
                            $new_groups[] = $group;
                        }
                    }
                }

                if ( ! empty( $new_groups ) ) {
                    $tab['groups'] = $new_groups;

                    $new_tabs[ $tab_key ] = $tab;
                }
            }

            $tabs = $new_tabs;
        }

        return $tabs;
    }

    public function block_row_start( $key, $args ) {
        // check for row
        if ( ! empty( $args['row'] ) ) {

            if ( ! empty( $args['row']['open'] ) ) {

                // element require
                $element_require     = ! empty( $args['element_require'] ) ? $this->block_props_replace( $args['element_require'], true ) . " && " : "";
                $device_type         = ! empty( $args['device_type'] ) ? esc_attr( $args['device_type'] ) : '';
                $device_type_require = ! empty( $args['device_type'] ) ? " deviceType == '" . esc_attr( $device_type ) . "' && " : '';
                $device_type_icon    = '';
                if ( $device_type == 'Desktop' ) {
                    $device_type_icon = '<span class="dashicons dashicons-desktop" style="font-size: 18px;" onclick="sd_show_view_options(this);"></span>';
                } elseif ( $device_type == 'Tablet' ) {
                    $device_type_icon = '<span class="dashicons dashicons-tablet" style="font-size: 18px;" onclick="sd_show_view_options(this);"></span>';
                } elseif ( $device_type == 'Mobile' ) {
                    $device_type_icon = '<span class="dashicons dashicons-smartphone" style="font-size: 18px;" onclick="sd_show_view_options(this);"></span>';
                }
                echo $element_require;
                echo $device_type_require;

            if ( false ){
                ?>
                <script><?php }?>
                    el('div', {
                            className: 'bsui components-base-control',
                        },
                            <?php if(! empty( $args['row']['title'] )){ ?>
                        el('label', {
                                className: 'components-base-control__label position-relative',
                                style: {width: "100%"}
                            },
                            el('span', {dangerouslySetInnerHTML: {__html: '<?php echo addslashes( $args['row']['title'] ) ?>'}}),
                                <?php if($device_type_icon){ ?>
                            deviceType == '<?php echo $device_type;?>' && el('span', {
                                dangerouslySetInnerHTML: {__html: '<?php echo $device_type_icon; ?>'},
                                title: deviceType + ": Set preview mode to change",
                                style: {right: "0", position: "absolute", color: "var(--wp-admin-theme-color)"}
                            })
                                <?php
                                }
                                ?>


                        ),
                            <?php }?>
                            <?php if(! empty( $args['row']['desc'] )){ ?>
                        el('p', {
                                className: 'components-base-control__help mb-0',
                            },
                            '<?php echo addslashes( $args['row']['desc'] ); ?>'
                        ),
                            <?php }?>
                        el(
                            'div',
                            {
                                className: 'row mb-n2 <?php if ( ! empty( $args['row']['class'] ) ) {
                                    echo esc_attr( $args['row']['class'] );
                                } ?>',
                            },
                            el(
                                'div',
                                {
                                    className: 'col pr-2 pe-2',
                                },

                    <?php
                    if ( false ){
                    ?></script><?php }
            } elseif ( ! empty( $args['row']['close'] ) ) {
            if ( false ){
                ?>
                <script><?php }?>
                    el(
                        'div',
                        {
                            className: 'col pl-0 ps-0',
                        },
                    <?php
                    if ( false ){
                    ?></script><?php }
            } else {
            if ( false ){
                ?>
                <script><?php }?>
                    el(
                        'div',
                        {
                            className: 'col pl-0 ps-0 pr-2 pe-2',
                        },
                    <?php
                    if ( false ){
                    ?></script><?php }
            }

        }
    }

    public function block_row_end( $key, $args ) {

        if ( ! empty( $args['row'] ) ) {
            // maybe close
            if ( ! empty( $args['row']['close'] ) ) {
                echo "))";
            }

            echo "),";
        }
    }

    public function block_tab_start( $key, $args ) {

        // check for row
        if ( ! empty( $args['tab'] ) ) {

            if ( ! empty( $args['tab']['tabs_open'] ) ) {

            if ( false ){
                ?>
                <script><?php }?>

                    el('div', {className: 'bsui'},

                        el('hr', {className: 'm-0'}), el(
                            wp.components.TabPanel,
                            {
                                activeClass: 'is-active',
                                className: 'btn-groupx',
                                initialTabName: '<?php echo addslashes( esc_attr( $args['tab']['key'] ) ); ?>',
                                tabs: [

                    <?php
                    if ( false ){
                    ?></script><?php }
            }

            if ( ! empty( $args['tab']['open'] ) ) {

            if ( false ){
                ?>
                <script><?php }?>
                    {
                        name: '<?php echo addslashes( esc_attr( $args['tab']['key'] ) ); ?>',
                            title
                    :
                        el('div', {dangerouslySetInnerHTML: {__html: '<?php echo addslashes( esc_attr( $args['tab']['title'] ) ); ?>'}}),
                            className
                    :
                        '<?php echo addslashes( esc_attr( $args['tab']['class'] ) ); ?>',
                            content
                    :
                        el('div', {}, <?php if(! empty( $args['tab']['desc'] )){ ?>el('p', {
                            className: 'components-base-control__help mb-0',
                            dangerouslySetInnerHTML: {__html: '<?php echo addslashes( $args['tab']['desc'] ); ?>'}
                        }),<?php }
                    if ( false ){
                    ?></script><?php }
            }

        }

    }

    public function block_tab_end( $key, $args ) {

        if ( ! empty( $args['tab'] ) ) {
            // maybe close
            if ( ! empty( $args['tab']['close'] ) ) {
                echo ")}, /* tab close */";
            }

            if ( ! empty( $args['tab']['tabs_close'] ) ) {
					if(false){?><script><?php }?>
						]}, ( tab ) => {
								return tab.content;
							}
						)), /* tabs close */
					<?php if(false){ ?></script><?php }
				}
        }
    }



    /**
     * A self looping function to create the output for JS block elements.
     *
     * This is what is output in the WP Editor visual view.
     *
     * @param $args
     */
    public function block_element( $args, $save = false ) {

//            print_r($args);echo '###';exit;

        if ( ! empty( $args ) ) {
            foreach ( $args as $element => $new_args ) {

                if ( is_array( $new_args ) ) { // its an element


                    if ( isset( $new_args['element'] ) ) {

                        if ( isset( $new_args['element_require'] ) ) {
                            echo str_replace( array(
                                            "'+",
                                            "+'"
                                    ), '', $this->block_props_replace( $new_args['element_require'] ) ) . " &&  ";
                            unset( $new_args['element_require'] );
                        }

                        if ( $new_args['element'] == 'InnerBlocks' ) {
                            echo "\n el( InnerBlocks, {";
                        } elseif ( $new_args['element'] == 'innerBlocksProps' ) {
                            if ( isset( $new_args['if_inner_element'] ) ) {
                                $element = $new_args['if_inner_element'];
                            } else {
                                $element = isset( $new_args['inner_element'] ) ? "'" . esc_attr( $new_args['inner_element'] ) . "'" : "'div'";
                            }
                            //  echo "\n el( 'section', wp.blockEditor.useInnerBlocksProps( blockProps, {";
//                                echo $save ? "\n el( '$element', wp.blockEditor.useInnerBlocksProps.save( " : "\n el( '$element', wp.blockEditor.useInnerBlocksProps( ";
                            echo $save ? "\n el( $element, wp.blockEditor.useInnerBlocksProps.save( " : "\n el( $element, wp.blockEditor.useInnerBlocksProps( ";
                            echo $save ? "wp.blockEditor.useBlockProps.save( {" : "wp.blockEditor.useBlockProps( {";
                            echo ! empty( $new_args['blockProps'] ) ? $this->block_element( $new_args['blockProps'], $save ) : '';

                            echo "} ), {";
                            echo ! empty( $new_args['innerBlocksProps'] ) && ! $save ? $this->block_element( $new_args['innerBlocksProps'], $save ) : '';
                            //    echo '###';

                            //  echo '###';
                        } elseif ( $new_args['element'] == 'BlocksProps' ) {

                            if ( isset( $new_args['if_inner_element'] ) ) {
                                $element = $new_args['if_inner_element'];
                            } else {
                                $element = isset( $new_args['inner_element'] ) ? "'" . esc_attr( $new_args['inner_element'] ) . "'" : "'div'";
                            }

                            unset( $new_args['inner_element'] );
                            echo $save ? "\n el( $element, wp.blockEditor.useBlockProps.save( {" : "\n el( $element, wp.blockEditor.useBlockProps( {";
//							echo $save ? "\n el( $element, {...blockProps," : "\n el( $element, {...blockProps,";
                            echo ! empty( $new_args['blockProps'] ) ? $this->block_element( $new_args['blockProps'], $save ) : '';


                            // echo "} ),";

                        } elseif($new_args['element'] == 'Fragment' ) {
                            echo "\n el( Fragment, {";
                        } else {
                            echo "\n el( '" . $new_args['element'] . "', {";
                        }


                        // get the attributes
                        foreach ( $new_args as $new_key => $new_value ) {


                            if ( $new_key == 'element' || $new_key == 'content' || $new_key == 'if_content' || $new_key == 'element_require' || $new_key == 'element_repeat' || is_array( $new_value ) ) {
                                // do nothing
                            } else {
                                echo $this->block_element( array( $new_key => $new_value ), $save );
                            }
                        }

                        echo $new_args['element'] == 'BlocksProps' ? '} ),' : "},";// end attributes
//						echo $new_args['element'] == 'BlocksProps' ? '},' : "},";// end attributes

                        // get the content
                        $first_item = 0;
                        foreach ( $new_args as $new_key => $new_value ) {
                            if ( $new_key === 'content' || $new_key === 'if_content' || is_array( $new_value ) ) {

                                if ( $new_key === 'content' ) {
                                    echo "'" . $this->block_props_replace( wp_slash( $new_value ) ) . "'";
                                } else if ( $new_key === 'if_content' ) {
                                    echo $this->block_props_replace( $new_value );
                                }

                                if ( is_array( $new_value ) ) {

                                    if ( isset( $new_value['element_require'] ) ) {
                                        echo str_replace( array(
                                                        "'+",
                                                        "+'"
                                                ), '', $this->block_props_replace( $new_value['element_require'] ) ) . " &&  ";
                                        unset( $new_value['element_require'] );
                                    }

                                    if ( isset( $new_value['element_repeat'] ) ) {
                                        $x = 1;
                                        while ( $x <= absint( $new_value['element_repeat'] ) ) {
                                            $this->block_element( array( '' => $new_value ), $save );
                                            $x ++;
                                        }
                                    } else {
                                        $this->block_element( array( '' => $new_value ), $save );
                                    }
                                }
                                $first_item ++;
                            }
                        }

                        if ( $new_args['element'] == 'innerBlocksProps' || $new_args['element'] == 'xBlocksProps' ) {
                            echo "))";// end content
                        } else {
                            echo ")";// end content
                        }


                        echo ", \n";

                    }
                } else {

                    if ( substr( $element, 0, 3 ) === "if_" ) {
                        $extra = '';
                        if ( strpos( $new_args, '[%WrapClass%]' ) !== false ) {
                            $new_args = str_replace( '[%WrapClass%]"', '" + sd_build_aui_class(props.attributes)', $new_args );
                            $new_args = str_replace( '[%WrapClass%]', '+ sd_build_aui_class(props.attributes)', $new_args );
                        }
                        echo str_replace( "if_", "", $element ) . ": " . $this->block_props_replace( $new_args, true ) . ",";
                    } elseif ( $element == 'style' && strpos( $new_args, '[%WrapStyle%]' ) !== false ) {
                        $new_args = str_replace( '[%WrapStyle%]', '', $new_args );
                        echo $element . ": {..." . $this->block_props_replace( $new_args ) . " , ...sd_build_aui_styles(props.attributes) },";
//                            echo $element . ": " . $this->block_props_replace( $new_args ) . ",";
                    } elseif ( $element == 'style' ) {
                        echo $element . ": " . $this->block_props_replace( $new_args ) . ",";
                    } elseif ( ( $element == 'class' || $element == 'className' ) && strpos( $new_args, '[%WrapClass%]' ) !== false ) {
                        $new_args = str_replace( '[%WrapClass%]', '', $new_args );
                        echo $element . ": '" . $this->block_props_replace( $new_args ) . "' + sd_build_aui_class(props.attributes),";
                    } elseif ( $element == 'template' && $new_args ) {
                        echo $element . ": $new_args,";
                    } else {
                        echo $element . ": '" . $this->block_props_replace( $new_args ) . "',";
                    }

                }
            }
        }
    }


}
