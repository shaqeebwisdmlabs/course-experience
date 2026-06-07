(function () {
	'use strict';

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
