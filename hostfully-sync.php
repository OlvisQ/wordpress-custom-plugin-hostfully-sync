<?php
/**
 * Plugin Name: Hostfully Sync
 * Plugin URI: https://github.com/shazzad/hostfully-sync
 * Description: Import hostfully properties into WordPress.
 * Version: 0.6.1
 * Author: Shazzad Hossain Khan
 * Author URI: https://shazzad.me
 * Requires at least: 5.8.2
 * Text Domain: hfsync
 * Domain Path: /languages
 *
 * @package Hostfully_Sync
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define base file.
if ( ! defined( 'HFSYNC_PLUGIN_FILE' ) ) {
	define( 'HFSYNC_PLUGIN_FILE', __FILE__ );
}

// Define plugin version.
if ( ! defined( 'HFSYNC_VERSION' ) ) {
	define( 'HFSYNC_VERSION', '0.6.1' );
}

// Load dependencies.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Intialize on plugins_loaded action.
 *
 * @return void
 */
function hfsync_init() {
	hfsync();
}
add_action( 'plugins_loaded', 'hfsync_init' );

/**
 * Get an instance of plugin main class.
 *
 * @return Hostfully_Sync Instance of main class.
 */
function hfsync() {
	return \Hostfully_Sync\Plugin::get_instance();
}

/**
 * Clear cronjobs schedules upon plugin deactivation.
 */
function hfsync_deactivate() {
	$hooks = array(
		'hfsync_auto_sync_cron',
		'hfsync_sync_process_cron',
		'hfsync_sync_process_alt_cron',
	);

	foreach ( $hooks as $hook ) {

		$timestamp = wp_next_scheduled( $hook );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $hook );
		}

		wp_clear_scheduled_hook( $hook );
	}
}
register_deactivation_hook( __FILE__, 'hfsync_deactivate' );
