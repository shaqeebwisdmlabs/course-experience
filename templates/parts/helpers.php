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

if ( ! function_exists( 'courseexp_url_display' ) ) {
	/**
	 * Resolve a url activity's display mode from moduledata.
	 *
	 * Prefers the already-resolved value so an "automatic" setting is honoured
	 * without re-deriving it. Matches Moodle's RESOURCELIB_DISPLAY_* constants
	 * (1 embed, 5 open, 6 popup; 0 automatic).
	 *
	 * @since 1.0.0
	 * @param array $moduledata Decoded moduledata bag.
	 * @return int Display constant.
	 */
	function courseexp_url_display( array $moduledata ): int {
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

if ( ! function_exists( 'courseexp_activity_opens_externally' ) ) {
	/**
	 * Whether a course-listing link should open the resource in a new tab instead
	 * of routing through the in-WP activity page.
	 *
	 * A url activity set to open in a popup (display 6) is excluded: its popup is
	 * launched from the activity page, so the listing link must lead there first.
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

		$is_popup_url = ( 'url' === $modname && 6 === courseexp_url_display( courseexp_activity_moduledata( $activity ) ) );

		return ! $is_popup_url;
	}
}
