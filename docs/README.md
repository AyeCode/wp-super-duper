# WP Super Duper Documentation

A WordPress Class to build a widget, shortcode and Gutenberg block at once.

## Table of Contents

- [API Reference](api-reference.md)
- [Builder Pattern (BlockArguments)](builder-pattern.md)
- [Examples](examples.md)
- [Features](features/)
  - [Dependent Fields](features/dependent-fields.md)

## Quick Start

See [hello-world.php](../hello-world.php) in the root directory for a basic working example.

## Overview

WP Super Duper allows you to define a single class that automatically generates:
- A WordPress Widget
- A Shortcode
- A Gutenberg Block

All three share the same arguments configuration, ensuring consistency across your WordPress site.

## Modern API (v3+)

Version 3 introduces the `BlockArguments` fluent builder as the modern alternative to the raw `arguments` array. Override `set_arguments()` in your class:

```php
public function set_arguments(): array {
    return ( new \AyeCode\SuperDuper\Builder\BlockArguments() )
        ->add_field( 'title', [
            'title'   => __( 'Title', 'ayecode-connect' ),
            'type'    => 'text',
            'default' => '',
        ] )
        ->add_margins()
        ->add_padding()
        ->add_visibility_conditions()
        ->add_class_and_anchor()
        ->get();
}
```

See [Builder Pattern](builder-pattern.md) for the full API reference.
