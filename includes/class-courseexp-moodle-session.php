<?php
/**
 * Background Moodle session warming
 *
 * @package EB_Course_Experience
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Silently establishes a Moodle session for the logged-in user while they are on
 * the Edwiser Bridge My Courses page.
 *
 * Course content renders inside WordPress via web-service calls, so the user's
 * browser never gets a MoodleSession cookie until they open something that lives
 * on Moodle (quiz, assignment, file viewer). When the short-lived Moodle session
 * has expired those links bounce to the Moodle login screen.
 *
 * To avoid that, a background request is fired from the My Courses page to
 * Edwiser Bridge Pro's existing SSO login flow. WordPress and Moodle share an
 * origin here (e.g. example.com and example.com/moodle), so the cookie set by
 * that request is first-party and persists for the next click into Moodle.
 *
 * @package EB_Course_Experience
 */
class CourseExp_Moodle_Session {

	/**
	 * Fully-qualified Edwiser Bridge Pro SSO login class.
	 */
	private const SSO_LOGIN_CLASS = 'app\wisdmlabs\edwiserBridgePro\includes\sso\Sso_Manage_Moodle_Login';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_courseexp_warm_moodle_session', array( $this, 'warm_session' ) );
	}

	/**
	 * Enqueue the session-warming script on the My Courses page.
	 *
	 * Only loads for logged-in users mapped to Moodle when the SSO module is
	 * active, so the request is never fired when it could not succeed.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! $this->is_warming_context() || ! $this->can_warm_current_user() ) {
			return;
		}

		wp_enqueue_script(
			'courseexp-moodle-session',
			COURSEEXP_PLUGIN_URL . 'assets/js/moodle-session.js',
			array(),
			COURSEEXP_VERSION,
			true
		);

		wp_localize_script(
			'courseexp-moodle-session',
			'courseexpMoodleSession',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'courseexp_warm_moodle_session' ),
			)
		);
	}

	/**
	 * Prime and establish the user's Moodle session.
	 *
	 * Delegates to Edwiser Bridge Pro's SSO login, which POSTs the encrypted
	 * one-time login data to Moodle and then redirects to the verification URL
	 * that sets the MoodleSession cookie. The browser's background fetch follows
	 * that redirect, so the cookie lands without leaving the My Courses page.
	 *
	 * @return void
	 */
	public function warm_session(): void {
		check_ajax_referer( 'courseexp_warm_moodle_session', 'nonce' );

		if ( ! $this->can_warm_current_user() ) {
			wp_die( '', '', array( 'response' => 204 ) );
		}

		$user = wp_get_current_user();

		$sso = new \app\wisdmlabs\edwiserBridgePro\includes\sso\Sso_Manage_Moodle_Login( 'edwiserbridge', COURSEEXP_VERSION );
		$sso->mdl_logged_in( $user->user_login, $user );
	}

	/**
	 * Whether the current request is a page where the user is about to follow a
	 * link into Moodle: the Edwiser Bridge My Courses page, or any course
	 * experience / section template page.
	 *
	 * @return bool
	 */
	private function is_warming_context(): bool {
		if ( get_query_var( 'eb_course_exp' ) ) {
			return true;
		}

		return courseexp_is_my_courses_page();
	}

	/**
	 * Whether the current user can be logged into Moodle via Edwiser Bridge SSO.
	 *
	 * @return bool
	 */
	private function can_warm_current_user(): bool {
		if ( ! is_user_logged_in() || ! $this->is_sso_available() ) {
			return false;
		}

		return '' !== (string) get_user_meta( get_current_user_id(), 'moodle_user_id', true );
	}

	/**
	 * Whether Edwiser Bridge Pro's SSO module is present and active.
	 *
	 * @return bool
	 */
	private function is_sso_available(): bool {
		if ( ! class_exists( self::SSO_LOGIN_CLASS ) ) {
			return false;
		}

		$modules_data = get_option( 'eb_pro_modules_data', array() );

		return isset( $modules_data['sso'] ) && 'active' === $modules_data['sso'];
	}
}
