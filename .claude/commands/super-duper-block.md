# WP Super Duper Block Builder

**Complete reference for building Gutenberg blocks using the WP Super Duper framework (AyeCode v3).**

Blocks are PHP classes that extend `WP_Super_Duper` and automatically produce a Gutenberg block, shortcode, and widget from a single definition.

---

## Quick Start

```php
class My_Block extends WP_Super_Duper {
    public function __construct() {
        parent::__construct( array(
            'textdomain'     => 'my-textdomain',
            'base_id'        => 'my_block',
            'name'           => __( 'My Block', 'my-textdomain' ),
            'class_name'     => __CLASS__,
            'block-icon'     => 'fas fa-star',
            'block-category' => 'widgets',
            'widget_ops'     => array(
                'classname'   => 'my-block',
                'description' => __( 'Block description', 'my-textdomain' ),
            ),
        ) );
    }

    public function set_arguments(): array {
        return ( new \AyeCode\SuperDuper\Builder\BlockArguments() )
            ->add_field( 'title', array(
                'type'    => 'text',
                'title'   => __( 'Title', 'my-textdomain' ),
                'group'   => 'content',
                'default' => '',
            ) )
            ->add_typography_group()
            ->add_responsive_margins()
            ->add_border_group()
            ->add_visibility_conditions()
            ->add_advanced_group()
            ->get();
    }

    public function output( $args = array(), $widget_args = array(), $content = '' ): string {
        $args = wp_parse_args( $args, array( 'title' => '' ) );
        if ( empty( $args['title'] ) && ! $this->is_preview() ) return '';

        $wrap_class = sd_build_aui_class( $args );
        return sprintf(
            '<div class="%s">%s</div>',
            esc_attr( $wrap_class ),
            esc_html( $args['title'] )
        );
    }
}
```

---

## Conversion Rules (When Editing or Converting Existing Blocks)

- **Never remove comments or docblocks.** Preserve all existing `/** ... */` docblocks, `//` line comments, and `/* ... */` block comments exactly as written. Only add new comments if the WHY is non-obvious.
- **Never remove `<script>` tags from `block_global_js()` methods.** The `<script>` / `</script>` wrapper is intentional — it enables IDE syntax highlighting and autocomplete for the JS snippet. The tags are stripped at runtime by `str_replace( [ '<script>', '</script>' ], '', ob_get_clean() )`.
- **Never remove the dead `outputx()` method** or similar alternate/draft output methods without explicit instruction — they may be works-in-progress.

---

## Core Principle: Minimize Arguments

**Goal:** Use the fewest arguments necessary to achieve the desired result.

- **Always prefer group methods** over individual fields
- **Omit default parameter values** — only pass parameters when overriding defaults
- **Use prefixes** only when you need multiple instances of the same group (e.g., `title_`, `subtitle_`)

---

## Field Selection Priority Rules

When building `set_arguments()`, follow this strict priority:

### 1. Check if a Group Method Exists First

| If you need... | Use this group method | NOT individual fields |
|----------------|----------------------|----------------------|
| Full typography (size, weight, color, align, etc.) | `add_typography_group()` | ❌ Individual `add_field()` calls |
| Text color only | `add_colors_group()` | ❌ `text_color` + `text_color_custom` |
| Background color/gradient/image | `add_background_group()` | ❌ Individual `bg`, `bg_color`, `bg_gradient` |
| All margins (mt/mr/mb/ml) | `add_margins()` or `add_responsive_margins()` | ❌ Four separate margin fields |
| All paddings (pt/pr/pb/pl) | `add_padding()` or `add_responsive_paddings()` | ❌ Four separate padding fields |
| Border (color/type/width/radius) | `add_border_group()` | ❌ Individual border fields |
| Layout (container + position) | `add_layout_group()` | ❌ Separate container/position fields |

### 2. Only Use Individual Fields When:

- You need a **subset** of a group (e.g., only `text_color` without full typography)
- You need **different `group` values** for different fields within a logical set (rare — `$overwrite` sets group for all; `$field_overwrites` overrides it per sub-field)

### 3. Never Pass Default Parameter Values

**❌ Wrong:**
```php
->add_background_group( '', [ 'group' => 'wrapper-styles' ], true, [] )
```
- `''` is the default prefix
- `'wrapper-styles'` is the default group
- `true` is the default for `$include_image`
- `[]` is the default for `$field_overwrites`

**✅ Correct:**
```php
->add_background_group()
```

**When to pass parameters:**
```php
// Custom prefix for a second background instance
->add_background_group( 'card_' )

// Different group for all fields
->add_background_group( '', [ 'group' => 'hero-styles' ] )

// Disable image picker
->add_background_group( '', [], false )

// Per-field default (new) — set a default color without touching other fields
->add_background_group( '', [], true, [ 'color' => [ 'default' => '#ff0000' ] ] )
```

**For `add_typography_group()`, the same rule applies:**
```php
// ❌ Wrong — repeating defaults
->add_typography_group( '', [ 'group' => 'typography' ], [] )

// ✅ Correct
->add_typography_group()

// ✅ Correct — per-field defaults without abandoning the group
->add_typography_group( 'num_', [ 'group' => 'typography' ], [
    'font_size' => [ 'default' => 'h2' ],
    'color'     => [ 'default' => 'primary' ],
    'text_align'=> [ 'default' => 'text-center' ],
] )
```

### 4. Understanding What Group Methods Include

**`add_background_group()`** includes ALL these fields automatically:
- `bg` — Background color select (with custom-color and custom-gradient options)
- `bg_color` — Custom color picker (shown when bg = "custom-color")
- `bg_gradient` — Gradient picker (shown when bg = "custom-gradient")
- `bg_on_text` — Checkbox for gradient-on-text effect (shown when bg = "custom-gradient")
- `bg_image` — Image picker (unless `$include_image = false`)
- `bg_image_xy` — Background position picker

**Never add these fields individually** — they're already in the group!

**`add_typography_group( $prefix, $overwrite, $field_overwrites )`** includes ALL these fields:
- `{$prefix}text_color` + `{$prefix}text_color_custom` — Text color select + custom picker
- `{$prefix}font_size` + `{$prefix}font_size_custom` — Font size select + custom input
- `{$prefix}font_weight` — Font weight select
- `{$prefix}font_case` — Letter case select
- `{$prefix}font_italic` — Italic select
- `{$prefix}font_line_height` — Line height input
- `{$prefix}text_justify` — Text justify checkbox
- `{$prefix}text_align` + `{$prefix}text_align_md` + `{$prefix}text_align_lg` — Responsive text align

**Example — Replace individual fields with group method:**

