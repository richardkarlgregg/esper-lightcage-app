<?php
class ExportHandler {
    
    public function __construct() {
        // Hook our class methods into the AJAX actions
        add_action('wp_ajax_esper_create_export', [$this, 'esper_create_export']);
        add_action('wp_ajax_esper_add_to_queue', [$this, 'esper_add_to_queue']);
        add_action('wp_ajax_esper_get_queue_table', [$this, 'esper_get_queue_table']);
        add_action('wp_ajax_esper_get_export_summary', [$this, 'esper_get_export_summary']);
        add_action('wp_ajax_esper_remove_from_queue', [$this, 'esper_remove_from_queue']);
        add_action('wp_ajax_esper_update_queue_status', [$this, 'esper_update_queue_status']);
        add_action('wp_ajax_esper_clear_queue', [$this, 'esper_clear_queue']);
    }

    /**
     * Create an export post and assign it to a user by ID.
     */
    public function createExport($title, $userId) {
        $post_data = [
            'post_title'  => $title,
            'post_type'   => 'export',
            'post_status' => 'publish',
            'post_author' => $userId
        ];
        $post_id = wp_insert_post($post_data);
        return ($post_id && !is_wp_error($post_id)) ? $post_id : false;
    }

    /**
     * Get export posts by user ID.
     */
    public function getExportsByUser($userId) {
        $args = [
            'post_type'      => 'export',
            'author'         => $userId,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC'
        ];
        return get_posts($args);
    }

    /**
     * AJAX handler for creating export posts.
     */
    public function esper_create_export() {
        check_ajax_referer('esper_ajax_nonce', 'nonce');
        // Validate parameters
        $take_id    = isset($_POST['take_id']) ? intval($_POST['take_id']) : 0;
        $image_ids  = isset($_POST['image_ids']) ? $_POST['image_ids'] : [];
        $job_id     = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
        $capture_id = isset($_POST['capture_id']) ? intval($_POST['capture_id']) : 0;
        $session_id = isset($_POST['session_id']) ? intval($_POST['session_id']) : 0;
        if (!$take_id) {
            wp_send_json_error([
                'message' => 'Invalid take ID',
                'error'   => 'Missing or invalid take_id parameter'
            ]);
        }
        if (!is_array($image_ids) || empty($image_ids)) {
            wp_send_json_error([
                'message' => 'No images selected',
                'error'   => 'Missing or empty image_ids parameter'
            ]);
        }
        // Get the take post to use its title
        $take = get_post($take_id);
        if (!$take || $take->post_type !== 'take') {
            wp_send_json_error([
                'message' => 'Invalid take',
                'error'   => 'Take post not found or invalid post type'
            ]);
        }
        // Create new export post
        $export_data = [
            'post_title'  => 'Export from ' . $take->post_title,
            'post_status' => 'publish',
            'post_type'   => 'export',
            'post_author' => get_current_user_id()
        ];
        $export_id = wp_insert_post($export_data);
        if (is_wp_error($export_id)) {
            wp_send_json_error([
                'message' => 'Failed to create export post',
                'error'   => $export_id->get_error_message()
            ]);
        }
        // Add meta values
        update_post_meta($export_id, 'user_id', get_current_user_id());
        if ($job_id)     update_post_meta($export_id, 'job_id', $job_id);
        if ($capture_id) update_post_meta($export_id, 'capture_id', $capture_id);
        if ($session_id) update_post_meta($export_id, 'session_id', $session_id);
        if ($take_id)    update_post_meta($export_id, 'take_id', $take_id);
        
        // Set initial status to 'export'
        update_field('status', 'export', $export_id);
        
        // Prepare and update images for ACF repeater field
        $images = [];
        foreach ($image_ids as $image_id) {
            $images[] = ['take_image' => $image_id];
        }
        $result = update_field('take_images', $images, $export_id);
        if ($result) {
            wp_send_json_success([
                'message'   => 'Export created successfully',
                'export_id' => $export_id
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Failed to add images to export',
                'error'   => 'ACF update_field failed'
            ]);
        }
    }

