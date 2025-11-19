(function ($) {
	'use strict';

	function renumberActiveCheckboxes() {
		$('#aio-faq-questions-body .aio-faq-question-row').each(function (index) {
			var $row = $(this);
			var $checkbox = $row.find('.aio-faq-visible-cell input[type="checkbox"]');
			if ($checkbox.length) {
				$checkbox.attr('name', 'aio_faq_active[' + index + ']');
			}
		});
	}

	$(function () {
		var $body = $('#aio-faq-questions-body');
		var rowTemplate = $('#tmpl-aio-faq-row').html();

		$('#aio-faq-add-row').on('click', function (e) {
			e.preventDefault();

			var index = $body.find('.aio-faq-question-row').length;
			var html = rowTemplate.replace(/{{index}}/g, String(index));

			$body.append(html);
			renumberActiveCheckboxes();
		});

		$body.on('click', '.aio-faq-remove-row', function (e) {
			e.preventDefault();
			$(this).closest('.aio-faq-question-row').remove();
			renumberActiveCheckboxes();
		});

		// Basic drag handle styling – actual drag-and-drop could be added with jQuery UI if desired.
	});
})(jQuery);