**❌ Wrong (individual fields):**
```php
->add_field( 'title_text_color', TypographyFields::text_color( [ 'group' => 'title-typography' ] ) )
->add_fields( TypographyFields::font_size_group( 'title_font_size', [ 'group' => 'title-typography' ] ) )
->add_field( 'title_font_weight', TypographyFields::font_weight( [ 'group' => 'title-typography' ] ) )
->add_field( 'title_text_justify', TypographyFields::text_justify( [ 'group' => 'title-typography' ] ) )
->add_field( 'title_text_align', TypographyFields::text_align( [
    'group'           => 'title-typography',
    'device_type'     => 'Mobile',
    'element_require' => '[%title_text_justify%]==""',
] ) )
->add_field( 'title_text_align_md', TypographyFields::text_align( [
    'group'           => 'title-typography',
    'device_type'     => 'Tablet',
    'element_require' => '[%title_text_justify%]==""',
] ) )
->add_field( 'title_text_align_lg', TypographyFields::text_align( [
    'group'           => 'title-typography',
    'device_type'     => 'Desktop',
    'element_require' => '[%title_text_justify%]==""',
] ) )
```

**✅ Correct (single group method):**
```php
->add_typography_group( 'title_', [ 'group' => 'title-typography' ] )
```

This creates the exact same fields with the exact same `element_require` logic, but in one line.

---

## Complete Constructor Options Reference

Pass to `parent::__construct()`:

| Option | Type | Required | Description |
|--------|------|----------|-------------|
| `textdomain` | string | **Yes** | Plugin/theme textdomain for translations and block namespacing |
| `base_id` | string | **Yes** | Unique snake_case identifier (used for widget ID and shortcode name) |
| `name` | string | **Yes** | Human-readable block name |
| `class_name` | string | **Yes** | PHP class name (use `__CLASS__`) |
| `block-icon` | string | **Yes** | Font Awesome class (`'fas fa-star'`) or Dashicon (`'dashicons-admin-site'`) |
| `block-category` | string | **Yes** | Block category: `'common'`, `'formatting'`, `'layout'`, `'widgets'`, `'embed'`, `'text'` |
| `widget_ops` | array | **Yes** | Widget configuration: `['classname' => '', 'description' => '']` |
| `block-keywords` | string | No | JSON array of search keywords (max 3): `"['keyword1','keyword2']"` |
| `block-supports` | array | No | Gutenberg block supports: `['customClassName' => false, 'anchor' => false]` |
| `block-wrap` | string | No | Wrapper element for PHP output: `'div'`, `'span'`, or `''` for none |
| `no_wrap` | bool | No | Set `true` with empty `block-wrap` to prevent wrapper div |
| `block_group_tabs` | array | No | Tab structure for block inspector (see below) |
| `output_types` | array | No | Output types: `['block', 'shortcode', 'widget']` (default: all three) |
| `block-output` | array | No | **Mode 1**: Auto-compiled static block output (see Output Modes) |
| `block-edit-return` | string | No | **Mode 2**: Custom JS for edit component (see Output Modes) |
| `block-save-return` | string | No | **Mode 2**: Custom JS for save component (see Output Modes) |
| `block-api-version` | int | No | Block API version (default: `3`) |
| `example` | array | No | Preview attributes for block inserter: `['attributes' => ['title' => 'Preview']]`. Use `'viewportWidth'` to control the preview canvas width: `['viewportWidth' => 400]` |
| `nested-block` | bool | No | Set `true` for blocks that contain InnerBlocks |
| `allowed-blocks` | array | No | For nested blocks: restricts which child block types are accepted — `['namespace/child-block']` |
| `parent` | array | No | Restricts this block to only appear as a child of specific blocks — `['namespace/parent-block']` |

---

## Block Group Tabs Structure

Standard three-tab layout (Content / Styles / Advanced):

```php
'block_group_tabs' => array(
    'content'  => array(
        'groups' => array(
            array( 'id' => 'content', 'title' => __( 'Content', 'textdomain' ) ),
            // Add more content groups as needed
        ),
        'tab' => array(
            'title'     => __( 'Content', 'textdomain' ),
            'key'       => 'bs_tab_content',
            'tabs_open' => true,
            'open'      => true,
            'class'     => 'text-center flex-fill d-flex justify-content-center',
        ),
    ),
    'styles'   => array(
        'groups' => array(
            array( 'id' => 'typography', 'title' => __( 'Typography', 'textdomain' ) ),
            array( 'id' => 'wrapper-styles', 'title' => __( 'Wrapper Styles', 'textdomain' ) ),
            // Add more style groups as needed
        ),
        'tab' => array(
            'title' => __( 'Styles', 'textdomain' ),
            'key'   => 'bs_tab_styles',
            'tabs_open' => true,
            'open'  => true,
            'class' => 'text-center flex-fill d-flex justify-content-center',
        ),
    ),
    'advanced' => array(
        'groups' => array(
            array( 'id' => 'wrapper-styles', 'title' => __( 'Wrapper Styles', 'textdomain' ) ),
            array( 'id' => 'visibility-conditions', 'title' => __( 'Visibility Conditions', 'textdomain' ) ),
            array( 'id' => 'advanced', 'title' => __( 'Advanced', 'textdomain' ) ),
        ),
        'tab' => array(
            'title' => __( 'Advanced', 'textdomain' ),
            'key'   => 'bs_tab_advanced',
            'tabs_open' => true,
            'open'  => true,
            'class' => 'text-center flex-fill d-flex justify-content-center',
        ),
    ),
),
```

**Important:** The `'id'` value in each group **must match** the `'group'` key in your field definitions.

---

## BlockArguments API — Complete Method Reference

### Core Methods

| Method | Signature | Description |
|--------|-----------|-------------|
| `add_field()` | `add_field( string $name, array $config ): self` | Add a single field definition |
| `add_fields()` | `add_fields( array $fields ): self` | Merge multiple field definitions (key → config pairs) |
| `get()` | `get(): array` | Return the compiled arguments array (always the last call) |

### Spacing Methods

| Method | Signature | Description |
|--------|-----------|-------------|
| `add_margins()` | `add_margins( array $overwrite = [], bool $include_negatives = true ): self` | Add mt/mr/mb/ml margin selects |
| `add_responsive_margins()` | `add_responsive_margins( array $overwrite = [], bool $include_negatives = true, string $mb_lg_default = '3' ): self` | Add margins for mobile/tablet/desktop breakpoints (base + _md + _lg variants). `mb_lg` defaults to `'3'`; pass `''` to omit |
| `add_padding()` | `add_padding( array $overwrite = [] ): self` | Add pt/pr/pb/pl padding selects |
| `add_responsive_paddings()` | `add_responsive_paddings( array $overwrite = [] ): self` | Add paddings for mobile/tablet/desktop breakpoints |

### Style Methods

| Method | Signature | Description |
|--------|-----------|-------------|
| `add_border_group()` | `add_border_group( string $prefix = '', array $overwrite = [] ): self` | Border color, show/type, width, opacity, radius type, radius size |
| `add_shadow_group()` | `add_shadow_group( string $prefix = '', array $overwrite = [] ): self` | Box shadow select |
| `add_background_group()` | `add_background_group( string $prefix = '', array $overwrite = [], bool $include_image = true, array $field_overwrites = [] ): self` | Background color, custom color, gradient, and image fields. `$field_overwrites` keys: `'color'`, `'gradient'`, `'image'` (pass `false` as value to omit that sub-field) |
| `add_display_group()` | `add_display_group( string $prefix = '', array $overwrite = [] ): self` | Display select (mobile only: none/flex/block/inline-flex/etc.) |
| `add_responsive_display_group()` | `add_responsive_display_group( string $prefix = '', array $overwrite = [] ): self` | Display selects for mobile/tablet/desktop |

