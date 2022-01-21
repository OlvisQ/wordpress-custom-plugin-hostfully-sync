<?php
/**
 * Property Class File.
 *
 * @package Hostfully_Sync
 */
namespace Hostfully_Sync;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Property class
 */
class Property {

	/**
	 * Hostfully property uid.
	 * 
	 * @var string
	 */
	public $uid;

	/**
	 * Local property post id.
	 * 
	 * @var string
	 */
	public $id = 0;

	/**
	 * Hostfully property data.
	 */
	public $api_property = null;

	/**
	 * Hostfully property descriptions data.
	 */
	public $api_descriptions = null;

	/**
	 * Local property meta key prefix.
	 */
	public static $meta_prefix = '';

	/**
	 * Avoid api cache.
	 */
	public $api_refresh = false;

	/**
	 * Construct.
	 */
	public function __construct( $uid = '', $id = 0 ) {
		if ( $uid ) {
			$this->uid = $uid;
		}

		if ( $id && get_post( $id ) ) {
			$this->id = $id;
		}

		if ( $this->id && ! $this->uid ) {
			$this->uid = get_post_meta( $this->id, Config::PROPERTY_UID_META_KEY, true );
		}

		if ( $this->uid && ! $this->id ) {
			$this->id = hfsync_get_local_property_id( $this->uid );
		}
	}

	public function get_id() {
		return $this->id;
	}

	public function get_uid() {
		return $this->uid;
	}

	public function ignore_api_cached() {
		$this->api_refresh = true;
	}

	public function use_api_cached() {
		$this->api_refresh = false;
	}

	/**
	 * Import everything in single run.
	 */
	public function import_now() {
		if ( $this->get_id() > 0 ) {
			$this->update();
		} else {
			$this->create();
		}

		$this->update_reviews();
		$this->update_amenities();
		$this->update_main_image();
		$this->update_photos();
		$this->finalize_photos();
	}

	/**
	 * Create local property.
	 */
	public function create() {
		$this->load_api_data();
		$this->create_post();
		$this->update_metadata();
		$this->update_terms();
		$this->update_calendar_pricing();
	}

	/**
	 * Update local property.
	 */
	public function update() {
		if ( ! $this->get_id() ) {
			throw new Exception( 'No local property found.' );
		}

		$this->load_api_data();
		$this->update_post();
		$this->update_metadata();
		$this->update_terms();
		$this->update_calendar_pricing();
	}


	/**
	 * Update property metadata
	 */
	public function update_metadata() {
		$acf_fields = array(
			Config::PROPERTY_UID_META_KEY          => $this->api_property['uid'],
			self::$meta_prefix . 'internal_name'   => $this->api_property['name'],
			self::$meta_prefix . 'base_daily_rate' => $this->api_property['baseDailyRate'],
			self::$meta_prefix . 'currency_symbol' => $this->api_property['currencySymbol'],
			self::$meta_prefix . 'minimum_stay'    => $this->api_property['minimumStay'],
			self::$meta_prefix . 'maximum_stay'    => $this->api_property['maximumStay'],
			self::$meta_prefix . 'base_guests'     => $this->api_property['baseGuests'],
			self::$meta_prefix . 'maximum_guests'  => $this->api_property['maximumGuests'],
			self::$meta_prefix . 'extra_guest_fee' => $this->api_property['extraGuestFee'],
			self::$meta_prefix . 'cleaning_fee_amount' => $this->api_property['cleaningFeeAmount'],
			self::$meta_prefix . 'taxation_rate'   => $this->api_property['taxationRate'],
			self::$meta_prefix . 'bedrooms'        => $this->api_property['bedrooms'],
			self::$meta_prefix . 'bathrooms'       => $this->api_property['bathrooms'],
			self::$meta_prefix . 'web_link'        => $this->api_property['webLink'],
			self::$meta_prefix . 'geolocation'     => $this->get_map_location(),
			self::$meta_prefix . 'address1'        => $this->api_property['address1'],
			self::$meta_prefix . 'address2'        => $this->api_property['address2'],
			self::$meta_prefix . 'city'            => $this->api_property['city'],
			self::$meta_prefix . 'zipcode'         => $this->api_property['postalCode'],
			self::$meta_prefix . 'state'           => $this->api_property['state'],
			self::$meta_prefix . 'country_code'    => $this->api_property['countryCode'],
			self::$meta_prefix . 'latitude'        => $this->api_property['latitude'],
			self::$meta_prefix . 'longitude'       => $this->api_property['longitude'],
			self::$meta_prefix . 'notes'           => $this->api_descriptions['notes'],
			self::$meta_prefix . 'access'          => $this->api_descriptions['access'],
			self::$meta_prefix . 'transit'         => $this->api_descriptions['transit'],
			self::$meta_prefix . 'interaction'     => $this->api_descriptions['interaction'],
			self::$meta_prefix . 'neighborhood'    => $this->api_descriptions['neighbourhood'],
			self::$meta_prefix . 'space'           => $this->api_descriptions['space' ],
		);

		foreach ( $acf_fields as $name => $value ) {
			update_field( $name, $value, $this->id );
		}
	}

