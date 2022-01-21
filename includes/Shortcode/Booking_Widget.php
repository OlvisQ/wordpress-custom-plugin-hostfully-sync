<?php
/**
 * Booking Widget Shortcode.
 *
 * @package Hostfully_Sync
 */
namespace Hostfully_Sync\Shortcode;

use WP_Error;
use WP_Query;
use Hostfully_Sync\Property;
use Hostfully_Sync\Utils;
use Hostfully_Sync\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Properties class
 */
class Booking_Widget {

	const SHORTCODE_TAG = 'hfsync-booking-widget';

	/**
	 * Construct.
	 */
	public static function init() {
		add_shortcode( self::SHORTCODE_TAG, array( __CLASS__, 'render' ) );
	}


	/**
	 * Render output
	 */
	public static function render( $attrs = array() ) {
		$attrs = wp_parse_args( $attrs );

		if ( empty( $attrs['uid'] ) && Config::PROPERTY_POST_TYPE === get_post_type() ) {
			$attrs['uid'] = hfsync_property_uid( get_the_ID() );
		}

		// Property Uid not available.
		if ( empty( $attrs['uid'] ) ) {
			return '';
		}

		foreach ( array( 'checkin', 'checkout', 'guests' ) as $param ) {
			if ( ! empty( $_REQUEST[ $param ] ) ) {
				$attrs[ $param ] = stripslashes( $_REQUEST[ $param ] );
			}
		}

		if ( empty( $attrs[ 'checkin' ] ) ) {
			$attrs[ 'checkin' ] = date( 'Y-m-d' );
		}
		if ( empty( $attrs[ 'checkout' ] ) ) {
			$attrs[ 'checkout' ] = date( 'Y-m-d', strtotime( $attrs[ 'checkin' ] ) + ( intval( get_option( 'hfsync_min_stay', 1 ) ) * DAY_IN_SECONDS ) );
		}
		if ( empty( $attrs[ 'guests' ] ) ) {
			$attrs[ 'guests' ] = 1;
		}

		if ( 'production' === get_option( 'hfsync_api_platform' ) ) {
			$host_url = 'https://platform.hostfully.com';
		} else {
			$host_url = 'https://sandbox.hostfully.com';
		}

		ob_start();
		// Utils::p( $attrs );
		?>
		<div id="leadWidget"></div>
		<script type="text/javascript" src="<?php echo $host_url; ?>/assets/js/pikaday.js"></script>
		<script type="text/javascript" src="<?php echo $host_url; ?>/assets/js/leadCaptureWidget_2.0.js"></script>
		<script>
			new Widget( 
				'leadWidget', 
				'<?php echo $attrs['uid']; ?>', 
				{ 
					"maximun_availability": "2024-11-18T01:35:25.152Z", 
					"type": "agency", 
					"fields": [], 
					"showAvailability": true, 
					"lang": "US", 
					"minStay": true, 
					"price": true, 
					"cc": false, 
					"emailClient": true, 
					"saveCookie": true, 
					"showDynamicMinStay": true, 
					"backgroundColor": "#FFFFFF", 
					"buttonSubmit": { "backgroundColor": "#F8981B" }, 
					"showPriceDetailsLink": false, 
					"showGetQuoteLink": false, 
					"labelColor": "#F8981B", 
					"showTotalWithoutSD": true, 
					"showDiscount": true, 
					"includeReferrerToRequest": true, 
					"customDomainName": null, 
					"source": null, 
					"aid": "ORB-49587220416635719", 
					"clickID": null, 
					"valuesByDefaults": { 
						"checkIn": { "value": "<?php echo $attrs['checkin']; ?>" }, 
						"checkOut": { "value": "<?php echo $attrs['checkout']; ?>" }, 
						"guests": { "value": "<?php echo $attrs['guests']; ?>" }, 
						"discountCode": { "value": "" } 
					}, 
					"pathRoot": "<?php echo $host_url; ?>/" 
				}
			);
		</script>
		<?php

		return ob_get_clean();
	}
}