### Typography Methods

| Method | Signature | Description |
|--------|-----------|-------------|
| `add_typography_group()` | `add_typography_group( string $prefix = '', array $overwrite = [], array $field_overwrites = [] ): self` | Full typography set: font size+custom, weight, case, italic, line-height, justify, align, text color+custom. `$field_overwrites` keys: `'color'`, `'font_size'`, `'font_weight'`, `'font_case'`, `'font_italic'`, `'line_height'`, `'text_justify'`, `'text_align'` |
| `add_colors_group()` | `add_colors_group( string $prefix = '', array $overwrite = [] ): self` | Text color + custom color picker only (subset of typography group) |

### Layout Methods

| Method | Signature | Description |
|--------|-----------|-------------|
| `add_position()` | `add_position( string $prefix = '', array $overwrite = [] ): self` | Position class select (static/relative/absolute/fixed/sticky) |
| `add_layout_group()` | `add_layout_group( string $prefix = '', array $overwrite = [] ): self` | Container width + position class together |
| `add_sticky_offset_group()` | `add_sticky_offset_group( string $prefix = '', array $overwrite = [] ): self` | Sticky offset top and bottom (shown when position is sticky) |

### Icon Methods

| Method | Signature | Description |
|--------|-----------|-------------|
| `add_icon_class()` | `add_icon_class( array $overwrite = [] ): self` | Icon class text input with icon picker (key: `icon_class`, group: `icon`) |
| `add_icon_position()` | `add_icon_position( array $overwrite = [] ): self` | Icon position select: Start/End/Remove (key: `icon_position`, group: `icon`) |
| `add_icon_group()` | `add_icon_group( array $overwrite = [] ): self` | Icon class + icon position together |

### Utility Methods

| Method | Signature | Description |
|--------|-----------|-------------|
| `add_visibility_conditions()` | `add_visibility_conditions( string $prefix = '', array $overwrite = [] ): self` | Block visibility conditions field (key: `visibility_conditions`, group: `visibility-conditions`) |
| `add_advanced_group()` | `add_advanced_group( string $prefix = '', array $overwrite = [] ): self` | CSS class + metadata name (standard Advanced tab fields) |
| `add_class_and_anchor()` | `add_class_and_anchor( string $prefix = '', array $overwrite = [] ): self` | CSS class + anchor ID + metadata name |
| `add_hidden_field()` | `add_hidden_field( string $name, array $overwrite = [] ): self` | Hidden field (no UI, value stored in attributes) |
| `add_style_id()` | `add_style_id( array $overwrite = [] ): self` | Hidden field for block style identifier (key: `styleid`, group: `advanced`) |

### Field-Level Tab Methods

| Method | Signature | Description |
|--------|-----------|-------------|
| `open_tabs()` | `open_tabs( string $key, string $title, string $class = '' ): self` | Open a tab container and its first tab pane |
| `open_tab()` | `open_tab( string $key, string $title, string $class = '' ): self` | Open a subsequent tab pane |
| `close_tab()` | `close_tab(): self` | Close the current tab pane |
| `close_tabs()` | `close_tabs(): self` | Close the current tab pane and the entire tab container |

---

## What Each Group Method Creates

Understanding exactly which fields are created by each group method is critical to avoiding duplicate fields.

### `add_margins( $overwrite = [], $include_negatives = true )`

Creates 4 fields with default `group: 'wrapper-styles'`:
- `mt` — Margin top select
- `mr` — Margin right select
- `mb` — Margin bottom select
- `ml` — Margin left select

### `add_responsive_margins( $overwrite = [], $include_negatives = true, $mb_lg_default = '3' )`

Creates 12 fields (mobile + tablet + desktop):
- `mt`, `mr`, `mb`, `ml` — Mobile (base) with `device_type: 'Mobile'`
- `mt_md`, `mr_md`, `mb_md`, `ml_md` — Tablet with `device_type: 'Tablet'`
- `mt_lg`, `mr_lg`, `mb_lg`, `ml_lg` — Desktop with `device_type: 'Desktop'`, `mb_lg` defaults to `'3'`

### `add_padding( $overwrite = [] )`

Creates 4 fields with default `group: 'wrapper-styles'`:
- `pt` — Padding top select
- `pr` — Padding right select
- `pb` — Padding bottom select
- `pl` — Padding left select

### `add_responsive_paddings( $overwrite = [] )`

Creates 12 fields (mobile + tablet + desktop):
- `pt`, `pr`, `pb`, `pl` — Mobile with `device_type: 'Mobile'`
- `pt_md`, `pr_md`, `pb_md`, `pl_md` — Tablet with `device_type: 'Tablet'`
- `pt_lg`, `pr_lg`, `pb_lg`, `pl_lg` — Desktop with `device_type: 'Desktop'`

### `add_border_group( $prefix = '', $overwrite = [] )`

Creates 6 fields with default `group: 'wrapper-styles'`:
- `{$prefix}border` — Border color select
- `{$prefix}border_type` — Border show/sides select (element_require: `[%border%]` is set)
- `{$prefix}border_width` — Border width select (element_require: `[%border%]` is set)
- `{$prefix}border_opacity` — Border opacity select (element_require: `[%border%]` is set)
- `{$prefix}rounded` — Border radius type select
- `{$prefix}rounded_size` — Border radius size select (element_require: `[%rounded%]=="custom"`)

Default prefix is empty string, so fields are: `border`, `border_type`, etc.

### `add_shadow_group( $prefix = '', $overwrite = [] )`

Creates 1 field with default `group: 'wrapper-styles'`:
- `{$prefix}shadow` — Box shadow select

### `add_background_group( $prefix = '', $overwrite = [], $include_image = true, $field_overwrites = [] )`

Creates up to 6 fields with default `group: 'wrapper-styles'`, default prefix `''` (so `bg` becomes the prefix):

**When called with defaults** `add_background_group()`:
- `bg` — Background color select (includes "Custom Color" and "Custom Gradient" options)
- `bg_color` — Custom color picker (element_require: `[%bg%]=="custom-color"`)
- `bg_gradient` — Gradient picker (element_require: `[%bg%]=="custom-gradient"`)
- `bg_on_text` — Background on text checkbox (element_require: `[%bg%]=="custom-gradient"`)
- `bg_image` — Image picker (omitted if `$include_image = false` or `$field_overwrites['image'] = false`)
- `bg_image_xy` — Background position picker (same omit conditions as above)

**`$field_overwrites`** — keyed per sub-component, merged on top of `$overwrite`. Valid keys:

