# Visibility Conditions

## Overview

Every block built with `add_visibility_conditions()` gets a **Set Block Visibility** button in its Advanced tab. The modal it opens lets a site builder add one or more rules, ANDed together, and choose what happens when they are met and when they are not: show the block, hide it, show a message, a page's content or a template part.

The rules are stored as JSON in the block's `visibility_conditions` attribute and evaluated on the front end by `AyeCode\SuperDuper\Utils::render_block()` on the `render_block` filter.

```json
{
  "rule1": { "type": "logged_in" },
  "rule2": { "type": "plugin_active", "condition": "is_active", "plugin": "geodirectory/geodirectory.php" },
  "outputN": { "type": "hide" }
}
```

`output` is the branch taken when every rule passes, `outputN` when any rule fails. A branch set to show the block is omitted from the JSON.

## Built-in rules

| Type | Extra keys | Passes when |
|---|---|---|
| `logged_in` | | The visitor is logged in |
| `logged_out` | | The visitor is not logged in |
| `post_author` | | The visitor is the author of the current post (never on pages) |
| `user_roles` | `user_roles` (comma separated) | The visitor has one of the roles, or is logged out and `logged_out` is in the list |
| `plugin_active` | `condition`, then `plugin` or `constant` | See below |
| `gd_field` | `field`, `condition`, `search` | A GeoDirectory field on the current listing matches. Only offered when GeoDirectory is active |

### Plugin Active

The modal lists every installed plugin in a searchable box, inactive ones marked as such, and ends with a **Custom constant...** entry. The rule stores one of:

- `plugin`: the plugin file as `get_plugins()` keys it, for example `geodirectory/geodirectory.php`. Must-use plugins are stored as `mu-plugins/file.php` and count as active whenever the file exists. The check reads the active plugins option directly, so it costs nothing on the front end and needs no admin includes.
- `constant`: a PHP constant name. The rule passes when `defined()` is true. The value is never read, so the rule cannot be used to probe secrets, and the name is reduced to letters, digits, underscores and namespace backslashes before it is checked.

`condition` is `is_active` or `is_not_active`. The negative form exists because rules are ANDed; "logged in and plugin X not active" cannot be expressed through the outputN branch alone.

The constant form is the escape hatch for anything the plugin list cannot see: a composer-bundled package, code in a theme, or a feature flag. Prefer the plugin form when a plugin exists, since the file path is the same on every site and the picker can show a friendly name for it.

A rule saved for a plugin that is not installed on the current site still works, evaluating as not active, and the picker shows its raw path so it survives a resave.

## Adding a rule type from another plugin

Two filters make the rule list extensible:

```php
add_filter( 'sd_visibility_rules_options', function ( $options ) {
    $options['has_subscription'] = __( 'Has Subscription', 'my-textdomain' );
    return $options;
} );

add_filter( 'sd_block_check_custom_rule', function ( $match, $rule ) {
    if ( 'has_subscription' === $rule['type'] ) {
        return my_plugin_user_has_subscription();
    }
    return $match;
}, 10, 2 );
```

The first filter puts the rule in the modal's dropdown and in the list `render_block()` validates saved rules against. The second evaluates it; it only runs for types the built-in switch does not handle. A rule added this way has no extra fields, so it saves as `{"type":"has_subscription"}`.

### When the contributing plugin is deactivated

A saved rule whose type is no longer in the filtered list is skipped during evaluation, not treated as failed. If no rules remain the block renders as though it had none. The rule stays in the post content and the modal keeps it selectable under its raw key, so it comes back as soon as the plugin is reactivated.

This is why "is plugin X active" is a core rule rather than something each addon contributes: a core rule is always evaluated, so deactivating the plugin sends the block down the outputN branch exactly as the author set it up.

## Other filters

| Filter | Purpose |
|---|---|
| `sd_block_parse_rules( $rules, $attrs )` | Adjust the flat list of rules parsed from the attribute |
| `sd_block_check_rule( $match, $rule )` | Runs for every rule; the built-in evaluator is attached here |
| `sd_block_check_rules( $match, $rules )` | Override the combined result |
| `sd_render_block_visibility_content( $content, $original, $attrs, $block, $instance, $matched )` | Change the final output |
