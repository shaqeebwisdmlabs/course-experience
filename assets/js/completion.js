(function () {
	'use strict';

	if (!window.fetch || !window.Idiomorph) {
		return;
	}

	var REGIONS = ['#courseexp-sidebar', '.courseexp-course-header', '#courseexp-sections', '.courseexp-activity-header'];

	var ACCORDIONS = [
		{ section: '.courseexp-section', toggle: '.courseexp-section__toggle', content: '.courseexp-section__content' },
		{ section: '.courseexp-section-block', toggle: '.courseexp-section-block__toggle', content: '.courseexp-section-block__body' }
	];

	function captureExpanded() {
		return ACCORDIONS.map(function (group) {
			var map = {};
			document.querySelectorAll(group.section + '[data-section-id]').forEach(function (section) {
				if (section.querySelector(group.toggle)) {
					map[section.dataset.sectionId] = section.classList.contains('is-expanded');
				}
			});
			return map;
		});
	}

	function restoreExpanded(state) {
		ACCORDIONS.forEach(function (group, index) {
			var map = state[index];
			document.querySelectorAll(group.section + '[data-section-id]').forEach(function (section) {
				var id = section.dataset.sectionId;
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

	function morphRegions(html) {
		var doc = new DOMParser().parseFromString(html, 'text/html');
		REGIONS.forEach(function (selector) {
			var current = document.querySelector(selector);
			var next = doc.querySelector(selector);
			if (current && next) {
				window.Idiomorph.morph(current, next.innerHTML, { morphStyle: 'innerHTML' });
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
					morphRegions(html);
					restoreExpanded(expanded);
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