| Key | Targets | Pass `false` to omit |
|---|---|---|
| `'color'` | `bg_color` custom color picker | Yes |
| `'gradient'` | `bg_gradient` + `bg_on_text` | Yes |
| `'image'` | all `bg_image*` fields | Yes |

```php
->add_background_group( '', [ 'group' => 'styles' ], true, [
    'color' => [ 'default' => '#ff0000' ],
    'image' => false,  // omit image picker
] )
```

**Important:** The prefix parameter does NOT include 'bg' — it's added internally. So:
- `add_background_group()` creates fields `bg`, `bg_color`, `bg_gradient`, etc.
- `add_background_group( 'card_' )` creates fields `card_bg`, `card_bg_color`, `card_bg_gradient`, etc.

### `add_display_group( $prefix = '', $overwrite = [] )`

Creates 1 field with default `group: 'wrapper-styles'`:
- `{$prefix}display` — Display select with `device_type: 'Mobile'`

### `add_responsive_display_group( $prefix = '', $overwrite = [] )`

Creates 3 fields with default `group: 'wrapper-styles'`:
- `{$prefix}display` — Mobile with `device_type: 'Mobile'`
- `{$prefix}display_md` — Tablet with `device_type: 'Tablet'`
- `{$prefix}display_lg` — Desktop with `device_type: 'Desktop'`

### `add_typography_group( $prefix = '', $overwrite = [], $field_overwrites = [] )`

Creates 12 fields with default `group: 'typography'`:
- `{$prefix}text_color` — Text color select (includes "Custom color" option)
- `{$prefix}text_color_custom` — Custom color picker (element_require: `[%{$prefix}text_color%]=="custom-color"`)
- `{$prefix}font_size` — Font size select (includes "Custom size" option)
- `{$prefix}font_size_custom` — Custom size number input (element_require: `[%{$prefix}font_size%]=="custom"`)
- `{$prefix}font_weight` — Font weight select
- `{$prefix}font_case` — Letter case select
- `{$prefix}font_italic` — Italic select
- `{$prefix}font_line_height` — Line height number input
- `{$prefix}text_justify` — Text justify checkbox
- `{$prefix}text_align` — Text align select, Mobile (element_require: `[%{$prefix}text_justify%]==""`)
- `{$prefix}text_align_md` — Text align select, Tablet (element_require: `[%{$prefix}text_justify%]==""`)
- `{$prefix}text_align_lg` — Text align select, Desktop (element_require: `[%{$prefix}text_justify%]==""`)

**`$overwrite`** — global baseline applied to every field (use for `group`, `desc_tip`, etc.).

**`$field_overwrites`** — keyed per sub-component, merged on top of `$overwrite`. Valid keys:

| Key | Targets |
|---|---|
| `'color'` | `text_color` + `text_color_custom` |
| `'font_size'` | `font_size` + `font_size_custom` |
| `'font_weight'` | `font_weight` |
| `'font_case'` | `font_case` |
| `'font_italic'` | `font_italic` |
| `'line_height'` | `font_line_height` |
| `'text_justify'` | `text_justify` |
| `'text_align'` | `text_align`, `text_align_md`, `text_align_lg` |

```php
->add_typography_group( 'num_', [ 'group' => 'typography' ], [
    'font_size' => [ 'default' => 'h2' ],
    'color'     => [ 'default' => 'primary' ],
    'text_align'=> [ 'default' => 'text-center' ],
] )
```

**Example:** `add_typography_group( 'title_' )` creates fields: `title_text_color`, `title_text_color_custom`, `title_font_size`, `title_font_size_custom`, etc.

### `add_colors_group( $prefix = '', $overwrite = [] )`

Creates 2 fields with default `group: 'typography'`:
- `{$prefix}text_color` — Text color select
- `{$prefix}text_color_custom` — Custom color picker

### `add_position( $prefix = '', $overwrite = [] )`

Creates 1 field with default `group: 'wrapper-styles'`:
- `{$prefix}position` — Position class select (static/relative/absolute/fixed/sticky)

### `add_layout_group( $prefix = '', $overwrite = [] )`

Creates 2 fields with default `group: 'wrapper-styles'`:
- `{$prefix}container` — Container width select
- `{$prefix}position` — Position class select

### `add_sticky_offset_group( $prefix = '', $overwrite = [] )`

Creates 2 fields with default `group: 'wrapper-styles'`:
- `{$prefix}sticky_offset_top` — Sticky offset top number (element_require: `[%position%]=="position-sticky"`)
- `{$prefix}sticky_offset_bottom` — Sticky offset bottom number (element_require: `[%position%]=="position-sticky"`)

### `add_icon_group( $overwrite = [] )`

Creates 2 fields with default `group: 'icon'`:
- `icon_class` — Icon class text input with icon picker
- `icon_position` — Icon position select (Start/End/Remove)

### `add_visibility_conditions( $prefix = '', $overwrite = [] )`

Creates 1 field with default `group: 'visibility-conditions'`:
- `{$prefix}visibility_conditions` — Block visibility conditions UI

### `add_advanced_group( $prefix = '', $overwrite = [] )`

Creates 2 fields with default `group: 'advanced'`:
- `{$prefix}css_class` — Additional CSS class(es) text input
- `{$prefix}metadata_name` — Custom block name text input

### `add_class_and_anchor( $prefix = '', $overwrite = [] )`

Creates 3 fields with default `group: 'advanced'`:
- `{$prefix}css_class` — Additional CSS class(es) text input
- `{$prefix}anchor` — HTML anchor ID text input
- `{$prefix}metadata_name` — Custom block name text input

---

## Complete Field Definition Properties

Every field passed to `add_field()` supports these properties:

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `type` | string | **Yes** | Field type (see Field Types below) |
| `title` | string | **Yes** | Label displayed for the field |
| `group` | string | **Yes** | Must match a group `id` in `block_group_tabs` |
| `default` | mixed | No | Default value |
| `desc` | string | No | Help text / description |
| `desc_tip` | bool | No | Show description as tooltip (`true`) or inline (`false`) |
| `placeholder` | string | No | Input placeholder text |
| `options` | array | No | For `select`/`checkbox`: `value => label` pairs. Supports optgroups (nested arrays) |
| `rows` | int | No | For `textarea`: number of rows |
| `value` | string | No | For `checkbox`: the checked-value (e.g., `'1'`) |
| `multiple` | bool | No | For `select`: allow multiple selections |
| `custom_attributes` | array | No | HTML attributes: `['min' => 0, 'max' => 100, 'step' => 5]` |
| `element_require` | string | No | Conditional display expression (see below) |
| `depends_on` | array | No | Dependent field configuration for REST API population (see Dependent Fields) |
| `device_type` | string | No | Responsive breakpoint label: `'Mobile'`, `'Tablet'`, `'Desktop'` |
| `dynamic_data` | bool | No | Allow dynamic data / variable substitution (`true` / `false`) |
| `status` | string | No | For `notice` type only: alert colour — `'info'`, `'warning'`, `'error'`, `'success'` |
| `tab` | array | No | Embed a tab pane boundary within a field's config. Open: `['title' => ..., 'key' => ..., 'tabs_open' => true, 'open' => true, 'class' => ...]`. Close pane: `['close' => true]`. Close pane + container: `['close' => true, 'tabs_close' => true]` |
| `advanced` | bool | No | Mark as advanced option (legacy, prefer `group` instead) |

