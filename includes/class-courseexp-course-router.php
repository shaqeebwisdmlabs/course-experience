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
	 * Lazily-created Moodle API client.
	 *
	 * @var CourseExp_API_Client|null
	 */
	private $api_client = null;

	/**
	 * Map of Moodle course id => can_teach (bool), fetched once per request.
	 *
	 * Null until first lookup so an empty result is not refetched.
	 *
	 * @var array<int,bool>|null
	 */
	private $teach_map = null;

	/**
	 * Instructor-capable cards collected during card rendering.
	 *
	 * Keyed by the card DOM id ("post-{ID}") with student/instructor URLs, handed
	 * to the front-end so the dual CTAs are injected only where the user teaches.
	 *
	 * @var array<string,array{student_url:string,instructor_url:string}>
	 */
	private $instructor_cards = array();

	/**
	 * Initialize the router
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'eb_content_course_before', array( $this, 'modify_my_courses_url' ), 20, 3 );
		add_filter( 'template_include', array( $this, 'load_course_experience_template' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_my_courses_assets' ) );
		add_action( 'wp_footer', array( $this, 'localize_instructor_cards' ), 5 );
	}

	/**
	 * Add rewrite rules for course experience endpoint
	 *
	 * @return void
	 */
	public function add_rewrite_rules(): void {
		$rules = array(
			COURSEEXP_SLUG . '/([^/]+)/activity/([^/]+)/?$' => 'index.php?eb_course_exp=1&course_slug=$matches[1]&course_activity=$matches[2]',
			COURSEEXP_SLUG . '/([^/]+)/([^/]+)/?$' => 'index.php?eb_course_exp=1&course_slug=$matches[1]&course_section=$matches[2]',
			COURSEEXP_SLUG . '/([^/]+)/?$'         => 'index.php?eb_course_exp=1&course_slug=$matches[1]',
		);

		foreach ( $rules as $regex => $redirect ) {
			add_rewrite_rule( $regex, $redirect, 'top' );
		}

		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );

		$rewrite_signature = COURSEEXP_VERSION . ':' . md5( (string) wp_json_encode( $rules ) );
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
		$vars[] = 'course_section';
		$vars[] = 'course_activity';
		$vars[] = 'courseexp_chapter';
		return $vars;
	}

	/**
	 * Modify course card URLs on the My Courses page.
	 *
	 * Every card's link is pointed at the in-WordPress course experience (the
	 * student view). For users who teach a course, the original Moodle link
	 * (Edwiser's autologin URL, already set on course_url at this point) is
	 * captured so the front-end can render a second "View as Instructor" CTA.
	 *
	 * @param array $course_data      Course data array.
	 * @param array $attr             Shortcode attributes.
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

		$instructor_url            = isset( $course_data['course_url'] ) ? (string) $course_data['course_url'] : '';
		$student_url               = home_url( '/' . COURSEEXP_SLUG . '/' . $post->post_name . '/' );
		$course_data['course_url'] = $student_url;

		$moodle_course_id = $this->api_client()->get_moodle_course_id( (int) $post->ID );
		$teach_map        = $this->get_teach_map();

		if ( '' !== $instructor_url && $moodle_course_id > 0 && ! empty( $teach_map[ $moodle_course_id ] ) ) {
			$this->instructor_cards[ 'post-' . (int) $post->ID ] = array(
				'student_url'    => esc_url_raw( $student_url ),
				'instructor_url' => esc_url_raw( $instructor_url ),
			);
		}

		return $course_data;
	}

	/**
	 * Enqueue My Courses assets (dual-CTA styling and injector).
	 *
	 * The stylesheet hides the default progress block for all users; the script
	 * injects the dual CTAs and is fed per-card data via localize_instructor_cards().
	 *
	 * @return void
	 */
	public function enqueue_my_courses_assets(): void {
		if ( ! courseexp_is_my_courses_page() ) {
			return;
		}

		wp_enqueue_style(
			'courseexp-my-courses',
			COURSEEXP_PLUGIN_URL . 'assets/css/my-courses.css',
			array(),
			COURSEEXP_VERSION
		);
		wp_enqueue_script(
			'courseexp-my-courses',
			COURSEEXP_PLUGIN_URL . 'assets/js/my-courses.js',
			array(),
			COURSEEXP_VERSION,
			true
		);
	}

	/**
	 * Pass the collected instructor-card data to the front-end script.
	 *
	 * Runs late (footer) because cards are only known after the My Courses loop
	 * has rendered, but before the footer scripts are printed.
	 *
	 * @return void
	 */
	public function localize_instructor_cards(): void {
		if ( empty( $this->instructor_cards ) || ! wp_script_is( 'courseexp-my-courses', 'enqueued' ) ) {
			return;
		}

		wp_localize_script(
			'courseexp-my-courses',
			'courseexpMyCourses',
			array(
				'cards' => $this->instructor_cards,
				'i18n'  => array(
					'student'    => __( 'View as Student', 'eb-course-exp' ),
					'instructor' => __( 'View as Instructor', 'eb-course-exp' ),
				),
			)
		);
	}

	/**
	 * Build a map of Moodle course id => whether the current user can teach it.
	 *
	 * Fetched once per request from a single get_user_progress call covering all
	 * the user's enrolled courses. Returns an empty map when Edwiser Bridge is
	 * unavailable, the user is unmapped, or the API errors.
	 *
	 * @return array<int,bool>
	 */
	private function get_teach_map(): array {
		if ( null !== $this->teach_map ) {
			return $this->teach_map;
		}

		$this->teach_map = array();

		$api            = $this->api_client();
		$moodle_user_id = $api->get_current_moodle_user_id();
		if ( $moodle_user_id <= 0 ) {
			return $this->teach_map;
		}

		$progress = $api->get_user_progress( $moodle_user_id );
		if ( is_wp_error( $progress ) ) {
			return $this->teach_map;
		}

		$progress = json_decode( wp_json_encode( $progress ), true );
		$courses  = is_array( $progress ) && isset( $progress['courses'] ) ? (array) $progress['courses'] : array();

		foreach ( $courses as $course ) {
			$course = (array) $course;
			if ( isset( $course['courseid'] ) ) {
				$this->teach_map[ (int) $course['courseid'] ] = ! empty( $course['can_teach'] );
			}
		}

		return $this->teach_map;
	}

	/**
	 * Lazily instantiate the Moodle API client.
	 *
	 * @return CourseExp_API_Client
	 */
	private function api_client(): CourseExp_API_Client {
		if ( null === $this->api_client ) {
			$this->api_client = new CourseExp_API_Client();
		}

		return $this->api_client;
	}

	/**
	 * Load custom template for course experience page
	 *
	 * @param string $template Current template path.
	 * @return string
	 */
	public function load_course_experience_template( string $template ): string {
		if ( get_query_var( 'eb_course_exp' ) ) {
			if ( '' !== (string) get_query_var( 'course_activity' ) ) {
				$template_file = 'templates/activity-experience.php';
			} elseif ( '' !== (string) get_query_var( 'course_section' ) ) {
				$template_file = 'templates/section-experience.php';
			} else {
				$template_file = 'templates/course-experience.php';
			}

			$custom_template = COURSEEXP_PLUGIN_DIR . $template_file;
			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}
		return $template;
	}
}
