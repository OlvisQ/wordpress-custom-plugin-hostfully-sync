<?php
/**
 * Batch processor class
 *
 * @package Hostfully_Sync
 */
namespace Hostfully_Sync;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Synchronizer class
 */
class Synchronizer {

	protected $identifier;
	protected $property_uids = array();
	protected $current_step = '';
	protected $current_property_uid = '';
	protected $current_property_photos = array();
	protected $logs = array();
	protected $start_time = 0;

	/**
	 * Constructor
	 *
	 * @param string $identifier Unique identifier.
	 */
	public function __construct( $identifier ) {
		$this->identifier = $identifier;

		$this->load();
	}

	/**
	 * Iterate through records & perform task
	 */
	public function process() {
		if ( ! $this->has_started() ) {
			return new WP_Error( 'synchronizer_not_started', __( 'Import has not started' ) );
		} elseif ( $this->has_completed() ) {
			return new WP_Error( 'synchronizer_completed', __( 'Import completed' ) );
		}

		// if ( $this->is_process_running() ) {
		// 	return;
		// }

		// $this->lock_process();

		$this->start_time = time(); // Set start time of current process.

		do {
			$this->sub_process();

		} while ( ! $this->time_exceeded() && ! $this->memory_exceeded() && ! $this->has_completed() );

		// $this->unlock_process();
	}

	protected function sub_process() {
		if ( empty( $this->property_uids ) && empty( $this->current_property_uid ) ) {
			$this->end();
			return;
		}

		if ( empty( $this->current_property_uid ) ) {
			$this->current_property_uid = array_shift( $this->property_uids );
		}

		if ( empty( $this->current_step ) ) {
			$this->current_step = 'property';
		}

		if ( 'property' === $this->current_step ) {
			$this->step_property();

		} elseif ( 'amenities' === $this->current_step ) {
			$this->step_amenities();

		} elseif ( 'reviews' === $this->current_step ) {
			$this->step_reviews();

		} elseif ( 'main_image' === $this->current_step ) {
			$this->step_main_image();

		} elseif ( 'photos' === $this->current_step ) {
			$this->step_photos();
		}

		// No more items.
		if ( empty( $this->property_uids ) && empty( $this->current_property_uid ) ) {
			$this->end();
		} else {
			$this->save_progress();
		}
	}

	protected function step_property() {
		$property = new Property( $this->current_property_uid );

		$this->add_log( '-------------------------------------------' );
		$this->add_log( sprintf( __( 'Current Property # %s' ), $this->current_property_uid ) );
		$this->add_log( '-------------------------------------------', true );

		try {
			if ( $property->get_id() ) {
				$property->update();
				$this->add_log( __( 'Property data Updated.' ), true );

			} else {
				$property->create();
				$this->add_log( __( 'Property Created.' ), true );
			}

		} catch ( Exception $e ) {
			$this->add_log( sprintf( __( 'Property Error: %s' ), $e->getMessage() ), true );
		}

		// Set next step.
		$this->current_step = 'amenities';
	}


	protected function step_amenities() {
		$property = new Property( $this->current_property_uid );

		try {
			$property->update_amenities();
			$this->add_log( __( 'Amenities Updated.' ), true );

		} catch ( Exception $e ) {
			$this->add_log( sprintf( __( 'Amenities Error: %s' ), $e->getMessage() ), true );
		}

		// Set next step.
		$this->current_step = 'reviews';
	}

	protected function step_reviews() {
		$property = new Property( $this->current_property_uid );

		try {
			$property->update_reviews();
			$this->add_log( __( 'Reviews Updated.' ), true );

		} catch ( Exception $e ) {
			$this->add_log( sprintf( __( 'Reviews Error: %s' ), $e->getMessage() ), true );
		}

		// Set next step.
		$this->current_step = 'main_image';
	}

