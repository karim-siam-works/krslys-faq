(function () {
	'use strict';

	var analyticsConfig = (typeof window !== 'undefined' && window.nlfFaqData) ? window.nlfFaqData : null;
	if (analyticsConfig && analyticsConfig.tracking === false) {
		analyticsConfig = null;
	}

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
			trackAnalytics(container, item, 'open');
		}

		if (isOpen) {
			trackAnalytics(container, item, 'close');
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
				// Select the question text span, excluding counter and icon spans
				var question = item.querySelector('.nlf-faq__question > span:not(.nlf-faq__counter):not(.nlf-faq__icon)');
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

	function trackAnalytics(container, item, action) {
		if (!analyticsConfig) {
			return;
		}

		var groupId = container ? container.getAttribute('data-group-id') : null;
		var questionId = item ? item.getAttribute('data-faq-id') : null;

		if (!groupId || !questionId) {
			return;
		}

		var payload = {
			groupId: parseInt(groupId, 10),
			questionId: parseInt(questionId, 10),
			action: action,
		};

		if (typeof window.nlfFaqAnalytics === 'function') {
			try {
				window.nlfFaqAnalytics(payload);
			} catch (error) {
				// no-op
			}
		}

		if (Array.isArray(window.dataLayer)) {
			window.dataLayer.push({
				event: 'nlfFaqInteraction',
				nlfFaq: payload,
			});
		}

		if (action === 'open') {
			sendAnalyticsBeacon(payload);
		}
	}

	function sendAnalyticsBeacon(payload) {
		if (!analyticsConfig || !analyticsConfig.ajaxurl || !analyticsConfig.nonce) {
			return;
		}

		var params = new URLSearchParams();
		params.append('action', 'nlf_faq_track');
		params.append('group_id', String(payload.groupId));
		params.append('question_id', String(payload.questionId));
		params.append('state', payload.action);
		params.append('nonce', analyticsConfig.nonce);

		if (navigator.sendBeacon) {
			navigator.sendBeacon(analyticsConfig.ajaxurl, params);
			return;
		}

		fetch(analyticsConfig.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			body: params,
		});
	}
})();


