<?php
/**
 * Core plugin class
 *
 * @package EB_Course_Experience
 */

/**
 * Main plugin class.
 *
 * @package EB_Course_Experience
 */
class CourseExp_Core {

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function init(): void {
		// Public hooks.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
		add_filter( 'template_include', array( $this, 'load_course_template' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_courseexp_load_activity', array( $this, 'ajax_load_activity' ) );
	}

	/**
	 * Enqueue public assets.
	 *
	 * @return void
	 */
	public function enqueue_public_assets(): void {
		if ( ! is_singular( 'eb_course' ) ) {
			return;
		}

		wp_enqueue_style( 'courseexp-public', COURSEEXP_PLUGIN_URL . 'assets/css/public.css', array(), COURSEEXP_VERSION );
		wp_enqueue_script( 'courseexp-public', COURSEEXP_PLUGIN_URL . 'assets/js/public.js', array( 'jquery' ), COURSEEXP_VERSION, true );
	}

	/**
	 * Load custom template for courses.
	 *
	 * @param string $template Current template.
	 * @return string
	 */
	public function load_course_template( string $template ): string {
		if ( is_singular( 'eb_course' ) ) {
			$custom_template = COURSEEXP_PLUGIN_DIR . 'templates/course-viewer.php';
			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}
		return $template;
	}

	/**
	 * AJAX handler for loading activity content.
	 *
	 * @return void
	 */
	public function ajax_load_activity(): void {
		check_ajax_referer( 'courseexp_nonce', 'nonce' );

		// Your AJAX handling code here.

		wp_send_json_success( array( 'message' => 'Activity loaded' ) );
	}
}