	protected function step_main_image() {
		$property = new Property( $this->current_property_uid );

		try {
			$property->update_main_image();
			$this->add_log( __( 'Main image updated' ), true );

		} catch ( Exception $e ) {
			$this->add_log( sprintf( __( 'Main Image Error: %s' ), $e->getMessage() ), true );
		}

		// Set next step.
		$this->current_step = 'photos';
	}

	protected function step_photos() {
		$property = new Property( $this->current_property_uid );

		if ( empty( $this->current_property_photos ) ) {
			$photos = hfsync_api_get_property_photos( $this->current_property_uid );
			if ( is_wp_error( $photos ) ) {
				$this->add_log( sprintf( __( 'Photos Error: %s' ), $photos->get_error_message() ), true );
				$this->step_completed();
				return;

			} elseif ( empty( $photos ) ) {
				$property->finalize_photos();
				$this->add_log( 'Notice: Empty photos.', true );
				$this->step_completed();
				return;

			} else {
				uasort( $photos, 'hfsync_sort_by_display_order' );

				$max_photos = intval( get_option( 'hfsync_max_photos_import', 1000 ) );
				if ( $max_photos > 0 && count( $photos ) > $max_photos ) {
					$photos = array_slice( $photos, 0, $max_photos );
				}

				$this->current_property_photos = $photos;
			}
		}

		$photo = array_shift( $this->current_property_photos );

		try {
			$property->update_photo( $photo['url'], $photo['description'] );
			$this->add_log( sprintf( __( 'Photo Updated. %s' ), $photo['url'] ), true );

		} catch ( Exception $e ) {
			$this->add_log( sprintf( __( 'Photo Error: %s' ), $e->getMessage() ), true );
		}

		// All done..
		if ( empty( $this->current_property_photos ) ) {
			$property->finalize_photos();
			$this->add_log( __( 'Photo update completed.' ), true );
			$this->step_completed();
		}
	}

	/**
	 * Last step of property import cycle.
	 */
	protected function step_completed() {
		$this->current_property_uid = '';
		$this->current_step = 'property';

		$this->add_log( '-------------------------------------------', true );
	}

	/**
	 * Retrieve progress from options tables using identifier
	 */
	protected function load() {
		$progress = get_option( $this->identifier . '_progress', array() );

		foreach ( $progress as $key => $val ) {
			$this->{$key} = $val;
		}

		$this->logs = get_option( $this->identifier . '_logs', array() );
	}

	/**
	 * Save current progress to database
	 */
	public function save_progress() {
		update_option( 
			$this->identifier . '_progress',
			array( 
				'property_uids'           => $this->property_uids,
				'current_step'            => $this->current_step,
				'current_property_uid'    => $this->current_property_uid,
				'current_property_photos' => $this->current_property_photos,
			 )
		 );
	}

	/**
	 * Schedule a job that will be processed in batch
	 */
	public function schedule( $property_uids = array() ) {
		if ( $this->is_scheduled() ) {
			return new WP_Error( 'scheduled_already', __( 'A job is in progress' ) );
		} elseif ( $this->has_started() ) {
			return new WP_Error( 'started_already', 'A job has started already.' );
		} elseif ( empty( $property_uids ) ) {
			return new WP_Error( 'no_properties', 'No properties exists on hostfully.' );
		}

		$this->property_uids = $property_uids;
		$this->current_step = '';
		$this->current_property_uid = '';
		$this->current_property_photos = array();

		update_option( $this->identifier . '_scheduled', time() );
		delete_option( $this->identifier . '_completed' );

		$this->save_progress();
		$this->clear_logs();
	}

	/**
	 * Delete current scheduled/active job
	 */
	public function unschedule() {
		delete_option( $this->identifier . '_scheduled' );
		delete_option( $this->identifier . '_started' );
		delete_option( $this->identifier . '_completed' );
		delete_option( $this->identifier . '_progress' );
	}

