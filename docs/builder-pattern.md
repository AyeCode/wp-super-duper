# Builder Pattern: BlockArguments

## Overview

`AyeCode\SuperDuper\Builder\BlockArguments` is a fluent builder that generates the `arguments` array for a Super Duper class using a chainable API. It replaces the raw `arguments` array in constructor options with a self-documenting, IDE-friendly alternative.

## Usage

Implement `set_arguments()` in your class and return a `BlockArguments::get()` array:

```php
class My_Widget extends WP_Super_Duper {

    public function __construct() {
        parent::__construct( array(
            'textdomain'     => 'ayecode-connect',
            'block-icon'     => 'dashicons-admin-site',
            'block-category' => 'widgets',
            'class_name'     => __CLASS__,
            'base_id'        => 'my_widget',
            'name'           => __( 'My Widget', 'ayecode-connect' ),
            'widget_ops'     => array(
                'classname'   => 'my-widget',
                'description' => __( 'Example widget', 'ayecode-connect' ),
            ),
            // No 'arguments' key needed when using set_arguments()
        ) );
    }

    public function set_arguments(): array {
        return ( new \AyeCode\SuperDuper\Builder\BlockArguments() )
            // Custom fields
            ->add_field( 'title', array(
                'title'   => __( 'Title', 'ayecode-connect' ),
                'type'    => 'text',
                'default' => '',
            ) )
            // Background color, custom color, gradient, and image
            ->add_background_group()
            // Margins — use add_responsive_margins() for mobile/tablet/desktop breakpoints
            ->add_margins()
            // Padding — use add_responsive_paddings() for mobile/tablet/desktop breakpoints
            ->add_padding()
            // Border color, radius type, and radius size
            ->add_border_group()
            // Box shadow
            ->add_shadow_group()
            // Display (mobile only) — use add_responsive_display_group() for all breakpoints
            ->add_display_group()
            // Font size, weight, case, italic, line height, text align, text color
            ->add_typography_group()
            // Text color only (subset of typography — use instead of add_typography_group() when only color is needed)
            // ->add_colors_group()
            // Container class and position class
            ->add_layout_group()
            // Sticky top/bottom offset (shown when position is sticky)
            ->add_sticky_offset_group()
            // Block visibility conditions
            ->add_visibility_conditions()
            // Additional CSS class, HTML anchor, custom block name
            ->add_class_and_anchor()
            ->get();
    }

    public function output( $args = array(), $widget_args = array(), $content = '' ) {
        extract( $args, EXTR_SKIP );
        return '<div>' . esc_html( $title ) . '</div>';
    }
}
```

`set_arguments()` is called by the `Initializer` trait. Its result is merged after any `arguments` key already present in `$options` constructor array (builder takes precedence on conflicts).

## Available Methods

### Core

| Method | Description |
|---|---|
| `add_field( string $name, array $config ): self` | Add a single field definition keyed by `$name` |
| `add_fields( array $fields ): self` | Merge multiple field definitions (key → config pairs) |
| `get(): array` | Return the compiled arguments array |

### Spacing

| Method | Description |
|---|---|
| `add_margins( array $overwrite = [], bool $include_negatives = true ): self` | Add `mt`/`mr`/`mb`/`ml` margin selects |
| `add_responsive_margins( array $overwrite = [], bool $include_negatives = true, string $mb_lg_default = '3' ): self` | Add margins for mobile, tablet (`_md`), and desktop (`_lg`) breakpoints. `mb_lg` defaults to `'3'`; pass `''` to leave unset |
| `add_padding( array $overwrite = [] ): self` | Add `pt`/`pr`/`pb`/`pl` padding selects |
| `add_responsive_paddings( array $overwrite = [] ): self` | Add paddings for mobile, tablet (`_md`), and desktop (`_lg`) breakpoints |

### Styles

| Method | Description |
|---|---|
| `add_border_group( string $prefix = '', array $overwrite = [] ): self` | Border color, border show/type, border width, border opacity, radius type, and radius size selects |
| `add_shadow_group( string $prefix = '', array $overwrite = [] ): self` | Shadow select |
| `add_background_group( string $prefix = '', array $overwrite = [], bool $include_image = true ): self` | Background color, custom color, gradient, and image fields. Pass `false` for `$include_image` to omit the image picker fields |
| `add_display_group( string $prefix = '', array $overwrite = [] ): self` | Display select (mobile only — none, flex, block, inline-flex, etc.) |
| `add_responsive_display_group( string $prefix = '', array $overwrite = [] ): self` | Display selects for mobile, tablet (`_md`), and desktop (`_lg`) |

