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

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initEmbed);
	} else {
		initEmbed();
	}
})();
