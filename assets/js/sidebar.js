(function () {
	'use strict';

	const STORAGE_KEY = 'courseexp-sidebar-collapsed';
	const ACCORDION_STORAGE_KEY = 'courseexp-accordion-state';

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

	function getAccordionState() {
		try {
			const saved = localStorage.getItem(ACCORDION_STORAGE_KEY);
			return saved ? JSON.parse(saved) : {};
		} catch (e) {
			return {};
		}
	}

	function saveAccordionState(state) {
		try {
			localStorage.setItem(ACCORDION_STORAGE_KEY, JSON.stringify(state));
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
		const closeBtn = document.getElementById('courseexp-sidebar-toggle');
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
			if (closeBtn) {
				closeBtn.focus();
			}
		}

		function close() {
			sidebar.classList.remove('is-open');
			body.classList.remove('courseexp-sidebar-open');
			if (overlay) {
				overlay.classList.remove('is-visible');
			}
			setState(true);
			if (mobileToggle) {
				mobileToggle.focus();
			}
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

	function initAccordion() {
		const accordion = document.getElementById('courseexp-accordion');
		const accordionToggleBtn = document.getElementById('courseexp-accordion-toggle');

		if (!accordion) {
			return;
		}

		const sections = accordion.querySelectorAll('.courseexp-section');
		const accordionState = getAccordionState();

		function setSectionExpanded(section, expanded, persist) {
			const toggle = section.querySelector('.courseexp-section__toggle');
			const content = section.querySelector('.courseexp-section__content');
			const sectionId = section.dataset.sectionId;

			section.classList.toggle('is-expanded', expanded);
			if (toggle) {
				toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
			}
			if (content) {
				if (expanded) {
					content.removeAttribute('hidden');
				} else {
					content.setAttribute('hidden', '');
				}
			}
			if (persist && sectionId) {
				accordionState[sectionId] = expanded;
			}
		}

		function areAllExpanded() {
			let allExpanded = true;
			sections.forEach(function (section) {
				if (!section.classList.contains('is-expanded')) {
					allExpanded = false;
				}
			});
			return allExpanded;
		}

		function updateToggleButton() {
			if (!accordionToggleBtn) {
				return;
			}
			const allExpanded = areAllExpanded();
			const data = accordionToggleBtn.dataset;

			accordionToggleBtn.classList.toggle('is-all-expanded', allExpanded);
			accordionToggleBtn.setAttribute('aria-pressed', allExpanded ? 'true' : 'false');
			accordionToggleBtn.setAttribute(
				'aria-label',
				allExpanded ? (data.labelCollapse || '') : (data.labelExpand || '')
			);
			accordionToggleBtn.setAttribute(
				'title',
				allExpanded ? (data.titleCollapse || '') : (data.titleExpand || '')
			);
		}

		function setAll(expanded) {
			sections.forEach(function (section) {
				setSectionExpanded(section, expanded, true);
			});
			saveAccordionState(accordionState);
			updateToggleButton();
		}

		function toggleAll() {
			setAll(!areAllExpanded());
		}

		sections.forEach(function (section) {
			const toggle = section.querySelector('.courseexp-section__toggle');
			if (toggle) {
				toggle.addEventListener('click', function () {
					const expanded = section.classList.contains('is-expanded');
					setSectionExpanded(section, !expanded, true);
					saveAccordionState(accordionState);
					updateToggleButton();
				});
			}

			const sectionId = section.dataset.sectionId;
			if (sectionId && accordionState.hasOwnProperty(sectionId)) {
				setSectionExpanded(section, accordionState[sectionId], false);
			}
		});

		if (accordionToggleBtn) {
			accordionToggleBtn.addEventListener('click', toggleAll);
		}

		updateToggleButton();
	}

	function initActivities() {
		const accordion = document.getElementById('courseexp-accordion');
		if (!accordion) {
			return;
		}

		function handleActivityClick(e) {
			const activityLink = e.target.closest('.courseexp-activity__link');
			if (activityLink) {
				e.preventDefault();
			}

			const activityItem = e.target.closest('.courseexp-activity');
			if (!activityItem) {
				return;
			}

			const activityId = activityItem.dataset.activityId;
			const activityType = activityItem.dataset.activityType;

			const allActivities = accordion.querySelectorAll('.courseexp-activity');
			allActivities.forEach(function (act) {
				act.classList.remove('is-active');
			});
			activityItem.classList.add('is-active');

			const event = new CustomEvent('courseexp:activitySelected', {
				detail: {
					activityId: activityId,
					activityType: activityType,
					activityElement: activityItem,
				},
			});
			document.dispatchEvent(event);

			if (window.innerWidth < 1024) {
				const sidebar = document.getElementById('courseexp-sidebar');
				const overlay = document.getElementById('courseexp-sidebar-overlay');
				const body = document.body;

				if (sidebar && sidebar.classList.contains('is-open')) {
					sidebar.classList.remove('is-open');
					body.classList.remove('courseexp-sidebar-open');
					if (overlay) {
						overlay.classList.remove('is-visible');
					}
				}
			}
		}

		accordion.addEventListener('click', handleActivityClick);
	}

	function initSkeleton() {
		const skeleton = document.getElementById('courseexp-skeleton');
		const accordion = document.getElementById('courseexp-accordion');

		if (!skeleton) {
			return;
		}

		if (accordion) {
			skeleton.classList.add('courseexp-is-hidden');
			skeleton.removeAttribute('aria-busy');
			accordion.classList.remove('courseexp-is-hidden');
		}
	}

	function initScrollSpy() {
		const sidebar = document.getElementById('courseexp-sidebar');
		const content = document.querySelector('.courseexp-main__content');

		if (!sidebar || !content || typeof IntersectionObserver === 'undefined') {
			return;
		}

		function setActive(items, attr, id) {
			Array.prototype.forEach.call(items, function (el) {
				el.classList.toggle('is-active', el.getAttribute(attr) === id);
			});
		}

		function spy(targets, attr, onActive) {
			if (!targets.length) {
				return;
			}

			const visible = [];
			const observer = new IntersectionObserver(
				function (entries) {
					entries.forEach(function (entry) {
						const index = visible.indexOf(entry.target);
						if (entry.isIntersecting && index === -1) {
							visible.push(entry.target);
						} else if (!entry.isIntersecting && index !== -1) {
							visible.splice(index, 1);
						}
					});

					let best = null;
					let bestTop = Infinity;
					visible.forEach(function (el) {
						const top = el.getBoundingClientRect().top;
						if (top < bestTop) {
							bestTop = top;
							best = el;
						}
					});

					if (best) {
						onActive(best.getAttribute(attr));
					}
				},
				{ rootMargin: '-15% 0px -70% 0px', threshold: 0 }
			);

			Array.prototype.forEach.call(targets, function (target) {
				observer.observe(target);
			});
		}

		const sidebarSections = sidebar.querySelectorAll('.courseexp-section[data-section-id]');
		const sidebarActivities = sidebar.querySelectorAll('.courseexp-activity[data-activity-id]');

		spy(
			content.querySelectorAll('.courseexp-section-block[data-section-id]'),
			'data-section-id',
			function (id) {
				setActive(sidebarSections, 'data-section-id', id);
			}
		);

		spy(
			content.querySelectorAll('[data-activity-id]'),
			'data-activity-id',
			function (id) {
				setActive(sidebarActivities, 'data-activity-id', id);
			}
		);
	}

	function init() {
		initSidebar();
		initMobileSidebar();
		initAccordion();
		initActivities();
		initSkeleton();
		initScrollSpy();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
