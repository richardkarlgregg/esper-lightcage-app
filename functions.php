<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Theme Setup
function esper_lightcage_setup() {
    // Add default posts and comments RSS feed links to head
    add_theme_support('automatic-feed-links');

    // Let WordPress manage the document title
    add_theme_support('title-tag');

    // Enable support for Post Thumbnails on posts and pages
    add_theme_support('post-thumbnails');

    // Add support for responsive embedded content
    add_theme_support('responsive-embeds');

    // Add support for HTML5 features
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));
}
add_action('after_setup_theme', 'esper_lightcage_setup');

// Hide WordPress admin bar
add_filter('show_admin_bar', '__return_false');

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
}
add_action('init', 'esper_register_post_types');


// Register REST API fields for relationships
function esper_register_rest_fields() {
    // Register parent_job field for sessions
    register_rest_field('session', 'parent_job', array(
        'get_callback' => function($post_arr) {
            return get_post_meta($post_arr['id'], 'parent_job', true);
        },
        'update_callback' => function($value, $post) {
            return update_post_meta($post->ID, 'parent_job', $value);
        },
        'schema' => array(
            'description' => 'Parent Job ID',
            'type' => 'integer'
        ),
    ));

    // Register parent_session field for captures
    register_rest_field('capture', 'parent_session', array(
        'get_callback' => function($post_arr) {
            return get_post_meta($post_arr['id'], 'parent_session', true);
        },
        'update_callback' => function($value, $post) {
            return update_post_meta($post->ID, 'parent_session', $value);
        },
        'schema' => array(
            'description' => 'Parent Session ID',
            'type' => 'integer'
        ),
    ));

    // Register parent_capture field for takes
    register_rest_field('take', 'parent_capture', array(
        'get_callback' => function($post_arr) {
            return get_post_meta($post_arr['id'], 'parent_capture', true);
        },
        'update_callback' => function($value, $post) {
            return update_post_meta($post->ID, 'parent_capture', $value);
        },
        'schema' => array(
            'description' => 'Parent Capture ID',
            'type' => 'integer'
        ),
    ));
}
add_action('rest_api_init', 'esper_register_rest_fields');

// Automatically set post author to current user
function esper_set_post_author($data, $postarr) {
    if (!in_array($data['post_type'], ['job', 'session', 'capture', 'take'])) {
        return $data;
    }

    // Only set author if it's a new post
    if (empty($postarr['ID'])) {
        $data['post_author'] = get_current_user_id();
    }

    return $data;
}
add_filter('wp_insert_post_data', 'esper_set_post_author', 10, 2);

// Flush rewrite rules on theme activation
function esper_rewrite_flush() {
    esper_register_post_types();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'esper_rewrite_flush');

// AJAX handlers
function esper_get_jobs() {
    // Verify nonce
    check_ajax_referer('esper_ajax_nonce', 'nonce');
    
    $args = array(
        'post_type' => 'job',
        'posts_per_page' => -1,
        'author' => get_current_user_id(),
        'orderby' => 'title',
        'order' => 'ASC'
    );
    
    $jobs = get_posts($args);
    $response = array();
    
    foreach ($jobs as $job) {
        $response[] = array(
            'id' => $job->ID,
            'title' => $job->post_title
        );
    }
    
    wp_send_json_success($response);
}
add_action('wp_ajax_esper_get_jobs', 'esper_get_jobs');

function esper_get_children() {
    // Verify nonce
    check_ajax_referer('esper_ajax_nonce', 'nonce');
    
    $parent_id = intval($_POST['parent_id']);
    $child_type = sanitize_text_field($_POST['child_type']);
    
    if (!in_array($child_type, array('session', 'capture', 'take'))) {
        wp_send_json_error('Invalid child type');
        return;
    }
    
    $args = array(
        'post_type' => $child_type,
        'posts_per_page' => -1,
        'author' => get_current_user_id(),
        'meta_query' => array(
            array(
                'key' => 'parent_' . $_POST['parent_type'],
                'value' => $parent_id
            )
        ),
        'orderby' => 'title',
        'order' => 'ASC'
    );
    
    $children = get_posts($args);
    $response = array();
    
    foreach ($children as $child) {
        $response[] = array(
            'id' => $child->ID,
            'title' => $child->post_title
        );
    }
    
    wp_send_json_success($response);
}
add_action('wp_ajax_esper_get_children', 'esper_get_children');

function esper_create_item() {
    // Verify nonce
    check_ajax_referer('esper_ajax_nonce', 'nonce');
    
    $title = sanitize_text_field($_POST['title']);
    $type = sanitize_text_field($_POST['type']);
    
    if (!in_array($type, array('job', 'session', 'capture', 'take'))) {
        wp_send_json_error('Invalid post type');
        return;
    }
    
    $post_data = array(
        'post_title' => $title,
        'post_type' => $type,
        'post_status' => 'publish',
        'post_author' => get_current_user_id()
    );
    
    $post_id = wp_insert_post($post_data);
    
    if ($post_id && !is_wp_error($post_id)) {
        // If there's a parent, set the relationship
        if (!empty($_POST['parent_type']) && !empty($_POST['parent_id'])) {
            $parent_type = sanitize_text_field($_POST['parent_type']);
            $parent_id = intval($_POST['parent_id']);
            update_post_meta($post_id, 'parent_' . $parent_type, $parent_id);
        }
        
        wp_send_json_success(array(
            'id' => $post_id,
            'title' => $title
        ));
    } else {
        wp_send_json_error('Failed to create item');
    }
}
add_action('wp_ajax_esper_create_item', 'esper_create_item');

// Helper function to generate a random serial number.
function generate_random_serial( $length = 8 ) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $serial = '';
    for ( $i = 0; $i < $length; $i++ ) {
        $serial .= $characters[ rand( 0, strlen( $characters ) - 1 ) ];
    }
    return $serial;
}

