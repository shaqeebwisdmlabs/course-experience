(function () {
	'use strict';

	var SECTIONS_STORAGE_KEY = 'courseexp-sections-state';

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
			saveSectionsState(state);
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

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initSectionAccordion);
	} else {
		initSectionAccordion();
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
