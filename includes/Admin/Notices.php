<?php
/**
 * Admin main class.
 *
 * @package Hostfully_Sync
 */
namespace Hostfully_Sync\Admin;

use Hostfully_Sync\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Notices Class.
 *
 * @class Hostfully_Sync_Admin_Main
 */
class Notices {

	public static $errors   = array();
	public static $messages = array();

	/**
	 * Constructor
	 */
	public static function init() {
		add_action( 'current_screen', array( __CLASS__, 'api_notices' ) );
		add_action( 'current_screen', array( __CLASS__, 'page_notices' ) );
		add_action( 'admin_notices', array( __CLASS__, 'display_notices' ) );
	}

	public static function add_error( $error, $key = null ) {
		if ( $key ) {
			self::$errors[ $key ] = $error;
		} else {
			self::$errors[] = $error;
		}
	}

	public static function add_message( $message, $key = null ) {
		if ( $key ) {
			self::$messages[ $key ] = $message;
		} else {
			self::$messages[] = $message;
		}
	}

	public static function api_notices() {
		if ( hfsync_api_config_missing() ) {
			self::add_error( sprintf( 
				__( 'Hostfully Sync Error: Missing api credentials. Please update your Hostfully <a href="%s">API Credentials</a>', 'hfsync' ),
				admin_url( 'edit.php?post_type='. Config:: PROPERTY_POST_TYPE . '&page=hfsync-settings' )
			) );
		} elseif ( get_option( 'hfsync_api_credentials_error' ) ) {
			self::add_error( sprintf( 
				__( 'Hostfully Sync Error: %1$s Please check & update your Hostfully <a href="%2$s">API Credentials</a>', 'hfsync' ), 
				get_option( 'hfsync_api_credentials_error' ),
				admin_url( 'edit.php?post_type='. Config:: PROPERTY_POST_TYPE . '&page=hfsync-settings' )
			) );
		}

		$screen = get_current_screen();

		if ( isset( $screen->id ) && Config:: PROPERTY_POST_TYPE . '_page_hfsync-settings' === $screen->id ) {
			if ( isset( $_REQUEST['message'] ) ) {
				self::add_message( urldecode( $_REQUEST['message'] ) );
			} elseif ( isset( $_REQUEST['error'] ) ) {
				self::add_error( urldecode( $_REQUEST['error'] ) );
			}

			$reached      = get_transient( 'hfsync_api_request_reached', false );
			$remaining    = get_transient( 'hfsync_api_request_remaining', 1000 );
			$expires_time = get_transient( 'hfsync_api_request_expires' );

			if ( $reached ) {
				self::add_error( sprintf( 
					__( 'Hostfully Api request limit reached. Resets in %s' ),
					human_time_diff( $expires_time )
				) );
			} else {
				self::add_message( sprintf( 
					__( 'Hostfully Api requests remaining - %d. Resets in %s' ),
					$remaining, 
					human_time_diff( $expires_time )
				) );
			}
		}
	}

	public static function page_notices() {
		$screen = get_current_screen();
		if ( isset( $screen->id ) && 'edit-' . Config:: PROPERTY_POST_TYPE === $screen->id ) {
			if ( isset( $_REQUEST['settings-updated'] ) && 'property-synchronized' === $_REQUEST['settings-updated'] ) {
				self::$messages[] = __( 'Property synchronized.', 'hfsync' );
			}
		}
	}

	public static function display_notices() {
		foreach ( self::$errors as $error ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo $error; ?></p>
			</div>
			<?php
		}

		foreach ( self::$messages as $message ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo $message; ?></p>
			</div>
			<?php
		}
	}
}
