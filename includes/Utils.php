<?php
/**
 * Utility Class File.
 *
 * @package Hostfully_Sync
 */

namespace Hostfully_Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Utility Class.
 *
 * @class Hostfully_Sync_Utils
 */
class Utils {

	public static function is_cron_disabled() {
		return defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
	}

	/**
	 * Pretty print variable.
	 *
	 * @param  mixed $data Variable.
	 */
	public static function p( $data ) {
		echo '<pre>';
		print_r( $data ); // phpcs:ignore
		echo '</pre>';
	}

	/**
	 * Pretty print & exit execution.
	 *
	 * @param  mixed $data Variable.
	 */
	public static function d( $data ) {
		self::p( $data );
		exit;
	}
}
