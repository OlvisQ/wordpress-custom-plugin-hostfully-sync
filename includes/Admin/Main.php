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
 * Admin Main Class.
 *
 * @class Hostfully_Sync_Admin_Main
 */
class Main {

	/**
	 * Constructor
	 */
	public function __construct() {
		Notices::init();

		new Ajax_Handlers();
		new Properties();
		new Settings\Page();

		add_action( 'plugin_action_links_' . HFSYNC_BASENAME, array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Adds plugin action links.
	 */
	public function plugin_action_links( $links ) {
		$new_links = array(
			'settings' => sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'edit.php?post_type='. Config:: PROPERTY_POST_TYPE . '&page=hfsync-settings' ),
				__( 'Settings', 'hfsync' )
			),
		);

		return array_merge( $new_links, $links );
	}
}
