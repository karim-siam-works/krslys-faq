(function () {
	'use strict';

	function toggleItem(item) {
		if (!item) {
			return;
		}
		var isOpen = item.classList.contains('is-open');
		if (isOpen) {
			item.classList.remove('is-open');
		} else {
			item.classList.add('is-open');
		}
	}

	function bindFaq(container) {
		if (!container) {
			return;
		}

		container.addEventListener('click', function (event) {
			var target = event.target;

			// Find question wrapper.
			while (target && target !== container) {
				if (target.classList && target.classList.contains('aio-faq__question')) {
					var item = target.closest('.aio-faq__item');
					toggleItem(item);
					break;
				}
				target = target.parentNode;
			}
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		var containers = document.querySelectorAll('.aio-faq');
		for (var i = 0; i < containers.length; i++) {
			bindFaq(containers[i]);
		}
	});
})();


