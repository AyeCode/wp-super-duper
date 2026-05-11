# Fields Reference

All field classes live under `AyeCode\SuperDuper\Fields\`.
The options helper lives under `AyeCode\SuperDuper\Helpers\`.

All methods are static factory methods that return field definition arrays.

**Taxonomy rules:**

| Kind | Returns | Method suffix | First param |
|---|---|---|---|
| **Field** | one field definition `array` | none | `$overwrite = []` |
| **Group** | `array<string,array>` keyed field definitions | `_group` | `$prefix = ''`, then `$overwrite = []` |
| **Options helper** | option arrays for `select`/`color` `options` key | — | lives in `src/Helpers/` |
| **Utility** | string or other non-field output | — | lives in `src/Utils.php` |

---

## SpacingFields

### Single-field methods

| Method | Field key | Notes |
|---|---|---|
| `margin( string $side, array $overwrite = [], bool $include_negatives = true )` | `mt` / `mr` / `mb` / `ml` | `$side`: `'top'` `'right'` `'bottom'` `'left'` |
| `padding( string $side, array $overwrite = [] )` | `pt` / `pr` / `pb` / `pl` | `$side`: `'top'` `'right'` `'bottom'` `'left'` |

### Group methods

| Method | Returns | Notes |
|---|---|---|
| `margin_group( array $overwrite = [], bool $include_negatives = true )` | `['mt'=>..., 'mr'=>..., 'mb'=>..., 'ml'=>...]` | |
| `padding_group( array $overwrite = [] )` | `['pt'=>..., 'pr'=>..., 'pb'=>..., 'pl'=>...]` | |

---

## StyleFields

### Border — single-field methods

Each dependent field's `element_require` defaults to `([%border%]&&[%border%]!="0")`.
When using `border_group()` with a prefix, the condition is updated automatically.

| Method | Description |
|---|---|
| `border_show( array $overwrite = [] )` | Border color select |
| `border_style( array $overwrite = [] )` | Border show/sides select (full, top, bottom, …) |
| `border_width( array $overwrite = [] )` | Border width select |
| `border_opacity( array $overwrite = [] )` | Border opacity select |
| `border_radius( array $overwrite = [] )` | Border radius type select |
| `border_radius_size( array $overwrite = [] )` | Border radius size select |

### Other single-field methods

| Method | Description |
|---|---|
| `shadow( array $overwrite = [] )` | Box shadow select |
| `display( array $overwrite = [] )` | Display select — pass `'device_type'` for responsive variants |
| `background( array $overwrite = [] )` | Single background color select |
| `opacity( array $overwrite = [] )` | Opacity select |
| `hover_animation( array $overwrite = [] )` | Hover animation multi-select |
| `hover_icon_animation( array $overwrite = [] )` | Hover icon animation select |
| `zindex( array $overwrite = [] )` | Z-index select |
| `overflow( array $overwrite = [] )` | Overflow select |
| `scrollbars( array $overwrite = [] )` | Scrollbar style select |

### Group methods

| Method | Returns | Notes |
|---|---|---|
| `border_group( string $prefix = '', array $overwrite = [] )` | `['border'=>..., 'border_type'=>..., 'border_width'=>..., 'border_opacity'=>..., 'rounded'=>..., 'rounded_size'=>...]` | Prefix is prepended to all keys; `element_require` references updated automatically |
| `background_group( string $prefix = 'bg', array $overwrite = [], array $field_overwrites = [], bool $include_button_colors = false )` | Color select + custom color picker + gradient + image fields | `$overwrite` applies to all fields; `$field_overwrites` keys: `'color'`, `'gradient'`, `'image'`. Pass `false` as a key value to omit that sub-field |

---

## TypographyFields

### Single-field methods

| Method | Description |
|---|---|
| `font_size( array $overwrite = [] )` | Font size select (no custom-size option) |
| `font_size_custom( array $overwrite = [] )` | Custom font size number input — wire `element_require` via `$overwrite` |
| `font_weight( array $overwrite = [] )` | Font weight / appearance select |
| `font_case( array $overwrite = [] )` | Letter case (text-transform) select |
| `font_italic( array $overwrite = [] )` | Font italic select |
| `line_height( array $overwrite = [] )` | Line height number input |
| `text_justify( array $overwrite = [] )` | Text justify checkbox |
| `text_align( array $overwrite = [] )` | Text align select — pass `'device_type'` for responsive variants |
| `text_color( array $overwrite = [] )` | Text color select (no custom option) |
| `text_color_custom( array $overwrite = [] )` | Custom color picker — wire `element_require` via `$overwrite` |

### Group methods

| Method | Returns | Notes |
|---|---|---|
| `font_size_group( string $prefix = 'font_size', array $overwrite = [], $overwrite_custom = [] )` | `[$prefix => ..., $prefix.'_custom' => ...]` | Main select includes "Custom size" option; custom field has auto-wired `element_require`. Pass `false` for `$overwrite_custom` to omit the custom field |
| `text_align_group( string $prefix = 'text_align', array $overwrite = [] )` | `[$prefix => ..., $prefix.'_md' => ..., $prefix.'_lg' => ...]` | Mobile / Tablet / Desktop responsive fields |
| `text_color_group( string $prefix = 'text_color', array $overwrite = [], $overwrite_custom = [] )` | `[$prefix => ..., $prefix.'_custom' => ...]` | Main select includes "Custom color" option; picker has auto-wired `element_require`. Pass `false` for `$overwrite_custom` to omit the custom field |

---

## LayoutFields

### Single-field methods

| Method | Description |
|---|---|
| `container( array $overwrite = [] )` | Container width / type select |
| `position( array $overwrite = [] )` | Position class select (static, relative, absolute, fixed, sticky) |
| `sticky_offset( string $side, array $overwrite = [] )` | Sticky offset number — `$side`: `'top'` or `'bottom'` |
| `col( array $overwrite = [] )` | Bootstrap column width select |
| `row_cols( array $overwrite = [] )` | Row columns select |
| `absolute_position( array $overwrite = [] )` | Absolute position select |
| `width( array $overwrite = [] )` | Width select — pass `'device_type'` for responsive variants |
| `height( array $overwrite = [] )` | Height select — pass `'device_type'` for responsive variants |
| `max_height( array $overwrite = [] )` | Max-height text input |

---

## CommonFields

### Single-field methods

| Method | Description |
|---|---|
| `css_class( array $overwrite = [] )` | Additional CSS class(es) text input |
| `anchor( array $overwrite = [] )` | HTML anchor ID text input |
| `metadata_name( array $overwrite = [] )` | Custom block name text input |
| `visibility_conditions( array $overwrite = [] )` | Block visibility conditions UI field |
| `hidden( array $overwrite = [] )` | Generic hidden input — no UI rendered, value stored in attributes |
| `style_id( array $overwrite = [] )` | Hidden field for block style identifier (key: `styleid`, group: `advanced`) |
| `icon_class( array $overwrite = [] )` | Icon class text input with icon picker (key: `icon_class`, group: `icon`) |
| `icon_position( array $overwrite = [] )` | Icon position select — Start / End / Remove (key: `icon_position`, group: `icon`) |
| `new_window( array $overwrite = [] )` | Open in new window checkbox |
| `nofollow( array $overwrite = [] )` | Add nofollow checkbox |
| `attributes( array $overwrite = [] )` | Custom HTML attributes text input |
| `title_tag( array $overwrite = [] )` | Title HTML tag select (h1–h6) |
| `html_tag( array $overwrite = [] )` | HTML wrapper element select |

### Group methods

| Method | Returns |
|---|---|
| `title_group()` | Full title configuration set: tag, size, alignment, color, border, margins, padding |

---

## FlexFields

### Single-field methods

All accept `'device_type'` in `$overwrite` (`'Mobile'` / `'Tablet'` / `'Desktop'`) for responsive variants.

| Method | Description |
|---|---|
| `align_items( array $overwrite = [] )` | Align items select |
| `justify_content( array $overwrite = [] )` | Justify content select |
| `align_self( array $overwrite = [] )` | Align self select |
| `order( array $overwrite = [] )` | Flex order select |
| `flex_wrap( array $overwrite = [] )` | Flex wrap select |
| `float( array $overwrite = [] )` | Float select |

### Group methods (mobile / tablet / desktop)

| Method                                                                                    | Returns  | Keys                                        |
|-------------------------------------------------------------------------------------------|----------|---------------------------------------------|
| `align_items_group( string $prefix = 'flex_align_items', array $overwrite = [] )`         | 3 fields | `$prefix`, `$prefix.'_md'`, `$prefix.'_lg'` |
| `justify_content_group( string $prefix = 'flex_justify_content', array $overwrite = [] )` | 3 fields | `$prefix`, `$prefix.'_md'`, `$prefix.'_lg'` |
| `align_self_group( string $prefix = 'flex_align_self', array $overwrite = [] )`           | 3 fields | `$prefix`, `$prefix.'_md'`, `$prefix.'_lg'` |
| `order_group( string $prefix = 'flex_order', array $overwrite = [] )`                     | 3 fields | `$prefix`, `$prefix.'_md'`, `$prefix.'_lg'` |
| `flex_wrap_group( string $prefix = 'flex_wrap', array $overwrite = [] )`                  | 3 fields | `$prefix`, `$prefix.'_md'`, `$prefix.'_lg'` |
| `float_group( string $prefix = 'float', array $overwrite = [] )`                          | 3 fields | `$prefix`, `$prefix.'_md'`, `$prefix.'_lg'` |

---

## ShapeFields

| Method | Returns |
|---|---|
| `divider_group( string $prefix = 'sd', array $overwrite = [], $overwrite_color = [], $overwrite_width = [], $overwrite_image = [] )` | All shape-divider field definitions keyed by argument name |

---

## Helpers: ColorOptions

`AyeCode\SuperDuper\Helpers\ColorOptions` — returns option arrays for use as a field's `options` key.
Not a field factory; do not call from `add_field()` directly.

| Method | Returns | Notes |
|---|---|---|
| `aui( array $types = [], bool $flatten = false )` | AUI color options | `$types`: `'core'`, `'subtle'`, `'emphasis'`, `'outline'`, `'outline_btn_text'`. Defaults to `['core']`. |
| `branding()` | Social / branding color options | Facebook, Twitter, Instagram, etc. |

Filters: `sd_get_aui_colors` (grouped), `sd_get_aui_colors_flat` (flat).

---

## Utilities: Utils::element_require()

`AyeCode\SuperDuper\Utils::element_require( array $args, string $key, string $type ): string`

Builds a parenthesised OR `element_require` expression over every shape in `$args` that supports `$key`.
Used internally by `ShapeFields::divider_group()`.

```php
$er = Utils::element_require( $requires_map, 'flip', 'sd' );
// → '([%sd%]=="mountains" || [%sd%]=="tilt" || ...)'
```

---

## Deprecated: ColorFields (shim)

`AyeCode\SuperDuper\Fields\ColorFields` — kept only for external backward compatibility.
All methods delegate to `ColorOptions`. Do not use in new code.

| Old method | New equivalent |
|---|---|
| `aui_colors( $include_branding, $include_outlines, ... )` | `ColorOptions::aui( $types, true )` |
| `get_aui_colors( $types, $flatten )` | `ColorOptions::aui( $types, $flatten )` |
| `branding_colors()` | `ColorOptions::branding()` |

---

## Global function wrappers (`includes/functions.php`)

All `sd_get_*` global functions are deprecated since 3.1.0. They keep the same external names for backward compatibility but now delegate to the new static methods.

| Global function                                                | New equivalent                                                                  |
|----------------------------------------------------------------|---------------------------------------------------------------------------------|
| `sd_get_margin_input( $type, $overwrite, $include_negatives )` | `SpacingFields::margin( $side, $overwrite, $include_negatives )`                |
| `sd_get_padding_input( $type, $overwrite )`                    | `SpacingFields::padding( $side, $overwrite )`                                   |
| `sd_get_border_input( $type, $overwrite )`                     | `StyleFields::border_show/style/width/opacity/radius/radius_size( $overwrite )` |
| `sd_get_shadow_input( $type, $overwrite )`                     | `StyleFields::shadow( $overwrite )`                                             |
| `sd_get_background_input( $type, $overwrite )`                 | `StyleFields::background( $overwrite )`                                         |
| `sd_get_background_inputs( $type, $overwrite, ... )`           | `StyleFields::background_group( $prefix, $overwrite, ... )`                     |
| `sd_get_opacity_input( $type, $overwrite )`                    | `StyleFields::opacity( $overwrite )`                                            |
| `sd_get_display_input( $type, $overwrite )`                    | `StyleFields::display( $overwrite )`                                            |
| `sd_get_shape_divider_inputs( $type, ... )`                    | `ShapeFields::divider_group( $prefix, ... )`                                    |
| `sd_get_element_require_string( $args, $key, $type )`          | `Utils::element_require( $args, $key, $type )`                                  |
| `sd_get_text_color_input( $type, $overwrite )`                 | `TypographyFields::text_color( $overwrite )`                                    |
| `sd_get_text_color_input_group( $type, $overwrite, $overwrite_custom )` | `TypographyFields::text_color_group( $prefix, $overwrite, $overwrite_custom )` |
| `sd_get_custom_color_input( $type, $overwrite, $parent_type )` | `TypographyFields::text_color_custom( $overwrite )`                             |
| `sd_get_font_size_input( $type, $overwrite )`                  | `TypographyFields::font_size( $overwrite )`                                     |
| `sd_get_font_size_input_group( $type, $overwrite, $overwrite_custom )` | `TypographyFields::font_size_group( $prefix, $overwrite, $overwrite_custom )` |
| `sd_get_font_custom_size_input( $type, $overwrite, $parent )`  | `TypographyFields::font_size_custom( $overwrite )`                              |
| `sd_get_font_weight_input( $type, $overwrite )`                | `TypographyFields::font_weight( $overwrite )`                                   |
| `sd_get_font_case_input( $type, $overwrite )`                  | `TypographyFields::font_case( $overwrite )`                                     |
| `sd_get_font_italic_input( $type, $overwrite )`                | `TypographyFields::font_italic( $overwrite )`                                   |
| `sd_get_font_line_height_input( $type, $overwrite )`           | `TypographyFields::line_height( $overwrite )`                                   |
| `sd_get_text_align_input( $type, $overwrite )`                 | `TypographyFields::text_align( $overwrite )`                                    |
| `sd_get_text_justify_input( $type, $overwrite )`               | `TypographyFields::text_justify( $overwrite )`                                  |
| `sd_get_container_class_input( $type, $overwrite )`            | `LayoutFields::container( $overwrite )`                                         |
| `sd_get_position_class_input( $type, $overwrite )`             | `LayoutFields::position( $overwrite )`                                          |
| `sd_get_sticky_offset_input( $type, $overwrite )`              | `LayoutFields::sticky_offset( $side, $overwrite )`                              |
| `sd_get_col_input( $type, $overwrite )`                        | `LayoutFields::col( $overwrite )`                                               |
| `sd_get_row_cols_input( $type, $overwrite )`                   | `LayoutFields::row_cols( $overwrite )`                                          |
| `sd_get_absolute_position_input( $type, $overwrite )`          | `LayoutFields::absolute_position( $overwrite )`                                 |
| `sd_get_width_input( $type, $overwrite )`                      | `LayoutFields::width( $overwrite )`                                             |
| `sd_get_height_input( $type, $overwrite )`                     | `LayoutFields::height( $overwrite )`                                            |
| `sd_get_max_height_input( $type, $overwrite )`                 | `LayoutFields::max_height( $overwrite )`                                        |
| `sd_get_class_input( $type, $overwrite )`                      | `CommonFields::css_class( $overwrite )`                                         |
| `sd_get_anchor_input( $type, $overwrite )`                     | `CommonFields::anchor( $overwrite )`                                            |
| `sd_get_custom_name_input( $type, $overwrite )`                | `CommonFields::metadata_name( $overwrite )`                                     |
| `sd_get_visibility_conditions_input( $type, $overwrite )`      | `CommonFields::visibility_conditions( $overwrite )`                             |
| `sd_get_new_window_input( $type, $overwrite )`                 | `CommonFields::new_window( $overwrite )`                                        |
| `sd_get_nofollow_input( $type, $overwrite )`                   | `CommonFields::nofollow( $overwrite )`                                          |
| `sd_get_attributes_input( $type, $overwrite )`                 | `CommonFields::attributes( $overwrite )`                                        |
| `sd_get_title_tag_input( $overwrite )`                         | `CommonFields::title_tag( $overwrite )`                                         |
| `sd_get_html_tag_input( $overwrite )`                          | `CommonFields::html_tag( $overwrite )`                                          |
| `sd_get_title_inputs()`                                        | `CommonFields::title_group()`                                                   |
| `sd_get_hover_animations_input( $type, $overwrite )`           | `StyleFields::hover_animation( $overwrite )`                                    |
| `sd_get_hover_icon_animation_input( $type, $overwrite )`       | `StyleFields::hover_icon_animation( $overwrite )`                               |
| `sd_get_zindex_input( $type, $overwrite )`                     | `StyleFields::zindex( $overwrite )`                                             |
| `sd_get_overflow_input( $type, $overwrite )`                   | `StyleFields::overflow( $overwrite )`                                           |
| `sd_get_scrollbars_input( $type, $overwrite )`                 | `StyleFields::scrollbars( $overwrite )`                                         |
| `sd_get_flex_align_items_input( $type, $overwrite )`           | `FlexFields::align_items( $overwrite )`                                         |
| `sd_get_flex_align_items_input_group( $type, $overwrite )`     | `FlexFields::align_items_group( $prefix, $overwrite )`                          |
| `sd_get_flex_justify_content_input( $type, $overwrite )`       | `FlexFields::justify_content( $overwrite )`                                     |
| `sd_get_flex_justify_content_input_group( $type, $overwrite )` | `FlexFields::justify_content_group( $prefix, $overwrite )`                      |
| `sd_get_flex_align_self_input( $type, $overwrite )`            | `FlexFields::align_self( $overwrite )`                                          |
| `sd_get_flex_align_self_input_group( $type, $overwrite )`      | `FlexFields::align_self_group( $prefix, $overwrite )`                           |
| `sd_get_flex_order_input( $type, $overwrite )`                 | `FlexFields::order( $overwrite )`                                               |
| `sd_get_flex_order_input_group( $type, $overwrite )`           | `FlexFields::order_group( $prefix, $overwrite )`                                |
| `sd_get_flex_wrap_input( $type, $overwrite )`                  | `FlexFields::flex_wrap( $overwrite )`                                           |
| `sd_get_flex_wrap_group( $type, $overwrite )`                  | `FlexFields::flex_wrap_group( $prefix, $overwrite )`                            |
| `sd_get_float_input( $type, $overwrite )`                      | `FlexFields::float( $overwrite )`                                               |
| `sd_get_float_group( $type, $overwrite )`                      | `FlexFields::float_group( $prefix, $overwrite )`                                |
| `sd_aui_colors( $include_branding, ... )`                      | `ColorOptions::aui( $types, true )`                                             |
| `sd_get_aui_colors( $types, $flatten )`                        | `ColorOptions::aui( $types, $flatten )`                                         |
| `sd_aui_branding_colors()`                                     | `ColorOptions::branding()`                                                      |

---

## Field Types

The `type` key in a field definition controls how the field renders.

| Type | Widget form | Gutenberg | Notes |
|---|---|---|---|
| `text` | Text input | Text control | |
| `number` | Number input | Number control | Use `min`, `max`, `step` in `custom_attributes` |
| `select` | `<select>` | Dropdown | `options` key required. Supports optgroups (nested arrays) and `multiple` |
| `checkbox` | Checkbox | Toggle | Saved as `'1'` / `''` |
| `textarea` | `<textarea>` | Textarea | |
| `color` | Color input | Color picker | HTML native color input in widget form |
| `range` | — | Range slider | |
| `gradient` | — | Gradient picker | |
| `image` | — | Image picker | Returns attachment URL |
| `image_xy` | — | Position picker | Used alongside an `image` field for background-position |
| `hidden` | Hidden input | — | |
| `notice` | — | Info panel | Informational text only, no saved value |
| `visibility_conditions` | — | Custom UI | Powers the block visibility conditions feature |
| `rest` | — | Dependent dropdown | Populated via REST API. See `docs/features/dependent-fields.md` |
