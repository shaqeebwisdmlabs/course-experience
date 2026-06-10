(function () {
	'use strict';

	var SECTIONS_STORAGE_KEY = 'courseexp-sections-state';
	var SUBSECTIONS_STORAGE_KEY = 'courseexp-subsections-state';

	function getSectionsState() {
		try {
			var saved = localStorage.getItem(SECTIONS_STORAGE_KEY);
			return saved ? JSON.parse(saved) : {};
		} catch (e) {
			return {};
		}
	}

	function saveSectionsState(state) {
		try {
			localStorage.setItem(SECTIONS_STORAGE_KEY, JSON.stringify(state));
		} catch (e) {}
	}

	function getSubsectionsState() {
		try {
			var saved = localStorage.getItem(SUBSECTIONS_STORAGE_KEY);
			return saved ? JSON.parse(saved) : {};
		} catch (e) {
			return {};
		}
	}

	function saveSubsectionsState(state) {
		try {
			localStorage.setItem(SUBSECTIONS_STORAGE_KEY, JSON.stringify(state));
		} catch (e) {}
	}

	function setSubsectionExpanded(sub, expanded, state, persist) {
		var toggle = sub.querySelector('.courseexp-subsection__toggle');
		var body = sub.querySelector('.courseexp-subsection__body');
		var id = sub.dataset.subsectionId;

		sub.classList.toggle('is-expanded', expanded);
		if (toggle) {
			toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
		}
		if (body) {
			if (expanded) {
				body.removeAttribute('hidden');
			} else {
				body.setAttribute('hidden', '');
			}
		}
		if (persist && id) {
			state[id] = expanded;
		}
	}

	function initSubsectionAccordions() {
		var content = document.querySelector('.courseexp-main__content');
		if (!content) {
			return;
		}

		var subs = content.querySelectorAll('.courseexp-subsection');
		if (!subs.length) {
			return;
		}

		var state = getSubsectionsState();

		Array.prototype.forEach.call(subs, function (sub) {
			var toggle = sub.querySelector('.courseexp-subsection__toggle');
			if (toggle) {
				toggle.addEventListener('click', function () {
					setSubsectionExpanded(sub, !sub.classList.contains('is-expanded'), state, true);
					saveSubsectionsState(state);
				});
			}

			var id = sub.dataset.subsectionId;
			if (id && state.hasOwnProperty(id)) {
				setSubsectionExpanded(sub, state[id], state, false);
			}
		});
	}

	function initSectionAccordion() {
		var container = document.getElementById('courseexp-sections');
		if (
			!container ||
			container.dataset.layout === 'onesectionperpage' ||
			container.classList.contains('courseexp-sections--detail')
		) {
			return;
		}

		var sections = container.querySelectorAll('.courseexp-section-block');
		if (!sections.length) {
			return;
		}

		var expandAllBtn = document.getElementById('courseexp-sections-expand-all');
		var state = getSectionsState();

		function setExpanded(section, expanded, persist) {
			var toggle = section.querySelector('.courseexp-section-block__toggle');
			var body = section.querySelector('.courseexp-section-block__body');
			var id = section.dataset.sectionId;

			section.classList.toggle('is-expanded', expanded);
			if (toggle) {
				toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
			}
			if (body) {
				if (expanded) {
					body.removeAttribute('hidden');
				} else {
					body.setAttribute('hidden', '');
				}
			}
			if (persist && id) {
				state[id] = expanded;
			}
		}

		function areAllExpanded() {
			return Array.prototype.every.call(sections, function (section) {
				return section.classList.contains('is-expanded');
			});
		}

		function updateExpandAll() {
			if (!expandAllBtn) {
				return;
			}
			var allExpanded = areAllExpanded();
			var data = expandAllBtn.dataset;
			var text = expandAllBtn.querySelector('.courseexp-section-block__expand-all-text');

			expandAllBtn.setAttribute('aria-pressed', allExpanded ? 'true' : 'false');
			if (text) {
				text.textContent = allExpanded ? (data.labelCollapse || '') : (data.labelExpand || '');
			}
		}

		function setAll(expanded) {
			Array.prototype.forEach.call(sections, function (section) {
				setExpanded(section, expanded, true);
			});
			var subState = getSubsectionsState();
			var subs = container.querySelectorAll('.courseexp-subsection');
			Array.prototype.forEach.call(subs, function (sub) {
				setSubsectionExpanded(sub, expanded, subState, true);
			});
			saveSectionsState(state);
			saveSubsectionsState(subState);
			updateExpandAll();
		}

		Array.prototype.forEach.call(sections, function (section) {
			var toggle = section.querySelector('.courseexp-section-block__toggle');
			if (toggle) {
				toggle.addEventListener('click', function () {
					setExpanded(section, !section.classList.contains('is-expanded'), true);
					saveSectionsState(state);
					updateExpandAll();
				});
			}

			var id = section.dataset.sectionId;
			if (toggle && id && state.hasOwnProperty(id)) {
				setExpanded(section, state[id], false);
			}
		});

		if (expandAllBtn) {
			expandAllBtn.addEventListener('click', function () {
				setAll(!areAllExpanded());
			});
		}

		updateExpandAll();
	}

	var highlighted = null;
	var highlightTimer = null;

	function flashTarget(el) {
		if (highlighted) {
			highlighted.classList.remove('is-target');
		}
		if (highlightTimer) {
			clearTimeout(highlightTimer);
		}
		highlighted = el;
		el.classList.add('is-target');
		highlightTimer = setTimeout(function () {
			el.classList.remove('is-target');
			highlighted = null;
			highlightTimer = null;
		}, 2200);
	}

	function revealActivity(cmid) {
		if (!/^\d+$/.test(String(cmid))) {
			return;
		}
		var content = document.querySelector('.courseexp-main__content');
		if (!content) {
			return;
		}
		var el = content.querySelector('[data-activity-id="' + cmid + '"]');
		if (!el) {
			return;
		}

		var block = el.closest('.courseexp-section-block');
		if (block && !block.classList.contains('is-expanded')) {
			var toggle = block.querySelector('.courseexp-section-block__toggle');
			if (toggle) {
				toggle.click();
			}
		}

		var sub = el.closest('.courseexp-subsection');
		if (sub && !sub.classList.contains('is-expanded')) {
			var subToggle = sub.querySelector('.courseexp-subsection__toggle');
			if (subToggle) {
				subToggle.click();
			}
		}

		var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		el.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
		flashTarget(el);
	}

	function revealSubsection(subId) {
		if (!/^\d+$/.test(String(subId))) {
			return;
		}
		var content = document.querySelector('.courseexp-main__content');
		if (!content) {
			return;
		}
		var el = content.querySelector('.courseexp-subsection[data-subsection-id="' + subId + '"]');
		if (!el) {
			return;
		}

		var block = el.closest('.courseexp-section-block');
		if (block && !block.classList.contains('is-expanded')) {
			var toggle = block.querySelector('.courseexp-section-block__toggle');
			if (toggle) {
				toggle.click();
			}
		}

		if (!el.classList.contains('is-expanded')) {
			var subToggle = el.querySelector('.courseexp-subsection__toggle');
			if (subToggle) {
				subToggle.click();
			}
		}

		var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		el.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
		flashTarget(el);
	}

	function revealFromHash() {
		var hash = window.location.hash || '';
		var activityMatch = /^#courseexp-activity-(\d+)$/.exec(hash);
		if (activityMatch) {
			revealActivity(activityMatch[1]);
			return;
		}
		var subsectionMatch = /^#courseexp-subsection-(\d+)$/.exec(hash);
		if (subsectionMatch) {
			revealSubsection(subsectionMatch[1]);
		}
	}

	document.addEventListener('courseexp:activitySelected', function (e) {
		if (e.detail && e.detail.cmid) {
			revealActivity(e.detail.cmid);
		}
	});

	document.addEventListener('courseexp:subsectionSelected', function (e) {
		if (e.detail && e.detail.subId) {
			revealSubsection(e.detail.subId);
		}
	});

	function init() {
		initSectionAccordion();
		initSubsectionAccordions();
		revealFromHash();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	function closeOutside(target) {
		var open = document.querySelectorAll('details.courseexp-todo[open]');
		Array.prototype.forEach.call(open, function (todo) {
			if (!todo.contains(target)) {
				todo.removeAttribute('open');
			}
		});
	}

	document.addEventListener('click', function (event) {
		closeOutside(event.target);
	});

	document.addEventListener('focusin', function (event) {
		closeOutside(event.target);
	});

	document.addEventListener('keydown', function (event) {
		if ('Escape' !== event.key) {
			return;
		}
		var open = document.querySelector('details.courseexp-todo[open]');
		if (!open) {
			return;
		}
		open.removeAttribute('open');
		var trigger = open.querySelector('.courseexp-todo__trigger');
		if (trigger) {
			trigger.focus();
		}
	});
})();
