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
courseexp_render_body_class();

set_query_var( 'course_title', $course_title );
set_query_var( 'course_data', $course_data );
load_template( COURSEEXP_PLUGIN_DIR . 'templates/parts/sidebar.php' );
?>

<div class="courseexp-layout" data-course-id="<?php echo esc_attr( $moodle_course_id ); ?>">
	<main class="courseexp-main" id="courseexp-main">
		<?php load_template( COURSEEXP_PLUGIN_DIR . 'templates/parts/course-header.php', false ); ?>

		<div class="courseexp-main__content">
			<?php load_template( COURSEEXP_PLUGIN_DIR . 'templates/parts/sections.php', false ); ?>
		</div>
	</main>
</div>

<?php
get_footer();
