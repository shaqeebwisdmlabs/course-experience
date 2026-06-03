(function () {
	'use strict';

	const STORAGE_KEY = 'courseexp-sidebar-collapsed';

	function getState() {
		try {
			return localStorage.getItem(STORAGE_KEY) === 'true';
		} catch (e) {
			return false;
		}
	}

	function setState(isCollapsed) {
		try {
			localStorage.setItem(STORAGE_KEY, isCollapsed ? 'true' : 'false');
		} catch (e) {}
	}

	function initSidebar() {
		const sidebar = document.getElementById('courseexp-sidebar');
		const toggleBtn = document.getElementById('courseexp-sidebar-toggle');
		const floatingBtn = document.getElementById('courseexp-floating-toggle');
		const body = document.body;

		if (!sidebar || !toggleBtn || !floatingBtn) {
			return;
		}

		function collapse() {
			body.classList.add('courseexp-sidebar-collapsed');
			body.classList.remove('courseexp-sidebar-open');
			sidebar.classList.remove('is-open');
			const overlay = document.getElementById('courseexp-sidebar-overlay');
			if (overlay) {
				overlay.classList.remove('is-visible');
			}
			toggleBtn.setAttribute('aria-expanded', 'false');
			floatingBtn.setAttribute('aria-expanded', 'false');
			setState(true);
		}

		function expand() {
			body.classList.remove('courseexp-sidebar-collapsed');
			toggleBtn.setAttribute('aria-expanded', 'true');
			floatingBtn.setAttribute('aria-expanded', 'true');
			setState(false);
		}

		toggleBtn.addEventListener('click', collapse);
		floatingBtn.addEventListener('click', expand);

		if (getState()) {
			collapse();
		} else {
			expand();
		}
	}

	function initMobileSidebar() {
		const sidebar = document.getElementById('courseexp-sidebar');
		const mobileToggle = document.getElementById('courseexp-mobile-toggle');
		const overlay = document.getElementById('courseexp-sidebar-overlay');
		const body = document.body;

		if (!sidebar) {
			return;
		}

		function open() {
			sidebar.classList.add('is-open');
			body.classList.add('courseexp-sidebar-open');
			body.classList.remove('courseexp-sidebar-collapsed');
			if (overlay) {
				overlay.classList.add('is-visible');
			}
			setState(false);
		}

		function close() {
			sidebar.classList.remove('is-open');
			body.classList.remove('courseexp-sidebar-open');
			if (overlay) {
				overlay.classList.remove('is-visible');
			}
			setState(true);
		}

		if (mobileToggle) {
			mobileToggle.addEventListener('click', open);
		}

		if (overlay) {
			overlay.addEventListener('click', close);
		}

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && sidebar.classList.contains('is-open')) {
				close();
			}
		});
	}

	function init() {
		document.body.classList.add('courseexp-page');
		initSidebar();
		initMobileSidebar();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
