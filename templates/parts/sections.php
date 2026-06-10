<?php
/**
 * Sections Area Template Part
 *
 * Renders the course section document(s) inside the main content column:
 * section headers, inline content (label/pulse) and activity rows with their
 * completion controls.
 *
 * Of the four Moodle setting layers, this pass honours two:
 *   - courseformat.hiddensections  ('shownamesonly' shows a name-only stub for
 *     hidden sections; 'hidden' means the server already omitted them).
 *   - completion.showcompletionconditions  (expands the automatic "To do"
 *     control into its per-condition list).
 *
 * @package EB_Course_Experience
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_data = isset( $course_data ) ? $course_data : array();

if ( is_object( $course_data ) ) {
	$course_data = json_decode( wp_json_encode( $course_data ), true );
}

$has_data = ! empty( $course_data ) && ! is_wp_error( $course_data ) && is_array( $course_data );
$sections = $has_data && isset( $course_data['sections'] ) ? $course_data['sections'] : array();

$courseformat        = $has_data && isset( $course_data['courseformat'] ) ? (array) $course_data['courseformat'] : array();
$completion_settings = $has_data && isset( $course_data['completion'] ) ? (array) $course_data['completion'] : array();

$layouttype  = isset( $courseformat['layouttype'] ) ? $courseformat['layouttype'] : 'allinonepage';
$is_paged    = ( 'onesectionperpage' === $layouttype );
$course_slug = (string) get_query_var( 'course_slug' );

$detail_section = (string) get_query_var( 'courseexp_detail_section' );
$is_detail      = ( '' !== $detail_section );

$courseexp_ctx = array(
	'enable_completion'   => ! empty( $completion_settings['enablecompletion'] ),
	'show_conditions'     => ! empty( $completion_settings['showcompletionconditions'] ),
	'uses_indentation'    => ! empty( $courseformat['usesindentation'] ),
	'show_activity_dates' => $has_data && ! empty( $course_data['showactivitydates'] ),
	'activity_base_url'   => $course_slug ? home_url( '/' . COURSEEXP_SLUG . '/' . $course_slug . '/activity/' ) : '',
);

if ( ! function_exists( 'courseexp_render_trusted_html' ) ) {
	/**
	 * Render Moodle rich text verbatim.
	 *
	 * Moodle course content is authored inside the trusted LMS, so it is rendered
	 * as-is — not run through wp_kses_post() — so embedded media (video players
	 * that rely on <script>) runs exactly as it does in Moodle.
	 *
	 * @param string $html Trusted HTML from the Moodle payload.
	 * @return void
	 */
	function courseexp_render_trusted_html( string $html ): void {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted Moodle course content, rendered verbatim so script-based embeds run.
	}
}

