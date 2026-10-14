# Roadmap

Deferred work, with the measurements behind each decision. Numbers were taken on a
live install with 78 registered blocks (BlockStrap + GeoDirectory), PHP 8.4, OPcache on.

Rejected items are recorded with their reasons so they are not re-proposed.

## Which request type each item affects

Most of the large numbers in this document are **editor or admin** costs. Read this table
before quoting any of them at a frontend problem.

| Item | Frontend page | Block editor | admin-ajax |
|---|---|---|---|
| 1. Inline block icons | **no effect** | 428 KB + 3 requests, first load | no effect |
| 2. Slim retained field config | 0.2–0.5 MB | no effect | no effect |
| 3. Defer functions-deprecated.php | ~43 KB + symbols | same | same |
| 4. GutenbergBlock trait off frontend | 37.8 KB per render | no effect | no effect |
| 5. Editor JS hook deps | no effect | unmeasured | no effect |

**Reference frontend baseline** (archive page, Query Monitor): `0.09 s / 11.6 MB / 110 Q`.
Super Duper's share of that is roughly **0.5–1.5 MB, ~7 ms, and zero queries**. The
package is not the bottleneck on a page like that — everything genuinely large that was
fixed in 3.0.10-beta was on the admin and AJAX paths.

---

## 1. Inline block icons via `ayecode_get_icon()` — editor only

**Status:** assessed, not started. Largest single number in the package, but it is an
**editor-only** cost — it does nothing for frontend page speed.

`src/Traits/GutenbergBlock.php:450` renders block icons as an external SVG fragment
reference:

```php
$icon_url = $this->get_url() . "icons/" . $fa_type . ".svg#" . $fa_icon;
// -> el('svg', {...}, el('use', {'xlink:href': '.../icons/solid.svg#bars'}))
```

A browser resolving `<use href="external.svg#id">` downloads the **entire** sprite and
discards the rest. One `#bars` reference costs all of `solid.svg`.

| sprite | raw | gzipped | symbols |
|---|---|---|---|
| solid.svg | 655,050 | 204,912 | 1,002 |
| brands.svg | 470,482 | 190,787 | 457 |
| regular.svg | 109,518 | 31,732 | 152 |
| **total** | **1.23 MB** | **428 KB** | **1,611** |

Across all 78 blocks only **34 distinct icons** are used (29 solid, 4 regular, 1 brands)
— 2.1% of the symbols. A single `fab fa-` icon drags in 470 KB of brands.svg.

This is editor-only (the icon feeds `$config['icon']` in the block registration JSON;
the frontend never touches it) and browser-cached after first load.

### The proposal

`ayecode_get_icon()` in `wp-font-awesome-settings` already supports exactly this:

```php
ayecode_get_icon( 'fas fa-bars', [ 'force_svg' => true ] );
```

- `force_svg` is already a first-class option — no change needed in that package.
- `parse_identifier()` accepts `fas fa-bars` / `far fa-…` / `fab fa-…`, an exact match
  for what `block-icon` already stores. No format translation.
- Measured: **694 bytes/icon average**, 34 icons ≈ **23.6 KB raw** (~6–8 KB gzipped
  inside the page), **0.65 ms for 34 calls** when warm.
- Output uses `fill="currentColor"`, so icons inherit theme colour and work in dark
  mode — the sprite `<use>` approach cannot do this.

Against 428 KB gzipped across 3 requests, that is roughly a 98% cut plus three fewer
HTTP requests, and would eventually allow deleting the 1.3 MB `icons/` folder.

### The risk that dictates the design

`SVG_Loader` resolves in three layers: memory/transient → uploads cache →
**blocking `wp_remote_get` to `cdn.jsdelivr.net`**. There is no queue or deferral, and
**no SVGs are bundled** with that package.

On a cold cache, building block icons means **34 sequential blocking CDN fetches** inside
`enqueue_block_editor_assets`. At 100 ms each that is ~3.4 s on the first editor load;
if jsDelivr is blocked, it is 34 stacked `wp_remote_get` timeouts.

It also trades a self-contained asset that always works for a cache needing **writable
uploads** and **outbound HTTP**. Hardened, air-gapped or read-only installs currently
get icons and would not.

Mitigating: sites already running Font Awesome in SVG mode pay this JIT cost everywhere
else already, so their cache is warm. It is specifically a cold first editor load.

### Implementation notes

1. **Never block on a cold cache.** If `get_inline_icon()` does not return from memory or
   filesystem, fall through to the sprite `<use>` rather than waiting on the CDN. Warm
   the cache out-of-band (activation hook or cron); the inline path switches on once
   icons land.
2. **Fallback chain:** `ayecode_get_icon` → sprite `<use>` → dashicon. Covers both the
   cold cache and installs without `wp-font-awesome-settings` (~1%).
