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
| `add_responsive_margins( array $overwrite = [], bool $include_negatives = true, array $per_field = [] ): self` | Add margins for mobile, tablet (`_md`), and desktop (`_lg`) breakpoints. `mb_lg` defaults to `'3'`; pass `array( 'mb_lg' => array( 'default' => '' ) )` to clear it |
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
| `add_position( string $prefix = '', array $overwrite = [] ): self` | Position class select (static, relative, absolute, fixed, sticky) |
| `add_layout_group( string $prefix = '', array $overwrite = [] ): self` | Container class select and position class select |

### Utilities

| Method | Description |
|---|---|
| `add_sticky_offset_group( string $prefix = '', array $overwrite = [] ): self` | Sticky offset top and bottom fields (shown when position is sticky) |
| `add_visibility_conditions( string $prefix = '', array $overwrite = [] ): self` | Block visibility conditions field |
| `add_advanced_group( string $prefix = '', array $overwrite = [] ): self` | Additional CSS class(es) and custom block name — standard "Advanced" tab fields |
| `add_class_and_anchor( string $prefix = '', array $overwrite = [] ): self` | Additional CSS class(es), HTML anchor, and custom block name fields |

## Calling `Fields\*` Static Classes Directly

Each `add_*` method delegates to a `Fields\*` static class. You can call these directly when you need a single field without the builder:

```php
use AyeCode\SuperDuper\Fields\SpacingFields;
use AyeCode\SuperDuper\Fields\CommonFields;

$mt_field = SpacingFields::margin( 'top' );
$anchor   = CommonFields::anchor( array( 'group' => 'Custom Group' ) );
```

| Static class | Methods |
|---|---|
| `Fields\SpacingFields` | `margin( $side, $overwrite, $include_negatives )`, `padding( $side, $overwrite )`, `margin_group( $overwrite, $include_negatives )`, `padding_group( $overwrite )` |
| `Fields\StyleFields` | `border_show()`, `border_style()`, `border_width()`, `border_opacity()`, `border_radius()`, `border_radius_size()`, `border_group( $prefix, $overwrite )`, `shadow()`, `background()`, `background_group( $prefix, $overwrite, $field_overwrites )`, `display()`, `opacity()`, `hover_animation()`, `hover_icon_animation()`, `zindex()`, `overflow()`, `scrollbars()` |
| `Fields\TypographyFields` | `font_size()`, `font_size_custom()`, `font_weight()`, `font_case()`, `font_italic()`, `line_height()`, `text_justify()`, `text_align()`, `text_color()`, `text_color_custom()`, `font_size_group( $prefix, $overwrite, $overwrite_custom )`, `text_align_group( $prefix, $overwrite )`, `text_color_group( $prefix, $overwrite, $overwrite_custom )` |
| `Fields\LayoutFields` | `container()`, `position()`, `sticky_offset( $side )`, `col()`, `row_cols()`, `absolute_position()`, `width()`, `height()`, `max_height()` |
| `Fields\CommonFields` | `css_class()`, `anchor()`, `metadata_name()`, `visibility_conditions()`, `new_window()`, `nofollow()`, `attributes()`, `title_tag()`, `html_tag()`, `title_group()` |
| `Helpers\ColorOptions` | `aui( $types, $flatten )`, `branding()` — returns option arrays for `select`/`color` fields, not field definitions |
| `Fields\ShapeFields` | `divider_group( $prefix, $overwrite )` |
| `Fields\FlexFields` | `align_items()`, `align_items_group( $prefix, $overwrite )`, `justify_content()`, `justify_content_group( $prefix, $overwrite )`, `align_self()`, `align_self_group( $prefix, $overwrite )`, `order()`, `order_group( $prefix, $overwrite )`, `flex_wrap()`, `flex_wrap_group( $prefix, $overwrite )`, `float()`, `float_group( $prefix, $overwrite )` |

## Deprecated Global Functions

All `sd_get_*` global functions are soft-deprecated since 3.1.0. They still work but call the `Fields\*` static classes internally and emit `_deprecated_function()` notices when `WP_DEBUG` is enabled. Migration examples:

