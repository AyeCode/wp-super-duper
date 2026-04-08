# Dependent Fields

## Overview

The Dependent Fields feature allows you to create dynamic relationships between form fields in Gutenberg blocks. When a parent field changes, dependent child fields automatically update their options via REST API calls.

**Common Use Case**: Linking a Custom Post Type selector to a Category/Taxonomy selector - when the user selects a post type, the category dropdown automatically populates with categories for that specific post type.

## Features

- **Dynamic Option Loading**: Fetches options via WordPress REST API when parent field changes
- **Global Caching**: Prevents duplicate API calls across multiple block instances
- **Performance Optimized**: Only fetches when block inspector panel is open
- **Template String Support**: Use `{field_name}` placeholders in REST paths
- **Automatic State Management**: Clears dependent field when parent is empty
- **Race Condition Prevention**: Handles concurrent requests gracefully

## Configuration

Add a `depends_on` configuration to any field argument:

```php
'arguments' => array(
    'post_type' => array(
        'title' => __('Post Type', 'textdomain'),
        'type'  => 'select',
        'options' => array(
            'post' => 'Posts',
            'page' => 'Pages',
            'gd_place' => 'Places',
        ),
        'default' => 'post',
    ),
    'category' => array(
        'title' => __('Category', 'textdomain'),
        'type'  => 'select',
        'depends_on' => array(
            'attribute'    => 'post_type',           // Parent field name
            'fetch_type'   => 'rest',                // Currently only 'rest' supported
            'rest_path'    => '/wp/v2/{post_type}category/?per_page=100', // {post_type} replaced with parent value
            'cache_key'    => 'taxonomy',            // Optional: Custom cache key prefix
            'map_response' => 'default',             // Optional: Response mapping format
            'default_option' => array(               // Optional: First option in dropdown
                'label' => '-- Select Category --',
                'value' => ''
            ),
        ),
    ),
)
```

## Configuration Options

### `depends_on` Array

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `attribute` | string | Yes | The name of the parent field this field depends on |
| `fetch_type` | string | Yes | Currently only `'rest'` is supported |
| `rest_path` | string | Yes | WordPress REST API path. Supports `{field}` placeholders |
| `cache_key` | string | No | Custom cache key prefix. Defaults to `rest:{path}` |
| `map_response` | string | No | Response format mapping. Default: `'default'` |
| `default_option` | array | No | First option to prepend to results. Format: `['label' => '', 'value' => '']` |

## Template String Syntax

Use `{field_name}` in `rest_path` to inject values from other fields:

```php
'rest_path' => '/wp/v2/{post_type}category/?per_page=100'
// If post_type = "gd_place", becomes:
// /wp/v2/gd_placecategory/?per_page=100
```

## Response Mapping

The `map_response` parameter controls how REST API responses are converted to select options:

### `'default'` (Auto-detection)

Automatically maps common WordPress REST API response formats:

```php
// Maps { name: "Category Name", id: 123 }
// OR { title: { rendered: "Post Title" }, id: 123 }
// TO { label: "Category Name", value: 123 }
```

## Caching Behavior

- **Global Cache**: Stored in `window.sdDependentFieldCache`
- **Cache Key Format**: `{cache_key}:{parent_value}` or `rest:{resolved_path}:{parent_value}`
- **Shared Across Blocks**: Multiple blocks with same parent value share cached data
- **Automatic Invalidation**: Cache cleared when parent field changes

## User Experience

1. User opens block inspector panel
2. User selects value in parent field (e.g., "Places" post type)
3. Dependent field shows "Loading options..."
4. API call fetches data (if not cached)
5. Dependent field updates with new options
6. User changes parent field again → dependent field instantly updates (from cache)
7. User clears parent field → dependent field clears

## Implementation Details

### JavaScript Architecture

Located in `assets/js/super-duper-block-editor.js`:

1. **Global Cache** (Line 20):
   ```javascript
   window.sdDependentFieldCache = window.sdDependentFieldCache || {};
   ```

2. **Utility Functions**:
   - `resolvePath()` - Replaces `{field}` placeholders
   - `mapRestResponse()` - Transforms API responses to select options
   - `fetchDependentOptions()` - Handles API calls with caching

3. **React State Management**:
   - Per-component state for reactive UI updates
   - Global cache for cross-instance data sharing
   - Hybrid approach: fallback reads from cache during render

4. **Race Condition Prevention**:
   - Fetching flags prevent duplicate concurrent requests
   - Format: `__fetching__${cacheKey}`

### PHP Configuration

Configuration is passed through `includes/traits/trait-gutenberg-block.php` without modification. The `depends_on` array is automatically serialized and passed to JavaScript in the block registration.

## Examples

### Basic Post Type → Category

```php
'post_type' => array(
    'title' => __('Post Type', 'textdomain'),
    'type'  => 'select',
    'options' => array(
        'post' => 'Posts',
        'gd_place' => 'Places',
    ),
),
'category' => array(
    'title' => __('Category', 'textdomain'),
    'type'  => 'select',
    'depends_on' => array(
        'attribute'  => 'post_type',
        'fetch_type' => 'rest',
        'rest_path'  => '/wp/v2/{post_type}category/?per_page=100',
    ),
),
```

### With Default Option

```php
'category' => array(
    'title' => __('Category', 'textdomain'),
    'type'  => 'select',
    'depends_on' => array(
        'attribute'  => 'post_type',
        'fetch_type' => 'rest',
        'rest_path'  => '/wp/v2/{post_type}category/?per_page=100',
        'default_option' => array(
            'label' => __('All Categories', 'textdomain'),
            'value' => '0'
        ),
    ),
),
```

### Custom Cache Key

```php
'depends_on' => array(
    'attribute'  => 'post_type',
    'fetch_type' => 'rest',
    'rest_path'  => '/wp/v2/{post_type}category/?per_page=100',
    'cache_key'  => 'my_custom_taxonomy', // Custom prefix
),
```

## Troubleshooting

### Options not loading

- Verify REST API path is correct
- Check browser console for API errors
- Ensure parent field has a non-empty value

### Duplicate API calls

- Should be prevented by caching mechanism
- Check for race conditions in browser network tab
- Verify `cache_key` is consistent

### Options not updating when parent changes

- Ensure parent field `attribute` name matches exactly
- Check that parent value is not empty string or '0'
- Verify cache key generation is consistent

## Browser Compatibility

Requires modern browser with:
- ES6 support (arrow functions, async/await)
- React Hooks support (WordPress 5.8+)
- Fetch API or WordPress `wp.apiFetch` polyfill

## Performance Considerations

- **API Calls**: Only triggered when block inspector is open
- **Caching**: Eliminates redundant requests for same parent value
- **Memory**: Cache stored in browser memory, cleared on page refresh
- **Network**: Respects WordPress REST API rate limits

## Future Enhancements

Potential future improvements:
- Custom fetch functions beyond REST API
- Cache persistence (localStorage)
- Dependent field chains (field A → field B → field C)
- Custom response mapping functions
- Cache TTL (time-to-live) configuration
