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

$courseexp_ctx = array(
	'enable_completion'   => ! empty( $completion_settings['enablecompletion'] ),
	'show_conditions'     => ! empty( $completion_settings['showcompletionconditions'] ),
	'uses_indentation'    => ! empty( $courseformat['usesindentation'] ),
	'show_activity_dates' => $has_data && ! empty( $course_data['showactivitydates'] ),
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

if ( ! function_exists( 'courseexp_render_completion_control' ) ) {
	/**
	 * Render the left-hand completion control for an activity row.
	 *
	 * Visual only — no completion write is wired in this pass.
	 *
	 * @param array $completion Activity completion sub-block.
	 * @param int   $cmid       Course module id.
	 * @param array $ctx        Course-level rendering context.
	 * @return void
	 */
	function courseexp_render_completion_control( array $completion, int $cmid, array $ctx ): void {
		if ( empty( $ctx['enable_completion'] ) ) {
			return;
		}

		$tracked = ! empty( $completion['tracked'] );
		if ( ! $tracked ) {
			return;
		}

		$mode     = isset( $completion['mode'] ) ? (int) $completion['mode'] : 0;
		$complete = ! empty( $completion['isoverallcomplete'] );
		$override = ! empty( $completion['overrideby'] );

		if ( $complete ) {
			$is_manual = ( 1 === $mode && ! empty( $completion['canmanuallycomplete'] ) );
			$tag       = $is_manual ? 'button' : 'span';
			?>
			<<?php echo esc_html( $tag ); ?>
				class="courseexp-completion courseexp-completion--done<?php echo $is_manual ? '' : ' is-readonly'; ?>"
				data-cmid="<?php echo esc_attr( $cmid ); ?>"
				<?php echo 'button' === $tag ? 'type="button"' : ''; ?>
			>
				<span class="courseexp-completion__icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
				</span>
				<span class="courseexp-completion__text"><?php esc_html_e( 'Done', 'eb-course-exp' ); ?></span>
			</<?php echo esc_html( $tag ); ?>>
			<?php
			return;
		}

		if ( 1 === $mode ) {
			$can_complete = ! empty( $completion['canmanuallycomplete'] );
			if ( $can_complete ) {
				?>
				<button type="button" class="courseexp-completion courseexp-completion--markdone" data-cmid="<?php echo esc_attr( $cmid ); ?>">
					<span class="courseexp-completion__text"><?php esc_html_e( 'Mark as done', 'eb-course-exp' ); ?></span>
				</button>
				<?php
			} else {
				?>
				<span class="courseexp-completion courseexp-completion--markdone is-readonly">
					<span class="courseexp-completion__text"><?php esc_html_e( 'To do', 'eb-course-exp' ); ?></span>
				</span>
				<?php
			}

			if ( $override ) {
				courseexp_render_override_note();
			}
			return;
		}

		$details = isset( $completion['details'] ) && is_array( $completion['details'] ) ? $completion['details'] : array();

		if ( ! empty( $ctx['show_conditions'] ) && ! empty( $details ) ) {
			?>
			<details class="courseexp-completion courseexp-completion--todo courseexp-todo">
				<summary class="courseexp-todo__trigger">
					<span class="courseexp-todo__label"><?php esc_html_e( 'To do', 'eb-course-exp' ); ?></span>
					<span class="courseexp-todo__caret" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
					</span>
				</summary>
				<div class="courseexp-todo__panel">
					<p class="courseexp-todo__intro"><?php esc_html_e( 'You must', 'eb-course-exp' ); ?></p>
					<ul class="courseexp-todo__list">
						<?php foreach ( $details as $detail ) : ?>
							<?php
							$detail      = (array) $detail;
							$description = isset( $detail['description'] ) ? $detail['description'] : '';
							?>
							<li class="courseexp-todo__item"><?php echo esc_html( $description ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</details>
			<?php
		} else {
			?>
			<span class="courseexp-completion courseexp-completion--todo is-readonly">
				<span class="courseexp-completion__text"><?php esc_html_e( 'To do', 'eb-course-exp' ); ?></span>
			</span>
			<?php
		}

		if ( $override ) {
			courseexp_render_override_note();
		}
	}
}

if ( ! function_exists( 'courseexp_render_override_note' ) ) {
	/**
	 * Render the "set by a teacher" annotation for an overridden completion.
	 *
	 * @return void
	 */
	function courseexp_render_override_note(): void {
		?>
		<span class="courseexp-completion__override"><?php esc_html_e( 'Set by a teacher', 'eb-course-exp' ); ?></span>
		<?php
	}
}

if ( ! function_exists( 'courseexp_completion_is_visible' ) ) {
	/**
	 * Whether a completion control would render for the given activity.
	 *
	 * Mirrors the gate in courseexp_render_completion_control().
	 *
	 * @param array $completion Activity completion sub-block.
	 * @param array $ctx        Course-level rendering context.
	 * @return bool
	 */
	function courseexp_completion_is_visible( array $completion, array $ctx ): bool {
		return ! empty( $ctx['enable_completion'] ) && ! empty( $completion['tracked'] );
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
		$available    = ! isset( $activity['available'] ) || (bool) $activity['available'];
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
		<div class="<?php echo esc_attr( $inline_class ); ?>">
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
		$cmid        = isset( $activity['cmid'] ) ? (int) $activity['cmid'] : 0;
		$name        = isset( $activity['name'] ) ? $activity['name'] : '';
		$icon        = isset( $activity['icon'] ) ? $activity['icon'] : '';
		$indent      = isset( $activity['indent'] ) ? (int) $activity['indent'] : 0;
		$available   = ! isset( $activity['available'] ) || (bool) $activity['available'];
		$avail_info  = isset( $activity['availabilityinfo'] ) ? $activity['availabilityinfo'] : '';
		$show_desc   = ! empty( $activity['showdescription'] );
		$description = isset( $activity['description'] ) ? $activity['description'] : '';
		$completion  = isset( $activity['completion'] ) ? (array) $activity['completion'] : array();

		$indent_attr = ! empty( $ctx['uses_indentation'] ) ? $indent : 0;

		$row_classes = array( 'courseexp-activity-row' );
		if ( ! $available ) {
			$row_classes[] = 'is-locked';
		}
		$row_class = implode( ' ', array_map( 'sanitize_html_class', $row_classes ) );
		?>
		<li class="<?php echo esc_attr( $row_class ); ?>" data-activity-id="<?php echo esc_attr( $cmid ); ?>" data-indent="<?php echo esc_attr( $indent_attr ); ?>">
			<div class="courseexp-activity-row__inner">
				<div class="courseexp-activity-row__top">
					<div class="courseexp-activity-row__main">
						<?php if ( ! empty( $icon ) ) : ?>
							<span class="courseexp-activity-row__icon" aria-hidden="true">
								<img src="<?php echo esc_url( $icon ); ?>" alt="" width="24" height="24" loading="lazy" />
							</span>
						<?php endif; ?>

						<?php if ( $available ) : ?>
							<a href="#activity-<?php echo esc_attr( $cmid ); ?>" class="courseexp-activity-row__name">
								<?php echo esc_html( $name ); ?>
							</a>
						<?php else : ?>
							<span class="courseexp-activity-row__name courseexp-activity-row__name--locked">
								<?php echo esc_html( $name ); ?>
							</span>
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
			$children = isset( $activity['children'] ) && is_array( $activity['children'] ) ? $activity['children'] : array();
			$mode     = isset( $activity['rendermode'] ) ? $activity['rendermode'] : '';

			$is_inline = 'inline' === $mode && empty( $children );

			if ( $is_inline || ! empty( $children ) ) {
				if ( $open_list ) {
					echo '</ul>';
					$open_list = false;
				}
			}

			if ( ! empty( $children ) ) {
				$sub_name = isset( $activity['name'] ) ? $activity['name'] : '';
				?>
				<div class="courseexp-subsection">
					<?php if ( '' !== trim( (string) $sub_name ) ) : ?>
						<h3 class="courseexp-subsection__title"><?php echo esc_html( $sub_name ); ?></h3>
					<?php endif; ?>
					<ul class="courseexp-activity-list">
						<?php
						foreach ( $children as $child ) {
							courseexp_render_activity_row( (array) $child, $ctx );
						}
						?>
					</ul>
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
?>

<div class="courseexp-sections" id="courseexp-sections">
	<?php if ( $has_data && ! empty( $sections ) ) : ?>
		<?php foreach ( $sections as $section_index => $section ) : ?>
			<?php
			$section    = (array) $section;
			$section_id = isset( $section['id'] ) ? (int) $section['id'] : (int) $section_index;
			$activities = isset( $section['activities'] ) ? $section['activities'] : array();
			$summary    = isset( $section['summary'] ) ? $section['summary'] : '';
			$is_visible = ! isset( $section['visible'] ) || (bool) $section['visible'];
			$is_current = ! empty( $section['current'] );
			$is_first   = ( 0 === $section_index );
			$body_id    = 'courseexp-section-body-' . $section_id;
			$toggle_id  = 'courseexp-section-toggle-' . $section_id;
			$title_id   = 'courseexp-section-title-' . $section_id;

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
			if ( $is_current ) {
				$section_classes[] = 'is-current';
			}
			if ( $is_first ) {
				$section_classes[] = 'is-expanded';
			}
			$section_class = implode( ' ', array_map( 'sanitize_html_class', $section_classes ) );
			?>
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
					<h2 class="courseexp-section-block__title" id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( $section_name ); ?></h2>

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
					<?php if ( ! $is_visible ) : ?>
						<p class="courseexp-section-block__stub-note"><?php esc_html_e( 'Not available', 'eb-course-exp' ); ?></p>
					<?php else : ?>
						<?php if ( '' !== trim( (string) $summary ) ) : ?>
							<div class="courseexp-section-block__summary">
								<?php courseexp_render_trusted_html( $summary ); ?>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $activities ) ) : ?>
							<div class="courseexp-section-block__items">
								<?php courseexp_render_section_items( $activities, $courseexp_ctx ); ?>
							</div>
						<?php else : ?>
							<p class="courseexp-section-block__empty"><?php esc_html_e( 'No content in this section yet.', 'eb-course-exp' ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</section>
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
