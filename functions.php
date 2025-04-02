<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once get_template_directory() . '/functions/custom-post-types.php';
require_once get_template_directory() . '/classes/export-class.php';
require_once get_template_directory() . '/classes/camera-settings-class.php';

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

            // If the new item is a take, copy the repeater field from the parent capture.
            if ($type === 'take') {
                // We assume the parent_type is 'capture'.
                $capture_id = $parent_id;
                // Get the camera settings post linked to this capture.
                $cameraSettingsPostID = get_post_meta($capture_id, 'capture_camera_settings', true);
                if ($cameraSettingsPostID) {
                    // Retrieve the repeater field rows from the camera settings post.
                    $rows = get_field('camera_settings_repeater', $cameraSettingsPostID);
                    if ($rows) {
                        // Copy the repeater data to the new take.
                        update_field('camera_settings_repeater', $rows, $post_id);
                    }
                }

                // Create 12 take_image posts
                for ($i = 1; $i <= 12; $i++) {
                    $image_title = "Image {$i}";
                    $image_post = array(
                        'post_title'    => $image_title,
                        'post_status'   => 'publish',
                        'post_type'     => 'take_image',
                        'post_parent'   => $post_id,
                        'post_author'   => get_current_user_id()
                    );

                    $image_id = wp_insert_post($image_post);

                    if ($image_id) {
                        // Add the take_id as meta data
                        update_post_meta($image_id, 'take_id', $post_id);
                        // Add the image number as meta data
                        update_post_meta($image_id, 'image_number', $i);
                    }
                }
            }
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
                            <h2 class="text-lg font-semibold text-white title-display" data-type="job" data-id="<?php echo esc_attr($post->ID); ?>"><?php echo esc_html($post->post_title); ?></h2>
                            <input type="text" class="hidden absolute inset-0 bg-black text-white text-lg font-semibold px-2 py-1 rounded title-input" value="<?php echo esc_attr($post->post_title); ?>">
                            <button class="ml-2 text-gray-500 hover:text-esper-yellow opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="material-symbols-outlined text-sm">edit</span>
                            </button>
                        </div>
                        <div class="text-gray-500 text-sm mt-2">Created: <?php echo get_the_date('F j, Y', $post); ?></div>
                        <button id="addSession" class="mt-4 bg-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded">
                            Add Session
                        </button>
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
                    <div class="text-3xl font-bold text-esper-yellow"><?php echo $session_count; ?></div>
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
                    <div class="text-3xl font-bold text-esper-yellow"><?php echo $captures; ?></div>
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
                    <div class="text-3xl font-bold text-esper-yellow"><?php echo $takes; ?></div>
                    <p class="hidden text-gray-500">Takes</p>
                </div>
            </div>

            <!-- Job Details -->
            <div class="border border-white border-opacity-10 rounded-lg p-6">
                
                <!-- Tags -->
                <div class="space-y-4">
                    <div class="p-4 rounded">
                        <h3 class="text-sm font-medium text-gray-300 mb-2">Notes</h3>
                        <textarea id="notes" class="w-full h-32 bg-black text-white border border-white border-opacity-10 rounded p-2 text-sm" placeholder="Add notes here..."><?php echo esc_textarea(get_post_meta($post->ID, 'notes', true)); ?></textarea>
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
                            <h2 class="text-lg font-semibold text-white title-display" data-type="session" data-id="<?php echo esc_attr($post->ID); ?>"><?php echo esc_html($post->post_title); ?></h2>
                            <input type="text" class="hidden absolute inset-0 bg-black text-white text-lg font-semibold px-2 py-1 rounded title-input" value="<?php echo esc_attr($post->post_title); ?>">
                            <button class="ml-2 text-gray-500 hover:text-esper-yellow opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="material-symbols-outlined text-sm">edit</span>
                            </button>
                        </div>
                        <div class="text-gray-500 text-sm mt-2">Created: <?php echo get_the_date('F j, Y', $post); ?></div>
                        <?php 
                        $parent_job_id = get_post_meta($post->ID, 'parent_job', true);
                        $parent_job = get_post($parent_job_id);
                        ?>
                        <div class="text-gray-500 text-sm mt-2">Job: <?php echo esc_html($parent_job->post_title); ?></div>
                        <button id="addCapture" class="mt-4 bg-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded">
                            Add Capture
                        </button>
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
                    <div class="text-3xl font-bold text-esper-yellow"><?php echo $capture_count; ?></div>
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
                    <div class="text-3xl font-bold text-esper-yellow"><?php echo $takes; ?></div>
                    <p class="hidden text-gray-500">Takes</p>
                </div>
            </div>

            <!-- Session Details -->
            <div class="border border-white border-opacity-10 rounded-lg p-6">
                <div class="space-y-4">
                    <!-- Notes -->
                    <div class="p-4 rounded">
                        <h3 class="text-sm font-medium text-gray-300 mb-2">Notes</h3>
                        <textarea id="notes" class="w-full h-32 bg-black text-white border border-white border-opacity-10 rounded p-2 text-sm" placeholder="Add notes here..."><?php echo esc_textarea(get_post_meta($post->ID, 'notes', true)); ?></textarea>
                    </div>

                    <!-- Tags -->
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

    $cameraSettings = new CameraSettings( $post );
    echo $cameraSettings->renderSettings();

   
    return ob_get_clean();
}



