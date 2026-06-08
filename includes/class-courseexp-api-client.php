<?php
/**
 * API Client - Handles communication with Moodle APIs
 *
 * Uses Edwiser Bridge's connection helper to call Moodle web services.
 *
 * @package EB_Course_Experience
 */

/**
 * Class CourseExp_API_Client
 *
 * Connects to Moodle Content APIs via Edwiser Bridge connection helper
 */
class CourseExp_API_Client {

	/**
	 * Connection helper instance
	 *
	 * @var \app\wisdmlabs\edwiserBridge\Eb_Connection_Helper|null
	 */
	private $connection_helper;

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( function_exists( '\app\wisdmlabs\edwiserBridge\edwiser_bridge_instance' ) ) {
			$eb_instance             = \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance();
			$this->connection_helper = $eb_instance->connection_helper();
		}
	}

	/**
	 * Check if connection helper is available
	 *
	 * @return bool
	 */
	private function is_connected(): bool {
		return null !== $this->connection_helper;
	}

	/**
	 * Fetch course structure from Moodle API
	 *
	 * @param int $moodle_course_id Moodle course ID.
	 * @param int $moodle_user_id   Moodle user ID.
	 * @return array|\WP_Error API response or error.
	 */
	public function get_course_structure( int $moodle_course_id, int $moodle_user_id ) {
		if ( ! $this->is_connected() ) {
			return new \WP_Error( 'eb_not_available', __( 'Edwiser Bridge connection helper not available', 'eb-course-exp' ) );
		}

		$request_data = array(
			'courseid' => $moodle_course_id,
			'userid'   => $moodle_user_id,
		);

		$response = $this->connection_helper->connect_moodle_with_args_helper(
			'mod_courselink_get_course_structure',
			$request_data
		);

		if ( ! $response['success'] ) {
			return new \WP_Error( 'api_error', $response['response_message'] );
		}

		return $response['response_data'];
	}

	/**
	 * Fetch user course progress
	 *
	 * @param int   $moodle_user_id Moodle user ID.
	 * @param array $course_ids     Optional array of course IDs.
	 * @return array|\WP_Error API response or error.
	 */
	public function get_user_progress( int $moodle_user_id, array $course_ids = array() ) {
		if ( ! $this->is_connected() ) {
			return new \WP_Error( 'eb_not_available', __( 'Edwiser Bridge connection helper not available', 'eb-course-exp' ) );
		}

		$request_data = array(
			'userid'    => $moodle_user_id,
			'courseids' => $course_ids,
		);

		$response = $this->connection_helper->connect_moodle_with_args_helper(
			'mod_courselink_get_user_progress',
			$request_data
		);

		if ( ! $response['success'] ) {
			return new \WP_Error( 'api_error', $response['response_message'] );
		}

		return $response['response_data'];
	}

	/**
	 * Fetch activity content for direct-render types
	 *
	 * @param int $cmid           Course module ID.
	 * @param int $moodle_user_id Moodle user ID.
	 * @return array|\WP_Error API response or error.
	 */
	public function get_activity_content( int $cmid, int $moodle_user_id ) {
		if ( ! $this->is_connected() ) {
			return new \WP_Error( 'eb_not_available', __( 'Edwiser Bridge connection helper not available', 'eb-course-exp' ) );
		}

		$request_data = array(
			'cmid'   => $cmid,
			'userid' => $moodle_user_id,
		);

		$response = $this->connection_helper->connect_moodle_with_args_helper(
			'mod_courselink_get_activity_content',
			$request_data
		);

		if ( ! $response['success'] ) {
			return new \WP_Error( 'api_error', $response['response_message'] );
		}

		return $response['response_data'];
	}

	/**
	 * Locate an activity (and its containing section) within a course structure.
	 *
	 * Searches top-level activities and subsection children so an activity is
	 * found regardless of nesting depth.
	 *
	 * @param array|object|\WP_Error $course_data Course structure payload.
	 * @param int                    $cmid        Course module id to locate.
	 * @return array {
	 *     @type array  $activity     The matched activity block (empty if not found).
	 *     @type array  $section      The containing section block (empty if not found).
	 *     @type int    $section_id   The containing section id.
	 *     @type string $section_name The containing section name ('' when unnamed).
	 * }
	 */
	public function find_activity( $course_data, int $cmid ): array {
		$empty = array(
			'activity'     => array(),
			'section'      => array(),
			'section_id'   => 0,
			'section_name' => '',
		);

		if ( is_object( $course_data ) ) {
			$course_data = json_decode( wp_json_encode( $course_data ), true );
		}

		if ( ! is_array( $course_data ) || empty( $course_data['sections'] ) ) {
			return $empty;
		}

		foreach ( $course_data['sections'] as $index => $section ) {
			$section    = (array) $section;
			$activities = isset( $section['activities'] ) ? (array) $section['activities'] : array();
			$found      = $this->search_activities( $activities, $cmid );

			if ( ! empty( $found ) ) {
				$name = isset( $section['name'] ) && '' !== trim( (string) $section['name'] ) ? (string) $section['name'] : '';

				return array(
					'activity'     => $found,
					'section'      => $section,
					'section_id'   => isset( $section['id'] ) ? (int) $section['id'] : (int) $index,
					'section_name' => $name,
				);
			}
		}

		return $empty;
	}

	/**
	 * Recursively search an activity list (including subsection children) for a cmid.
	 *
	 * @param array $activities Activity blocks.
	 * @param int   $cmid       Course module id to locate.
	 * @return array Matched activity (array-cast) or an empty array.
	 */
	private function search_activities( array $activities, int $cmid ): array {
		foreach ( $activities as $activity ) {
			$activity = (array) $activity;

			if ( isset( $activity['cmid'] ) && (int) $activity['cmid'] === $cmid ) {
				return $activity;
			}

			if ( ! empty( $activity['children'] ) && is_array( $activity['children'] ) ) {
				$child = $this->search_activities( $activity['children'], $cmid );
				if ( ! empty( $child ) ) {
					return $child;
				}
			}
		}

		return array();
	}

	/**
	 * Append the Edwiser Bridge web-service token to a Moodle pluginfile URL.
	 *
	 * Moodle returns file URLs as webservice/pluginfile.php links that require the
	 * token as a query argument before the browser can fetch them.
	 *
	 * @param string $url Raw file URL from get_activity_content().
	 * @return string URL with the token appended, or the input unchanged when no token.
	 */
	public function append_file_token( string $url ): string {
		if ( '' === $url ) {
			return '';
		}

		$connection = get_option( 'eb_connection' );
		$token      = is_array( $connection ) && isset( $connection['eb_access_token'] ) ? (string) $connection['eb_access_token'] : '';

		if ( '' === $token ) {
			return $url;
		}

		return add_query_arg( 'token', $token, $url );
	}

	/**
	 * Get Moodle course ID from WordPress post
	 *
	 * @param int $post_id WordPress post ID.
	 * @return int Moodle course ID or 0.
	 */
	public function get_moodle_course_id( int $post_id ): int {
		$course_options = get_post_meta( $post_id, 'eb_course_options', true );

		if ( is_array( $course_options ) && isset( $course_options['moodle_course_id'] ) ) {
			return intval( $course_options['moodle_course_id'] );
		}

		return 0;
	}

	/**
	 * Get current user's Moodle ID
	 *
	 * @return int Moodle user ID or 0.
	 */
	public function get_current_moodle_user_id(): int {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return 0;
		}

		$moodle_user_id = get_user_meta( $user_id, 'moodle_user_id', true );

		return intval( $moodle_user_id );
	}
}
