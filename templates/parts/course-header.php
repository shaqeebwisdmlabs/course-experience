<?php
/**
 * Course Header Template Part
 *
 * Renders the course category, title and overall progress at the top of the
 * course experience page. Reads the course block from the
 * `get_course_structure` API response.
 *
 * @package EB_Course_Experience
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_title = isset( $course_title ) ? $course_title : '';
$course_data  = isset( $course_data ) ? $course_data : array();

if ( is_object( $course_data ) ) {
	$course_data = json_decode( wp_json_encode( $course_data ), true );
}

$has_data = ! empty( $course_data ) && ! is_wp_error( $course_data ) && is_array( $course_data );

$category    = $has_data && isset( $course_data['categoryname'] ) ? $course_data['categoryname'] : '';
$course_name = $has_data && ! empty( $course_data['fullname'] ) ? $course_data['fullname'] : $course_title;
?>

<div class="courseexp-course-header">
	<div class="courseexp-course-header__intro">
		<?php if ( ! empty( $category ) ) : ?>
			<p class="courseexp-course-header__category"><?php echo esc_html( $category ); ?></p>
		<?php endif; ?>

		<h1 class="courseexp-course-header__title">
			<?php echo $course_name ? esc_html( $course_name ) : esc_html__( 'Course Not Found', 'eb-course-exp' ); ?>
		</h1>
	</div>
</div>