function esper_get_capture_template($post) {
    ob_start();
    ?>
    <div class="capture-template bg-black min-h-screen p-6">
        <div class="w-full">
            <!-- Capture Header -->
            <div class="bg-black p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <div class="flex items-center mb-2 group relative">
                            <h2 class="text-2xl font-bold text-white title-display" data-type="capture" data-id="<?php echo esc_attr($post->ID); ?>"><?php echo esc_html($post->post_title); ?></h2>
                            <input type="text" class="hidden absolute inset-0 bg-black text-white text-2xl font-bold px-2 py-1 title-input" value="<?php echo esc_attr($post->post_title); ?>">
                            <button class="ml-2 text-gray-400 hover:text-esper-yellow opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="material-symbols-outlined">edit</span>
                            </button>
                        </div>
                        <div class="text-gray-400 text-sm">
                            <?php 
                            $parent_session_id = get_post_meta($post->ID, 'parent_session', true);
                            $parent_session = get_post($parent_session_id);
                            ?>
                            <p>Session: <?php echo esc_html($parent_session->post_title); ?></p>
                            <p>Created: <?php echo get_the_date('F j, Y g:i a', $post); ?></p>

                            <div class="flex flex-wrap mt-3">
                                <div class="flex items-center cursor-pointer mr-4 bg-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded" data-action="openScreen" data-id="<?php echo esc_attr($post->ID); ?>" data-type="capture" data-context="camera_settings"><span class="material-symbols-outlined w-6 h-6 mr-2 text-black flex-none">photo_camera</span> Advanced Camera Settings</div>
                                <div class="flex items-center cursor-pointer bg-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded" data-action="openScreen" data-id="<?php echo esc_attr($post->ID); ?>" data-type="capture" data-context="light_settings"><span class="material-symbols-outlined w-6 h-6 mr-2 text-black flex-none">light_mode</span> Advanced Light Settings</div>
                            </div>
                            

                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button id="triggerTake" 
                                class="bg-esper-yellow text-black px-6 py-3 rounded-lg font-semibold flex items-center"
                                data-capture-id="<?php echo esc_attr($post->ID); ?>">

                                <span class="material-symbols-outlined w-6 h-6 mr-2 text-black flex-none">camera</span>
                                
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

            <div class="bg-black p-6">
                <div class="w-full mb-4">
                    <h3>Current Camera Settings</h3>
                </div>
                <?php
                    $cameraSettings = new CameraSettings( $post );
                    //echo $cameraSettings->renderSettings();

                    $common = $cameraSettings->findCommonFields($cameraSettings->rows);

                    echo $cameraSettings->renderCommonFields( $common );
                ?>
            </div>

           <!-- Takes Gallery -->
<div class="bg-black shadow-lg p-6">
    <div class="mb-4">
        <h3 id="toggleTakes" class="text-lg font-semibold text-white cursor-pointer inline-flex items-center">
            Takes
            <span id="toggleTakesIcon" class="material-symbols-outlined transition-transform duration-300 ml-2">
                keyboard_arrow_down
            </span>
        </h3>
    </div>
    <?php 
        $takes = get_posts(array(
            'post_type'      => 'take',
            'meta_key'       => 'parent_capture',
            'meta_value'     => $post->ID,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC'
        ));
        // Only add grid classes if takes exist.
        $galleryClasses = !empty($takes) ? "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4" : "";
    ?>
    <div id="takesGallery" class="<?php echo esc_attr($galleryClasses); ?>">
        <?php 
        if ( empty( $takes ) ) {
            echo '<p class="text-white">No takes found, please trigger some.</p>';
        } else {
            foreach ( $takes as $take ) {
                $thumbnail = get_the_post_thumbnail_url( $take->ID, 'medium' );
                if ( ! $thumbnail ) {
                    $thumbnail = 'https://placehold.co/600x400';
                }
                ?>
                <div class="take-card bg-black overflow-hidden cursor-pointer border border-white border-opacity-10 hover:bg-white hover:bg-opacity-10 transition" 
                     data-take-id="<?php echo esc_attr( $take->ID ); ?>">
                    <img src="<?php echo esc_url( $thumbnail ); ?>" 
                         alt="<?php echo esc_attr( $take->post_title ); ?>"
                         class="w-full aspect-w-16 aspect-h-9 object-cover">
                    <div class="p-4">
                        <h4 class="text-white font-semibold"><?php echo esc_html( $take->post_title ); ?></h4>
                        <p class="text-gray-400 text-sm">
                            <?php echo get_the_date( 'F j, Y g:i a', $take ); ?>
                        </p>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>
</div>

<style>
/* Class to rotate the icon */
.rotate-180 {
    transform: rotate(180deg);
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#toggleTakes').on('click', function() {
        $('#takesGallery').slideToggle(300);
        $('#toggleTakesIcon').toggleClass('rotate-180');
    });
});
</script>


        </div>
    </div>
    <?php
    return ob_get_clean();
}

