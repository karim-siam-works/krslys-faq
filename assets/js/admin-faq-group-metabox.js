(function ($) {
	'use strict';

	// Tab Switching
	function initTabs() {
		$('.nlf-faq-tab-button').on('click', function () {
			var targetTab = $(this).data('tab');
			
			// Update buttons
			$('.nlf-faq-tab-button').removeClass('active');
			$(this).addClass('active');
			
			// Update panels
			$('.nlf-faq-tab-panel').removeClass('active');
			$('.nlf-faq-tab-panel[data-tab="' + targetTab + '"]').addClass('active');
			
			// Load live preview when switching to live view tab
			if (targetTab === 'live-view') {
				loadLivePreview();
			}
		});
	}

	// Theme Selection
	function initThemeSelection() {
		$('.nlf-theme-card').on('click', function () {
			$('.nlf-theme-card').removeClass('active');
			$(this).addClass('active');
			$(this).find('input[type="radio"]').prop('checked', true);
		});
	}

	// Custom Style Toggle
	function initCustomStyleToggle() {
		$('#nlf-use-custom-style-toggle').on('change', function () {
			var $fields = $('.nlf-custom-style-fields');
			if ($(this).is(':checked')) {
				$fields.show();
			} else {
				$fields.hide();
			}
		});
	}

	// Color Pickers
	function initColorPickers() {
		if (typeof $.fn.wpColorPicker !== 'undefined') {
			$('.nlf-color-picker').wpColorPicker();
		}
	}

	// Live Preview Loader
	function loadLivePreview() {
		var $container = $('.nlf-live-view-container');
		var $loading = $container.find('.nlf-live-view-loading');
		var $content = $container.find('.nlf-live-view-content');
		var groupId = $container.data('group-id');
		
		if (!groupId || groupId === '0' || groupId === 0) {
			$loading.html('<p>Save the group first to see the preview.</p>');
			return;
		}
		
		$loading.show();
		$content.removeClass('loaded').hide();
		
		// Make AJAX request to fetch preview
		$.ajax({
			url: nlfGroupData.ajaxurl,
			type: 'POST',
			data: {
				action: 'nlf_get_group_preview',
				group_id: groupId,
				nonce: nlfGroupData.nonce
			},
			success: function (response) {
				if (response.success && response.data.html) {
					$content.html(response.data.html);
					$loading.hide();
					$content.addClass('loaded').show();
					
					// Initialize frontend FAQ interactions
					if (typeof window.nlfInitFaq === 'function') {
						window.nlfInitFaq($content);
					}
				} else {
					$loading.html('<p>' + (response.data.message || 'Failed to load preview.') + '</p>');
				}
			},
			error: function () {
				$loading.html('<p>Error loading preview. Please save the group and try again.</p>');
			}
		});
	}

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
		// Initialize tabs
		if ($('.nlf-faq-tabs-nav').length) {
			initTabs();
			initThemeSelection();
			initCustomStyleToggle();
			initColorPickers();
		}

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