3. Gutenberg's `icon` property takes a **React element, not an SVG string**, and the
   returned markup opens with an `<?xml …?>` prolog that is invalid inline. It must be
   converted to `el('svg', {viewBox}, el('path', {d}))`, handling multi-path and
   `<g>`-wrapped icons, not just the single-path common case.
4. **Cache the converted React string** (not just the SVG), keyed on icon + FA version,
   so the parse happens once rather than per editor load.
5. **Keep `icons/` initially** as the fallback. Deleting it is a separate decision, only
   once the inline path is proven across installs.

**Build it as a progressive enhancement over the sprites, not a replacement.** The moment
it blocks on a CDN fetch during an editor load it is worse than what it replaces.

---

## 2. Frontend memory: slim the retained field config

**Status:** measured, not started. Modest but real; the only frontend memory lever found.

Each rendered block retains its full field config on the cached Registry instance
(`$this->arguments`, set in `OutputHandler::argument_values()`). On the frontend render
path that method reads only **two keys per field**:

```php
$argument_values[ $key ] = isset( $instance[ $key ] ) ? $instance[ $key ] : ( $args['default'] ?? '' );
if ( $args['type'] == 'checkbox' && ! isset( $instance[ $key ] ) ) { ... }
```

Everything else — `title`, `desc`, `group`, `element_require`, and above all the `options`
lists (29-entry colour maps and similar) — exists only to render editor controls.

### Measured at realistic frontend block counts

```
 1 block : +0.43 MB total, of which 0.07 MB is retained config
 3 blocks: +0.25 MB total, of which 0.17 MB is retained config
 5 blocks: +0.29 MB total, of which 0.15 MB is retained config
10 blocks: +1.42 MB total, of which 0.49 MB is retained config
```

So on a typical page the realistic saving is **0.2–0.5 MB**, against a total page peak of
around 11.6 MB — call it **2–4%**. The remaining ~60% of SD's per-block cost is the block
class files being autoloaded and the objects themselves, which slimming does not touch.

A slim `[ key => [ 'default', 'type' ] ]` map costs effectively nothing (below measurement
granularity), and repeat renders of the same block still get their defaults from it, so
there is no rebuild.

### Constraints

- `$this->arguments` is **public** and `get_arguments()` returns it. Slimming the stored
  copy changes what a later `get_arguments()` call sees — the slim map probably wants to be
  a separate property with `$this->arguments` freed, not an in-place replacement.
- Page builders render on the frontend and **do** need the full config —
  `src/BricksElement.php` calls `sd_get_arguments()`. Gate on `Utils::is_preview()` and
  `is_admin()` so only genuine frontend renders slim.
- `WidgetForm.php:70-73` and `GutenbergBlock.php:490` both need the full config; neither is
  a frontend path, but the gating must not catch them.

Verified clean: `$this->options['arguments']` holds only 120 bytes (blocks use
`set_arguments()` now) so there is no second copy, and **no code outside the package reads
`$this->arguments`**.

---

## 3. Deferring `includes/functions-deprecated.php` — blocked, but not impossible

43,384 bytes and 65 `sd_get_*` shims are loaded on **every** request, including frontend
pages that render no Super Duper blocks at all. The shims themselves are only called while
a block builds its arguments, so deferring the `require` to `Registry::get_instance()`
looks obvious.

**It does not work as-is.** The file registers three filters at file scope:

```
:973   add_filter( 'render_block', 'sd_render_block', 9, 3 );
:1014  add_filter( 'sd_block_check_rule', 'sd_block_check_rule', 10, 2 );
:1092  add_filter( 'render_block', 'sd_blocks_render_blocks', 10, 3 );
```

Those fire on the frontend for every block on the page and drive block visibility
conditions. Lazy-loading the file would silently disable them.

Viable path: move those three registrations into `Loader::__construct()` pointing at
`Utils` statics (the same treatment the generator tag and AJAX handlers got in 3.0.10),
*then* the file can be required lazily.

Note that `sd_blocks_render_blocks()` is a **live implementation** living in the
deprecated file, not a shim — it is the frontend render path for every Super Duper block.
`Utils::build_shortcode()` was migrated but this was left behind. It should move to
`Utils::blocks_render_blocks()` as part of the same piece of work, not on its own: doing
one of the three filters in isolation buys nothing, since all three must move before the
file can be deferred.

---

## 4. Editor-only trait code loaded on frontend renders

`src/Traits/GutenbergBlock.php` is **37,806 bytes** of block-editor JS generation, and it
is composed into `SuperDuper` — so it loads on any frontend request that renders a block.
Traits are resolved at class-composition time, so this cannot be conditionally skipped.

Avoiding it means converting the trait into a lazily-instantiated collaborator object that
is only constructed in editor contexts. That is an architectural change to the base class
and would alter the public method surface, so it needs its own design pass.

---

## 5. Editor JS: `JSON.stringify` in React hook dependencies

**Status:** identified, impact unverified.

`assets/js/super-duper-block-editor.js`:

