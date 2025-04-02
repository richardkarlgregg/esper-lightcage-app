import $ from 'jquery';

import store from './Store.js';

export default class ExportManager {
    constructor() {
        this.init();
    }

    init() {
    }

    setupEventListeners() {
         // Handle Add to Queue
         $(document).on('click', '[data-action="add-to-queue"]', (e) => {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const exportId = $button.data('export-id');
            this.addToQueue(exportId, $button);
        });

        // Handle Remove from Queue
        $(document).on('click', '[data-action="remove-from-queue"]', (e) => {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const queueId = $button.data('queue-id');
            this.removeFromQueue(queueId, $button);
        });
    }

    /**
     * Makes an AJAX call to add an export to the queue, removes the row, and refreshes the queue table.
     */
    addToQueue(exportId, $button) {
        $.ajax({
            url: esperApi.ajaxurl,
            type: 'POST',
            data: {
                action: 'esper_add_to_queue',
                nonce: esperApi.nonce,
                export_id: exportId
            },
            success: (response) => {
                if (response.success) {
                    // Remove the export row and refresh queue
                    $button.closest('tr').fadeOut(300, function() {
                        const $tbody = $(this).closest('tbody');
                        $(this).remove();
                        
                        // Check if table body is now empty (excluding header row)
                        if ($tbody.find('tr').length === 0) {
                            $tbody.append('<tr><td colspan="8" class="px-4 py-2 text-center">No exports found</td></tr>');
                        }
                    });
                    this.refreshQueueTable();
                } else {
                    alert(`Failed to add export to queue: ${response.data.message}`);
                }
            },
            error: () => {
                alert('Failed to add export to queue.');
            }
        });
    }

    /**
     * Refreshes the #queue-table content via AJAX.
     */
    refreshQueueTable() {
        const jobId = store.navigationManager.getPostIdByCriteria('job');

        $.ajax({
            url: esperApi.ajaxurl,
            type: 'POST',
            data: {
                action: 'esper_get_queue_table',
                nonce: esperApi.nonce,
                job_id: jobId
            },
            success: (response) => {
                console.log(response);
                if (response.success) {
                    $('#queue-table tbody').html(response.data.html);
                }
            }
        });
    }

    /**
     * Makes an AJAX call to remove an item from the queue and refreshes the queue table.
     */
    removeFromQueue(queueId, $button) {
        $.ajax({
            url: esperApi.ajaxurl,
            type: 'POST',
            data: {
                action: 'esper_remove_from_queue',
                nonce: esperApi.nonce,
                queue_id: queueId
            },
            success: (response) => {
                if (response.success) {
                    // Remove the queue row and refresh queue
                    $button.closest('tr').fadeOut(300, function() {
                        const $tbody = $(this).closest('tbody');
                        $(this).remove();
                        
                        // Check if table body is now empty (excluding header row)
                        if ($tbody.find('tr').length === 0) {
                            $tbody.append('<tr><td colspan="9" class="px-4 py-2 text-center">No items in queue.</td></tr>');
                        }
                    });
                    this.refreshQueueTable();
                } else {
                    alert(`Failed to remove item from queue: ${response.data.message}`);
                }
            },
            error: () => {
                alert('Failed to remove item from queue.');
            }
        });
    }

};