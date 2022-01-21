<?php
/**
 * Event Hanlder.
 *
 * @package Hostfully_Sync
 */
namespace Hostfully_Sync;

use WP_REST_Server;
use WP_Error;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Event_Hanlder class
 */
class Event_Hanlder {

	const AUTO_SYNC_EVENT_NAME        = 'hfsync_auto_sync_cron';
	const SYNC_PROCESS_EVENT_NAME     = 'hfsync_sync_process_cron';
	const SYNC_PROCESS_ALT_EVENT_NAME = 'hfsync_sync_process_alt_cron';
	const SYNC_PROCESSOR_INTERVAL     = 5;

	/**
	 * Construct.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'maybe_register_auto_sync_event' ) );

		add_action( self::AUTO_SYNC_EVENT_NAME, array( __CLASS__, 'handle_auto_sync_event' ) );
		add_action( self::SYNC_PROCESS_EVENT_NAME, array( __CLASS__, 'handle_sync_process_event' ) );
		add_action( self::SYNC_PROCESS_ALT_EVENT_NAME, array( __CLASS__, 'handle_sync_process_event' ) );
		add_action( 'hfsync_schedule_sync_process_event', array( __CLASS__, 'schedule_sync_process_event' ) );

		add_action( 'hfsync_update_property_cron', array( __CLASS__, 'update_property_cron' ) );
		add_action( 'hfsync_update_property_alt_cron', array( __CLASS__, 'update_property_cron' ) );
	}

	/**
	 * Schedule auto sync event when enabled.
	 */
	public static function maybe_register_auto_sync_event() {
		$auto_sync     = 'yes' === get_option( 'hfsync_auto_sync_enabled', 'no' );
		$sync_interval = (int) get_option( 'hfsync_auto_sync_interval', 1440 );
		if ( $sync_interval < 1 ) {
			$sync_interval = 1;
		}

		$scheduled = wp_next_scheduled( self::AUTO_SYNC_EVENT_NAME );

		if ( $auto_sync && ! $scheduled ) {
			wp_schedule_single_event( time() + ( $sync_interval * 60 ), self::AUTO_SYNC_EVENT_NAME );
			// wp_schedule_single_event( time() + 2, self::AUTO_SYNC_EVENT_NAME );
			return;
		}

		if ( ! $auto_sync && $scheduled ) {
			wp_unschedule_event( $scheduled, self::AUTO_SYNC_EVENT_NAME );
			wp_clear_scheduled_hook( self::AUTO_SYNC_EVENT_NAME );
		}
	}

	/**
	 * Handle auto sync event
	 */
	public static function handle_auto_sync_event() {
		$synchronizer = new Synchronizer( 'hfsync_auto_sync' );

		// End any existing job.
		$synchronizer->end();

		// Get all property ids.
		$property_uids = hfsync_get_all_property_uids();
		if ( is_wp_error( $property_uids ) ) {
			hfsync_log( 
				sprintf( 
					__( 'Unable to schedule auto sync. Api Error: %s' ), 
					$property_uids->get_error_message()
				)
			);
			return;
		}

		// Schedule.
		$can_schedule = $synchronizer->schedule( $property_uids );

		if ( ! is_wp_error( $can_schedule ) ) {
			do_action( 'hfsync_schedule_sync_process_event', 'hfsync_auto_sync' );
		}
	}

	/**
	 * Handle sync processor event.
	 */
	public static function handle_sync_process_event( $identifier ) {
		$synchronizer = new Synchronizer( $identifier );

		if ( $synchronizer->has_completed() ) {
			// All done, do not schedule next event.

		} elseif ( $synchronizer->has_started() ) {
			$synchronizer->process();
			do_action( 'hfsync_sync_process_cron', $identifier );

		} elseif ( $synchronizer->is_scheduled() ) {
			$synchronizer->start();
			do_action( 'hfsync_sync_process_cron', $identifier );
		}
	}

	/**
	 * Schedule sync processor event.
	 */
	public static function schedule_sync_process_event( $identifier ) {
		if ( self::SYNC_PROCESS_EVENT_NAME === current_filter() ) {
			wp_schedule_single_event( time() + self::SYNC_PROCESSOR_INTERVAL, self::SYNC_PROCESS_ALT_EVENT_NAME, array( $identifier ) );
		} else {
			wp_schedule_single_event( time() + self::SYNC_PROCESSOR_INTERVAL, self::SYNC_PROCESS_EVENT_NAME, array( $identifier ) );
		}
	}

	/**
	 * Update property
	 * 
	 * @param string $property_uid Hostfully property uid
	 */
	public static function update_property_cron( $property_uid ) {
		$property = new Property( $property_uid );
		$update = $property->get_id() > 0;

		try {
			$property->ignore_api_cached();
			$property->import_now();

		} catch ( Exception $e ) {
			if ( $update ) {
				hfsync_log( sprintf( esc_html__( 'Property Update Error: %s', 'hfsync' ), $e->getMessage() ) );
			} else {
				hfsync_log( sprintf( esc_html__( 'Property Create Error: %s', 'hfsync' ), $e->getMessage() ) );
			}
		}
	}
}