# Dynamic Data

Any field registered with `'dynamic_data' => true` has its tags replaced at render time, for example `Copyright {site_title} {current_date:Y}`. The classes live in `src/DynamicData/`.

| Class | Role |
|---|---|
| `Grammar` | Finds, parses and builds tag strings |
| `Registry` | Groups, tags and option sources; the registration API |
| `CoreTags` | The tags and option sources that ship with Super Duper |
| `Renderer` | Resolves and escapes tags in a string |
| `Context` | The post, user and queried object a tag resolves against |
| `RestApi` | Schema and option endpoints for the editor picker |

`Utils::replace_variables( $text )` is a thin wrapper around `Renderer::render( $text )`.

## Syntax

```
{name}
{name:primary value}
{name @key:'value' @key:value @flag}
{name:primary value @key:'value' @flag}
```

- **name** is lowercase `[a-z0-9_]` straight after the `{`.
- **Primary value:** a tag can mark one arg as primary. `:value` sets it and runs to the first whitespace followed by `@`, or to the `}`. Colons, spaces, commas and slashes need no quoting: `{current_date:g:i A}`, `{post_date:F j, Y}`.
- **Named args:** `@key:value`. Quote the value with single quotes when it contains whitespace, `'`, `{`, `}` or `@`. Inside quotes, `\'` is a quote and `\\` a backslash. A named value wins over the primary shorthand when both set the same arg.
- **Flags:** `@link` is a named arg with no value.
- Double quotes are never used, so a tag can sit inside a shortcode attribute.

Anything that does not fit, an unknown name, or a primary value on a tag that takes none, is left in the text exactly as written. That keeps CSS (`.a{color:red}`), JSON and code samples intact. Return `''` from the `sd_dynamic_data_unknown_tag` filter to remove unknown tags instead.

Unknown `@args` on a known tag are ignored, so content written for a newer version still renders on an older one.

### Modifiers

Shared args every suitable tag accepts:

| Modifier | Example | Applies to |
|---|---|---|
| `@words:N` | `{post_excerpt @words:20}` | `text` and `html` tags. Strips tags and trims to N words. |
| `@link` | `{post_title @link}` | Tags with a `link` callback, except `url` tags |
| `@new_tab` | `{post_title @link @new_tab}` | With `@link` |
| `@fallback:'text'` | `{post_meta:price @fallback:'POA'}` | Every tag. Shown when the value is empty. |

### Examples

```
{post_title @link @new_tab}
{post_excerpt @words:20 @fallback:'No excerpt yet'}
{current_date:Y}
{current_date @format:'g:i A'}
{post_meta:price @fallback:'POA'}
{post_terms:category @separator:' / '}
{featured_image_url:medium}
{request_parameter:ref @fallback:direct}
```

## Registering tags

Register on `sd_dynamic_data_register`. It fires the first time a tag is looked up, so it costs nothing on requests that render none.

```php
use AyeCode\SuperDuper\DynamicData\Context;
use AyeCode\SuperDuper\DynamicData\Registry;

add_action( 'sd_dynamic_data_register', static function () {
	Registry::register_group( 'listing', __( 'Listing', 'ayecode-connect' ), 30 );

	Registry::register( 'listing_field', array(
		'label'    => __( 'Listing Field', 'ayecode-connect' ),
		'group'    => 'listing',
		'returns'  => 'text',
		'args'     => array(
			array(
				'key'      => 'field',
				'label'    => __( 'Field', 'ayecode-connect' ),
				'type'     => 'combobox',
				'source'   => 'listing_fields',
				'primary'  => true,
				'required' => true,
			),
		),
		'link'     => static fn( array $args, Context $c ) => $c->post() ? get_permalink( $c->post() ) : '',
		'callback' => static fn( array $args, Context $c ) => $c->post() ? get_listing_value( $c->post()->ID, $args['field'] ) : null,
	) );

	Registry::register_source( 'listing_fields', static fn( array $params ) => array(
		array( 'value' => 'price', 'label' => __( 'Price', 'ayecode-connect' ) ),
	) );
} );
```

Registering an existing name replaces it, so an addon can override a core tag. `Registry::unregister( $name )` removes one.

### Tag definition