function esper_get_take_template($post) {
    ob_start();

    $firstThumbnailUrl = 'https://placehold.co/1920x1080/333333/FFFFFF/png?text=1';
    $createdDate = get_the_date('F j, Y', $post) ? get_the_date('F j, Y', $post) : 'Just now';
    $resolution = get_post_meta($post->ID, 'resolution', true) ?: '1920x1080';
    $size = get_post_meta($post->ID, 'size', true) ?: '2.4 MB';
    $format = get_post_meta($post->ID, 'format', true) ?: 'PNG';

    // Get the filmstrip thumbnails by passing the take ID
    $filmstripData = store_take_generate_filmstrip_thumbnails($post->ID);
    $filmstripThumbnails = $filmstripData['thumbnails'];
    $imageCount = $filmstripData['count'];

    $parent_capture = get_field('parent_capture', $post->ID);
    ?>
    <div class="take-review flex flex-col bg-black" style="height: calc(100vh - 40px);" data-take-id="<?php echo esc_attr($post->ID); ?>">
        
           
    
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
            <div class="w-64 bg-black/80 p-4 overflow-auto scrollbar" id="rightSidebarPane">
                <div class="flex items-center justify-between mb-4 group relative">
                    <h3 class="text-lg font-semibold text-white title-display" data-type="take" data-id="<?php echo esc_attr($post->ID); ?>"><?php echo esc_attr($post->post_title); ?></h3>
                    <input type="text" class="hidden absolute inset-0 bg-black text-white text-lg font-semibold px-2 py-1 rounded title-input" value="<?php echo esc_attr($post->post_title); ?>">
                    <button class="ml-2 text-gray-400 hover:text-esper-yellow opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="material-symbols-outlined text-sm">edit</span>
                    </button>
                </div>
               
                <div class="space-y-4"> 
                    
                    <div class="w-full relative bg-black flex items-center justify-start">
                        <div class="flex items-center cursor-pointer bg-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded" data-action="openScreen" data-id="<?php echo esc_attr($parent_capture); ?>" data-type="capture" data-context="retake_<?php echo esc_attr($post->ID); ?>"><span class="material-symbols-outlined w-6 h-6 mr-2 text-black flex-none hidden">photo_camera</span> Retake</div>
                    </div>
                    <div class="bg-black/60">
                        <h4 class="text-sm font-medium text-gray-300 mb-2">Details</h4>
                        <p class="text-gray-400 text-sm">Created: <?php echo esc_html($createdDate); ?></p>
                        <p class="text-gray-400 text-sm">Status: Active</p>
                    </div>
                    <div class="bg-black/60">
                        <h4 class="text-sm font-medium text-gray-300 mb-2">Metadata</h4>
                        <p class="text-gray-400 text-sm">Resolution: <?php echo esc_html($resolution); ?></p>
                        <p class="text-gray-400 text-sm">Size: <?php echo esc_html($size); ?></p>
                        <p class="text-gray-400 text-sm">Format: <?php echo esc_html($format); ?></p>
                    </div>
                    <div class="bg-black/60">
                        <h4 class="text-sm font-medium text-gray-300 mb-2">Camera Settings</h4>
                        <p class="text-gray-400 text-sm">Shutter: 1/125</p>
                        <p class="text-gray-400 text-sm">Aperture: f/2.8</p>
                        <p class="text-gray-400 text-sm">ISO: 100</p>
                    </div>

                    <div class="bg-black/60">
                        <h4 class="text-sm font-medium text-gray-300 mb-2">Export Settings</h4>
                        <p class="text-gray-400 text-sm">Include sets</p>
                        <p class="text-gray-400 text-sm">Ability to exclude images</p>
                        <p class="text-gray-400 text-sm">Re-order images?</p>
                        <label class="hidden" for="include_export">
                            <input type="checkbox" id="include_export" name="include_export" value="1" checked>
                            Include in Export
                        </label>
                    </div>

                    <div class="w-full relative bg-black flex items-center justify-start">
                        <div class="flex items-center cursor-pointer mr-4 bg-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded" data-action="openScreen" data-id="<?php echo esc_attr($parent_capture); ?>" data-type="capture" data-context=""><span class="material-symbols-outlined w-6 h-6 mr-2 text-black flex-none hidden">photo_camera</span> Return to Capture</div>
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
                    <span class="text-gray-400 text-sm"><?php echo esc_html($imageCount); ?> images</span>
                    <div class="flex items-center space-x-2">
                        <button class="select-all-visible text-xs text-white hover:text-esper-yellow transition-colors">
                            Select All Visible
                        </button>
                        <button class="rating-filter px-2 py-1 text-xs rounded bg-green-500/20 text-green-500 hover:bg-green-500/30 transition-colors" data-rating="green">
                            Green
                        </button>
                        <button class="rating-filter px-2 py-1 text-xs rounded bg-yellow-500/20 text-yellow-500 hover:bg-yellow-500/30 transition-colors" data-rating="yellow">
                            Yellow
                        </button>
                        <button class="rating-filter px-2 py-1 text-xs rounded bg-red-500/20 text-red-500 hover:bg-red-500/30 transition-colors" data-rating="red">
                            Red
                        </button>
                        <button class="rating-filter px-2 py-1 text-xs rounded bg-gray-500/20 text-gray-500 hover:bg-gray-500/30 transition-colors" data-rating="all">
                            All
                        </button>
                    </div>
                </div>
                <!-- Filmstrip content with custom scrollbar -->
                <div class="flex-1 overflow-x-auto scrollbar filmstrip-scroll">
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

function store_take_generate_filmstrip_thumbnails($take_id = null) {
    // If no take_id provided, return dummy thumbnails
    if (!$take_id) {
        return array(
            'thumbnails' => generate_dummy_thumbnails(),
            'count' => 12
        );
    }

    // Get all take_image posts for this take using meta take_id
    $take_images = get_posts(array(
        'post_type' => 'take_image',
        'posts_per_page' => 12,
        'meta_query' => array(
            array(
                'key' => 'take_id',
                'value' => $take_id
            )
        ),
        'orderby' => 'meta_value_num',
        'meta_key' => 'image_number',
        'order' => 'ASC'
    ));

    // If we have take images, use them
    if (!empty($take_images)) {
        $thumbnails = '';
        foreach ($take_images as $image) {
            $thumbnail_url = get_the_post_thumbnail_url($image->ID, 'thumbnail');
            if (!$thumbnail_url) {
                $thumbnail_url = 'https://placehold.co/1920x1080/333333/FFFFFF/png?text=' . get_post_meta($image->ID, 'image_number', true);
            }
            
            $extra_class = ($image->ID === $take_images[0]->ID) ? 'ring-2 ring-esper-yellow' : '';
            
            // Get current rating
            $current_rating = get_field('colour_rating', $image->ID);
            $rating_indicator = '';
            if ($current_rating) {
                $rating_indicator = '<div class="rating-indicator absolute top-2 right-2 w-3 h-3 rounded-full bg-' . esc_attr($current_rating) . '-500"></div>';
            }
            
            $thumbnails .= '
                <div class="flex-none group" data-image-id="' . esc_attr($image->ID) . '">
                    <div class="h-full bg-black/60 overflow-hidden relative cursor-pointer hover:ring-2 hover:ring-esper-yellow transition-all duration-200 ' . $extra_class . '">
                        <img src="' . esc_url($thumbnail_url) . '" 
                             alt="' . esc_attr($image->post_title) . '"
                             class="w-full h-full object-cover"
                             loading="lazy">

                        ' . $rating_indicator . '
                    </div>
                </div>';
        }
        return array(
            'thumbnails' => $thumbnails,
            'count' => count($take_images)
        );
    }

    // If no take images exist, generate dummy thumbnails
    return array(
        'thumbnails' => generate_dummy_thumbnails(),
        'count' => 12
    );
}

// Helper function to generate dummy thumbnails
function generate_dummy_thumbnails() {
    $colors = array('333333', '444444', '555555', '666666', '777777', '888888');
    $thumbnails = '';
    $num_colors = count($colors);
    
    // Generate 12 placeholder thumbnails with proper aspect ratio
    for ($i = 1; $i <= 12; $i++) {
        $index = $i % $num_colors;
        $color = $colors[$index];
        $extra_class = ($i === 1) ? 'ring-2 ring-esper-yellow' : '';
        
        $thumbnails .= '
            <div class="flex-none group 2">
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
    
    if (!in_array($post_type, array('job', 'session', 'capture', 'take', 'export'))) {
        wp_send_json_error('Invalid post type: ' . $post_type);
        return;
    }
    $current_user_id = get_current_user_id();
    if ($post_type == 'export') {
        
        $exportHandler = new ExportHandler();
    
        // Retrieve export posts for the current user.
        $exports = $exportHandler->getExportsByUser($current_user_id);
    
        // If there is at least one export, get the first (most recent) one.
        if (!empty($exports)) {
            $post = $exports[0];
        } else {
            // No export found; create a new export post.
            $new_post_id = $exportHandler->createExport('New Export Title', $current_user_id);
            if (!$new_post_id) {
                wp_send_json_error('Failed to create export post.');
                return;
            }
            $post = get_post($new_post_id);
        }
    
        // Ensure the post exists.
        if (!$post) {
            wp_send_json_error('Post not found with ID: ' . (isset($post->ID) ? $post->ID : 'Unknown'));
            return;
        }
    
        // Verify that the post author matches the current user.
        if ($post->post_author != $current_user_id) {
            wp_send_json_error('Access denied. Post author (' . $post->post_author . ') does not match current user (' . $current_user_id . ')');
            return;
        }
    
    } else {
        $post = get_post($post_id);
    }
        
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
                    $content = '<div class="h-full grid grid-cols-4 gap-4">
  <!-- Left column: Panels (2 rows) -->
  <div class="col-span-3 grid grid-rows-2 gap-4">
    <div class="bg-white bg-opacity-10 p-4 flex flex-wrap items-center justify-center">
      <span class="opacity-25 uppercase">Light Settings Here</span>
    </div>
    <div class="bg-white bg-opacity-10 p-4 flex flex-wrap items-center justify-center">
      <span class="opacity-25 uppercase">Light Settings Here</span>
    </div>
  </div>
  <!-- Right column: Sidebar -->
  <div class="col-span-1 bg-white bg-opacity-10 p-4 flex flex-wrap items-center justify-center">
    <span class="opacity-25 uppercase">Light Settings Here</span>
  </div>
</div>
';
                break;
                default:
                    $content = esper_get_capture_template($post);
                break;
            }
            
            break;
        case 'take':
            $content = esper_get_take_template($post);
            break;
        case 'export':
            $exportHandler = new ExportHandler();
            $content = $exportHandler->esper_get_export_template($post);
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
    
    // Enqueue Google Material Icons
    wp_enqueue_style(
        'google-material-symbols-outlined',
        'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined',
        array(),
        null
    );
    // Enqueue main styles
    wp_enqueue_style(
        'esper-lightcage-styles', 
        get_template_directory_uri() . '/dist/css/style.css',
        array(),
        wp_get_theme()->get('Version')
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
    
    $post_id = intval($_POST['id']);
    $post = get_post($post_id);
    
    if (!$post || $post->post_author != get_current_user_id()) {
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
        update_post_meta($post_id, 'notes', sanitize_textarea_field($_POST['notes']));
    }
    
    // Update tags if provided
    if (isset($_POST['tags'])) {
        $tags = array_map('sanitize_text_field', $_POST['tags']);
        update_post_meta($post_id, 'job_tags', $tags);
    }
    
    // If we have post updates, apply them
    if (!empty($updates)) {
        $updates['ID'] = $post_id;
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
    .scrollbar::-webkit-scrollbar {
        width: 8px;
    }
    .scrollbar::-webkit-scrollbar-track {
        background: #000000;
    }
    .scrollbar::-webkit-scrollbar-thumb {
        background: #333333;
        border-radius: 4px;
    }
    .scrollbar::-webkit-scrollbar-thumb:hover {
        background: #fcd34d;
    }
    /* For Firefox */
    .scrollbar {
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
                $global_input_class = isset( $set['input_class'] ) ? $set['input_class'] : 'w-full bg-black border p-2 border-white border-opacity-25 text-white';

                
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

                $show_icon = isset( $field['show_icon'] ) ? $field['show_icon'] : false;
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
                    echo '<label for="' . esc_attr( $field['field_slug'] ) . '" class="' . esc_attr( $label_class ) . ' flex flex-wrap items-center">';

                        if ( $show_icon && !empty($field['icon'])) {
                            echo '<span class="material-symbols-outlined w-6 h-6 mr-2 text-black flex-none">'.$field['icon'].'</span>';
                        }
                        echo esc_html( $field['field_name'] );
                    echo '</label>';
                }

                // Render the input based on type.
                $type       = isset( $field['type'] ) ? $field['type'] : 'text';
                $field_slug = isset( $field['field_slug'] ) ? $field['field_slug'] : '';
                $value      = isset( $field['value'] ) ? $field['value'] : '';

                switch ( $type ) {
                    case 'text':
                    case 'number':
                    case 'readOnly':
                        // If the type is readOnly, add the readonly attribute and change type to text.
                        $readonly = ( $type === 'readOnly' ) ? ' readonly="readonly"' : '';
                        $inputType = ( $type === 'readOnly' ) ? 'text' : $type;
                        echo '<input type="' . esc_attr( $inputType ) . '" name="' . esc_attr( $field_slug ) . '" id="' . esc_attr( $field_slug ) . '" value="' . esc_attr( $value ) . '" class="' . esc_attr( $input_class ) . '"' . $attr_string . $readonly . '>';
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
                        echo '<input  class="' . esc_attr( $input_class ) . '" type="range" name="' . esc_attr( $field_slug ) . '" id="' . esc_attr( $field_slug ) . '" value="' . esc_attr( $value ) . '"' . $attr_string . '>';
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

function my_custom_log( $data ) {
    // Get the WordPress uploads directory.
    $upload_dir = wp_upload_dir();
    // Define the log file path.
    $log_file = trailingslashit( $upload_dir['basedir'] ) . 'my-log.txt';
    
    // Format the log entry with a timestamp.
    $time = date("Y-m-d H:i:s");
    $log_entry = $time . " - " . print_r( $data, true ) . PHP_EOL;
    
    // Append the log entry to the log file.
    file_put_contents( $log_file, $log_entry, FILE_APPEND );
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

    // Log the $rows data.
    my_custom_log( $post_id );
    my_custom_log( $rows );

    // Update the ACF repeater field named "camera_settings_repeater" for the given post.
    update_field('camera_settings_repeater', $rows, $post_id);
    
    wp_send_json_success('Repeater updated');
   
}
add_action('wp_ajax_update_camera_settings_repeater', 'update_camera_settings_repeater_ajax');

// AJAX Login Handler
function esper_ajax_login() {
    // Verify nonce
    check_ajax_referer('esper_ajax_nonce', 'nonce');
    
    // Get login credentials
    $username = sanitize_user($_POST['username']);
    $password = $_POST['password'];
    $remember = (bool) $_POST['remember'];
    
    if (empty($username) || empty($password)) {
        wp_send_json_error(array('message' => 'Please enter both username and password.'));
        return;
    }
    
    // Attempt to log in
    $credentials = array(
        'user_login' => $username,
        'user_password' => $password,
        'remember' => $remember
    );
    
    $user = wp_signon($credentials, false);
    
    if (is_wp_error($user)) {
        wp_send_json_error(array('message' => 'Invalid username or password.'));
        return;
    }
    
    wp_send_json_success(array(
        'message' => 'Login successful!',
        'redirect' => home_url()
    ));
}
add_action('wp_ajax_nopriv_esper_ajax_login', 'esper_ajax_login');

// Function to create take images for a take
function esper_create_take_images($take_id) {
    // Create 12 take images
    for ($i = 1; $i <= 12; $i++) {
        $image_title = "Image {$i}";
        $image_post = array(
            'post_title'    => $image_title,
            'post_status'   => 'publish',
            'post_type'     => 'take_image',
            'post_parent'   => $take_id,
            'post_author'   => get_current_user_id()
        );

        $image_id = wp_insert_post($image_post);

        if ($image_id) {
            // Add the take_id as meta data
            update_post_meta($image_id, 'take_id', $take_id);
            // Add the image number as meta data
            update_post_meta($image_id, 'image_number', $i);
        }
    }
}

// Hook into take creation
add_action('esper_take_created', 'esper_create_take_images');

// Add AJAX handler for updating image ratings
function esper_update_image_rating() {
    check_ajax_referer('esper_ajax_nonce', 'nonce');
    
    $take_id = intval($_POST['take_id']);
    $thumbnails = $_POST['thumbnails'];
    
    if (!is_array($thumbnails)) {
        wp_send_json_error('Invalid thumbnails data');
        return;
    }
    
    $success = true;
    foreach ($thumbnails as $thumbnail) {
        $image_id = intval($thumbnail['image_id']);
        $rating = sanitize_text_field($thumbnail['rating']);
        
        if (!in_array($rating, ['green', 'yellow', 'red'])) {
            $success = false;
            continue;
        }
        
        $update_result = update_field('colour_rating', $rating, $image_id);
        if (!$update_result) {
            $success = false;
        }
    }
    
    if ($success) {
        wp_send_json_success();
    } else {
        wp_send_json_error('Some updates failed');
    }
}
add_action('wp_ajax_esper_update_image_rating', 'esper_update_image_rating');



