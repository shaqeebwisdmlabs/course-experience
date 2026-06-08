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
$completion  = $has_data && isset( $course_data['completion'] ) ? (array) $course_data['completion'] : array();

$tracks_completion = empty( $completion ) || ! empty( $completion['enablecompletion'] );

$progress_block = $has_data && isset( $course_data['progress'] ) ? (array) $course_data['progress'] : array();

$total_activities     = isset( $progress_block['totalactivities'] ) ? absint( $progress_block['totalactivities'] ) : 0;
$completed_activities = isset( $progress_block['completedactivities'] ) ? absint( $progress_block['completedactivities'] ) : 0;

if ( isset( $progress_block['percentage'] ) ) {
	$progress = (int) floor( (float) $progress_block['percentage'] );
} elseif ( $total_activities > 0 ) {
	$progress = (int) floor( ( $completed_activities / $total_activities ) * 100 );
} else {
	$progress = 0;
}

$progress = max( 0, min( 100, $progress ) );

$show_progress = $tracks_completion && $total_activities > 0;
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

	<?php if ( $show_progress ) : ?>
		<div class="courseexp-course-header__progress">
			<div class="courseexp-course-header__progress-meta">
				<span class="courseexp-course-header__progress-label">
					<?php
					printf(
						/* translators: 1: number of completed activities, 2: total number of activities. */
						esc_html__( '%1$d of %2$d activities completed', 'eb-course-exp' ),
						esc_html( $completed_activities ),
						esc_html( $total_activities )
					);
					?>
				</span>
				<span class="courseexp-course-header__progress-value">
					<?php
					/* translators: %d: course completion percentage. */
					printf( esc_html__( '%d%%', 'eb-course-exp' ), esc_html( $progress ) );
					?>
				</span>
			</div>
			<progress
				class="courseexp-course-header__progress-bar"
				max="100"
				value="<?php echo esc_attr( $progress ); ?>"
				aria-label="<?php esc_attr_e( 'Course progress', 'eb-course-exp' ); ?>"
			>
			<?php
			/* translators: %d: course completion percentage. */
			printf( esc_html__( '%d%%', 'eb-course-exp' ), esc_html( $progress ) );
			?>
			</progress>
		</div>
	<?php endif; ?>
</div>
