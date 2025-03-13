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
    ?>
    <div class="take-template bg-black min-h-screen p-6 flex flex-col h-screen">
        <div class="max-w-7xl mx-auto w-full flex-1 flex flex-col space-y-6">
            <!-- Take Header -->
            <div class="bg-black p-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-4 group relative">
                            <h3 class="text-lg font-semibold text-white take-title-display" data-take-id="<?php echo esc_attr($post->ID); ?>"><?php echo esc_html($post->post_title); ?>4</h3>
                            <input type="text" class="hidden absolute inset-0 bg-black text-white text-lg font-semibold px-2 py-1 take-title-input" value="<?php echo esc_attr($post->post_title); ?>">
                            <button class="ml-2 text-gray-400 hover:text-yellow-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="material-icons text-sm">edit</span>
                            </button>
                        </div>
                        <div class="text-gray-400 text-sm">
                            <?php 
                            $parent_capture_id = get_post_meta($post->ID, 'parent_capture', true);
                            $parent_capture = get_post($parent_capture_id);
                            ?>
                            <p>Capture: <?php echo esc_html($parent_capture->post_title); ?></p>
                            <p>Created: <?php echo get_the_date('F j, Y g:i a', $post); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Image View -->
            <div class="flex-1 bg-black flex items-center justify-center min-h-0">
                <div id="mainImageView" class="w-full h-full flex items-center justify-center p-4">
                    <img src="https://placehold.co/1200x800/1f2937/ffffff?text=Selected+Image" 
                         alt="Selected Image"
                         class="max-h-full max-w-full object-contain">
                </div>
            </div>

            <!-- Filmstrip -->
            <div class="bg-black rounded-lg shadow-lg p-4 h-48 relative">
                <!-- Left Arrow -->
                <button id="scrollLeft" class="absolute left-2 top-1/2 -translate-y-1/2 bg-black hover:bg-gray-700 text-white rounded-full p-2 z-10 shadow-lg opacity-0 transition-opacity duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>

                <!-- Right Arrow -->
                <button id="scrollRight" class="absolute right-2 top-1/2 -translate-y-1/2 bg-black hover:bg-gray-700 text-white rounded-full p-2 z-10 shadow-lg opacity-0 transition-opacity duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>

                <div class="h-full flex space-x-4 overflow-x-auto pb-2 px-10 scroll-smooth filmstrip-scroll" id="filmstrip">
                    <?php
                    // Generate 12 filmstrip thumbnails with different background colors
                    $colors = array('1f2937', '374151', '4b5563', '6b7280', '9ca3af', 'd1d5db');
                    for ($i = 1; $i <= 12; $i++) {
                        $color = $colors[$i % count($colors)];
                        $mainImageUrl = "https://placehold.co/1200x800/${color}/ffffff?text=Image+" . $i;
                        $thumbnailUrl = "https://placehold.co/400x300/${color}/ffffff?text=Image+" . $i;
                        ?>
                        <div class="filmstrip-thumbnail flex-none w-40 h-full bg-black rounded cursor-pointer transition-all duration-200 hover:ring-2 hover:ring-yellow-300" 
                             data-image-url="<?php echo esc_url($mainImageUrl); ?>">
                            <img src="<?php echo esc_url($thumbnailUrl); ?>" 
                                 alt="Image <?php echo $i; ?>"
                                 class="w-full h-full object-cover rounded">
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <style>
    /* Custom scrollbar styles */
    .filmstrip-scroll::-webkit-scrollbar {
        height: 8px;
    }

    .filmstrip-scroll::-webkit-scrollbar-track {
        background: #1f2937;
        border-radius: 4px;
    }

    .filmstrip-scroll::-webkit-scrollbar-thumb {
        background: #4b5563;
        border-radius: 4px;
    }

    .filmstrip-scroll::-webkit-scrollbar-thumb:hover {
        background: #6b7280;
    }

    /* Hide scrollbar for Firefox */
    .filmstrip-scroll {
        scrollbar-width: thin;
        scrollbar-color: #4b5563 #1f2937;
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Initialize first thumbnail as selected
        $('.filmstrip-thumbnail').first().addClass('ring-2 ring-yellow-300');

        // Handle thumbnail clicks
        $('.filmstrip-thumbnail').on('click', function() {
            // Update selected state
            $('.filmstrip-thumbnail').removeClass('ring-2 ring-yellow-300');
            $(this).addClass('ring-2 ring-yellow-300');

            // Update main image
            const imageUrl = $(this).data('image-url');
            $('#mainImageView img').attr('src', imageUrl);

            // Smooth scroll thumbnail into view if needed
            this.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        });

        // Handle arrow clicks
        $('#scrollLeft').on('click', function() {
            const filmstrip = document.getElementById('filmstrip');
            filmstrip.scrollBy({
                left: -300,
                behavior: 'smooth'
            });
        });

        $('#scrollRight').on('click', function() {
            const filmstrip = document.getElementById('filmstrip');
            filmstrip.scrollBy({
                left: 300,
                behavior: 'smooth'
            });
        });

        // Show/hide arrows based on scroll position
        const filmstrip = document.getElementById('filmstrip');
        filmstrip.addEventListener('scroll', function() {
            const showLeft = filmstrip.scrollLeft > 0;
            const showRight = filmstrip.scrollLeft < (filmstrip.scrollWidth - filmstrip.clientWidth);
            
            $('#scrollLeft').css('opacity', showLeft ? '1' : '0');
            $('#scrollRight').css('opacity', showRight ? '1' : '0');
        });

        // Trigger initial scroll check
        $(filmstrip).trigger('scroll');
    });
    </script>
    <?php
    return ob_get_clean();
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
    
    if ($post_type === 'take') {
        // For takes, return the post data instead of template
        $parent_capture_id = get_post_meta($post->ID, 'parent_capture', true);
        $parent_capture = get_post($parent_capture_id);
        
        wp_send_json_success(array(
            'title' => $post->post_title,
            'date' => get_the_date('F j, Y g:i a', $post),
            'parent_capture' => array(
                'id' => $parent_capture_id,
                'title' => $parent_capture->post_title
            )
        ));
    } else {
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
        }
        
        wp_send_json_success(array(
            'content' => $content
        ));
    }
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