	/**
	 * Update local property main image.
	 */
	public function update_main_image() {
		$this->load_api_data();

		if ( empty( $this->api_property['picture'] ) ) {
			delete_post_thumbnail( $this->get_id() );
			return false;
		}

		$source = $this->api_property['picture'];

		// Assing feature image if media already exists.
		$media_id = $this->get_media_by_source( $source );
		if ( $media_id ) {
			set_post_thumbnail( $this->get_id(), $media_id );
			return;
		}

		$thumbnail_id = get_post_meta( $this->get_id(), '_thumbnail_id', true );

		if ( $thumbnail_id && $source === get_post_meta( $thumbnail_id, '_hfsync_source', true ) ) {
			return true;
		}

		// Do the validation and storage stuff.
        $media_id = $this->download_image( $source );

        if ( is_wp_error( $media_id ) ) {
			throw new Exception( $media_id->get_error_message() );
		}

		$desc = sprintf( '%s Main Image', get_post_field( 'post_title', $this->get_id() ) );

		// Add a meta for the media to find it later.
		update_post_meta( $media_id, '_hfsync_source', $source );

		// Import identifier.
		update_post_meta( $media_id, '_hfsync_imported', time() );

		// Update alt text.
		update_post_meta( $media_id, '_wp_attachment_image_alt', $desc );

		// Set image as post thumbnail.
		set_post_thumbnail( $this->get_id(), $media_id );

		return true;
	}

	/**
	 * Update local property photos.
	 */
	public function update_photos() {
		$photos = hfsync_api_get_property_photos( $this->get_uid(), $this->api_refresh );
		if ( is_wp_error( $photos ) ) {
			throw new Exception( $photos->get_error_message() );
		}

		if ( empty( $photos ) ) {
			return false;
		}

		uasort( $photos, 'hfsync_sort_by_display_order' );

		$max_photos = intval( get_option( 'hfsync_max_photos_import', 1000 ) );
		if ( $max_photos > 0 && count( $photos ) > $max_photos ) {
			$photos = array_slice( $photos, 0, $max_photos );
		}

		foreach ( $photos as $photo ) {
			$this->update_photo( $photo['url'], $photo['description'] );
		}
	}

	/**
	 * Update local property individual photo.
	 */
	public function update_photo( $source, $desc = '' ) {

		$photo_ids = get_field( self::$meta_prefix . 'photos', $this->get_id(), false );
		if ( empty( $photo_ids ) ) {
			$photo_ids = array();
		}

		$media_id = $this->get_media_by_source( $source );
		if ( $media_id ) {
			if ( ! in_array( $media_id, $photo_ids ) ) {
				$photo_ids[] = $media_id;
				update_field( self::$meta_prefix . 'photos', $photo_ids, $this->get_id() );
			}

			// Update alt text.
			update_post_meta( $media_id, '_wp_attachment_image_alt', $desc );

			return $media_id;
		}

		// Do the validation and storage stuff.
		$media_id = $this->download_image( $source );

		if ( is_wp_error( $media_id ) ) {
			return $media_id;
		}

		// Add a meta for the media to find it later.
		update_post_meta( $media_id, '_hfsync_source', $source );

		// Import identifier.
		update_post_meta( $media_id, '_hfsync_imported', time() );

		// Update alt text.
		update_post_meta( $media_id, '_wp_attachment_image_alt', $desc );

		if ( ! in_array( $media_id, $photo_ids ) ) {
			$photo_ids[] = $media_id;
			update_field( self::$meta_prefix . 'photos', $photo_ids, $this->get_id() );
		}
	}

