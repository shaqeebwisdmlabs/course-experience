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

		var FILL_MIN_HEIGHT = 480;

		function markLoaded() {
			embed.classList.add('is-loaded');
		}

		function computeFillHeight() {
			return Math.max(FILL_MIN_HEIGHT, Math.round(window.innerHeight * 0.9));
		}

		function lockToFill(iframe) {
			iframe.style.height = computeFillHeight() + 'px';
			iframe.style.minHeight = '0';
			iframe.setAttribute('scrolling', 'auto');
			iframe.dataset.clLocked = '1';
			markLoaded();
		}

		var NAV_TIMEOUT = 15000;

		function showNavigating(iframe) {
			var parent = iframe.parentNode;
			if (!parent) {
				return;
			}
			var overlay = iframe._clOverlay;
			if (!overlay) {
				overlay = document.createElement('div');
				overlay.className = 'courseexp-activity-embed__navigating';
				overlay.setAttribute('aria-hidden', 'true');
				var spinner = document.createElement('span');
				spinner.className = 'courseexp-activity-embed__spinner';
				overlay.appendChild(spinner);
				parent.appendChild(overlay);
				iframe._clOverlay = overlay;
			}
			overlay.classList.add('is-visible');
			if (iframe._clNavTimer) {
				window.clearTimeout(iframe._clNavTimer);
			}
			iframe._clNavTimer = window.setTimeout(function () {
				hideNavigating(iframe);
			}, NAV_TIMEOUT);
		}

		function hideNavigating(iframe) {
			if (iframe._clNavTimer) {
				window.clearTimeout(iframe._clNavTimer);
				iframe._clNavTimer = null;
			}
			if (iframe._clOverlay) {
				iframe._clOverlay.classList.remove('is-visible');
			}
		}

		var MAX_SANE_HEIGHT = 50000;

		function applyHeight(iframe, reported) {
			if (iframe.dataset.clLocked === '1') {
				return;
			}
			var height = parseInt(reported, 10);
			if (!height || height <= 0 || height > MAX_SANE_HEIGHT) {
				return;
			}
			iframe.style.height = height + 'px';
			iframe.style.minHeight = '0';
			markLoaded();
		}

		function findFrame(source) {
			if (!source) {
				return null;
			}
			if (frame.contentWindow === source) {
				return frame;
			}
			var frames = document.querySelectorAll('iframe.courseexp-activity-embed__frame');
			for (var i = 0; i < frames.length; i++) {
				if (frames[i].contentWindow === source) {
					return frames[i];
				}
			}
			return null;
		}

		function requestHeight() {
			if (!frame.contentWindow || !frameOrigin) {
				return;
			}
			frame.contentWindow.postMessage({ type: 'mod_courselink:requestHeight' }, frameOrigin);
		}

		var content = document.querySelector('.courseexp-activity-content');
		var activityCmid = content ? content.dataset.activityId || '' : '';

		var lastCompleted = null;

		function sidebarShowsComplete(cmid) {
			var item = document.querySelector('#courseexp-sidebar .courseexp-activity[data-activity-id="' + cmid + '"]');
			if (!item) {
				return null;
			}
			return !!item.querySelector('.courseexp-activity__icon--complete');
		}

		function handleCompletion(data) {
			if (!activityCmid || String(data.cmid) !== activityCmid) {
				return;
			}
			var completed = !!data.completed;
			if (completed === lastCompleted) {
				return;
			}
			lastCompleted = completed;

			if (sidebarShowsComplete(String(data.cmid)) === completed) {
				return;
			}

			document.dispatchEvent(new CustomEvent('courseexp:refresh'));
		}

		window.addEventListener('message', function (event) {
			if (!frameOrigin || event.origin !== frameOrigin) {
				return;
			}
			var data = event.data;
			if (!data) {
				return;
			}
			var targetFrame = findFrame(event.source);
			if (!targetFrame) {
				return;
			}
			if (data.type === 'mod_courselink:navigating') {
				showNavigating(targetFrame);
			} else if (data.type === 'mod_courselink:navigated') {
				hideNavigating(targetFrame);
			} else if (data.type === 'mod_courselink:resize') {
				hideNavigating(targetFrame);
				if (data.interactive === true) {
					lockToFill(targetFrame);
				} else {
					applyHeight(targetFrame, data.height);
				}
			} else if (data.type === 'mod_courselink:completion') {
				hideNavigating(targetFrame);
				handleCompletion(data);
			}
		});

		var resizeScheduled = false;
		window.addEventListener('resize', function () {
			if (resizeScheduled) {
				return;
			}
			resizeScheduled = true;
			window.requestAnimationFrame(function () {
				resizeScheduled = false;
				var fillHeight = computeFillHeight();
				var frames = document.querySelectorAll('iframe.courseexp-activity-embed__frame');
				Array.prototype.forEach.call(frames, function (f) {
					if (f.dataset.clLocked === '1') {
						f.style.height = fillHeight + 'px';
					}
				});
			});
		});

		frame.addEventListener('load', function () {
			frame.dataset.clLocked = '';
			markLoaded();
			hideNavigating(frame);
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

	function syncDirectRenderCompletion() {
		var content = document.querySelector('.courseexp-activity-content');
		if (!content) {
			return;
		}
		var rendermode = content.dataset.rendermode || '';
		if (rendermode !== 'html' && rendermode !== 'file') {
			return;
		}
		var cmid = content.dataset.activityId || '';
		if (!cmid) {
			return;
		}
		var item = document.querySelector('#courseexp-sidebar .courseexp-activity[data-activity-id="' + cmid + '"]');
		if (!item || !item.querySelector('.courseexp-activity__icon--incomplete')) {
			return;
		}
		document.dispatchEvent(new CustomEvent('courseexp:refresh'));
	}

	function init() {
		initEmbed();
		initPopupLinks();
		syncDirectRenderCompletion();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
