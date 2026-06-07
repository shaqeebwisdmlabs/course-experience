<?php
/**
 * Plugin Name:       Edwiser Bridge - Course Experience
 * Plugin URI:        https://edwiser.org/bridge-wordpress-moodle-integration/
 * Description:       An Edwiser Bridge extension that provides a seamless course viewing experience within WordPress by integrating with Moodle.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            WisdmLabs
 * Author URI:        https://edwiser.org/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       eb-course-exp
 * Domain Path:       /languages
 *
 * @package EB_Course_Experience
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Current plugin version.
 */
define( 'COURSEEXP_VERSION', '1.0.0' );

/**
 * URL slug for the course experience endpoint.
 *
 * Change this in one place to rebrand every course/section URL. Use only
 * URL-safe characters ([a-z0-9-]); the router re-flushes rewrite rules
 * automatically whenever this value changes.
 */
define( 'COURSEEXP_SLUG', 'eb-course-experience' );

/**
 * Plugin base name.
 */
define( 'COURSEEXP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Plugin directory path.
 */
define( 'COURSEEXP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'COURSEEXP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Activate the plugin.
 *
 * @return void
 */
function courseexp_activate(): void {
	flush_rewrite_rules();
}

/**
 * Deactivate the plugin.
 *
 * @return void
 */
function courseexp_deactivate(): void {
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'courseexp_activate' );
register_deactivation_hook( __FILE__, 'courseexp_deactivate' );

/**
 * Initialize the plugin.
 *
 * @return void
 */
function courseexp_init(): void {
	load_plugin_textdomain( 'eb-course-exp', false, dirname( COURSEEXP_PLUGIN_BASENAME ) . '/languages' );

	require_once COURSEEXP_PLUGIN_DIR . 'includes/class-courseexp-core.php';

	$core = new CourseExp_Core();
	$core->init();
}
add_action( 'plugins_loaded', 'courseexp_init' );
