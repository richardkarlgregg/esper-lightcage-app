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

        // Handle Process Queue
        $(document).on('click', '[data-action="process-queue"]', (e) => {
            e.preventDefault();
            this.processQueue();
        });

        // Handle Select All checkbox
        $(document).on('change', '.queue-select-all', (e) => {
            const isChecked = $(e.currentTarget).prop('checked');
            $('#queue-table tbody .queue-item-select').prop('checked', isChecked);
        });

        // Handle individual checkbox changes
        $(document).on('change', '.queue-item-select', (e) => {
            const $allCheckboxes = $('#queue-table tbody .queue-item-select');
            const $checkedCheckboxes = $allCheckboxes.filter(':checked');
            const $selectAll = $('.queue-select-all');
            
            // Update select all checkbox state
            $selectAll.prop('checked', $allCheckboxes.length === $checkedCheckboxes.length);
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
     * Refreshes the export summary table content via AJAX.
     */
    refreshExportSummary() {
        const jobId = store.navigationManager.getPostIdByCriteria('job');

        $.ajax({
            url: esperApi.ajaxurl,
            type: 'POST',
            data: {
                action: 'esper_get_export_summary',
                nonce: esperApi.nonce,
                job_id: jobId
            },
            success: (response) => {
                console.log(response);
                if (response.success) {
                    $('.export-template .exportSummary').html(response.data.html);
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
                    this.refreshExportSummary();
                } else {
                    alert(`Failed to remove item from queue: ${response.data.message}`);
                }
            },
            error: () => {
                alert('Failed to remove item from queue.');
            }
        });
    }

    /**
     * Process all checked queue items with progress bar animation
     */
    processQueue() {
        const $checkedRows = $('#queue-table tbody tr:has(.queue-item-select:checked)');
        
        if ($checkedRows.length === 0) {
            alert('Please select at least one item to process');
            return;
        }

        $checkedRows.each((index, row) => {
            const $row = $(row);
            const queueId = $row.find('.queue-item-select').data('queue-id');
            const totalImages = parseInt($row.find('td:nth-child(7)').text());
            
            // Create progress bar
            const $progressBar = $('<div>', {
                class: 'w-full bg-gray-700 rounded-full h-2.5',
                html: '<div class="bg-esper-yellow h-2.5 rounded-full" style="width: 0%"></div>'
            });
            
            // Replace the "Images To Export" cell content with progress bar
            $row.find('td:nth-child(7)').html($progressBar);
            
            // Animate progress bar over 2 seconds
            $progressBar.find('div').animate({ width: '100%' }, 2000, 'linear', () => {
                // Update status to completed
                this.updateQueueStatus(queueId, 'completed', () => {
                    // Update the status cell
                    $row.find('td:nth-child(9)').text('completed');
                    // Restore the original image count
                    $row.find('td:nth-child(7)').text(totalImages);
                });
            });
        });
    }

    /**
     * Update the status of a queue item via AJAX
     */
    updateQueueStatus(queueId, status, callback) {
        $.ajax({
            url: esperApi.ajaxurl,
            type: 'POST',
            data: {
                action: 'esper_update_queue_status',
                nonce: esperApi.nonce,
                queue_id: queueId,
                status: status
            },
            success: (response) => {
                if (response.success) {
                    if (typeof callback === 'function') {
                        callback();
                    }
                } else {
                    alert(`Failed to update status: ${response.data.message}`);
                }
            },
            error: () => {
                alert('Failed to update status.');
            }
        });
    }

};