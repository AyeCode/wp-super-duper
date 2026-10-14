# AI Schema API — build status & map

Living status doc for the AI block API. Kept current as work lands.
Design: `~/.claude/plans/blockstrap-ai-woolly-treehouse.md` (see **Revision 2** at its top).
Contract: `docs/dev/ai-api.md`. Consumer spec: `wp-ai-labs/docs/block-api-spec.md`.

**Last updated:** 2026-07-27

**Where we are:** both generation paths are built and live.
- **Schema-first** (`/catalog` + `/schemas`) returns the createBlock-ready native contract
  (block_name, tiered native attributes, enums, nesting, image-URL path). Used by the single-block
  edit wand.
- **HTML-first** (`/vocabulary` + `/convert`, plus dev-gated `/render`) — the pivot. The model
  designs in Bootstrap 5.3 HTML and the server deterministically converts it to a native block
  tree with a loss report. `class_prefix` metadata + `ai-html` claims added; converter mirrors
  `Utils::build_aui_class` in reverse. See the HTML-first spec (the `/convert` implementation
  brief) and `docs/dev/ai-api.md`.

213 CLI tests pass, phpcs clean. Not yet done: wp-ai-labs wiring to the new endpoints, patterns,
gold-standard examples, and the fidelity harness that scores conversions via `/render`.

---

## The architecture (decided)

The consumer is `wp-ai-labs`. It works like this:

1. User types a prompt (or converses) in an editor sidebar.
2. `wp-ai-labs` sends prompt + a block catalog (from THIS api) to its own server route,
   which calls the WordPress AI Client and gets back a **tree of `{name, attributes, innerBlocks}`**.
3. The editor inserts that tree with **`createBlock()`** client-side. Gutenberg's own
   `serialize()` writes the markup.

Consequences that shape everything here:

- **We emit the NATIVE contract.** Blocks are identified by their real Gutenberg
  `block_name` (`blockstrap/blockstrap-widget-container`), and attributes use **native keys
  and native values** (`text_align_lg: "text-lg-start"`), because `createBlock` matches them
  against the block's registered attributes and drops anything else.
- **No `/convert`, no `SaveMarkup`.** `createBlock` + editor `serialize()` produce correct
  markup for free. This deletes the riskiest part of the original plan.
- **No `/validate` for MVP.** `createBlock` cannot make an invalid block (unknown names and
  undeclared attrs are dropped, values coerced). Validate returns later only as a *quality*
  gate for things that are valid-but-render-wrong (a `col` outside a `row`).
- **Generation is pattern-first.** The model assembles vetted, pre-designed sections
  (BlockStrap patterns) and writes the copy, dropping to raw blocks only for gaps. This is
  the main "not shoddy" lever — a human designed the sections.
- **Images v1:** `bs_image` with `img_src:"url"`, a placeholder-service `img_url`, and an
  AI-written `img_alt`. AI-generated / media-library images are a later editor feature.

---

## Where quality is won (priority order)

1. **Patterns** — assemble designer-made sections, don't generate layout from scratch.
2. **Examples** — 1-2 gold-standard page trees in the catalog; the model imitates them.
3. **Design-system system prompt** (in wp-ai-labs) — spacing rhythm, one accent + one
   neutral, bs_heading for headings, etc.
4. **Block descriptions** — a good `ai-hint` per block so the model picks the right one.

---

## Endpoints

| Route | Method | Status | Purpose |
|---|---|---|---|
| `/ai/catalog` | GET | ✅ Live (native) | Bulk list: block_name + content-tier native attrs (+ `html` claims), for planning/generation. |
| `/ai/schemas?blocks=…` | GET | ✅ Live (native) | Per-block full native schema (content + style tiers), for the single-block edit "wand". |
| `/ai/vocabulary` | GET | ✅ Live | Authoring rules + custom-element list for the HTML-first prompt. |
| `/ai/convert` | POST | ✅ Live | HTML → native block tree + loss report (the HTML-first pivot). |
| `/ai/render` | POST | ✅ Live (dev-gated) | Native tree → front-end HTML, for the fidelity harness. |
| `/ai/validate` | POST | ⏸ Deferred (quality phase) | — |

### Test (replace with your app password)

```bash
CREDS='admin:XXXX XXXX XXXX XXXX XXXX XXXX'
curl -s -u "$CREDS" "http://localhost/wp-json/ayecode/v3/ai/catalog"
curl -s -u "$CREDS" "http://localhost/wp-json/ayecode/v3/ai/schemas?blocks=bs_container,bs_heading"
```

---

## Where things live

### This library (`wp-super-duper`)