### `element_require` Syntax

Conditional display logic:

```php
'element_require' => '[%field_name%]'              // Show when field_name is truthy
'element_require' => '![%field_name%]'             // Show when field_name is falsy
'element_require' => '[%field_name%]=="value"'     // Show when field equals "value"
'element_require' => '[%field_name%]!="value"'     // Show when field does not equal "value"
'element_require' => '([%a%]&&[%b%])||[%c%]'       // Complex conditions with &&/||
```

### Dependent Fields (`depends_on`)

Populate field options via REST API when a parent field changes:

```php
'parent_field' => array(
    'type'    => 'select',
    'title'   => __( 'Post Type', 'textdomain' ),
    'options' => array( 'post' => 'Posts', 'page' => 'Pages' ),
    'group'   => 'content',
),
'dependent_field' => array(
    'type'  => 'select',
    'title' => __( 'Category', 'textdomain' ),
    'group' => 'content',
    'depends_on' => array(
        'attribute'  => 'parent_field',                                  // Parent field name
        'fetch_type' => 'rest',                                          // Only 'rest' supported
        'rest_path'  => '/wp/v2/{parent_field}category/?per_page=100',  // {field} replaced with parent value
        'cache_key'  => 'taxonomy',                                      // Optional: custom cache key prefix
        'map_response' => 'default',                                     // Optional: response mapping format
        'default_option' => array(                                       // Optional: first option in dropdown
            'label' => __( 'All Categories', 'textdomain' ),
            'value' => '0',
        ),
    ),
),
```

---

## Complete Field Types Reference

| Type | Widget Form | Gutenberg | Notes |
|------|-------------|-----------|-------|
| `text` | Text input | Text control | |
| `number` | Number input | Number control | Use `custom_attributes` for `min`, `max`, `step` |
| `select` | `<select>` | Dropdown | `options` required. Supports optgroups and `multiple` |
| `checkbox` | Checkbox | Toggle | Saved as `'1'` / `''`. Use `value` key for checked value |
| `textarea` | `<textarea>` | Textarea | Use `rows` for height |
| `color` | Color input | Color picker | HTML native color input in widget form |
| `range` | — | Range slider | Gutenberg only |
| `gradient` | — | Gradient picker | Gutenberg only |
| `image` | — | Image picker | Returns attachment URL |
| `image_xy` | — | Position picker | Used alongside an `image` field for background-position |
| `hidden` | Hidden input | — | No UI rendered, value stored in attributes |
| `notice` | — | Info panel | Informational text only, no saved value |
| `visibility_conditions` | — | Custom UI | Powers the block visibility conditions feature |
| `rest` | — | Dependent dropdown | Populated via REST API (use `depends_on` instead) |
| `media` | — | Media library | WordPress media picker |

---

## Static Field Classes — Direct Access

All `BlockArguments` methods delegate to these static classes. Use them directly when you need individual fields:

```php
use AyeCode\SuperDuper\Fields\SpacingFields;
use AyeCode\SuperDuper\Fields\StyleFields;
use AyeCode\SuperDuper\Fields\TypographyFields;
use AyeCode\SuperDuper\Fields\LayoutFields;
use AyeCode\SuperDuper\Fields\CommonFields;
use AyeCode\SuperDuper\Fields\FlexFields;
use AyeCode\SuperDuper\Fields\ShapeFields;
use AyeCode\SuperDuper\Helpers\ColorOptions;

// Example: Single field
$mt_field = SpacingFields::margin( 'top', [ 'group' => 'spacing', 'default' => '3' ] );

// Example: Group of fields
$border_fields = StyleFields::border_group( '', [ 'group' => 'wrapper-styles' ] );

// Add to BlockArguments
return ( new BlockArguments() )
    ->add_field( 'mt', $mt_field )
    ->add_fields( $border_fields )
    ->get();
```

### SpacingFields Methods

| Method | Signature | Returns |
|--------|-----------|---------|
| `margin()` | `margin( string $side, array $overwrite = [], bool $include_negatives = true )` | Single margin field (`mt`/`mr`/`mb`/`ml`) |
| `padding()` | `padding( string $side, array $overwrite = [] )` | Single padding field (`pt`/`pr`/`pb`/`pl`) |
| `margin_group()` | `margin_group( array $overwrite = [], bool $include_negatives = true )` | All four margin fields |
| `padding_group()` | `padding_group( array $overwrite = [] )` | All four padding fields |

`$side`: `'top'`, `'right'`, `'bottom'`, `'left'`

### StyleFields Methods

| Method | Signature | Returns |
|--------|-----------|---------|
| `border_show()` | `border_show( array $overwrite = [] )` | Border color select |
| `border_style()` | `border_style( array $overwrite = [] )` | Border show/sides select (full/top/bottom/…) |
| `border_width()` | `border_width( array $overwrite = [] )` | Border width select |
| `border_opacity()` | `border_opacity( array $overwrite = [] )` | Border opacity select |
| `border_radius()` | `border_radius( array $overwrite = [] )` | Border radius type select |
| `border_radius_size()` | `border_radius_size( array $overwrite = [] )` | Border radius size select |
| `border_group()` | `border_group( string $prefix = '', array $overwrite = [] )` | All border fields (color/type/width/opacity/radius) |
| `shadow()` | `shadow( array $overwrite = [] )` | Box shadow select |
| `background()` | `background( array $overwrite = [] )` | Single background color select |
| `background_group()` | `background_group( string $prefix = 'bg', array $overwrite = [], array $field_overwrites = [], bool $include_button_colors = false )` | Background color + custom color + gradient + image. `$field_overwrites` keys: `'color'`, `'gradient'`, `'image'` (pass `false` to omit) |
| `display()` | `display( array $overwrite = [] )` | Display select (pass `'device_type'` for responsive) |
| `opacity()` | `opacity( array $overwrite = [] )` | Opacity select |
| `hover_animation()` | `hover_animation( array $overwrite = [] )` | Hover animation multi-select |
| `hover_icon_animation()` | `hover_icon_animation( array $overwrite = [] )` | Hover icon animation select |
| `zindex()` | `zindex( array $overwrite = [] )` | Z-index select |
| `overflow()` | `overflow( array $overwrite = [] )` | Overflow select |
| `scrollbars()` | `scrollbars( array $overwrite = [] )` | Scrollbar style select |

Pass `false` as a value in `$field_overwrites` to omit that sub-field: `background_group( 'bg', [], [ 'image' => false ] )`.

### TypographyFields Methods

