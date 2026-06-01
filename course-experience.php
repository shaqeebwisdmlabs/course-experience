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

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Current plugin version.
 */
define( 'COURSEEXP_VERSION', '1.0.0' );

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
	// Activation code here.
	flush_rewrite_rules();
}

/**
 * Deactivate the plugin.
 *
 * @return void
 */
function courseexp_deactivate(): void {
	// Deactivation code here.
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
	// Load text domain for translations.
	load_plugin_textdomain( 'eb-course-exp', false, dirname( COURSEEXP_PLUGIN_BASENAME ) . '/languages' );

	// Include core files.
	require_once COURSEEXP_PLUGIN_DIR . 'includes/class-core.php';

	// Initialize the main class.
	$core = new CourseExp_Core();
	$core->init();
}
add_action( 'plugins_loaded', 'courseexp_init' );
