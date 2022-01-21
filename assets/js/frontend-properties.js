(function ($) {
	const dateFormat = "yy-mm-dd";
	const maxCalendarDays = parseInt(hfsyncPropertiesVars.maxCalendarDays, 10) || 30;
	const minStay = parseInt(hfsyncPropertiesVars.minStay, 10) || 1;

	const getDate = (element) => {
		let date;
		try {
			date = $.datepicker.parseDate(dateFormat, element.value);
		} catch (error) {
			date = null;
		}
		return date;
	};

	const debounce = (func, timeout = 300) => {
		let timer;
		return (...args) => {
			clearTimeout(timer);
			timer = setTimeout(() => { func.apply(this, args); }, timeout);
		};
	};

	const handleAutoload = debounce(() => {
		const $button = $('.properties-more-btn');
		if ($button.length < 1) {
			$(document).off('scroll', handleAutoload);
		} else {
			const buttonTop = $button.offset().top;
			const viewPosition = $(document).scrollTop() + $(window).outerHeight();

			if (viewPosition > buttonTop - 100 && !$button.is(':disabled')) {
				$button.trigger('click');
			}
		}
	});

	$(document.body).on('click', '.properties-more-btn', function () {
		const $button = $(this);
		const attrs = $button.data('attrs');
		const filter = $button.data('filter');
		const page = parseInt($button.data('page'));
		const maxpages = parseInt($button.data('maxpages'));

		$button.prop('disabled', true);

		$.ajax({
			method: 'POST',
			url: hfsyncPropertiesVars.ajaxUrl + '?action=hfsync_get_properties_html',
			data: { attrs: attrs, filter: filter, page: page },
			success: (r) => {
				if (r.data.properties) {
					$('.properties').append(r.data.properties);
				}

				if (!r.data.properties || r.data.page === maxpages) {
					$button.parent('div').remove();
				} else {
					$button.data('page', r.data.page + 1);
					$button.prop('disabled', false);
				}
			},
			fail: () => {
				$button.prop('disabled', false);
			}
		});
		return false;
	});

	$(document).ready(function () {
		const $checkin = $('.hfsync-filters input[name="checkin"]').datepicker({
			dateFormat: dateFormat,
			minDate: 0,
			maxDate: '+' + (maxCalendarDays - minStay),
			dayNamesMin: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]
		});

		const $checkout = $('.hfsync-filters input[name="checkout"]').datepicker({
			dateFormat: dateFormat,
			maxDate: '+' + maxCalendarDays
		});

		$checkin.on("change", function () {
			var date = getDate(this);
			if (date) {
				date.setDate(date.getDate() + minStay);
			}

			$checkout.datepicker("option", "minDate", date);
			$checkout.datepicker("setDate", date);

			setTimeout(() => {
				$checkout.datepicker("show");
			}, 100);
		});

		$(document).on('scroll', handleAutoload);

	});
})(jQuery);