<?php

// Default cache period in seconds (5 minutes).
if ( ! defined( 'HFSYNC_API_CACHE_PERIOD' ) ) {
	define( 'HFSYNC_API_CACHE_PERIOD', 300 );
}

function hfsync_api_config_missing() {
	$agency_uid   = get_option( 'hfsync_agency_uid' );
	$api_key      = get_option( 'hfsync_api_key' );
	$api_platform = get_option( 'hfsync_api_platform' );

	return empty( $agency_uid ) || empty( $api_key ) || empty( $api_platform );
}


/**
 * Get properties.
 *
 * @return array Array of properties.
 */
function hfsync_api_get_properties( $params = array(), $refresh = false ) {
	if ( ! get_option( 'hfsync_agency_uid' ) ) {
		return new WP_Error( 
			'missing_credentials', 
			__( 'Could not connect to hfsync. Missing agency uid.', 'hfsync' )
		);
	}

	$params['agencyUid'] = get_option( 'hfsync_agency_uid' );
	return hfsync_api_get( '/v2/properties/', $params, HFSYNC_API_CACHE_PERIOD, $refresh );
}

/**
 * Get property
 *
 * @return array Property details.
 */
function hfsync_api_get_property( $id, $refresh = false ) {
	return hfsync_api_get( "/v2/properties/{$id}/", array(), HFSYNC_API_CACHE_PERIOD, $refresh );
}

/**
 * Get property descriptions
 *
 * @return array Property descriptions.
 */
function hfsync_api_get_property_descriptions( $uid, $refresh = false ) {
	return hfsync_api_get( "/v2/propertydescriptions", array( 'propertyUid' => $uid ), HFSYNC_API_CACHE_PERIOD, $refresh );
}

/**
 * Get property descriptions
 *
 * @return array Property descriptions.
 */
function hfsync_api_get_property_photos( $uid, $refresh = false ) {
	return hfsync_api_get( "/v2/photos", array( 'propertyUid' => $uid ), HFSYNC_API_CACHE_PERIOD, $refresh );
}

/**
 * Get property descriptions
 *
 * @return array Property descriptions.
 */
function hfsync_api_get_property_reviews( $uid, $refresh = false ) {
	return hfsync_api_get( "/v2/reviews", array( 'propertyUid' => $uid ), HFSYNC_API_CACHE_PERIOD, $refresh );
}

/**
 * Get property amenities
 *
 * @return array Property amenities.
 */
function hfsync_api_get_property_amenities( $uid, $refresh = false ) {
	return hfsync_api_get( "/v2/amenities/{$uid}", array(), HFSYNC_API_CACHE_PERIOD, $refresh );
}

/**
 * Get property calendar pricing
 *
 * @return array Property calendar pricing.
 */
function hfsync_api_get_property_calendar_pricing( $uid, $from, $to, $refresh = false ) {
	return hfsync_api_get( "/v2/propertycalendar/{$uid}", array( 'from' => $from, 'to' => $to ), HFSYNC_API_CACHE_PERIOD, $refresh );
}

/**
 * Perform a Delete request on Hostfully API.
 * 
 * @param string $path Api path.
 * @param array $params Parameters.
 * @param int $ttl Cache time to live.
 * 
 * @return mixed.
 */
function hfsync_api_delete( $path ) {
	return hfsync_api_request( 'DELETE', $path, array(), array() );
}


/**
 * Perform a GET request on Hostfully API.
 * 
 * @param string $path Api path.
 * @param array $params Parameters.
 * @param int $ttl Cache time to live.
 * 
 * @return mixed.
 */
function hfsync_api_post( $path, $data = array() ) {
	return hfsync_api_request( 'POST', $path, array(), $data );
}

/**
 * Perform a GET request on Hostfully API.
 * 
 * @param string $path Api path.
 * @param array $params Parameters.
 * @param int $ttl Cache time to live.
 * 
 * @return mixed.
 */
function hfsync_api_get( $path, $params = array(), $ttl = 0, $refresh = false ) {
	return hfsync_api_request( 'GET', $path, $params, array(), $ttl, $refresh );
}


/**
 * Perform a api request on Hostfully API.
 * 
 * @param string $method Request method.
 * @param string $path Api path.
 * @param array $params Parameters.
 * @param array $data Body parameters.
 * @param int $ttl Cache time to live.
 * 
 * @return mixed.
 */
