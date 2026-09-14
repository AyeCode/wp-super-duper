# Examples

## Basic Examples

### Hello World

See [hello-world.php](../../hello-world.php) for the most basic working example.

### Simple Text Widget

```php
class SD_Simple_Text extends WP_Super_Duper {

    public function __construct() {
        $options = array(
            'textdomain'     => 'my-plugin',
            'block-icon'     => 'dashicons-editor-alignleft',
            'block-category' => 'widgets',
            'block-keywords' => "['text','simple']",
            'block-output'   => array(
                array(
                    'element' => 'div',
                    'class'   => '[%className%]',
                    'content' => '[%text%]',
                ),
            ),
            'class_name'     => __CLASS__,
            'base_id'        => 'simple_text',
            'name'           => __('Simple Text', 'my-plugin'),
            'widget_ops'     => array(
                'classname'   => 'simple-text-widget',
                'description' => __('Display simple text', 'my-plugin'),
            ),
            'arguments'      => array(
                'text' => array(
                    'title'       => __('Text', 'my-plugin'),
                    'desc'        => __('Enter your text', 'my-plugin'),
                    'type'        => 'textarea',
                    'default'     => '',
                    'placeholder' => 'Enter text...',
                ),
            ),
        );

        parent::__construct($options);
    }

    public function output($args = array(), $widget_args = array(), $content = '') {
        extract($args, EXTR_SKIP);
        return '<div class="simple-text">' . wpautop($text) . '</div>';
    }
}

// Register — never instantiate the class yourself.
add_action( 'widgets_init', function () {
    ayecode_sd_register( 'simple_text', 'SD_Simple_Text', array( 'block', 'shortcode' ) );
} );
```

## Dependent Fields Examples

### Post Type → Category Selector

```php
class SD_Post_Selector extends WP_Super_Duper {

    public function __construct() {
        $options = array(
            'textdomain'     => 'my-plugin',
            'block-icon'     => 'dashicons-admin-post',
            'block-category' => 'widgets',
            'class_name'     => __CLASS__,
            'base_id'        => 'post_selector',
            'name'           => __('Post Selector', 'my-plugin'),
            'widget_ops'     => array(
                'classname'   => 'post-selector-widget',
                'description' => __('Select posts by type and category', 'my-plugin'),
            ),
            'arguments'      => array(
                'post_type' => array(
                    'title'   => __('Post Type', 'my-plugin'),
                    'type'    => 'select',
                    'options' => array(
                        'post'     => 'Posts',
                        'page'     => 'Pages',
                        'product'  => 'Products',
                    ),
                    'default' => 'post',
                ),
                'category' => array(
                    'title' => __('Category', 'my-plugin'),
                    'type'  => 'select',
                    'depends_on' => array(
                        'attribute'  => 'post_type',
                        'fetch_type' => 'rest',
                        'rest_path'  => '/wp/v2/{post_type}category/?per_page=100',
                        'default_option' => array(
                            'label' => __('All Categories', 'my-plugin'),
                            'value' => '0',
                        ),
                    ),
                ),
                'posts_per_page' => array(
                    'title'   => __('Number of Posts', 'my-plugin'),
                    'type'    => 'number',
                    'default' => 5,
                ),
            ),
        );

        parent::__construct($options);
    }

    public function output($args = array(), $widget_args = array(), $content = '') {
        extract($args, EXTR_SKIP);

        $query_args = array(
            'post_type'      => $post_type,
            'posts_per_page' => $posts_per_page,
        );

        if (!empty($category) && $category !== '0') {
            $query_args['cat'] = $category;
        }

        $query = new WP_Query($query_args);

        if (!$query->have_posts()) {
            return '<p>No posts found.</p>';
        }

        $output = '<ul class="post-list">';
        while ($query->have_posts()) {
            $query->the_post();
            $output .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
        }
        $output .= '</ul>';

        wp_reset_postdata();

        return $output;
    }
}

add_action( 'widgets_init', function () {
    ayecode_sd_register( 'post_selector', 'SD_Post_Selector', array( 'block', 'shortcode' ) );
} );
```

### Custom Taxonomy Dependent Fields

```php
'taxonomy' => array(
    'title'   => __('Taxonomy', 'my-plugin'),
    'type'    => 'select',
    'options' => array(
        'category'    => 'Categories',
        'post_tag'    => 'Tags',
        'custom_tax'  => 'Custom Taxonomy',
    ),
    'default' => 'category',
),
'term' => array(
    'title' => __('Term', 'my-plugin'),
    'type'  => 'select',
    'depends_on' => array(
        'attribute'  => 'taxonomy',
        'fetch_type' => 'rest',
        'rest_path'  => '/wp/v2/{taxonomy}?per_page=100',
        'cache_key'  => 'taxonomy_terms',
    ),
),
```

