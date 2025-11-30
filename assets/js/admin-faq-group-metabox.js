(function ($) {
	'use strict';

	// Tab Switching with ARIA support
	function initTabs() {
		var $tabs = $('.nlf-faq-tab-button');
		var $panels = $('.nlf-faq-tab-panel');

		$tabs.on('click', function (e) {
			e.preventDefault();
			switchToTab($(this));
		});

		// Keyboard navigation for tabs
		$tabs.on('keydown', function (e) {
			var $currentTab = $(this);
			var currentIndex = $tabs.index($currentTab);
			var $targetTab = null;

			switch(e.key) {
				case 'ArrowRight':
				case 'ArrowDown':
					$targetTab = $tabs.eq((currentIndex + 1) % $tabs.length);
					break;
				case 'ArrowLeft':
				case 'ArrowUp':
					$targetTab = $tabs.eq((currentIndex - 1 + $tabs.length) % $tabs.length);
					break;
				case 'Home':
					$targetTab = $tabs.first();
					break;
				case 'End':
					$targetTab = $tabs.last();
					break;
				default:
					return;
			}

			if ($targetTab) {
				e.preventDefault();
				switchToTab($targetTab);
				$targetTab.focus();
			}
		});

		// Tab switch button (e.g., from empty state)
		$(document).on('click', '[data-switch-tab]', function (e) {
			e.preventDefault();
			var targetTab = $(this).data('switch-tab');
			var $targetTabBtn = $tabs.filter('[data-tab="' + targetTab + '"]');
			if ($targetTabBtn.length) {
				switchToTab($targetTabBtn);
			}
		});
	}

	function switchToTab($tab) {
		var targetTab = $tab.data('tab');
		var $tabs = $('.nlf-faq-tab-button');
		var $panels = $('.nlf-faq-tab-panel');

		// Update ARIA and classes
		$tabs.removeClass('active').attr('aria-selected', 'false');
		$tab.addClass('active').attr('aria-selected', 'true');

		// Update panels
		$panels.removeClass('active').attr('hidden', true);
		var $targetPanel = $panels.filter('[data-tab="' + targetTab + '"]');
		$targetPanel.addClass('active').removeAttr('hidden');

		// Focus panel for screen readers
		$targetPanel.focus();

		// Load preview when switching to preview tab
		if (targetTab === 'preview') {
			loadLivePreview();
		}
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
		var $container = $('.nlf-preview-container');
		var $loading = $container.find('.nlf-preview-loading');
		var $content = $container.find('.nlf-preview-content');
		var groupId = $container.data('group-id');
		
		if (!groupId || groupId === '0' || groupId === 0) {
			return; // Empty state is shown in PHP
		}
		
		// Show loading skeleton
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
					$loading.fadeOut(200, function () {
						$content.addClass('loaded').fadeIn(200);
						
						// Initialize frontend FAQ interactions
						if (typeof window.nlfInitFaq === 'function') {
							window.nlfInitFaq($content.get(0));
						}
					});
				} else {
					$loading.html('<div class="nlf-preview-error"><span class="dashicons dashicons-warning"></span><p>' + (response.data.message || 'Failed to load preview.') + '</p></div>');
				}
			},
			error: function () {
				$loading.html('<div class="nlf-preview-error"><span class="dashicons dashicons-warning"></span><p>Error loading preview. Please save the group and try again.</p></div>');
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

	// Unsaved Changes Warning
	function initUnsavedWarning() {
		var hasChanges = false;
		var $indicator = $('<div class="nlf-unsaved-indicator"><span class="dashicons dashicons-warning"></span><span>Unsaved changes</span></div>');
		$('body').append($indicator);

		// Track changes
		$('#post').on('change input', 'input, select, textarea', function () {
			if (!hasChanges) {
				hasChanges = true;
				$indicator.addClass('visible');
			}
		});

		// Clear on save
		$('#publish, #save-post').on('click', function () {
			hasChanges = false;
			$indicator.removeClass('visible');
		});

		// Warn before leaving
		$(window).on('beforeunload', function (e) {
			if (hasChanges) {
				var message = 'You have unsaved changes. Are you sure you want to leave?';
				e.returnValue = message;
				return message;
			}
		});
	}

	// Device Preview Toggle
	function initDeviceToggle() {
		$('.nlf-device-btn').on('click', function () {
			var device = $(this).data('device');
			
			$('.nlf-device-btn').removeClass('active');
			$(this).addClass('active');
			
			$('.nlf-preview-viewport').attr('data-device', device);
		});

		// Manual refresh button
		$('#nlf-manual-refresh').on('click', function (e) {
			e.preventDefault();
			loadLivePreview();
		});
	}

	// Help Tooltips Toggle
	function initHelpTooltips() {
		$('.nlf-help-trigger').on('click', function (e) {
			e.preventDefault();
			var $helpText = $(this).closest('tr').find('.nlf-help-text');
			$helpText.slideToggle(200);
			
			var expanded = $helpText.is(':visible');
			$(this).attr('aria-expanded', expanded);
		});
	}

	// Show table when adding first item from empty state
	function showTableOnFirstAdd() {
		$(document).on('click', '.nlf-empty-state .button', function () {
			$('.nlf-empty-state').fadeOut(200, function () {
				$('.nlf-faq-group-table').fadeIn(200);
			});
		});
	}

	$(function () {
		// Initialize all components
		if ($('.nlf-faq-tabs-nav').length) {
			initTabs();
			initThemeSelection();
			initCustomStyleToggle();
			initColorPickers();
			initUnsavedWarning();
			initDeviceToggle();
			initHelpTooltips();
			showTableOnFirstAdd();
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