| Key | Type | Notes |
|---|---|---|
| `label` | string | Shown in the picker |
| `group` | string | Group key |
| `description` | string | Optional help text |
| `returns` | string | `text` (default), `html`, `url` or `number`. Decides the escaping. |
| `args` | array | See below |
| `link` | callable | `fn( array $args, Context $c ): string`. Enables `@link` and `@new_tab`. |
| `modifiers` | string[] | Optional. Narrows the modifiers that would otherwise apply. |
| `callback` | callable | `fn( array $args, Context $c )`. Return null or `''` for empty. Non-scalar values render as empty. |

### Arg definition

| Key | Type | Notes |
|---|---|---|
| `key` | string | Used as `@key` |
| `label` | string | |
| `type` | string | `text`, `number`, `toggle`, `select`, `combobox`, `date_format` |
| `default` | mixed | Used when not given |
| `primary` | bool | Filled by the `{name:value}` shorthand. One per tag. |
| `required` | bool | Without it the callback is not called and the fallback shows |
| `options` | array or callable | `value => label`. A callable is only called for the editor schema. |
| `source` | string | Option source key, fetched by the editor |
| `placeholder`, `help` | string | |

Values reach the callback typed: `number` as int or float, `toggle` as bool, and a `select` with static options only accepts a value from them.

## Escaping

Callbacks return raw values. The renderer escapes by `returns`: `esc_html` for text, `wp_kses_post` for html, `esc_url` for url, and a numeric cast for number. Fallback text is always `esc_html`. A tag cannot supply its own escaper. The final, escaped output passes through the `sd_dynamic_data_output` filter.

## Meta keys

| Tag | Reads | Default rule |
|---|---|---|
| `{post_meta:key}` | the current post | any key except protected ones |
| `{term_meta:key}` | the queried term | any key except protected ones |
| `{author_meta:key}` | the post author | allowlist only |
| `{user_meta:key}` | the logged in visitor | allowlist only |

Anyone who can edit a block can type any meta key, so every meta tag refuses non-scalar values, and post and term meta refuse protected keys (`is_protected_meta()`, anything starting with `_`).

User meta is stricter. Plugins keep personal data there under unprotected keys (WooCommerce's `billing_phone`, for one), and `{author_meta}` would print it on public pages. Only `CoreTags::USER_META_ALLOWLIST` (`first_name`, `last_name`, `nickname`, `description`) is allowed by default.

Opt keys in (or out) with one filter for all three object types:

```php
add_filter( 'sd_dynamic_data_meta_key_allowed', function ( $allowed, $key, $object_type ) {
	if ( 'user' === $object_type && 'company' === $key ) {
		return true;
	}
	return 'post' === $object_type && '_price' === $key ? true : $allowed;
}, 10, 3 );
```

The `post_meta_keys`, `user_meta_keys` and `term_meta_keys` option sources apply the same check, so the picker only lists keys that would render.

## REST

Both need `edit_posts`. An option source can require a different capability through `register_source()`.

```
GET  /ayecode/v3/dynamic-data                              groups, tags and modifiers, without callbacks
GET  /ayecode/v3/dynamic-data/options/{source}?post_type=  array of { value, label }
POST /ayecode/v3/dynamic-data/preview { text, post_id }    { html } rendered as the frontend would
```

Preview with a `post_id` needs `edit_post` on that post, since it prints the post's data. Without one, post tags render empty (or their fallback).

## Editor

The picker lives in wp-ayecode-ui (`dynamic-data-picker-react.js`, with `dynamic-data-grammar.js` as the JS parser). Super Duper's block editor wires it up:

- **Text and textarea fields** with `dynamic_data` pass `getInitialTag` to the button. With the caret inside or right after a tag, the picker opens that tag in its configure pane and Insert replaces it. Otherwise Insert goes at the caret or over the selection.
- **RichText blocks** with `block-dynamic-field` get the modal from Super Duper. Do not render a second `DynamicDataModal` in `block-edit-return`. The tag at the caret is passed to the modal, and to edit components as `dynamicDataInitialTag`.
- **Typing `{`** in a RichText block opens the picker (pass `updateCaret` to the RichText's `onKeyUp`). Focus moves to the search box, so the user can keep typing to filter. The chosen tag replaces the brace; closing the picker leaves it.

## Tests

```bash
php tests/dynamic-data/run.php [filter]
```

`tests/fixtures/dynamic-data-grammar.json` holds the grammar cases. The JS parser in the picker must pass the same file, so change it whenever the grammar changes.
