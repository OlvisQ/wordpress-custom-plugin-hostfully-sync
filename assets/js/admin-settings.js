(function ($) {
	var
		runSyncProgress = function () {
			$.get(hfsyncVar.syncProgress)
				.done(function (r) {
					$('#hfsync-sync-message').html(r.message);
					if (r.continue) {
						setTimeout(runSyncProgress, 1000);
					}
				})
				.fail(function (xhr) {
					if (xhr.status === 400) {
						$('#hfsync-sync-message').html(hfsyncVar.ajaxNoResponse);
					} else if (xhr.status === 500) {
						$('#hfsync-sync-message').html(hfsyncVar.serverSideError);
					}

					if (xhr.responseJSON && xhr.responseJSON.message) {
						$('#hfsync-sync-message').append(xhr.responseJSON.message);
					}
				});
		},
		updateSyncLogs = function () {
			$.get(hfsyncVar.syncLogs)
				.done(function (r) {
					if (!hfsyncVar.useAjaxProcessor && r.message) {
						$('#hfsync-sync-message').html(r.message);
					}

					if (r.logs) {
						$('#hfsync-sync-logs').empty();
						addSyncLog(r.logs)
					}

					if (r.continue) {
						setTimeout(updateSyncLogs, 5000);
					} else {
						$('.hfsync-sync-btn').removeClass('ld').prop('disabled', false);
						$('.hfsync-sync-cancel-btn').prop('disabled', true).hide();
						$('.hfsync-cleanup-btn').show();
					}
				})
				.fail(function (xhr) {
					$('.hfsync-sync-btn').removeClass('ld').prop('disabled', false);
					$('.hfsync-sync-cancel-btn').prop('disabled', true).hide();
					$('.hfsync-cleanup-btn').show();

					if (xhr.status === 400) {
						addSyncLog(hfsyncVar.ajaxNoResponse);
					} else if (xhr.status === 500) {
						addSyncLog(hfsyncVar.serverSideError);
					}

					if (xhr.responseJSON && xhr.responseJSON.message) {
						$('#hfsync-sync-message').append(xhr.responseJSON.message);
					}
				});
		},
		addSyncLog = function (log) {
			if (Array.isArray(log)) {
				for (var i = 0; i < log.length; i++) {
					addSyncLog(log[i]);
				}
			} else {
				$('#hfsync-sync-logs').append('<li>' + log + '</li>');
			}
		},
		webhookSuccess = function (r) {
			$('#hfsync-webhook-message').html(r.message);
		},
		webhookFailure = function (xhr) {
			if (xhr.status === 400) {
				$('#hfsync-webhook-message').html(hfsyncVar.ajaxNoResponse);
			} else if (xhr.status === 500) {
				$('#hfsync-webhook-message').html(hfsyncVar.serverSideError);
			} else if (xhr.responseJSON && xhr.responseJSON.message) {
				$('#hfsync-webhook-message').html(xhr.responseJSON.message);
			}
		};

	$(document.body).on('click', '.hfsync-sync-btn', function (e) {
		e.preventDefault();
		var $button = $(this);
		if ($button.hasClass('ld')) {
			return false;
		}

		$button.addClass('ld').prop('disabled', true);
		$('.hfsync-sync-cancel-btn').prop('disabled', false).show();

		$([document.documentElement, document.body]).animate({
			scrollTop: $button.closest('.hfsync-box').offset().top - 36
		}, 500);

		$('#hfsync-sync-container').show();
		$('#hfsync-sync-logs').empty();
		$('.hfsync-cleanup-btn').hide();

		$.post(hfsyncVar.scheduleSync)
			.done(function (r) {
				$('#hfsync-sync-message').html(r.message);
				updateSyncLogs();

				// Use ajax if cron disabled.
				if (hfsyncVar.useAjaxProcessor) {
					if (r.success || r.code === 'scheduled_already' || r.code === 'started_already') {
						runSyncProgress();
					}
				}
			})
			.fail(function (xhr) {
				if (xhr.responseJSON && (xhr.responseJSON.code === 'started_already' || xhr.responseJSON.code === 'scheduled_already')) {
					if (hfsyncVar.useAjaxProcessor) {
						if (r.success || r.code === 'scheduled_already' || r.code === 'started_already') {
							runSyncProgress();
						}
					}
				} else {
					$button.removeClass('ld').prop('disabled', false);
					$('.hfsync-cleanup-btn').show();

					if (xhr.status === 400) {
						$('#hfsync-sync-message').html(hfsyncVar.ajaxNoResponse);
					} else if (xhr.status === 500) {
						$('#hfsync-sync-message').html(hfsyncVar.serverSideError);
					}
				}
			});

		return false;
	});


	$(document.body).on('click', '.hfsync-sync-cancel-btn', function (e) {
		var $button = $(this);
		if ($button.hasClass('ld')) {
			return false;
		}

		$button.addClass('ld').prop('disabled', true);

		$.post(hfsyncVar.cancelSync)
			.done(function (r) {
				if (r.success) {
					$('#hfsync-sync-message').html(r.message);
				}

				$('.hfsync-sync-btn').prop('disabled', false).show();
				$button.removeClass('ld').hide();
			});

		return false;

	});

	$(document.body).on('click', '.hfsync-sync-cancel-btn', function (e) {
		var $button = $(this);
		if ($button.hasClass('ld')) {
			return false;
		}

		$button.addClass('ld').prop('disabled', true);

		$.post(hfsyncVar.cancelSync)
			.done(function (r) {
				if (r.success) {
					$('#hfsync-sync-message').html(r.message);
				}

				$('.hfsync-sync-btn').prop('disabled', false).show();
				$button.removeClass('ld').hide();
			});

		return false;

	});

	$(document.body).on('click', '#hfsync-register-webhooks-btn', function (e) {
		var $button = $(this);
		if ($button.hasClass('ld')) {
			return false;
		}

		$button.addClass('ld');
		$('#hfsync-webhook-box button').prop('disabled', true);
		$('#hfsync-webhook-message').empty();

		$.post(hfsyncVar.registerWebhooks, { callback_url: $('#webhook_callback_url').val() })
			.done(webhookSuccess)
			.fail(webhookFailure)
			.always(function () {
				$button.removeClass('ld');
				$('#hfsync-webhook-box button').prop('disabled', false);
			});

		return false;

	});

	$(document.body).on('click', '#hfsync-delete-webhooks-btn', function (e) {
		var $button = $(this);
		if ($button.hasClass('ld')) {
			return false;
		}

		$button.addClass('ld');
		$('#hfsync-webhook-box button').prop('disabled', true);
		$('#hfsync-webhook-message').empty();

		$.post(hfsyncVar.deleteWebhooks, { callback_url: $('#webhook_callback_url').val() })
			.done(webhookSuccess)
			.fail(webhookFailure)
			.always(function () {
				$button.removeClass('ld');
				$('#hfsync-webhook-box button').prop('disabled', false);
			});

		return false;

	});

	$(document.body).on('click', '#hfsync-clear-webhooks-btn', function (e) {
		var $button = $(this);
		if ($button.hasClass('ld')) {
			return false;
		}

		$button.addClass('ld');
		$('#hfsync-webhook-box button').prop('disabled', true);
		$('#hfsync-webhook-message').empty();

		$.post(hfsyncVar.deleteWebhooks, { callback_url: $('#webhook_callback_url').val() })
			.done(webhookSuccess)
			.fail(webhookFailure)
			.always(function () {
				$button.removeClass('ld');
				$('#hfsync-webhook-box button').prop('disabled', false);
			});

		return false;

	});

	$(document.body).on('click', '#hfsync-check-webhooks-btn', function (e) {
		var $button = $(this);
		if ($button.hasClass('ld')) {
			return false;
		}

		$button.addClass('ld');
		$('#hfsync-webhook-box button').prop('disabled', true);
		$('#hfsync-webhook-message').empty();

		$.post(hfsyncVar.checkWebhooks, { callback_url: $('#webhook_callback_url').val() })
			.done(webhookSuccess)
			.fail(webhookFailure)
			.always(function () {
				$button.removeClass('ld');
				$('#hfsync-webhook-box button').prop('disabled', false);
			});

		return false;

	});

	$(document).ready(function () {
		$.post(hfsyncVar.syncStatus)
			.done(function (r) {
				if (r.success && r.running) {
					$('.hfsync-sync-btn').addClass('ld').prop('disabled', true);
					$('.hfsync-sync-cancel-btn').prop('disabled', false).show();

					$('#hfsync-sync-container').show();
					$('#hfsync-sync-logs').empty();
					$('.hfsync-cleanup-btn').hide();

					updateSyncLogs();

					// Use ajax if cron disabled.
					if (hfsyncVar.useAjaxProcessor) {
						if (r.success || r.code === 'scheduled_already' || r.code === 'started_already') {
							runSyncProgress();
						}
					}
				}
			});
	});

})(jQuery);