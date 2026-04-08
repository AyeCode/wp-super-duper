# API Reference

## Super Duper Configuration Options

This document outlines all configuration options available when creating a WP Super Duper widget/block/shortcode.

## Constructor Options Array

Pass these options to `parent::__construct()`:

```php
$options = array(
    'textdomain'     => 'your-textdomain',
    'block-icon'     => 'dashicons-admin-site',
    'block-category' => 'widgets',
    'block-keywords' => "['keyword1','keyword2']",
    'block-output'   => array(/* ... */),
    'block-wrap'     => 'div',
    'class_name'     => __CLASS__,
    'base_id'        => 'unique_id',
    'name'           => __('Display Name', 'textdomain'),
    'widget_ops'     => array(/* ... */),
    'arguments'      => array(/* ... */),
);
```

### Top-Level Options

| Option | Type | Required | Description |
|--------|------|----------|-------------|
| `textdomain` | string | Yes | Plugin/theme textdomain for translations and block namespacing |
| `block-icon` | string | Yes | Dashicon name (e.g., `dashicons-admin-site`) or Font Awesome 5 class (e.g., `fas fa-globe`) |
| `block-category` | string | Yes | Block category: `common`, `formatting`, `layout`, `widgets`, `embed` |
| `block-keywords` | string | No | JSON array string of search keywords (max 3): `"['keyword1','keyword2']"` |
| `block-output` | array | Yes | Array defining block visual output elements |
| `block-wrap` | string | No | HTML element to wrap block: `div`, `span`, or empty for no wrap |
| `class_name` | string | Yes | PHP class name (typically `__CLASS__`) |
| `base_id` | string | Yes | Unique identifier used for widget ID and shortcode name |
| `name` | string | Yes | Human-readable name for widget/block |
| `widget_ops` | array | Yes | Widget configuration options |
| `no_wrap` | boolean | No | If `true`, prevents widget wrapper div |
| `arguments` | array | Yes | Field definitions for the widget/block/shortcode |

## Field Arguments

Each field in the `arguments` array follows this structure:

```php
'field_name' => array(
    'title'       => __('Field Title', 'textdomain'),
    'desc'        => __('Field description', 'textdomain'),
    'type'        => 'text',
    'placeholder' => '',
    'default'     => '',
    'desc_tip'    => true,
    'advanced'    => false,
    'options'     => array(), // For select, radio, etc.
    'depends_on'  => array(), // For dependent fields
)
```

### Common Field Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `title` | string | Yes | Label displayed for the field |
| `desc` | string | No | Help text/description |
| `type` | string | Yes | Field type (see Field Types below) |
| `placeholder` | string | No | Placeholder text for input fields |
| `default` | mixed | No | Default value |
| `desc_tip` | boolean | No | Show description as tooltip |
| `advanced` | boolean | No | Mark as advanced option |

### Field Types

#### `text`
Basic text input field.

```php
'field_name' => array(
    'title' => __('Text Field', 'textdomain'),
    'type'  => 'text',
    'placeholder' => 'Enter text...',
    'default' => '',
)
```

#### `select`
Dropdown selection field.

```php
'field_name' => array(
    'title' => __('Select Field', 'textdomain'),
    'type'  => 'select',
    'options' => array(
        'option1' => 'Option 1',
        'option2' => 'Option 2',
    ),
    'default' => 'option1',
)
```

#### `checkbox`
Boolean checkbox field.

```php
'field_name' => array(
    'title' => __('Checkbox', 'textdomain'),
    'type'  => 'checkbox',
    'default' => '1', // Checked by default
)
```

#### `number`
Numeric input field.

```php
'field_name' => array(
    'title' => __('Number', 'textdomain'),
    'type'  => 'number',
    'default' => 10,
    'placeholder' => '0',
)
```

#### `textarea`
Multi-line text input.

```php
'field_name' => array(
    'title' => __('Textarea', 'textdomain'),
    'type'  => 'textarea',
    'rows'  => 5,
)
```

## Dependent Fields

See [Dependent Fields Documentation](features/dependent-fields.md) for detailed information.

Quick example:

```php
'parent_field' => array(
    'title' => __('Parent', 'textdomain'),
    'type'  => 'select',
    'options' => array('a' => 'A', 'b' => 'B'),
),
'dependent_field' => array(
    'title' => __('Dependent', 'textdomain'),
    'type'  => 'select',
    'depends_on' => array(
        'attribute'  => 'parent_field',
        'fetch_type' => 'rest',
        'rest_path'  => '/wp/v2/categories/?parent={parent_field}',
    ),
),
```

## Block Output Configuration

The `block-output` array defines how the block renders in the editor:

```php
'block-output' => array(
    array(
        'element'       => 'BlocksProps',
        'inner_element' => 'div',
        'blockProps'    => array(
            'className' => '[%WrapClass%]',
        ),
        'content' => 'Output: [%field_name%]',
    ),
)
```

### Block Output Parameters

| Parameter | Description |
|-----------|-------------|
| `element` | HTML element or `BlocksProps` for block wrapper |
| `inner_element` | Inner HTML element when using `BlocksProps` |
| `blockProps` | Block properties (className, etc.) |
| `content` | Block content with `[%field_name%]` placeholders |
| `title` | Element title/label |
| `class` | CSS class names |

### Template Variables

Use `[%field_name%]` in `content` or `class` to inject field values:

```php
'content' => 'Hello [%name%], you selected [%option%]'
```

## Widget Operations

```php
'widget_ops' => array(
    'classname'   => 'my-widget-class',
    'description' => __('Widget description', 'textdomain'),
)
```

## Complete Example

```php
class My_Super_Widget extends WP_Super_Duper {
    public function __construct() {
        $options = array(
            'textdomain'     => 'my-plugin',
            'block-icon'     => 'dashicons-admin-site',
            'block-category' => 'widgets',
            'block-keywords' => "['example','demo']",
            'block-output'   => array(
                array(
                    'element' => 'div',
                    'class'   => 'my-widget [%size%]',
                    'content' => '<h3>[%title%]</h3><p>[%content%]</p>',
                ),
            ),
            'class_name'     => __CLASS__,
            'base_id'        => 'my_widget',
            'name'           => __('My Widget', 'my-plugin'),
            'widget_ops'     => array(
                'classname'   => 'my-widget',
                'description' => __('Example widget', 'my-plugin'),
            ),
            'arguments'      => array(
                'title' => array(
                    'title'   => __('Title', 'my-plugin'),
                    'type'    => 'text',
                    'default' => 'Default Title',
                ),
                'size' => array(
                    'title'   => __('Size', 'my-plugin'),
                    'type'    => 'select',
                    'options' => array(
                        'small'  => 'Small',
                        'medium' => 'Medium',
                        'large'  => 'Large',
                    ),
                    'default' => 'medium',
                ),
                'content' => array(
                    'title' => __('Content', 'my-plugin'),
                    'type'  => 'textarea',
                    'rows'  => 5,
                ),
            ),
        );

        parent::__construct($options);
    }

    public function output($args = array(), $widget_args = array(), $content = '') {
        extract($args, EXTR_SKIP);

        return '<div class="my-widget ' . esc_attr($size) . '">'
            . '<h3>' . esc_html($title) . '</h3>'
            . '<p>' . esc_html($content) . '</p>'
            . '</div>';
    }
}
```

## See Also

- [Dependent Fields](features/dependent-fields.md)
- [Examples](examples.md)
- [hello-world.php](../hello-world.php)
