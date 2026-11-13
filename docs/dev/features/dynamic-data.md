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
| `@round:N` | `{post_count @round:100}` | `number` tags. Rounds down to the nearest N, so 12345 gives 12300. |
| `@thousands` | `{post_count @thousands}` | `number` tags. Adds the site's thousands separator: 12,345. Off by default, so a number can feed a counter or a calculation. |
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
{post_count:page @round:100 @thousands}+
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
| `link` | callable | `fn( array $args, Context $c, array $def ): string`. Enables `@link` and `@new_tab`. |
| `modifiers` | string[] | Optional. Narrows the modifiers that would otherwise apply. |
| `cache` | int | Optional. Seconds to keep the value. See [Caching](#caching). |
| `vary` | string[] or callable | Optional. What a cached value depends on besides its args. |
| `callback` | callable | `fn( array $args, Context $c, array $def )`. Return null or `''` for empty. Non-scalar values render as empty. `$def` is the normalized definition with its `name`, so one static method can serve several tags; a callback that takes two parameters works unchanged. Prefer `[ self::class, 'method' ]` over a closure: the registry loads on every page that renders a tag, and each closure costs about 1 KB of request memory. |

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

## Counts

A count tag goes in the group of the thing it counts, not in a group of its own: a site total in Site, an author's posts in Post Author, a listing count in the addon's own group. End the label in "Count" so a search for "count" in the picker lists them together.

| Tag | Group | Cost |
|---|---|---|
| `{post_count:post_type}` | Site | free, WordPress keeps the total |
| `{term_count:taxonomy @hide_empty}` | Site | one query, kept 10 minutes |
| `{user_count}` | Site | free |
| `{user_count:role}` | Site | reads every user, kept an hour |
| `{site_comment_count}` | Site | free |
| `{author_post_count @post_type}` | Post Author | one query per author per request |
| `{user_post_count @post_type}` | Current User | one query per request |
| `{comment_count}`, `{term_post_count}`, `{query_results_count}` | Post, Archive & Term | free |

Post type counts only print for post types visitors can view, and taxonomy counts for viewable taxonomies.

## Caching

A tag whose value needs a query sets `cache`, and the renderer does the rest. No tag keeps its own cache.

| `cache` | Behavior |
|---|---|
| not set | The callback runs every time the tag renders. Right for anything read from an object already in memory. |
| `0` | Runs once per request for the same args, however often the tag appears. |
| seconds | As `0`, and the value is kept that long between requests: in the object cache where the site has one, otherwise in a transient. |

The stored key is the tag name plus its resolved args, so `{user_count:author}` and `{user_count:editor}` are kept apart. A value that also depends on the page needs `vary`:

- `[ 'post' ]`, `[ 'term' ]`, `[ 'user' ]` or a mix: the current post, queried term or logged in visitor.
- a callable `fn( array $args, Context $c ): string` for anything else, such as the post author or a current location.

Only give a stored tag a `vary` with few possible values. `[ 'post' ]` with a `cache` of seconds writes one entry per post, which on a large site without an object cache is one transient per post. Use `0` there.

```php
Registry::register( 'listing_count', [
	'label'    => __( 'Listing Count', 'ayecode-connect' ),
	'group'    => 'listing',
	'returns'  => 'number',
	'cache'    => 10 * MINUTE_IN_SECONDS,
	'vary'     => [ My_Tags::class, 'vary_location' ],
	'callback' => [ My_Tags::class, 'listing_count' ],
] );
```

Count with a direct `COUNT(*)` or a stored counter, never a `WP_Query` that loads posts to count them.

Values are not invalidated when data changes; they expire. Where a stale number matters, drop it when you know it changed:

```php
Renderer::forget( 'listing_count', [ 'post_type' => 'gd_place' ] );
```

`forget()` builds the key for the current request's context, so a tag with `vary` has to be forgotten from a request with the same post, term or user, or simply left to expire. The `sd_dynamic_data_cache_ttl` filter changes the seconds per tag; return 0 to keep values for the request only. An editor preview reads stored values but never writes them.

## Number fields

```php
->add_field( 'num_end', [
	'type'         => 'number',
	'title'        => __( 'End number', 'ayecode-connect' ),
	'default'      => '123',
	'dynamic_data' => true,
] )
```

`render_dynamic_fields()` hands the block a numeric string for such a field, whatever was saved. A tag is resolved and, when the result is not numeric (an empty count, a tag typed by hand in the code editor, `@thousands` output), the field's `default` is used instead. Unlike text fields, the tag is resolved in an editor preview too, since a block can do nothing with the tag itself. Format the number in the block, not in the tag.

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

While a preview renders, `Utils::is_preview()` returns true, the same as during a block's editor preview. A tag that needs an object the editor does not have (a listing, on a template) can check it and fall back to a demo one, the way blocks do.

## Editor

The picker lives in wp-ayecode-ui (`dynamic-data-picker-react.js`, with `dynamic-data-grammar.js` as the JS parser). Super Duper's block editor wires it up:

- **Text and textarea fields** with `dynamic_data` pass `getInitialTag` to the button. With the caret inside or right after a tag, the picker opens that tag in its configure pane and Insert replaces it. Otherwise Insert goes at the caret or over the selection.
- **Number fields** with `dynamic_data` hold a number or one tag, never free text. The input stays a number input; picking a tag swaps it for the tag, which opens the picker again when clicked and has a button to go back to the number. The picker only lists `number` tags.
- **Limiting the picker:** `'dynamic_data' => 'url'`, or an array of kinds, lists only tags whose `returns` matches. `DynamicDataButton` and `DynamicDataModal` take the same list as a `returns` prop.
- **RichText blocks** with `block-dynamic-field` get the modal from Super Duper. Do not render a second `DynamicDataModal` in `block-edit-return`. The tag at the caret is passed to the modal, and to edit components as `dynamicDataInitialTag`.
- **Typing `{`** in a RichText block opens the picker (pass `updateCaret` to the RichText's `onKeyUp`). Focus moves to the search box, so the user can keep typing to filter. The chosen tag replaces the brace; closing the picker leaves it.

## Tests

```bash
php tests/dynamic-data/run.php [filter]
```

`tests/fixtures/dynamic-data-grammar.json` holds the grammar cases. The JS parser in the picker must pass the same file, so change it whenever the grammar changes.
