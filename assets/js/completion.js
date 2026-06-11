(function () {
	'use strict';

	if (!window.fetch || !window.Idiomorph) {
		return;
	}

	var REGIONS = ['#courseexp-sidebar', '.courseexp-course-header', '#courseexp-sections', '.courseexp-activity-header'];
	var PRESERVE = [
		'.courseexp-activity-content__description',
		'.courseexp-activity-row__description',
		'.courseexp-section-block__summary',
		'.courseexp-inline-content__body',
		'.courseexp-activity-book__chapter-body',
		'.courseexp-activity-html'
	].join(',');

	var ACCORDIONS = [
		{ section: '.courseexp-section', toggle: '.courseexp-section__toggle', content: '.courseexp-section__content', attr: 'data-section-id' },
		{ section: '.courseexp-section-block', toggle: '.courseexp-section-block__toggle', content: '.courseexp-section-block__body', attr: 'data-section-id' },
		{ section: '.courseexp-subsection', toggle: '.courseexp-subsection__toggle', content: '.courseexp-subsection__body', attr: 'data-subsection-id' },
		{ section: '.courseexp-subnav', toggle: '.courseexp-subnav__toggle', content: '.courseexp-subnav__content', attr: 'data-subsection-id' }
	];

	function captureExpanded() {
		return ACCORDIONS.map(function (group) {
			var map = {};
			document.querySelectorAll(group.section + '[' + group.attr + ']').forEach(function (section) {
				if (section.querySelector(group.toggle)) {
					map[section.getAttribute(group.attr)] = section.classList.contains('is-expanded');
				}
			});
			return map;
		});
	}

	function restoreExpanded(state) {
		ACCORDIONS.forEach(function (group, index) {
			var map = state[index];
			document.querySelectorAll(group.section + '[' + group.attr + ']').forEach(function (section) {
				var id = section.getAttribute(group.attr);
				if (!(id in map)) {
					return;
				}
				var expanded = map[id];
				section.classList.toggle('is-expanded', expanded);

				var toggle = section.querySelector(group.toggle);
				if (toggle) {
					toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
				}

				var content = section.querySelector(group.content);
				if (content) {
					if (expanded) {
						content.removeAttribute('hidden');
					} else {
						content.setAttribute('hidden', '');
					}
				}
			});
		});
	}

	var HIGHLIGHTS = [
		{ item: '#courseexp-sidebar .courseexp-section', attr: 'data-section-id' },
		{ item: '#courseexp-sidebar .courseexp-subnav', attr: 'data-subsection-id' },
		{ item: '#courseexp-sidebar .courseexp-activity', attr: 'data-activity-id' }
	];

	function captureActive() {
		return HIGHLIGHTS.map(function (group) {
			var active = [];
			document.querySelectorAll(group.item + '[' + group.attr + ']').forEach(function (item) {
				if (item.classList.contains('is-active')) {
					active.push(item.getAttribute(group.attr));
				}
			});
			return active;
		});
	}

	function restoreActive(state) {
		HIGHLIGHTS.forEach(function (group, index) {
			var active = state[index];
			if (!active.length) {
				return;
			}
			document.querySelectorAll(group.item + '[' + group.attr + ']').forEach(function (item) {
				item.classList.toggle('is-active', active.indexOf(item.getAttribute(group.attr)) !== -1);
			});
		});
	}

	var MORPH_OPTIONS = {
		morphStyle: 'innerHTML',
		callbacks: {
			beforeNodeMorphed: function (oldNode) {
				return !(oldNode.nodeType === 1 && oldNode.closest(PRESERVE));
			}
		}
	};

	function morphRegions(html) {
		var doc = new DOMParser().parseFromString(html, 'text/html');
		REGIONS.forEach(function (selector) {
			var current = document.querySelector(selector);
			var next = doc.querySelector(selector);
			if (current && next) {
				window.Idiomorph.morph(current, next.innerHTML, MORPH_OPTIONS);
			}
		});
	}

	function refocusControl(cmid) {
		var input = document.querySelector('.courseexp-completion-form input[name="cmid"][value="' + cmid + '"]');
		var button = input && input.form ? input.form.querySelector('button[type="submit"]') : null;
		if (button) {
			button.focus();
		}
	}

	function setBusy(button, busy) {
		if (!button) {
			return;
		}
		button.classList.toggle('is-busy', busy);
		button.disabled = busy;
		if (busy) {
			button.setAttribute('aria-busy', 'true');
		} else {
			button.removeAttribute('aria-busy');
		}
	}

	document.addEventListener('submit', function (event) {
		var form = event.target.closest('.courseexp-completion-form');
		if (!form) {
			return;
		}

		event.preventDefault();

		var button = form.querySelector('button[type="submit"]');
		var cmidInput = form.querySelector('input[name="cmid"]');
		var cmid = cmidInput ? cmidInput.value : '';

		setBusy(button, true);

		var endpoint = form.getAttribute('action');

		fetch(endpoint, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
			body: new URLSearchParams(new FormData(form))
		}).then(
			function (response) {
				if (!response.ok) {
					throw new Error('request failed');
				}
				return response.text();
			}
		).then(
			function (html) {
				try {
					var expanded = captureExpanded();
					var active = captureActive();
					morphRegions(html);
					restoreExpanded(expanded);
					restoreActive(active);
					refocusControl(cmid);
				} catch (e) {
					setBusy(button, false);
				}
			},
			function () {
				form.submit();
			}
		);
	});
})();