function create_light_and_camera_settings_for_capture( $post_id, $post, $update ) {
    // Only run for capture post type.
    if ( 'capture' !== $post->post_type ) {
        return;
    }

    // Only run on new posts (not on update).
    if ( $update ) {
        return;
    }

    // Bail out if this is an autosave or a revision.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    // If our meta already exists, do nothing.
    if ( get_post_meta( $post_id, 'capture_camera_settings', true ) || get_post_meta( $post_id, 'capture_light_settings', true ) ) {
        return;
    }

    // Create Camera Settings post.
    $camera_settings_post = array(
        'post_title'  => 'Camera Settings for Capture ' . $post_id,
        'post_type'   => 'camera_settings',
        'post_status' => 'publish',
        'post_author' => $post->post_author,
    );
    $camera_settings_id = wp_insert_post( $camera_settings_post );
    if ( ! is_wp_error( $camera_settings_id ) ) {
        // Link the camera settings to the capture by updating meta.
        update_post_meta( $camera_settings_id, 'parent_capture', $post_id );
        update_post_meta( $post_id, 'capture_camera_settings', $camera_settings_id );

        // Define camera brands and models.
        $camera_names  = array( "Canon", "Sony", "Nikon", "Fujifilm", "Panasonic", "Olympus" );
        $camera_models = array(
            "Canon"     => array( "Canon EOS 5D Mark IV", "Canon EOS Rebel T7i", "Canon EOS 90D" ),
            "Sony"      => array( "Sony Alpha a7 III", "Sony Alpha a6500", "Sony Alpha a7R IV" ),
            "Nikon"     => array( "Nikon D850", "Nikon Z6", "Nikon D750" ),
            "Fujifilm"  => array( "Fujifilm X-T3", "Fujifilm X-T30" ),
            "Panasonic" => array( "Panasonic Lumix GH5", "Panasonic Lumix S1" ),
            "Olympus"   => array( "Olympus OM-D E-M1 Mark III", "Olympus PEN-F" ),
        );

        // Generate a random number (between 2 and 5) of rows for the ACF repeater field.
        $num_rows = rand( 2, 5 );
        $rows     = array();
        for ( $i = 0; $i < $num_rows; $i++ ) {
            $brand  = $camera_names[ array_rand( $camera_names ) ];
            $model  = $camera_models[ $brand ][ array_rand( $camera_models[ $brand ] ) ];
            $serial = generate_random_serial();
            $rows[] = array(
                'camera_name'   => '',
                'serial_number' => $serial,
                'camera_model'  => $model,
                'iso'           => '',
                'aperture'      => '',
                'white_balance' => '',
                'colour_temp'   => '',
                'shutter_speed' => '',
                'file_type'     => '',
                'jpeg_quality'  => '',
                'drive_mode'    => '',
                'focus_mode'    => '',
            );
        }
        // Use ACF's update_field() to set the repeater field.
        update_field( 'camera_settings_repeater', $rows, $camera_settings_id );
    
    }

    // Create Light Settings post.
    $light_settings_post = array(
        'post_title'  => 'Light Settings for Capture ' . $post_id,
        'post_type'   => 'light_settings',
        'post_status' => 'publish',
        'post_author' => $post->post_author,
    );
    $light_settings_id = wp_insert_post( $light_settings_post );
    if ( ! is_wp_error( $light_settings_id ) ) {
        // Link the light settings to the capture by updating meta.
        update_post_meta( $light_settings_id, 'parent_capture', $post_id );
        update_post_meta( $post_id, 'capture_light_settings', $light_settings_id );
    }
}
add_action( 'save_post_capture', 'create_light_and_camera_settings_for_capture', 10, 3 );




