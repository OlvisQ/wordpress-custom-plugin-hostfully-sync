<?php
/**
 * Register scripts.
 *
 * @package Hostfully_Sync
 */
namespace Hostfully_Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scripts class
 */
class Scripts {

	/**
	 * Construct.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_admin_scripts' ), 5 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_public_scripts' ), 5 );
	}

	public static function register_admin_scripts() {
		wp_register_style( 
			'hfsync-admin-settings', 
			HFSYNC_URL . 'assets/css/admin-settings.css', 
			array(),
			HFSYNC_BASENAME
		);
		
		wp_register_script(
			'hfsync-admin-settings',
			HFSYNC_URL . 'assets/js/admin-settings.js',
			array( 'jquery' ),
			HFSYNC_BASENAME,
			true
		);
	}

	public static function register_public_scripts() {
		// wp_register_script(
		// 	'uikit2',
		// 	HFSYNC_URL . 'assets/uikit/uikit.min.js',
		// 	array( 'jquery' ),
		// 	HFSYNC_VERSION,
		// 	true
		// );
		// wp_register_script(
		// 	'uikit2-datepicker',
		// 	HFSYNC_URL . 'assets/uikit/datepicker.min.js',
		// 	array( 'uikit2' ),
		// 	HFSYNC_VERSION,
		// 	true
		// );
		wp_register_script(
			'hfsync-frontend-properties',
			HFSYNC_URL . 'assets/js/frontend-properties.js',
			array( 'jquery-ui-datepicker' ),
			HFSYNC_VERSION,
			true
		);

		// wp_register_style(
		// 	'uikit2-datepicker',
		// 	HFSYNC_URL . 'assets/uikit/datepicker.css',
		// 	array(),
		// 	HFSYNC_VERSION,
		// 	'all'
		// );
		wp_register_style(
			'hfsync-frontend-properties',
			HFSYNC_URL . 'assets/css/frontend-properties.css',
			array(),
			HFSYNC_VERSION,
			'all'
		);
	}
}