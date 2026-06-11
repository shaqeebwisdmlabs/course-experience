(function () {
	'use strict';

	function initEmbed() {
		var embed = document.getElementById('courseexp-activity-embed');
		var frame = document.getElementById('courseexp-activity-frame');
		if (!embed || !frame) {
			return;
		}

		var frameOrigin = '';
		try {
			frameOrigin = new URL(frame.src, window.location.href).origin;
		} catch (e) {
			frameOrigin = '';
		}

		function markLoaded() {
			embed.classList.add('is-loaded');
		}

		function applyHeight(reported) {
			var height = parseInt(reported, 10);
			if (!height || height < 0) {
				return;
			}
			frame.style.height = height + 'px';
			frame.style.minHeight = '0';
			markLoaded();
		}

		function requestHeight() {
			if (!frame.contentWindow || !frameOrigin) {
				return;
			}
			frame.contentWindow.postMessage({ type: 'mod_courselink:requestHeight' }, frameOrigin);
		}

		window.addEventListener('message', function (event) {
			if (event.source !== frame.contentWindow) {
				return;
			}
			if (frameOrigin && event.origin !== frameOrigin) {
				return;
			}
			var data = event.data;
			if (!data || data.type !== 'mod_courselink:resize') {
				return;
			}
			applyHeight(data.height);
		});

		frame.addEventListener('load', function () {
			markLoaded();
			requestHeight();
		});

		try {
			if (frame.contentDocument && frame.contentDocument.readyState === 'complete') {
				markLoaded();
				requestHeight();
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