### Country → State/Province Selector

```php
'country' => array(
    'title'   => __('Country', 'my-plugin'),
    'type'    => 'select',
    'options' => array(
        'us' => 'United States',
        'ca' => 'Canada',
        'uk' => 'United Kingdom',
    ),
    'default' => 'us',
),
'state' => array(
    'title' => __('State/Province', 'my-plugin'),
    'type'  => 'select',
    'depends_on' => array(
        'attribute'  => 'country',
        'fetch_type' => 'rest',
        'rest_path'  => '/my-plugin/v1/states/{country}',
        'cache_key'  => 'country_states',
        'default_option' => array(
            'label' => __('Select State', 'my-plugin'),
            'value' => '',
        ),
    ),
),
```

## Advanced Examples

### Multiple Field Types

```php
'arguments' => array(
    'title' => array(
        'title'   => __('Title', 'my-plugin'),
        'type'    => 'text',
        'default' => 'Widget Title',
    ),
    'show_date' => array(
        'title'   => __('Show Date', 'my-plugin'),
        'type'    => 'checkbox',
        'default' => '1',
    ),
    'alignment' => array(
        'title'   => __('Alignment', 'my-plugin'),
        'type'    => 'select',
        'options' => array(
            'left'   => 'Left',
            'center' => 'Center',
            'right'  => 'Right',
        ),
        'default' => 'left',
    ),
    'items_count' => array(
        'title'       => __('Number of Items', 'my-plugin'),
        'type'        => 'number',
        'default'     => 5,
        'placeholder' => '5',
    ),
    'description' => array(
        'title' => __('Description', 'my-plugin'),
        'type'  => 'textarea',
        'rows'  => 3,
    ),
)
```

### Complex Block Output

```php
'block-output' => array(
    array(
        'element'       => 'BlocksProps',
        'inner_element' => 'div',
        'blockProps'    => array(
            'className' => '[%WrapClass%] alignment-[%alignment%]',
        ),
        'content' => '<div class="widget-header">'
                   . '<h3>[%title%]</h3>'
                   . '</div>'
                   . '<div class="widget-content">'
                   . '[%description%]'
                   . '</div>',
    ),
)
```

### Using Filters in Output

```php
public function output($args = array(), $widget_args = array(), $content = '') {
    extract($args, EXTR_SKIP);

    // Allow filtering the output
    $output = apply_filters('my_plugin_widget_output', '', $args);

    if (!empty($output)) {
        return $output;
    }

    // Default output
    $classes = array('my-widget');
    if (!empty($alignment)) {
        $classes[] = 'align-' . $alignment;
    }

    $output = '<div class="' . implode(' ', $classes) . '">';

    if (!empty($title)) {
        $output .= '<h3>' . esc_html($title) . '</h3>';
    }

    if ($show_date) {
        $output .= '<time>' . current_time('F j, Y') . '</time>';
    }

    if (!empty($description)) {
        $output .= wpautop($description);
    }

    $output .= '</div>';

    return $output;
}
```

## Integration Examples

### Registering Multiple Blocks

Put the list behind a filter so addons can extend it, and pass `$file_path` when the classes
are not autoloadable — the registry then requires the file only if the class is ever built.

```php
// In your plugin main file
add_action( 'widgets_init', function () {
    $blocks = apply_filters( 'my_plugin_get_blocks', array(
        array( 'widget_one',   'SD_Widget_One',   array( 'block', 'shortcode' ) ),
        array( 'widget_two',   'SD_Widget_Two',   array( 'block', 'shortcode' ) ),
        array( 'widget_three', 'SD_Widget_Three', array( 'block', 'shortcode', 'widget' ) ),
    ) );

    foreach ( $blocks as $block ) {
        list( $base_id, $class_name, $output_types ) = $block;

        ayecode_sd_register(
            $base_id,
            $class_name,
            $output_types,
            plugin_dir_path( __FILE__ ) . 'widgets/class-' . str_replace( '_', '-', $base_id ) . '.php'
        );
    }
} );
```

Nothing is loaded up front — no `require_once`, no `new`. See
[Block Building → Registration](block-building.md#registration).

### Disabling a Block

```php
add_filter( 'ayecode_sd_registered_blocks', function ( array $entries ): array {
    unset( $entries['widget_two'] );

    return $entries;
} );
```

## See Also

- [API Reference](api-reference.md)
- [Dependent Fields](features/dependent-fields.md)
- [hello-world.php](../../hello-world.php)
