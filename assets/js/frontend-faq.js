(function () {
	'use strict';

	function toggleItem(item, container) {
		if (!item) {
			return;
		}
		
		var isOpen = item.classList.contains('is-open');
		var isAccordion = container && container.dataset.accordion === '1';
		
		// If accordion mode, close all other items
		if (!isOpen && isAccordion) {
			var allItems = container.querySelectorAll('.nlf-faq__item.is-open');
			for (var i = 0; i < allItems.length; i++) {
				if (allItems[i] !== item) {
					allItems[i].classList.remove('is-open');
				}
			}
		}
		
		if (isOpen) {
			item.classList.remove('is-open');
		} else {
			item.classList.add('is-open');
		}

		// Smooth scroll if enabled
		if (!isOpen && container && container.dataset.smoothScroll === '1') {
			setTimeout(function() {
				item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
			}, 100);
		}
	}

	function initSearch(container) {
		var searchInput = container.querySelector('.nlf-faq-search-input');
		if (!searchInput) {
			return;
		}

		searchInput.addEventListener('input', function (e) {
			var query = e.target.value.toLowerCase().trim();
			var items = container.querySelectorAll('.nlf-faq__item');

			for (var i = 0; i < items.length; i++) {
				var item = items[i];
				var question = item.querySelector('.nlf-faq__question span');
				var answer = item.querySelector('.nlf-faq__answer');
				
				var questionText = question ? question.textContent.toLowerCase() : '';
				var answerText = answer ? answer.textContent.toLowerCase() : '';
				
				if (query === '' || questionText.indexOf(query) !== -1 || answerText.indexOf(query) !== -1) {
					item.style.display = '';
				} else {
					item.style.display = 'none';
				}
			}
		});
	}

	function bindFaq(container) {
		if (!container) {
			return;
		}

		// Handle click events
		container.addEventListener('click', function (event) {
			var target = event.target;

			// Find question wrapper.
			while (target && target !== container) {
				if (target.classList && target.classList.contains('nlf-faq__question')) {
					var item = target.closest('.nlf-faq__item');
					toggleItem(item, container);
					break;
				}
				target = target.parentNode;
			}
		});

		// Initialize search if present
		initSearch(container);
	}

	function initFaq(context) {
		var containers = (context || document).querySelectorAll('.nlf-faq');
		for (var i = 0; i < containers.length; i++) {
			bindFaq(containers[i]);
		}
	}

	// Export for use in admin live preview
	if (typeof window !== 'undefined') {
		window.nlfInitFaq = initFaq;
	}

	document.addEventListener('DOMContentLoaded', function () {
		initFaq();
	});
})();