| Deprecated global | Replacement |
|---|---|
| `sd_get_margin_input( 'mt' )` | `SpacingFields::margin( 'top' )` |
| `sd_get_padding_input( 'pt' )` | `SpacingFields::padding( 'top' )` |
| `sd_get_border_input( 'border' )` | `StyleFields::border_show()` |
| `sd_get_shadow_input()` | `StyleFields::shadow()` |
| `sd_get_background_inputs()` | `StyleFields::background_group()` |
| `sd_get_display_input()` | `StyleFields::display()` |
| `sd_get_text_color_input_group()` | `TypographyFields::text_color_group()` |
| `sd_get_text_align_input()` | `TypographyFields::text_align()` |
| `sd_get_font_size_input_group()` | `TypographyFields::font_size_group()` |
| `sd_get_font_weight_input()` | `TypographyFields::font_weight()` |
| `sd_get_font_case_input()` | `TypographyFields::font_case()` |
| `sd_get_font_italic_input()` | `TypographyFields::font_italic()` |
| `sd_get_container_class_input()` | `LayoutFields::container()` |
| `sd_get_position_class_input()` | `LayoutFields::position()` |
| `sd_get_class_input()` | `CommonFields::css_class()` |
| `sd_get_anchor_input()` | `CommonFields::anchor()` |
| `sd_get_custom_name_input()` | `CommonFields::metadata_name()` |
| `sd_get_visibility_conditions_input()` | `CommonFields::visibility_conditions()` |
| `sd_get_font_line_height_input()` | `TypographyFields::line_height()` |
| `sd_get_text_justify_input()` | `TypographyFields::text_justify()` |
| `sd_get_sticky_offset_input()` | `LayoutFields::sticky_offset( 'top' )` |
| `sd_get_col_input()` | `LayoutFields::col()` |
| `sd_get_row_cols_input()` | `LayoutFields::row_cols()` |
| `sd_get_absolute_position_input()` | `LayoutFields::absolute_position()` |
| `sd_get_width_input()` | `LayoutFields::width()` |
| `sd_get_height_input()` | `LayoutFields::height()` |
| `sd_get_max_height_input()` | `LayoutFields::max_height()` |
| `sd_get_background_input()` | `StyleFields::background()` |
| `sd_get_opacity_input()` | `StyleFields::opacity()` |
| `sd_get_hover_animations_input()` | `StyleFields::hover_animation()` |
| `sd_get_hover_icon_animation_input()` | `StyleFields::hover_icon_animation()` |
| `sd_get_zindex_input()` | `StyleFields::zindex()` |
| `sd_get_overflow_input()` | `StyleFields::overflow()` |
| `sd_get_scrollbars_input()` | `StyleFields::scrollbars()` |
| `sd_get_new_window_input()` | `CommonFields::new_window()` |
| `sd_get_nofollow_input()` | `CommonFields::nofollow()` |
| `sd_get_attributes_input()` | `CommonFields::attributes()` |
| `sd_get_title_tag_input()` | `CommonFields::title_tag()` |
| `sd_get_html_tag_input()` | `CommonFields::html_tag()` |
| `sd_get_title_inputs()` | `CommonFields::title_group()` |
| `sd_get_aui_colors()` | `ColorOptions::aui()` |
| `sd_get_branding_colors()` | `ColorOptions::branding()` |
| `sd_get_shape_divider_inputs()` | `ShapeFields::divider_group()` |
| `sd_get_element_require_string()` | `Utils::element_require()` |
| `sd_get_flex_align_items_input()` | `FlexFields::align_items()` |
| `sd_get_flex_justify_content_input()` | `FlexFields::justify_content()` |
| `sd_get_flex_align_self_input()` | `FlexFields::align_self()` |
| `sd_get_flex_order_input()` | `FlexFields::order()` |
| `sd_get_flex_wrap_input()` | `FlexFields::flex_wrap()` |
| `sd_get_float_input()` | `FlexFields::float()` |

## See Also

- [API Reference](api-reference.md)
- [Examples](examples.md)
- [hello-world.php](../hello-world.php)
