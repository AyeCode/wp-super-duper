# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

WP Super Duper is a PHP framework/library (composer package `ayecode/wp-super-duper`) that lets a developer define a single class and automatically generate a **WordPress Widget**, a **Shortcode**, and a **Gutenberg Block** — all sharing one arguments configuration.

Current version is defined in `wp-super-duper.php` (plugin header) and `package-loader.php` (`$this_version`). When bumping the version, update both those files plus `composer.json` — and `CHANGELOG.md` first.

## Linting / Code Standards

Run PHP_CodeSniffer using the project ruleset:

```bash
# From repo root
vendor/bin/phpcs --standard=phpcs.xml.dist
vendor/bin/phpcbf --standard=phpcs.xml.dist   # auto-fix
```

Standards enforced: `WordPress-Extra`, PHP ≥ 7.4, WordPress ≥ 5.0, text domain `ayecode-connect`.

There are no automated tests or a build step for PHP. The JS at `assets/js/super-duper-block-editor.js` has a pre-minified companion (`*.min.js`) — update both when editing JS.

## Architecture

### Entry Point & Loading

`wp-super-duper.php` is the WordPress plugin header file. It simply requires `package-loader.php`.

`package-loader.php` is the **AyeCode Double Negotiation loader** — the real bootstrap. It has four phases:

