/**
 * IMGVerse Admin JavaScript
 *
 * @package IMGVerse
 * @author Krafty Sprouts Media, LLC
 * @since 1.0.0
 */

(function ($) {
	'use strict';

	$(document).ready(function () {
		var $navButtons = $('.imgv-settings__nav-button');

		$navButtons.on('click', function () {
			var target = $(this).data('target');
			var $section = $('#' + target);

			$navButtons.removeClass('is-active');
			$(this).addClass('is-active');

			if ($section.length) {
				$('html, body').animate(
					{ scrollTop: $section.offset().top - 60 },
					220
				);
			}
		});

		$('#imgv-image-quality').on('input', function () {
			$(this)
				.siblings('.imgv-quality-value')
				.text($(this).val() + '%');
		});

		$('#imgv-clear-cache').on('click', function () {
			if (
				!confirm(
					(imgv_ajax.strings &&
						imgv_ajax.strings.confirm_clear_cache) ||
						'Are you sure you want to clear all cache?'
				)
			) {
				return;
			}

			var $btn = $(this);
			$btn.prop('disabled', true).text('Clearing…');

			$.post(imgv_ajax.ajax_url, {
				action: 'imgv_clear_cache',
				nonce: imgv_ajax.nonce,
			})
				.done(function (response) {
					var data =
						typeof response === 'string'
							? JSON.parse(response)
							: response;
					alert(
						data.message ||
							(imgv_ajax.strings &&
								imgv_ajax.strings.cache_cleared) ||
							'Cache cleared successfully.'
					);
					location.reload();
				})
				.fail(function () {
					alert(
						(imgv_ajax.strings && imgv_ajax.strings.error) ||
							'Error occurred. Please try again.'
					);
				})
				.always(function () {
					$btn.prop('disabled', false).text('Clear Cache');
				});
		});

		$('#imgv-test-api').on('click', function () {
			var $btn = $(this);
			$btn.prop('disabled', true).text('Testing…');

			$.post(imgv_ajax.ajax_url, {
				action: 'imgv_search',
				nonce: imgv_ajax.nonce,
				query: 'nature',
				page: 1,
			})
				.done(function (response) {
					var data =
						typeof response === 'string'
							? JSON.parse(response)
							: response;
					if (data.success) {
						alert(
							(imgv_ajax.strings &&
								imgv_ajax.strings.api_success) ||
								'API connection successful!'
						);
					} else {
						alert(
							((imgv_ajax.strings &&
								imgv_ajax.strings.api_failed) ||
								'API connection failed:') +
								' ' +
								(data.message || 'Unknown error')
						);
					}
				})
				.fail(function () {
					alert(
						(imgv_ajax.strings && imgv_ajax.strings.api_failed) ||
							'API connection failed.'
					);
				})
				.always(function () {
					$btn.prop('disabled', false).text('Test Openverse');
				});
		});
	});
})(jQuery);
