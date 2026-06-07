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
