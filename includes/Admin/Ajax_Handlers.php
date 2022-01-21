<?php
/**
 * Handle ajax requests.
 */

namespace Hostfully_Sync\Admin;

use Hostfully_Sync\Utils;
use Hostfully_Sync\Synchronizer;
use Hostfully_Sync\Webhook;

/**
 * @class Ajax_handlers
 */
class Ajax_Handlers {

	/**
	 * Constructor
	 */
	function __construct() {
		add_action( 'wp_ajax_hfsync_register_webhooks', array( $this, 'register_webhooks_ajax' ) );
		add_action( 'wp_ajax_hfsync_delete_webhooks', array( $this, 'delete_webhooks_ajax' ) );
		add_action( 'wp_ajax_hfsync_check_webhooks', array( $this, 'check_webhooks_ajax' ) );
		add_action( 'wp_ajax_hfsync_schedule_sync', array( $this, 'schedule_sync_ajax' ) );
		add_action( 'wp_ajax_hfsync_cancel_sync', array($this, 'cancel_sync_ajax' ));
		add_action( 'wp_ajax_hfsync_sync_progress', array( $this, 'sync_progress_ajax' ) );
		add_action( 'wp_ajax_hfsync_sync_status', array($this, 'sync_status_ajax' ));
		add_action( 'wp_ajax_hfsync_sync_logs', array( $this, 'sync_logs_ajax' ) );
	}

