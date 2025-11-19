(function ($) {
	'use strict';

	function applyPreviewFromData() {
		var root = $('#aio-faq-preview-root');
		if (!root.length) {
			return;
		}

		var container = root.find('.aio-faq');

		container.css({
			backgroundColor: root.data('container-background'),
			borderColor: root.data('container-border-color'),
			borderRadius: root.data('container-border-radius') + 'px',
			padding: root.data('container-padding') + 'px',
			boxShadow: root.data('shadow') === 1 || root.data('shadow') === '1'
				? '0 10px 25px rgba(15,23,42,0.08)'
				: 'none'
		});

		container.find('.aio-faq__item + .aio-faq__item').css('margin-top', root.data('gap-between-items') + 'px');

		container.find('.aio-faq__question').css({
			color: root.data('question-color'),
			fontSize: root.data('question-font-size') + 'px',
			fontWeight: root.data('question-font-weight')
		});

		container.find('.aio-faq__answer').css({
			color: root.data('answer-color'),
			fontSize: root.data('answer-font-size') + 'px'
		});

		container.find('.aio-faq__icon').css('color', root.data('accent-color'));
	}

	function updateDataProp(prop, value) {
		var root = $('#aio-faq-preview-root');
		if (!root.length) {
			return;
		}

		switch (prop) {
			case 'container_background':
				root.data('container-background', value);
				break;
			case 'container_border_color':
				root.data('container-border-color', value);
				break;
			case 'container_border_radius':
				root.data('container-border-radius', parseInt(value, 10) || 0);
				break;
			case 'container_padding':
				root.data('container-padding', parseInt(value, 10) || 0);
				break;
			case 'question_color':
				root.data('question-color', value);
				break;
			case 'question_font_size':
				root.data('question-font-size', parseInt(value, 10) || 16);
				break;
			case 'question_font_weight':
				root.data('question-font-weight', parseInt(value, 10) || 600);
				break;
			case 'answer_color':
				root.data('answer-color', value);
				break;
			case 'answer_font_size':
				root.data('answer-font-size', parseInt(value, 10) || 14);
				break;
			case 'accent_color':
				root.data('accent-color', value);
				break;
			case 'gap_between_items':
				root.data('gap-between-items', parseInt(value, 10) || 12);
				break;
			case 'shadow':
				root.data('shadow', value ? 1 : 0);
				break;
			case 'animation':
				root.data('animation', value);
				break;
			case 'icon_style':
				root.data('icon-style', value);
				break;
		}

		applyPreviewFromData();
	}

	$(function () {
		applyPreviewFromData();

		$('#aio-faq-style-form').on('input change', '[data-preview-prop]', function () {
			var $el = $(this);
			var prop = $el.data('preview-prop');
			var val;

			if ($el.is(':checkbox')) {
				val = $el.is(':checked');
			} else {
				val = $el.val();
			}

			updateDataProp(prop, val);
		});

		// Simple saved indicator using WordPress submit button.
		var $form = $('#aio-faq-style-form');
		var $submit = $form.find('input[type="submit"], button[type="submit"]');

		$form.on('submit', function () {
			if (!$submit.length || typeof aioFaqAdmin === 'undefined') {
				return;
			}
			var originalText = $submit.val() || $submit.text();

			if ($submit.is('input')) {
				$submit.val(aioFaqAdmin.i18n.saving);
			} else {
				$submit.text(aioFaqAdmin.i18n.saving);
			}

			setTimeout(function () {
				if ($submit.is('input')) {
					$submit.val(aioFaqAdmin.i18n.saved);
				} else {
					$submit.text(aioFaqAdmin.i18n.saved);
				}

				setTimeout(function () {
					if ($submit.is('input')) {
						$submit.val(originalText);
					} else {
						$submit.text(originalText);
					}
				}, 1200);
			}, 400);
		});
	});
})(jQuery);


