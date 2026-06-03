<?php
/**
 * Sidebar Template Part
 *
 * @package EB_Course_Experience
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<button
	class="courseexp-mobile-toggle"
	id="courseexp-mobile-toggle"
	aria-label="<?php esc_attr_e( 'Open course menu', 'eb-course-exp' ); ?>"
	aria-controls="courseexp-sidebar"
>
	<span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-panel-left-open-icon lucide-panel-left-open"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/><path d="m14 9 3 3-3 3"/></svg></span>
</button>

<button
	class="courseexp-floating-toggle"
	id="courseexp-floating-toggle"
	aria-label="<?php esc_attr_e( 'Expand sidebar', 'eb-course-exp' ); ?>"
	aria-controls="courseexp-sidebar"
	aria-expanded="false"
>
	<span class="courseexp-floating-toggle__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-panel-left-open-icon lucide-panel-left-open"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/><path d="m14 9 3 3-3 3"/></svg></span>
</button>

<div class="courseexp-sidebar-overlay" id="courseexp-sidebar-overlay"></div>

<aside class="courseexp-sidebar" id="courseexp-sidebar" role="navigation" aria-label="<?php esc_attr_e( 'Course content navigation', 'eb-course-exp' ); ?>">
	<div class="courseexp-sidebar__header">
		<h2 class="courseexp-sidebar__title"><?php esc_html_e( 'Course Content', 'eb-course-exp' ); ?></h2>
		<button
			class="courseexp-sidebar__toggle"
			id="courseexp-sidebar-toggle"
			aria-expanded="true"
			aria-controls="courseexp-sidebar"
			aria-label="<?php esc_attr_e( 'Collapse sidebar', 'eb-course-exp' ); ?>"
		>
			<span class="courseexp-sidebar__toggle-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-panel-left-close-icon lucide-panel-left-close"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/><path d="m16 15-3-3 3-3"/></svg></span>
		</button>
	</div>
	<div class="courseexp-sidebar__content">
		<nav class="courseexp-course-nav">
			<p class="courseexp-placeholder-text"><?php esc_html_e( 'Course sections and activities will appear here.', 'eb-course-exp' ); ?></p>
		</nav>
	</div>
</aside>
