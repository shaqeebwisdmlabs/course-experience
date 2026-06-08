(function () {
	'use strict';

	function initEmbed() {
		var embed = document.getElementById('courseexp-activity-embed');
		var frame = document.getElementById('courseexp-activity-frame');
		if (!embed || !frame) {
			return;
		}

		function markLoaded() {
			embed.classList.add('is-loaded');
		}

		frame.addEventListener('load', markLoaded);

		try {
			if (frame.contentDocument && frame.contentDocument.readyState === 'complete') {
				markLoaded();
			}
		} catch (e) {}
	}

	function initPopupLinks() {
		var links = document.querySelectorAll('a[data-courseexp-popup-width]');
		Array.prototype.forEach.call(links, function (link) {
			link.addEventListener('click', function (event) {
				var width = parseInt(link.dataset.courseexpPopupWidth, 10);
				var height = parseInt(link.dataset.courseexpPopupHeight, 10);
				if (!width || !height) {
					return;
				}

				var features = 'width=' + width + ',height=' + height + ',scrollbars=yes,resizable=yes';
				var popup = window.open(link.href, 'courseexp_url_popup', features);

				if (popup) {
					event.preventDefault();
					popup.focus();
				}
			});
		});
	}

	function init() {
		initEmbed();
		initPopupLinks();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
