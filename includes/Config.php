<?php
/**
 * Config Class File.
 *
 * @package Hostfully_Sync
 */

namespace Hostfully_Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Config Class.
 *
 * @class Config
 */
class Config {

	const PROPERTY_POST_TYPE    = 'property';
	const PROPERTY_CATEGORY     = 'property_cat';
	const PROPERTY_AMENITY      = 'property_amenity';
	const PROPERTY_TYPE         = 'property_type';
	const PROPERTY_LISTING_TYPE = 'listing_type';
	const PROPERTY_UID_META_KEY = 'hostfully_property_uid';
}