| Method | Signature | Returns |
|--------|-----------|---------|
| `font_size()` | `font_size( array $overwrite = [] )` | Font size select (no custom option) |
| `font_size_custom()` | `font_size_custom( array $overwrite = [] )` | Custom font size number input |
| `font_weight()` | `font_weight( array $overwrite = [] )` | Font weight / appearance select |
| `font_case()` | `font_case( array $overwrite = [] )` | Letter case (text-transform) select |
| `font_italic()` | `font_italic( array $overwrite = [] )` | Font italic select |
| `line_height()` | `line_height( array $overwrite = [] )` | Line height number input |
| `text_justify()` | `text_justify( array $overwrite = [] )` | Text justify checkbox |
| `text_align()` | `text_align( array $overwrite = [] )` | Text align select (pass `'device_type'` for responsive) |
| `text_color()` | `text_color( array $overwrite = [] )` | Text color select (no custom option) |
| `text_color_custom()` | `text_color_custom( array $overwrite = [] )` | Custom color picker |
| `font_size_group()` | `font_size_group( string $prefix = 'font_size', array $overwrite = [], $overwrite_custom = [] )` | Font size + custom size (auto-wired `element_require`). Pass `false` for `$overwrite_custom` to omit the custom field |
| `text_align_group()` | `text_align_group( string $prefix = 'text_align', array $overwrite = [] )` | Mobile/Tablet/Desktop responsive text align |
| `text_color_group()` | `text_color_group( string $prefix = 'text_color', array $overwrite = [], $overwrite_custom = [] )` | Text color + custom color (auto-wired `element_require`). Pass `false` for `$overwrite_custom` to omit the custom field |

### LayoutFields Methods

| Method | Signature | Returns |
|--------|-----------|---------|
| `container()` | `container( array $overwrite = [] )` | Container width / type select |
| `position()` | `position( array $overwrite = [] )` | Position class select (static/relative/absolute/fixed/sticky) |
| `sticky_offset()` | `sticky_offset( string $side, array $overwrite = [] )` | Sticky offset number (`$side`: `'top'` or `'bottom'`) |
| `col()` | `col( array $overwrite = [] )` | Bootstrap column width select |
| `row_cols()` | `row_cols( array $overwrite = [] )` | Row columns select |
| `absolute_position()` | `absolute_position( array $overwrite = [] )` | Absolute position select |
| `width()` | `width( array $overwrite = [] )` | Width select (pass `'device_type'` for responsive) |
| `height()` | `height( array $overwrite = [] )` | Height select (pass `'device_type'` for responsive) |
| `max_height()` | `max_height( array $overwrite = [] )` | Max-height text input |

### CommonFields Methods

| Method | Signature | Returns |
|--------|-----------|---------|
| `css_class()` | `css_class( array $overwrite = [] )` | Additional CSS class(es) text input |
| `anchor()` | `anchor( array $overwrite = [] )` | HTML anchor ID text input |
| `metadata_name()` | `metadata_name( array $overwrite = [] )` | Custom block name text input |
| `visibility_conditions()` | `visibility_conditions( array $overwrite = [] )` | Block visibility conditions UI field |
| `hidden()` | `hidden( array $overwrite = [] )` | Generic hidden input (no UI) |
| `style_id()` | `style_id( array $overwrite = [] )` | Hidden field for block style ID (key: `styleid`) |
| `icon_class()` | `icon_class( array $overwrite = [] )` | Icon class text input with picker (key: `icon_class`) |
| `icon_position()` | `icon_position( array $overwrite = [] )` | Icon position select (key: `icon_position`) |
| `new_window()` | `new_window( array $overwrite = [] )` | Open in new window checkbox |
| `nofollow()` | `nofollow( array $overwrite = [] )` | Add nofollow checkbox |
| `attributes()` | `attributes( array $overwrite = [] )` | Custom HTML attributes text input |
| `title_tag()` | `title_tag( array $overwrite = [] )` | Title HTML tag select (h1–h6) |
| `html_tag()` | `html_tag( array $overwrite = [] )` | HTML wrapper element select |
| `title_group()` | `title_group()` | Full title configuration set |

### FlexFields Methods

All methods accept `'device_type'` in `$overwrite` for responsive variants.

| Method | Signature | Returns |
|--------|-----------|---------|
| `align_items()` | `align_items( array $overwrite = [] )` | Align items select |
| `justify_content()` | `justify_content( array $overwrite = [] )` | Justify content select |
| `align_self()` | `align_self( array $overwrite = [] )` | Align self select |
| `order()` | `order( array $overwrite = [] )` | Flex order select |
| `flex_wrap()` | `flex_wrap( array $overwrite = [] )` | Flex wrap select |
| `float()` | `float( array $overwrite = [] )` | Float select |
| `align_items_group()` | `align_items_group( string $prefix = 'flex_align_items', array $overwrite = [] )` | Mobile/Tablet/Desktop responsive |
| `justify_content_group()` | `justify_content_group( string $prefix = 'flex_justify_content', array $overwrite = [] )` | Mobile/Tablet/Desktop responsive |
| `align_self_group()` | `align_self_group( string $prefix = 'flex_align_self', array $overwrite = [] )` | Mobile/Tablet/Desktop responsive |
| `order_group()` | `order_group( string $prefix = 'flex_order', array $overwrite = [] )` | Mobile/Tablet/Desktop responsive |
| `flex_wrap_group()` | `flex_wrap_group( string $prefix = 'flex_wrap', array $overwrite = [] )` | Mobile/Tablet/Desktop responsive |
| `float_group()` | `float_group( string $prefix = 'float', array $overwrite = [] )` | Mobile/Tablet/Desktop responsive |

### ShapeFields Methods

| Method | Signature | Returns |
|--------|-----------|---------|
| `divider_group()` | `divider_group( string $prefix = 'sd', array $overwrite = [], $overwrite_color = [], $overwrite_width = [], $overwrite_image = [] )` | All shape-divider field definitions |

### Color Options — `ayecode_get_sd_colors()` (recommended)

Returns a flat or grouped option array for use as a field's `options` key.

```php
// In a field definition:
'options' => ayecode_get_sd_colors( [ 'none', 'core', 'outline', 'outline_btn_text', 'branding' ] ),

// Grouped (for select2 optgroups):
'options' => ayecode_get_sd_colors( [ 'core', 'outline', 'branding' ], false ),
```

**Signature:** `ayecode_get_sd_colors( array $types = ['core'], bool $flatten = true ): array`

**`$types` options:**

| Type slug | What it adds |
|---|---|
| `'none'` | Prepends `'' => 'None'` (empty/reset option) |
| `'transparent'` | Prepends `'transparent'` |
| `'core'` | Standard Bootstrap colors: primary, secondary, success, danger, warning, info, light, dark, black, white |
| `'subtle'` | Subtle variants that adapt to dark mode (primary-subtle, body, etc.) |
| `'emphasis'` | Emphasis text colors that adapt to dark mode |
| `'outline'` | Outline variants (button use only unless combined with `outline_btn_text`) |
| `'outline_btn_text'` | Appends "(button only)" suffix to outline labels |
| `'branding'` | Social/brand colors: Facebook, Twitter, Instagram, LinkedIn, YouTube, etc. |

