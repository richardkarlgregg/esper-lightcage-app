<?php

// Register Custom Post Types
function esper_register_post_types() {
    // Job Post Type
    register_post_type('job', array(
        'labels' => array(
            'name' => 'Jobs',
            'singular_name' => 'Job',
            'add_new' => 'Add New Job',
            'add_new_item' => 'Add New Job',
            'edit_item' => 'Edit Job',
            'new_item' => 'New Job',
            'view_item' => 'View Job',
            'search_items' => 'Search Jobs',
            'not_found' => 'No jobs found',
            'not_found_in_trash' => 'No jobs found in Trash',
            'parent_item_colon' => ''
        ),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'rest_base' => 'job',
        'rest_controller_class' => 'WP_REST_Posts_Controller',
        'query_var' => true,
        'rewrite' => array('slug' => 'job'),
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_position' => 5,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields', 'author'),
        'menu_icon' => 'dashicons-portfolio'
    ));

    // Session Post Type
    register_post_type('session', array(
        'labels' => array(
            'name' => 'Sessions',
            'singular_name' => 'Session',
            'add_new' => 'Add New Session',
            'add_new_item' => 'Add New Session',
            'edit_item' => 'Edit Session',
            'new_item' => 'New Session',
            'view_item' => 'View Session',
            'search_items' => 'Search Sessions',
            'not_found' => 'No sessions found',
            'not_found_in_trash' => 'No sessions found in Trash',
            'parent_item_colon' => 'Parent Job:'
        ),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'rest_base' => 'session',
        'rest_controller_class' => 'WP_REST_Posts_Controller',
        'query_var' => true,
        'rewrite' => array('slug' => 'session'),
        'capability_type' => 'post',
        'hierarchical' => true,
        'menu_position' => 5,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes', 'author'),
        'menu_icon' => 'dashicons-calendar-alt'
    ));

    // Capture Post Type
    register_post_type('capture', array(
        'labels' => array(
            'name' => 'Captures',
            'singular_name' => 'Capture',
            'add_new' => 'Add New Capture',
            'add_new_item' => 'Add New Capture',
            'edit_item' => 'Edit Capture',
            'new_item' => 'New Capture',
            'view_item' => 'View Capture',
            'search_items' => 'Search Captures',
            'not_found' => 'No captures found',
            'not_found_in_trash' => 'No captures found in Trash',
            'parent_item_colon' => 'Parent Session:'
        ),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'rest_base' => 'capture',
        'rest_controller_class' => 'WP_REST_Posts_Controller',
        'query_var' => true,
        'rewrite' => array('slug' => 'capture'),
        'capability_type' => 'post',
        'hierarchical' => true,
        'menu_position' => 5,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes', 'author'),
        'menu_icon' => 'dashicons-camera'
    ));

    // Take Post Type
    register_post_type('take', array(
        'labels' => array(
            'name' => 'Takes',
            'singular_name' => 'Take',
            'add_new' => 'Add New Take',
            'add_new_item' => 'Add New Take',
            'edit_item' => 'Edit Take',
            'new_item' => 'New Take',
            'view_item' => 'View Take',
            'search_items' => 'Search Takes',
            'not_found' => 'No takes found',
            'not_found_in_trash' => 'No takes found in Trash',
            'parent_item_colon' => 'Parent Capture:'
        ),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'rest_base' => 'take',
        'rest_controller_class' => 'WP_REST_Posts_Controller',
        'query_var' => true,
        'rewrite' => array('slug' => 'take'),
        'capability_type' => 'post',
        'hierarchical' => true,
        'menu_position' => 5,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes', 'author'),
        'menu_icon' => 'dashicons-video-alt3'
    ));

    // Camera Settings Post Type
    register_post_type('camera_settings', array(
        'labels' => array(
            'name' => 'Camera Settings',
            'singular_name' => 'Camera Setting',
            'add_new' => 'Add New Camera Setting',
            'add_new_item' => 'Add New Camera Setting',
            'edit_item' => 'Edit Camera Setting',
            'new_item' => 'New Camera Setting',
            'view_item' => 'View Camera Setting',
            'search_items' => 'Search Camera Settings',
            'not_found' => 'No camera settings found',
            'not_found_in_trash' => 'No camera settings found in Trash',
            'parent_item_colon' => ''
        ),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'rest_base' => 'camera_settings',
        'rest_controller_class' => 'WP_REST_Posts_Controller',
        'query_var' => true,
        'rewrite' => array('slug' => 'camera-setting'),
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_position' => 6,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields', 'author'),
        'menu_icon' => 'dashicons-admin-settings'
    ));

    // Light Settings Post Type
    register_post_type('light_settings', array(
        'labels' => array(
            'name' => 'Light Settings',
            'singular_name' => 'Light Setting',
            'add_new' => 'Add New Light Setting',
            'add_new_item' => 'Add New Light Setting',
            'edit_item' => 'Edit Light Setting',
            'new_item' => 'New Light Setting',
            'view_item' => 'View Light Setting',
            'search_items' => 'Search Light Settings',
            'not_found' => 'No light settings found',
            'not_found_in_trash' => 'No light settings found in Trash',
            'parent_item_colon' => ''
        ),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'rest_base' => 'light_settings',
        'rest_controller_class' => 'WP_REST_Posts_Controller',
        'query_var' => true,
        'rewrite' => array('slug' => 'light-setting'),
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_position' => 7,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields', 'author'),
        'menu_icon' => 'dashicons-lightbulb'
    ));

    // Export Post Type
    register_post_type('export', array(
        'labels' => array(
            'name' => 'Exports',
            'singular_name' => 'Export',
            'add_new' => 'Add New Export',
            'add_new_item' => 'Add New Export',
            'edit_item' => 'Edit Export',
            'new_item' => 'New Export',
            'view_item' => 'View Export',
            'search_items' => 'Search Exports',
            'not_found' => 'No exports found',
            'not_found_in_trash' => 'No exports found in Trash',
            'parent_item_colon' => ''
        ),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'rest_base' => 'export',
        'rest_controller_class' => 'WP_REST_Posts_Controller',
        'query_var' => true,
        'rewrite' => array('slug' => 'export'),
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_position' => 5,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'menu_icon' => 'dashicons-download'
    ));
}
add_action('init', 'esper_register_post_types'); 