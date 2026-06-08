<?php
/**
 * Sidebar Template Part
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

$has_data = ! empty( $course_data ) && ! is_wp_error( $course_data );
$sections = $has_data && isset( $course_data['sections'] ) ? $course_data['sections'] : array();

$course_slug   = (string) get_query_var( 'course_slug' );
$course_url    = $course_slug ? home_url( '/' . COURSEEXP_SLUG . '/' . $course_slug . '/' ) : '';
$active_cmid   = (int) get_query_var( 'courseexp_active_cmid' );
$activity_base = $course_slug ? home_url( '/' . COURSEEXP_SLUG . '/' . $course_slug . '/activity/' ) : '';
?>

<button
	class="courseexp-mobile-toggle"
	id="courseexp-mobile-toggle"
	aria-label="<?php esc_attr_e( 'Open course menu', 'eb-course-exp' ); ?>"
	aria-controls="courseexp-sidebar"
>
	<span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/><path d="m14 9 3 3-3 3"/></svg></span>
</button>

<button
	class="courseexp-floating-toggle"
	id="courseexp-floating-toggle"
	aria-label="<?php esc_attr_e( 'Expand sidebar', 'eb-course-exp' ); ?>"
	aria-controls="courseexp-sidebar"
	aria-expanded="false"
>
	<span class="courseexp-floating-toggle__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/><path d="m14 9 3 3-3 3"/></svg></span>
</button>

<div class="courseexp-sidebar-overlay" id="courseexp-sidebar-overlay"></div>

<aside class="courseexp-sidebar" id="courseexp-sidebar" role="navigation" aria-label="<?php esc_attr_e( 'Course content navigation', 'eb-course-exp' ); ?>">
	<div class="courseexp-sidebar__header">
		<?php if ( ! empty( $course_title ) ) : ?>
			<h2 class="courseexp-sidebar__title">
				<?php if ( $course_url ) : ?>
					<a class="courseexp-sidebar__title-link" href="<?php echo esc_url( $course_url ); ?>"><?php echo esc_html( $course_title ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $course_title ); ?>
				<?php endif; ?>
			</h2>
		<?php else : ?>
			<h2 class="courseexp-sidebar__title"><?php esc_html_e( 'Course Content', 'eb-course-exp' ); ?></h2>
		<?php endif; ?>
		<div class="courseexp-sidebar__actions">
			<button
				class="courseexp-sidebar__action-btn"
				id="courseexp-accordion-toggle"
				aria-label="<?php esc_attr_e( 'Expand all sections', 'eb-course-exp' ); ?>"
				aria-pressed="false"
				title="<?php esc_attr_e( 'Expand/Collapse All', 'eb-course-exp' ); ?>"
				data-label-expand="<?php esc_attr_e( 'Expand all sections', 'eb-course-exp' ); ?>"
				data-label-collapse="<?php esc_attr_e( 'Collapse all sections', 'eb-course-exp' ); ?>"
				data-title-expand="<?php esc_attr_e( 'Expand All', 'eb-course-exp' ); ?>"
				data-title-collapse="<?php esc_attr_e( 'Collapse All', 'eb-course-exp' ); ?>"
			>
				<span class="courseexp-accordion-toggle__icon-expand" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-list-chevrons-up-down-icon lucide-list-chevrons-up-down"><path d="M3 5h8"/><path d="M3 12h8"/><path d="M3 19h8"/><path d="m15 8 3-3 3 3"/><path d="m15 16 3 3 3-3"/></svg>
				</span>
				<span class="courseexp-accordion-toggle__icon-collapse" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-list-chevrons-down-up-icon lucide-list-chevrons-down-up"><path d="M3 5h8"/><path d="M3 12h8"/><path d="M3 19h8"/><path d="m15 5 3 3 3-3"/><path d="m15 19 3-3 3 3"/></svg>
				</span>
			</button>
			<button
				class="courseexp-sidebar__action-btn"
				id="courseexp-sidebar-toggle"
				aria-expanded="true"
				aria-controls="courseexp-sidebar"
				aria-label="<?php esc_attr_e( 'Collapse sidebar', 'eb-course-exp' ); ?>"
				title="<?php esc_attr_e( 'Collapse Sidebar', 'eb-course-exp' ); ?>"
			>
				<span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/><path d="m16 15-3-3 3-3"/></svg></span>
			</button>
		</div>
	</div>

	<div class="courseexp-sidebar__content">
		<?php if ( $has_data && ! empty( $sections ) ) : ?>
			<nav class="courseexp-accordion" id="courseexp-accordion" aria-label="<?php esc_attr_e( 'Course sections', 'eb-course-exp' ); ?>">
				<?php foreach ( $sections as $section_index => $section ) : ?>
					<?php
					if ( is_object( $section ) ) {
						$section = json_decode( wp_json_encode( $section ), true );
					}
					$section_id        = isset( $section['id'] ) ? $section['id'] : $section_index;
					$activities        = isset( $section['activities'] ) ? $section['activities'] : array();
					$section_unique    = 'courseexp-section-' . esc_attr( $section_id );
					$is_first_section  = 0 === $section_index;
					$section_url       = $course_slug ? home_url( '/' . COURSEEXP_SLUG . '/' . $course_slug . '/' . $section_id . '/' ) : '';
					$section_is_active = false;
					if ( $active_cmid > 0 ) {
						foreach ( $activities as $section_activity ) {
							$section_activity = (array) $section_activity;
							if ( isset( $section_activity['cmid'] ) && (int) $section_activity['cmid'] === $active_cmid ) {
								$section_is_active = true;
								break;
							}
						}
					}
					$is_expanded = $is_first_section || $section_is_active;

					if ( isset( $section['name'] ) ) {
						$section_name = $section['name'];
					} else {
						/* translators: %d: section number (1-based index) used as a fallback when the section has no name. */
						$section_name = sprintf( __( 'Section %d', 'eb-course-exp' ), $section_index + 1 );
					}
					?>
					<div class="courseexp-section<?php echo $is_expanded ? ' is-expanded' : ''; ?><?php echo $section_is_active ? ' is-active' : ''; ?>" data-section-id="<?php echo esc_attr( $section_id ); ?>">
						<div class="courseexp-section__header">
							<?php if ( $section_url ) : ?>
								<a class="courseexp-section__title-link" id="<?php echo esc_attr( $section_unique . '-title' ); ?>" href="<?php echo esc_url( $section_url ); ?>">
									<span class="courseexp-section__title"><?php echo esc_html( $section_name ); ?></span>
								</a>
							<?php else : ?>
								<span class="courseexp-section__title" id="<?php echo esc_attr( $section_unique . '-title' ); ?>"><?php echo esc_html( $section_name ); ?></span>
							<?php endif; ?>
							<button
								type="button"
								class="courseexp-section__toggle"
								aria-expanded="<?php echo $is_expanded ? 'true' : 'false'; ?>"
								aria-controls="<?php echo esc_attr( $section_unique ); ?>"
								id="<?php echo esc_attr( $section_unique . '-toggle' ); ?>"
								aria-label="<?php /* translators: %s: section name. */ printf( esc_attr__( 'Toggle %s', 'eb-course-exp' ), esc_attr( $section_name ) ); ?>"
							>
								<span class="courseexp-section__icon" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down-icon lucide-chevron-down"><path d="m6 9 6 6 6-6"/></svg>
								</span>
							</button>
						</div>
						<div
							class="courseexp-section__content"
							id="<?php echo esc_attr( $section_unique ); ?>"
							role="region"
							aria-labelledby="<?php echo esc_attr( $section_unique . '-title' ); ?>"
							<?php echo $is_expanded ? '' : 'hidden'; ?>
						>
							<?php if ( ! empty( $activities ) ) : ?>
								<ul class="courseexp-activities">
									<?php foreach ( $activities as $activity ) : ?>
										<?php
										if ( is_object( $activity ) ) {
											$activity = json_decode( wp_json_encode( $activity ), true );
										}

										$activity_id   = isset( $activity['cmid'] ) ? intval( $activity['cmid'] ) : 0;
										$activity_name = isset( $activity['name'] ) ? $activity['name'] : '';
										$indent        = isset( $activity['indent'] ) ? intval( $activity['indent'] ) : 0;
										$available     = isset( $activity['available'] ) ? (bool) $activity['available'] : true;
										$rendermode    = isset( $activity['rendermode'] ) ? (string) $activity['rendermode'] : '';
										$external_url  = isset( $activity['externalurl'] ) ? (string) $activity['externalurl'] : '';

										$completion       = isset( $activity['completion'] ) ? $activity['completion'] : array();
										$is_tracked       = isset( $completion['tracked'] ) ? (bool) $completion['tracked'] : false;
										$completion_state = isset( $completion['state'] ) ? (int) $completion['state'] : 0;
										$show_status_icon = $is_tracked;

										$is_external = ( 'external' === $rendermode && '' !== $external_url );
										$is_inline   = ( 'inline' === $rendermode );
										if ( $is_external ) {
											$activity_url = $external_url;
										} elseif ( $is_inline ) {
											$activity_url = $course_slug
												? home_url( '/' . COURSEEXP_SLUG . '/' . $course_slug . '/' . $section_id . '/' ) . '#courseexp-activity-' . $activity_id
												: '';
										} else {
											$activity_url = $activity_base ? $activity_base . $activity_id . '/' : '';
										}
										$is_active = ( $active_cmid > 0 && $activity_id === $active_cmid );

										$activity_classes = array( 'courseexp-activity' );
										if ( ! $available ) {
											$activity_classes[] = 'is-locked';
										}
										if ( $is_active ) {
											$activity_classes[] = 'is-active';
										}
										$activity_class = implode( ' ', array_map( 'sanitize_html_class', $activity_classes ) );

										?>
										<li class="<?php echo esc_attr( $activity_class ); ?>" data-activity-id="<?php echo esc_attr( $activity_id ); ?>" data-indent="<?php echo esc_attr( $indent ); ?>">
											<a
												href="<?php echo esc_url( $activity_url ); ?>"
												class="courseexp-activity__link"
												<?php echo $is_active ? 'aria-current="page"' : ''; ?>
												<?php echo $is_external ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
											>
												<span class="courseexp-activity__status">
													<?php if ( $show_status_icon ) : ?>
														<?php if ( 1 === $completion_state || 2 === $completion_state ) : ?>
															<svg class="courseexp-activity__icon courseexp-activity__icon--complete" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
														<?php elseif ( 3 === $completion_state ) : ?>
															<svg class="courseexp-activity__icon courseexp-activity__icon--fail" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
														<?php else : ?>
															<svg class="courseexp-activity__icon courseexp-activity__icon--incomplete" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/></svg>
														<?php endif; ?>
													<?php endif; ?>
												</span>
												<span class="courseexp-activity__label">
													<span class="courseexp-activity__name"><?php echo esc_html( $activity_name ); ?></span>
													<?php if ( ! $available ) : ?>
														<span class="courseexp-activity__lock" title="<?php esc_attr_e( 'Locked', 'eb-course-exp' ); ?>">
															<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lock-keyhole-icon lucide-lock-keyhole"><circle cx="12" cy="16" r="1"/><rect x="3" y="10" width="18" height="12" rx="2"/><path d="M7 10V7a5 5 0 0 1 10 0v3"/></svg>
														</span>
													<?php endif; ?>
												</span>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php else : ?>
								<p class="courseexp-section__empty"><?php esc_html_e( 'No activities in this section.', 'eb-course-exp' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</nav>
		<?php elseif ( is_wp_error( $course_data ) ) : ?>
			<div class="courseexp-error">
				<p><?php esc_html_e( 'Unable to load course content. Please try again later.', 'eb-course-exp' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</aside>