	public function finalize_photos() {
		$photos = hfsync_api_get_property_photos( $this->get_uid(), $this->api_refresh );
		if ( is_wp_error( $photos ) ) {
			throw new Exception( $photos->get_error_message() );
		}

		uasort( $photos, 'hfsync_sort_by_display_order' );

		$max_photos = intval( get_option( 'hfsync_max_photos_import', 1000 ) );
		if ( $max_photos > 0 && count( $photos ) > $max_photos ) {
			$photos = array_slice( $photos, 0, $max_photos );
		}

		$old_photo_ids = get_field( self::$meta_prefix . 'photos', $this->get_id(), false );
		if ( empty( $old_photo_ids ) ) {
			$old_photo_ids = array();
		}

		if ( empty( $old_photo_ids ) && empty( $photos ) ) {
			return;
		}

		$photos_to_delete = array();
		$photo_source_ids = array();
		$new_photo_ids = array();

		if ( ! empty( $old_photo_ids ) ) {
			foreach ( $old_photo_ids as $photo_id ) {
				$source = get_post_meta( $photo_id, '_hfsync_source', true );
				if ( empty( $source ) ) {
					continue;
				}
	
				if ( isset( $photo_source_ids[ $source ] ) ) {
					$photos_to_delete[] = $photo_id;
					continue;
				}
	
				$photo_source_ids[ $source ] = $photo_id;
			}
		}

		if ( ! empty( $photos ) ) {
			foreach ( $photos as $photo ) {
				if ( isset( $photo_source_ids[ $photo['url'] ] ) ) {
					$new_photo_ids[] = $photo_source_ids[ $photo['url'] ];
				}
			}
		}

		update_field( self::$meta_prefix . 'photos', $new_photo_ids, $this->get_id() );

		if ( ! empty( $photo_source_ids ) ) {
			foreach ( $photo_source_ids as $source => $photo_id ) {
				if ( ! in_array( $photo_id, $new_photo_ids ) ) {
					$photos_to_delete[] = $photo_id;
				}
			}
		}

		if ( ! empty( $photos_to_delete ) ) {
			hfsync_log( 'Photos to be deleted', $photos_to_delete );
			foreach ( $photos_to_delete as $photo_id ) {
				wp_delete_post( $photo_id, true );
			}
		}
	}

	/**
	 * Update property reviews
	 */
	public function update_reviews() {
		$reviews = hfsync_api_get_property_reviews( $this->get_uid(), $this->api_refresh );
		if ( is_wp_error( $reviews ) ) {
			throw new Exception( $reviews->get_error_message() );
		}

		$review_data = array();
		if ( ! empty( $reviews ) ) {
			foreach ( $reviews as $review ) {
				$review_data[] = array(
					'review_author'      => $review['author'],
					'review_title'       => $review['title'],
					'review_content'     => $review['content'],
					'review_rating'      => $review['rating'],
					'review_date'        => $review['date'],
				);
			}

			update_field( self::$meta_prefix . 'reviews', $review_data, $this->get_id() );
		}
	}

	/**
	 * Update property terms
	 */
	public function update_terms() {
		$tax_inputs = array(
			array(
				'taxonomy' => Config::PROPERTY_TYPE,
				'term'     => $this->api_property['type']
			),
			array(
				'taxonomy' => Config::PROPERTY_CATEGORY,
				'term'     => $this->api_property['city']
			)
		);

		foreach ( $tax_inputs as $tax_input ) {
			if ( ! empty( $tax_input['term'] ) && taxonomy_exists( $tax_input['taxonomy'] ) ) {
				$term_ids = array( $this->create_term( $tax_input['term'], $tax_input['taxonomy'] ) );
	
				$response = wp_set_object_terms( $this->get_id(), $term_ids, $tax_input['taxonomy'] );
				if ( is_wp_error( $response ) ) {
					throw new Exception( $response->get_error_message() );
				}
			}
		}
	}

