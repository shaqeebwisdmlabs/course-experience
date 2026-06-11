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
		add_action( 'admin_post_courseexp_set_completion', array( $this, 'handle_set_completion' ) );
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
		require_once COURSEEXP_PLUGIN_DIR . 'templates/parts/completion.php';
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
			wp_enqueue_script( 'courseexp-public', COURSEEXP_PLUGIN_URL . 'assets/js/public.js', array(), COURSEEXP_VERSION, true );
			wp_enqueue_script( 'courseexp-sidebar', COURSEEXP_PLUGIN_URL . 'assets/js/sidebar.js', array(), COURSEEXP_VERSION, true );
			wp_enqueue_script( 'courseexp-sections', COURSEEXP_PLUGIN_URL . 'assets/js/sections.js', array(), COURSEEXP_VERSION, true );
			wp_enqueue_script( 'courseexp-activity', COURSEEXP_PLUGIN_URL . 'assets/js/activity.js', array(), COURSEEXP_VERSION, true );
			wp_enqueue_script( 'courseexp-idiomorph', COURSEEXP_PLUGIN_URL . 'assets/js/vendor/idiomorph.min.js', array(), '0.7.3', true );
			wp_enqueue_script( 'courseexp-completion', COURSEEXP_PLUGIN_URL . 'assets/js/completion.js', array( 'courseexp-idiomorph' ), COURSEEXP_VERSION, true );
		}
	}

	/**
	 * Handle the manual "mark as done" form submission.
	 *
	 * Writes the completion then redirects back to the originating page (PRG), so
	 * it re-renders server-side with fresh data — completing an item can unlock
	 * restricted activities and move the progress bar with no client code.
	 *
	 * The Moodle user is resolved from the logged-in WordPress user, never the
	 * request, so a user can only ever toggle their own completion; Moodle in turn
	 * enforces enrolment and manual-completion capability for the cmid.
	 *
	 * @return void
	 */
	public function handle_set_completion(): void {
		$cmid = isset( $_POST['cmid'] ) ? absint( wp_unslash( $_POST['cmid'] ) ) : 0;

		check_admin_referer( 'courseexp_set_completion_' . $cmid );

		$state    = ( isset( $_POST['state'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['state'] ) ) ) ? 1 : 0;
		$redirect = wp_get_referer();
		$redirect = $redirect ? $redirect : home_url();

		if ( $cmid > 0 ) {
			$api_client     = new CourseExp_API_Client();
			$moodle_user_id = $api_client->get_current_moodle_user_id();

			if ( $moodle_user_id > 0 ) {
				$api_client->set_activity_completion( $cmid, $moodle_user_id, $state );
			}
		}

		wp_safe_redirect( $redirect . '#courseexp-activity-' . $cmid );
		exit;
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
