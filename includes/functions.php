<?php
/**
 * Clear api request cache and other transients.
 */
function hfsync_clear_all_cache() {
	global $wpdb;

	// Clear transient.
	$match = '%\_hfsync_cache\_%';
	$options = $wpdb->get_col("SELECT option_name FROM $wpdb->options WHERE option_name LIKE '$match'");
	if ( ! empty( $options ) ) {
		foreach ( $options as $option ) {
			delete_option( $option );
		}
	}

	// Clear api credentials error.
	delete_option( 'hfsync_api_credentials_error' );

	// Clear opcache.
	if ( function_exists( 'opcache_reset' ) ) {
		opcache_reset();
	}
}

function hfsync_delete_all() {
	$property_ids = get_posts(
		array(
			'post_type'      => Hostfully_Sync\Config::PROPERTY_POST_TYPE,
			'post_status'    => 'any',
			'meta_key'       => '_hfsync_imported',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	if ( ! empty( $property_ids ) ) {
		foreach ( $property_ids as $property_id ) {
			wp_delete_post( $property_id );		
		}
	}

	$media_ids = get_posts( 
		array( 
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => '_hfsync_imported'
		)
	);

	if ( ! empty( $media_ids ) ) {
		foreach ( $media_ids as $media_id ) {
			wp_delete_post( $media_id );		
		}
	}
}

/**
 * Get all properties.
 *
 * @return mixed All properties array or WP_Error.
 */
function hfsync_get_all_property_uids() {

	$offset = 0;
	$limit = 5000;
	$property_uids = array();

	for ( $i = 0; $i < 100; $i ++ ) {
		$api_properties = hfsync_api_get_properties( array( 'offset' => $offset, 'limit' => $limit ) );
		if ( is_wp_error( $api_properties ) ) {
			return $api_properties;
		}

		if ( empty( $api_properties['propertiesUids'] ) ) {
			break;
		}

		$property_uids = array_merge( $property_uids, $api_properties['propertiesUids'] );

		$offset += $limit;
	}

	return $property_uids;
}

function hfsync_get_local_property_id( $property_uid ) {
	$property_ids = get_posts(
		array(
			'post_type'      => Hostfully_Sync\Config::PROPERTY_POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'meta_key'       => Hostfully_Sync\Config::PROPERTY_UID_META_KEY,
			'meta_value'     => $property_uid,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	if ( ! empty( $property_ids ) ) {
		return array_shift( $property_ids );
	}

	return 0;
}

function hfsync_get_property_pricing( $id ) {
	$pricing_text = get_post_meta( $id, 'pricing', true );
	if ( empty( $pricing_text ) ) {
		return array();
	}

	$pricing = array();

	$pricing_lines = explode( "\n", $pricing_text );
	foreach ( $pricing_lines as $pricing_line ) {
		$parts = explode( " ", $pricing_line );
		$parts = array_filter( $parts );

		if ( count( $parts ) === 2 ) {
			list( $date, $price ) = $parts;
			$pricing[ $date ] = (float) $price;
		}
	}

	return $pricing;
}

function hfsync_get_dates_from_range( $start, $end, $format = 'Y-m-d' ) {
    $array = array();
    $interval = new DateInterval('P1D');

    $realEnd = new DateTime($end);
    $realEnd->add($interval);

    $period = new DatePeriod(new DateTime($start), $interval, $realEnd);

    foreach($period as $date) { 
        $array[] = $date->format($format); 
    }

    return $array;
}

function hfsync_normalize_price( $price ) {

	// Remove trailing zeroes.
	if ( '.00' === substr( $price, -3, 3 ) ) {
		$price = substr( $price, 0, strlen( $price ) - 3 );
	}

	return $price;
}

/**
 * Get hostfully property uid
 */
function hfsync_property_uid( $property_id ) {
	return get_post_meta( $property_id, Hostfully_Sync\Config::PROPERTY_UID_META_KEY, true );
}

function hfsync_sort_by_display_order( $a, $b ) {
	if ( $a['displayOrder'] === $b['displayOrder'] ) {
        return 0;
    }

	return $a['displayOrder'] < $b['displayOrder'] ? -1 : 1;
}

/**
 * Log
 */
function hfsync_log( $message, $context = array() ) {
	if ( empty( $context ) ) {
		$context = array(
			'Cron' => (int) wp_doing_cron(),
			'Ajax' => (int) wp_doing_ajax()
		);
	}

	do_action(
		'swpl_log',
		'Hostfully_Sync',
		$message,
		$context
	);
}
