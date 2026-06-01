# WordPress Plugin Development Guidelines

## Code Organization

### File Structure
```
plugin-name/
├── plugin-name.php          # Main plugin file
├── includes/                # Core classes
│   ├── class-core.php       # Main initialization
│   └── class-*.php          # Feature classes
├── templates/               # Template files
├── assets/                  # CSS, JS, images
│   ├── css/
│   └── js/
├── languages/               # Translation files
└── guidelines.md            # This file
```

### Naming Conventions
- **Classes**: `PluginName_Class_Name` (prefix with unique plugin slug)
- **Functions**: `pluginname_function_name()`
- **Constants**: `PLUGINNAME_CONSTANT_NAME`
- **Text Domain**: `plugin-slug` (kebab-case, matches plugin folder)
- **Database Options**: `pluginname_option_name`
- **Query Vars**: `pluginname_varname`

## Coding Standards

### PHP
- Follow WordPress PHP Coding Standards (WPCS)
- Use strict typing: `public function method(): void`
- Validate all inputs with `sanitize_*()` functions
- Escape all outputs with `esc_*()` functions
- Use prepared statements for database queries

### Hooks
```php
// Actions
add_action( 'init', array( $this, 'method_name' ), 10, 2 );

// Filters
add_filter( 'filter_name', array( $this, 'filter_method' ), 20, 3 );
```

Priority guidelines:
- `10` - Default
- `5-9` - Early execution
- `11-99` - Late execution
- `100+` - Override others

## Security (Non-Negotiable)

### 1. Input Validation
```php
$input = sanitize_text_field( $_POST['field'] );
$int   = absint( $_GET['id'] );
$email = sanitize_email( $_POST['email'] );
```

### 2. Output Escaping
```php
// HTML attributes
esc_attr( $var );

// HTML content
esc_html( $var );

// URLs
esc_url( $var );

// JavaScript
esc_js( $var );
```

### 3. Nonce Verification
```php
// Form
wp_nonce_field( 'action_name', 'nonce_field' );

// Verification
check_ajax_referer( 'action_name', 'nonce_field' );
check_admin_referer( 'action_name' );
```

### 4. Capability Checks
```php
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Unauthorized', 'text-domain' ) );
}
```

## Internationalization

### Text Domain
- Define in plugin header: `Text Domain: plugin-slug`
- Load in main file: `load_plugin_textdomain( 'plugin-slug', ... )`

### Translation Functions
```php
// Simple string
__( 'String', 'plugin-slug' );

// Echo
echo esc_html__( 'String', 'plugin-slug' );
_esc_html( 'String', 'plugin-slug' );

// With context
_x( 'String', 'context', 'plugin-slug' );

// Plural
_n( 'One', 'Many', $count, 'plugin-slug' );
```

### Generate POT File
```bash
wp i18n make-pot . languages/plugin-slug.pot --domain=plugin-slug
```

## Database

### Options API
```php
// Get with default
$value = get_option( 'pluginname_option', 'default' );

// Update
update_option( 'pluginname_option', $value );

// Delete on uninstall
delete_option( 'pluginname_option' );
```

### Post Meta
```php
// Get
$meta = get_post_meta( $post_id, 'pluginname_key', true );

// Update
update_post_meta( $post_id, 'pluginname_key', $value );
```

## Assets

### Enqueue Scripts
```php
add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

public function enqueue_assets(): void {
    wp_enqueue_style(
        'pluginname-handle',
        PLUGIN_URL . 'assets/css/style.css',
        array(),
        PLUGIN_VERSION
    );
    
    wp_enqueue_script(
        'pluginname-handle',
        PLUGIN_URL . 'assets/js/script.js',
        array( 'jquery' ),
        PLUGIN_VERSION,
        true
    );
}
```

## Documentation

### File Headers
```php
<?php
/**
 * Short description of file
 *
 * @package Package_Name
 */
```

### Function DocBlocks
```php
/**
 * Short description
 *
 * @since 1.0.0
 * @param string $param Description
 * @return array Description
 */
```

## Git Workflow

### Commits
- Prefix: `feat:`, `fix:`, `refactor:`, `docs:`, `test:`
- Imperative mood: "Add feature" not "Added feature"
- Reference issues: `Fixes #123`

### Branches
- `main` - Production
- `develop` - Integration
- `feature/name` - Features
- `fix/name` - Bug fixes

## Testing Checklist

Before committing:
- [ ] No PHP syntax errors (`php -l file.php`)
- [ ] WordPress Coding Standards pass
- [ ] All strings are translatable
- [ ] All inputs sanitized
- [ ] All outputs escaped
- [ ] Nonce verification for forms/AJAX
- [ ] Capability checks for admin actions
- [ ] Works with WP_DEBUG enabled
- [ ] No console errors in browser

## Anti-Patterns (Never Do)

1. **Direct DB queries** without `$wpdb->prepare()`
2. **Echo unescaped** user input
3. **Trust user input** without validation
4. **Use `$_POST`/`$_GET`** directly without sanitization
5. **Global variables** without namespacing
6. **Inline CSS/JS** - always enqueue
7. **Hardcoded paths** - use `plugin_dir_path()`, `plugin_dir_url()`
8. **Mixed concerns** - separate logic from presentation

## Quick Reference

```php
// Plugin paths
define( 'PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PLUGIN_VERSION', '1.0.0' );

// Check if ABSPATH defined
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Activation hook
register_activation_hook( __FILE__, 'pluginname_activate' );

// Deactivation hook  
register_deactivation_hook( __FILE__, 'pluginname_deactivate' );

// Load text domain
load_plugin_textdomain( 'plugin-slug', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
```

## Environment Notes

- Always work in `/var/www/html/wp-content/plugins/plugin-name/`
- Run `wp rewrite flush` after changing rewrite rules
- Use `WP_DEBUG` and `WP_DEBUG_LOG` for development
- Test with Query Monitor plugin active