	public function update_calendar_pricing() {
		$from = date( 'Y-m-d' );
		$to = date( 'Y-m-d', time() + ( intval( get_option( 'hfsync_max_calendar_days' ) ) * DAY_IN_SECONDS ) );

		$calendar_pricing = hfsync_api_get_property_calendar_pricing( $this->get_uid(), $from, $to, $this->api_refresh );
		if ( is_wp_error( $calendar_pricing ) ) {
			throw new Exception( $calendar_pricing->get_error_message() );
		}

		$data = '';
		foreach ( $calendar_pricing['entries'] as $day_pricing ) {
			$data .= $day_pricing['date'] . ' '. hfsync_normalize_price( $day_pricing['pricing']['value'] ) . "\n";
		}

		update_field( self::$meta_prefix . 'pricing', $data, $this->get_id() );

		// Utils::p( $from );
		// Utils::p( $data );
		// Utils::d( $calendar_pricing );
	}

	/**
	 * Update amenities from api.
	 */
	public function update_amenities() {
		$amenities = hfsync_api_get_property_amenities( $this->get_uid(), $this->api_refresh );
		if ( is_wp_error( $amenities ) ) {
			throw new Exception( $amenities->get_error_message() );
		}

		// Load all terms.
		$amenity_terms = get_terms(
			array(
				'taxonomy'   => Config::PROPERTY_AMENITY,
				'hide_empty' => false
			)
		);

		$term_ids = array();

		foreach ( $amenities as $amenity_key => $true ) {
			if ( ! $true ) {
				continue;
			}

			$amenity_name = $this->format_amenity_key_to_name( $amenity_key );
			$term_ids[]   = $this->create_term( $amenity_name, Config::PROPERTY_AMENITY );
		}

		$response = wp_set_object_terms( $this->get_id(), $term_ids, Config::PROPERTY_AMENITY );
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}
	}

	/**
	 * Load hostfully property data.
	 */
	public function load_api_data() {

		if ( $this->api_property === null ) {
			$property = hfsync_api_get_property( $this->uid, $this->api_refresh );
			if ( is_wp_error( $property ) ) {
				throw new Exception( $property->get_error_message() );
			}

			$this->api_property = $property;
		}

		if ( $this->api_descriptions === null ) {
			$descriptions = hfsync_api_get_property_descriptions( $this->uid, $this->api_refresh );
			if ( is_wp_error( $descriptions ) ) {
				throw new Exception( $descriptions->get_error_message() );
			}

			$this->api_descriptions = array_shift( $descriptions );
		}
	}

	/**
	 * Download image to media library from url.
	 * 
	 * @param string $source Image source.
	 */
	protected function download_image( $source ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$head = wp_remote_head( $source );
		if ( is_wp_error( $head ) ) {
			return $head;
		}

		if ( empty( $response['headers']['content-type'] ) ) {
			$mime_type = 'image/jpeg';
		} else {
			$mime_type = $response['headers']['content-type'];
		}

		$file_name = wp_basename( $source ) . '.' . substr( $mime_type, 6 );

		$file_array         = array();
        $file_array['name'] = $file_name;
        $file_array['tmp_name'] = download_url( $source );

		if ( is_wp_error( $file_array['tmp_name'] ) ) {
            return $file_array['tmp_name'];
        }

		add_filter( 'wp_handle_upload', array( $this, 'handle_upload' ) );

		// Do the validation and storage stuff.
        $media_id = media_handle_sideload( $file_array, $this->get_id() );

		remove_filter( 'wp_handle_upload', array( $this, 'handle_upload' ) );

		// If error storing permanently, unlink.
        if ( is_wp_error( $media_id ) ) {
            @unlink( $file_array['tmp_name'] );
		}

		return $media_id;
	}

	/**
	 * Handle image upload
	 */
	public function handle_upload( $file ) {
		if ( ! isset( $file['error'] ) && file_exists( $file['file'] ) ) {
			$threshold = absint( get_option( 'hfsync_max_image_size_threshold', 1000 ) );
			$this->downscale_image( $file['file'], $threshold );
		}

		return $file;
	}

	/**
	 * Downscale image.
	 */
	protected function downscale_image( $file, $threshold ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$imagesize = wp_getimagesize( $file );
		if ( empty( $imagesize ) ) {
			return;
		}
	
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

		if ( $threshold && ( $image_meta['width'] > $threshold || $image_meta['height'] > $threshold ) ) {
			$editor = wp_get_image_editor( $file );
			if ( ! is_wp_error( $editor ) ) {
				$resized = $editor->resize( $threshold, $threshold );
				if ( ! is_wp_error( $resized ) ) {
					$editor->save( $file );
				}
			}
		}
	}

	/**
	 * Find existing media by source / download url
	 * 
	 * @param string $source Image source.
	 */
	protected function get_media_by_source( $source ) {
		$posts = get_posts( 
			array( 
				'post_type'     => 'attachment',
				'post_status'   => 'inherit',
				'numberposts'   => 1,
				'fields'        => 'ids',
				'no_found_rows' => true,
				'meta_key'      => '_hfsync_source',
				'meta_value'    => $source
			)
		);

		if ( ! empty( $posts ) ) {
			return array_shift( $posts );
		}

		return 0;
	}

	/**
	 * Create property post
	 */
	protected function create_post() {
		$postdata = array(
			'post_title'    => ! empty( $this->api_descriptions['name'] ) ? $this->api_descriptions['name'] : $this->api_property['name'],
			'post_type'     => Config::PROPERTY_POST_TYPE,
			'post_status'   => (bool) $this->api_property['isActive'] ? 'publish' : 'pending',
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $this->api_property['createdDate'] / 1000 ),
			'post_content'  => $this->api_descriptions['summary'],
			'post_excerpt'  => $this->api_descriptions['shortSummary'],
			'meta_input'    => array(
				'_hfsync_imported' => time()
			)
		);

		$create = wp_insert_post( $postdata );
		if ( is_wp_error( $create ) ) {
			throw new Exception( $create->get_error_message() );
		}

		$this->id = (int) $create;
	}

	/**
	 * Create property post.
	 */
	protected function update_post() {
		$postdata = array(
			'ID'            => $this->get_id(),
			'post_title'    => ! empty( $this->api_descriptions['name'] ) ? $this->api_descriptions['name'] : $this->api_property['name'],
			'post_type'     => Config::PROPERTY_POST_TYPE,
			'post_status'   => (bool) $this->api_property['isActive'] ? 'publish' : 'pending',
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $this->api_property['createdDate'] / 1000 ),
			'post_content'  => $this->api_descriptions['summary'],
			'post_excerpt'  => $this->api_descriptions['shortSummary'],
			'meta_input'    => array(
				'_hfsync_updated' => time(),
			)
		);

		$update = wp_update_post( $postdata );
		if ( is_wp_error( $update ) ) {
			throw new Exception( $update->get_error_message() );
		}
	}

	/**
	 * Create a term.
	 */
	protected function create_term( $term, $taxonomy ) {
		$term_id = term_exists( $term, $taxonomy );
		if ( ! $term_id ) {
			$term_id = wp_insert_term( $term, $taxonomy );
		}

		if ( is_wp_error( $term_id ) ) {
			throw new Exception( $term_id->get_error_message() );
		}

		return (int) $term_id['term_id'];
	}

	/**
	 * Format hostfully amenity key to term name.
	 */
	protected function format_amenity_key_to_name( $amenity ) {
		$pieces = preg_split( '/(?=[A-Z])/', $amenity );
		if ( in_array( $pieces[0], array( 'is', 'has' ) ) ) {
			array_shift( $pieces );
		}

		$amenity = ucwords( implode( ' ', $pieces ) );

		$replacements = array(
			'T V'                      => 'TV',
			'Cable T V'                => 'Cable TV',
			'C D D V D Player'         => 'CD/DVD Player',
			'Childrens Books And Toys' => 'Children Books & Toys',
			'Pack N Play Travel Crib'  => 'Pack & Play TravelCrib',
			'Ski In Ski Out'           => 'Ski-In Ski-Out',
		);

		if ( isset( $replacements[ $amenity ] ) ) {
			$amenity = $replacements[ $amenity ];
		}

		return $amenity;
	}

	/**
	 * Organize map location data combining lat/lng and address
	 */
	protected function get_map_location() {
		$address_fields = array( 
			$this->api_property['address1'],
			$this->api_property['address2'],
			$this->api_property['city'],
			$this->api_property['state'],
			$this->api_property['countryCode']
		);

		$address_fields = array_map( 'trim', $address_fields );
		$address_fields = array_filter( $address_fields );

		return array(
			"address" => implode( ', ', $address_fields ),
			"lat"     => $this->api_property['latitude'],
			"lng"     => $this->api_property['longitude'],
			"zoom"    => 14
		);
	}
}