| File | Role | Status |
|---|---|---|
| `src/Ai/SchemaTransformer.php` | Raw `get_arguments()` → native, tiered AI schema (+ `class_prefix`) | ✅ native + tiering |
| `src/Ai/Catalog.php` | Assembles `/catalog` + `/schemas`; block_name, tiering, nesting, inner_blocks, `html` claims | ✅ native |
| `src/Ai/RestApi.php` | Routes, auth, params | ✅ (catalog, schemas, vocabulary, convert, render) |
| `src/Ai/RequireExpression.php` | `element_require` parser/evaluator | ✅ kept (feeds `requires` info + converter pruning) |
| `src/Ai/ClassMap.php` | Reverse of `build_aui_class`: prefix map + literal attrs + exceptions | ✅ new |
| `src/Ai/ReverseIndex.php` | Per-block `class → {attr,value}` index + content/icon/image/link attr resolution | ✅ new |
| `src/Ai/HtmlSanitizer.php` | Strips script/style/event handlers/dangerous URLs before parsing | ✅ new |
| `src/Ai/Converter.php` | HTML fragment → native block tree + loss report | ✅ new |
| `src/Ai/Vocabulary.php` | `/vocabulary`: authoring rules + custom elements | ✅ new |
| `src/Ai/Renderer.php` | `/render`: native tree → block markup → `do_blocks()` (dev-gated) | ✅ new |
| `src/Ai/Patterns.php` | Expose BlockStrap patterns to the catalog | ⬜ new (quality upgrade, not MVP-blocking) |
| `src/Ai/Validator.php` | Quality gate | ⏸ deferred |
| `src/Loader.php` | Wires `Ai\RestApi` onto `rest_api_init` | ✅ |
| `tests/ai/*` | CLI runner + tests (101 passing) | ✅ native |
| `docs/dev/ai-api.md` | Contract | ✅ rewritten to native (examples pulled from live output) |

### Consumer (`wp-ai-labs`)

| File | Role |
|---|---|
| `src/Rest/AiController.php` | Server generate + single-block modify (WP AI Client) — working |
| `assets/src/lib/block-tree.js` | `createBlock` insertion, drops invalid — working |
| `assets/src/lib/block-catalog.js` | Currently builds catalog client-side from `window.MyBlockAPI`; to be sourced from our API |
| system prompt (in AiController) | The design-system layer — needs authoring for quality |

---

## Native contract (what /catalog & /schemas will return)

Per block: `block_name`, `title`, `context`, `description` (from `ai-hint`), `inner_blocks`
(`"none"` / `"any"` / allowlist of block_names), and `attributes` keyed by native attribute
name. Per attribute: `json_type`, `tier` (`content` | `style`), `enum` + `enum_labels` for
selects, `default`, optional `requires` (conditional-visibility info), `format` for
color/gradient.

`tier` drives size: `/catalog` returns **content-tier only** (the ~6 authoring attrs), the
per-block `/schemas` returns content + style (for the edit wand). `checkbox` is
`json_type: string`, `enum: ["","1"]` — never boolean.

---

## Remaining work (in order)

1. ~~Reshape transformer + catalog to native~~ ✅ DONE.
2. **Author `ai-hint` for the ~37 blocks** (BlockStrap repo) — in progress, done via an agent
   prompt, reviewed manually. High quality impact; unblocks good block selection.
3. **Wire wp-ai-labs to `/ai/catalog`** — source the catalog from our endpoint (retire the
   `window.MyBlockAPI` client path), map our native attr schema into its generate/modify
   flow, and add the design-system system prompt. This is what makes the loop run end-to-end.
   NOT blocked by patterns.
4. **`Patterns` exposure** — surface BlockStrap `patterns/*` as described, composable sections;
   the main quality upgrade, best done AFTER the loop is proven end-to-end.
5. **Gold-standard example tree(s)** in `/catalog` or `/schemas` `examples`.
6. **Images** — placeholder-service URL guidance in the prompt; AI treats `bs_image` as
   `img_src:"url"` + `img_url` + `img_alt`.
7. ~~Rewrite `docs/dev/ai-api.md` to the native contract~~ ✅ DONE.
8. **Validate** (quality phase) — reintroduce as a soft check for valid-but-wrong trees.

---

## Known pre-existing bugs (separate tickets)

- `bs_container`/`bs_nav` flex-alignment fields barely work in the editor (element_require too
  complex for the flat parser). 9 fields on container, 3 on nav.
- Bare-negation `element_require` silently drops (~16 fields always shown):
  `bs_archive_actions`, `bs_pagination`, `bs_search`.
- `bs_navbar.cscos` mixes `&&`/`||`; flat parser can't honour it.
- `bs_post_info` declares `block-wrap` twice.
- All 37 blocks use `block-category => 'layout'`, so catalog `category` carries no grouping
  signal — `context` + descriptions do that work.

---

## Deferred to later

- Validate as a quality gate; `visibility_conditions`, `hover_animations` in the schema.
- AI-generated images + media-library insertion (upload → use attachment ID in `bs_image`).
- Conversational multi-turn refinement (built on the same generate + modify primitives).