if ( ! function_exists( 'courseexp_render_inline_content' ) ) {
	/**
	 * Render an inline (label/pulse) item directly into the section flow.
	 *
	 * Inline items are never click targets, but pulse activities can carry a
	 * manual completion control — rendered in a footer beneath the content.
	 *
	 * @param array $activity Activity block with rendermode 'inline'.
	 * @param array $ctx      Course-level rendering context.
	 * @return void
	 */
	function courseexp_render_inline_content( array $activity, array $ctx ): void {
		$description  = isset( $activity['description'] ) ? $activity['description'] : '';
		$cmid         = isset( $activity['cmid'] ) ? (int) $activity['cmid'] : 0;
		$available    = ( ! isset( $activity['available'] ) || (bool) $activity['available'] ) && ! courseexp_activity_is_unavailable( $activity );
		$avail_info   = isset( $activity['availabilityinfo'] ) ? $activity['availabilityinfo'] : '';
		$completion   = isset( $activity['completion'] ) ? (array) $activity['completion'] : array();
		$has_body     = '' !== trim( (string) $description );
		$show_control = $available && courseexp_completion_is_visible( $completion, $ctx );

		if ( ! $has_body && ! $show_control && $available ) {
			return;
		}

		$inline_classes = array( 'courseexp-inline-content' );
		if ( ! $available ) {
			$inline_classes[] = 'is-locked';
		}
		$inline_class = implode( ' ', array_map( 'sanitize_html_class', $inline_classes ) );
		?>
		<div class="<?php echo esc_attr( $inline_class ); ?>" id="courseexp-activity-<?php echo esc_attr( $cmid ); ?>" data-activity-id="<?php echo esc_attr( $cmid ); ?>">
			<?php if ( $show_control ) : ?>
				<div class="courseexp-inline-content__header">
					<?php courseexp_render_completion_control( $completion, $cmid, $ctx ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $has_body ) : ?>
				<div class="courseexp-inline-content__body">
					<?php courseexp_render_trusted_html( $description ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! $show_control && ! $available ) : ?>
				<div class="courseexp-inline-content__lock">
					<span class="courseexp-activity-row__lock-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="16" r="1"/><rect x="3" y="10" width="18" height="12" rx="2"/><path d="M7 10V7a5 5 0 0 1 10 0v3"/></svg>
					</span>
					<?php if ( ! empty( $avail_info ) ) : ?>
						<span class="courseexp-activity-row__lock-text"><?php echo wp_kses_post( $avail_info ); ?></span>
					<?php else : ?>
						<span class="courseexp-activity-row__lock-text"><?php esc_html_e( 'Not available', 'eb-course-exp' ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'courseexp_render_activity_row' ) ) {
	/**
	 * Render a single activity row (Section 3.2 anatomy, scoped to this pass).
	 *
	 * @param array $activity Activity block.
	 * @param array $ctx      Course-level rendering context.
	 * @return void
	 */
	function courseexp_render_activity_row( array $activity, array $ctx ): void {
		$cmid           = isset( $activity['cmid'] ) ? (int) $activity['cmid'] : 0;
		$name           = isset( $activity['name'] ) ? $activity['name'] : '';
		$icon           = isset( $activity['icon'] ) ? $activity['icon'] : '';
		$indent         = isset( $activity['indent'] ) ? (int) $activity['indent'] : 0;
		$is_unavailable = courseexp_activity_is_unavailable( $activity );
		$available      = ( ! isset( $activity['available'] ) || (bool) $activity['available'] ) && ! $is_unavailable;
		$avail_info     = isset( $activity['availabilityinfo'] ) ? $activity['availabilityinfo'] : '';
		$show_desc      = ! empty( $activity['showdescription'] );
		$description    = isset( $activity['description'] ) ? $activity['description'] : '';
		$completion     = isset( $activity['completion'] ) ? (array) $activity['completion'] : array();
		$external       = isset( $activity['externalurl'] ) ? (string) $activity['externalurl'] : '';
		$afterlink      = isset( $activity['afterlink'] ) ? trim( (string) $activity['afterlink'] ) : '';

		$badge         = isset( $activity['activitybadge'] ) ? (array) $activity['activitybadge'] : array();
		$badge_content = isset( $badge['content'] ) ? (string) $badge['content'] : '';
		$badge_style   = isset( $badge['style'] ) ? sanitize_html_class( (string) $badge['style'] ) : '';

		$is_external  = courseexp_activity_opens_externally( $activity );
		$activity_url = $is_external ? $external : ( ! empty( $ctx['activity_base_url'] ) ? $ctx['activity_base_url'] . $cmid . '/' : '' );

		$indent_attr = ! empty( $ctx['uses_indentation'] ) ? $indent : 0;

		$row_classes = array( 'courseexp-activity-row' );
		if ( ! $available ) {
			$row_classes[] = 'is-locked';
		}
		$row_class = implode( ' ', array_map( 'sanitize_html_class', $row_classes ) );
		?>
		<li class="<?php echo esc_attr( $row_class ); ?>"<?php echo $is_unavailable ? ' id="courseexp-activity-' . esc_attr( $cmid ) . '"' : ''; ?> data-activity-id="<?php echo esc_attr( $cmid ); ?>" data-indent="<?php echo esc_attr( $indent_attr ); ?>">
			<div class="courseexp-activity-row__inner">
				<div class="courseexp-activity-row__top">
					<div class="courseexp-activity-row__main">
						<?php if ( ! empty( $icon ) ) : ?>
							<span class="courseexp-activity-row__icon" aria-hidden="true">
								<img src="<?php echo esc_url( $icon ); ?>" alt="" width="24" height="24" loading="lazy" />
							</span>
						<?php endif; ?>

						<?php if ( $available ) : ?>
							<a
								href="<?php echo esc_url( $activity_url ); ?>"
								class="courseexp-activity-row__name"
								<?php echo $is_external ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
							>
								<?php echo esc_html( $name ); ?>
							</a>
						<?php else : ?>
							<span class="courseexp-activity-row__name courseexp-activity-row__name--locked">
								<?php echo esc_html( $name ); ?>
							</span>
						<?php endif; ?>

						<?php if ( '' !== trim( $badge_content ) ) : ?>
							<span class="courseexp-activity-row__badge<?php echo $badge_style ? ' ' . esc_attr( $badge_style ) : ''; ?>"><?php echo esc_html( $badge_content ); ?></span>
						<?php endif; ?>
					</div>

					<?php if ( $available ) : ?>
						<div class="courseexp-activity-row__completion">
							<?php courseexp_render_completion_control( $completion, $cmid, $ctx ); ?>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( $show_desc && '' !== trim( (string) $description ) ) : ?>
					<div class="courseexp-activity-row__description">
						<?php courseexp_render_trusted_html( $description ); ?>
					</div>
				<?php endif; ?>

				<?php if ( '' !== $afterlink ) : ?>
					<div class="courseexp-activity-row__afterlink"><?php courseexp_render_trusted_html( $afterlink ); ?></div>
				<?php endif; ?>

				<?php if ( ! $available ) : ?>
					<div class="courseexp-activity-row__lock">
						<span class="courseexp-activity-row__lock-icon" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="16" r="1"/><rect x="3" y="10" width="18" height="12" rx="2"/><path d="M7 10V7a5 5 0 0 1 10 0v3"/></svg>
						</span>
						<?php if ( ! empty( $avail_info ) ) : ?>
							<span class="courseexp-activity-row__lock-text"><?php echo wp_kses_post( $avail_info ); ?></span>
						<?php else : ?>
							<span class="courseexp-activity-row__lock-text"><?php esc_html_e( 'Not available', 'eb-course-exp' ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</li>
		<?php
	}
}

if ( ! function_exists( 'courseexp_render_section_items' ) ) {
	/**
	 * Render the ordered items of a section: inline content, subsections and rows.
	 *
	 * @param array $activities Activity blocks in document order.
	 * @param array $ctx        Course-level rendering context.
	 * @return void
	 */
	function courseexp_render_section_items( array $activities, array $ctx ): void {
		$open_list = false;

		foreach ( $activities as $activity ) {
			$activity = (array) $activity;
			if ( ! courseexp_activity_on_course_page( $activity ) ) {
				continue;
			}
			$children      = isset( $activity['children'] ) && is_array( $activity['children'] ) ? $activity['children'] : array();
			$mode          = isset( $activity['rendermode'] ) ? $activity['rendermode'] : '';
			$is_subsection = courseexp_activity_is_subsection( $activity );

			$is_inline = 'inline' === $mode && ! $is_subsection && ! courseexp_activity_is_unavailable( $activity );

			if ( $is_inline || $is_subsection ) {
				if ( $open_list ) {
					echo '</ul>';
					$open_list = false;
				}
			}

			if ( $is_subsection ) {
				$sub_name      = isset( $activity['name'] ) ? $activity['name'] : '';
				$sub_id        = isset( $activity['cmid'] ) ? (int) $activity['cmid'] : 0;
				$sub_unique    = 'courseexp-subsection-' . $sub_id;
				$sub_body_id   = $sub_unique . '-body';
				$sub_ttl_id    = $sub_unique . '-title';
				$sub_avail     = courseexp_block_availability( $activity );
				$sub_locked    = ! $sub_avail['available'];
				$sub_div_class = 'courseexp-subsection is-expanded' . ( $sub_locked ? ' is-locked' : '' );
				?>
				<div class="<?php echo esc_attr( $sub_div_class ); ?>" id="<?php echo esc_attr( $sub_unique ); ?>" data-subsection-id="<?php echo esc_attr( $sub_id ); ?>">
					<div class="courseexp-subsection__header">
						<button
							type="button"
							class="courseexp-subsection__toggle"
							aria-expanded="true"
							aria-controls="<?php echo esc_attr( $sub_body_id ); ?>"
							aria-labelledby="<?php echo esc_attr( $sub_ttl_id ); ?>"
						>
							<span class="courseexp-subsection__toggle-icon" aria-hidden="true">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
							</span>
						</button>
						<h3 class="courseexp-subsection__title" id="<?php echo esc_attr( $sub_ttl_id ); ?>">
							<?php echo esc_html( $sub_name ); ?>
							<?php if ( $sub_locked ) : ?>
								<?php courseexp_render_lock_icon( 'courseexp-subsection__lock' ); ?>
							<?php endif; ?>
						</h3>
					</div>
					<div class="courseexp-subsection__body" id="<?php echo esc_attr( $sub_body_id ); ?>">
						<?php if ( $sub_locked ) : ?>
							<?php courseexp_render_restricted_notice( $sub_avail['info'] ); ?>
						<?php else : ?>
							<ul class="courseexp-activity-list">
								<?php
								foreach ( $children as $child ) {
									$child = (array) $child;
									if ( ! courseexp_activity_on_course_page( $child ) ) {
										continue;
									}
									courseexp_render_activity_row( $child, $ctx );
								}
								?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
				<?php
				continue;
			}

			if ( $is_inline ) {
				courseexp_render_inline_content( $activity, $ctx );
				continue;
			}

			if ( ! $open_list ) {
				echo '<ul class="courseexp-activity-list">';
				$open_list = true;
			}
			courseexp_render_activity_row( $activity, $ctx );
		}

		if ( $open_list ) {
			echo '</ul>';
		}
	}
}

if ( ! function_exists( 'courseexp_render_section_body_inner' ) ) {
	/**
	 * Render the inner content of a section body: stub note, or summary + items.
	 *
	 * Shared by both layouts so the markup stays in one place.
	 *
	 * @param array $section Section block (array-cast).
	 * @param array $ctx     Course-level rendering context.
	 * @return void
	 */
	function courseexp_render_section_body_inner( array $section, array $ctx ): void {
		$availability = courseexp_block_availability( $section );
		if ( ! $availability['available'] ) {
			courseexp_render_restricted_notice( $availability['info'] );
			return;
		}

		$is_visible = ! isset( $section['visible'] ) || (bool) $section['visible'];

		if ( ! $is_visible ) {
			?>
			<p class="courseexp-section-block__stub-note"><?php esc_html_e( 'Not available', 'eb-course-exp' ); ?></p>
			<?php
			return;
		}

		$summary    = isset( $section['summary'] ) ? $section['summary'] : '';
		$activities = isset( $section['activities'] ) ? $section['activities'] : array();

		if ( '' !== trim( (string) $summary ) ) {
			?>
			<div class="courseexp-section-block__summary">
				<?php courseexp_render_trusted_html( $summary ); ?>
			</div>
			<?php
		}

		if ( ! empty( $activities ) ) {
			?>
			<div class="courseexp-section-block__items">
				<?php courseexp_render_section_items( $activities, $ctx ); ?>
			</div>
			<?php
		} else {
			?>
			<p class="courseexp-section-block__empty"><?php esc_html_e( 'No content in this section yet.', 'eb-course-exp' ); ?></p>
			<?php
		}
	}
}

if ( ! function_exists( 'courseexp_section_metrics' ) ) {
	/**
	 * Read a section's footer metrics from the server-provided progress block.
	 *
	 * Expected shape on each section (one-section-per-page layout):
	 *   'progress' => array(
	 *       'totalactivities'     => int,  // all activities -> "Activities: N"
	 *       'completedactivities' => int,  // completed completion-tracked activities
	 *       'trackedactivities'   => int,  // completion-tracked activities (denominator)
	 *   )
	 *
	 * @param array $section Section block (array-cast).
	 * @return array Associative array with 'activities', 'completed', 'total' keys.
	 */
	function courseexp_section_metrics( array $section ): array {
		$progress = isset( $section['progress'] ) && is_array( $section['progress'] ) ? $section['progress'] : array();

		return array(
			'activities' => isset( $progress['totalactivities'] ) ? absint( $progress['totalactivities'] ) : 0,
			'completed'  => isset( $progress['completedactivities'] ) ? absint( $progress['completedactivities'] ) : 0,
			'total'      => isset( $progress['trackedactivities'] ) ? absint( $progress['trackedactivities'] ) : 0,
		);
	}
}
?>

<div class="courseexp-sections<?php echo $is_detail ? ' courseexp-sections--detail' : ''; ?>" id="courseexp-sections" data-layout="<?php echo esc_attr( $layouttype ); ?>">
	<?php if ( $is_detail ) : ?>
		<?php
		$target_section = null;
		if ( $has_data ) {
			foreach ( $sections as $section_index => $section ) {
				$section = (array) $section;
				$sid     = isset( $section['id'] ) ? (int) $section['id'] : (int) $section_index;
				if ( (string) $sid === $detail_section ) {
					$target_section = $section;
					break;
				}
			}
		}
		?>
		<?php if ( null !== $target_section ) : ?>
			<section class="courseexp-section-block" id="section-<?php echo esc_attr( $detail_section ); ?>" data-section-id="<?php echo esc_attr( $detail_section ); ?>">
				<div class="courseexp-section-block__body">
					<?php courseexp_render_section_body_inner( $target_section, $courseexp_ctx ); ?>
				</div>
			</section>
		<?php else : ?>
			<div class="courseexp-sections__empty">
				<p><?php esc_html_e( 'Section not found.', 'eb-course-exp' ); ?></p>
			</div>
		<?php endif; ?>
	<?php elseif ( $has_data && ! empty( $sections ) ) : ?>
		<?php foreach ( $sections as $section_index => $section ) : ?>
			<?php
			$section       = (array) $section;
			$section_id    = isset( $section['id'] ) ? (int) $section['id'] : (int) $section_index;
			$is_visible    = ! isset( $section['visible'] ) || (bool) $section['visible'];
			$is_restricted = ! courseexp_block_availability( $section )['available'];
			$is_current    = ! empty( $section['current'] );
			$is_first      = ( 0 === $section_index );
			$body_id       = 'courseexp-section-body-' . $section_id;
			$toggle_id     = 'courseexp-section-toggle-' . $section_id;
			$title_id      = 'courseexp-section-title-' . $section_id;

			if ( isset( $section['name'] ) && '' !== trim( (string) $section['name'] ) ) {
				$section_name = $section['name'];
			} else {
				/* translators: %d: section number (1-based) used when a section has no name. */
				$section_name = sprintf( __( 'Section %d', 'eb-course-exp' ), (int) $section_index + 1 );
			}

			$section_classes = array( 'courseexp-section-block' );
			if ( ! $is_visible ) {
				$section_classes[] = 'courseexp-section-block--stub';
			}
			if ( $is_restricted ) {
				$section_classes[] = 'is-locked';
			}
			if ( $is_current ) {
				$section_classes[] = 'is-current';
			}
			if ( $is_paged ) {
				$section_classes[] = 'courseexp-section-block--paged';
			} elseif ( $is_first ) {
				$section_classes[] = 'is-expanded';
			}
			$section_class = implode( ' ', array_map( 'sanitize_html_class', $section_classes ) );

			$section_url = $course_slug ? home_url( '/' . COURSEEXP_SLUG . '/' . $course_slug . '/' . $section_id . '/' ) : '';
			?>
			<?php if ( $is_paged ) : ?>
				<section class="<?php echo esc_attr( $section_class ); ?>" id="section-<?php echo esc_attr( $section_id ); ?>" data-section-id="<?php echo esc_attr( $section_id ); ?>">
					<div class="courseexp-section-block__header">
						<h2 class="courseexp-section-block__title">
							<?php if ( $is_restricted ) : ?>
								<?php echo esc_html( $section_name ); ?>
								<?php courseexp_render_lock_icon( 'courseexp-section-block__lock' ); ?>
							<?php else : ?>
								<a class="courseexp-section-block__title-link" href="<?php echo esc_url( $section_url ); ?>"><?php echo esc_html( $section_name ); ?></a>
							<?php endif; ?>
						</h2>
						<?php if ( ! $is_restricted ) : ?>
							<a
								class="courseexp-section-block__arrow"
								href="<?php echo esc_url( $section_url ); ?>"
								aria-label="<?php /* translators: %s: section name. */ printf( esc_attr__( 'Open %s', 'eb-course-exp' ), esc_attr( $section_name ) ); ?>"
							>
								<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
							</a>
						<?php endif; ?>
					</div>

					<?php if ( $is_restricted ) : ?>
						<div class="courseexp-section-block__body">
							<?php courseexp_render_restricted_notice( courseexp_block_availability( $section )['info'] ); ?>
						</div>
					<?php elseif ( $is_first ) : ?>
						<div class="courseexp-section-block__body">
							<?php courseexp_render_section_body_inner( $section, $courseexp_ctx ); ?>
						</div>
					<?php else : ?>
						<?php $section_summary = isset( $section['summary'] ) ? (string) $section['summary'] : ''; ?>
						<?php if ( '' !== trim( $section_summary ) ) : ?>
							<div class="courseexp-section-block__summary">
								<?php courseexp_render_trusted_html( $section_summary ); ?>
							</div>
						<?php endif; ?>
						<?php $metrics = courseexp_section_metrics( $section ); ?>
						<div class="courseexp-section-block__footer">
							<span class="courseexp-section-block__metric">
								<span class="courseexp-section-block__metric-icon" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
								</span>
								<?php
								/* translators: %d: number of activities in the section. */
								printf( esc_html__( 'Activities: %d', 'eb-course-exp' ), (int) $metrics['activities'] );
								?>
							</span>
							<?php if ( $metrics['total'] > 0 ) : ?>
								<span class="courseexp-section-block__metric">
									<span class="courseexp-section-block__metric-icon" aria-hidden="true">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="m19 9-5 5-4-4-3 3"/></svg>
									</span>
									<?php
									printf(
										/* translators: 1: completed activities, 2: total activities that have completion conditions. */
										esc_html__( 'Progress: %1$d / %2$d', 'eb-course-exp' ),
										(int) $metrics['completed'],
										(int) $metrics['total']
									);
									?>
								</span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</section>
			<?php else : ?>
				<section class="<?php echo esc_attr( $section_class ); ?>" id="section-<?php echo esc_attr( $section_id ); ?>" data-section-id="<?php echo esc_attr( $section_id ); ?>">
					<div class="courseexp-section-block__header">
						<button
							type="button"
							class="courseexp-section-block__toggle"
							id="<?php echo esc_attr( $toggle_id ); ?>"
							aria-expanded="<?php echo $is_first ? 'true' : 'false'; ?>"
							aria-controls="<?php echo esc_attr( $body_id ); ?>"
							aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
						>
							<span class="courseexp-section-block__toggle-icon" aria-hidden="true">
								<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
							</span>
						</button>
						<h2 class="courseexp-section-block__title" id="<?php echo esc_attr( $title_id ); ?>">
							<?php if ( $section_url && ! $is_restricted ) : ?>
								<a class="courseexp-section-block__title-link" href="<?php echo esc_url( $section_url ); ?>"><?php echo esc_html( $section_name ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $section_name ); ?>
							<?php endif; ?>
							<?php if ( $is_restricted ) : ?>
								<?php courseexp_render_lock_icon( 'courseexp-section-block__lock' ); ?>
							<?php endif; ?>
						</h2>

						<?php if ( $is_first ) : ?>
							<button
								type="button"
								class="courseexp-section-block__expand-all"
								id="courseexp-sections-expand-all"
								aria-pressed="false"
								data-label-expand="<?php esc_attr_e( 'Expand all', 'eb-course-exp' ); ?>"
								data-label-collapse="<?php esc_attr_e( 'Collapse all', 'eb-course-exp' ); ?>"
							>
								<span class="courseexp-section-block__expand-all-text"><?php esc_html_e( 'Expand all', 'eb-course-exp' ); ?></span>
							</button>
						<?php endif; ?>
					</div>

					<div class="courseexp-section-block__body" id="<?php echo esc_attr( $body_id ); ?>"<?php echo $is_first ? '' : ' hidden'; ?>>
						<?php courseexp_render_section_body_inner( $section, $courseexp_ctx ); ?>
					</div>
				</section>
			<?php endif; ?>
		<?php endforeach; ?>
	<?php elseif ( is_wp_error( $course_data ) ) : ?>
		<div class="courseexp-error">
			<p><?php esc_html_e( 'Unable to load course content. Please try again later.', 'eb-course-exp' ); ?></p>
		</div>
	<?php else : ?>
		<div class="courseexp-sections__empty">
			<p><?php esc_html_e( 'This course has no content yet.', 'eb-course-exp' ); ?></p>
		</div>
	<?php endif; ?>
</div>
