<?php
/**
 * Activity Experience Template
 *
 * Loaded for a single activity URL
 * (/{slug}/{course-slug}/activity/{cmid}/). Shows a breadcrumb
 * (Course / Section / Activity), the activity title and its body rendered by
 * rendermode, reusing the persistent sidebar.
 *
 * @package EB_Course_Experience
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_slug = (string) get_query_var( 'course_slug' );
$cmid        = (int) get_query_var( 'course_activity' );
$course_post = $course_slug ? get_page_by_path( $course_slug, OBJECT, 'eb_course' ) : null;
$api_client  = new CourseExp_API_Client();

$moodle_course_id = $course_post ? $api_client->get_moodle_course_id( $course_post->ID ) : 0;
$moodle_user_id   = $api_client->get_current_moodle_user_id();

$course_data = array();
if ( $moodle_course_id > 0 && $moodle_user_id > 0 ) {
	$course_data = $api_client->get_course_structure( $moodle_course_id, $moodle_user_id );
}

$course_title    = $course_post ? $course_post->post_title : '';
$course_url      = $course_slug ? home_url( '/' . COURSEEXP_SLUG . '/' . $course_slug . '/' ) : '';
$course_data_arr = is_object( $course_data ) ? json_decode( wp_json_encode( $course_data ), true ) : $course_data;

$found        = $api_client->find_activity( $course_data_arr, $cmid );
$activity     = $found['activity'];
$section_id   = (int) $found['section_id'];
$section_name = '' !== $found['section_name'] ? $found['section_name'] : __( 'Section', 'eb-course-exp' );
$section_url  = ( $course_slug && $section_id > 0 ) ? home_url( '/' . COURSEEXP_SLUG . '/' . $course_slug . '/' . $section_id . '/' ) : '';

$activity_name = isset( $activity['name'] ) && '' !== trim( (string) $activity['name'] )
	? $activity['name']
	: __( 'Activity', 'eb-course-exp' );

$activity_icon = isset( $activity['icon'] ) ? (string) $activity['icon'] : '';

$activity_available  = ! isset( $activity['available'] ) || (bool) $activity['available'];
$activity_completion = isset( $activity['completion'] ) ? (array) $activity['completion'] : array();

$completion_settings = ( is_array( $course_data_arr ) && isset( $course_data_arr['completion'] ) ) ? (array) $course_data_arr['completion'] : array();
$completion_ctx      = array(
	'enable_completion' => ! empty( $completion_settings['enablecompletion'] ),
	'show_conditions'   => ! empty( $completion_settings['showcompletionconditions'] ),
);
$show_completion     = $activity_available && courseexp_completion_is_visible( $activity_completion, $completion_ctx );

get_header();
courseexp_render_body_class();

set_query_var( 'course_title', $course_title );
set_query_var( 'course_data', $course_data );
set_query_var( 'courseexp_active_cmid', (string) $cmid );
load_template( COURSEEXP_PLUGIN_DIR . 'templates/parts/sidebar.php' );
?>

<div class="courseexp-layout" data-course-id="<?php echo esc_attr( $moodle_course_id ); ?>">
	<main class="courseexp-main" id="courseexp-main">
		<nav class="courseexp-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'eb-course-exp' ); ?>">
			<?php if ( $course_url ) : ?>
				<a class="courseexp-breadcrumb__link" href="<?php echo esc_url( $course_url ); ?>">
					<?php echo esc_html( $course_title ? $course_title : __( 'Course', 'eb-course-exp' ) ); ?>
				</a>
				<span class="courseexp-breadcrumb__sep" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
				</span>
			<?php endif; ?>
			<?php if ( $section_url ) : ?>
				<a class="courseexp-breadcrumb__link" href="<?php echo esc_url( $section_url ); ?>">
					<?php echo esc_html( $section_name ); ?>
				</a>
				<span class="courseexp-breadcrumb__sep" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
				</span>
			<?php endif; ?>
			<span class="courseexp-breadcrumb__current" aria-current="page">
				<?php echo esc_html( $activity_name ); ?>
			</span>
		</nav>

		<div class="courseexp-activity-header">
			<h1 class="courseexp-section-page__title courseexp-activity-title">
				<?php if ( '' !== $activity_icon ) : ?>
					<img class="courseexp-activity-title__icon" src="<?php echo esc_url( $activity_icon ); ?>" alt="" width="32" height="32" loading="lazy" />
				<?php endif; ?>
				<?php echo esc_html( $activity_name ); ?>
			</h1>
			<?php if ( $show_completion ) : ?>
				<div class="courseexp-activity-header__completion">
					<?php courseexp_render_completion_control( $activity_completion, $cmid, $completion_ctx ); ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="courseexp-main__content">
			<?php
			set_query_var( 'courseexp_activity', $activity );
			set_query_var( 'courseexp_cmid', $cmid );
			set_query_var( 'courseexp_moodle_user_id', $moodle_user_id );
			load_template( COURSEEXP_PLUGIN_DIR . 'templates/parts/activity.php', false );
			?>
		</div>
	</main>
</div>

<?php
get_footer();
