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
│   └── parts/               # Template parts (feature-wise)
├── assets/                  # CSS, JS, images
│   ├── css/
│   └── js/
├── languages/               # Translation files
└── guidelines.md            # This file
```

### Feature-Wise File Organization
Organize files by feature, not by type. This keeps all related code together:

```
assets/
├── css/
│   ├── public.css           # Global styles only
│   ├── sidebar.css          # All sidebar styles
│   └── forms.css            # All form styles
├── js/
│   ├── public.js            # Global scripts only
│   ├── sidebar.js           # All sidebar functionality
│   └── forms.js             # All form functionality
templates/
├── course-experience.php    # Main template
└── parts/
    ├── sidebar.php          # All sidebar HTML
    └── form-login.php       # All login form HTML
```

Benefits:
- All related code is in one place
- Easier to maintain and debug
- Can work on a feature without touching multiple folders
- Clear separation of concerns

Never split a feature across multiple small files (e.g., sidebar-state.js, sidebar-toggle.js, sidebar-utils.js). Keep all related code in one file.

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

### JavaScript
- Wrap each feature file in an IIFE with `'use strict';`
- Toggle CSS classes for state and visibility — never set `element.style.*`
  inline. Use a utility class (e.g. `.courseexp-is-hidden`) to hide/show.
- Drive icon swaps and similar visual state from a single state class on the
  parent; let CSS handle which child shows
- Keep user-facing strings out of JS. Pass translatable text from the template
  via `data-*` attributes and read them with `element.dataset.*`
- Decouple modules with `CustomEvent` (e.g. `courseexp:activitySelected`) instead
  of direct cross-module calls
- Guard `localStorage` access in `try/catch`; degrade silently when unavailable
- Keep ARIA in sync with state (`aria-expanded`, `aria-pressed`, `hidden`)

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

### CSS Methodology

**Mobile-First Approach**
- Base styles (no media query) target mobile ≤768px
- Use `min-width` queries only for progressive enhancement
- Avoid `max-width` queries. The only allowed exceptions are the WordPress
  admin-bar breakpoints, used solely to adjust fixed-element top offsets:
  - `≤600px` — admin bar becomes non-fixed
  - `≤782px` — admin bar grows to 46px (nested inside a `min-width` block)
- Sidebar: off-screen by default on mobile, `.is-open` toggles it in
- Layout elements start with `margin-left: 0` on mobile

**BEM Naming & Low Specificity**
- Use BEM: `.courseexp-block`, `.courseexp-block__element`, `.courseexp-block--modifier`
- Prefer single-class selectors (0,1,0 specificity). Do **not** qualify a class
  with an element (`h2.courseexp-sidebar__title` → `.courseexp-sidebar__title`)
- Avoid IDs in CSS and `!important` outside utility classes
- Use `is-*` state classes (`.is-open`, `.is-expanded`, `.is-active`) for runtime
  state, toggled from JS

**Design Tokens (required)**
- Declare every color, radius, spacing and transition value as a `:root` custom
  property prefixed `--courseexp-*`. Rule bodies reference tokens — **no raw hex,
  radius or spacing literals outside `:root`**.
- Shared scale tokens:
  - `--courseexp-radius: 5px` (MedDiet's standard corner radius)
  - `--courseexp-space-sm`, `--courseexp-space-md` for padding/gaps
- Colors prefer the active theme's presets and fall back to the MedDiet palette,
  so the component matches MedDiet here but adapts to any theme:
  ```css
  --courseexp-primary-green: var(--wp--preset--color--primary, #7cc146);
  --courseexp-accent-orange: var(--wp--preset--color--secondary, #ea7813);
  --courseexp-sidebar-text:  var(--wp--preset--color--foreground, #333333);
  --courseexp-sidebar-border: var(--wp--preset--color--border, #e0e0e0);
  ```
- Text rendered over a brand color uses an on-accent token (`--courseexp-on-accent`)

**Accessibility**
- Use `:focus-visible` (not `:focus`) for keyboard outlines; outline color is the
  accent orange
- Wrap non-essential motion in `@media (prefers-reduced-motion: reduce)` (disable
  shimmer/transition animations)
- Manage focus for overlay UI: move focus into a drawer on open, restore it to the
  trigger on close

**MedDiet Theme Compatibility**
- Header selectors must always include `.site-navbar` alongside generic tags:
  ```css
  body.courseexp-page header,
  body.courseexp-page .site-header,
  body.courseexp-page #masthead,
  body.courseexp-page .site-navbar { ... }
  ```
- Footer selectors: `footer`, `.site-footer`, `.footer-section` (meddiet uses `.footer-section`)
- Do not modify files in `assets/meddiet-theme/`

## Documentation

### Comments Policy

Applies to PHP, JS and CSS alike. Use comments only when necessary — to explain
*why*, not *what*. Code should be self-explanatory through clear naming and
structure. In CSS, do not add group-label comments that merely restate token
names (e.g. `/* Skeleton loader */` above `--courseexp-skeleton-*`).

**Good comments:**
```php
// Calculate tax after discount is applied
$tax = ($price - $discount) * $tax_rate;

// WordPress admin bar is 32px high, adjust accordingly
$top_offset = is_admin_bar_showing() ? 32 : 0;
```

**Bad comments (decorative):**
```php
// ==========================================================================
// Sidebar Component
// ==========================================================================

/**
 * Initialize the sidebar
 */
function initSidebar() { ... }
```

Never use:
- Section dividers with `===` or `---`
- Decorative boxes or ASCII art
- Obvious comments that repeat the code
- Comments that state what is already clear from the function/variable name

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
- [ ] CSS uses `:root` tokens (no raw hex/radius/spacing in rule bodies)
- [ ] No inline styles in markup or set via JS (`element.style.*`)
- [ ] Keyboard focus visible (`:focus-visible`) and `prefers-reduced-motion` respected

## Anti-Patterns (Never Do)

1. **Direct DB queries** without `$wpdb->prepare()`
2. **Echo unescaped** user input
3. **Trust user input** without validation
4. **Use `$_POST`/`$_GET`** directly without sanitization
5. **Global variables** without namespacing
6. **Inline CSS/JS** - always enqueue
7. **Inline styles via JS** (`element.style.x = ...`) - toggle a CSS class instead
8. **Raw color/radius/spacing values in CSS rule bodies** - reference `:root` tokens
9. **Element-qualified or ID selectors in CSS** - use single BEM classes
10. **User-facing strings hardcoded in JS** - pass via `data-*` from the template
11. **Hardcoded paths** - use `plugin_dir_path()`, `plugin_dir_url()`
12. **Mixed concerns** - separate logic from presentation

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
