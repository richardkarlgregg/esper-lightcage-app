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

                            <p>Add camera settings icon</p>
                            <p>Add Light settings icon</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button id="triggerTake" 
                                class="bg-yellow-300 hover:bg-yellow-400 text-black px-6 py-3 rounded-lg font-semibold flex items-center"
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
                        <div id="takeProgressBar" class="h-full bg-yellow-300 transition-all duration-300" style="width: 0%"></div>
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
            $content = esper_get_capture_template($post);
            break;
        case 'take':
            $content = esper_get_take_template($post);
            break;
    }
    
    wp_send_json_success(array(
        'content' => $content
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