- `:1383` — `}, [JSON.stringify(watchedAttributes)]);`
- `:1621` — `}, [deviceType, activeTab, JSON.stringify(attributes), groupedArgs]);`
- `:1441` — a `filter` + `map` + `stringify` chain

`attributes` carries roughly one key per declared argument (~100). These run on every
render of the component they sit in.

**Do not act on this without browser profiling first.** The claim that it affects every SD
block on the page is unverified — Gutenberg may only re-render the selected block's
inspector, which would make the real cost far smaller. Measure in DevTools before
changing anything.

---

## 6. Standards and tooling debt

- **phpcs has never actually been run.** `vendor/` is absent, so `phpcs.xml.dist` is
  unverified. Add `docs/`, `hello-world.php`, `map.php`, `sd-plugin.php` exclusions to the
  ruleset before generating a baseline, or it will be unusable.
- **`src/SuperDuper.php:33-53` and `src/SuperDuperWidget.php:34-54`** — 12 untyped public
  properties each. Deliberately left untyped: 292 consumer classes extend these, and
  adding scalar types risks `TypeError` across code that cannot be audited. The real PHP
  8.2+/9 risk is *undeclared* dynamic properties, and these are all declared, so the
  classes are already safe in the way that matters. Revisit only with a consumer audit.
- **`src/Utils.php:1159`** — `__( $field['admin_title'], 'geodirectory' )`. A variable
  passed to `__()` (unextractable, hard phpcs error) *and* GeoDirectory-specific code
  living in Super Duper (`geodir_badge_field_skip_key`, `geodir_post_meta_address_fields`).
  The text domain is deliberately left as `geodirectory` — changing it to
  `ayecode-connect` would break the translation rather than fix it. The real fix is moving
  this logic out of the package.

---

## Rejected — do not re-propose without new information

### Transient-caching the per-block editor config

Consumer blocks put **DB-derived options directly into field definitions** —
`sd_template_page_options()` in BlockStrap and ayecode-connect,
`geodir_template_page_options()` in GeoDirectory. Caching the per-block config means
adding or renaming a page stops appearing in those dropdowns until expiry. Invalidation is
not tractable: those are other packages' functions, and any consumer can introduce a new
dynamic source. The build cost it would save is only ~39 ms.

### Moving the editor payload to a cacheable asset

The payload gzips **13:1** — 992,989 bytes raw becomes **75,387 gzipped**, because it is
highly repetitive JSON. Moving it to a separately-cached asset saves at most ~75 KB on
repeat editor loads, in exchange for an extra blocking round trip, risk to **block
registration timing** (Gutenberg needs blocks registered before the editor mounts, which
inline-after-the-handle currently guarantees), and the same staleness trap as above if the
URL is version-hashed. Bad trade.

### Shipping a minified block editor JS

Deleted in 3.0.10-beta. A fresh terser build gzips to 10,109 bytes against the source's
17,920 — a **7.8 KB saving on an admin-only screen**. The maintenance cost is proven, not
hypothetical: CLAUDE.md already instructed "update both when editing JS" and the file still
drifted six weeks and shipped a build missing the entire dependent-fields subsystem
(`sdDependentFieldCache`: 15 occurrences in source, 0 in the shipped min). An mtime guard
cannot help — git checkouts normalise mtimes. Doing it safely needs a content-hash sidecar
generated at release: real machinery for 7.8 KB.

### Replacing the per-render `serialize()` in the instance hash

`Utilities.php:143-146` serialises the resolved instance array on every render to derive
the `sdel-` CSS class. It looks like an obvious per-render cost. Measured:
**0.0001 ms/call — 0.00 ms across 10 blocks.** There is nothing to win, and the hash must
stay stable for identical args because style dedupe depends on it. Leave it alone.

### Removing the `sd_get_*` deprecation shims

`includes/functions-deprecated.php` is load-bearing. Consumer plugins call these **5,220
times** across 12+ plugins (`sd_get_margin_input` alone: 1,191). The ~60 commented-out
`_deprecated_function()` calls must **stay commented out** — enabling them would emit tens
of thousands of notices per request under `WP_DEBUG`.

---

## Deliberate design notes

- **`$eager` gating** (`src/Registry.php`, `request_needs_all_blocks()`): editor screens
  stay eager because `enqueue_block_editor_assets`, `media_buttons` and the Fusion/Bricks
  `init` hooks are all registered from block constructors. Unknown and plugin admin pages
  (`admin.php`) default to eager so anything unanticipated fails safe. Narrowing further
  requires moving those five hooks to `Loader` first.
- **`Utils::pagenow_exclude()`** is the list of admin screens that skip instantiation. It
  predates the registry and is filterable via `sd_pagenow_exclude`.
- **`State` is a class, not trait statics.** A `static` declared inside a trait is
  per-using-class, so `SuperDuper` and `SuperDuperWidget` would each get their own copy and
  every "only once" guard would fire twice.