    /**
     * AJAX handler for adding exports to the queue.
     */
    public function esper_add_to_queue() {
        check_ajax_referer('esper_ajax_nonce', 'nonce');
        $export_id = isset($_POST['export_id']) ? intval($_POST['export_id']) : 0;
        if (!$export_id) {
            wp_send_json_error([
                'message' => 'Invalid export ID',
                'error'   => 'Missing or invalid export_id parameter'
            ]);
        }
        $export = get_post($export_id);
        if (!$export || $export->post_type !== 'export') {
            wp_send_json_error([
                'message' => 'Invalid export',
                'error'   => 'Export post not found or invalid post type'
            ]);
        }
        $queue_data = [
            'post_title'  => 'Queue Item: ' . $export->post_title,
            'post_status' => 'publish',
            'post_type'   => 'export_queue',
            'post_author' => get_current_user_id()
        ];
        $queue_id = wp_insert_post($queue_data);
        if (is_wp_error($queue_id)) {
            wp_send_json_error([
                'message' => 'Failed to create queue item',
                'error'   => $queue_id->get_error_message()
            ]);
        }
        // Copy selected ACF fields from export to queue post
        $acf_fields = [
            'take_images',
            'job_id',
            'session_id',
            'take_id',
            'capture_id',
            'user_id',
            'status',
            'total_images',
            'processed_images',
            'export_id'
        ];
        foreach ($acf_fields as $field) {
            $value = get_field($field, $export_id);
            if ($value !== false) {
                update_field($field, $value, $queue_id);
            }
        }
        update_field('status', 'queued', $queue_id);
        update_field('processed_images', 0, $queue_id);
        $take_images  = get_field('take_images', $queue_id);
        $total_images = is_array($take_images) ? count($take_images) : 0;
        update_field('total_images', $total_images, $queue_id);
        update_field('export_id', $export_id, $queue_id);
        update_post_meta($queue_id, 'export_id', $export_id);
        update_post_meta($export_id, 'queue_id', $queue_id);
        wp_send_json_success([
            'message'  => 'Export added to queue successfully',
            'queue_id' => $queue_id
        ]);
    }

