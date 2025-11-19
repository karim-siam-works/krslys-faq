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
		var $textarea = $row.find('.aio-faq-group-answer-editor');
		if (!$textarea.length) {
			return;
		}

		// Check if WordPress editor APIs are available
		if (!window.wp || !window.wp.editor) {
			return;
		}

		// Ensure textarea has a unique ID before initialization
		var id = $textarea.attr('id');
		if (!id) {
			id = 'aio-faq-group-answer-' + String(Date.now());
			$textarea.attr('id', id);
		}

		// Use wp.oldEditor if available (alias for wp.editor in WordPress 5.x+), otherwise fallback to wp.editor
		var editorAPI = (window.wp.oldEditor && typeof window.wp.oldEditor.initialize === 'function') 
			? window.wp.oldEditor 
			: (window.wp.editor && typeof window.wp.editor.initialize === 'function' ? window.wp.editor : null);

		if (editorAPI) {
			// Initialize TinyMCE editor with teeny settings matching wp_editor() PHP call
			editorAPI.initialize(id, {
				tinymce: {
					wpautop: true
				},
				quicktags: true,
				mediaButtons: false
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

			// Remove TinyMCE editor instance to avoid leaks
			var $textarea = $row.find('.aio-faq-group-answer-editor');
			if ($textarea.length) {
				var id = $textarea.attr('id');
				if (id && window.wp && window.wp.editor && typeof window.wp.editor.remove === 'function') {
					window.wp.editor.remove(id);
				} else if (id && window.tinymce) {
					var editor = window.tinymce.get(id);
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