### Typography

| Method | Description |
|---|---|
| `add_typography_group( string $prefix = '', array $overwrite = [] ): self` | Font size + custom size, font weight, letter case, italic, line height, text justify, text align (hidden when justify is set), text color + custom color |
| `add_colors_group( string $prefix = '', array $overwrite = [] ): self` | Text color + custom color picker (subset of typography group) |

### Layout

| Method | Description |
|---|---|
| `add_layout_group( string $prefix = '', array $overwrite = [] ): self` | Container class select and position class select |

### Utilities

| Method | Description |
|---|---|
| `add_sticky_offset_group( string $prefix = '', array $overwrite = [] ): self` | Sticky offset top and bottom fields (shown when position is sticky) |
| `add_visibility_conditions( string $prefix = '', array $overwrite = [] ): self` | Block visibility conditions field |
| `add_class_and_anchor( string $prefix = '', array $overwrite = [] ): self` | Additional CSS class(es), HTML anchor, and custom block name fields |

## Calling `Fields\*` Static Classes Directly

Each `add_*` method delegates to a `Fields\*` static class. You can call these directly when you need a single field without the builder:

```php
use AyeCode\SuperDuper\Fields\SpacingFields;
use AyeCode\SuperDuper\Fields\CommonFields;

$mt_field = SpacingFields::margin_input( 'mt' );
$anchor   = CommonFields::anchor_input( 'anchor', array( 'group' => 'Custom Group' ) );
```

| Static class | Methods |
|---|---|
| `Fields\SpacingFields` | `margin_input( $type, $overwrite, $include_negatives )`, `padding_input( $type, $overwrite )` |
| `Fields\StyleFields` | `border_input( $type, $overwrite )`, `shadow_input( $type, $overwrite )`, `background_inputs( $type, $overwrite, ... )`, `background_input( $type, $overwrite )`, `display_input( $type, $overwrite )`, `opacity_input( $type, $overwrite )`, `hover_animations_input( $type, $overwrite )`, `hover_icon_animation_input( $type, $overwrite )`, `zindex_input( $type, $overwrite )`, `overflow_input( $type, $overwrite )`, `scrollbars_input( $type, $overwrite )` |
| `Fields\TypographyFields` | `font_size_input_group()`, `font_size_input()`, `font_custom_size_input()`, `font_weight_input()`, `font_case_input()`, `font_italic_input()`, `font_line_height_input()`, `text_justify_input()`, `text_align_input()`, `text_align_input_group()`, `text_color_input_group()`, `text_color_input()`, `custom_color_input()` |
| `Fields\LayoutFields` | `container_class_input( $type, $overwrite )`, `position_class_input( $type, $overwrite )`, `sticky_offset_input( $type, $overwrite )`, `col_input( $type, $overwrite )`, `row_cols_input( $type, $overwrite )`, `absolute_position_input( $type, $overwrite )`, `width_input( $type, $overwrite )`, `height_input( $type, $overwrite )`, `max_height_input( $type, $overwrite )` |
| `Fields\CommonFields` | `class_input()`, `anchor_input()`, `custom_name_input()`, `visibility_conditions_input()`, `new_window_input()`, `nofollow_input()`, `attributes_input()`, `title_tag_input()`, `html_tag_input()`, `title_inputs()` |
| `Fields\ColorFields` | `aui_colors( $include_branding, $include_outlines, ... )`, `get_aui_colors( $types, $flatten )`, `branding_colors()` |
| `Fields\ShapeFields` | `divider_inputs( $type, $overwrite, ... )`, `element_require_string( $args, $key, $type )` |
| `Fields\FlexFields` | `align_items_input( $type, $overwrite )`, `align_items_group( $type, $overwrite )`, `justify_content_input( $type, $overwrite )`, `justify_content_group( $type, $overwrite )`, `align_self_input( $type, $overwrite )`, `align_self_group( $type, $overwrite )`, `order_input( $type, $overwrite )`, `order_group( $type, $overwrite )`, `wrap_input( $type, $overwrite )`, `wrap_group( $type, $overwrite )`, `float_input( $type, $overwrite )`, `float_group( $type, $overwrite )` |

## Deprecated Global Functions

All `sd_get_*` global functions are soft-deprecated since 3.1.0. They still work but call the `Fields\*` static classes internally and emit `_deprecated_function()` notices when `WP_DEBUG` is enabled. Migration examples:

