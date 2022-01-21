<?php
/**
 * Debugger
 *
 * @package Hostfully_Sync
 */

namespace Hostfully_Sync;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Debug {

	public function __construct() {
		add_action( 'template_redirect', array( __CLASS__, 'test_import' ) );
		// add_action( 'template_redirect', array( __CLASS__, 'test_webhooks' ) );
	}

	public static function test_webhooks() {
		if ( ! isset( $_REQUEST['debug'] ) ) {
			return;
		}

		// Utils::p( Webhook_Hanlder::register_webhooks() );
		Utils::p( Webhook_Hanlder::delete_webhooks() );

		exit;
	}

	public static function test_download() {
		if ( ! isset( $_REQUEST['debug'] ) ) {
			return;
		}

		$property_uid = '0655caca-c1f6-4e33-963a-b51427c0e6ca';
		$property = new Property( $property_uid );
		Utils::p( $property->download_image( 'https://orbirental-images.s3.amazonaws.com/0aaed275-81bf-4c46-92cd-b244dc3a4e25' ) );
		exit;
	}

	public static function test_resize() {
		if ( ! isset( $_REQUEST['debug'] ) ) {
			return;
		}

		$attachment_id = 1828;
		$file = get_attached_file( $attachment_id );
		$file = str_replace( '-moh.', '.', $file );
		$threshold = 1000;

		// require_once ABSPATH . 'wp-admin/includes/image.php';
		// $exif_meta = wp_get_attachment_metadata( $file );
		// $file = ABSPATH . 'wp-content/uploads/test.png';
		// Utils::p( $file );

		$resize_image = self::downscale_image( $file, $threshold );
		Utils::p( $resize_image );

		exit;
	}

	public static function downscale_image( $file, $threshold ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$imagesize = wp_getimagesize( $file );
 
		if ( empty( $imagesize ) ) {
			// File is not an image.
			return array();
		}
	
		// Default image meta.
		$image_meta = array(
			'width'  => $imagesize[0],
			'height' => $imagesize[1],
			'file'   => _wp_relative_upload_path( $file ),
			'sizes'  => array(),
		);
	
		$exif_meta = wp_read_image_metadata( $file );
	
		if ( $exif_meta ) {
			$image_meta['image_meta'] = $exif_meta;
		}
	
		// if ( 'image/png' !== $imagesize['mime'] ) {
			if ( $threshold && ( $image_meta['width'] > $threshold || $image_meta['height'] > $threshold ) ) {
				$editor = wp_get_image_editor( $file );
				if ( ! is_wp_error( $editor ) ) {
					$resized = $editor->resize( $threshold, $threshold );
					if ( ! is_wp_error( $resized ) ) {
						$saved = $editor->save( $file );
					}
				}
			}
		// }
	}

	public static function test_import() {
		if ( ! isset( $_REQUEST['debug'] ) ) {
			return;
		}

		$property_uid = '26c8a376-43d5-45e1-9281-f1eb223e6b8d';
		$property = new Property( $property_uid );

		try {
			// $property->load_api_data();
			$property->update_amenities();
			// Utils::p( $property );
			// $property->update_taxonomy_terms();
			// $property->save();

			// $property->update_main_image();
			// $photos = hfsync_api_get_property_photos( $property_uid );
			// $property->update_calendar_pricing();
	
		} catch ( Exception $e ) {
			Utils::p( $e->getMessage() );
		}

		exit;
	}
}