**Step 0 — Early Claim (direct-load time, before `plugins_loaded`)**
Defines `SUPER_DUPER_VER` and `SUPER_DUPER_PLUGIN_URL`, registers the PSR-4 SPL autoloader (`AyeCode\SuperDuper\` → `src/`), requires the global function files (`includes/functions.php`, `includes/helpers.php`) and all legacy trait files (`includes/traits/`), then sets `class_alias('AyeCode\SuperDuper\SuperDuper', 'WP_Super_Duper')`. This early-claim prevents older bundled copies (loaded alphabetically before this file) from winning the class-definition race.

**Step 1 — Version Registration (priority 1)**
Registers this copy's version in `$GLOBALS['ayecode_super_duper_registry']`. The copy with the highest version wins subsequent steps.

**Step 2 — Class Loading (priority 2)**
Only the winning copy runs this. Re-registers the SPL autoloader and reloads function files if needed.

**Step 3 — Loader Instantiation (priority 10)**
Only the winning copy runs this. Instantiates `AyeCode\SuperDuper\Loader`, which registers all WordPress hooks.

`includes/loader.php` — legacy loader, still present for backward compatibility with any composer dependency copies that do not use `package-loader.php`. Not called by the standalone plugin.

### PSR-4 Namespace (`src/`)

All modern classes live under `AyeCode\SuperDuper\` (mapped to `src/` by the SPL autoloader):

| Class | File | Role |
|---|---|---|
| `SuperDuper` | `src/SuperDuper.php` | Base class (global alias: `WP_Super_Duper`) |
| `SuperDuperWidget` | `src/SuperDuperWidget.php` | Widget-mode class (extends `WP_Widget`) |
| `BricksElement` | `src/BricksElement.php` | Bricks Builder adapter |
| `Loader` | `src/Loader.php` | Hook-registration entry point (constructor only) |
| `Utils` | `src/Utils.php` | Static utilities: `is_preview()`, `string_to_bool()`, `encode_shortcodes()`, `decode_shortcodes()`, `replace_variables()` |

**Builder:**

| Class | File | Role |
|---|---|---|
| `Builder\BlockArguments` | `src/Builder/BlockArguments.php` | Fluent field-definition builder (see `docs/builder-pattern.md`) |

**Fields — static factory classes** (replace deprecated `sd_get_*` global functions):

| Class | File | Methods |
|---|---|---|
| `Fields\SpacingFields` | `src/Fields/SpacingFields.php` | `margin_input()`, `padding_input()` |
| `Fields\StyleFields` | `src/Fields/StyleFields.php` | `border_input()`, `shadow_input()`, `background_inputs()`, `background_input()`, `display_input()`, `opacity_input()`, `hover_animations_input()`, `hover_icon_animation_input()`, `zindex_input()`, `overflow_input()`, `scrollbars_input()` |
| `Fields\TypographyFields` | `src/Fields/TypographyFields.php` | `font_size_input_group()`, `font_size_input()`, `font_custom_size_input()`, `font_weight_input()`, `font_case_input()`, `font_italic_input()`, `font_line_height_input()`, `text_justify_input()`, `text_align_input()`, `text_align_input_group()`, `text_color_input_group()`, `text_color_input()`, `custom_color_input()` |
| `Fields\LayoutFields` | `src/Fields/LayoutFields.php` | `container_class_input()`, `position_class_input()`, `sticky_offset_input()`, `col_input()`, `row_cols_input()`, `absolute_position_input()`, `width_input()`, `height_input()`, `max_height_input()` |
| `Fields\CommonFields` | `src/Fields/CommonFields.php` | `class_input()`, `anchor_input()`, `custom_name_input()`, `visibility_conditions_input()`, `new_window_input()`, `nofollow_input()`, `attributes_input()`, `title_tag_input()`, `html_tag_input()`, `title_inputs()` |
| `Fields\ColorFields` | `src/Fields/ColorFields.php` | `aui_colors()`, `get_aui_colors()`, `branding_colors()` |
| `Fields\ShapeFields` | `src/Fields/ShapeFields.php` | `divider_inputs()`, `element_require_string()` |
| `Fields\FlexFields` | `src/Fields/FlexFields.php` | `align_items_input()`, `align_items_group()`, `justify_content_input()`, `justify_content_group()`, `align_self_input()`, `align_self_group()`, `order_input()`, `order_group()`, `wrap_input()`, `wrap_group()`, `float_input()`, `float_group()` |

### Trait Breakdown

All traits exist in two locations: the canonical `src/Traits/` (PSR-4 autoloaded) and legacy copies in `includes/traits/` (required directly by `package-loader.php` Step 0 for backward compat). The two are kept in sync.

| Trait | Responsibility |
|---|---|
| `Initializer` | `initialize_super_duper()` — sets properties, filters options, registers hooks; calls `set_arguments()` if defined |
| `GutenbergBlock` | Gutenberg block registration, JS enqueue (`super-duper-block-editor.js`), inline block config data |
| `OutputHandler` | Shortcode registration and AJAX render handler (`wp_ajax_super_duper_output_shortcode`) for block previews |
| `WidgetForm` | Widget admin form rendering |
| `PageBuilders` | Compatibility with Elementor, Divi, Beaver Builder, Bricks, Avada Fusion, Cornerstone, SiteOrigin, Oxygen |
| `ShortcodeInserter` | Shortcode inserter UI in the Classic Editor |
| `Utilities` | Shared utility methods |

### Helper Files (`includes/helpers/`)

- `gutenberg-block-helpers.php` — PHP-generated inline JS for block configuration
- `gutenberg-editor-styles.php` — Inline CSS for the block editor
- `visibility-conditions-js.php` — Inline JS for block visibility conditions

`includes/helpers.php` (top-level) provides standalone functions like `sd_is_preview()` that detect page-builder preview contexts.

`includes/functions.php` — global field-helper functions (`sd_get_*`). All are soft-deprecated since 3.1.0; implementations now live in the `Fields\*` static classes.

### JavaScript (`assets/js/super-duper-block-editor.js`)

Single universal block editor script. Key internals:
- `window.sdDependentFieldCache` — global REST response cache shared across block instances
- `resolvePath()`, `mapRestResponse()`, `fetchDependentOptions()` — dependent field utilities

### Page Builder / Bricks Integration

- `src/BricksElement.php` — Bricks Builder element adapter (legacy copy: `includes/class-super-duper-bricks-element.php`)
- Fusion Builder (Avada) and SiteOrigin adapters live inside the `PageBuilders` trait

## How Consuming Code Uses This

**Classic approach** (all versions):
1. Extend `WP_Super_Duper` and call `parent::__construct($options)` from `__construct()`.
2. Pass field definitions in the `'arguments'` key of `$options`.
3. Define `output($args, $widget_args, $content)` — return HTML string.
4. Instantiate the class (no `register_widget()` needed in v2+).

**Modern approach (v3+):**
Override `set_arguments(): array` and return a `BlockArguments::get()` array. This eliminates the raw `arguments` array in `$options`. The `Initializer` trait calls `set_arguments()` and merges its result with any `arguments` already set.

```php
public function set_arguments(): array {
    return ( new \AyeCode\SuperDuper\Builder\BlockArguments() )
        ->add_field( 'title', [ 'type' => 'text', 'title' => __( 'Title', 'ayecode-connect' ) ] )
        ->add_margins()
        ->add_visibility_conditions()
        ->get();
}
```

See `hello-world.php` for the canonical working example and `docs/builder-pattern.md` for the full `BlockArguments` API.

### Key `$options` keys

`textdomain`, `base_id` (unique slug), `class_name` (`__CLASS__`), `name`, `block-icon`, `block-category`, `block-keywords`, `block-output`, `block-wrap`, `widget_ops`, `arguments`, `no_wrap`, `output_types`, `nested-block`.

Field types in `arguments`: `text`, `select`, `checkbox`, `number`, `textarea`, and more. Fields support `depends_on` for dynamic Gutenberg dropdowns populated via REST API (see `docs/features/dependent-fields.md`).

## AyeCode Standards (from `.aiassistant/rules/ayecode-standards.md`)

These rules apply to all work in this codebase:

- **PHP ≥ 7.4**: use typed properties, arrow functions, modern syntax.
- **Method names**: strict `snake_case`. Class names: `PascalCase`.
- **No `die()`/`exit()`** in handlers — use `wp_die()`.
- **All SQL** through `$wpdb->prepare()`.
- **All assets** via `wp_enqueue_script`/`wp_enqueue_style` — no hardcoded tags.
- **Inputs**: always `wp_unslash()` before sanitizing (e.g., `sanitize_text_field( wp_unslash( $_POST['field'] ) )`).
- **AJAX/forms**: verify nonce + `current_user_can()` before processing.
- **i18n text domain**: `ayecode-connect` everywhere.
- **Icons in public/frontend output**: use `ayecode_get_icon('fa-...')` or `ayecode_icon('fa-...')` — never raw `<i>` tags. Raw `<i>` tags are fine in wp-admin output.
- **Version numbers**: do NOT increment during development. Only bump on a finalized release (bump `CHANGELOG.md` first, then PHP/`composer.json`).
- **No global PHP functions** — all new code in the package namespace or as methods/traits.
- **Bootstrap 5.3 utility classes** for UI; custom CSS only as a last resort.
- **No `languages/` directory** — translations are handled centrally via `ayecode-connect`.
