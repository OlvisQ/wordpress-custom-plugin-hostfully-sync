<?php
/**
 * Properties Shortcode.
 *
 * @package Hostfully_Sync
 */
namespace Hostfully_Sync\Shortcode;

use DateTime;
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
class Properties {

	const SHORTCODE_TAG = 'hfsync-properties';
	const HOOK_PREFIX   = 'hfsync_properties_shortcode_';

	/**
	 * Construct.
	 */
	public static function init() {
		add_shortcode( self::SHORTCODE_TAG, array( __CLASS__, 'render' ) );

		add_action( 'wp_ajax_hfsync_get_properties_html', array( __CLASS__, 'properties_html_ajax' ) );
		add_action( 'wp_ajax_nopriv_hfsync_get_properties_html', array( __CLASS__, 'properties_html_ajax' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( self::HOOK_PREFIX . 'before_loop', array( __CLASS__, 'before_loop' ), 10, 2 );
		add_action( self::HOOK_PREFIX . 'after_loop', array( __CLASS__, 'after_loop' ), 10, 2 );
	}

	public static function properties_html_ajax() {
		if ( empty( $_REQUEST['attrs'] ) ) {
			wp_send_json_error( __( 'Invalid attrs' ) );
		}

		$attrs = unserialize( base64_decode( $_REQUEST['attrs'] ) );
		$filter_params = ! empty( $_REQUEST['filter'] ) ? unserialize( base64_decode( $_REQUEST['filter'] ) ) : array();

		$attrs['filter'] = false;
		if ( ! empty( $_REQUEST['page'] ) ) {
			$attrs['page'] = intval( $_REQUEST['page'] );
		} else {
			$attrs['page'] = 1;
		}

		remove_action( self::HOOK_PREFIX . 'before_loop', array( __CLASS__, 'before_loop' ), 10, 2 );
		remove_action( self::HOOK_PREFIX . 'after_loop', array( __CLASS__, 'after_loop' ), 10, 2 );

		$properties = self::get_properties_html( $attrs, $filter_params );

		add_action( self::HOOK_PREFIX . 'before_loop', array( __CLASS__, 'before_loop' ), 10, 2 );
		add_action( self::HOOK_PREFIX . 'after_loop', array( __CLASS__, 'after_loop' ), 10, 2 );

		wp_send_json_success(
			array(
				'properties' => $properties,
				'page'       => $attrs['page']
			)
		);
	}

	public static function enqueue_scripts() {
		global $post;

		if ( is_singular() && has_shortcode( $post->post_content, self::SHORTCODE_TAG ) ) {
			wp_localize_script( 
				'hfsync-frontend-properties', 
				'hfsyncPropertiesVars', 
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'minStay' => intval( get_option( 'hfsync_min_stay', 1 ) ),
					'maxCalendarDays' => intval( get_option( 'hfsync_max_calendar_days', 30 ) ),
				)
			);

			wp_enqueue_script( array( 'hfsync-frontend-properties' ) );
			wp_enqueue_style( array( 'hfsync-frontend-properties' ) );
		}
	}

	/**
	 * Render output
	 */
	public static function render( $attrs = array() ) {

		$attrs = wp_parse_args( $attrs );

		if ( ! empty( $attrs['filter'] ) ) {
			$attrs['filter'] = true;
		} else {
			$attrs['filter'] = false;
		}

		$template = apply_filters( self::HOOK_PREFIX . 'loop_template', HFSYNC_DIR . '/templates/property-loop.php' );
		if ( ! file_exists( $template ) ) {
			return __( 'Missing property template' );
		}

		if ( $attrs['filter'] ) {
			$filter_template = apply_filters( self::HOOK_PREFIX . 'filter_template', HFSYNC_DIR . '/templates/properties-filter.php' );
			if ( ! file_exists( $filter_template ) ) {
				return __( 'Missing property filter template' );
			}
		}

		$filter_params = stripslashes_deep( $_REQUEST );
		$filter_params = wp_parse_args( $filter_params, array(
			'type'     => '',
			'location' => '',
			'checkin'  => '',
			'checkout' => '',
			'guests'   => '',
		) );

		// Utils::d( $query->request );

		return self::get_properties_html( $attrs, $filter_params );
	}

	public static function get_property_loop_template() {
		return apply_filters( self::HOOK_PREFIX . 'loop_template', HFSYNC_DIR . '/templates/property-loop.php' );
	}

	public static function get_filter_template() {
		return apply_filters( self::HOOK_PREFIX . 'filter_template', HFSYNC_DIR . '/templates/properties-filter.php' );
	}

	public static function get_filter_fields() {
		$filter_fields = array(
			'checkin',
			'checkout',
			'guests'
		);

		$additional_filter_fields = get_option( 'hfsync_filter_fields' );
		if ( ! empty( $additional_filter_fields ) ) {
			$filter_fields = array_merge( $additional_filter_fields, $filter_fields );
		}

		return apply_filters( 'hfsync_filter_fields', $filter_fields );
	}

	private static function get_properties_html( $attrs, $filter_params ) {
		$filter_fields   = self::get_filter_fields();
		$loop_template   = self::get_property_loop_template();
		$filter_template = self::get_filter_template();
		$query_args      = self::prepare_query_args( $attrs, $filter_params );
		$query_args      = apply_filters( self::HOOK_PREFIX . 'query_args', $query_args );

		$query = new WP_Query( $query_args );

		ob_start();

		do_action( self::HOOK_PREFIX . 'wrapper_open', $attrs, $query );

		$is_filtered = ! empty( $filter_params['type'] ) 
					&& ! empty( $filter_params['location'] ) 
					&& ! empty( $filter_params['guests'] );

		$is_date_filtered = ! empty( $filter_params['checkin'] ) && ! empty( $filter_params['checkout'] );

		$show_base_price = true;
		if ( 'yes' === get_option( 'hfsync_hide_base_price' ) && ! $is_date_filtered ) {
			$show_base_price = false;
		}

		if ( $attrs['filter'] ) {
			require $filter_template;
		}

		if ( $query->have_posts() ) {

			do_action( self::HOOK_PREFIX . 'before_loop', $attrs, $query );

			while ( $query->have_posts() ) {
				$query->the_post();
				require $loop_template;
			}

			wp_reset_postdata();

			do_action( self::HOOK_PREFIX . 'after_loop', $attrs, $query );
		}

		do_action( self::HOOK_PREFIX . 'wrapper_close', $attrs, $query );

		return ob_get_clean();
	}

	private static function prepare_query_args( $attrs, $filter_params ) {
		$query_args = array(
			'post_type'      => Config::PROPERTY_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'paged'			 => 1,
			'meta_query'     => array(
				'relation' => 'AND'
			),
			'tax_query'      => array(
				'relation' => 'AND'
			)
		);

		$numeric_args = array( 
			'per_page' => 'posts_per_page',
			'page'     => 'paged',
			'sf_id'    => 'search_filter_id'
		);

		foreach ( $numeric_args as $attr => $arg ) {
			if ( array_key_exists( $attr, $attrs ) ) {
				$query_args[ $arg ] = intval( $attrs[ $attr ] );
			}
		}

		if ( get_query_var( 'paged' ) ) {
			$query_args[ 'paged' ] = get_query_var( 'paged' );
		}

		$taxonomy_args = array( 
			'location' => Config::PROPERTY_CATEGORY,
			'type'     => Config::PROPERTY_TYPE,
		);

		foreach ( $taxonomy_args as $param => $taxonomy ) {
			if ( ! empty( $filter_params[ $param ] ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => $taxonomy,
					'terms'    => $filter_params[ $param ],
					'field'    => 'slug'
				);
			}
		}

		if ( ! empty( $filter_params['checkin'] ) || ! empty( $filter_params['checkout'] ) ) {
			$properties_params = array();
			if ( ! empty( $filter_params['checkin'] ) ) {
				$properties_params['checkInDate'] = $filter_params['checkin'];
			}
			if ( ! empty( $filter_params['checkout'] ) ) {
				$properties_params['checkOutDate'] = $filter_params['checkout'];
			}
			if ( ! empty( $filter_params['guests'] ) && $filter_params['guests'] > 0 ) {
				$properties_params['minimumGuests'] = $filter_params['guests'];
			}

			$properties_params = array_filter( $properties_params );

			if ( ! empty( $properties_params ) ) {

				$params = $properties_params;
				$params['agencyUid'] = get_option( 'hfsync_agency_uid' );
				$response = hfsync_api_get( '/v2/properties/', $params, intval( get_option( 'hfsync_availability_cache_period' ) ) );

				if ( is_wp_error( $response ) || empty( $response ) ) {
					$query_args['p'] = -1;
	
				} elseif ( ! empty( $response['propertiesUids'] ) ) {
					$query_args['meta_query'][] = array(
						'key'     => Config::PROPERTY_UID_META_KEY,
						'value'   => $response['propertiesUids'],
						'compare' => 'IN'
					);
				}
			}

			// Utils::d( $properties_params );
			// hfsync_api_get_properties();

		} elseif ( ! empty( $filter_params['guests'] ) ) {
			$query_args['meta_query'][] = array(
				'key'     => 'maximum_guests',
				'value'   => $filter_params['guests'],
				'compare' => '>='
			);
		}

		return $query_args;
	}

	/**
	 * Before properties loop
	 */
	public static function before_loop( $attrs, $query ) {
		?>
		<div class="properties-wrap uk-margin">
			<p class="uk-text-center">
				<?php 
					printf( 
						_n( '%d Property', '%d Properties', $query->found_posts ), 
						$query->found_posts
					);

					if ( ! empty( $_REQUEST['checkin'] ) || ! empty( $_REQUEST['checkout'] ) ) {
						$date1 = new DateTime( $_REQUEST['checkin'] );
						$date2 = new DateTime( $_REQUEST['checkout'] );
						$interval = $date1->diff( $date2 );
						$total_nights = max( 1, $interval->days );

						echo ' &middot; ';
						printf( _n( '%d Night', '%s Nights', $total_nights ), $total_nights );
					}

					if ( ! empty( $_REQUEST['checkin'] ) 
						|| ! empty( $_REQUEST['checkout'] ) 
						|| ! empty( $_REQUEST['guests'] ) ) {
						echo ' &middot; ';
						printf( '<a href="%s">%s</a>', get_permalink(), __( 'Reset Filter' ) );
					}
				?>
			</p>
			<div class="properties uk-child-width-1-1 uk-child-width-1-2@s uk-child-width-1-3@l uk-grid-match" uk-grid>
		<?php
	}

	public static function after_loop( $attrs, $query ) {
		?>
		</div>
		<?php 
			if ( $query->max_num_pages > $query->get( 'paged' ) ) {
				echo '<div class="uk-margin-top">';
				printf( 
					'<button class="properties-more-btn uk-button uk-button-primary uk-align-center" type="button" data-attrs="%s" data-filter="%s" data-page="%d" data-maxpages="%d">Load More</button>',
					base64_encode( serialize( $attrs ) ),
					base64_encode( serialize( $_REQUEST ) ),
					$query->get( 'paged' ) + 1,
					$query->max_num_pages
				);
				echo '</div>';
			}
		?>
		</div>
		<?php
	}
}