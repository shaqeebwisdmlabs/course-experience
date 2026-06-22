<?php
/**
 * Shared template helpers for the course experience parts.
 *
 * @package EB_Course_Experience
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'courseexp_render_body_class' ) ) {
	/**
	 * Guarantee the page-scope body class regardless of theme.
	 *
	 * All of our CSS is namespaced under `.courseexp-page`. We add that class via
	 * the `body_class` filter, but some themes hardcode their `<body>` tag and
	 * never call WordPress's body_class(), so the filter never runs. This prints a
	 * tiny synchronous script immediately after the opening body tag (after
	 * get_header()) to add the class on the client side. It executes before the
	 * rest of the body is parsed, so the namespaced styles apply with no flash of
	 * unstyled content.
	 *
	 * @since 1.2.8
	 * @return void
	 */
	function courseexp_render_body_class(): void {
		wp_print_inline_script_tag( "document.body.classList.add('courseexp-page');" );
	}
}

if ( ! function_exists( 'courseexp_activity_moduledata' ) ) {
	/**
	 * Decode an activity's moduledata bag, which the API may deliver as JSON.
	 *
	 * @since 1.0.0
	 * @param array $activity Activity payload.
	 * @return array Decoded moduledata.
	 */
	function courseexp_activity_moduledata( array $activity ): array {
		$moduledata = isset( $activity['moduledata'] ) ? $activity['moduledata'] : array();
		if ( is_string( $moduledata ) ) {
			$moduledata = json_decode( $moduledata, true );
		}

		return is_array( $moduledata ) ? $moduledata : array();
	}
}

if ( ! function_exists( 'courseexp_display_mode' ) ) {
	/**
	 * Resolve a url or resource activity's display mode from moduledata.
	 *
	 * Prefers the already-resolved value so an "automatic" setting is honoured
	 * without re-deriving it. Matches Moodle's RESOURCELIB_DISPLAY_* constants
	 * (1 embed, 4 force download, 5 open, 6 popup; 0 automatic).
	 *
	 * @since 1.0.0
	 * @param array $moduledata Decoded moduledata bag.
	 * @return int Display constant.
	 */
	function courseexp_display_mode( array $moduledata ): int {
		if ( ! empty( $moduledata['displayresolved'] ) ) {
			return (int) $moduledata['displayresolved'];
		}

		return isset( $moduledata['display'] ) ? (int) $moduledata['display'] : 0;
	}
}

if ( ! function_exists( 'courseexp_activity_is_unavailable' ) ) {
	/**
	 * Whether an activity is a linked activity the user cannot reach.
	 *
	 * The Moodle course-activity-link service returns a placeholder block with
	 * modname 'unavailable' when the linked activity is missing, hidden, or in a
	 * course the user is not enrolled in. Such a block reports available=true and
	 * rendermode=inline, so it is detected by modname alone and then rendered in
	 * the locked style with its availabilityinfo as the reason text.
	 *
	 * @since 1.0.0
	 * @param array $activity Activity payload.
	 * @return bool
	 */
	function courseexp_activity_is_unavailable( array $activity ): bool {
		$modname = isset( $activity['modname'] ) ? (string) $activity['modname'] : '';

		return 'unavailable' === $modname;
	}
}

if ( ! function_exists( 'courseexp_activity_on_course_page' ) ) {
	/**
	 * Whether an activity should appear on the course page / in the listing.
	 *
	 * Moodle sets uservisibleoncoursepage=false for modules the user must not see
	 * there (hidden, deleted, fully-restricted-and-hidden, or stealth). Such items
	 * are skipped entirely — unlike a "show greyed" restriction, which keeps
	 * uservisibleoncoursepage=true and is rendered locked.
	 *
	 * @since 1.2.8
	 * @param array $activity Activity payload.
	 * @return bool
	 */
	function courseexp_activity_on_course_page( array $activity ): bool {
		if ( isset( $activity['uservisibleoncoursepage'] ) ) {
			return (bool) $activity['uservisibleoncoursepage'];
		}

		return true;
	}
}

if ( ! function_exists( 'courseexp_activity_is_subsection' ) ) {
	/**
	 * Whether an activity block is a subsection container.
	 *
	 * The Moodle course-structure service delivers a subsection as an activity
	 * with modname 'subsection' whose nested activities live in its `children`
	 * array. Detected by modname (not children presence) so the signal is
	 * explicit and stable.
	 *
	 * @since 1.2.8
	 * @param array $activity Activity payload.
	 * @return bool
	 */
	function courseexp_activity_is_subsection( array $activity ): bool {
		$modname = isset( $activity['modname'] ) ? (string) $activity['modname'] : '';

		return 'subsection' === $modname;
	}
}

