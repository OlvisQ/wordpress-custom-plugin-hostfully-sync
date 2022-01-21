All fields are mapped in [hostfully-sync\includes\Property.php](includes/Property.php) file. 



### In this file:

* `$this->meta_prefix` is set to ``.
* `$this->api_property` contains a single property information from [property api](https://dev.hostfully.com/reference/propertiesuid)
* `$this->api_descriptions` contains a single property descriptions from [property descriptions api](https://dev.hostfully.com/reference/propertydescriptionsuid)


## Metadata
* Do no remove `Config::PROPERTY_UID_META_KEY` line as this contains the property relation between hostfully and your wp site.
* `$acf_fields` contains the key/value pair. Add or remove fields from there.

```php
public function update_metadata() {
	$acf_fields = array(
		Config::PROPERTY_UID_META_KEY        => $this->api_property['uid'],
		$this->meta_prefix . 'internal_name' => $this->api_property['name'],
		$this->meta_prefix . 'guests'        => $this->api_property['maximumGuests'],
		$this->meta_prefix . 'bedrooms'      => $this->api_property['bedrooms'],
		$this->meta_prefix . 'bathrooms'     => $this->api_property['bathrooms'],
		$this->meta_prefix . 'web_link'      => $this->api_property['webLink'],
		$this->meta_prefix . 'location'      => $this->get_map_location(),
		$this->meta_prefix . 'address1'      => $this->api_property['address1'],
		$this->meta_prefix . 'address2'      => $this->api_property['address2'],
		$this->meta_prefix . 'city'          => $this->api_property['city'],
		$this->meta_prefix . 'zipcode'       => $this->api_property['postalCode'],
		$this->meta_prefix . 'state'         => $this->api_property['state'],
		$this->meta_prefix . 'country_code'  => $this->api_property['countryCode'],
		$this->meta_prefix . 'latitude'      => $this->api_property['latitude'],
		$this->meta_prefix . 'longitude'     => $this->api_property['longitude'],
		$this->meta_prefix . 'notes'         => $this->api_descriptions['notes'],
		$this->meta_prefix . 'access'        => $this->api_descriptions['access'],
		$this->meta_prefix . 'transit'       => $this->api_descriptions['transit'],
		$this->meta_prefix . 'interaction'   => $this->api_descriptions['interaction'],
		$this->meta_prefix . 'neighborhood'  => $this->api_descriptions['neighbourhood'],
		$this->meta_prefix . 'space'         => $this->api_descriptions['space' ],
	);

	foreach ( $acf_fields as $name => $value ) {
		update_field( $name, $value, $this->id );
	}
}
```

## Amenities
Amenities are stored in a custom taxonomy named `accommodation_amenity` from [amenity api](https://dev.hostfully.com/reference/amenitiesuid).

Taxonomy name can be changed in `hostfully-sync\includes\Config.php` file.

`const PROPERTY_AMENITY_TAXONOMY  = 'accommodation_amenity';`

## Category & Type
Property category and type is stored in separated custom taxonomy.

Those name can be changed in `hostfully-sync\includes\Config.php` file.

```php
const PROPERTY_TYPE_TAXONOMY     = 'accommodation_type_tag';
const PROPERTY_CATEGORY_TAXONOMY = 'accommodation_cat';
```

## Reviews
Property reviews are stored in ACF repeater field named `$this->meta_prefix . 'reviews'`;
Subfields mapped for [each review](https://dev.hostfully.com/reference/reviews-4) - 
```php
array(
	'review_author'      => $review['author'],
	'review_title'       => $review['title'],
	'review_content'     => $review['content'],
	'review_rating'      => $review['rating'],
	'review_date'        => $review['date'],
);
```


## Cover Photo
Cover photo is stored as featured image.

## Other Photos
Photos are stored in acf galley field named `$this->meta_prefix . 'photos'`;

```php
public function update_photo( $source, $desc = '' ) {

	$photo_ids = get_field( $this->meta_prefix . 'photos', $this->get_id(), false );
	if ( empty( $photo_ids ) ) {
		$photo_ids = array();
	}

	$media_id = $this->get_media_by_source( $source );
	if ( $media_id ) {
		if ( ! in_array( $media_id, $photo_ids ) ) {
			$photo_ids[] = $media_id;
			update_field( $this->meta_prefix . 'photos', $photo_ids, $this->get_id() );
		}

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
		update_field( $this->meta_prefix . 'photos', $photo_ids, $this->get_id() );
	}
}
```
