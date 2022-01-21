<?php
/**
 * Post_Type_Taxonomies
 *
 * @package Hostfully_Sync
 */
namespace Hostfully_Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post_Type_Taxonomies class
 */
class Post_Type_Taxonomies {

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ) );
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ) );
	}

	/**
	 * Register post types.
	 */
	public static function register_post_types() {
		$labels = array(
			'name'                     => __( 'Properties', 'hfsync' ),
			'singular_name'            => __( 'Property', 'hfsync' ),
			'menu_name'                => __( 'Properties', 'hfsync' ),
			'all_items'                => __( 'All Properties', 'hfsync' ),
			'add_new'                  => __( 'Add new', 'hfsync' ),
			'add_new_item'             => __( 'Add new Property', 'hfsync' ),
			'edit_item'                => __( 'Edit Property', 'hfsync' ),
			'new_item'                 => __( 'New Property', 'hfsync' ),
			'view_item'                => __( 'View Property', 'hfsync' ),
			'view_items'               => __( 'View Properties', 'hfsync' ),
			'search_items'             => __( 'Search Properties', 'hfsync' ),
			'not_found'                => __( 'No Properties found', 'hfsync' ),
			'not_found_in_trash'       => __( 'No Properties found in trash', 'hfsync' ),
			'parent'                   => __( 'Parent Property:', 'hfsync' ),
			'featured_image'           => __( 'Featured image for this Property', 'hfsync' ),
			'set_featured_image'       => __( 'Set featured image for this Property', 'hfsync' ),
			'remove_featured_image'    => __( 'Remove featured image for this Property', 'hfsync' ),
			'use_featured_image'       => __( 'Use as featured image for this Property', 'hfsync' ),
			'archives'                 => __( 'Property archives', 'hfsync' ),
			'insert_into_item'         => __( 'Insert into Property', 'hfsync' ),
			'uploaded_to_this_item'    => __( 'Upload to this Property', 'hfsync' ),
			'filter_items_list'        => __( 'Filter Properties list', 'hfsync' ),
			'items_list_navigation'    => __( 'Properties list navigation', 'hfsync' ),
			'items_list'               => __( 'Properties list', 'hfsync' ),
			'attributes'               => __( 'Properties attributes', 'hfsync' ),
			'name_admin_bar'           => __( 'Property', 'hfsync' ),
			'item_published'           => __( 'Property published', 'hfsync' ),
			'item_published_privately' => __( 'Property published privately.', 'hfsync' ),
			'item_reverted_to_draft'   => __( 'Property reverted to draft.', 'hfsync' ),
			'item_scheduled'           => __( 'Property scheduled', 'hfsync' ),
			'item_updated'             => __( 'Property updated.', 'hfsync' ),
			'parent_item_colon'        => __( 'Parent Property:', 'hfsync' ),
		);

		$args = array(
			'label'                 => __( 'Properties', 'hfsync' ),
			'labels'                => $labels,
			'description'           => 'Bookable properties',
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'show_in_rest'          => true,
			'rest_base'             => 'properties',
			'has_archive'           => 'properties',
			'show_in_menu'          => true,
			'show_in_nav_menus'     => true,
			'delete_with_user'      => false,
			'exclude_from_search'   => false,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => array(
				'slug'       => Config::PROPERTY_POST_TYPE,
				'with_front' => true,
			),
			'query_var'             => true,
			'menu_icon'             => 'dashicons-admin-home',
			'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author' ),
			'taxonomies'            => array( 'discover_tag' ),
			'show_in_graphql'       => false,
		);

		register_post_type( Config::PROPERTY_POST_TYPE, $args );
	}

	/**
	 * Register taxonomies.
	 */
	public static function register_taxonomies() {
		/**
		 * Taxonomy: Property Categories.
		 */
		$labels = array(
			'name'                       => __( 'Property Categories', 'hfsync' ),
			'singular_name'              => __( 'Property Category', 'hfsync' ),
			'menu_name'                  => __( 'Categories', 'hfsync' ),
			'all_items'                  => __( 'All Property Categories', 'hfsync' ),
			'edit_item'                  => __( 'Edit Property Category', 'hfsync' ),
			'view_item'                  => __( 'View Property Category', 'hfsync' ),
			'update_item'                => __( 'Update Property Category name', 'hfsync' ),
			'add_new_item'               => __( 'Add new Property Category', 'hfsync' ),
			'new_item_name'              => __( 'New Property Category name', 'hfsync' ),
			'parent_item'                => __( 'Parent Property Category', 'hfsync' ),
			'parent_item_colon'          => __( 'Parent Property Category:', 'hfsync' ),
			'search_items'               => __( 'Search Property Categories', 'hfsync' ),
			'popular_items'              => __( 'Popular Property Categories', 'hfsync' ),
			'separate_items_with_commas' => __( 'Separate Property Categories with commas', 'hfsync' ),
			'add_or_remove_items'        => __( 'Add or remove Property Categories', 'hfsync' ),
			'choose_from_most_used'      => __( 'Choose from the most used Property Categories', 'hfsync' ),
			'not_found'                  => __( 'No Property Categories found', 'hfsync' ),
			'no_terms'                   => __( 'No Property Categories', 'hfsync' ),
			'items_list_navigation'      => __( 'Property Categories list navigation', 'hfsync' ),
			'items_list'                 => __( 'Property Categories list', 'hfsync' ),
			'back_to_items'              => __( 'Back to Property Categories', 'hfsync' ),
		);
		$args   = array(
			'label'                 => __( 'Property Categories', 'hfsync' ),
			'labels'                => $labels,
			'public'                => true,
			'publicly_queryable'    => true,
			'hierarchical'          => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => true,
			'query_var'             => true,
			'rewrite'               => array(
				'slug'       => 'property-category',
				'with_front' => true,
			),
			'show_admin_column'     => true,
			'show_in_rest'          => true,
			'show_tagcloud'         => false,
			'rest_base'             => 'property-categories',
			'show_in_quick_edit'    => false,
			'show_in_graphql'       => false,
		);
		register_taxonomy( Config::PROPERTY_CATEGORY, array( Config::PROPERTY_POST_TYPE ), $args );

		/**
		 * Taxonomy: Property Amenities.
		 */
		$labels = array(
			'name'                       => __( 'Property Amenities', 'hfsync' ),
			'singular_name'              => __( 'Property Amenity', 'hfsync' ),
			'menu_name'                  => __( 'Amenities', 'hfsync' ),
			'all_items'                  => __( 'All Property Amenities', 'hfsync' ),
			'edit_item'                  => __( 'Edit Property Amenity', 'hfsync' ),
			'view_item'                  => __( 'View Property Amenity', 'hfsync' ),
			'update_item'                => __( 'Update Property Amenity name', 'hfsync' ),
			'add_new_item'               => __( 'Add new Property Amenity', 'hfsync' ),
			'new_item_name'              => __( 'New Property Amenity name', 'hfsync' ),
			'parent_item'                => __( 'Parent Property Amenity', 'hfsync' ),
			'parent_item_colon'          => __( 'Parent Property Amenity:', 'hfsync' ),
			'search_items'               => __( 'Search Property Amenities', 'hfsync' ),
			'popular_items'              => __( 'Popular Property Amenities', 'hfsync' ),
			'separate_items_with_commas' => __( 'Separate Property Amenities with commas', 'hfsync' ),
			'add_or_remove_items'        => __( 'Add or remove Property Amenities', 'hfsync' ),
			'choose_from_most_used'      => __( 'Choose from the most used Property Amenities', 'hfsync' ),
			'not_found'                  => __( 'No Property Amenities found', 'hfsync' ),
			'no_terms'                   => __( 'No Property Amenities', 'hfsync' ),
			'items_list_navigation'      => __( 'Property Amenities list navigation', 'hfsync' ),
			'items_list'                 => __( 'Property Amenities list', 'hfsync' ),
			'back_to_items'              => __( 'Back to Property Amenities', 'hfsync' ),
		);
		$args   = array(
			'label'                 => __( 'Property Amenities', 'hfsync' ),
			'labels'                => $labels,
			'public'                => true,
			'publicly_queryable'    => true,
			'hierarchical'          => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => true,
			'query_var'             => true,
			'rewrite'               => array(
				'slug'       => 'property-amenity',
				'with_front' => true,
			),
			'show_admin_column'     => false,
			'show_in_rest'          => true,
			'show_tagcloud'         => false,
			'rest_base'             => 'property-amenities',
			'show_in_quick_edit'    => false,
			'show_in_graphql'       => false,
		);
		register_taxonomy( Config::PROPERTY_AMENITY, array( Config::PROPERTY_POST_TYPE ), $args );

		/**
		* Taxonomy: Property Types.
		*/
		$labels = array(
			'name'                       => __( 'Property Types', 'hfsync' ),
			'singular_name'              => __( 'Property Type', 'hfsync' ),
			'menu_name'                  => __( 'Types', 'hfsync' ),
			'all_items'                  => __( 'All Property Types', 'hfsync' ),
			'edit_item'                  => __( 'Edit Property Type', 'hfsync' ),
			'view_item'                  => __( 'View Property Type', 'hfsync' ),
			'update_item'                => __( 'Update Property Type name', 'hfsync' ),
			'add_new_item'               => __( 'Add new Property Type', 'hfsync' ),
			'new_item_name'              => __( 'New Property Type name', 'hfsync' ),
			'parent_item'                => __( 'Parent Property Type', 'hfsync' ),
			'parent_item_colon'          => __( 'Parent Property Type:', 'hfsync' ),
			'search_items'               => __( 'Search Property Types', 'hfsync' ),
			'popular_items'              => __( 'Popular Property Types', 'hfsync' ),
			'separate_items_with_commas' => __( 'Separate Property Types with commas', 'hfsync' ),
			'add_or_remove_items'        => __( 'Add or remove Property Types', 'hfsync' ),
			'choose_from_most_used'      => __( 'Choose from the most used Property Types', 'hfsync' ),
			'not_found'                  => __( 'No Property Types found', 'hfsync' ),
			'no_terms'                   => __( 'No Property Types', 'hfsync' ),
			'items_list_navigation'      => __( 'Property Types list navigation', 'hfsync' ),
			'items_list'                 => __( 'Property Types list', 'hfsync' ),
			'back_to_items'              => __( 'Back to Property Types', 'hfsync' ),
		);
		$args   = array(
			'label'                 => __( 'Property Types', 'hfsync' ),
			'labels'                => $labels,
			'public'                => true,
			'publicly_queryable'    => true,
			'hierarchical'          => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => true,
			'query_var'             => true,
			'rewrite'               => array(
				'slug'       => 'property-type',
				'with_front' => true,
			),
			'show_admin_column'     => true,
			'show_in_rest'          => true,
			'show_tagcloud'         => false,
			'rest_base'             => 'property-types',
			'show_in_quick_edit'    => false,
			'show_in_graphql'       => false,
		);
		register_taxonomy( Config::PROPERTY_TYPE, array( Config::PROPERTY_POST_TYPE ), $args );

		/**
		 * Taxonomy: Listing Type Tags.
		 */
		$labels = array(
			'name'          => __( 'Listing Type', 'hfsync' ),
			'singular_name' => __( 'Listing Type', 'hfsync' ),
			'menu_name'     => __( 'Listing Type', 'hfsync' ),
		);
		$args   = array(
			'label'                 => __( 'Listing Type', 'hfsync' ),
			'labels'                => $labels,
			'public'                => true,
			'publicly_queryable'    => true,
			'hierarchical'          => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => true,
			'query_var'             => true,
			'rewrite'               => array(
				'slug'       => 'listing-type',
				'with_front' => true,
			),
			'show_admin_column'     => false,
			'show_in_rest'          => true,
			'show_tagcloud'         => false,
			'rest_base'             => 'listing-types',
			'show_in_quick_edit'    => false,
			'show_in_graphql'       => false,
		);
		register_taxonomy( Config::PROPERTY_LISTING_TYPE, array( Config::PROPERTY_POST_TYPE ), $args );

		/**
		* Taxonomy: Property Flags.
		*/
		// $labels = array(
		// 	'name'                       => __( 'Property Flags', 'hfsync' ),
		// 	'singular_name'              => __( 'Property Flag', 'hfsync' ),
		// 	'menu_name'                  => __( 'Flags', 'hfsync' ),
		// 	'all_items'                  => __( 'All Property Flags', 'hfsync' ),
		// 	'edit_item'                  => __( 'Edit Property Flag', 'hfsync' ),
		// 	'view_item'                  => __( 'View Property Flag', 'hfsync' ),
		// 	'update_item'                => __( 'Update Property Flag name', 'hfsync' ),
		// 	'add_new_item'               => __( 'Add new Property Flag', 'hfsync' ),
		// 	'new_item_name'              => __( 'New Property Flag name', 'hfsync' ),
		// 	'parent_item'                => __( 'Parent Property Flag', 'hfsync' ),
		// 	'parent_item_colon'          => __( 'Parent Property Flag:', 'hfsync' ),
		// 	'search_items'               => __( 'Search Property Flags', 'hfsync' ),
		// 	'popular_items'              => __( 'Popular Property Flags', 'hfsync' ),
		// 	'separate_items_with_commas' => __( 'Separate Property Flags with commas', 'hfsync' ),
		// 	'add_or_remove_items'        => __( 'Add or remove Property Flags', 'hfsync' ),
		// 	'choose_from_most_used'      => __( 'Choose from the most used Property Flags', 'hfsync' ),
		// 	'not_found'                  => __( 'No Property Flags found', 'hfsync' ),
		// 	'no_terms'                   => __( 'No Property Flags', 'hfsync' ),
		// 	'items_list_navigation'      => __( 'Property Flags list navigation', 'hfsync' ),
		// 	'items_list'                 => __( 'Property Flags list', 'hfsync' ),
		// 	'back_to_items'              => __( 'Back to Property Flags', 'hfsync' ),
		// );
		// $args   = array(
		// 	'label'                 => __( 'Property Flags', 'hfsync' ),
		// 	'labels'                => $labels,
		// 	'public'                => true,
		// 	'publicly_queryable'    => true,
		// 	'hierarchical'          => false,
		// 	'show_ui'               => true,
		// 	'show_in_menu'          => true,
		// 	'show_in_nav_menus'     => true,
		// 	'query_var'             => true,
		// 	'rewrite'               => array(
		// 		'slug'       => 'property_flag',
		// 		'with_front' => true,
		// 	),
		// 	'show_admin_column'     => true,
		// 	'show_in_rest'          => true,
		// 	'show_tagcloud'         => false,
		// 	'rest_base'             => 'property_flags',
		// 	'show_in_quick_edit'    => false,
		// 	'show_in_graphql'       => false,
		// );
		// register_taxonomy( 'property_flag', array( Config::PROPERTY_POST_TYPE ), $args );

		/**
		 * Taxonomy: Discover Tags.
		 */
		// $labels = array(
		// 	'name'          => __( 'Discover Tags', 'hfsync' ),
		// 	'singular_name' => __( 'Discover Tag', 'hfsync' ),
		// 	'name'          => __( 'Discover Tags', 'hfsync' ),
		// 	'singular_name' => __( 'Discover Tag', 'hfsync' ),
		// 	'menu_name'     => __( 'Discover', 'hfsync' ),
		// );
		// $args   = array(
		// 	'label'                 => __( 'Discover Tags', 'hfsync' ),
		// 	'labels'                => $labels,
		// 	'public'                => true,
		// 	'publicly_queryable'    => true,
		// 	'hierarchical'          => false,
		// 	'show_ui'               => true,
		// 	'show_in_menu'          => true,
		// 	'show_in_nav_menus'     => true,
		// 	'query_var'             => true,
		// 	'rewrite'               => array(
		// 		'slug'       => 'discover_tag',
		// 		'with_front' => false,
		// 	),
		// 	'show_admin_column'     => true,
		// 	'show_in_rest'          => true,
		// 	'show_tagcloud'         => true,
		// 	'rest_base'             => 'discover_tags',
		// 	'show_in_quick_edit'    => true,
		// 	'show_in_graphql'       => false,
		// );

		// register_taxonomy( 'discover_tag', array( Config::PROPERTY_POST_TYPE ), $args );
	}
}
