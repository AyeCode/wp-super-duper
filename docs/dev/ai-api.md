# AI Schema API

WP Super Duper exposes a REST surface designed for AI page generation. It describes every
registered block in a form an LLM can build with directly: the block's real Gutenberg name, the
attributes worth authoring, their allowed values, and their nesting rules — small enough to fit
in a prompt.

It is built for the **native `createBlock` flow**: a model produces a tree of
`{ name, attributes, innerBlocks }`, and the editor inserts it with `createBlock()`, which writes
the markup itself. So this API speaks the native contract — attribute keys and values are exactly
what the registered block accepts, and can be passed straight to `createBlock` with no
translation.

There are two ways to generate with this API. The **schema-first** path (`/catalog` + `/schemas`)
hands the model our native attribute contract and it authors block JSON directly — used by the
single-block edit "wand". The **HTML-first** path (`/vocabulary` + `/convert`) lets the model
design in the Bootstrap 5.3 HTML it was trained on, and the server converts that HTML to a native
block tree — used for full-page/section generation, because models produce far richer output in
their native language than in an attribute dialect they have never seen.

| Endpoint | Purpose |
|---|---|
| [`GET /ai/catalog`](#catalog) | Every block: real name, nesting, description, claimed HTML, and its **content-tier** attributes. The planning + generation payload. |
| [`GET /ai/schemas`](#schemas) | The **full** attribute schema (content + style) for chosen blocks. Used for single-block editing. |
| [`GET /ai/vocabulary`](#vocabulary) | The authoring rules + custom-element list a model needs to emit convertible HTML. |
| [`POST /ai/convert`](#convert) | Convert a Bootstrap 5.3 HTML fragment into a native block tree, with a loss report. |
| [`POST /ai/render`](#render) | Dev-gated: render a native tree to front-end HTML for the fidelity harness. |

> **`/validate` is still absent.** `createBlock` cannot produce an invalid block (unknown names
> and undeclared attributes are dropped, values coerced), so no server-side validator is needed
> for correctness. A `/validate` quality gate — for valid-but-wrong trees, e.g. a `col` outside a
> `row` — may return later. Note the converter already handles that specific case by synthesizing
> the missing wrapper.

**Namespace:** `ayecode/v3`
**Base path:** `/wp-json/ayecode/v3/ai`

The internal [`/blocks/*` endpoints](rest-api.md) are unchanged and remain the raw source of
truth; this API is the AI-facing projection of them.

---

## Quick start

Every route needs the `edit_pages` capability, so authenticate. For a dev, an
[Application Password](https://make.wordpress.org/core/2020/11/05/application-passwords-integration-in-wordpress-5-6/)
(Users → Profile → Application Passwords) with HTTP Basic Auth is simplest:

```bash
CREDS='admin:XXXX XXXX XXXX XXXX XXXX XXXX'

# Every block, with content-tier attributes (jump to §Catalog)
curl -s -u "$CREDS" "http://localhost/wp-json/ayecode/v3/ai/catalog"

# Full schema for specific blocks — blocks is REQUIRED (jump to §Schemas)
curl -s -u "$CREDS" "http://localhost/wp-json/ayecode/v3/ai/schemas?blocks=bs_container,bs_heading"
```

From the block editor, use `apiFetch` — it supplies the REST nonce automatically:

```js
const catalog = await wp.apiFetch( { path: '/ayecode/v3/ai/catalog' } );
```

Three responses that commonly confuse:

- **There is no `/ai/` index route.** Hitting the base path returns `404 rest_no_route`. Use a
  concrete route such as `/ai/catalog`.
- **`/ai/schemas` with no `blocks` returns `400`** (`rest_missing_callback_param`). There is
  deliberately no "all schemas at once" response — call `/ai/catalog` to discover ids, then
  request the ones you need.
- **A plain browser GET returns `401`.** REST cookie auth needs a nonce; a logged-in tab without
  one is anonymous. Use Basic Auth or `apiFetch`.

---

## Authentication

Both endpoints require the `edit_pages` capability. Change it with the
`ayecode_sd_rest_ai_capability` filter:

```php
add_filter( 'ayecode_sd_rest_ai_capability', function () {
    return 'manage_options';
} );
```

Application Passwords are accepted, so external tools work without a browser session.

---

## Conventions

### Native, not compressed

Attribute **keys and values are the block's own**. A responsive control is three separate
attributes (`mt`, `mt_md`, `mt_lg`), and a value is the literal class the block stores
(`text_align_lg: "text-lg-start"`). Pass them to `createBlock` as-is — the editor drops anything
the block does not declare, which is the safety net that lets the model be constrained without a
server round-trip.

### Delta rule

Set only the attributes that differ from a block's default; omit the rest and the block falls
back to its own default. Every attribute carries its `default` where one exists.

### Tiers

Every attribute is tagged `tier: "content"` or `tier: "style"`:

- **content** — authoring-critical: the layout choice, the text, the link, the image, the
  semantic tag. `/catalog` ships these.
- **style** — appearance: margins, padding, colours, typography, borders, and responsive
  breakpoint variants. Excluded from `/catalog`; included in `/schemas` for the edit path.

### The tree node format (consumer side)

The consumer builds a tree of native nodes and inserts them with `createBlock`:

```json
{
  "name": "blockstrap/blockstrap-widget-container",
  "attributes": { "container": "row" },
  "innerBlocks": [ /* child nodes */ ]
}
```

`name` is the `block_name` from the catalog; `attributes` are native keys/values; `innerBlocks`
is only meaningful where `inner_blocks` is not `"none"`.

---

## Attribute schema shapes

Every attribute in a response is one of these. All are taken verbatim from live output.

```json
"container": { "json_type": "string", "enum": ["container", "row", "col", "card", "..."],
               "enum_labels": { "container": "container (default)", "row": "row" },
               "tier": "content", "default": "container", "label": "Type",
               "group": "container" }

"h100":      { "json_type": "string", "enum": ["", "h-100"],
               "enum_labels": { "": "No", "h-100": "Yes" },
               "tier": "content", "label": "Card equal heights",
               "requires": { "container": "card" } }

"bg_color":  { "json_type": "string", "format": "hex", "tier": "style",
               "label": "Custom color", "requires": { "bg": "custom-color" } }

"css_class": { "json_type": "string", "tier": "style", "label": "Additional CSS class(es)",
               "note": "extra Bootstrap utility classes; use sparingly" }

"img_url":   { "json_type": "string", "tier": "content", "label": "Image URL",
               "hint": "https://example.com/uploads/my-image.jpg",
               "requires": { "img_src": "url" } }
```

| Field | Meaning |
|---|---|
| `json_type` | `string`, `number`, `integer`, or `array`. |
| `enum` | Allowed native values. `""` means "unset / default". |
| `enum_labels` | Human label per enum value — the meaning behind a class name (`fw-bold` → "bold"). |
| `tier` | `content` or `style` (see above). |
| `default` | Value assumed when the attribute is omitted. Present only when non-empty. |
| `label` | Short human name (the field title). |
| `group` | **`/schemas` only.** The form section the field lives in (`typography`, `icon-style`, `wrapper-styles`, …). Disambiguates labels that repeat across sections — a block can carry three "Font size" fields (`icon-style` / `title-style` / `description-style`); the `group` is what tells them apart. Omitted from `/catalog`. |
| `format` | `hex` (colour) or `css-gradient`. |
| `note` | A one-line semantic hint, where it adds meaning. |
| `hint` | An example of the expected format (e.g. a URL). |
| `requires` | The attribute only applies when the condition holds — `{ "attr": "value" }`, or `{ "attr": "*" }` for "any truthy value". |
| `requires_any` | Array of conditions; the attribute applies when **any** hold. |

**Checkboxes are strings, never booleans** — `enum: ["", "1"]` (or the block's on-value). A
boolean would be coerced to `true`/`false`, which the block does not accept.

`requires` / `requires_any` are derived from the block's own conditional-field logic and included
only when that logic translates cleanly. They are advisory for the model; a missing `requires`
does not mean an attribute is unconditional.

---

## Endpoints

### Catalog

```
GET /wp-json/ayecode/v3/ai/catalog
```

Every registered block across all active products, each with its content-tier attributes. Small
enough to hand to a model whole (the full BlockStrap library is ~24 KB).

**Query parameters**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `context` | string | No | Filter to a single context: `page`, `site`, `archive`, or `single`. |

**Example response** (one block shown; attribute enums trimmed)

```json
{
  "version": "3.0.9-beta",
  "schema_hash": "0f8948fd6af8cd205e5062b9d3a495ff",
  "meta": {
    "attributes": "Attribute keys and values are native — pass them straight to createBlock. Responsive variants are separate attributes (mt, mt_md, mt_lg). Checkboxes are strings (\"\" / \"1\"), never booleans.",
    "delta_rule": "Only set attributes that differ from their default; omit the rest.",
    "framework": "bootstrap-5.3"
  },
  "blocks": [
    {
      "id": "bs_container",
      "block_name": "blockstrap/blockstrap-widget-container",
      "title": "Container",
      "context": "page",
      "inner_blocks": "any",
      "description": "Universal layout block. Set the `container` attribute: page section wrappers (container/container-fluid), grid (row/col), cards, list-groups. col must be inside row.",
      "attributes": {
        "container": { "json_type": "string", "enum": ["container", "row", "col", "card", "..."], "tier": "content", "default": "container", "label": "Type" },
        "col":       { "json_type": "string", "enum": ["", "auto", "1", "..."], "tier": "content", "requires": { "container": "col" } },
        "html_tag":  { "json_type": "string", "enum": ["", "header", "main", "section", "..."], "tier": "content", "label": "HTML Element" }
      }
    }
  ],
  "patterns": []
}
```

**Block entry**

| Field | Type | Description |
|---|---|---|
| `id` | string | The block's `base_id`. Used to request its full schema from `/ai/schemas`. |
| `block_name` | string | The real Gutenberg name — pass this to `createBlock`. |
| `title` | string | Human name, with any `"BS > "`-style prefix stripped. |
| `context` | string | `page` (default), `site`, `archive`, or `single`. |
| `inner_blocks` | string \| array | `"none"`, `"any"`, or an allowlist of child `block_name`s. |
| `description` | string | A hint written for the model. Absent when the block declares none. |
| `attributes` | object | Content-tier attributes only, keyed by native name. |

`patterns` is reserved for a later phase and is currently an empty array.

---

### Schemas

```
GET /wp-json/ayecode/v3/ai/schemas?blocks=bs_container,bs_heading
```

The **full** native schema (content + style tiers) for the requested blocks — everything a
single-block editor needs. Blocks are keyed by `base_id`.

> **`blocks` is required.** There is no "all schemas" response. Without it you get
> `400 rest_missing_callback_param`. Discover ids with [`/ai/catalog`](#catalog) first, and build
> a `block_name` → `id` map from it if you only have native names.

**Query parameters**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `blocks` | string | **Yes** | Comma-separated block ids, e.g. `bs_container,bs_heading`. |
| `raw_compare` | boolean | No | When `1`, adds a `debug` object comparing raw vs transformed sizes. |

**Example response** (attributes trimmed)

```json
{
  "blocks": {
    "bs_container": {
      "block_name": "blockstrap/blockstrap-widget-container",
      "title": "Container",
      "inner_blocks": "any",
      "description": "Universal layout block. …",
      "attributes": {
        "container": { "json_type": "string", "enum": ["container", "row", "col", "..."], "tier": "content", "default": "container", "label": "Type" },
        "mt":        { "json_type": "string", "enum": ["", "auto", "0", "1", "...", "n12"], "tier": "style", "label": "Margin top" },
        "bg_color":  { "json_type": "string", "format": "hex", "tier": "style", "label": "Custom color", "requires": { "bg": "custom-color" } },
        "css_class": { "json_type": "string", "tier": "style", "label": "Additional CSS class(es)", "note": "extra Bootstrap utility classes; use sparingly" }
      },
      "nesting": {
        "attr": "container",
        "rules": {
          "col":             { "parent_attr": { "container": "row" } },
          "card-body":       { "parent_attr": { "container": "card" } },
          "list-group-item": { "parent_attr": { "container": "list-group*" } }
        }
      }
    }
  },
  "examples": []
}
```

**Per-block entry**

| Field | Type | Description |
|---|---|---|
| `block_name` | string | The real Gutenberg name. |
| `title` | string | Human name. |
| `inner_blocks` | string \| array | `"none"`, `"any"`, or an allowlist of child `block_name`s. |
| `attributes` | object | **All** authorable attributes (content + style), keyed by native name. |
| `description` | string | Model-facing hint, when declared. |
| `nesting` | object | Attribute-conditional placement rules, when the block declares any (below). |

`examples` is reserved for gold-standard trees and is currently empty.

**Nesting rules.** `nesting.attr` names the attribute the rules key on; `nesting.rules` maps each
value of that attribute to a placement constraint. `parent_attr` requires the parent block to
carry a specific attribute value; a trailing `*` (e.g. `list-group*`) is a prefix match. Simple
parent/child block constraints are already expressed by each block's `inner_blocks` allowlist and
are not repeated here.

**Unknown blocks** return `400` with a machine-readable list:

```json
{
  "code": "rest_unknown_blocks",
  "message": "Unknown block ids: bs_nope.",
  "data": { "status": 400, "unknown": ["bs_nope"] }
}
```

---

## Images (v1)

The AI places images through `bs_image` using the URL path only:

- `img_src: "url"` — the source mode.
- `img_url` — a resolvable image URL. Because a model cannot invent a real photo URL, use a
  placeholder service (e.g. `https://picsum.photos/seed/<word>/1200/800`) and let the user swap
  it. `img_url` carries `requires: { "img_src": "url" }`, so it only applies in URL mode.
- `img_alt` — descriptive alt text the model writes.

The `upload` and `featured` modes need a media ID or post context the AI does not have, so v1
does not use them.

---

## How a block enriches its AI representation

All optional. A block declares these in its constructor `$options`; nothing here is keyed by a
hardcoded slug, so any product's blocks work the same way.

| Option | Effect |
|---|---|
| `ai-hint` | The block-level `description` — a one-line "what/when/instead-of". |
| `ai-context` | `page` (default), `site`, `archive`, or `single`. |
| `ai-nesting` | Attribute-conditional placement rules (the `nesting.attr` + `rules` shape). |
| `ai-html` | Native HTML elements this block claims for conversion — a list of `tag` or `tag.class` selectors (e.g. `['h1','h2']`, `['a.btn']`). See [Convert](#convert). |
| `ai-html-fallback` | Safety-net claim selectors that map an element to this block **without** making it a native block: it still appears as a custom element in the vocabulary (`<form>` → `bs_contact`, which the model should author as `<bs-contact>`). |

Filters `ayecode_sd_ai_hints`, `ayecode_sd_ai_nesting`, `ayecode_sd_ai_examples`, and
`ayecode_sd_ai_html` let a product supply the same data centrally. A block that declares nothing
still appears in the catalog with a usable schema.

---

<a id="vocabulary"></a>
## `GET /ai/vocabulary`

The prompt-side companion to the converter: everything a model must be told to produce
convertible HTML, generated from the live registry so it tracks active products.

```json
{
  "schema_hash": "…",
  "rules": [
    "Output Bootstrap 5.3 HTML only. No <script>, no <style>, no inline style attributes.",
    "Use semantic color tokens (bg-body, bg-body-tertiary, *-subtle, *-emphasis, text-body-secondary). Never bg-white, text-dark, or hex colors — they break dark mode.",
    "…"
  ],
  "elements": [
    { "tag": "gd-map", "description": "Interactive map of listings.",
      "attributes": { "zoom": { "json_type": "integer" } }, "leaf": true }
  ]
}
```

`rules` are the fixed authoring rules. `elements` lists **custom elements** — product blocks
with no native HTML equivalent, addressed by their `base_id` as a tag (underscores → dashes,
`gd_map` → `gd-map`) — plus a small set of synthetic **core elements** (currently
`<bs-post-content>` → `core/post-content`, offered only in `single` context). Native HTML
(headings, paragraphs, the grid) needs no vocabulary. Supports the same `?context=` filter as
`/catalog`.

Each element carries `leaf`: `true` for a self-closing widget (configured via attributes only),
`false` for a **container** widget that must be filled with child elements. A non-leaf element
whose block declares an allowlist also carries `children` — the allowed child tags
(`<bs-navbar>` → `children: ["bs-navbar-brand", "bs-nav"]`) — so the model nests exactly what the
block accepts. (This replaced the earlier blanket "every custom element is a leaf" rule, which
contradicted the navbar/accordion families and made the model avoid them.)

---

<a id="convert"></a>
## `POST /ai/convert`

Convert a Bootstrap 5.3 HTML fragment into a native `{ name, attributes, innerBlocks }` tree —
pass `blocks` straight to `createBlock`. Every loss is recorded in `report`; nothing is dropped
silently.

**Request**

```json
{ "html": "<section class=\"py-5 bg-body-tertiary\">…</section>" }
```

**Response**

```json
{
  "blocks": [
    { "name": "blockstrap/blockstrap-widget-container",
      "attributes": { "html_tag": "section", "pt": "5", "pb": "5", "bg": "body-tertiary" },
      "innerBlocks": [] }
  ],
  "report": {
    "elements_total": 14, "elements_converted": 13,
    "dropped_elements": [ { "tag": "svg", "reason": "no mapping", "excerpt": "<svg …" } ],
    "classes_as_attributes": 41, "classes_as_css_class": 3, "classes_dropped": [],
    "inline_styles_dropped": [], "repairs": [], "notes": []
  }
}
```

### How an element resolves

Each element is resolved in this order:

0. **Core element** — a small fixed allowlist of synthetic vocabulary tags that convert to a
   core WordPress block rather than a product block. Currently only `<bs-post-content>` →
   `core/post-content` (the dynamic post body, which no product block provides — required once in
   any single-post template's content area). A leaf: any interior is ignored and noted.
1. **Custom element** — a tag containing `-` that matches a block's dashed `base_id`
   (`<gd-map>`). Its HTML attributes map to same-named block attributes (validated against the
   block's enums; invalid values dropped + reported). A **leaf** widget ignores any children (it
   renders its own interior). A **container** widget — a block whose `nested-block` is set (navbar,
   nav, dropdown, accordion, tabs, modal, offcanvas) — instead **recurses**: its child custom/core
   elements become inner blocks (`<bs-navbar>` > `<bs-navbar-brand>` + `<bs-nav>` > `<bs-nav-item>`
   → the nested navbar block tree). When the block declares an `allowed-blocks` allowlist, a child
   outside it is ignored and noted; a non-widget child (raw HTML the block does not model) is
   ignored and noted.
2. **Claimed element** — a tag (or `tag.class`) a block declared via `ai-html`; the most specific
   selector wins (`a.btn` beats a bare `a`). Structural tags claimed by the container block
   (`div`, `section`, …) get their role inferred from classes (`row`, `col-*`, `card`,
   `card-body`, `list-group*` → the `container` attribute), preserving the tag via `html_tag`.
3. **Unmappable** — anything else. If it has element children it is treated as a transparent
   wrapper and descended into; if not, it is dropped and reported.

### Classes → attributes

For the chosen block, each class is resolved against a reverse index built from that block's own
schema — literal-class enums (`text-center` → `text_align`), `class_prefix` token enums (`mt-3` →
`mt` `"3"`, `bg-primary` → `bg` `"primary"`), and a small exception table (`border-0`,
`text-justify`, `absolute_position` combinations). Anything unmatched lands in `css_class`
verbatim, so styling is never lost.

**Intentional `css_class` residue.** A spill census (`spill-census.php`, aggregated across the
preserved corpora) drives which classes the mapping layer learns. Some top spillers are
*intentionally* left in `css_class` because no block attribute fits and they render correctly
there: image utilities (`object-fit-cover`, `img-fluid`, `card-img-top` — `bs_image` territory),
Bootstrap card typography (`card-title`, `card-text`), child-sizing utilities (`flex-grow-1`,
`flex-shrink-0`, `w-100`), row gutters (`g-*`), the `border-top`/`border-bottom` divider idiom and
the bare `border` marker (no colour to bind to an attribute), and button/nav framework classes a
block emits itself (`text-decoration-none`, `nav-link`). These are accepted, not defects.

Spacing shorthands map to the block's per-side attributes (`p-4` → pt/pr/pb/pl, `py-5` → pt/pb,
`px-4` → pl/pr, plus the `-md-`/`-lg-` breakpoints), so the block's spacing controls reflect the
design; they render the equivalent longhand.

**Responsive variants are mapped, not spilled.** `mt-lg-3` → `mt_lg` `"3"`, `pt-md-4` → `pt_md`
`"4"`, `text-lg-start` → `text_align_lg`, `d-lg-flex` → `display_lg`, `col-md-6` → `col_md`. The
`build_aui_class` breakpoint-collapse bug (an empty base breakpoint dropping the `-lg-`/`-md-`
infix) was fixed across the whole responsive attribute family, so these round-trip to the correct
breakpoint class rather than being kept verbatim in `css_class`.

**Flex utilities map when `display` is set on the same element.** `align-items-*` →
`flex_align_items`, `justify-content-*` → `flex_justify_content` (and their `-md-`/`-lg-` variants)
are lifted into the block's flex attributes whenever the element also carries `d-flex`/`d-inline-flex`
(their `element_require` prerequisite), instead of spilling to `css_class` with the flex controls
left empty.

**Button variants** are decomposed into the button's own fields rather than left as raw classes:
`btn-{v}` / `btn-outline-{v}` → `link_bg`, `btn-lg`/`btn-sm` → `link_size`, and the bare `btn` is
dropped (the block emits it from its link type) so it is not printed twice.

**Coloured borders** — `build_aui_class` emits the `border` attribute as a two-class combo
(`border border-{token}`), so it is consumed as a combo: `border-{token}` (a valid `border` enum
value such as `primary` or `danger-subtle`) → the `border` attribute, and the bare `border`
on-marker beside it is absorbed rather than spilled. `border-0` (the None case) maps via the
exception table; a non-enum `border-top` (an `<hr>` rule) is left to spill.

**Border radius is border-independent.** The radius fields (`rounded`, `rounded_size`,
`rounded_size_md/lg`) are gated behind a visible border in the *editor form*, but `build_aui_class`
emits the radius class regardless. So a radius whose editor condition is unmet (a `rounded-4` on a
`border-0` or borderless element) is kept as its attribute rather than spilled to `css_class` —
keeping it renders the same class. (Attributes that genuinely depend on the border, like
`border_type`/`border_width`, still spill when the border is off.) The same holds for
`flex_align_self` (gated on `container=="col"` in the form, emitted regardless): `align-self-*`
maps to the attribute rather than spilling.

**`.small` is the fs-sm utility.** Bootstrap's bare `.small` renders the same font-size as the AUI
`.fs-sm` token, so it is mapped to a block's font-size attribute wherever that attribute accepts
`fs-sm` (`small` → `font_size:"fs-sm"`, or `title_size`/`desc_size` on a folded caption). This is
universal in the class consumer, not only on the badge/composite paths.

**Container flex, gap and ratio families.** The container declares the flex-layout families that
Bootstrap layouts lean on, so they map instead of spilling: `flex-column`/`flex-row`/`flex-md-row`
→ `flex_direction`(+`_md`/`_lg`), `flex-wrap`/`flex-nowrap` → `flex_wrap`(+`_md`/`_lg`), `gap-2`/
`gap-md-3` → `gap`(+`_md`/`_lg`), and `align-self-*` → `flex_align_self`. Aspect ratios are a
two-class combo (`ratio ratio-16x9`) consumed like `border`: `ratio-16x9` → the `ratio` attribute,
the bare `ratio` marker absorbed. All render the same classes back out.

**Defaults neutralization.** A block renders its own schema default for any styling attribute the
markup leaves unset, injecting classes the design never asked for (a text block's `mb-3`, a
container's max-width, an icon-box's centered alignment and primary background). For every styling
attribute the source did not set, the converter pins the neutral enum value (`""`/`"0"`) so the
block renders nothing for it. Functional attributes (the HTML tag, link type, image mode) carry no
utility class and are left alone.

An attribute whose `element_require` condition is not met is first given a chance to be satisfied
by setting its unambiguous equality prerequisites; if it still cannot be met (e.g. `rounded-4` on
a `border-0` element, where the block hides the radius control behind a border), the source class
is spilled back to `css_class` so the design still renders, rather than being dropped. Only an
attribute with no recoverable source class is dropped and noted.

### Content, nesting, safety

- **Text** → the block's text attribute. `bs_text`/`bs_heading` store post-safe HTML, so inline
  `<strong>`/`<a>` is preserved; a plain-text field (`bs_button`) receives flattened text.
- **Images** — `<img>` → `img_url` + `img_src:"url"` + `img_alt`.
- **Links** — an `<a>`/`<button>` `href` → the block's custom-URL attribute (`type:"custom"`).
- **Icons** — a standalone `<i class="fa-…">` maps to the icon block's `icon_class`; an inline
  `<i>` inside a button/heading fills that block's icon attribute.
- **Composite decomposition** — an element that maps to a block modelling sub-parts is split
  across the block's dedicated attributes rather than crammed into one.
  **Hard fold boundaries:** interactive and claimed elements outrank composites categorically and
  are never absorbed by any icon composite (icon item, media object, chip). An `<a class="btn">`,
  `<button>`, `<img>`, badge or custom element inside a would-be composite forces it to convert as
  a plain container with those elements as their own blocks — a button's leading `<i>` is the
  *button's* icon (`icon_class`/`icon_position`), never a chip. This holds even for an **icon-only**
  `<a class="btn">`/`<button>` (a share/social circle): it is a `bs_button` with `icon_class` set
  and no text, never an icon-box, even when it carries a chip-like `rounded-circle` marker. The
  single exception is a plain (unclaimed) `<a>` wrapping an entire icon+label item, which becomes
  that item's `custom_url`.
  A composite also never fuses more than one text run: a multi-run inline row (a trust line of
  bullet-separated spans, a `justify-content-between` assurance strip) stays as separate blocks
  rather than one fused title. Two label units disqualify the fold regardless of which one carries
  an icon — a row of `<span><i></i> A</span><span><i></i> B</span></span>` is two blocks, not one.
  Finally, a Font Awesome identity class (`fa-*`, `fas`/`far`/`fab`) never appears in any
  `css_class`: an icon's identity always belongs in a block's icon field, so a stray one left over
  from a composite is stripped (and noted), never spilled.
  - *Icon + text item* (`<li><i class="fa-check text-success-emphasis"></i><span class="text-body
    small">Copy</span></li>`): identity classes → `icon_class`, `text-{token}` → `icon_color`,
    `fs-*` → `icon_size`, the copy → `title`, and the item's **and its inner text element's** own
    colour/size → `title_color`/`title_size` (everything else spills to `css_class`). A wrapping
    `<a href>` → `type:"custom"` + `custom_url`, otherwise `type:"none"`. Gap utilities (`me-2`)
    and the wrapper's/chip's own flex layout (`d-flex`, `align-items-*`, `gap-*`) are absorbed and
    noted — the icon-box lays out its own icon and copy. Structural shape (`title_tag:"div"`,
    `icon_position:"left"`) is set; size/colour are left to neutralization. When the copy **leads
    with a bold run** (`<strong>Systemic Efficiency:</strong> …`), it splits: the bold lead
    (trailing colon stripped) → `title`, the remainder → `description`, so a "label: sentence"
    caption no longer fuses into one over-long title. A mid-text bold is emphasis, not a title.
  - *Icon chip* (`<div class="d-inline-flex p-3 bg-primary-subtle text-primary-emphasis
    rounded-3"><i class="fa-lg"></i></div>`, an icon-only coloured wrapper) → a single `bs_iconbox`
    with `icon_type:"iconbox-fill"`, `bg-{v}-subtle` → `icon_bg`, `text-{v}-emphasis` →
    `icon_color`, `rounded-{n}`/`rounded-circle`/`rounded-pill` → `icon_rounded_size`; padding
    renders through normal consumption. The `icon_bg`/`icon_color` enums carry the
    `-subtle`/`-emphasis` tokens, so the chip round-trips.
  - *Media object* (a chip or icon beside a caption — the trust/feature card, the most common
    real-world icon pattern): the icon plus its adjacent copy fold into ONE `bs_iconbox`. The icon
    may be an icon chip **or a bare `<i class="fa-…">`**, leading the row (`[icon][copy]`) or — for
    the narrow "name + verified badge" shape only — a bare icon trailing a single heading/label
    (`[h3 Elena Vance][i.fa-circle-check]` → one titled icon-box, not a stranded icon beside the
    heading). The caption may be direct siblings
    (`[chip][h3][p]`, or the dominant `[chip][span]` label shape), or wrapped in one element
    (`[chip][div > h3 + p]`, which collapses). The **title** comes from a heading (→ `title_tag`
    too), an inline label (`span`/`small`/`strong`/`b`/`em`), or an inline-only `<div>` caption
    row; a following paragraph or a second caption line → **description**. The title source's
    classes route through the `title_*` family (`small` → `title_size:"fs-sm"`, `fw-semibold` →
    `title_font_weight`, `text-body-emphasis` → `title_color`); the description's through `desc_*`.
    An inline label may carry inline markup (`<br>`, `<strong>`) — it is flattened to one clean
    title string with `<br>` read as a space. The layout sets `icon_position` (`"left"` for a flex
    row, top for a vertical stack). The stack's alignment (`text-start`/`center`/`end`, incl.
    `-md-`/`-lg-`) is read from the wrapper (else the title element) and mapped to
    `icon_text_align`/`title_text_align`/`desc_text_align`. The chip's own `fs-*` → `icon_size`.
    Spill hygiene: the chip's `p-*` is absorbed into the icon-box's per-side padding; an explicit
    `mb-0` is never silently absorbed (a blank margin renders a default `mb-3`), so it maps to
    `mb:"0"` on the outer element and `title_pb`/`desc_pb` `"0"` on the folded copy; a bare `small`
    becomes the target's font-size attribute (`desc_size`/`title_size`) as `"fs-sm"`.
    **Trailing content is preserved, not swallowed:** the caption is the contiguous run right after
    the icon; the first non-caption element (a feature `<ul>`, a CTA button, an image, a second
    heading) ends the run and stays as a following sibling — the caption still folds into a *titled*
    icon-box, so nothing is stranded and nothing beyond the caption is lost. (An inline "icon + text"
    row whose icon is not the first child — e.g. a status pill led by a spinner — is decomposed the
    same way, into one titled icon-box.)
  - *Badge* (`span.badge` / `a.badge`) → `bs_badge`: `bg-{v}`/`bg-{v}-subtle` → `badge_bg`,
    `text-{v}` → `text_color`, `rounded-pill` → `badge_style`, `fs-*` and the bare `small` →
    `font_size`, spacing expands to per-side attributes, a wrapping `<a href>` → `type:"custom"`.
    The bare `badge` class is dropped (the block emits it).
  - *Button variants* — see the class-mapping section above.
- **Dividers, loose text, decorative elements** — an `<hr>` becomes the generic wrapper carrying
  its classes plus `border-top`; a loose text node directly inside a container is wrapped in a text
  block; an empty but styled element (a decorative dot) keeps its classes on the text block, which
  renders its wrapper even when empty.
- **Line breaks** — a `<br>` is content flow, never dropped: inside a text run or a folded caption
  it is absorbed by that copy (a line break where the target attribute stores HTML, a space where
  it is plain text); a standalone `<br>` between block-level runs is skipped silently.
- **Nesting** — a missing structural wrapper is synthesized rather than dropping content: an
  orphan `col` gains a `row` parent, orphan `card-*` parts gain a `card`. Any `col-*` breakpoint
  variant is recognized as a column even without a bare `col`. Noted in the report.
- **Forms** — a hand-built `<form>` is a safety-net claim (`ai-html-fallback`) mapping to
  `bs_contact`; its hand-built fields are dropped and noted, because the block renders its own.
  The model is expected to use the `<bs-contact>` custom element instead (advertised in the
  vocabulary).
- **Background styles** — an inline `style=""` is not blindly dropped: its background declarations
  are consumed into the block's bg attribute family (the inbound half of `Utils::get_bg_styles`),
  mapping only when the block declares the target field. `background-image:`/`background:` with a
  gradient → `bg:"custom-gradient"` + `bg_gradient`; `background-color: <hex|rgb|hsl>` →
  `bg:"custom-color"` + `bg_color`; `background-image: url(…)` → `bg_image` (+
  `background-attachment: fixed` → `bg_image_fixed`); `background-clip: text` /
  `-webkit-background-clip: text` → `bg_on_text:"1"`, with its transparent `text-fill-color`/`color`
  companions absorbed silently. A non-transparent `color: <hex|rgb|hsl>` maps to the
  `text_color:"custom"` + `text_color_custom` pair (evaluated after the combo rule, so a combo's
  `color: transparent` is a companion, never a text colour). Unsafe values (`expression(`,
  `javascript:`, `;`/`{`/`}`) are rejected. This round-trips: the renderer emits the same
  declarations, and a re-convert reproduces the identical attributes.
- **Bootstrap validity repairs** — some utilities are structurally invalid where the model places
  them and are rewritten deterministically rather than dropped, recorded under the report's
  `repairs` key (not as loss). Flex `gap-*` on a `.row` fights the grid's padding-gutter system, so
  on any `.row` element `gap{-bp}?-N` → `g{-bp}?-N`, `row-gap{-bp}?-N` → `gy{-bp}?-N`, and
  `column-gap{-bp}?-N` → `gx{-bp}?-N` (the source is dropped when the target gutter class is already
  present).
- **Sanitization** — `<script>`/`<style>` blocks, event-handler attributes, and `javascript:`
  URLs are stripped; any `style=` declaration that is not a consumable background (see above) is
  dropped and reported as the residue of that element's inline style.
- **Dark-mode lint** (report-only) — `bg-white`, `text-dark`, `bg-light` are flagged in
  `report.notes`; nothing is rewritten.
- Depth limit 12, ~500 nodes; truncation is noted.

### Answered design questions

- **Does `bs_text` store HTML?** Yes — its `text` attribute is rendered through `wp_kses_post`, so
  inline markup round-trips. Plain-text fields do not, and receive flattened text.
- **Icons** — mapped to the icon block's `icon_class` (auto-detected as the field flagged
  `icon_picker`), not dropped. An icon that accompanies text (a list item) is decomposed into a
  full icon-box — see composite decomposition above.
- **`py-*`/`px-*`** — expanded to `pt`+`pb` / `pl`+`pr` (no block has a combined padding
  attribute).

---

<a id="render"></a>
## `POST /ai/render` (dev-gated)

Renders a native tree to front-end HTML, returning `{ "html": "…" }` — the output of a converted
tree without a browser, for the fidelity harness. SuperDuper blocks are not registered as
server-rendered Gutenberg blocks (so `do_blocks()` on their comment markup produces nothing), so
the tree is rendered bottom-up through each block's own `shortcode_output()`: a block's children
are rendered first and passed as its content, exactly as a nested layout renders on the front end.
Sibling blocks are joined with a newline so adjacent text does not fuse when whitespace between
tags is collapsed.

Because it executes render callbacks on arbitrary input it is **off in production**: gated behind
the AI capability *and* the `ayecode_sd_rest_ai_render_enabled` filter (default `false`). Enable it
only in CI/dev:

```php
add_filter( 'ayecode_sd_rest_ai_render_enabled', '__return_true' );
```

Disabled, the route returns `404 rest_render_disabled`.

---

## Notes

- The catalog reflects **only active products** — the transformer iterates every block registered
  through `ayecode_sd_register()`, so adding or removing a plugin changes the output automatically.
  `schema_hash` changes whenever any transformed schema does.
- Directly-instantiated blocks (not registered through the Registry) are not included, same as the
  [block REST API](rest-api.md).
- Attributes for images-as-upload, background images, visibility conditions, and hover animations
  are excluded from the AI schema in this version.
