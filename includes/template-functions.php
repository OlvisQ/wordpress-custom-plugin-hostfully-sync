<?php
function hfsync_get_property_guests( $id = 0 ) {
	if ( ! $id ) {
		$id = get_the_ID();
		if ( ! $id || ! get_post( $id ) ) {
			return '';
		}
	}

	$guests = get_post_meta( $id, 'maximum_guests', true );
	if ( ! $guests ) {
		return __( 'No guests.' );
	}

	return sprintf( _n( '%s guest', '%s guests', $guests, 'hfsync' ), $guests );
}

function hfsync_get_property_bedrooms( $id = 0 ) {
	if ( ! $id ) {
		$id = get_the_ID();
		if ( ! $id || ! get_post( $id ) ) {
			return '';
		}
	}

	$bedrooms = get_post_meta( $id, 'bedrooms', true );
	if ( ! $bedrooms ) {
		return __( 'No bedrooms.' );
	}

	return sprintf( _n( '%s bedroom', '%s bedrooms', $bedrooms, 'hfsync' ), $bedrooms );
}

function hfsync_get_property_bathrooms( $id = 0 ) {
	if ( ! $id ) {
		$id = get_the_ID();
		if ( ! $id || ! get_post( $id ) ) {
			return '';
		}
	}

	$bathrooms = get_post_meta( $id, 'bathrooms', true );
	if ( ! $bathrooms ) {
		return __( 'No bathrooms.' );
	}

	return sprintf( _n( '%s bathroom', '%s bathrooms', $bathrooms, 'hfsync' ), $bathrooms );
}

function hfsync_get_property_currency_symbol( $id = 0 ) {
	if ( ! $id ) {
		$id = get_the_ID();
		if ( ! $id || ! get_post( $id ) ) {
			return '';
		}
	}

	$currency_symbol = get_post_meta( $id, 'currency_symbol', true );

	// Default to dollar.
	if ( ! $currency_symbol ) {
		$currency_symbol = '$';
	}

	return $currency_symbol;
}

function hfsync_get_property_base_rate( $id = 0 ) {
	if ( ! $id ) {
		$id = get_the_ID();
		if ( ! $id || ! get_post( $id ) ) {
			return '';
		}
	}

	$base_daily_rate = (float) get_post_meta( $id, 'base_daily_rate', true );
	if ( ! $base_daily_rate ) {
		return '';
	}

	return sprintf( '%s%s', hfsync_get_property_currency_symbol(), $base_daily_rate );
}

function hfsync_get_property_minimum_expense( $id = 0 ) {
	if ( ! $id ) {
		$id = get_the_ID();
		if ( ! $id || ! get_post( $id ) ) {
			return '';
		}
	}

	$minimum_stay = intval( get_post_meta( $id, 'minimum_stay', true ) );
	$base_daily_rate = (float) get_post_meta( $id, 'base_daily_rate', true );
	if ( ! $minimum_stay || ! $base_daily_rate ) {
		return '';
	}

	$minimum_expense = $minimum_stay * $base_daily_rate;

	return sprintf( '%s%s', hfsync_get_property_currency_symbol(), $minimum_expense );
}

function hfsync_get_property_stay_expense( $checking, $checkout, $guests = 1, $id = 0 ) {
	if ( ! $id ) {
		$id = get_the_ID();
		if ( ! $id || ! get_post( $id ) ) {
			return '';
		}
	}

	$date1 = new DateTime( $checking );
	$date2 = new DateTime( $checkout );
	$interval = $date1->diff( $date2 );
	$total_nights = max( 1, $interval->days );

	$base_daily_rate = (float) get_post_meta( $id, 'base_daily_rate', true );
	if ( ! $base_daily_rate ) {
		return '';
	}

	$base_guests = (float) get_post_meta( $id, 'base_guests', true );
	$extra_guest_fee = (float) get_post_meta( $id, 'extra_guest_fee', true );
	$taxation_rate = (float) get_post_meta( $id, 'taxation_rate', true );
	$cleaning_fee = (float) get_post_meta( $id, 'cleaning_fee_amount', true );

	$dates = hfsync_get_dates_from_range( $checking, $checkout );
	// strip out last date.
	array_pop( $dates );
	$pricing = hfsync_get_property_pricing( $id );

	// echo '<pre>';
	// print_r( $pricing );
	// echo '</pre>';
	// exit;

	$stay_expense = 0;
	foreach ( $dates as $date ) {
		if ( isset( $pricing[ $date ] ) ) {
			$stay_expense += $pricing[ $date ];
		} else {
			$stay_expense += $base_daily_rate;
		}

		if ( $guests > $base_guests && $extra_guest_fee > 0 ) {
			$stay_expense += ( $guests - $base_guests ) * $extra_guest_fee;
		}
	}

	if ( $taxation_rate > 0 ) {
		$stay_expense = $stay_expense + ( $stay_expense / 100 ) * $taxation_rate;
	}

	if ( $cleaning_fee > 0 ) {
		$stay_expense += $cleaning_fee;
	}

	$stay_expense = ceil( $stay_expense );

	return sprintf( '%s%s', hfsync_get_property_currency_symbol(), $stay_expense );
}