	/**
	 * Handle synchronization schedule request.
	 */
	public function register_webhooks_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json(
				array(
					'success'  => false,
					'code'     => 'unauthorized',
					'message'  => sprintf(
						__( 'Sorry, you need %s capability to perform this request.', 'hfsync' ),
						'manage_options'
					)
				)
			);
		}

		$callback_url = isset( $_REQUEST['callback_url'] ) ? wp_unslash( $_REQUEST['callback_url'] ) : '';
		$register = Webhook::register_webhooks( $callback_url );

		if ( is_wp_error( $register ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => $register->get_error_message()
				)
			);
		}

		wp_send_json(
			array(
				'success' => true,
				'message' => sprintf( __( '%d Webhook Registered', 'hfsync' ), $register )
			)
		);
	}

	/**
	 * Handle synchronization schedule request.
	 */
	public function delete_webhooks_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json(
				array(
					'success'  => false,
					'code'     => 'unauthorized',
					'message'  => sprintf(
						__( 'Sorry, you need %s capability to perform this request.', 'hfsync' ),
						'manage_options'
					)
				)
			);
		}

		$callback_url = isset( $_REQUEST['callback_url'] ) ? wp_unslash( $_REQUEST['callback_url'] ) : '';
		$delete = Webhook::delete_webhooks( $callback_url );

		if ( is_wp_error( $delete ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => $delete->get_error_message()
				)
			);
		}

		wp_send_json(
			array(
				'success' => true,
				'message' => sprintf( __( '%d Webhook Deleted', 'hfsync' ), $delete )
			)
		);
	}

	/**
	 * Handle synchronization schedule request.
	 */
	public function check_webhooks_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json(
				array(
					'success'  => false,
					'code'     => 'unauthorized',
					'message'  => sprintf(
						__( 'Sorry, you need %s capability to perform this request.', 'hfsync' ),
						'manage_options'
					)
				)
			);
		}

		$callback_url = isset( $_REQUEST['callback_url'] ) ? wp_unslash( $_REQUEST['callback_url'] ) : '';
		$missing = Webhook::missing_webhooks( $callback_url );

		if ( is_wp_error( $missing ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => $missing->get_error_message()
				)
			);
		}

		if ( count( $missing ) > 0 ) {
			wp_send_json(
				array(
					'success' => true,
					'message' => sprintf( __( 'Missing Webhooks - %s', 'hfsync' ), implode( ', ', $missing ) )
				)
			);
		}

		wp_send_json(
			array(
				'success' => true,
				'message' => __( 'All Webhooks are present.' )
			)
		);
	}

	/**
	 * Handle synchronization schedule request.
	 */
	public function schedule_sync_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json(
				array(
					'success'  => false,
					'code'     => 'unauthorized',
					'message'  => sprintf(
						__( 'Sorry, you need %s capability to perform this request.', 'hfsync' ),
						'manage_options'
					)
				)
			);
		}

		$synchronizer = new Synchronizer( 'hfsync_manual_sync' );
		$synchronizer->end();
		$synchronizer->clear_logs();

		$property_uids = hfsync_get_all_property_uids();
		if ( is_wp_error( $property_uids ) ) {
			wp_send_json(
				array(
					'success' => false,
					'code'    => $property_uids->get_error_code(),
					'message' => sprintf( 
						__( 'Unable to schedule sync. Hostfully Api Error: %s' ), 
						$property_uids->get_error_message()
					)
				)
			);
		}

		$can_schedule = $synchronizer->schedule( $property_uids );

		if ( is_wp_error( $can_schedule ) ) {
			wp_send_json(
				array(
					'success' => false,
					'code'    => $can_schedule->get_error_code(),
					'message' => $can_schedule->get_error_message()
				)
			);
		}

		if ( ! Utils::is_cron_disabled() && 'cron' === get_option( 'hfsync_manual_sync_processor' ) ) {
			do_action( 'hfsync_schedule_sync_process_event', 'hfsync_manual_sync' );
		}

		wp_send_json(
			array(
				'success' => true,
				'code'    => 'scheduled',
				'message' => __( 'Sync Scheduled', 'hfsync' )
			)
		);
	}

	/**
	 * Handle synchronization schedule request.
	 */
	public function cancel_sync_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json(array(
				'success'  => false,
				'code'     => 'unauthorized',
				'message'  => sprintf(
					__( 'Sorry, you need %s capability to perform this request.', 'hfsync' ),
					'manage_options'
				)
			));
		}

		$synchronizer = new Synchronizer( 'hfsync_manual_sync' );
		$synchronizer->end();

		wp_send_json(
			array(
				'success' => true,
				'message' => __( 'Synchronization Cancelled', 'hfsync' )
			)
		);
	}

	/**
	 * Handle synchronization schedule request.
	 */
	public function sync_status_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json(array(
				'success'  => false,
				'code'     => 'unauthorized',
				'message'  => sprintf(
					__( 'Sorry, you need %s capability to perform this request.', 'hfsync' ),
					'manage_options'
				)
			));
		}

		$synchronizer = new Synchronizer( 'hfsync_manual_sync' );
		if ( $synchronizer->is_scheduled() || $synchronizer->has_started() ) {

			// Trigger a event schedule incase original event stopped.
			if ( ! Utils::is_cron_disabled() && 'cron' === get_option( 'hfsync_manual_sync_processor' ) ) {
				do_action( 'hfsync_schedule_sync_process_event', 'hfsync_manual_sync' );
			}

			wp_send_json(
				array(
					'success' => true,
					'running' => true
				)
			);
		}

		wp_send_json(
			array(
				'success' => true,
				'running' => false
			)
		);
	}

	/**
	 * Get manual sync progress.
	 */
	public function sync_progress_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json(
				array(
					'success'  => false,
					'code'     => 'unauthorized',
					'message'  => sprintf(
						__( 'Sorry, you need %s capability to perform this request.', 'hfsync' ),
						'manage_options'
					)
				)
			);
		}

		$synchronizer = new Synchronizer( 'hfsync_manual_sync' );
		if ( $synchronizer->has_completed() ) {
			wp_send_json(
				array(
					'success'   => true,
					'completed' => true,
					'message'   => __( 'Sync Completed.' )
				)
			);

		} elseif ( $synchronizer->has_started() ) {
			session_write_close();
			$synchronizer->process();
			wp_send_json(
				array(
					'success'  => true,
					'continue' => true,
					'message'  => __( 'Sync running...' )
				)
			);

		} elseif ( $synchronizer->is_scheduled() ) {
			$synchronizer->start();

			wp_send_json(
					array(
					'success'  => true,
					'continue' => true,
					'message'  => __( 'Sync Started', 'hfsync' ),
				)
			);
		} else {
			wp_send_json(
				array(
					'success' => true,
					'message' => __( 'No Sync Scheduled', 'hfsync' ),
				)
			);
		}
	}

	/**
	 * Get current synchronization logs
	 */
	public function sync_logs_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json(
				array(
					'success'  => false,
					'code'     => 'unauthorized',
					'message'  => sprintf(
						__( 'Sorry, you need %s capability to perform this request.', 'hfsync' ),
						'manage_options'
					)
				)
			);
		}

		$synchronizer = new Synchronizer( 'hfsync_manual_sync' );
		if ( $synchronizer->has_completed() ) {
			wp_send_json(
				array(
					'success'   => true,
					'continue'  => false,
					'message'   => __( 'Sync Completed.' ),
					'logs'      => $synchronizer->get_logs(),
				)
			);

		} elseif ( $synchronizer->has_started() || $synchronizer->is_scheduled( ) ) {
			wp_send_json(
				array(
					'success'  => true,
					'continue' => true,
					'message'  => __( 'Sync running...' ),
					'logs'     => $synchronizer->get_logs(),
				)
			);
		} else {
			wp_send_json(
				array(
					'success' => true,
					'continue'  => false,
					'message' => __( 'No Sync Scheduled', 'hfsync' ),
					'logs'    => array(__( 'No Sync Scheduled', 'hfsync' ) ),
				)
			);
		}
	}
}