<?php
/**
 * Section Experience Template
 *
 * Loaded for a single course section URL
 * (/{slug}/{course-slug}/{section-id}/). Shows a breadcrumb and that one
 * section's activities, reusing the sidebar and the sections renderer in
 * detail mode.
 *
 * @package EB_Course_Experience
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_slug = (string) get_query_var( 'course_slug' );
$section_id  = (int) get_query_var( 'course_section' );
$course_post = $course_slug ? get_page_by_path( $course_slug, OBJECT, 'eb_course' ) : null;
$api_client  = new CourseExp_API_Client();

$moodle_course_id = $course_post ? $api_client->get_moodle_course_id( $course_post->ID ) : 0;
$moodle_user_id   = $api_client->get_current_moodle_user_id();

$course_data = array();
if ( $moodle_course_id > 0 && $moodle_user_id > 0 ) {
	$course_data = $api_client->get_course_structure( $moodle_course_id, $moodle_user_id );
}

$course_title = $course_post ? $course_post->post_title : '';
$course_url   = $course_slug ? home_url( '/' . COURSEEXP_SLUG . '/' . $course_slug . '/' ) : '';

$sections_data = is_object( $course_data ) ? json_decode( wp_json_encode( $course_data ), true ) : $course_data;

$section_name = '';
if ( is_array( $sections_data ) && isset( $sections_data['sections'] ) ) {
	foreach ( $sections_data['sections'] as $index => $section ) {
		$section = (array) $section;
		$sid     = isset( $section['id'] ) ? (int) $section['id'] : (int) $index;

		if ( $sid === $section_id ) {
			if ( isset( $section['name'] ) && '' !== trim( (string) $section['name'] ) ) {
				$section_name = $section['name'];
			} else {
				/* translators: %d: section number (1-based) used when a section has no name. */
				$section_name = sprintf( __( 'Section %d', 'eb-course-exp' ), (int) $index + 1 );
			}
			break;
		}
	}
}

get_header();
courseexp_render_body_class();

set_query_var( 'course_title', $course_title );
set_query_var( 'course_data', $course_data );
load_template( COURSEEXP_PLUGIN_DIR . 'templates/parts/sidebar.php' );
?>

<div class="courseexp-layout">
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
			<span class="courseexp-breadcrumb__current" aria-current="page">
				<?php echo esc_html( $section_name ? $section_name : __( 'Section', 'eb-course-exp' ) ); ?>
			</span>
		</nav>

		<?php if ( $section_name ) : ?>
			<h1 class="courseexp-section-page__title"><?php echo esc_html( $section_name ); ?></h1>
		<?php endif; ?>

		<div class="courseexp-main__content">
			<?php
			set_query_var( 'course_data', $course_data );
			set_query_var( 'courseexp_detail_section', (string) $section_id );
			load_template( COURSEEXP_PLUGIN_DIR . 'templates/parts/sections.php', false );
			?>
		</div>
	</main>
</div>

<?php
get_footer();
