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
		$this->includes();

		$router = new CourseExp_Course_Router();
		$router->init();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
	}

	/**
	 * Include required files
	 *
	 * @return void
	 */
	private function includes(): void {
		require_once COURSEEXP_PLUGIN_DIR . 'includes/class-courseexp-course-router.php';
		require_once COURSEEXP_PLUGIN_DIR . 'includes/class-courseexp-api-client.php';
		require_once COURSEEXP_PLUGIN_DIR . 'templates/parts/helpers.php';
	}

	/**
	 * Enqueue public assets.
	 *
	 * @return void
	 */
	public function enqueue_public_assets(): void {
		if ( $this->is_courseexp_context() ) {
			wp_enqueue_style( 'courseexp-public', COURSEEXP_PLUGIN_URL . 'assets/css/public.css', array(), COURSEEXP_VERSION );
			wp_enqueue_style( 'courseexp-sidebar', COURSEEXP_PLUGIN_URL . 'assets/css/sidebar.css', array(), COURSEEXP_VERSION );
			wp_enqueue_style( 'courseexp-course-header', COURSEEXP_PLUGIN_URL . 'assets/css/course-header.css', array(), COURSEEXP_VERSION );
			wp_enqueue_style( 'courseexp-sections', COURSEEXP_PLUGIN_URL . 'assets/css/sections.css', array(), COURSEEXP_VERSION );
			wp_enqueue_style( 'courseexp-activity', COURSEEXP_PLUGIN_URL . 'assets/css/activity.css', array(), COURSEEXP_VERSION );
			wp_enqueue_script( 'courseexp-sidebar', COURSEEXP_PLUGIN_URL . 'assets/js/sidebar.js', array(), COURSEEXP_VERSION, true );
			wp_enqueue_script( 'courseexp-sections', COURSEEXP_PLUGIN_URL . 'assets/js/sections.js', array(), COURSEEXP_VERSION, true );
			wp_enqueue_script( 'courseexp-activity', COURSEEXP_PLUGIN_URL . 'assets/js/activity.js', array(), COURSEEXP_VERSION, true );
		}
	}

	/**
	 * Add the page-scope body class on course experience and section pages.
	 *
	 * @param array $classes Existing body classes.
	 * @return array
	 */
	public function add_body_class( array $classes ): array {
		if ( $this->is_courseexp_context() ) {
			$classes[] = 'courseexp-page';
		}

		return $classes;
	}

	/**
	 * Whether the current request is a course experience or section page.
	 *
	 * The eb_course_exp query var is set by the COURSEEXP_SLUG rewrite rules
	 * for both the course page and a single-section page.
	 *
	 * @return bool
	 */
	private function is_courseexp_context(): bool {
		return (bool) get_query_var( 'eb_course_exp' );
	}
}