// Template functions for different post types
function esper_get_job_template($post) {
    ob_start();
    ?>
    <div class="job-template bg-black min-h-screen p-6">
        <div class="w-full space-y-6">

            <!-- Job Header -->
            <div class="bg-black/80 rounded-lg shadow-lg p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center justify-start mb-2 group relative">
                            <h2 class="text-lg font-semibold text-white job-title-display" data-job-id="<?php echo esc_attr($post->ID); ?>"><?php echo esc_html($post->post_title); ?></h2>
                            <input type="text" class="hidden absolute inset-0 bg-black text-white text-lg font-semibold px-2 py-1 rounded job-title-input" value="<?php echo esc_attr($post->post_title); ?>">
                            <button class="ml-2 text-gray-500 hover:text-yellow-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="material-icons text-sm">edit</span>
                            </button>
                        </div>
                        <div class="text-gray-500 text-sm mt-2">Created: <?php echo get_the_date('F j, Y', $post); ?></div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="border border-white border-opacity-10 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-white mb-0">Sessions</h3>
                    <?php 
                    $sessions = get_posts(array(
                        'post_type' => 'session',
                        'meta_key' => 'parent_job',
                        'meta_value' => $post->ID,
                        'posts_per_page' => -1
                    ));
                    $session_count = count($sessions);
                    ?>
                    <div class="text-3xl font-bold text-yellow-300"><?php echo $session_count; ?></div>
                    <p class="hidden text-gray-500">Sessions</p>
                </div>

                <div class="border border-white border-opacity-10 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-white mb-0">Captures</h3>
                    <?php 
                    $captures = 0;
                    foreach ($sessions as $session) {
                        $captures += count(get_posts(array(
                            'post_type' => 'capture',
                            'meta_key' => 'parent_session',
                            'meta_value' => $session->ID,
                            'posts_per_page' => -1
                        )));
                    }
                    ?>
                    <div class="text-3xl font-bold text-yellow-300"><?php echo $captures; ?></div>
                    <p class="hidden text-gray-500">Captures</p>
                </div>

                <div class="border border-white border-opacity-10 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-white mb-0">Takes</h3>
                    <?php 
                    $takes = 0;
                    foreach ($sessions as $session) {
                        $session_captures = get_posts(array(
                            'post_type' => 'capture',
                            'meta_key' => 'parent_session',
                            'meta_value' => $session->ID,
                            'posts_per_page' => -1
                        ));
                        foreach ($session_captures as $capture) {
                            $takes += count(get_posts(array(
                                'post_type' => 'take',
                                'meta_key' => 'parent_capture',
                                'meta_value' => $capture->ID,
                                'posts_per_page' => -1
                            )));
                        }
                    }
                    ?>
                    <div class="text-3xl font-bold text-yellow-300"><?php echo $takes; ?></div>
                    <p class="hidden text-gray-500">Takes</p>
                </div>
            </div>

            <!-- Job Details -->
            <div class="border border-white border-opacity-10 rounded-lg p-6">
                
                <!-- Tags -->
                <div class="space-y-4">
                    <div class="p-4 rounded">
                        <h3 class="text-sm font-medium text-gray-300 mb-2">Notes</h3>
                        <textarea id="jobNotes" class="w-full h-32 bg-black text-white border border-white border-opacity-10 rounded p-2 text-sm" placeholder="Add notes here..."><?php echo esc_textarea(get_post_meta($post->ID, 'job_notes', true)); ?></textarea>
                    </div>

                    <div class="bg-black/80 p-4 rounded">
                        <h3 class="text-sm font-medium text-gray-300 mb-2">Tags</h3>
                        <div class="flex flex-wrap gap-2 mb-2" id="tagContainer">
                            <?php
                            $tags = get_post_meta($post->ID, 'job_tags', true);
                            if (is_array($tags)) {
                                foreach ($tags as $tag) {
                                    echo '<span class="bg-esper-yellow text-black px-2 py-1 rounded text-sm flex items-center">' . 
                                         esc_html($tag) . 
                                         '<button class="ml-2 text-gray-500 hover:text-black remove-tag" data-tag="' . esc_attr($tag) . '">&times;</button>' .
                                         '</span>';
                                }
                            }
                            ?>
                        </div>
                        <div class="flex space-x-2">
                            <input type="text" id="newTag" class="flex-1 border border-white border-opacity-10 bg-black text-white rounded px-2 py-1 text-sm" placeholder="Add a tag">
                            <button id="addTag" class="bg-esper-yellow text-black px-3 py-1 rounded text-sm">
                                Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function esper_get_session_template($post) {
    ob_start();
    ?>
    <div class="session-template bg-black min-h-screen p-6">
        <div class="w-full space-y-6">
            <!-- Session Header -->
            <div class="bg-black/80 rounded-lg shadow-lg p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center justify-start mb-2 group relative">
                            <h2 class="text-lg font-semibold text-white title-display" data-session-id="<?php echo esc_attr($post->ID); ?>"><?php echo esc_html($post->post_title); ?></h2>
                            <input type="text" class="hidden absolute inset-0 bg-black text-white text-lg font-semibold px-2 py-1 rounded title-input" value="<?php echo esc_attr($post->post_title); ?>">
                            <button class="ml-2 text-gray-500 hover:text-yellow-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="material-icons text-sm">edit</span>
                            </button>
                        </div>
                        <div class="text-gray-500 text-sm mt-2">Created: <?php echo get_the_date('F j, Y', $post); ?></div>
                        <?php 
                        $parent_job_id = get_post_meta($post->ID, 'parent_job', true);
                        $parent_job = get_post($parent_job_id);
                        ?>
                        <div class="text-gray-500 text-sm mt-2">Parent Job: <?php echo esc_html($parent_job->post_title); ?></div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="border border-white border-opacity-10 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-white mb-0">Captures</h3>
                    <?php 
                    $captures = get_posts(array(
                        'post_type' => 'capture',
                        'meta_key' => 'parent_session',
                        'meta_value' => $post->ID,
                        'posts_per_page' => -1
                    ));
                    $capture_count = count($captures);
                    ?>
                    <div class="text-3xl font-bold text-yellow-300"><?php echo $capture_count; ?></div>
                    <p class="hidden text-gray-500">Captures</p>
                </div>
                <div class="border border-white border-opacity-10 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-white mb-0">Takes</h3>
                    <?php 
                    $takes = 0;
                    foreach ($captures as $capture) {
                        $takes += count(get_posts(array(
                            'post_type' => 'take',
                            'meta_key' => 'parent_capture',
                            'meta_value' => $capture->ID,
                            'posts_per_page' => -1
                        )));
                    }
                    ?>
                    <div class="text-3xl font-bold text-yellow-300"><?php echo $takes; ?></div>
                    <p class="hidden text-gray-500">Takes</p>
                </div>
            </div>

            <!-- Session Details -->
            <div class="border border-white border-opacity-10 rounded-lg p-6">
                <div class="space-y-4">
                    <!-- Notes -->
                    <div class="p-4 rounded">
                        <h3 class="text-sm font-medium text-gray-300 mb-2">Notes</h3>
                        <textarea id="sessionNotes" class="w-full h-32 bg-black text-white border border-white border-opacity-10 rounded p-2 text-sm" placeholder="Add notes here..."><?php echo esc_textarea(get_post_meta($post->ID, 'session_notes', true)); ?></textarea>
                    </div>

                    <!-- Tags -->
                    <div class="bg-black/80 p-4 rounded">
                        <h3 class="text-sm font-medium text-gray-300 mb-2">Tags</h3>
                        <div class="flex flex-wrap gap-2 mb-2" id="sessionTagContainer">
                            <?php
                            $tags = get_post_meta($post->ID, 'session_tags', true);
                            if (is_array($tags)) {
                                foreach ($tags as $tag) {
                                    echo '<span class="bg-esper-yellow text-black px-2 py-1 rounded text-sm flex items-center">' . 
                                         esc_html($tag) . 
                                         '<button class="ml-2 text-gray-500 hover:text-black remove-tag" data-tag="' . esc_attr($tag) . '">&times;</button>' .
                                         '</span>';
                                }
                            }
                            ?>
                        </div>
                        <div class="flex space-x-2">
                            <input type="text" id="newSessionTag" class="flex-1 border border-white border-opacity-10 bg-black text-white rounded px-2 py-1 text-sm" placeholder="Add a tag">
                            <button id="addSessionTag" class="bg-esper-yellow text-black px-3 py-1 rounded text-sm">
                                Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Helper Functions (make sure these are defined and available)
function get_iso_options() {
    return array(
        '100'   => '100',
        '200'   => '200',
        '400'   => '400',
        '800'   => '800',
        '1600'  => '1600',
        '3200'  => '3200',
        '6400'  => '6400',
    );
}

function get_aperture_options() {
    return array(
        'f/1.4' => 'f/1.4',
        'f/2.0' => 'f/2.0',
        'f/2.8' => 'f/2.8',
        'f/4.0' => 'f/4.0',
        'f/5.6' => 'f/5.6',
        'f/8'   => 'f/8',
        'f/11'  => 'f/11',
        'f/16'  => 'f/16',
    );
}

function get_white_balance_options() {
    return array(
        'auto'        => 'Auto',
        'daylight'    => 'Daylight',
        'cloudy'      => 'Cloudy',
        'tungsten'    => 'Tungsten',
        'fluorescent' => 'Fluorescent',
    );
}

function get_shutter_speed_options() {
    return array(
        '1/1000' => '1/1000',
        '1/500'  => '1/500',
        '1/250'  => '1/250',
        '1/125'  => '1/125',
        '1/60'   => '1/60',
        '1/30'   => '1/30',
        '1/15'   => '1/15',
    );
}

function get_file_type_options() {
    return array(
        'JPEG' => 'JPEG',
        'PNG'  => 'PNG',
        'RAW'  => 'RAW',
    );
}

function get_jpeg_quality_options() {
    return array(
        'ExFine'   => 'ExFine',
        'Fine'     => 'Fine',
        'Standard' => 'Standard',
    );
}

function get_drive_mode_options() {
    return array(
        'Single' => 'Single',
        'Burst'  => 'Burst',
    );
}

function get_focus_mode_options() {
    return array(
        'MF' => 'MF',
        'AF' => 'AF',
    );
}

// Populate ISO options
add_filter('acf/load_field/name=iso', function($field) {
    $field['choices'] = get_iso_options();
    return $field;
});

// Populate Aperture options
add_filter('acf/load_field/name=aperture', function($field) {
    $field['choices'] = get_aperture_options();
    return $field;
});

// Populate White Balance options
add_filter('acf/load_field/name=white_balance', function($field) {
    $field['choices'] = get_white_balance_options();
    return $field;
});

// Populate Shutter Speed options
add_filter('acf/load_field/name=shutter_speed', function($field) {
    $field['choices'] = get_shutter_speed_options();
    return $field;
});

// Populate File Type options
add_filter('acf/load_field/name=file_type', function($field) {
    $field['choices'] = get_file_type_options();
    return $field;
});

// Populate JPEG Quality options
add_filter('acf/load_field/name=jpeg_quality', function($field) {
    $field['choices'] = get_jpeg_quality_options();
    return $field;
});

// Populate Drive Mode options
add_filter('acf/load_field/name=drive_mode', function($field) {
    $field['choices'] = get_drive_mode_options();
    return $field;
});

// Populate Focus Mode options
add_filter('acf/load_field/name=focus_mode', function($field) {
    $field['choices'] = get_focus_mode_options();
    return $field;
});



function esper_get_camera_settings_template($post) {
    ob_start();

    // Get the Camera Settings post ID from the parent capture.
    $camera_settings_id = get_post_meta($post->ID, 'capture_camera_settings', true);
    if ( ! $camera_settings_id ) {
        echo '<p>No camera settings found.</p>';
        return ob_get_clean();
    }

    // Get the saved repeater rows from ACF.
    $rows = get_field('camera_settings_repeater', $camera_settings_id);
    if ( ! is_array($rows) ) {
        $rows = array();
    }

    // Define the base input set (without table wrappers).
    $camera_settings_fields = array(
        'set_name'   => 'Camera Settings',
        'set_slug'   => 'camera_settings',
        'fieldSets'  => array(
            array(
                'field_name' => 'Camera Name',
                'field_slug' => 'camera_name',
                'type'       => 'text',
                'display'   => true,
                'value'      => '',
                'hide_label' => true,
                'placeholder'=> 'Enter camera name',
                'beforeHTML' => '<td class="border-r border-esper-yellow">',
                'afterHTML'  => '</td>',
            ),
            array(
                'field_name' => 'Serial Number',
                'field_slug' => 'serial_number',
                'type'       => 'text',
                'display'   => true,
                'value'      => '',
                'hide_label' => true,
                'placeholder'=> 'Enter serial number',
                'beforeHTML' => '<td class="border-r border-esper-yellow">',
                'afterHTML'  => '</td>',
            ),
            array(
                'field_name' => 'Camera Model',
                'field_slug' => 'camera_model',
                'type'       => 'text',
                'display'   => true,
                'value'      => '',
                'hide_label' => true,
                'placeholder'=> 'Enter camera model',
                'beforeHTML' => '<td class="border-r border-esper-yellow">',
                'afterHTML'  => '</td>',
            ),
            array(
                'field_name' => 'ISO',
                'field_slug' => 'iso',
                'type'       => 'select',
                'value'      => '',
                'display'   => true,
                'hide_label' => true,
                'options'    => get_iso_options(),
                'beforeHTML' => '<td class="border-r border-esper-yellow">',
                'afterHTML'  => '</td>',
            ),
            array(
                'field_name' => 'Aperture',
                'field_slug' => 'aperture',
                'type'       => 'select',
                'value'      => '',
                'display'   => true,
                'hide_label' => true,
                'options'    => get_aperture_options(),
                'beforeHTML' => '<td class="border-r border-esper-yellow">',
                'afterHTML'  => '</td>',
            ),
            array(
                'field_name' => 'White Balance',
                'field_slug' => 'white_balance',
                'type'       => 'select',
                'value'      => '',
                'display'   => true,
                'hide_label' => true,
                'options'    => get_white_balance_options(),
                'beforeHTML' => '<td class="border-r border-esper-yellow">',
                'afterHTML'  => '</td>',
            ),
            array(
                'field_name' => 'Colour Temp',
                'field_slug' => 'colour_temp',
                'type'       => 'range',
                'hide_label' => true,
                'value'      => '3000',
                'display'   => true,
                'attributes' => array(
                    'min'  => '1000',
                    'max'  => '5000',
                    'step' => '100',
                ),
                'beforeHTML' => '<td class="border-r border-esper-yellow">',
                'afterHTML'  => '</td>',
            ),
            array(
                'field_name' => 'Shutter Speed',
                'field_slug' => 'shutter_speed',
                'type'       => 'select',
                'value'      => '',
                'display'   => true,
                'hide_label' => true,
                'options'    => get_shutter_speed_options(),
                'beforeHTML' => '<td class="border-r border-esper-yellow">',
                'afterHTML'  => '</td>',
            ),
            array(
                'field_name' => 'File Type',
                'field_slug' => 'file_type',
                'type'       => 'select',
                'value'      => '',
                'display'   => true,
                'hide_label' => true,
                'options'    => get_file_type_options(),
                'beforeHTML' => '<td class="border-r border-esper-yellow">',
                'afterHTML'  => '</td>',
            ),
            array(
                'field_name' => 'JPEG Quality',
                'field_slug' => 'jpeg_quality',
                'type'       => 'select',
                'value'      => '',
                'display'   => true,
                'hide_label' => true,
                'options'    => get_jpeg_quality_options(),
                'beforeHTML' => '<td class="border-r border-esper-yellow">',
                'afterHTML'  => '</td>',
            ),
            array(
                'field_name' => 'Drive Mode',
                'field_slug' => 'drive_mode',
                'type'       => 'select',
                'value'      => '',
                'display'   => true,
                'hide_label' => true,
                'options'    => get_drive_mode_options(),
                'beforeHTML' => '<td class="border-r border-esper-yellow">',
                'afterHTML'  => '</td>',
            ),
            array(
                'field_name' => 'Focus Mode',
                'field_slug' => 'focus_mode',
                'type'       => 'select',
                'value'      => '',
                'display'   => true,
                'hide_label' => true,
                'options'    => get_focus_mode_options(),
                'beforeHTML' => '<td class="border-r border-esper-yellow">',
                'afterHTML'  => '</td>',
            ),
        )
    );
 echo  '<div class="capture-template bg-black min-h-screen p-6">';
    echo  '<div class="w-full space-y-6">';
    // Output the "Back" and "Save Settings" buttons above the table.
    echo '<div class="w-full flex justify-between flex-wrap mb-4">
            <div class="bg-esper-yellow cursor-pointer text-black px-6 py-3 rounded-lg font-semibold flex items-center" data-action="back">Back</div>
            <div class="bg-esper-yellow cursor-pointer text-black px-6 py-3 rounded-lg font-semibold flex items-center" data-id="'.$camera_settings_id.'" data-action="save_camera_settings">Save Settings</div>
          </div>';

    // Build the table header dynamically from the field names.
    $thead = '<table class="input-set-table w-full text-xs border border-esper-yellow" border="1" cellpadding="5" cellspacing="0">';
    $thead .= '<thead><tr class="bg-esper-yellow">';
    foreach ( $camera_settings_fields['fieldSets'] as $field ) {
        $thead .= '<th class="font-normal text-black text-left">' . esc_html( $field['field_name'] ) . '</th>';
    }
    $thead .= '</tr></thead><tbody>';
    
  
    // Output the header.
    echo $thead;

    // For each saved repeater row, update the base field definitions and render the row.
if ( ! empty( $rows ) ) {
    foreach ( $rows as $row ) {
        // Create a copy of the base input set.
        $fields_copy = $camera_settings_fields;
        // Override the overall beforeHTML/afterHTML for this row.
        $fields_copy['beforeHTML'] = '<tr class="border-b border-esper-yellow">';
        $fields_copy['afterHTML']  = '</tr>';

        // Loop through each field in the fieldSets and update its value.
        if ( isset( $fields_copy['fieldSets'] ) && is_array( $fields_copy['fieldSets'] ) ) {
            foreach ( $fields_copy['fieldSets'] as $key => $field ) {
                $slug = isset( $field['field_slug'] ) ? $field['field_slug'] : '';
                $fields_copy['fieldSets'][$key]['value'] = isset( $row[ $slug ] ) ? $row[ $slug ] : '';
                // If camera_name is empty and this field isn't camera_name, hide the field.
                if ( empty( $row['camera_name'] ) && $slug !== 'camera_name' ) {
                    // Ensure attributes array exists.
                    if ( ! isset( $fields_copy['fieldSets'][$key]['attributes'] ) || ! is_array( $fields_copy['fieldSets'][$key]['attributes'] ) ) {
                        $fields_copy['fieldSets'][$key]['attributes'] = array();
                    }
                    $fields_copy['fieldSets'][$key]['attributes']['style'] = 'display:none;';
                }
            }
        }

        // Render the row.
        render_input_sets( array( $fields_copy ) );
    }
} else {
    // If no rows exist, output a single empty row.
    $empty_set = $camera_settings_fields;
    $empty_set['beforeHTML'] = '<tr>';
    $empty_set['afterHTML']  = '</tr>';
    render_input_sets( array( $empty_set ) );
}


    // Close the table.
    echo '</tbody></table></div></div>';

    return ob_get_clean();
}



function esper_get_capture_template($post) {
    ob_start();
    ?>
    <div class="capture-template bg-black min-h-screen p-6">
        <div class="w-full space-y-6">
            <!-- Capture Header -->
            <div class="bg-black p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-2 group relative">
                            <h2 class="text-2xl font-bold text-white capture-title-display" data-capture-id="<?php echo esc_attr($post->ID); ?>"><?php echo esc_html($post->post_title); ?></h2>
                            <input type="text" class="hidden absolute inset-0 bg-black text-white text-2xl font-bold px-2 py-1 capture-title-input" value="<?php echo esc_attr($post->post_title); ?>">
                            <button class="ml-2 text-gray-400 hover:text-yellow-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="material-icons">edit</span>
                            </button>
                        </div>
                        <div class="text-gray-400 text-sm">
                            <?php 
                            $parent_session_id = get_post_meta($post->ID, 'parent_session', true);
                            $parent_session = get_post($parent_session_id);
                            ?>
                            <p>Session: <?php echo esc_html($parent_session->post_title); ?></p>
                            <p>Created: <?php echo get_the_date('F j, Y g:i a', $post); ?></p>

                            <p data-action="openScreen" data-id="<?php echo esc_attr($post->ID); ?>" data-type="capture" data-context="camera_settings">Add camera settings icon</p>
                            <p data-action="openScreen" data-id="<?php echo esc_attr($post->ID); ?>" data-type="capture" data-context="light_settings">Add Light settings icon</p>

                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button id="triggerTake" 
                                class="bg-esper-yellow text-black px-6 py-3 rounded-lg font-semibold flex items-center"
                                data-capture-id="<?php echo esc_attr($post->ID); ?>">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Trigger Take
                        </button>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div id="takeProgress" class="mt-4 opacity-0 transition-opacity duration-200">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm text-gray-400">Capturing Take...</span>
                        <span class="text-sm text-gray-400" id="progressPercentage">0%</span>
                    </div>
                    <div class="w-full h-2 bg-gray-600 rounded-full overflow-hidden">
                        <div id="takeProgressBar" class="h-full bg-esper-yellow transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <!-- Takes Gallery -->
            <div class="bg-black shadow-lg p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Takes</h3>
                <div id="takesGallery" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php 
                    $takes = get_posts(array(
                        'post_type' => 'take',
                        'meta_key' => 'parent_capture',
                        'meta_value' => $post->ID,
                        'posts_per_page' => -1,
                        'orderby' => 'date',
                        'order' => 'DESC'
                    ));

                    foreach ($takes as $take) {
                        $thumbnail = get_the_post_thumbnail_url($take->ID, 'medium');
                        if (!$thumbnail) {
                            $thumbnail = 'https://placehold.co/600x400';
                        }
                        ?>
                        <div class="take-card bg-black overflow-hidden cursor-pointer hover:bg-gray-900 transition-colors" 
                             data-take-id="<?php echo esc_attr($take->ID); ?>">
                            <img src="<?php echo esc_url($thumbnail); ?>" 
                                 alt="<?php echo esc_attr($take->post_title); ?>"
                                 class="w-full h-48 object-cover">
                            <div class="p-4">
                                <h4 class="text-white font-semibold"><?php echo esc_html($take->post_title); ?></h4>
                                <p class="text-gray-400 text-sm"><?php echo get_the_date('g:i a', $take); ?></p>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function esper_get_take_template($post) {
    ob_start();

    // Retrieve values from the post or meta data
    $takeTitle         = $post->post_title ? $post->post_title : "Take {$post->ID}";
    $firstThumbnailUrl = 'https://placehold.co/1920x1080/333333/FFFFFF/png?text=1';
    $createdDate       = get_the_date('F j, Y', $post) ? get_the_date('F j, Y', $post) : 'Just now';
    $resolution        = get_post_meta($post->ID, 'resolution', true) ?: '1920x1080';
    $size              = get_post_meta($post->ID, 'size', true) ?: '2.4 MB';
    $format            = get_post_meta($post->ID, 'format', true) ?: 'PNG';

    // Optionally, get the filmstrip thumbnails via a helper function.
    // If you don't have this function, you can replace it with your own markup.
    $filmstripThumbnails = function_exists('store_take_generate_filmstrip_thumbnails') 
        ? store_take_generate_filmstrip_thumbnails() 
        : '<!-- Filmstrip thumbnails placeholder -->';
    ?>
    <div class="take-review flex flex-col bg-black" style="height: calc(100vh - 40px);">
        <!-- Main container with resizable panes -->
        <div class="flex-1 flex" id="takePanesContainer">
            <!-- Main image pane -->
            <div class="flex-1 relative bg-black flex items-center justify-center overflow-hidden" id="mainImagePane">
                <img src="<?php echo esc_url($firstThumbnailUrl); ?>" 
                     alt="Main Image"
                     class="w-full h-full object-contain">
            </div>
            
            <!-- Vertical resize handle -->
            <div class="w-1 bg-white bg-opacity-10 hover:bg-opacity-100 cursor-col-resize" id="verticalResizeHandle"></div>
            
            <!-- Right sidebar -->
            <div class="w-64 bg-black/80 p-4" id="rightSidebarPane">
                <div class="flex items-center justify-between mb-4 group relative">
                    <h3 class="text-lg font-semibold text-white take-title-display" data-take-id="<?php echo esc_attr($post->ID); ?>">
                        <?php echo esc_html($takeTitle); ?>
                    </h3>
                    <input type="text" class="hidden absolute inset-0 bg-black text-white text-lg font-semibold px-2 py-1 rounded take-title-input" value="<?php echo esc_attr($takeTitle); ?>">
                    <button class="ml-2 text-gray-400 hover:text-esper-yellow opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="material-icons text-sm">edit</span>
                    </button>
                </div>
                <div class="space-y-4">
                    <div class="bg-black/60 p-3 rounded">
                        <h4 class="text-sm font-medium text-gray-300 mb-2">Details</h4>
                        <p class="text-gray-400 text-sm">Created: <?php echo esc_html($createdDate); ?></p>
                        <p class="text-gray-400 text-sm">Status: Active</p>
                    </div>
                    <div class="bg-black/60 p-3 rounded">
                        <h4 class="text-sm font-medium text-gray-300 mb-2">Metadata</h4>
                        <p class="text-gray-400 text-sm">Resolution: <?php echo esc_html($resolution); ?></p>
                        <p class="text-gray-400 text-sm">Size: <?php echo esc_html($size); ?></p>
                        <p class="text-gray-400 text-sm">Format: <?php echo esc_html($format); ?></p>
                    </div>
                    <div class="bg-black/60 p-3 rounded">
                        <h4 class="text-sm font-medium text-gray-300 mb-2">Camera Settings</h4>
                        <p class="text-gray-400 text-sm">Shutter: 1/125</p>
                        <p class="text-gray-400 text-sm">Aperture: f/2.8</p>
                        <p class="text-gray-400 text-sm">ISO: 100</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Horizontal resize handle -->
        <div class="h-1 bg-white bg-opacity-10 hover:bg-opacity-100 cursor-row-resize" id="horizontalResizeHandle"></div>
        
        <!-- Bottom thumbnails filmstrip -->
        <div class="h-32 bg-black/80" id="thumbnailsPane">
            <div class="h-full flex flex-col">
                <!-- Filmstrip toolbar -->
                <div class="bg-black/90 px-4 py-1 flex items-center justify-between border-b border-black/60">
                    <span class="text-gray-400 text-sm">12 images</span>
                </div>
                <!-- Filmstrip content with custom scrollbar -->
                <div class="flex-1 overflow-x-auto filmstrip-scroll">
                    <style>
                        .filmstrip-scroll::-webkit-scrollbar {
                            height: 6px;
                        }
                        .filmstrip-scroll::-webkit-scrollbar-track {
                            background: #000000;
                        }
                        .filmstrip-scroll::-webkit-scrollbar-thumb {
                            background: #333333;
                            border-radius: 3px;
                        }
                        .filmstrip-scroll::-webkit-scrollbar-thumb:hover {
                            background: #fcd34d;
                        }
                    </style>
                    <div class="flex h-full p-2 space-x-2">
                        <?php echo $filmstripThumbnails; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function store_take_generate_filmstrip_thumbnails() {
    $colors = array('333333', '444444', '555555', '666666', '777777', '888888');
    $thumbnails = '';
    $num_colors = count($colors);
    
    // Generate 12 placeholder thumbnails with proper aspect ratio
    for ($i = 1; $i <= 12; $i++) {
        $index = $i % $num_colors;
        $color = $colors[$index];
        $extra_class = ($i === 1) ? 'ring-2 ring-esper-yellow' : '';
        
        $thumbnails .= '
            <div class="flex-none group">
                <div class="h-full bg-black/60 overflow-hidden relative cursor-pointer hover:ring-2 hover:ring-esper-yellow transition-all duration-200 ' . $extra_class . '">
                    <img src="https://placehold.co/1920x1080/' . esc_attr($color) . '/FFFFFF/png?text=' . esc_attr($i) . '" 
                         alt="Thumbnail ' . esc_attr($i) . '"
                         class="w-full h-full object-cover"
                         loading="lazy">
                    <div class="absolute bottom-0 left-0 right-0 bg-black/80 text-white text-xs py-1 px-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        ' . esc_html($i) . '
                    </div>
                </div>
            </div>';
    }
    
    return $thumbnails;
}


// Update the get_content AJAX handler to use templates
function esper_get_content() {
    // Verify nonce
    if (!check_ajax_referer('esper_ajax_nonce', 'nonce', false)) {
        wp_send_json_error('Invalid security token');
        return;
    }
    
    $post_id = intval($_POST['post_id']);
    $post_type = sanitize_text_field($_POST['post_type']);
    
    if (!in_array($post_type, array('job', 'session', 'capture', 'take'))) {
        wp_send_json_error('Invalid post type: ' . $post_type);
        return;
    }
    
    $post = get_post($post_id);
    $current_user_id = get_current_user_id();
    
    if (!$post) {
        wp_send_json_error('Post not found with ID: ' . $post_id);
        return;
    }
    
    if ($post->post_author != $current_user_id) {
        wp_send_json_error('Access denied. Post author (' . $post->post_author . ') does not match current user (' . $current_user_id . ')');
        return;
    }

    // Retrieve and decode the navigation array sent from the client
    $context = array();
    if (!empty($_POST['context'])) {
        $context = json_decode(wp_unslash($_POST['context']), true);
    }

    // For other types, return the template
    $content = '';
    switch ($post_type) {
        case 'job':
            $content = esper_get_job_template($post);
            break;
        case 'session':
            $content = esper_get_session_template($post);
            break;
        case 'capture':

            switch( $context) {
                case 'camera_settings':
                    $content = esper_get_camera_settings_template($post);


                break;
                case 'light_settings':
                    $content = 'light settings';
                break;
                default:
                    $content = esper_get_capture_template($post);
                break;
            }
            
            break;
        case 'take':
            $content = esper_get_take_template($post);
            break;
    }
    
    wp_send_json_success(array(
        'content' => $content,
        '$context' => $context
    ));
    
}
add_action('wp_ajax_esper_get_content', 'esper_get_content');

// Add this new function after the other AJAX handlers
function esper_get_hierarchy() {
    // Verify nonce
    check_ajax_referer('esper_ajax_nonce', 'nonce');
    
    // Get job ID if specified
    $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : null;
    
    // Get jobs query args
    $jobs_args = array(
        'post_type' => 'job',
        'posts_per_page' => -1,
        'author' => get_current_user_id(),
        'orderby' => 'title',
        'order' => 'ASC'
    );
    
    // If job_id is specified, only get that job
    if ($job_id) {
        $jobs_args['p'] = $job_id;
    }
    
    $jobs = get_posts($jobs_args);
    $hierarchy = array();
    
    foreach ($jobs as $job) {
        // Get sessions for this job
        $sessions = get_posts(array(
            'post_type' => 'session',
            'posts_per_page' => -1,
            'author' => get_current_user_id(),
            'meta_query' => array(
                array(
                    'key' => 'parent_job',
                    'value' => $job->ID
                )
            ),
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        $session_data = array();
        foreach ($sessions as $session) {
            // Get captures for this session
            $captures = get_posts(array(
                'post_type' => 'capture',
                'posts_per_page' => -1,
                'author' => get_current_user_id(),
                'meta_query' => array(
                    array(
                        'key' => 'parent_session',
                        'value' => $session->ID
                    )
                ),
                'orderby' => 'title',
                'order' => 'ASC'
            ));

            $capture_data = array();
            foreach ($captures as $capture) {
                // Get takes for this capture
                $takes = get_posts(array(
                    'post_type' => 'take',
                    'posts_per_page' => -1,
                    'author' => get_current_user_id(),
                    'meta_query' => array(
                        array(
                            'key' => 'parent_capture',
                            'value' => $capture->ID
                        )
                    ),
                    'orderby' => 'title',
                    'order' => 'ASC'
                ));

                $take_data = array();
                foreach ($takes as $take) {
                    $take_data[] = array(
                        'id' => $take->ID,
                        'title' => $take->post_title,
                        'type' => 'take'
                    );
                }

                $capture_data[] = array(
                    'id' => $capture->ID,
                    'title' => $capture->post_title,
                    'type' => 'capture',
                    'children' => $take_data
                );
            }

            $session_data[] = array(
                'id' => $session->ID,
                'title' => $session->post_title,
                'type' => 'session',
                'children' => $capture_data
            );
        }

        $hierarchy[] = array(
            'id' => $job->ID,
            'title' => $job->post_title,
            'type' => 'job',
            'children' => $session_data
        );
    }
    
    wp_send_json_success($hierarchy);
}
add_action('wp_ajax_esper_get_hierarchy', 'esper_get_hierarchy');

// Enqueue scripts and styles
function esper_lightcage_scripts() {
    // Enqueue main styles
    wp_enqueue_style(
        'esper-lightcage-styles', 
        get_template_directory_uri() . '/dist/css/style.css',
        array(),
        wp_get_theme()->get('Version')
    );

    // Enqueue Google Material Icons
    wp_enqueue_style(
        'google-material-icons',
        'https://fonts.googleapis.com/icon?family=Material+Icons',
        array(),
        null
    );

    // Enqueue tooltip styles
    wp_enqueue_style(
        'esper-lightcage-tooltips',
        get_template_directory_uri() . '/src/css/tooltips.css',
        array(),
        wp_get_theme()->get('Version')
    );

    // Enqueue jQuery from WordPress core
    wp_enqueue_script('jquery');
    
    // Enqueue main script
    wp_enqueue_script(
        'esper-lightcage-scripts',
        get_template_directory_uri() . '/dist/js/main.bundle.js',
        array('jquery'),
        wp_get_theme()->get('Version'),
        true
    );

    // Add AJAX URL and nonce
    wp_localize_script('esper-lightcage-scripts', 'esperApi', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('esper_ajax_nonce'),
        'currentUser' => get_current_user_id()
    ));
    
    // Add WordPress admin URL
    wp_localize_script('esper-lightcage-scripts', 'wpAdmin', array(
        'url' => admin_url()
    ));
}
add_action('wp_enqueue_scripts', 'esper_lightcage_scripts');

// Add new AJAX handler for updating job details
function esper_update_job() {
    check_ajax_referer('esper_ajax_nonce', 'nonce');
    
    $job_id = intval($_POST['job_id']);
    $job = get_post($job_id);
    
    if (!$job || $job->post_author != get_current_user_id()) {
        wp_send_json_error('Access denied');
        return;
    }
    
    $updates = array();
    
    // Update title if provided
    if (isset($_POST['title'])) {
        $updates['post_title'] = sanitize_text_field($_POST['title']);
    }
    
    // Update notes if provided
    if (isset($_POST['notes'])) {
        update_post_meta($job_id, 'job_notes', sanitize_textarea_field($_POST['notes']));
    }
    
    // Update tags if provided
    if (isset($_POST['tags'])) {
        $tags = array_map('sanitize_text_field', $_POST['tags']);
        update_post_meta($job_id, 'job_tags', $tags);
    }
    
    // If we have post updates, apply them
    if (!empty($updates)) {
        $updates['ID'] = $job_id;
        wp_update_post($updates);
    }
    
    wp_send_json_success();
}
add_action('wp_ajax_esper_update_job', 'esper_update_job');

// Add AJAX handlers for take operations
add_action('wp_ajax_esper_update_take', 'esper_handle_update_take');
add_action('wp_ajax_nopriv_esper_update_take', 'esper_handle_update_take');

function esper_handle_update_take() {
    // Verify nonce
    if (!check_ajax_referer('esper_ajax_nonce', 'nonce', false)) {
        wp_send_json_error('Invalid security token');
        wp_die();
    }

    // Get and validate parameters
    $take_id = isset($_POST['take_id']) ? intval($_POST['take_id']) : 0;
    $new_title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    
    if (!$take_id || empty($new_title)) {
        wp_send_json_error('Missing required parameters');
        wp_die();
    }

    // Verify post exists and is a take
    $post = get_post($take_id);
    if (!$post || $post->post_type !== 'take') {
        wp_send_json_error('Invalid take ID');
        wp_die();
    }

    // Update the take title
    $update_result = wp_update_post([
        'ID' => $take_id,
        'post_title' => $new_title
    ], true);

    if (is_wp_error($update_result)) {
        wp_send_json_error($update_result->get_error_message());
    } else {
        wp_send_json_success([
            'message' => 'Take name updated successfully',
            'title' => $new_title
        ]);
    }

    wp_die();
}

// Add AJAX handler for updating capture names
function esper_handle_update_capture() {
    // Verify nonce
    check_ajax_referer('esper_ajax_nonce', 'nonce');
    
    // Get and validate parameters
    $capture_id = isset($_POST['capture_id']) ? intval($_POST['capture_id']) : 0;
    $new_title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    
    if (!$capture_id || empty($new_title)) {
        wp_send_json_error('Missing required parameters');
        return;
    }
    
    // Verify post exists and is a capture
    $post = get_post($capture_id);
    if (!$post || $post->post_type !== 'capture' || $post->post_author != get_current_user_id()) {
        wp_send_json_error('Invalid capture or permission denied');
        return;
    }
    
    // Update the capture title
    $updated = wp_update_post(array(
        'ID' => $capture_id,
        'post_title' => $new_title
    ));
    
    if ($updated) {
        wp_send_json_success(array(
            'message' => 'Capture name updated successfully',
            'title' => $new_title
        ));
    } else {
        wp_send_json_error('Failed to update capture name');
    }
}
add_action('wp_ajax_esper_update_capture', 'esper_handle_update_capture');

// Add custom scrollbar styles
function esper_add_scrollbar_styles() {
    ?>
    <style>
    /* Custom scrollbar styles for the sidebar */
    #sidebar::-webkit-scrollbar {
        width: 8px;
    }
    #sidebar::-webkit-scrollbar-track {
        background: #000000;
    }
    #sidebar::-webkit-scrollbar-thumb {
        background: #333333;
        border-radius: 4px;
    }
    #sidebar::-webkit-scrollbar-thumb:hover {
        background: #fcd34d;
    }
    /* For Firefox */
    #sidebar {
        scrollbar-width: thin;
        scrollbar-color: #333333 #000000;
    }
    </style>
    <?php
}
add_action('wp_head', 'esper_add_scrollbar_styles');

// Handle session update
add_action('wp_ajax_esper_update_session', 'esper_update_session');
function esper_update_session() {
    check_ajax_referer('esper_ajax_nonce', 'nonce');

    $session_id = intval($_POST['session_id']);
    $title = sanitize_text_field($_POST['title']);

    // Update session logic here
    // For example, update the session title in the database
    // $result = update_session_title($session_id, $title);

    if (/* $result */ true) { // Replace with actual condition
        wp_send_json_success(array('message' => 'Session updated successfully'));
    } else {
        wp_send_json_error(array('message' => 'Failed to update session'));
    }
}

// Handle session notes update
add_action('wp_ajax_esper_update_session_notes', 'esper_update_session_notes');
function esper_update_session_notes() {
    check_ajax_referer('esper_ajax_nonce', 'nonce');

    $session_id = intval($_POST['session_id']);
    $notes = sanitize_textarea_field($_POST['notes']);

    // Update session notes logic here
    // For example, update the session notes in the database
    // $result = update_session_notes($session_id, $notes);

    if (/* $result */ true) { // Replace with actual condition
        wp_send_json_success(array('message' => 'Session notes updated successfully'));
    } else {
        wp_send_json_error(array('message' => 'Failed to update session notes'));
    }
}

/**
 * Renders input sets from an associative array.
 *
 * Each set can include:
 *  - beforeHTML, afterHTML: wrapper markup for the whole set.
 *  - set_name: an optional title.
 *  - fieldSets: an array of field definitions. Each field can include:
 *      - field_name: Label text.
 *      - field_slug: Used for the name and id attributes.
 *      - type: Input type (text, number, select, checkbox, radio, range, etc.).
 *      - value: Default value.
 *      - placeholder: Optional placeholder attribute.
 *      - hide_label: If set to true, the label will not be output.
 *      - options: For select or radio types.
 *      - attributes: An associative array of additional attributes.
 *      - beforeHTML, afterHTML: Wrapper markup for the field.
 *
 * @param array $sets Array of input set definitions.
 */
function render_input_sets( array $sets ) {
    foreach ( $sets as $set ) {

                // Get top-level styling classes if provided.
                $global_label_class = isset( $set['label_class'] ) ? $set['label_class'] : '';
                $global_input_class = isset( $set['input_class'] ) ? $set['input_class'] : 'bg-black border p-2 border-white border-opacity-25 text-white';

                
        // Output set wrapper (beforeHTML).
        if ( ! empty( $set['beforeHTML'] ) ) {
            echo $set['beforeHTML'];
        }

        // Optional set title.
        if ( ! empty( $set['set_name'] ) ) {
            //echo '<h3>' . esc_html( $set['set_name'] ) . '</h3>';
        }

        // Loop through each field in the set.
        if ( ! empty( $set['fieldSets'] ) && is_array( $set['fieldSets'] ) ) {
            foreach ( $set['fieldSets'] as $field ) {

                // Get field-specific classes; fall back to global ones.
                $label_class = isset( $field['label_class'] ) ? $field['label_class'] : $global_label_class;
                $input_class = isset( $field['input_class'] ) ? $field['input_class'] : $global_input_class;
                
                // Output any field beforeHTML.
                if ( ! empty( $field['beforeHTML'] ) ) {
                    echo $field['beforeHTML'];
                }

                // Check if we should hide the label.
                $hide_label = isset( $field['hide_label'] ) ? $field['hide_label'] : false;
                // Retrieve placeholder if provided.
                $placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

                // Build additional attribute string.
                $attr_string = '';
                if ( ! empty( $placeholder ) ) {
                    $attr_string .= ' placeholder="' . esc_attr( $placeholder ) . '"';
                }
                if ( isset( $field['display']) && $field['display'] == false ) {
                    $attr_string .= ' style="display:none;" ' ;
                }
                if ( isset( $field['attributes'] ) && is_array( $field['attributes'] ) ) {
                    foreach ( $field['attributes'] as $attr_key => $attr_val ) {
                        $attr_string .= ' ' . esc_attr( $attr_key ) . '="' . esc_attr( $attr_val ) . '"';
                    }
                }

                // Render the label unless hidden.
                if ( ! $hide_label ) {
                    echo '<label for="' . esc_attr( $field['field_slug'] ) . '" class="' . esc_attr( $label_class ) . '">' . esc_html( $field_name ) . '</label>';
                }

                // Render the input based on type.
                $type       = isset( $field['type'] ) ? $field['type'] : 'text';
                $field_slug = isset( $field['field_slug'] ) ? $field['field_slug'] : '';
                $value      = isset( $field['value'] ) ? $field['value'] : '';

                switch ( $type ) {
                    case 'text':
                    case 'number':
                        echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $field_slug ) . '" id="' . esc_attr( $field_slug ) . '" value="' . esc_attr( $value ) . '" class="' . esc_attr( $input_class ) . '"' . $attr_string . '>';
                        break;

                    case 'select':
                        echo '<select name="' . esc_attr( $field_slug ) . '" id="' . esc_attr( $field_slug ) . '" class="' . esc_attr( $input_class ) . '"' . $attr_string . '>';
                        if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
                            foreach ( $field['options'] as $option_value => $option_label ) {
                                echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( $option_value, $value, false ) . '>' . esc_html( $option_label ) . '</option>';
                            }
                        }
                        echo '</select>';
                        break;

                    case 'checkbox':
                        echo '<label for="' . esc_attr( $field_slug ) . '">';
                        echo '<input type="checkbox" name="' . esc_attr( $field_slug ) . '" id="' . esc_attr( $field_slug ) . '" value="1" ' . checked( 1, $value, false ) . $attr_string . '>';
                        echo esc_html( $field['field_name'] );
                        echo '</label>';
                        break;

                    case 'radio':
                        echo '<span>' . esc_html( $field['field_name'] ) . '</span>';
                        if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
                            foreach ( $field['options'] as $option_value => $option_label ) {
                                echo '<label>';
                                echo '<input type="radio" name="' . esc_attr( $field_slug ) . '" value="' . esc_attr( $option_value ) . '" ' . checked( $option_value, $value, false ) . $attr_string . '>';
                                echo esc_html( $option_label );
                                echo '</label>';
                            }
                        }
                        break;

                    case 'range':
                        echo '<input type="range" name="' . esc_attr( $field_slug ) . '" id="' . esc_attr( $field_slug ) . '" value="' . esc_attr( $value ) . '"' . $attr_string . '>';
                        break;

                    default:
                        echo '<input type="text" name="' . esc_attr( $field_slug ) . '" id="' . esc_attr( $field_slug ) . '" value="' . esc_attr( $value ) . '"' . $attr_string . '>';
                        break;
                }

                // Output any field afterHTML.
                if ( ! empty( $field['afterHTML'] ) ) {
                    echo $field['afterHTML'];
                }
            }
        }

        // Output set wrapper closing markup.
        if ( ! empty( $set['afterHTML'] ) ) {
            echo $set['afterHTML'];
        }
    }
}

