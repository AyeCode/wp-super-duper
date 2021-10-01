# wp-super-duper
A WordPress Class to build a widget, shortcode and Gutenberg block at once.

# V1 to V2 migration
- Change how you load the widgets

```php
// SD V1 used to extend the widget class. V2 does not, so we cannot call register_widget() on it.
$widget = 'SD_Hello_World';
if ( is_subclass_of( $widget, 'WP_Widget' ) ) { // SD V1 is loaded.
	register_widget( $widget );
} else {
	new $widget(); // SD V2 is loaded.
}
```