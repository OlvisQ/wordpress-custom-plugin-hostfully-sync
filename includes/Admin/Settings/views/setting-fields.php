<?php
return array(
	'general' => array(
		array(
			'id'                => 'hfsync_filter_fields',
			'name'              => 'hfsync_filter_fields',
			'label'             => __( 'Property Filter Fields', 'hfsync' ),
			'type'              => 'multicheck',
			'desc'				=> __( 'Select fields will be shown on property filter form alongside with date and guests fields.', 'hfsync' ),
			'options'           => $filter_fields,
			'default'           => array(),
			'sanitize_callback' => 'sanitize_text_field'
		),
		array(
			'id'                => 'hfsync_min_stay',
			'name'              => 'hfsync_min_stay',
			'label'             => __( 'Minimum Stay', 'hfsync' ),
			'type'              => 'number',
			'desc'				=> __( 'The minumum days a user can select for booking.', 'hfsync' ),
			'default'           => 1,
			'sanitize_callback' => 'sanitize_text_field'
		),
		array(
			'id'                => 'hfsync_max_calendar_days',
			'name'              => 'hfsync_max_calendar_days',
			'label'             => __( 'Maximum Calendar Days', 'hfsync' ),
			'type'              => 'number',
			'desc'				=> __( 'Enter number of days ahead an user can see the calendar. This will also impact the date prices we are storing from hostfully.', 'hfsync' ),
			'default'           => 90,
			'sanitize_callback' => 'sanitize_text_field'
		),
		array(
			'id'                => 'hfsync_hide_base_price',
			'name'              => 'hfsync_hide_base_price',
			'label'             => __( 'Date Search Base Price', 'hfsync' ),
			'type'              => 'checkbox',
			'desc'				=> __( 'Check to hide before search is performed.', 'hfsync' ),
			'default'           => 'yes',
			'sanitize_callback' => 'sanitize_text_field'
		),
		array(
			'id'                => 'hfsync_availability_cache_period',
			'name'              => 'hfsync_availability_cache_period',
			'label'             => __( 'Availability Cache Period', 'hfsync' ),
			'type'              => 'number',
			'desc'				=> __( 'In seconds. On frontend date search page, availability will cached for the given period. Leave empty or 0 to bypass cache.' ),
			'default'			=> 300,
			'sanitize_callback' => 'sanitize_text_field'
		),
	),
	'api' => array(
		array(
			'id'                => 'hfsync_api_platform',
			'name'              => 'hfsync_api_platform',
			'label'             => __( 'Api Platform', 'hfsync' ),
			'type'              => 'select',
			'desc'				=> __( 'NOTE: Agency Uid & Api Key differs by Platform.' ),
			'options'           => array(
				'sandbox'    => __( 'Sandbox', 'hfsync' ),
				'production' => __( 'Production', 'hfsync' )
			),
			'sanitize_callback' => 'sanitize_text_field'
		),
		array(
			'id'                => 'hfsync_agency_uid',
			'name'              => 'hfsync_agency_uid',
			'label'             => __( 'Agency Uid', 'hfsync' ),
			'type'              => 'text',
			'desc'				=> sprintf( 
				__( 'Login to your <a href="%s">hostfully</a> account to get this information.', 'hfsync' ),
				'https://platform.hostfully.com/login.jsp'
			),
			'sanitize_callback' => 'sanitize_text_field'
		),
		array(
			'id'                => 'hfsync_api_key',
			'name'              => 'hfsync_api_key',
			'label'             => __( 'Api Key', 'hfsync' ),
			'type'              => 'text',
			'sanitize_callback' => 'sanitize_text_field'
		),
	),
	'import' => array(
		array(
			'id'                => 'hfsync_max_photos_import',
			'name'              => 'hfsync_max_photos_import',
			'label'             => __( 'Maximum Photos to Import', 'hfsync' ),
			'desc'				=> __( 'Leave empty to import all property photos.', 'hfsync' ),
			'type'              => 'number',
			'sanitize_callback' => 'sanitize_text_field'
		),
		array(
			'id'                => 'hfsync_max_image_size_threshold',
			'name'              => 'hfsync_max_image_size_threshold',
			'label'             => __( 'Maximum Image Size Threshold', 'hfsync' ),
			'desc'				=> __( 'Maximum width/height of downloaded image, default 1000px.', 'hfsync' ),
			'type'              => 'number',
			'default'			=> 1000,
			'sanitize_callback' => 'sanitize_text_field'
		),
	),
	'synchronizer' => array(
		array(
			'id'                => 'hfsync_auto_sync_enabled',
			'name'              => 'hfsync_auto_sync_enabled',
			'label'             => __( 'Auto Synchronize?', 'hfsync' ),
			'desc'				=> '',
			'type'              => 'select',
			'options'           => array(
				'yes' => 'Yes',
				'no'  => 'No'
			),
			'sanitize_callback' => 'sanitize_text_field'
		),
		array(
			'id'                => 'hfsync_auto_sync_interval',
			'name'              => 'hfsync_auto_sync_interval',
			'label'             => __( 'Auto Sync Interval', 'hfsync' ),
			'desc'				=> __( 'Enter number in minutes. This defines how frequently properties should be updated.', 'hfsync' ),
			'type'              => 'number',
			'default'			=> 300,
			'sanitize_callback' => 'sanitize_text_field'
		),
		array(
			'id'                => 'hfsync_manual_sync_processor',
			'name'              => 'hfsync_manual_sync_processor',
			'label'             => __( 'Manual Sync Processor', 'hfsync' ),
			'desc'				=> __( 'Cronjob will work in the background. Ajax requires you to keep the browser tab open. Ajax processor perform faster than Cronjob.', 'hfsync' ),
			'type'              => 'select',
			'options'           => array(
				'cron' => 'Cronjob',
				'ajax' => 'Ajax'
			),
			'default'			=> 'ajax',
			'sanitize_callback' => 'sanitize_text_field'
		),
	),
);