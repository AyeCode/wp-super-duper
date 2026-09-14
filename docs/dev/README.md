# WP Super Duper Documentation

A WordPress Class to build a widget, shortcode and Gutenberg block at once.

## Table of Contents

- [Block Building Reference](block-building.md) — the complete reference: registration, the standard tab/field structure, fields, output modes, assets
- [Builder Pattern (BlockArguments)](builder-pattern.md) — the fluent field builder API
- [Fields](fields.md) — every `Fields\*` static class and field type
- [API Reference](api-reference.md) — constructor options and field arguments
- [Examples](examples.md) — working block classes, start to finish
- [REST API](rest-api.md) — block discovery endpoints
- [Features](features/)
  - [Block Output Formats](features/block-output-formats.md)
  - [Dependent Fields](features/dependent-fields.md)

## Quick Start

See [hello-world.php](../../hello-world.php) in the root directory for a basic working example.

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

## Registration

A class does not register itself. Every block is registered with `ayecode_sd_register()`, which
stores the registration without loading the class — nothing is instantiated until a block,
shortcode or widget is actually rendered:

```php
add_action( 'widgets_init', function () {
    ayecode_sd_register( 'my_block', 'My_Block', [ 'block', 'shortcode' ] );
} );
```

`output_types` defaults to `[ 'block', 'shortcode' ]`. Adding `'widget'` is the only thing that
forces the class to be built on every page load, so leave it out unless the block belongs in a
sidebar. See [Block Building](block-building.md) for the full contract.

## Assets

Frontend scripts and styles belong in an `enqueue_scripts()` override, which the framework calls
only once the block has actually rendered. Never enqueue — or hook `wp_enqueue_scripts` — from
`__construct()`.