function update_camera_settings_repeater_ajax() {
    check_ajax_referer('esper_ajax_nonce', 'nonce');

    $post_id = intval($_POST['post_id']);
    if (!$post_id) {
        wp_send_json_error('Invalid post ID');
    }

    $rows_json = wp_unslash($_POST['rows']);
    $rows = json_decode($rows_json, true);
    if (!is_array($rows)) {
        wp_send_json_error('Invalid rows data');
    }

    // Update the ACF repeater field named "camera_settings_repeater" for the given post.
    if (update_field('camera_settings_repeater', $rows, $post_id)) {
        wp_send_json_success('Repeater updated');
    } else {
        wp_send_json_error('Failed to update repeater');
    }
}
add_action('wp_ajax_update_camera_settings_repeater', 'update_camera_settings_repeater_ajax');

// Register Custom Post Type: Export
function register_export_post_type() {
    $labels = array(
        'name'                  => _x('Exports', 'Post Type General Name', 'text_domain'),
        'singular_name'         => _x('Export', 'Post Type Singular Name', 'text_domain'),
        'menu_name'             => __('Exports', 'text_domain'),
        'name_admin_bar'        => __('Export', 'text_domain'),
        'archives'              => __('Export Archives', 'text_domain'),
        'attributes'            => __('Export Attributes', 'text_domain'),
        'parent_item_colon'     => __('Parent Export:', 'text_domain'),
        'all_items'             => __('All Exports', 'text_domain'),
        'add_new_item'          => __('Add New Export', 'text_domain'),
        'add_new'               => __('Add New', 'text_domain'),
        'new_item'              => __('New Export', 'text_domain'),
        'edit_item'             => __('Edit Export', 'text_domain'),
        'update_item'           => __('Update Export', 'text_domain'),
        'view_item'             => __('View Export', 'text_domain'),
        'view_items'            => __('View Exports', 'text_domain'),
        'search_items'          => __('Search Export', 'text_domain'),
        'not_found'             => __('Not found', 'text_domain'),
        'not_found_in_trash'    => __('Not found in Trash', 'text_domain'),
        'featured_image'        => __('Featured Image', 'text_domain'),
        'set_featured_image'    => __('Set featured image', 'text_domain'),
        'remove_featured_image' => __('Remove featured image', 'text_domain'),
        'use_featured_image'    => __('Use as featured image', 'text_domain'),
        'insert_into_item'      => __('Insert into export', 'text_domain'),
        'uploaded_to_this_item' => __('Uploaded to this export', 'text_domain'),
        'items_list'            => __('Exports list', 'text_domain'),
        'items_list_navigation' => __('Exports list navigation', 'text_domain'),
        'filter_items_list'     => __('Filter exports list', 'text_domain'),
    );
    $args = array(
        'label'                 => __('Export', 'text_domain'),
        'description'           => __('Custom post type for exports', 'text_domain'),
        'labels'                => $labels,
        'supports'              => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'taxonomies'            => array('category', 'post_tag'),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
    );
    register_post_type('export', $args);
}

add_action('init', 'register_export_post_type', 0);