| Deprecated global | Replacement |
|---|---|
| `sd_get_margin_input( 'mt' )` | `SpacingFields::margin_input( 'mt' )` |
| `sd_get_padding_input( 'pt' )` | `SpacingFields::padding_input( 'pt' )` |
| `sd_get_border_input( 'border' )` | `StyleFields::border_input( 'border' )` |
| `sd_get_shadow_input()` | `StyleFields::shadow_input()` |
| `sd_get_background_inputs()` | `StyleFields::background_inputs()` |
| `sd_get_display_input()` | `StyleFields::display_input()` |
| `sd_get_text_color_input_group()` | `TypographyFields::text_color_input_group()` |
| `sd_get_text_align_input()` | `TypographyFields::text_align_input()` |
| `sd_get_font_size_input_group()` | `TypographyFields::font_size_input_group()` |
| `sd_get_font_weight_input()` | `TypographyFields::font_weight_input()` |
| `sd_get_font_case_input()` | `TypographyFields::font_case_input()` |
| `sd_get_font_italic_input()` | `TypographyFields::font_italic_input()` |
| `sd_get_container_class_input()` | `LayoutFields::container_class_input()` |
| `sd_get_position_class_input()` | `LayoutFields::position_class_input()` |
| `sd_get_class_input()` | `CommonFields::class_input()` |
| `sd_get_anchor_input()` | `CommonFields::anchor_input()` |
| `sd_get_custom_name_input()` | `CommonFields::custom_name_input()` |
| `sd_get_visibility_conditions_input()` | `CommonFields::visibility_conditions_input()` |
| `sd_get_font_line_height_input()` | `TypographyFields::font_line_height_input()` |
| `sd_get_text_justify_input()` | `TypographyFields::text_justify_input()` |
| `sd_get_sticky_offset_input()` | `LayoutFields::sticky_offset_input()` |
| `sd_get_col_input()` | `LayoutFields::col_input()` |
| `sd_get_row_cols_input()` | `LayoutFields::row_cols_input()` |
| `sd_get_absolute_position_input()` | `LayoutFields::absolute_position_input()` |
| `sd_get_width_input()` | `LayoutFields::width_input()` |
| `sd_get_height_input()` | `LayoutFields::height_input()` |
| `sd_get_max_height_input()` | `LayoutFields::max_height_input()` |
| `sd_get_background_input()` | `StyleFields::background_input()` |
| `sd_get_opacity_input()` | `StyleFields::opacity_input()` |
| `sd_get_hover_animations_input()` | `StyleFields::hover_animations_input()` |
| `sd_get_hover_icon_animation_input()` | `StyleFields::hover_icon_animation_input()` |
| `sd_get_zindex_input()` | `StyleFields::zindex_input()` |
| `sd_get_overflow_input()` | `StyleFields::overflow_input()` |
| `sd_get_scrollbars_input()` | `StyleFields::scrollbars_input()` |
| `sd_get_new_window_input()` | `CommonFields::new_window_input()` |
| `sd_get_nofollow_input()` | `CommonFields::nofollow_input()` |
| `sd_get_attributes_input()` | `CommonFields::attributes_input()` |
| `sd_get_title_tag_input()` | `CommonFields::title_tag_input()` |
| `sd_get_html_tag_input()` | `CommonFields::html_tag_input()` |
| `sd_get_title_inputs()` | `CommonFields::title_inputs()` |
| `sd_get_aui_colors()` | `ColorFields::get_aui_colors()` |
| `sd_get_branding_colors()` | `ColorFields::branding_colors()` |
| `sd_get_shape_divider_inputs()` | `ShapeFields::divider_inputs()` |
| `sd_get_element_require_string()` | `ShapeFields::element_require_string()` |
| `sd_get_flex_align_items_input()` | `FlexFields::align_items_input()` |
| `sd_get_flex_justify_content_input()` | `FlexFields::justify_content_input()` |
| `sd_get_flex_align_self_input()` | `FlexFields::align_self_input()` |
| `sd_get_flex_order_input()` | `FlexFields::order_input()` |
| `sd_get_flex_wrap_input()` | `FlexFields::wrap_input()` |
| `sd_get_float_input()` | `FlexFields::float_input()` |

## See Also

- [API Reference](api-reference.md)
- [Examples](examples.md)
- [hello-world.php](../hello-world.php)
