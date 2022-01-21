<?php
/**
 * Rest api for hostfully webhooks.
 *
 * @package Hostfully_Sync
 */

namespace Hostfully_Sync\Rest_Controller;

use WP_REST_Server;
use WP_Error;
use Exception;
use Hostfully_Sync\Webhook;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Webhook class
 */
class Hostfully_Events {

	protected $namespace = 'hfsync/v1';
	protected $rest_base = 'hostfully-event';

	/**
	 * Register routes.
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'handle_webhook_event' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * handle webhook event
	 */
	public function handle_webhook_event( $request ) {

		hfsync_log( 
			sprintf( 'Webhook Reveived - %s', $request->get_param( 'event_type' ) ),
			$request->get_params()
		);

		$response = Webhook::handle_event( $request->get_params() );

		return $response;
	}

	/**
	 * Check for required parameters
	 */
	public function permissions_check( $request ) {

		if ( ! $request->get_param( 'agency_uid' ) ) {
			return new WP_Error( 'error', __( 'Empty agency_uid', 'hfsync' ), array( 'status' => 401 ) );

		} elseif ( get_option( 'hfsync_agency_uid' ) !== $request->get_param( 'agency_uid' ) ) {
			return new WP_Error( 'error', __( 'Invalid agency uid', 'hfsync' ), array( 'status' => 401 ) );

		} elseif ( ! $request->get_param( 'event_type' ) ) {
			return new WP_Error( 'error', __( 'Empty event_type', 'hfsync' ), array( 'status' => 401 ) );

		} elseif ( ! $request->get_param( 'property_uid' ) ) {
			return new WP_Error( 'error', __( 'Empty property_uid', 'hfsync' ), array( 'status' => 401 ) );
		}

		return true;
	}
}