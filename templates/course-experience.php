<?php
/**
 * Course Experience Template
 *
 * This template is loaded when user clicks a course card from My Courses page.
 *
 * @package EB_Course_Experience
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_slug = get_query_var( 'course_slug' );
$course_post = get_page_by_path( $course_slug, OBJECT, 'eb_course' );
$api_client  = new CourseExp_API_Client();

$moodle_course_id = 0;
if ( $course_post ) {
	$moodle_course_id = $api_client->get_moodle_course_id( $course_post->ID );
}

$moodle_user_id = $api_client->get_current_moodle_user_id();

$course_data = array();
if ( $moodle_course_id > 0 && $moodle_user_id > 0 ) {
	$course_data = $api_client->get_course_structure( $moodle_course_id, $moodle_user_id );
}

$progress_data = array();
if ( $moodle_course_id > 0 && $moodle_user_id > 0 ) {
	$progress_data = $api_client->get_user_progress( $moodle_user_id, array( $moodle_course_id ) );
}

$course_title = $course_post ? $course_post->post_title : '';

get_header();

set_query_var( 'course_title', $course_title );
set_query_var( 'course_data', $course_data );
load_template( COURSEEXP_PLUGIN_DIR . 'templates/parts/sidebar.php' );
?>

<div class="courseexp-layout">
	<main class="courseexp-main" id="courseexp-main">
		<div class="courseexp-main__header">
			<h1><?php echo $course_post ? esc_html( $course_post->post_title ) : esc_html__( 'Course Not Found', 'eb-course-exp' ); ?></h1>
		</div>

		<div class="courseexp-main__content">
			<div class="courseexp-debug-info">
				<h2><?php esc_html_e( 'Debug Information', 'eb-course-exp' ); ?></h2>
				<p><strong><?php esc_html_e( 'Course Slug:', 'eb-course-exp' ); ?></strong> <?php echo esc_html( $course_slug ); ?></p>
				<p><strong><?php esc_html_e( 'WordPress Post ID:', 'eb-course-exp' ); ?></strong> <?php echo $course_post ? esc_html( $course_post->ID ) : esc_html__( 'Not found', 'eb-course-exp' ); ?></p>
				<p><strong><?php esc_html_e( 'Moodle Course ID:', 'eb-course-exp' ); ?></strong> <?php echo esc_html( $moodle_course_id ); ?></p>
				<p><strong><?php esc_html_e( 'Moodle User ID:', 'eb-course-exp' ); ?></strong> <?php echo esc_html( $moodle_user_id ); ?></p>
			</div>

			<div class="courseexp-api-data">
				<h2><?php esc_html_e( 'Course Structure API Response', 'eb-course-exp' ); ?></h2>
				<?php
				if ( is_wp_error( $course_data ) ) {
					echo '<p class="courseexp-error">' . esc_html__( 'Error:', 'eb-course-exp' ) . ' ' . esc_html( $course_data->get_error_message() ) . '</p>';
				} else {
					echo '<pre>';
					var_dump( $course_data );
					echo '</pre>';
				}
				?>
			</div>

			<div class="courseexp-progress-data">
				<h2><?php esc_html_e( 'User Progress API Response', 'eb-course-exp' ); ?></h2>
				<?php
				if ( is_wp_error( $progress_data ) ) {
					echo '<p class="courseexp-error">' . esc_html__( 'Error:', 'eb-course-exp' ) . ' ' . esc_html( $progress_data->get_error_message() ) . '</p>';
				} else {
					echo '<pre>';
					var_dump( $progress_data );
					echo '</pre>';
				}
				?>
			</div>
		</div>
	</main>
</div>

<?php
get_footer();
