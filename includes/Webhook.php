<?php
/**
 * Webhook Class File.
 *
 * @package Hostfully_Sync
 */
namespace Hostfully_Sync;

use WP_Error;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Webhook Class
 */
class Webhook {

	public static $event_types = array(
		'NEW_PROPERTY',
		'UPDATED_PROPERTY',
		'DELETED_PROPERTY',
		'ACTIVATED_PROPERTY',
		'ACTIVATED_PROPERTY',
	);

	/**
	 * Organize map location data combining lat/lng and address
	 */
	public static function handle_event( $event ) {
		$event_type = $event['event_type'];
		$property_uid = $event['property_uid'];

		switch ( $event_type ) :

			case 'NEW_PROPERTY':

				try {
					$property = new Property( $property_uid );
					$property->ignore_api_cached();
					$property->import_now();

				} catch ( Exception $e ) {
					hfsync_log( sprintf( 'Property creation error: %s', $e->getMessage() ) );
				}

				// Update in 10 minutes.
				if ( ! wp_next_scheduled( 'hfsync_update_property_cron', array( $property_uid ) ) ) {
					wp_schedule_single_event( time() + 1800, 'hfsync_update_property_cron', array( $property_uid ) );
				}

				// Update after 1 hour.
				if ( ! wp_next_scheduled( 'hfsync_update_property_alt_cron', array( $property_uid ) ) ) {
					wp_schedule_single_event( time() + 1800, 'hfsync_update_property_alt_cron', array( $property_uid ) );
				}

				$response = array( 'message' => __( 'OK', 'hfsync' ) );
				break;


			case 'UPDATED_PROPERTY':

				try {
					$property = new Property( $property_uid );
					$property->ignore_api_cached();
					$property->import_now();

				} catch ( Exception $e ) {
					hfsync_log( sprintf( 'Property Update Error: %s', $e->getMessage() ) );
				}

				$response = array( 'message' => __( 'OK', 'hfsync' ) );
				break;


			case 'DELETED_PROPERTY':

				$property = new Property( $property_uid );
				if ( $property->get_id() ) {
					wp_trash_post( $property->get_id() );
				}

				$response = array( 'message' => __( 'OK', 'hfsync' ) );
				break;


			case 'ACTIVATED_PROPERTY':
				$property = new Property( $property_uid );
				if ( $property->get_id() ) {
					wp_update_post(
						array(
							'ID'          => $property->get_id(),
							'post_status' => 'publish',
						)
					);
				}

				$response = array( 'message' => __( 'OK', 'hfsync' ) );
				break;


			case 'DEACTIVATED_PROPERTY':
				$property = new Property( $property_uid );
				if ( $property->get_id() ) {
					wp_update_post(
						array(
							'ID'          => $property->get_id(),
							'post_status' => 'pending',
						)
					);
				}

				$response = array( 'message' => __( 'OK', 'hfsync' ) );
				break;
	
			default:
				$response = array( 'message' => sprintf( __( 'Thanks, but we aren\'t handling %s event.' ), $event_type ) );

		endswitch;

		return $response;
	}

	public static function register_webhooks( $callback_url = '' ) {
		$agency_uid = get_option( 'hfsync_agency_uid' );
		if ( ! $agency_uid ) {
			return new WP_Error( 
				'missing_credentials', 
				__( 'Could not connect to hfsync. Missing api credentials.', 'hfsync' )
			);
		}

		if ( ! $callback_url ) {
			$callback_url = rest_url( 'hfsync/v1/hostfully-event' );
		}

		$webhooks = hfsync_api_get( "/v2/webhooks", array( 'agencyUid' => $agency_uid ) );
		if ( is_wp_error( $webhooks ) ) {
			return $webhooks;
		}

		// Utils::p( $webhooks );

		$missing_event_types = self::$event_types;
		foreach ( $missing_event_types as $i => $event_type ) {
			foreach ( $webhooks as $webhook ) {
				if ( $webhook['callbackUrl'] === $callback_url && $webhook['eventType'] === $event_type ) {
					unset( $missing_event_types[ $i ] );

					// Utils::p( 'Exists' );
					// Utils::p( $webhook );

					break;
				}
			}
		}

		if ( empty( $missing_event_types ) ) {
			return 0;
		}

		$created = 0;
		foreach ( $missing_event_types as $event_type ) {
			$data = array(
				'objectUid'   => $agency_uid,
				'eventType'   => $event_type,
				'agencyUid'   => $agency_uid,
				'webHookType' => 'POST_JSON',
				'callbackUrl' => $callback_url
			);

			$create = hfsync_api_post( "/v2/webhooks", $data );

			// Utils::p( 'Created' );
			// Utils::p( $create );

			if ( is_wp_error( $create ) ) {
				return $create;
			}

			++ $created;
		}

		return $created;
	}

	public static function delete_webhooks( $callback_url = '' ) {
		$agency_uid = get_option( 'hfsync_agency_uid' );
		if ( ! $agency_uid ) {
			return new WP_Error( 
				'missing_credentials', 
				__( 'Could not connect to hfsync. Missing api credentials.', 'hfsync' )
			);
		}

		if ( ! $callback_url ) {
			$callback_url = rest_url( 'hfsync/v1/hostfully-event' );
		}

		$webhooks = hfsync_api_get( "/v2/webhooks", array( 'agencyUid' => $agency_uid ) );
		if ( is_wp_error( $webhooks ) ) {
			return $webhooks;
		}

		$deleted = 0;
		foreach ( $webhooks as $webhook ) {
			if ( $webhook['callbackUrl'] === $callback_url && in_array( $webhook['eventType'], self::$event_types ) ) {
				$delete = hfsync_api_delete( "/v2/webhooks/" . $webhook['uid'] );
				if ( ! is_wp_error( $delete ) ) {
					++ $deleted;
				}
			}
		}

		return $deleted;
	}

	public static function missing_webhooks( $callback_url = '' ) {
		$agency_uid = get_option( 'hfsync_agency_uid' );
		if ( ! $agency_uid ) {
			return new WP_Error( 
				'missing_credentials', 
				__( 'Could not connect to hfsync. Missing api credentials.', 'hfsync' )
			);
		}

		if ( ! $callback_url ) {
			$callback_url = rest_url( 'hfsync/v1/hostfully-event' );
		}

		$webhooks = hfsync_api_get( "/v2/webhooks", array( 'agencyUid' => $agency_uid ) );
		if ( is_wp_error( $webhooks ) ) {
			return $webhooks;
		}

		// Utils::p( $webhooks );

		$missing_event_types = self::$event_types;
		foreach ( $missing_event_types as $i => $event_type ) {
			foreach ( $webhooks as $webhook ) {
				if ( $webhook['callbackUrl'] === $callback_url && $webhook['eventType'] === $event_type ) {
					unset( $missing_event_types[ $i ] );

					// Utils::p( 'Exists' );
					// Utils::p( $webhook );

					break;
				}
			}
		}

		if ( empty( $missing_event_types ) ) {
			return array();
		}
		
		return $missing_event_types;
	}
}