if ( ! function_exists( 'courseexp_activities_contain_cmid' ) ) {
	/**
	 * Whether an activity list contains a given cmid, descending into subsections.
	 *
	 * @since 1.2.8
	 * @param array $activities Activity blocks (may include subsection children).
	 * @param int   $cmid       Course module id to find.
	 * @return bool
	 */
	function courseexp_activities_contain_cmid( array $activities, int $cmid ): bool {
		foreach ( $activities as $activity ) {
			$activity = (array) $activity;
			if ( isset( $activity['cmid'] ) && (int) $activity['cmid'] === $cmid ) {
				return true;
			}
			if ( ! empty( $activity['children'] ) && is_array( $activity['children'] )
				&& courseexp_activities_contain_cmid( $activity['children'], $cmid ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'courseexp_block_availability' ) ) {
	/**
	 * Read an item's access-restriction state, matching the activity model.
	 *
	 * Sections, subsections and activities all carry the same `available` flag and
	 * `availabilityinfo` reason; this reads them in one place so restrictions are
	 * detected by the flag rather than inferred from an empty content list.
	 *
	 * @since 1.2.8
	 * @param array $block Section, subsection or activity payload.
	 * @return array{available:bool,info:string}
	 */
	function courseexp_block_availability( array $block ): array {
		return array(
			'available' => ! isset( $block['available'] ) || (bool) $block['available'],
			'info'      => isset( $block['availabilityinfo'] ) ? trim( (string) $block['availabilityinfo'] ) : '',
		);
	}
}

if ( ! function_exists( 'courseexp_render_lock_icon' ) ) {
	/**
	 * Render the restriction lock icon shown beside a restricted title.
	 *
	 * @since 1.2.8
	 * @param string $icon_class Element class for the wrapping span.
	 * @param int    $size       Icon width/height in pixels.
	 * @return void
	 */
	function courseexp_render_lock_icon( string $icon_class, int $size = 18 ): void {
		?>
		<span class="<?php echo esc_attr( $icon_class ); ?>" title="<?php esc_attr_e( 'Locked', 'eb-course-exp' ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" width="<?php echo esc_attr( $size ); ?>" height="<?php echo esc_attr( $size ); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="16" r="1"/><rect x="3" y="10" width="18" height="12" rx="2"/><path d="M7 10V7a5 5 0 0 1 10 0v3"/></svg>
		</span>
		<?php
	}
}

if ( ! function_exists( 'courseexp_render_restricted_notice' ) ) {
	/**
	 * Render a restriction reason in place of restricted section/subsection content.
	 *
	 * @since 1.2.8
	 * @param string $info The Moodle availabilityinfo reason (may be empty).
	 * @return void
	 */
	function courseexp_render_restricted_notice( string $info ): void {
		?>
		<div class="courseexp-restricted-note">
			<span class="courseexp-restricted-note__icon" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="16" r="1"/><rect x="3" y="10" width="18" height="12" rx="2"/><path d="M7 10V7a5 5 0 0 1 10 0v3"/></svg>
			</span>
			<span class="courseexp-restricted-note__text courseexp-prose">
				<?php
				if ( '' !== trim( $info ) ) {
					echo wp_kses_post( $info );
				} else {
					esc_html_e( 'Not available', 'eb-course-exp' );
				}
				?>
			</span>
		</div>
		<?php
	}
}

if ( ! function_exists( 'courseexp_activity_dates' ) ) {
	/**
	 * Extract an activity's display dates from the API `dates` array.
	 *
	 * Moodle delivers each entry with an already-localised `label` (e.g. "Opened:",
	 * "Closes:", "Due:"), a Unix `timestamp`, and a `formatted` string rendered in
	 * the user's Moodle timezone. Entries without a positive timestamp are dropped
	 * so only set dates surface.
	 *
	 * @since 1.2.9
	 * @param array $activity Activity payload.
	 * @return array[] List of { label, timestamp, formatted } entries with a usable timestamp.
	 */
	function courseexp_activity_dates( array $activity ): array {
		$dates = isset( $activity['dates'] ) ? $activity['dates'] : array();
		if ( is_string( $dates ) ) {
			$dates = json_decode( $dates, true );
		}
		if ( ! is_array( $dates ) ) {
			return array();
		}

		$out = array();
		foreach ( $dates as $date ) {
			$date      = (array) $date;
			$timestamp = isset( $date['timestamp'] ) ? (int) $date['timestamp'] : 0;
			if ( $timestamp <= 0 ) {
				continue;
			}
			$out[] = array(
				'label'     => isset( $date['label'] ) ? (string) $date['label'] : '',
				'timestamp' => $timestamp,
				'formatted' => isset( $date['formatted'] ) ? (string) $date['formatted'] : '',
			);
		}

		return $out;
	}
}

if ( ! function_exists( 'courseexp_render_activity_dates' ) ) {
	/**
	 * Render an activity's dates block (label + formatted date), e.g. for
	 * assignment and choice activities whose description lives inside the iframe.
	 *
	 * @since 1.2.9
	 * @param array $activity Activity payload.
	 * @return void
	 */
	function courseexp_render_activity_dates( array $activity ): void {
		$dates = courseexp_activity_dates( $activity );
		if ( empty( $dates ) ) {
			return;
		}

		/* translators: date/time format used when the API does not supply a pre-formatted date, e.g. "Wednesday, 10 June 2026, 8:13 AM". */
		$format = _x( 'l, j F Y, g:i A', 'activity date format', 'eb-course-exp' );
		?>
		<div class="courseexp-activity-dates">
			<?php foreach ( $dates as $date ) : ?>
				<?php $value = '' !== trim( $date['formatted'] ) ? $date['formatted'] : wp_date( $format, $date['timestamp'] ); ?>
				<div class="courseexp-activity-dates__item">
					<?php if ( '' !== trim( $date['label'] ) ) : ?>
						<span class="courseexp-activity-dates__label"><?php echo esc_html( $date['label'] ); ?></span>
					<?php endif; ?>
					<span class="courseexp-activity-dates__value"><?php echo esc_html( $value ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'courseexp_book_chapter_numbers' ) ) {
	/**
	 * Compute hierarchical chapter numbers ("1", "1.1", "2") for a book TOC.
	 *
	 * Mirrors Moodle's two-level book numbering: top-level chapters take a running
	 * integer, subchapters take "<chapter>.<sub>". Hidden chapters are skipped and
	 * do not consume a number, matching what the learner sees.
	 *
	 * @since 1.2.9
	 * @param array $chapters Ordered chapter blocks.
	 * @return array<int,string> Map of chapter index to its number string.
	 */
	function courseexp_book_chapter_numbers( array $chapters ): array {
		$numbers = array();
		$top     = 0;
		$sub     = 0;

		foreach ( $chapters as $index => $chapter ) {
			$chapter = (array) $chapter;
			if ( ! empty( $chapter['hidden'] ) ) {
				continue;
			}
			if ( ! empty( $chapter['subchapter'] ) ) {
				++$sub;
				$numbers[ $index ] = $top . '.' . $sub;
			} else {
				++$top;
				$sub               = 0;
				$numbers[ $index ] = (string) $top;
			}
		}

		return $numbers;
	}
}

if ( ! function_exists( 'courseexp_activity_opens_externally' ) ) {
	/**
	 * Whether a course-listing link should open the resource in a new tab instead
	 * of routing through the in-WP activity page.
	 *
	 * A url activity set to open in a popup (display 6) is excluded: its popup is
	 * launched from the activity page, so the listing link must lead there first.
	 * A resource is also excluded: its external URL is a token-protected Moodle
	 * file URL, so it must route through the activity page where the token is
	 * appended and the display mode is honoured.
	 *
	 * @since 1.0.0
	 * @param array $activity Activity payload.
	 * @return bool
	 */
	function courseexp_activity_opens_externally( array $activity ): bool {
		$rendermode   = isset( $activity['rendermode'] ) ? (string) $activity['rendermode'] : '';
		$external_url = isset( $activity['externalurl'] ) ? (string) $activity['externalurl'] : '';
		$modname      = isset( $activity['modname'] ) ? (string) $activity['modname'] : '';

		if ( 'external' !== $rendermode || '' === $external_url ) {
			return false;
		}

		if ( 'resource' === $modname ) {
			return false;
		}

		$is_popup_url = ( 'url' === $modname && 6 === courseexp_display_mode( courseexp_activity_moduledata( $activity ) ) );

		return ! $is_popup_url;
	}
}

if ( ! function_exists( 'courseexp_set_document_title' ) ) {
	/**
	 * Set the browser document title for a course-experience page.
	 *
	 * Templates compute their course/section/activity names before get_header()
	 * runs, so calling this beforehand lets the value drive the <title> tag with
	 * no extra queries. Returns the title verbatim via pre_get_document_title.
	 *
	 * @since 1.2.9
	 * @param string $title Fully composed document title.
	 * @return void
	 */
	function courseexp_set_document_title( string $title ): void {
		$title = esc_html( $title );
		add_filter(
			'pre_get_document_title',
			static function () use ( $title ) {
				return $title;
			}
		);
	}
}
