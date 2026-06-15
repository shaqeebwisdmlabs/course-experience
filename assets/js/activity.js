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

		var MAX_SANE_HEIGHT = 50000;

		var RISE_LOCK_THRESHOLD = 8;

		function applyHeight(iframe, reported) {
			if (iframe.dataset.clLocked === '1') {
				return;
			}
			var height = parseInt(reported, 10);
			if (!height || height <= 0 || height > MAX_SANE_HEIGHT) {
				return;
			}
			var st = iframe._clState || ( iframe._clState = { last: 0, rising: 0 } );
			st.rising = ( height > st.last ) ? st.rising + 1 : 0;
			st.last = height;
			if (st.rising >= RISE_LOCK_THRESHOLD) {
				lockToFill(iframe);
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
			if (data.type === 'mod_courselink:resize') {
				if (data.interactive === true) {
					lockToFill(targetFrame);
				} else {
					applyHeight(targetFrame, data.height);
				}
			} else if (data.type === 'mod_courselink:completion') {
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
