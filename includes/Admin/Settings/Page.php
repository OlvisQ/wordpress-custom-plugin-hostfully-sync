<?php
/**
 * Admin Settings Page Class.
 *
 * @package Hostfully_Sync
 */
namespace Hostfully_Sync\Admin\Settings;

use Hostfully_Sync\Utils;
use Hostfully_Sync\Config;
use Hostfully_Sync\Admin\WP_Settings_Api;

/**
 * Page class.
 */
class Page {

	/**
	 * Settings api.
	 */
	private $settings_api;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings_api = new WP_Settings_Api();

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 120 );
	}

	/**
	 * Register settings page menu.
	 */
	public function admin_menu() {
		$admin_page = add_submenu_page(
			'edit.php?post_type=' . Config::PROPERTY_POST_TYPE,
			__( 'Hostfully Sync Settings', 'hfsync' ),
			__( 'Settings', 'hfsync' ),
			'manage_options',
			'hfsync-settings',
			array( $this, 'render_page' )
		);

		add_action( "admin_print_styles-{$admin_page}", array( $this, 'enqueue_scripts' ) );
		add_action( "load-{$admin_page}", array( $this, 'handle_actions' ) );
	}

	public function handle_actions() {
		// Clear cache.
		if ( isset( $_REQUEST['action'] ) && 'clear_cache' === $_REQUEST['action'] ) {
			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'hfsync_clear_cache' ) ) {
				wp_die( __( 'Cheating huh?' ) );
			}

			hfsync_clear_all_cache();

			$message = urlencode( __( 'Cache cleared.', 'hfsync' ) );
			wp_redirect( admin_url( 'edit.php?post_type=' . Config:: PROPERTY_POST_TYPE . '&page=hfsync-settings&message='. $message ) );
			exit;
		}

		// Delete everything.
		if ( isset( $_REQUEST['action'] ) && 'delete_all' === $_REQUEST['action'] ) {
			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'hfsync_delete_all' ) ) {
				wp_die( __( 'Cheating huh?' ) );
			}

			hfsync_delete_all();

			$message = urlencode( __( 'All imported properties & images were deleted', 'hfsync' ) );
			wp_redirect( admin_url( 'edit.php?post_type=' . Config:: PROPERTY_POST_TYPE . '&page=hfsync-settings&message=' . $message ) );
			exit;
		}

		// Delete credentials error on update.
		if ( isset( $_REQUEST['settings-updated'] ) ) {
			delete_option( 'hfsync_api_credentials_error' );
		}
	}

    public function admin_init() {
        $this->settings_api->set_page( 'hfsync-settings' );
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );
        $this->settings_api->admin_init();
    }

    public function get_settings_sections() {
        $sections = array(
            array(
                'id'    => 'general',
                'title' => __( 'General Settings', 'hfsync' )
            ),
            array(
                'id'    => 'api',
                'title' => __( 'API Settings', 'hfsync' )
            ),
            array(
                'id'    => 'import',
                'title' => __( 'Import Settings', 'hfsync' )
            ),
            array(
                'id'    => 'synchronizer',
                'title' => __( 'Sync Setting', 'hfsync' )
            ),
        );

		return $sections;
    }

	public function get_settings_fields() {;
		$filter_fields = array(
			'location' => __( 'Locations Dropdown' ),
			'type'     => __( 'Types Dropdown' ),
		);

        $settings_fields = require __DIR__ . '/views/setting-fields.php';

		if ( Utils::is_cron_disabled() && isset( $settings_fields['synchronizer'][0] ) ) {
			$settings_fields['synchronizer'][0]['desc'] = '<div><strong style="color:red;">' 
				.__( 'WordPress Cronjob is Disabled. Auto sync 
				might not work properly.' )
				. '</strong></div>';

			// Remove manual cron processor field.
			unset( $settings_fields['synchronizer'][2] );
		}

        return $settings_fields;
    }

	protected function run_sync() {
		$synchronizer = new \Hostfully_Sync\Synchronizer( 'hfsync_demo' );

		// $can_schedule = $synchronizer->schedule( hfsync_get_all_property_uids() );
		// if ( is_wp_error( $can_schedule ) ) {
		// 	Utils::p( $can_schedule );
		// }

		if ( $synchronizer->has_completed() ) {
		} elseif ( $synchronizer->has_started() ) {
			$synchronizer->process();

		} elseif ( $synchronizer->is_scheduled() ) {
			$synchronizer->start();
		}

		Utils::p( $synchronizer );
	}

	public function render_page() {
		// $this->run_sync();

		?>
		<div class="wrap hfsync-wrap">
			<h1><?php _e( 'Hostfully Sync Settings', 'hfsync' ) ?></h1>

			<div class="hfsync-settings-wrap">
				<div class="hfsync-primary">
					<div class="hfsync-form">
						<?php $this->settings_api->show_forms(); ?>
					</div>

					<?php
						include __DIR__ . '/views/html-auto-sync-section.php';
						include __DIR__ . '/views/html-sync-now-section.php';
						include __DIR__ . '/views/html-webhooks-section.php';
					?>
				</div>

				<div class="hfsync-sidebar">
					<?php
						include __DIR__ . '/views/html-clear-cache-section.php';
						include __DIR__ . '/views/html-delete-all-section.php';
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue page scripts
	 */
	public function enqueue_scripts() {
		$vars = array( 
			'pageUrl'          => admin_url( 'edit.php?post_type='. Config:: PROPERTY_POST_TYPE . '&page=hfsync-settings' ),
			'scheduleSync'     => admin_url( 'admin-ajax.php?action=hfsync_schedule_sync' ),
			'cancelSync'       => admin_url( 'admin-ajax.php?action=hfsync_cancel_sync' ),
			'syncStatus'       => admin_url( 'admin-ajax.php?action=hfsync_sync_status' ),
			'syncProgress'     => admin_url( 'admin-ajax.php?action=hfsync_sync_progress' ),
			'syncLogs'         => admin_url( 'admin-ajax.php?action=hfsync_sync_logs' ),
			'registerWebhooks' => admin_url( 'admin-ajax.php?action=hfsync_register_webhooks' ),
			'deleteWebhooks'   => admin_url( 'admin-ajax.php?action=hfsync_delete_webhooks' ),
			'checkWebhooks'    => admin_url( 'admin-ajax.php?action=hfsync_check_webhooks' ),
			'useAjaxProcessor' => Utils::is_cron_disabled() || 'ajax' === get_option( 'hfsync_manual_sync_processor' ),
			'ajaxNoResponse'   => __( 'No Response From Ajax Handler', 'hfsync' ),
			'serverSideError'  => __( 'Server side error occured, please check your server error log.', 'hfsync' )
		);
		wp_localize_script( 'hfsync-admin-settings', 'hfsyncVar', $vars );

		wp_enqueue_style( 'hfsync-admin-settings' );
		wp_enqueue_script( 'hfsync-admin-settings' );
	}
}
