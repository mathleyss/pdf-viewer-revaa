/* global jQuery, revaaPdfAdmin */
(function ($) {
	'use strict';

	if (typeof revaaPdfAdmin === 'undefined') return;

	$('#revaa-upload-form').on('submit', function (e) {
		e.preventDefault();
		var $btn = $(this).find('button[type=submit]');
		var $msg = $('#revaa-upload-message');

		var formData = new FormData(this);
		formData.append('action', 'revaa_upload_pdf');

		$btn.prop('disabled', true).text('Upload en cours…');
		$msg.text('').css('color', '');

		$.ajax({
			url: revaaPdfAdmin.ajaxUrl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function (response) {
				$btn.prop('disabled', false).text('Uploader');
				if (response.success) {
					$msg.text('Fichier uploadé avec succès.').css('color', 'green');
					setTimeout(function () { location.reload(); }, 1200);
				} else {
					$msg.text(
						(response.data && response.data.error) || 'Erreur lors de l\'upload.'
					).css('color', '#d63638');
				}
			},
			error: function () {
				$btn.prop('disabled', false).text('Uploader');
				$msg.text('Erreur réseau.').css('color', '#d63638');
			},
		});
	});

	$(document).on('click', '.revaa-delete-btn', function () {
		var $btn = $(this);
		var filename = $btn.data('filename');
		if (!confirm('Supprimer « ' + filename + ' » définitivement ?')) return;

		$btn.prop('disabled', true).text('Suppression…');

		$.post(revaaPdfAdmin.ajaxUrl, {
			action: 'revaa_delete_pdf',
			nonce: revaaPdfAdmin.nonce,
			filename: filename,
		}, function (response) {
			if (response.success) {
				location.reload();
			} else {
				alert((response.data && response.data.error) || 'Erreur.');
				$btn.prop('disabled', false).text('Supprimer');
			}
		});
	});
})(jQuery);
