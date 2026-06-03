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
		add_action( 'wp_ajax_courseexp_load_activity', array( $this, 'ajax_load_activity' ) );
	}

	/**
	 * Include required files
	 *
	 * @return void
	 */
	private function includes(): void {
		require_once COURSEEXP_PLUGIN_DIR . 'includes/class-course-router.php';
		require_once COURSEEXP_PLUGIN_DIR . 'includes/class-api-client.php';
	}

	/**
	 * Enqueue public assets.
	 *
	 * @return void
	 */
	public function enqueue_public_assets(): void {
		if ( get_query_var( 'eb_course_exp' ) || $this->is_my_courses_page() ) {
			wp_enqueue_style( 'courseexp-public', COURSEEXP_PLUGIN_URL . 'assets/css/public.css', array(), COURSEEXP_VERSION );
			wp_enqueue_style( 'courseexp-sidebar', COURSEEXP_PLUGIN_URL . 'assets/css/sidebar.css', array(), COURSEEXP_VERSION );
			wp_enqueue_script( 'courseexp-sidebar', COURSEEXP_PLUGIN_URL . 'assets/js/sidebar.js', array(), COURSEEXP_VERSION, true );
		}
	}

	/**
	 * Check if current page is My Courses page
	 *
	 * @return bool
	 */
	private function is_my_courses_page(): bool {
		$post = get_post();
		if ( $post && has_shortcode( $post->post_content, 'eb_my_courses' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * AJAX handler for loading activity content.
	 *
	 * @return void
	 */
	public function ajax_load_activity(): void {
		check_ajax_referer( 'courseexp_nonce', 'nonce' );

		wp_send_json_success( array( 'message' => __( 'Activity loaded', 'eb-course-exp' ) ) );
	}
}