**Migration from `sd_aui_colors()`:**

| Legacy `sd_aui_colors()` call | Modern `ayecode_get_sd_colors()` |
|---|---|
| `sd_aui_colors()` | `ayecode_get_sd_colors()` |
| `sd_aui_colors( false, true )` | `ayecode_get_sd_colors( ['core', 'outline'] )` |
| `sd_aui_colors( false, true, true )` | `ayecode_get_sd_colors( ['core', 'outline', 'outline_btn_text'] )` |
| `sd_aui_colors( true, true, true )` | `ayecode_get_sd_colors( ['core', 'outline', 'outline_btn_text', 'branding'] )` |
| `sd_aui_colors( false, false, false, false, true )` | `ayecode_get_sd_colors( ['core', 'subtle'] )` |
| `sd_aui_colors( false, false, false, false, false, true )` | `ayecode_get_sd_colors( ['core', 'emphasis'] )` |

`'core'` is always the baseline. Add `'none'` to prepend a blank option. `$flatten = true` is the default (matches the old function's always-flat output). The underlying class is `ColorOptions::aui()` — use that directly for grouped/unflattened results or to avoid the global function.

Filters: `sd_get_aui_colors` (grouped result), `sd_get_aui_colors_flat` (flat result).

---

## Block Output Modes

### Mode 1 — `block-output` Array (Auto-Compiled)

Simplest approach for presentational blocks with no rich interactions.

```php
'block-output' => array(
    array(
        'element'       => 'BlocksProps',         // wraps with useBlockProps
        'inner_element' => 'p',                   // inner HTML tag
        'blockProps'    => array(
            'className' => '[%WrapClass%]',       // [%WrapClass%] = full AUI class string
        ),
        'content' => 'Hello: [%title%]',          // [%attr_name%] = attribute value
    ),
),
'block-wrap' => '',
```

**Use when:** Block is purely presentational, no RichText, no InnerBlocks, no custom toolbar.

**Advanced `block-output` attribute syntax**

Every key in an element definition follows these rules:

| Key form | Example | Meaning |
|---|---|---|
| Plain key | `'type' => 'button'` | Literal HTML attribute value |
| `if_` prefix | `'if_className' => 'expr'` | JavaScript expression evaluated at block render time |
| `"quoted-key"` | `'"data-bs-toggle"' => 'collapse'` | Literal attribute whose name contains hyphens |
| `if_"quoted-key"` | `'if_"data-bs-target"' => '"#"+props.attributes.anchor'` | JS expression for a hyphenated attribute name |
| `if_x` | `'if_x' => 'props.setAttributes({foo:"bar"})'` | Execute a JS side-effect expression (no attribute created) |
| `if_content` | `'if_content' => 'props.attributes.text'` | JS expression for the element's text content |

**Nesting:** Child arrays within an element definition become child elements:

```php
array(
    'element'    => 'div',
    'className'  => 'accordion-item',
    array(
        'element'   => 'h2',
        'className' => 'accordion-header',
        array(
            'element'             => 'button',
            'if_className'        => 'props.attributes.state === "closed" ? "accordion-button collapsed" : "accordion-button"',
            '"data-bs-toggle"'    => 'collapse',
            'if_"data-bs-target"' => '"#collapse-"+props.attributes.anchor',
            'if_content'          => 'props.attributes.text',
        ),
    ),
),
```

**`innerBlocksProps` element** (nested block InnerBlocks container):

```php
array(
    'element'          => 'innerBlocksProps',
    'blockProps'       => array(
        'if_className' => '"my-container " + [%WrapClass%]',
        'style'        => '{[%WrapStyle%]}',
    ),
    'innerBlocksProps' => array(
        'orientation' => 'vertical',
        'if_template' => "[
            ['namespace/child-block', {text:'Item 1', anchor:'item-1'}],
            ['namespace/child-block', {text:'Item 2', anchor:'item-2'}],
        ]",
    ),
),
```

### Mode 2 — Custom `block-edit-return` / `block-save-return` JS

Provide raw JavaScript expression strings in constructor options.

```php
'block-edit-return' => "
    wp.element.createElement(
        'div',
        wp.blockEditor.useBlockProps({
            className: sd_build_aui_class(props.attributes),
            style: sd_build_aui_styles(props.attributes)
        }),
        el(wp.blockEditor.RichText, {
            tagName: 'span',
            value: props.attributes.text,
            onChange: (text) => props.setAttributes({ text }),
            placeholder: __('Enter text...')
        })
    )
",
'block-save-return' => "
    wp.element.createElement(
        'div',
        wp.blockEditor.useBlockProps.save({
            className: sd_build_aui_class(props.attributes),
            style: sd_build_aui_styles(props.attributes)
        }),
        el(wp.blockEditor.RichText.Content, {
            tagName: 'span',
            value: props.attributes.text
        })
    )
",
'block-wrap' => '',
```

**Available in `block-edit-return`:**
- `props`, `props.attributes`, `props.setAttributes`
- `el` (alias for `wp.element.createElement`)
- `wp.blockEditor.useBlockProps`, `wp.blockEditor.RichText`, `wp.blockEditor.InnerBlocks`, `wp.blockEditor.BlockControls`, `wp.blockEditor.InspectorControls`
- `__` (`wp.i18n.__`)
- `sd_build_aui_class(attrs)`, `sd_build_aui_styles(attrs)`
- `window.sdBlockFunctions.*` (helpers from `block_global_js()`)

**Available in `block-save-return`:**
Same except no `setAttributes`, use `useBlockProps.save()` and `RichText.Content`.

**Use when:** Block needs RichText, InnerBlocks, or custom toolbar controls.

### Mode 3 — PHP Dynamic Output (Server-Side Render)

**Do not** set `block-output`, `block-edit-return`, or `block-save-return`. The block is rendered entirely by the `output()` PHP method.

```php
'block-wrap' => 'div',   // optional wrapper; '' for none
```

```php
public function output( $args = array(), $widget_args = array(), $content = '' ): string {
    $args = wp_parse_args( $args, array(
        'title'      => '',
        'show_count' => '',
    ) );

    if ( empty( $args['title'] ) && ! $this->is_preview() ) {
        return '';
    }

    $wrap_class = sd_build_aui_class( $args );

    return sprintf(
        '<div class="%s"><h2>%s</h2></div>',
        esc_attr( $wrap_class ),
        esc_html( $args['title'] )
    );
}
```

**Guidelines:**
- Always `wp_parse_args()` with full defaults
- Use `$this->is_preview()` to detect block editor context
- Never echo — always return a string
- Use `sd_build_aui_class( $args )` for wrapper class
- Public-facing icons: `ayecode_get_icon( 'fa-name' )` — never raw `<i>` tags (raw `<i>` tags are fine in wp-admin only)
- Escape all output: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- `$content` holds InnerBlocks content for nested blocks

**Use when:** Block queries the database, reads user/post context, or generates dynamic markup (most data-driven blocks).

### `block_global_js()` Method

Optional method for injecting global JS helpers for the editor.

**Always wrap the JS in `<script>` tags** — this enables IDE syntax highlighting and autocomplete. The tags are stripped at runtime by `str_replace()`:

```php
public function block_global_js(): string {
    ob_start();
    ?>
    <script>
    function my_helper(attrs) { return attrs.some_field || ''; }
    window.sdBlockFunctions.my_helper = my_helper;
    </script>
    <?php
    return str_replace( [ '<script>', '</script>' ], '', ob_get_clean() );
}
```

---

## Output Format Decision Guide

```
Block pulls live data or PHP context  →  Mode 3 (PHP dynamic)
Block is purely presentational/static →  Mode 1 (block-output array)
Block needs RichText / InnerBlocks    →  Mode 2 (custom JS)
Shared JS helpers needed in editor    →  add block_global_js()
```

---

## AyeCode Standards Checklist

- [ ] Method names are `snake_case`; class name is `PascalCase`
- [ ] No `die()` or `exit()` — use `wp_die()` if needed
- [ ] All user-facing strings wrapped in `__()` or `esc_html__()` with correct textdomain
- [ ] `wp_unslash()` before sanitizing any `$_POST`/`$_GET` input
- [ ] AJAX handlers verify nonce + `current_user_can()` before processing
- [ ] No hardcoded `<script>` or `<link>` tags — use `wp_enqueue_script`/`wp_enqueue_style`
- [ ] Public-facing icons use `ayecode_get_icon()`, not raw `<i>` tags
- [ ] No version number bumps during development
- [ ] No global PHP functions — all code inside the class
- [ ] Bootstrap 5.3 utility classes for layout/styling; custom CSS only as last resort
- [ ] `$wpdb->prepare()` for any direct SQL

---

## Common Patterns

### Standard Three-Tab Block with Full Styling

```php
public function set_arguments(): array {
    return ( new BlockArguments() )
        // Content tab
        ->add_field( 'title', [ 'type' => 'text', 'title' => __( 'Title', 'td' ), 'group' => 'content' ] )

        // Styles tab
        ->add_typography_group()
        ->add_background_group()
        ->add_responsive_margins()
        ->add_responsive_paddings()
        ->add_border_group()
        ->add_shadow_group()
        ->add_responsive_display_group()

        // Advanced tab
        ->add_position()
        ->add_sticky_offset_group()
        ->add_visibility_conditions()
        ->add_advanced_group()
        ->get();
}
```

### Custom Field with Conditional Display

```php
->add_field( 'show_icon', [
    'type'    => 'checkbox',
    'title'   => __( 'Show Icon', 'td' ),
    'group'   => 'content',
    'default' => '1',
] )
->add_field( 'icon_class', [
    'type'            => 'text',
    'title'           => __( 'Icon Class', 'td' ),
    'group'           => 'content',
    'element_require' => '[%show_icon%]',  // Only show when show_icon is checked
] )
```

### Dependent Field (Post Type → Category)

```php
->add_field( 'post_type', [
    'type'    => 'select',
    'title'   => __( 'Post Type', 'td' ),
    'options' => [ 'post' => 'Posts', 'page' => 'Pages' ],
    'group'   => 'content',
] )
->add_field( 'category', [
    'type'  => 'select',
    'title' => __( 'Category', 'td' ),
    'group' => 'content',
    'depends_on' => [
        'attribute'  => 'post_type',
        'fetch_type' => 'rest',
        'rest_path'  => '/wp/v2/{post_type}category/?per_page=100',
    ],
] )
```

### Nested Block (Parent + Child)

Parent block accepts only its designated children; child block is locked to its parent:

```php
// Parent constructor options
$options = array(
    'nested-block'   => true,
    'allowed-blocks' => array( 'myplugin/child-block' ),
    'block-wrap'     => '',
    'block-output'   => array(
        array(
            'element'          => 'innerBlocksProps',
            'blockProps'       => array(
                'if_className' => '"my-container " + [%WrapClass%]',
                'style'        => '{[%WrapStyle%]}',
            ),
            'innerBlocksProps' => array(
                'orientation' => 'vertical',
                'if_template' => "[
                    ['myplugin/child-block', {text:'Item 1', anchor:'item-1'}],
                    ['myplugin/child-block', {text:'Item 2', anchor:'item-2'}],
                ]",
            ),
        ),
    ),
    // ...
);

// Child constructor options
$options = array(
    'nested-block'   => true,
    'parent'         => array( 'myplugin/parent-block' ),
    'block-supports' => array( 'anchor' => false, 'customClassName' => false ),
    'block-wrap'     => '',
    // ...
);
```

### Inline Tabs within a Field Group (Normal / Hover)

Use the `tab` key in `$overwrite` to embed tab panes directly inside a group's fields — ideal for Normal/Hover variants without adding standalone tab fields:

```php
use AyeCode\SuperDuper\Fields\StyleFields;
use AyeCode\SuperDuper\Fields\TypographyFields;

// Open tab container + first pane on the first group
->add_fields( StyleFields::background_group( 'link_bg', array(
    'group' => 'button',
    'tab'   => array(
        'title'     => __( 'Normal', 'td' ),
        'key'       => 'btn_normal',
        'tabs_open' => true,   // opens the tab container
        'open'      => true,   // this pane is active by default
        'class'     => 'text-center w-50 d-flex justify-content-center',
    ),
) ) )
->add_field( 'text_color', TypographyFields::text_color( array( 'group' => 'button' ) ) )
->add_field( 'text_color_custom', TypographyFields::text_color_custom( array(
    'group' => 'button',
    'tab'   => array( 'close' => true ),  // close this pane
) ) )

// Open second pane
->add_fields( StyleFields::background_group( 'bg_hover', array(
    'group' => 'button',
    'tab'   => array(
        'title' => __( 'Hover', 'td' ),
        'key'   => 'btn_hover',
        'open'  => true,
        'class' => 'text-center w-50 d-flex justify-content-center',
    ),
) ) )
->add_field( 'text_color_hover', TypographyFields::text_color( array( 'group' => 'button' ) ) )
->add_field( 'text_color_hover_custom', TypographyFields::text_color_custom( array(
    'group' => 'button',
    'tab'   => array( 'close' => true, 'tabs_close' => true ),  // close pane + container
) ) )
```

### Using Static Field Classes Directly

```php
use AyeCode\SuperDuper\Fields\StyleFields;
use AyeCode\SuperDuper\Fields\FlexFields;

public function set_arguments(): array {
    return ( new BlockArguments() )
        ->add_field( 'title', [ 'type' => 'text', 'title' => __( 'Title', 'td' ), 'group' => 'content' ] )
        ->add_fields( FlexFields::justify_content_group( 'flex_justify_content', [ 'group' => 'styles' ] ) )
        ->add_field( 'hover_animation', StyleFields::hover_animation( [ 'group' => 'styles' ] ) )
        ->get();
}
```

