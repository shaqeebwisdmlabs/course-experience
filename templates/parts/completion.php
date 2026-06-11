<?php
/**
 * Completion control template helpers.
 *
 * Renders the activity completion controls shared across the section rows,
 * inline pulse items and the activity page: the interactive manual toggle
 * (Mark as done / Done), the automatic read-only status, and the conditions
 * dropdown. The manual toggle posts to admin-post.php and redirects back.
 *
 * @package EB_Course_Experience
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'courseexp_render_condition_status' ) ) {
	/**
	 * Render a completion-condition status icon.
	 *
	 * Mirrors the sidebar activity status icons for visual consistency:
	 * green check when met, an empty circle when still to do, a red cross on fail.
	 *
	 * @param int $status Condition status: 0 incomplete, 1 complete, 2 complete-pass, 3 complete-fail.
	 * @return void
	 */
	function courseexp_render_condition_status( int $status ): void {
		if ( 1 === $status || 2 === $status ) {
			?>
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
			<?php
			return;
		}

		if ( 3 === $status ) {
			?>
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
			<?php
			return;
		}
		?>
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/></svg>
		<?php
	}
}

if ( ! function_exists( 'courseexp_render_completion_control' ) ) {
	/**
	 * Render the completion control for an activity.
	 *
	 * Manual activities get an interactive toggle (a self-submitting form);
	 * automatic ones render read-only status.
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

		$details        = isset( $completion['details'] ) && is_array( $completion['details'] ) ? $completion['details'] : array();
		$has_conditions = 2 === $mode && ! empty( $ctx['show_conditions'] ) && ! empty( $details );

		if ( $has_conditions ) {
			courseexp_render_todo_dropdown( $details, $complete );
			if ( $override ) {
				courseexp_render_override_note();
			}
			return;
		}

		if ( 1 === $mode && ! empty( $completion['canmanuallycomplete'] ) ) {
			courseexp_render_manual_toggle( $cmid, $complete );
			if ( $override ) {
				courseexp_render_override_note();
			}
			return;
		}

		if ( $complete ) {
			?>
			<span class="courseexp-completion courseexp-completion--done is-readonly">
				<span class="courseexp-completion__icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
				</span>
				<span class="courseexp-completion__text"><?php esc_html_e( 'Done', 'eb-course-exp' ); ?></span>
			</span>
			<?php
			if ( $override ) {
				courseexp_render_override_note();
			}
			return;
		}

		if ( ! empty( $details ) ) {
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

if ( ! function_exists( 'courseexp_render_manual_toggle' ) ) {
	/**
	 * Render the manual completion control as a self-submitting form.
	 *
	 * Posts to admin-post.php, which writes the completion and redirects back, so
	 * the page re-renders from fresh data (unlocks and progress update for free).
	 * No JavaScript is required.
	 *
	 * @param int  $cmid     Course module id.
	 * @param bool $complete Whether the activity is currently complete.
	 * @return void
	 */
	function courseexp_render_manual_toggle( int $cmid, bool $complete ): void {
		$classes = 'courseexp-completion courseexp-completion--manual';
		if ( $complete ) {
			$classes .= ' is-complete';
		}
		$target_state = $complete ? 0 : 1;
		?>
		<form class="courseexp-completion-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'courseexp_set_completion_' . $cmid ); ?>
			<?php wp_referer_field(); ?>
			<input type="hidden" name="action" value="courseexp_set_completion" />
			<input type="hidden" name="cmid" value="<?php echo esc_attr( $cmid ); ?>" />
			<input type="hidden" name="state" value="<?php echo esc_attr( $target_state ); ?>" />
			<button type="submit" class="<?php echo esc_attr( $classes ); ?>" aria-pressed="<?php echo $complete ? 'true' : 'false'; ?>">
				<span class="courseexp-completion__icon" aria-hidden="true">
					<?php if ( $complete ) : ?>
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
					<?php else : ?>
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/></svg>
					<?php endif; ?>
				</span>
				<span class="courseexp-completion__text"><?php echo esc_html( $complete ? __( 'Done', 'eb-course-exp' ) : __( 'Mark as done', 'eb-course-exp' ) ); ?></span>
			</button>
		</form>
		<?php
	}
}

if ( ! function_exists( 'courseexp_render_todo_dropdown' ) ) {
	/**
	 * Render the expandable completion-conditions control.
	 *
	 * Used for automatic activities that expose a conditions list, in both the
	 * "to do" and completed states. When complete, the trigger shows the done
	 * icon and label while the per-condition list stays reviewable in the panel.
	 *
	 * @param array $details  Per-condition detail blocks.
	 * @param bool  $complete Whether the activity is overall complete.
	 * @return void
	 */
	function courseexp_render_todo_dropdown( array $details, bool $complete ): void {
		$details_class = 'courseexp-completion courseexp-completion--todo courseexp-todo';
		if ( $complete ) {
			$details_class .= ' is-done';
		}
		?>
		<details class="<?php echo esc_attr( $details_class ); ?>">
			<summary class="courseexp-todo__trigger">
				<?php if ( $complete ) : ?>
					<span class="courseexp-completion__icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
					</span>
				<?php endif; ?>
				<span class="courseexp-todo__label"><?php echo $complete ? esc_html__( 'Done', 'eb-course-exp' ) : esc_html__( 'To do', 'eb-course-exp' ); ?></span>
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
						$status      = isset( $detail['status'] ) ? (int) $detail['status'] : 0;
						if ( 1 === $status || 2 === $status ) {
							$item_class = 'courseexp-todo__item is-complete';
						} elseif ( 3 === $status ) {
							$item_class = 'courseexp-todo__item is-fail';
						} else {
							$item_class = 'courseexp-todo__item is-incomplete';
						}
						?>
						<li class="<?php echo esc_attr( $item_class ); ?>">
							<span class="courseexp-todo__item-status" aria-hidden="true">
								<?php courseexp_render_condition_status( $status ); ?>
							</span>
							<span class="courseexp-todo__item-text"><?php echo esc_html( $description ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</details>
		<?php
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
		if ( empty( $ctx['enable_completion'] ) || empty( $completion['tracked'] ) ) {
			return false;
		}

		$mode = isset( $completion['mode'] ) ? (int) $completion['mode'] : 0;
		if ( ! empty( $completion['isoverallcomplete'] ) ) {
			return true;
		}
		if ( 1 === $mode && ! empty( $completion['canmanuallycomplete'] ) ) {
			return true;
		}

		$details = isset( $completion['details'] ) && is_array( $completion['details'] ) ? $completion['details'] : array();
		return ! empty( $details );
	}
}
