/**
 * Course Experience - Public Scripts
 *
 * Measures the theme's fixed site header at runtime and writes its height into
 * the --courseexp-header-height CSS variable, so sticky offsets and the body
 * top padding adapt to any theme instead of relying on a hard-coded value.
 *
 * @package Course_Experience
 */

(function () {
	'use strict';

	const HEADER_SELECTORS = 'header, .site-header, #masthead, .site-navbar';

	/**
	 * Find the theme's site header, ignoring any bare <header> rendered inside
	 * the course content (those are course markup, not the site chrome).
	 *
	 * @return {Element|null}
	 */
	function findSiteHeader() {
		const candidates = document.querySelectorAll(HEADER_SELECTORS);
		for (let i = 0; i < candidates.length; i++) {
			const el = candidates[i];
			if (el.closest('.courseexp-layout')) {
				continue;
			}
			if (el.offsetParent === null && el.offsetHeight === 0) {
				continue;
			}
			return el;
		}
		return null;
	}

	function applyHeaderHeight(header) {
		if (!header) {
			return;
		}
		const height = Math.round(header.getBoundingClientRect().height);
		if (height > 0) {
			document.documentElement.style.setProperty(
				'--courseexp-header-height',
				height + 'px'
			);
		}
	}

	function init() {
		const header = findSiteHeader();
		if (!header) {
			return;
		}

		applyHeaderHeight(header);

		window.addEventListener('load', function () {
			applyHeaderHeight(header);
		});

		if (typeof ResizeObserver === 'function') {
			const observer = new ResizeObserver(function () {
				applyHeaderHeight(header);
			});
			observer.observe(header);
		} else {
			window.addEventListener('resize', function () {
				applyHeaderHeight(header);
			});
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
