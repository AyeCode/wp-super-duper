# Global Helper Functions

The functions in `includes/functions.php` are the public contract a block author calls from
`set_arguments()` and `output()`. Every one is a one-line wrapper over a static method on
`AyeCode\SuperDuper\Utils`, `Registry` or `Helpers\ColorOptions`. Both spellings are supported
and behave identically; the global is the one consuming plugins use.

```php
sd_build_aui_styles( $args );                        // global
\AyeCode\SuperDuper\Utils::build_aui_styles( $args ); // the same thing
```

Functions in `includes/functions-deprecated.php` are a different matter. They still work, but
they are either the old `sd_get_*` field-input helpers that the `Fields\*` statics and the
`BlockArguments` builder replaced, or wrappers over framework plumbing that only Super Duper
itself should call. Nothing new goes in that file. See [Fields](fields.md) for the field-input
replacements.

## Contents

- [Registration](#registration)
- [Colors](#colors)
- [Output attributes](#output-attributes)
- [CSS value sanitizers](#css-value-sanitizers)
- [Content and templates](#content-and-templates)
- [Widget registration](#widget-registration)
- [URLs](#urls)

---

## Registration

### `ayecode_sd_register( string $base_id, string $class_name, array $output_types = [ 'block', 'shortcode' ], string $file_path = '' ): void`

Registers a block class with the lazy registry. The class file is not loaded and the class is
not instantiated until a shortcode or block with this `base_id` is actually rendered. On admin
and AJAX requests every registered class is built eagerly so the editor and preview handlers
work.

| Parameter | Meaning |
|---|---|
| `$base_id` | The shortcode tag and block slug, for example `bs_alert`. |
| `$class_name` | The class, for example `BlockStrap_Widget_Alert`. |
| `$output_types` | Any of `block`, `shortcode`, `widget`. Must match the `output_types` key in the class's `$options`. `widget` is the one type that forces instantiation on every page load, so leave it out unless the block belongs in a sidebar. |
| `$file_path` | Absolute path to the class file. Required when the class is not autoloadable, which is most plugins. |

```php
add_action( 'widgets_init', function () {
    ayecode_sd_register( 'bs_alert', 'BlockStrap_Widget_Alert', [ 'block', 'shortcode' ], __DIR__ . '/blocks/class-blockstrap-widget-alert.php' );
} );
```

Classes do not register themselves and `new My_Block()` is not the way. See
[Block Building](block-building.md#registration).

---

## Colors

### `ayecode_get_sd_colors( array $types = [ 'core' ], bool $flatten = false ): array`

Returns option arrays for `select` and `color` fields. These are options, not field
definitions. Wraps `Helpers\ColorOptions::aui()`.

| `$types` entry | Adds |
|---|---|
| `none` | An empty "None" option first |
| `transparent` | Transparent |
| `core` | The Bootstrap theme colors (primary, secondary, success, ...) |
| `subtle` | The `-subtle` background variants |
| `emphasis` | The `-emphasis` text variants |
| `outline` | The `outline-*` variants |
| `outline_btn_text` | Only changes labels: appends "(button only)" to each outline option. Needs `outline` too. |
| `branding` | The social and brand colors from `ColorOptions::branding()` |

With `$flatten` false (the default) the result is grouped into optgroups. With `$flatten` true
it is a flat `value => label` array.

```php
->add_field( 'bg', [
    'type'    => 'select',
    'title'   => __( 'Background', 'ayecode-connect' ),
    'options' => ayecode_get_sd_colors( [ 'none', 'core', 'subtle' ], true ),
] )
```

Replaces the removed `sd_aui_colors()` and its boolean flags.

### `sd_get_color_from_var( $var )`

Turns a color slug into a CSS variable reference. A value containing `#` is returned untouched.
Otherwise it becomes `var(--wp--preset--color--{slug})` when BlockStrap is active and
`var(--{slug})` when it is not. Used by the hover style builder; useful when you write a
color into your own CSS.

```php
sd_get_color_from_var( 'primary' ); // var(--wp--preset--color--primary)
sd_get_color_from_var( '#ff0000' ); // #ff0000
```

The return value is not validated as a color. If the slug came from user input, run the result
through `Utils::sanitize_css_color()` before writing it anywhere.

---

## Output attributes

The settings a block inherits from the standard groups (margins, padding, colors, display,
border, typography, background, position and so on) are turned into HTML by one family of
functions. The two **builders** return a bare value. The **attribute helpers** return a
finished attribute, or nothing when it would be empty. Use the attribute helpers unless you
need to merge the value into something else.

All of them share one spacing convention: **no leading space, a trailing space when there is
output**, so they drop straight into an opening tag.

```php
public function output( $args = [], $widget_args = [], $content = '' ): string {
    $html  = '<figure ' . sd_build_aui_wrapper_attrs( $args, 'overflow-hidden' ) . '>';
    $html .= '<a href="' . esc_url( $args['url'] ) . '" ' . sd_build_attributes_string_escaped( $args ) . '>';
    // ...
    return $html;
}
```

Renders as `<figure class="overflow-hidden mb-3 text-primary" style="font-size:2rem;" >`, and
as `<figure >` when the block has no settings that produce output.

### `sd_build_aui_wrapper_attrs( $args, $extra_classes = '' )`

The class and style attributes for the wrapper element in one call. Equivalent to
`sd_build_aui_class_attr( $args, $extra_classes ) . sd_build_aui_style_attr( $args )`. Either
attribute is left out when empty, so an unstyled block never prints `style=""`.

### `sd_build_aui_class_attr( $args, $extra_classes = '' )`

Returns `class="..." ` or an empty string. `$extra_classes` is a space-separated list the block
wants on the same element; it goes first. Every token, yours included, is run through
`sanitize_html_class()`, and repeated whitespace is collapsed.

### `sd_build_aui_style_attr( $args )`

Returns `style="..." ` or an empty string. The value is `sd_build_aui_styles( $args )`, escaped
for the attribute. Do not pass the result through any other sanitizer.

### `sd_build_aui_class( $args )`

The bare class string, for when you need to combine it with classes computed elsewhere.
Settings that produce classes: the responsive margin and padding keys (`mt`, `mb`, `ml`, `mr`,
`pt`, `pb`, `pl`, `pr` and their `_md` and `_lg` variants), `text_color`, `text_align`,
`text_justify`, `display`, `col`, `row_cols`, `border`, `border_type`, `rounded`,
`rounded_size`, `bg` (the named colors), `bg_image_fixed`, `ratio`, `absolute_position`,
`hover_animations` and `hover_icon_animation`.

Each class is built from a value passed through `sanitize_html_class()`, so the string is safe
in a class attribute as it is. `sd_build_aui_class_attr()` adds the attribute itself, merges
your extra classes and handles the empty case.

### `sd_build_aui_styles( $args )`

The bare style value, for example `background-color:#fff;font-size:1.2rem;`. Settings that
produce inline styles:

| Setting | Property | Validation |
|---|---|---|
| `bg` = `custom-color` with `bg_color` | `background-color` | color |
| `bg` = `custom-gradient` with `bg_gradient` | `background-image` | gradient |
| `bg_on_text` with a gradient | `background-clip: text` and the text-fill pair | none needed |
| `bg_image` | `background-image: url(...)`, `background-size`, blend mode when layered on a color | URL |
| `bg_image_fixed` | `background-attachment` | none needed |
| `bg_image_xy` | `background-position` | numeric |
| `sticky_offset_top`, `sticky_offset_bottom` | `top`, `bottom` | integer |
| `font_size_custom` | `font-size` in rem | numeric |
| `text_color_custom` | `color` | color |
| `font_line_height` | `line-height` | length |
| `max_height` | `max-height` | length |

Every free-text value is validated as the CSS type in the last column before it is used, and
a value that fails is dropped rather than printed. This matters because block attributes
survive `kses`: a Contributor can store anything in them, and attribute escaping alone does not
stop `red;position:fixed;...` from adding its own declarations. See
[CSS value sanitizers](#css-value-sanitizers).

The return value is already escaped for a double-quoted attribute, so
`' style="' . sd_build_aui_styles( $args ) . '"'` is safe. Even so, prefer
`sd_build_aui_style_attr()`; it also handles the empty case.

**Never pass a style string through `sd_sanitize_html_classes()`.** It strips `:` and `;`,
which turns `font-size:2rem;` into `font-size2rem` and silently kills every style setting on
the block. Test the style string, not the class string, when deciding whether to print a style
attribute.

### `sd_build_hover_styles( $args, $is_preview = false, $inner = '' )`

Builds the `:hover` rules for the block from `text_color_hover`, `text_color_hover_custom`,
`bg_hover`, `bg_hover_color` and `bg_hover_gradient`, scoped to the block's `styleid` class.
Returns a complete `<style>` element, or an empty string when there are no hover settings or no
`styleid` (a typed shortcode or a widget has none, so it prints nothing rather than an unscoped
rule). With `$is_preview` true the selector is prefixed for the editor canvas. Everything
written into the element is validated first, because a stored value that could close the
`<style>` tag or end the selector would otherwise be an injection point: the style id goes
through `Utils::sanitize_css_selector()`, colors through `Utils::sanitize_css_color()` and the
gradient through `Utils::sanitize_css_gradient()`.

To aim the rules at an element inside the block, pass its selector as the third argument.
Appending the tail to the style id still works, but when the id is empty that leaves a bare
tail, which the function now drops, so prefer the parameter:

```php
$html .= sd_build_hover_styles( $args, $this->is_preview(), '.btn' );
```

```php
$html .= sd_build_hover_styles( $args, $this->is_preview() );
```

### `sd_build_attributes_string_escaped( $args )`

Escaped `key="value" ` pairs for the link or inner element, built from three settings:

| Setting | Produces |
|---|---|
| `custom` | Whatever the user typed, parsed by `sd_parse_custom_attributes()` |
| `new_window` | `target="_blank"` |
| `nofollow` | `rel="nofollow"`, appended to any `rel` already present |

Keys are lower-cased and limited to `[-_a-z0-9]`. `href` and anything beginning `on` are
dropped, so a user cannot add an event handler through the custom attributes field. Both key
and value are passed through `esc_attr()`.

These attributes belong on the element the settings describe, usually the link, not on the
wrapper. That is why this function is separate from `sd_build_aui_wrapper_attrs()`.

### `sd_parse_custom_attributes( $attributes_string, $delimiter = ',' )`

Parses the `custom` field's `key|value,key2|value2` format into an array, applying the key
rules above. Returns the array unescaped; `sd_build_attributes_string_escaped()` is the normal
consumer.

```php
sd_parse_custom_attributes( 'data-id|42,aria-label|Open' );
// [ 'data-id' => '42', 'aria-label' => 'Open' ]
```

### `sd_sanitize_html_classes( $classes, $sep = ' ' )`

Runs each class through `sanitize_html_class()`. Accepts an array or a string split on `$sep`.
Returns a space-separated string with a trailing space. For classes only; see the warning
under `sd_build_aui_styles()`.

---

## CSS value sanitizers

These are `Utils` statics with no global wrapper. They back the validation in
`sd_build_aui_styles()` and `sd_build_hover_styles()`, and they are public so a block can apply
the same rules to a CSS value it prints itself. Each returns the trimmed value when it passes
and an empty string when it does not. The value sanitizers reject `;`, `{`, `}`, `<`, `>`,
backslash, `url(`, `expression`, `javascript`, `@import`, `behavior` and `-moz-binding`
outright; the selector sanitizer strips rather than rejects, so a bad character never costs the
block its hover rules, it just stops matching.

| Method | Accepts |
|---|---|
| `Utils::sanitize_css_color( $value )` | Hex from `#rgb` to `#rrggbbaa`; a keyword (named colors, `transparent`, `currentColor`, `inherit`); `rgb()`, `rgba()`, `hsl()`, `hsla()`, `hwb()`, `lab()`, `lch()`, `oklab()`, `oklch()` or `color()` with simple arguments; `var(--name)` with an optional fallback of any of those. |
| `Utils::sanitize_css_length( $value )` | A number with an optional unit (unitless is allowed, as `line-height` needs it); the keywords `auto`, `none`, `normal`, `inherit`, `initial`, `unset`, `fit-content`, `max-content`, `min-content`; `calc()`, `min()`, `max()` or `clamp()` built from numbers, units and operators; `var(--name)` with an optional numeric fallback. |
| `Utils::sanitize_css_gradient( $value )` | One or more comma-separated `linear-gradient()`, `radial-gradient()` or `conic-gradient()` functions, including the `repeating-` forms, with one level of nested function such as `rgba()` inside; or a `var(--name)` reference to a preset gradient. |
| `Utils::sanitize_css_selector( $value )` | A class name, optionally followed by a selector tail, for use where a stored id is written into a `<style>` element. Keeps letters, digits, `_`, `-`, `.`, `#`, `:`, `~`, `+`, `>` and single spaces; everything else is removed. |
| `Utils::sanitize_css_url( $value )` | A URL that passes `esc_url_raw()`. The characters that would end or confuse an unquoted `url()` token (`(`, `)`, `;`, quotes, space, backslash) are percent-encoded. Ampersands are left as `&` for the attribute escape to handle. |

```php
$color = \AyeCode\SuperDuper\Utils::sanitize_css_color( $args['my_accent'] );
if ( '' !== $color ) {
    $html .= '<span style="border-color:' . esc_attr( $color ) . ';">';
}
```

Validation is not escaping. A value that passes still goes through `esc_attr()` when it lands
in an attribute, as the builders do.

---

## Content and templates

### `sd_replace_variables( $text )`

Replaces dynamic data tags such as `{post_title}` or `{current_date:Y @fallback:'n/a'}` with
their values. Values are escaped by the renderer. Wraps `DynamicData\Renderer::render()`; the
tag syntax and the available tags are documented in [Dynamic Data](features/dynamic-data.md).

```php
$title = sd_replace_variables( $args['title'] );
```

### `sd_build_shortcode( $name, $args = [], $content = '' )`

Compiles a shortcode string. `$name` can be a bare tag or a full `[tag a='1']` string; in the
second case the attributes already in the string act as defaults and `$args` wins. The keys
`content`, `sd_shortcode`, `sd_shortcode_close` and `metadata` are dropped, array values are
joined with commas, and both keys and values are escaped. When `$content` is non-empty a closing
tag is appended.

```php
sd_build_shortcode( 'bs_alert', [ 'type' => 'info', 'text' => 'Hi' ] );
// [bs_alert  type='info'  text='Hi' ]
```

### `sd_get_shortcode_slug( $str )`

The tag from a shortcode string or a bare tag: `[bs_alert type='a']` and `bs_alert` both give
`bs_alert`.

### `sd_template_page_options( $args = [] )`

`ID => title` for published pages, for a `select` field. The front page and the posts page are
excluded. Cached per request.

| `$args` key | Default | Meaning |
|---|---|---|
| `nocache` | `false` | Re-query instead of using the per-request cache |
| `with_slug` | `false` | Append the slug to each label |
| `default_label` | "Select Page..." | Label of the empty first option |

Filters: `sd_template_page_options_limit` (default 500) and `sd_template_page_options`.

### `sd_template_part_options( $args = [] )`

`slug => title (#slug)` for block template parts, with an empty "Select Template Part..."
option first. Cached per request. Filter: `sd_template_part_options`.

### `sd_get_template_part_by_slug( $slug )`

The published template part object for a slug, or an empty array when there is none. Cached
per request. Filter: `sd_get_template_part_by_slug`.

### `sd_get_page_content( $page_id )`

The content of a page with shortcodes and blocks rendered. Filters: `sd_bypass_page_content`
(return a non-empty string to replace the content before rendering) and `sd_get_page_content`.

### `sd_get_template_part_content( $template_part )`

The same for a template part, by slug. Filters: `sd_bypass_template_part_content` and
`sd_get_template_part_content`.

---

## Widget registration

### `sd_pagenow_exclude()`

The wp-admin page filenames on which widget registration is skipped, to keep screens that never
show widgets fast. Default: `upload.php`, `edit-comments.php`, `edit-tags.php`, `index.php`,
`media-new.php`, `options-discussion.php`, `options-writing.php`, `edit.php`, `themes.php`,
`users.php`. Filter: `sd_pagenow_exclude`.

### `sd_widget_exclude()`

Widget class names that are never registered. Empty by default. Filter: `sd_widget_exclude`.

---

## URLs

### `ayecode_get_current_url( $with_query_string = true )`

The current request URL as a raw, un-HTML-escaped string suitable for redirects, storage and
HTTP requests. Built from `REQUEST_URI` (falling back to `SCRIPT_NAME` and `QUERY_STRING`),
passed through `home_url()` and `esc_url_raw()`. Escape it with `esc_url()` before printing it.