function hfsync_api_request( $method = 'get', $path, $params = array(), $data = array(), $ttl = 0, $refresh = false ) {
	if ( hfsync_api_config_missing() ) {
		return new WP_Error( 
			'missing_credentials', 
			__( 'Could not connect to hfsync. Missing api credentials.', 'hfsync' )
		);
	}

	if ( get_transient( 'hfsync_api_request_reached' ) ) {
		return new WP_Error( 
			'request_reached', 
			sprintf( 
				__( 'Api request limit reached, try after %s.', 'hfsync' ), 
				human_time_diff( get_transient( 'hfsync_api_request_expires' ) )
			)
		);
	}

	$api_platform = get_option( 'hfsync_api_platform' );
	$agency_uid   = get_option( 'hfsync_agency_uid' );
	$api_key      = get_option( 'hfsync_api_key' );
	$method       = strtoupper( $method );

	if ( 'GET' === $method ) {
		$cache_key = 'hfsync_cache_'. md5( $agency_uid . $path . serialize( $params ) );
		if ( $ttl > 0 && ! $refresh && false !== get_transient( $cache_key ) ) {
			return get_transient( $cache_key );
		}
	}

	if ( 'production' === $api_platform ) {
		$api_endpoint = 'https://api.hostfully.com';
	} else {
		$api_endpoint = 'https://sandbox-api.hostfully.com';
	}

	$url = $api_endpoint . $path;

	if ( ! empty( $params ) ) {
		$url = add_query_arg( $params, $url );
	}

	$args = array(
		'method'  => $method,
		'headers' => array(
			'X-HOSTFULLY-APIKEY' => $api_key
		),
	);

	if ( ! empty( $data ) ) {
		$args['body'] = json_encode( $data );
	}

	// log request.
	hfsync_log( 'API Request - ' . $path, $params );

	// Delete previously stored error.
	delete_option( 'hfsync_api_credentials_error' );

	$response = wp_remote_request( $url, $args );
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( $code > 399 ) {

		$error_code = 'hostfully_api_error';
		if ( 401 === $code ) {
			$error_code = 'wrong_api_key';
		} elseif ( 404 === $code ) {
			$error_code = 'not_exists';
		}

		$error_message = __( 'Hostfully Api Error' );
		if ( isset( $body['apiErrorMessage'] ) ) {
			$error_message = $body['apiErrorMessage'];
		}

		$response = new WP_Error( $error_code, $error_message );

		// if ( 401 === $code ) {
		// 	$response = new WP_Error( 'wrong_api_key', __( 'Wrong API Key.', 'hfsync' ) );
	
		// } elseif ( 404 === $code ) {
		// 	$response = new WP_Error( 'not_exists', __( 'Resource not found.', 'hfsync' ) );
	
		// } elseif ( 500 === $code ) {
		// 	$response = new WP_Error( 'not_exists', __( 'Resource not found.', 'hfsync' ) );
		// }
	}

	$headers = wp_remote_retrieve_headers( $response );

	$reset_period = HOUR_IN_SECONDS;
	$limit = isset( $headers['x-ratelimit-limit'] ) ? intval( $headers['x-ratelimit-limit'] ) : 1000;
	$remaining = isset( $headers['x-ratelimit-remaining'] ) ? intval( $headers['x-ratelimit-remaining'] ) : 500;

	// Store api request reset time.
	if ( ! get_transient( 'hfsync_api_request_expires' ) || $limit - 1 === $remaining ) {
		set_transient( 'hfsync_api_request_expires', time() + $reset_period, $reset_period );
	}

	if ( $expires_time = get_transient( 'hfsync_api_request_expires' ) ) {
		$expires_in = intval( $expires_time ) - time();
	} else {
		$expires_in = 300;
	}

	set_transient( 'hfsync_api_request_remaining', $remaining, $expires_in );

	// Set a transient cache.
	if ( 0 === $remaining ) {
		set_transient( 'hfsync_api_request_reached', time(), $expires_in );
	} else {
		delete_transient( 'hfsync_api_request_reached' );
	}

	// update_option( 'hfsync_api_last_request_time', current_time( 'mysql' ) );
	// Hostfully_Sync\Utils::d( $headers );

	if ( is_wp_error( $response ) ) {
		if ( 401 === $code ) {
			update_option( 'hfsync_api_credentials_error', $response->get_error_message() );
		}

		return $response;
	}

	if ( 'GET' === $method ) {
		if ( $ttl > 0 ) {
			set_transient( $cache_key, $body, $ttl );
		}
	}

	return $body;
}