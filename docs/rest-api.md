# REST API

WP Super Duper exposes a read-only REST API for introspecting all registered blocks.

**Namespace:** `ayecode/v3`  
**Base path:** `/wp-json/ayecode/v3/blocks`

---

## Authentication

All endpoints require the `edit_posts` capability (Editor role or above). Requests from unauthenticated users or Subscribers receive a `401 Unauthorized` response.

The required capability can be changed via the `ayecode_sd_rest_blocks_capability` filter:

```php
add_filter( 'ayecode_sd_rest_blocks_capability', function () {
    return 'manage_options'; // Restrict to Administrators only.
} );
```

---

## Endpoints

### List all blocks

```
GET /wp-json/ayecode/v3/blocks
```

Returns a summary of every registered block.

**Query parameters**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `textdomain` | string | No | Filter results to blocks registered under a specific textdomain. |

**Example request**

```
GET /wp-json/ayecode/v3/blocks
GET /wp-json/ayecode/v3/blocks?textdomain=geodirectory
```

**Example response**

```json
[
  {
    "slug": "bs_dark_mode",
    "name": "BS > Dark Mode",
    "description": "A dark/light mode switcher.",
    "textdomain": "blockstrap",
    "class_name": "BlockStrap_Widget_Dark_Mode"
  },
  {
    "slug": "gd_map",
    "name": "GD > Map",
    "description": "A Google Map.",
    "textdomain": "geodirectory",
    "class_name": "GD_Widget_Map"
  }
]
```

---

### Get a single block schema

```
GET /wp-json/ayecode/v3/blocks/{slug}
```

Returns the full configuration and compiled field definitions for one block.

**URL parameters**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `slug` | string | Yes | The block's `base_id` (e.g. `gd_map`). |

**Example request**

```
GET /wp-json/ayecode/v3/blocks/gd_map
```

**Example response**

```json
{
  "slug": "gd_map",
  "name": "GD > Map",
  "description": "A Google Map.",
  "textdomain": "geodirectory",
  "class_name": "GD_Widget_Map",
  "icon": "fas fa-map",
  "category": "widgets",
  "output_types": ["block", "shortcode", "widget"],
  "block_group_tabs": {
    "content": { "..." },
    "styles": { "..." },
    "advanced": { "..." }
  },
  "arguments": {
    "zoom": {
      "type": "number",
      "title": "Zoom",
      "default": "12",
      "group": "content"
    }
  }
}
```

**Response fields**

| Field | Type | Description |
|---|---|---|
| `slug` | string | The block's unique `base_id`. |
| `name` | string | Human-readable block name. |
| `description` | string | Widget description string from `widget_ops`. |
| `textdomain` | string | The plugin/package textdomain. |
| `class_name` | string | PHP class name. |
| `icon` | string | Font Awesome or Dashicon class (e.g. `fas fa-star`). |
| `category` | string | Gutenberg block category (e.g. `widgets`). |
| `output_types` | array | Enabled output types: `block`, `shortcode`, `widget`. |
| `block_group_tabs` | object | Inspector panel tab/group structure, or empty object if not defined. |
| `arguments` | object | Compiled field definitions keyed by field name (result of `get_arguments()`). |

**404 response**

```json
{
  "code": "rest_block_not_found",
  "message": "No block found with that slug.",
  "data": { "status": 404 }
}
```

---

## Notes

- The `arguments` object reflects the fully compiled field definitions after `set_arguments()` and any `wp_super_duper_arguments` filters have run.
- Blocks registered via `ayecode_sd_register()` are included. Blocks instantiated directly (without going through the Registry) are not.
- The API is only available after `widgets_init` has fired, which is always the case for standard REST API requests.
