(function ($) {
	'use strict';

	function renumberGroupCheckboxes() {
		$('#aio-faq-group-questions-body .aio-faq-question-row').each(function (index) {
			var $row = $(this);

			$row.find('input[type="checkbox"][name^="aio_faq_group_visible"]').attr('name', 'aio_faq_group_visible[' + index + ']');
			$row.find('input[type="checkbox"][name^="aio_faq_group_open"]').attr('name', 'aio_faq_group_open[' + index + ']');
			$row.find('input[type="checkbox"][name^="aio_faq_group_highlight"]').attr('name', 'aio_faq_group_highlight[' + index + ']');
		});
	}

	function initNewEditor($row) {
		console.log('initNewEditor', $row);
		var $textarea = $row.find('.aio-faq-group-answer-editor');
		if (!$textarea.length) {
			return;
		}

		var id = $textarea.attr('id');
		console.log('id', id);
		wp.editor.initialize(id, true);
		if (!id) {
			id = 'aio-faq-group-answer-' + String(Date.now());
			$textarea.attr('id', id);
		}

		if (!window.wp) {
			return;
		}

		// WP 5.x/6.x exposes the classic editor API as wp.oldEditor.
		if (wp.oldEditor && typeof wp.oldEditor.initialize === 'function') {
			wp.oldEditor.initialize(id, {
				teeny: true,
				mediaButtons: false,
			});
		} else if (wp.editor && typeof wp.editor.initialize === 'function') {
			// Back-compat for older versions.
			wp.editor.initialize(id, {
				teeny: true,
				mediaButtons: false,
			});
		}
	}

	$(function () {
		var $body = $('#aio-faq-group-questions-body');
		if (!$body.length) {
			return;
		}

		var rowTemplate = $('#tmpl-aio-faq-group-row').html();

		$('#aio-faq-group-add-row').on('click', function (e) {
			e.preventDefault();

			var index = $body.find('.aio-faq-question-row').length;
			var html = rowTemplate.replace(/{{index}}/g, String(index));

			var $row = $(html);
			$body.append($row);
			initNewEditor($row);
			renumberGroupCheckboxes();
		});

		$body.on('click', '.aio-faq-remove-row', function (e) {
			e.preventDefault();
			var $row = $(this).closest('.aio-faq-question-row');

			// If using TinyMCE, remove its instance to avoid leaks.
			if (window.tinymce) {
				var $textarea = $row.find('.aio-faq-group-answer-editor');
				if ($textarea.length) {
					var id = $textarea.attr('id');
					var editor = tinymce.get(id);
					if (editor) {
						editor.remove();
					}
				}
			}

			$row.remove();
			renumberGroupCheckboxes();
		});
	});
})(jQuery);


