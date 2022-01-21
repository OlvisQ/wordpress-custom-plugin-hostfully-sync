<?php
/**
 * Main plugin file.
 *
 * @package Hostfully_Sync
 */

namespace Hostfully_Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin class.
 */
final class Plugin {

	/**
	 * Singleton The reference the *Singleton* instance of this class.
	 *
	 * @var Plugin
	 */
	protected static $instance = null;

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	private function __construct() {}

	/**
	 * Define constants
	 */
	private function define_constants() {
		define( 'HFSYNC_DIR', plugin_dir_path( HFSYNC_PLUGIN_FILE ) );
		define( 'HFSYNC_URL', plugin_dir_url( HFSYNC_PLUGIN_FILE ) );
		define( 'HFSYNC_BASENAME', plugin_basename( HFSYNC_PLUGIN_FILE ) );
	}

	/**
	 * Initialize the plugin
	 */
	private function initialize() {
		Post_Type_Taxonomies::init();
		new Debug();
		new Event_Hanlder();

		Scripts::init();
		Shortcode\Properties::init();
		Shortcode\Booking_Widget::init();

		if ( is_admin() ) {
			new Admin\Main();
		}
	}

	/**
	 * Load plugin translation file
	 */
	public function load_plugin_translations() {
		load_plugin_textdomain(
			'hfsync',
			false,
			basename( dirname( HFSYNC_PLUGIN_FILE ) ) . '/languages'
		);
	}

	public function register_image_size() {
		add_image_size( 'hfsync-thumbnail', 400, 270, true );
	}

	public function register_rest_apis() {
		$controller = new Rest_Controller\Hostfully_Events();
		$controller->register_routes();
	}

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Singleton The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->define_constants();
			self::$instance->initialize();
	
			add_action( 'init', array( self::$instance, 'load_plugin_translations' ) );
			add_action( 'rest_api_init', array( self::$instance, 'register_rest_apis' ) );
			add_action( 'after_setup_theme', array( self::$instance, 'register_image_size' ) );
		}

		return self::$instance;
	}
}