    /**
     * AJAX handler for refreshing the queue table.
     */
    public function esper_get_queue_table() {
        check_ajax_referer('esper_ajax_nonce', 'nonce');
        $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
        if (!$job_id) {
            wp_send_json_error(['message' => 'Invalid job ID']);
        }
        $args = [
            'post_type'      => 'export_queue',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'   => 'job_id',
                    'value' => $job_id
                ],
                [
                    'key'   => 'user_id',
                    'value' => get_current_user_id()
                ]
            ],
            'orderby' => 'date',
            'order'   => 'ASC'
        ];
        $query = new WP_Query($args);
        ob_start();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                echo $this->renderQueueRow();
            }
        } else {
            echo '<tr><td colspan="10" class="px-4 py-2 text-center">No items in queue.</td></tr>';
        }
        wp_reset_postdata();
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }

    /**
     * AJAX handler for getting export summary (standardized with the template).
     */
    public function esper_get_export_summary() {
        check_ajax_referer('esper_ajax_nonce', 'nonce');
        $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
        if (!$job_id) {
            wp_send_json_error(['message' => 'Invalid job ID']);
        }
        
        $current_user_id = get_current_user_id();
        $job_title = get_the_title($job_id);
        $exports = $this->getExportsForJob($job_id, $current_user_id);
        
        ob_start();
        echo $this->renderExportSummarySection($job_title, $exports);
        $html = ob_get_clean();
        
        wp_reset_postdata();
        wp_send_json_success(['html' => $html]);
    }

    /**
     * Generates and returns the full export template HTML.
     *
     * @param WP_Post $post The current export post.
     * @return string The generated HTML.
     */
    public function esper_get_export_template($post) {
        ob_start();
        $current_user_id = get_current_user_id();
        $job_id = get_post_meta($post->ID, 'job_id', true);
        $exports = $this->getExportsForJob($job_id, $current_user_id);
        $job_title = get_the_title($job_id);
        ?>
        <div class="export-template bg-black min-h-screen p-6">
            <?php
            echo $this->renderHeader();
            //echo $this->renderFileNameItems(); ?>
            <div class="exportSummary">
                <?php echo $this->renderExportSummarySection($job_title, $exports); ?>
            </div>
            <div class="exportQueue">
                <?php echo $this->renderQueueSection($job_id, $job_title, $current_user_id); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ================================
       Helper Methods for Template
       ================================ */

    /**
     * Retrieve exports for a specific job and user.
     */
    private function getExportsForJob($job_id, $user_id) {
        $args = [
            'post_type'      => 'export',
            'posts_per_page' => -1,
            'author'         => $user_id,
            'meta_query'     => [
                [
                    'key'   => 'job_id',
                    'value' => $job_id
                ],
                [
                    'relation' => 'OR',
                    [
                        'key'     => 'queue_id',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key'     => 'queue_id',
                        'value'   => '',
                        'compare' => '='
                    ]
                ]
            ],
            'orderby' => 'date',
            'order'   => 'DESC'
        ];
        return get_posts($args);
    }

    /**
     * Render the header section with the main title and back button.
     */
    private function renderHeader() {
        ob_start();
        ?>
        <h2 class="text-2xl font-bold text-white mb-4">Export</h2>
        <div class="hidden w-full flex justify-between flex-wrap mb-4">
            <div class="bg-esper-yellow cursor-pointer text-black px-6 py-3 rounded-lg font-semibold flex items-center" data-action="back">Back</div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the File Name Items section.
     */
    private function renderFileNameItems() {
        ob_start();
        ?>
        <div class="bg-black p-6 border border-esper-yellow">
            <h3 class="text-lg font-semibold text-white mb-4">File Name Items</h3>
            <ul class="list-disc pl-6 text-white">
                <li>job</li>
                <li>session</li>
                <li>take</li>
                <li>camera_serial</li>
                <li>camera_name</li>
                <li>angle (for turntable captures)</li>
                <li>Image Name Will End_FrameIndex</li>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the Export Summary section (table) given the job title and exports.
     */
    private function renderExportSummarySection($job_title, $exports) {
        ob_start();
        ?>
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-white mb-4">Export Summary</h3>
            <p class="text-white mb-4">Job: <?php echo esc_html($job_title); ?></p>
            <table class="w-full text-xs border border-esper-yellow" cellpadding="5" cellspacing="0">
                <thead>
                    <tr class="bg-esper-yellow">
                        <th class="font-normal text-black text-left">Job</th>
                        <th class="font-normal text-black text-left">Session</th>
                        <th class="font-normal text-black text-left">Take</th>
                        <th class="font-normal text-black text-left">Cameras</th>
                        <th class="font-normal text-black text-left">Images</th>
                        <th class="font-normal text-black text-left">Jpegs</th>
                        <th class="font-normal text-black text-left">Raws</th>
                        <th class="font-normal text-black text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if (!empty($exports)) {
                    foreach ($exports as $export) {
                        echo $this->renderExportRow($export, $job_title);
                    }
                } else {
                    echo '<tr><td colspan="8" class="text-center text-white">No exports found</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single row for an export in the summary table.
     */
    private function renderExportRow($export, $job_title) {
        ob_start();
        $session_id    = get_post_meta($export->ID, 'session_id', true);
        $take_id       = get_post_meta($export->ID, 'take_id', true);
        $session_title = get_the_title($session_id);
        $take_title    = get_the_title($take_id);
        $take_images   = get_field('take_images', $export->ID);
        $image_count   = is_array($take_images) ? count($take_images) : 0;
        $camera_count  = 0;
        if ($take_id) {
            $camera_settings = get_field('camera_settings_repeater', $take_id);
            if (is_array($camera_settings)) {
                $camera_count = count($camera_settings);
            }
        }
        $counts = $this->calculateImageCounts($image_count);
        $status = get_field('status', $export->ID);
        ?>
        <tr class="border-b border-esper-yellow">
            <td><?php echo esc_html($job_title); ?></td>
            <td><?php echo esc_html($session_title); ?></td>
            <td><?php echo esc_html($take_title); ?></td>
            <td><?php echo esc_html($camera_count); ?></td>
            <td><?php echo esc_html($image_count); ?></td>
            <td><?php echo esc_html($counts['jpegs']); ?></td>
            <td><?php echo esc_html($counts['raws']); ?></td>
            <td>
                <?php if ($status === 'export'): ?>
                    <button data-action="add-to-queue"
                            data-export-id="<?php echo esc_attr($export->ID); ?>"
                            class="bg-esper-yellow text-black px-3 py-1 rounded text-sm">
                        Add to Queue
                    </button>
                <?php elseif ($status === 'exported'): ?>
                    <button data-action="open-file-location"
                            data-export-id="<?php echo esc_attr($export->ID); ?>"
                            class="bg-esper-yellow text-black px-3 py-1 rounded text-sm">
                        Open File Location
                    </button>
                <?php endif; ?>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Calculate the number of JPEGs and RAWs (placeholder logic).
     */
    private function calculateImageCounts($image_count) {
        return [
            'jpegs' => ceil($image_count / 2),
            'raws'  => floor($image_count / 2)
        ];
    }

    /**
     * Render the Queue Controls section along with the queue table.
     */
    private function renderQueueSection($job_id, $job_title, $current_user_id) {
        ob_start();
        // Query queue items
        $queue_args = [
            'post_type'      => 'export_queue',
            'posts_per_page' => -1,
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => 'user_id',
                    'value'   => $current_user_id,
                    'compare' => '='
                ],
                [
                    'key'     => 'job_id',
                    'value'   => $job_id,
                    'compare' => '='
                ]
            ],
            'orderby' => 'date',
            'order'   => 'ASC'
        ];
        $queue_query = new WP_Query($queue_args);
        ?>
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-white mb-4">Queue</h3>
            <div class="flex space-x-4 mb-4">
                <button data-action="process-queue" class="bg-esper-yellow text-black px-4 py-2 rounded text-sm">Process</button>
                <button data-action="clear-queued" class="bg-esper-yellow text-black px-4 py-2 rounded text-sm">Clear Queued</button>
                <button data-action="clear-completed" class="bg-esper-yellow text-black px-4 py-2 rounded text-sm">Clear Completed</button>
            </div>
            <table id="queue-table" class="w-full text-xs border border-esper-yellow" cellpadding="5" cellspacing="0">
                <thead>
                    <tr class="bg-esper-yellow">
                        <th class="font-normal text-black text-left">
                            <input type="checkbox" class="queue-select-all" checked>
                        </th>
                        <th class="font-normal text-black text-left">Job</th>
                        <th class="font-normal text-black text-left">Session</th>
                        <th class="font-normal text-black text-left">Take</th>
                        <th class="font-normal text-black text-left">Jpegs</th>
                        <th class="font-normal text-black text-left">Raws</th>
                        <th class="font-normal text-black text-left">Images To Export</th>
                        <th class="font-normal text-black text-left">Remaining</th>
                        <th class="font-normal text-black text-left">Status</th>
                        <th class="font-normal text-black text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($queue_query->have_posts()) {
                    while ($queue_query->have_posts()) {
                        $queue_query->the_post();
                        echo $this->renderQueueRow();
                    }
                } else {
                    echo '<tr><td colspan="10" class="text-center text-white">No items in queue</td></tr>';
                }
                wp_reset_postdata();
                ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single row for a queue item.
     */
    private function renderQueueRow() {
        ob_start();
        $job_id           = get_field('job_id');
        $job_title        = get_the_title($job_id);
        $session_id       = get_field('session_id');
        $take_id          = get_field('take_id');
        $session_title    = get_the_title($session_id);
        $take_title       = get_the_title($take_id);
        $total_images     = get_field('total_images');
        $processed_images = get_field('processed_images');
        $remaining        = max(0, $total_images - $processed_images);
        $counts           = $this->calculateImageCounts($total_images);
        $status           = get_field('status');
        ?>
        <tr class="border-b border-esper-yellow">
            <td>
                <?php if ($status === 'queued'): ?>
                    <input type="checkbox" class="queue-item-select" checked data-queue-id="<?php echo esc_attr(get_the_ID()); ?>">
                <?php endif; ?>
            </td>
            <td><?php echo esc_html($job_title); ?></td>
            <td><?php echo esc_html($session_title); ?></td>
            <td><?php echo esc_html($take_title); ?></td>
            <td><?php echo esc_html($counts['jpegs']); ?></td>
            <td><?php echo esc_html($counts['raws']); ?></td>
            <td><?php echo esc_html($total_images); ?></td>
            <td><?php echo esc_html($remaining); ?></td>
            <td><?php echo esc_html($status); ?></td>
            <td>
                <?php if ($status === 'completed'): ?>
                    <button data-action="open-file-location"
                            data-queue-id="<?php echo esc_attr(get_the_ID()); ?>"
                            class="bg-esper-yellow text-black px-3 py-1 rounded text-sm mr-2">
                        Open File Location
                    </button>
                <?php elseif ($status === 'queued'): ?>
                    <button data-action="process-single"
                            data-queue-id="<?php echo esc_attr(get_the_ID()); ?>"
                            class="bg-esper-yellow text-black px-3 py-1 rounded text-sm mr-2">
                        Process
                    </button>
                <?php endif; ?>
                <button data-action="remove-from-queue"
                        data-queue-id="<?php echo esc_attr(get_the_ID()); ?>"
                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                    Remove
                </button>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler for removing items from the queue.
     */
    public function esper_remove_from_queue() {
        check_ajax_referer('esper_ajax_nonce', 'nonce');
        
        $queue_id = isset($_POST['queue_id']) ? intval($_POST['queue_id']) : 0;
        if (!$queue_id) {
            wp_send_json_error([
                'message' => 'Invalid queue ID',
                'error' => 'Missing or invalid queue_id parameter'
            ]);
            return;
        }
        
        // Get the queue item
        $queue_item = get_post($queue_id);
        if (!$queue_item || $queue_item->post_type !== 'export_queue') {
            wp_send_json_error([
                'message' => 'Invalid queue item',
                'error' => 'Queue item not found or invalid post type'
            ]);
            return;
        }
        
        // Get the export_id and status from ACF
        $export_id = get_field('export_id', $queue_id);
        $status = get_field('status', $queue_id);
        
        if ($export_id) {
            // Clear the queue_id from the export post
            update_post_meta($export_id, 'queue_id', '');
            
            // If the queue item was completed, update the export status to exported
            if ($status === 'completed') {
                update_post_meta($export_id, 'status', 'exported');
            }
        }
        
        // Delete the queue item
        $result = wp_delete_post($queue_id, true);
        
        if ($result) {
            wp_send_json_success([
                'message' => 'Item removed from queue successfully'
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Failed to remove item from queue',
                'error' => 'wp_delete_post failed'
            ]);
        }
    }

    /**
     * AJAX handler for updating queue item status.
     */
    public function esper_update_queue_status() {
        check_ajax_referer('esper_ajax_nonce', 'nonce');
        
        $queue_id = isset($_POST['queue_id']) ? intval($_POST['queue_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if (!$queue_id) {
            wp_send_json_error([
                'message' => 'Invalid queue ID',
                'error' => 'Missing or invalid queue_id parameter'
            ]);
            return;
        }
        
        if (!$status) {
            wp_send_json_error([
                'message' => 'Invalid status',
                'error' => 'Missing or invalid status parameter'
            ]);
            return;
        }
        
        // Update the ACF field
        $result = update_field('status', $status, $queue_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => 'Status updated successfully'
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Failed to update status',
                'error' => 'ACF update_field failed'
            ]);
        }
    }

    /**
     * AJAX handler to clear all items from the queue.
     */
    public function esper_clear_queue() {
        check_ajax_referer('esper_ajax_nonce', 'nonce');

        $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
        if (!$job_id) {
            wp_send_json_error(['message' => 'Invalid job ID']);
            return;
        }

        $args = array(
            'post_type' => 'export_queue',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'job_id',
                    'value' => $job_id
                ),
                array(
                    'key' => 'user_id',
                    'value' => get_current_user_id()
                )
            )
        );

        $queue_items = get_posts($args);

        foreach ($queue_items as $queue_item) {
            $export_id = get_field('export_id', $queue_item->ID);
            if ($export_id) {
                update_field('queue_id', '', $export_id);
            }
            wp_delete_post($queue_item->ID, true);
        }

        wp_send_json_success();
    }
}

// Finally, instantiate the class so hooks get registered.
$exportHandler = new ExportHandler();
