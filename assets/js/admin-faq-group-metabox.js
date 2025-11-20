(function ($) {
	'use strict';

	function renumberGroupCheckboxes() {
		$('#nlf-faq-group-questions-body .nlf-faq-question-row').each(function (index) {
			var $row = $(this);

			$row.find('input[type="checkbox"][name^="nlf_faq_group_visible"]').attr('name', 'nlf_faq_group_visible[' + index + ']');
			$row.find('input[type="checkbox"][name^="nlf_faq_group_open"]').attr('name', 'nlf_faq_group_open[' + index + ']');
			$row.find('input[type="checkbox"][name^="nlf_faq_group_highlight"]').attr('name', 'nlf_faq_group_highlight[' + index + ']');
		});
	}

	function initNewEditor($row) {
		var $textarea = $row.find('.nlf-faq-group-answer-editor');
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
			id = 'nlf-faq-group-answer-' + String(Date.now());
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
		var $body = $('#nlf-faq-group-questions-body');
		if (!$body.length) {
			return;
		}

		// Initialize sortable for drag-and-drop reordering
		$body.sortable({
			handle: '.nlf-faq-sort-handle',
			items: '.nlf-faq-question-row',
			placeholder: 'nlf-faq-sort-placeholder',
			cursor: 'move',
			opacity: 0.8,
			tolerance: 'pointer',
			axis: 'y',
			start: function (event, ui) {
				// Save and remove TinyMCE editors before dragging to prevent DOM issues
				var $row = ui.item;
				var editorIds = [];
				
				// Find all textareas that might have editors (both ID patterns and class-based)
				$row.find('textarea.nlf-faq-group-answer-editor, textarea[id^="nlf_faq_group_answer_"], textarea[id^="nlf-faq-group-answer-"]').each(function () {
					var $textarea = $(this);
					var id = $textarea.attr('id');
					if (id && window.tinymce) {
						var editor = window.tinymce.get(id);
						if (editor) {
							// Save editor content before removing
							if (!editor.isHidden()) {
								editor.save();
							}
							// Remove editor to prevent DOM access errors during drag
							editor.remove();
							editorIds.push(id);
						}
					}
				});
				
				// Store editor IDs for restoration after drag
				ui.item.data('editor-ids', editorIds);
				
				// Add visual feedback when dragging starts
				ui.placeholder.height(ui.item.height());
			},
			stop: function (event, ui) {
				// Restore TinyMCE editors after dragging
				var $row = ui.item;
				var editorIds = ui.item.data('editor-ids') || [];
				
				// Use wp.oldEditor if available, otherwise fallback to wp.editor
				var editorAPI = (window.wp && window.wp.oldEditor && typeof window.wp.oldEditor.initialize === 'function')
					? window.wp.oldEditor
					: (window.wp && window.wp.editor && typeof window.wp.editor.initialize === 'function' ? window.wp.editor : null);
				
				if (editorAPI) {
					editorIds.forEach(function (id) {
						var $textarea = $row.find('textarea#' + id);
						if ($textarea.length) {
							// Reinitialize the editor after drag completes
							editorAPI.initialize(id, {
								tinymce: {
									wpautop: true
								},
								quicktags: true,
								mediaButtons: false
							});
						}
					});
				}
				
				// Clean up stored data
				ui.item.removeData('editor-ids');
				
				// Renumber checkboxes after sorting
				renumberGroupCheckboxes();
			}
		});

		var rowTemplate = $('#tmpl-nlf-faq-group-row').html();

		$('#nlf-faq-group-add-row').on('click', function (e) {
			e.preventDefault();

			var index = $body.find('.nlf-faq-question-row').length;
			var html = rowTemplate.replace(/{{index}}/g, String(index));

			var $row = $(html);
			$body.append($row);
			initNewEditor($row);
			renumberGroupCheckboxes();
			// Refresh sortable to include the new row
			$body.sortable('refresh');
		});

		$body.on('click', '.nlf-faq-remove-row', function (e) {
			e.preventDefault();
			var $row = $(this).closest('.nlf-faq-question-row');

			// Remove TinyMCE editor instance to avoid leaks
			var $textarea = $row.find('.nlf-faq-group-answer-editor');
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
			// Refresh sortable after removing a row
			$body.sortable('refresh');
		});
	});
})(jQuery);