	/**
	 * Start the process
	 */
	public function start() {
		if ( $this->has_started() ) {
			return new WP_Error( 'started_already', 'A job has started already.' );
		} elseif ( ! $this->is_scheduled() ) {
			return new WP_Error( 'not_scheduled', 'Please schedule before starting job.' );
		}

		delete_option( $this->identifier . '_scheduled' );
		update_option( $this->identifier . '_started', time() );

		$this->add_log( 'Sync Started' );
		$this->add_log( '-------------------------------------------', true );
	}

	/**
	 * Finalize a job.
	 */
	public function end() {
		delete_option( $this->identifier . '_scheduled' );
		delete_option( $this->identifier . '_started' );
		delete_option( $this->identifier . '_progress' );
		update_option( $this->identifier . '_completed', time() );

		$this->add_log( 'Sync Ended', true );
	}

	/**
	 * Check if sync is scheduled.
	 */
	public function is_scheduled() {
		return (bool) get_option( $this->identifier . '_scheduled' );
	}

	/**
	 * Check if sync has started.
	 */
	public function has_started() {
		return ( bool ) get_option( $this->identifier . '_started' );
	}

	/**
	 * Check if sync has completed.
	 */
	public function has_completed() {
		return ( bool ) get_option( $this->identifier . '_completed' );
	}

	/**
	 * Get logs
	 */
	public function get_logs() {
		return $this->logs;
	}

	/**
	 * Add log
	 * 
	 * @param string $log Log message.
	 * @param boolean $do_save Save log.
	 */
	public function add_log( $log, $do_save = false ) {
		$this->logs[] = $log;

		// Also save the log in db.
		if ( $do_save ) {
			$this->save_logs();
		}
	}

	/**
	 * Empty logs and update to db.
	 */
	public function clear_logs() {
		$this->logs = array();
		$this->save_logs();
	}

	/**
	 * Update log in db.
	 */
	public function save_logs() {
		update_option( $this->identifier . '_logs', $this->logs );
	}

	/**
	 * Is process running
	 *
	 * Check whether the current process is already running
	 * in a background process.
	 */
	protected function is_process_running() {
		if ( get_site_transient( $this->identifier . '_process_lock' ) ) {
			// Process already running.
			return true;
		}

		return false;
	}

	/**
	 * Lock process
	 *
	 * Lock the process so that multiple instances can't run simultaneously.
	 * Override if applicable, but the duration should be greater than that
	 * defined in the time_exceeded() method.
	 */
	protected function lock_process() {
		$lock_duration = apply_filters( $this->identifier . '_queue_lock_time', 60 );

		set_site_transient( $this->identifier . '_process_lock', microtime(), $lock_duration );
	}

	/**
	 * Unlock process
	 *
	 * Unlock the process so that other instances can spawn.
	 *
	 * @return $this
	 */
	protected function unlock_process() {
		delete_site_transient( $this->identifier . '_process_lock' );

		return $this;
	}

	/**
	 * Time exceeded.
	 *
	 * Ensures the batch never exceeds a sensible time limit.
	 * A timeout limit of 30s is common on shared hosting.
	 *
	 * @return bool
	 */
	protected function time_exceeded() {
		$finish = $this->start_time + apply_filters( $this->identifier . '_default_time_limit', 20 ); // 20 seconds
		$return = false;

		if ( time() >= $finish ) {
			$return = true;
		}

		return apply_filters( $this->identifier . '_time_exceeded', $return );
	}

	/**
	 * Memory exceeded
	 *
	 * Ensures the batch process never exceeds 90%
	 * of the maximum WordPress memory.
	 *
	 * @return bool
	 */
	protected function memory_exceeded() {
		$memory_limit   = $this->get_memory_limit() * 0.9; // 90% of max memory
		$current_memory = memory_get_usage( true );
		$return         = false;

		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}

		return apply_filters( $this->identifier . '_memory_exceeded', $return );
	}

	/**
	 * Get memory limit
	 *
	 * @return int
	 */
	protected function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// Sensible default.
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || -1 === $memory_limit ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32000M';
		}

		return intval( $memory_limit ) * 1024 * 1024;
	}
}
