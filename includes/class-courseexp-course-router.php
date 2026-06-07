<?php
/**
 * Course Router - Handles URL rewriting and template loading
 *
 * @package EB_Course_Experience
 */

/**
 * Class CourseExp_Course_Router
 *
 * Handles custom endpoint for course experience and modifies My Courses card URLs
 */
class CourseExp_Course_Router {

	/**
	 * Initialize the router
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'eb_content_course_before', array( $this, 'modify_my_courses_url' ), 20, 3 );
		add_filter( 'template_include', array( $this, 'load_course_experience_template' ), 99 );
	}

	/**
	 * Add rewrite rules for course experience endpoint
	 *
	 * @return void
	 */
	public function add_rewrite_rules(): void {
		add_rewrite_rule(
			COURSEEXP_SLUG . '/([^/]+)/?$',
			'index.php?eb_course_exp=1&course_slug=$matches[1]',
			'top'
		);

		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );

		// Re-flush when either the version or the endpoint slug changes.
		$rewrite_signature = COURSEEXP_VERSION . ':' . COURSEEXP_SLUG;
		if ( get_option( 'courseexp_flush_rewrite_rules' ) !== $rewrite_signature ) {
			flush_rewrite_rules();
			update_option( 'courseexp_flush_rewrite_rules', $rewrite_signature );
		}
	}

	/**
	 * Register custom query variables
	 *
	 * @param array $vars Existing query vars.
	 * @return array
	 */
	public function register_query_vars( array $vars ): array {
		$vars[] = 'eb_course_exp';
		$vars[] = 'course_slug';
		return $vars;
	}

	/**
	 * Modify course URL for My Courses page only
	 *
	 * @param array $course_data Course data array.
	 * @param array $attr        Shortcode attributes.
	 * @param bool  $is_eb_my_courses Whether this is the My Courses page.
	 * @return array Modified course data.
	 */
	public function modify_my_courses_url( array $course_data, array $attr, bool $is_eb_my_courses ): array {
		if ( ! $is_eb_my_courses ) {
			return $course_data;
		}

		global $post;
		if ( ! $post ) {
			return $course_data;
		}

		$course_data['course_url'] = home_url( '/' . COURSEEXP_SLUG . '/' . $post->post_name . '/' );

		return $course_data;
	}

	/**
	 * Load custom template for course experience page
	 *
	 * @param string $template Current template path.
	 * @return string
	 */
	public function load_course_experience_template( string $template ): string {
		if ( get_query_var( 'eb_course_exp' ) ) {
			$custom_template = COURSEEXP_PLUGIN_DIR . 'templates/course-experience.php';
			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}
		return $template;
	}
}
