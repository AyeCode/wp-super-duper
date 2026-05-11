# Block Output Formats

WP Super Duper supports three distinct modes for defining how a block looks and behaves in the
Gutenberg editor. Choose the mode that fits the block's complexity.

---

## Mode 1 — `block-output` Array (auto-compiled)

The simplest approach. Pass a `block-output` array in the constructor `$options`. The framework
automatically generates both `block-edit-return` and `block-save-return` JavaScript from it.

**When to use:** Presentational blocks with no rich editor interactions (no RichText, no
InnerBlocks, no custom toolbar controls).

```php
$options = array(
    // ...
    'block-output' => array(
        array(
            'element'       => 'BlocksProps',   // wraps with useBlockProps
            'inner_element' => 'p',             // inner HTML tag
            'blockProps'    => array(
                'className' => '[%WrapClass%]', // built-in: AUI class + custom class
            ),
            'content' => 'Hello: [%after_text%]', // [%attr_name%] interpolates attribute values
        ),
    ),
    'block-wrap' => '',
);
```

**`[%…%]` attribute interpolation:**

| Token | Resolves to |
|-------|-------------|
| `[%WrapClass%]` | The full AUI/Bootstrap class string built from all style attributes |
| `[%attr_name%]` | The value of `attributes.attr_name` at render time |

**Element keys:**

| Key | Purpose |
|-----|---------|
| `element` | Outer HTML tag or `'BlocksProps'` (applies `useBlockProps`) |
| `inner_element` | Optional inner tag when `element` is `'BlocksProps'` |
| `blockProps` | Props passed to `useBlockProps` (e.g. `className`, `style`) |
| `content` | Text/attribute content of the element |
| `class` | CSS class on the element (alias when not using `blockProps`) |

---

## Mode 2 — Custom `block-edit-return` / `block-save-return` JS

Provide raw JavaScript expression strings in the constructor options. These are injected directly
into the compiled edit and save React components and have full access to the block editor API.

**When to use:** Blocks that need `RichText`, `InnerBlocks`, custom toolbar controls, or any
editor UI that can't be expressed with the `block-output` array.

```php
$options = array(
    // ...
    'block-edit-return' => "
        wp.element.createElement(
            'div',
            wp.blockEditor.useBlockProps({
                className: 'alert alert-' + props.attributes.alert_type
                    + ' ' + sd_build_aui_class(props.attributes),
                style: sd_build_aui_styles(props.attributes),
                role: 'alert'
            }),
            el(wp.blockEditor.RichText, {
                tagName: 'span',
                value: props.attributes.text,
                onChange: function(text) { props.setAttributes({ text: text }); },
                placeholder: __('Enter text...')
            })
        )
    ",
    'block-save-return' => "
        wp.element.createElement(
            'div',
            wp.blockEditor.useBlockProps.save({
                className: 'alert alert-' + props.attributes.alert_type
                    + ' ' + sd_build_aui_class(props.attributes),
                style: sd_build_aui_styles(props.attributes),
                role: 'alert'
            }),
            el(wp.blockEditor.RichText.Content, {
                tagName: 'span',
                value: props.attributes.text
            })
        )
    ",
    'block-wrap' => '',
);
```

Modern JavaScript (arrow functions, template literals, destructuring) is fully supported.

### Variables available in `block-edit-return`

| Variable | Value |
|----------|-------|
| `props` | Full block props object |
| `props.attributes` | Block attribute values |
| `props.setAttributes` | Attribute setter function |
| `el` | Alias for `wp.element.createElement` |
| `wp.blockEditor.useBlockProps` | Hook for edit wrapper props |
| `wp.blockEditor.RichText` | RichText control |
| `wp.blockEditor.InnerBlocks` | InnerBlocks control |
| `wp.blockEditor.BlockControls` | Toolbar slot |
| `wp.blockEditor.InspectorControls` | Sidebar slot |
| `__` | `wp.i18n.__` |
| `sd_build_aui_class(attrs)` | Builds Bootstrap/AUI class string from attributes |
| `sd_build_aui_styles(attrs)` | Builds inline style object from attributes |
| `window.sdBlockFunctions.*` | Any helpers registered via `block_global_js()` |

### Variables available in `block-save-return`

Same as edit, **except**: no `setAttributes`, no interactive controls. Use
`wp.blockEditor.useBlockProps.save()` instead of `useBlockProps`, and
`wp.blockEditor.RichText.Content` instead of `wp.blockEditor.RichText`.

---

## Mode 3 — PHP Dynamic Output (server-side render)

Do **not** set `block-output`, `block-edit-return`, or `block-save-return`. The block is rendered
entirely by the `output()` PHP method. The editor shows a live AJAX preview of the PHP output.

**When to use:** Any block that queries the database, reads user/post context, or generates markup
that can't be statically serialised (the vast majority of data-driven blocks).

```php
$options = array(
    // ...
    'block-wrap' => 'div',   // optional wrapper element; '' for none
    // No block-output, block-edit-return, or block-save-return keys
);
```

The `output()` method handles everything:

```php
public function output( $args = array(), $widget_args = array(), $content = '' ): string {
    $args = wp_parse_args( $args, array(
        'title'      => '',
        'show_count' => '',
    ) );

    $wrap_class = sd_build_aui_class( $args );

    // Return early if there's nothing to render.
    if ( empty( $args['title'] ) ) {
        return '';
    }

    return sprintf(
        '<div class="%s"><h2>%s</h2></div>',
        esc_attr( $wrap_class ),
        esc_html( $args['title'] )
    );
}
```

---

## `block_global_js()` Method

An optional method you can add to any block class. Returns a JavaScript string (no `<script>` tags)
that is injected globally into the block editor once, before the block is registered. Use it to
define helper functions that `block-edit-return` / `block-save-return` strings can call.

All helpers **must** be registered on `window.sdBlockFunctions` so the framework can manage them.

```php
public function block_global_js(): string {
    ob_start();
    ?>
    function my_block_build_icon_class(attrs) {
        if (attrs.icon_class) return attrs.icon_class;
        if (attrs.alert_type === 'info') return 'fas fa-info-circle';
        if (attrs.alert_type === 'warning') return 'fas fa-exclamation-triangle';
        if (attrs.alert_type === 'success') return 'fas fa-check-circle';
        return '';
    }
    window.sdBlockFunctions.my_block_build_icon_class = my_block_build_icon_class;
    <?php
    return ob_get_clean();
}
```

The helper is then callable inside `block-edit-return` / `block-save-return` as
`my_block_build_icon_class(props.attributes)`.

---

## Quick Decision Guide

```
Block pulls live data or PHP context  →  Mode 3 (PHP dynamic)
Block is purely presentational/static →  Mode 1 (block-output array)
Block needs RichText / InnerBlocks    →  Mode 2 (custom JS)
Shared JS helpers needed in editor    →  add block_global_js()
```

---

## `block-wrap` Option

Controls the wrapper element added around PHP dynamic output. Set in constructor options.

| Value | Result |
|-------|--------|
| `'div'` | Wraps output in `<div>` |
| `'span'` | Wraps output in `<span>` |
| `''` (empty) | No wrapper — `no_wrap` must also be `true` |

Typically paired with `'no_wrap' => true` when set to empty string.
