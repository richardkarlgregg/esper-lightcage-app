<?php

class ExportHandler {
    // Create an export post and assign it to a user by ID
    public function createExport($title, $userId) {
        $post_data = array(
            'post_title' => $title,
            'post_type' => 'export',
            'post_status' => 'publish',
            'post_author' => $userId
        );

        $post_id = wp_insert_post($post_data);

        if ($post_id && !is_wp_error($post_id)) {
            return $post_id;
        } else {
            return false;
        }
    }

    // Get export posts by user ID
    public function getExportsByUser($userId) {
        $args = array(
            'post_type' => 'export',
            'author' => $userId,
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );

        $exports = get_posts($args);

        return $exports;
    }
}