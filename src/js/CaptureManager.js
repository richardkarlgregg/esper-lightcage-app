import $ from 'jquery';
import 'jquery-ui/ui/widgets/datepicker';

import store from './Store.js';

export default class CaptureManager {
    constructor() {
        this.init();
    }

    init() {
    }

    setupEventListeners() {
        // Handle take card clicks
        $(document).on('click', '.take-card', function() {
            const takeId = $(this).data('take-id');
            store.navigationManager.pushScreen(takeId, 'take');
        });


        jQuery(document).on('keyup change', 'input[name="camera_name"]', function() {
            var $this = jQuery(this);
            // Clear any existing timer.
            clearTimeout($this.data('typingTimer'));
            
            // Set a new timer to run 500ms after the last keyup/change.
            $this.data('typingTimer', setTimeout(function() {
                var value = jQuery.trim($this.val());
                if (value.length > 0) {
                    // Show all hidden inputs and selects in the same row.
                    $this.closest('tr').find('input, select').filter(':hidden').fadeIn(300);
                } else {
                    // Hide all fields except the camera_name field.
                    $this.closest('tr').find('input, select').not('input[name="camera_name"]').fadeOut(300);
                }
            }, 500));
        });

        jQuery(document).on('click', '[data-action="save_camera_settings"]', function() {
            var $table = jQuery('.input-set-table');
            // Assume the table has a data attribute "data-postid" with the Camera Settings post ID.
            var cameraSettingsId = jQuery(this).data('id');
            var rowsData = [];
            
            // Loop through each row in the table body.
            $table.find('tbody tr').each(function() {
                var $row = jQuery(this);
                var rowData = {
                    camera_name: $row.find('input[name="camera_name"]').val() || '',
                    serial_number: $row.find('input[name="serial_number"]').val() || '',
                    camera_model: $row.find('input[name="camera_model"]').val() || '',
                    iso: $row.find('select[name="iso"]').val() || '',
                    aperture: $row.find('select[name="aperture"]').val() || '',
                    white_balance: $row.find('select[name="white_balance"]').val() || '',
                    colour_temp: $row.find('input[name="colour_temp"]').val() || '',
                    shutter_speed: $row.find('select[name="shutter_speed"]').val() || '',
                    file_type: $row.find('select[name="file_type"]').val() || '',
                    jpeg_quality: $row.find('select[name="jpeg_quality"]').val() || '',
                    drive_mode: $row.find('select[name="drive_mode"]').val() || '',
                    focus_mode: $row.find('select[name="focus_mode"]').val() || ''
                };
                rowsData.push(rowData);
            });
            
            // Send AJAX request to update the repeater field.
            jQuery.ajax({
                url: esperApi.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'update_camera_settings_repeater',
                    nonce: esperApi.nonce,
                    post_id: cameraSettingsId,
                    rows: JSON.stringify(rowsData)
                },
                success: function(response) {
                    if(response.success) {
                        alert('Camera settings updated successfully.');
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX error: ' + error);
                }
            });
        });
        
        
        
      
        
    }

    // Initialize capture screen handlers
 initCaptureHandlers() {
    const captureId = $('#triggerTake').data('capture-id');
    
    // Initialize capture name editor with the capture ID
    this.initializeCaptureNameEditor(captureId);
    
    // Remove any existing click handlers before adding new one
    $('#triggerTake').off('click').on('click', async function() {
        const $button = $(this);
        const $progress = $('#takeProgress');
        const $progressBar = $('#takeProgressBar');
        const $progressPercentage = $('#progressPercentage');
        
        try {
            // Disable button and show progress
            $button.prop('disabled', true);
            $progress.css('opacity', '1');
            
            // Create new take first
            const takeCount = $('.folder-item[data-type="take"]').length + 1;
            const response = await store.postManager.createPost('take', `Take ${takeCount}`, 'capture', captureId);
            
            if (response.success) {
                // Add take to folder tree and expand parents
                const $capture = $(`.folder-item[data-id="${captureId}"]`);
                const $session = $capture.parent().closest('.folder-item');
                const $job = $session.parent().closest('.folder-item');

                // Expand job
                $job.children('div').last().removeClass('hidden');
                $job.find('.material-icons').first().addClass('rotate-90');

                // Expand session
                $session.children('div').last().removeClass('hidden');
                $session.find('.material-icons').first().addClass('rotate-90');

                // Ensure capture has a children container
                let $captureChildren = $capture.children('div').last();
                if (!$captureChildren.length || $captureChildren.hasClass('flex')) {
                    $captureChildren = $('<div>', {
                        'class': 'pl-3 mt-1 space-y-1'
                    });
                    $capture.append($captureChildren);
                }

                // Show capture's arrow and expand
                const $captureArrow = $capture.find('.material-icons').first();
                $captureArrow.removeClass('invisible').addClass('rotate-90');
                $captureChildren.removeClass('hidden');

                // Simulate progress (you can replace this with real progress updates)
                let progress = 0;
                const interval = setInterval(() => {
                    progress += 10;
                    $progressBar.css('width', progress + '%');
                    $progressPercentage.text(progress + '%');

                    if (progress >= 100) {
                        clearInterval(interval);
                        $progress.css('opacity', '0');
                        $progressBar.css('width', '0%');
                        $progressPercentage.text('0%');
                        $button.prop('disabled', false);
                        
                        // Show success notification and add new take to folder tree
                        store.notificationManager.showSuccess('Take captured successfully');
                        const newTakeItem = store.uiManager.createFolderItem({
                            id: response.data.id,
                            title: response.data.title,
                            type: 'take',
                            children: []
                        });
                        $captureChildren.append(newTakeItem);

                        // Load the new take content
                        store.navigationManager.pushScreen(response.data.id, 'take');
                        $('.folder-item').removeClass('bg-esper-yellow bg-opacity-10');
                        $(`[data-id="${response.data.id}"]`).addClass('bg-esper-yellow bg-opacity-10');
                    }
                }, 300);
            }
        } catch (error) {
            console.error('Error creating take:', error);
            store.notificationManager.showError('Error creating take');
            $button.prop('disabled', false);
            $progress.css('opacity', '0');
            $progressBar.css('width', '0%');
            $progressPercentage.text('0%');
        }
    });
}

// Add capture name editor functionality
initializeCaptureNameEditor(captureId) {
    const $titleDisplay = $('.capture-title-display');
    const $titleInput = $('.capture-title-input');
    const $editButton = $titleDisplay.siblings('button');

    // Store original title for reverting if needed
    $titleDisplay.data('original-title', $titleDisplay.text());

    function startEditing() {
        $titleDisplay.addClass('hidden');
        $titleInput.removeClass('hidden').val($titleDisplay.text()).focus();
    }

    function stopEditing() {
        const newTitle = $titleInput.val().trim();
        if (!newTitle) {
            $titleInput.val($titleDisplay.text());
            $titleInput.addClass('hidden');
            $titleDisplay.removeClass('hidden');
            return;
        }

        $titleInput.addClass('hidden');
        $titleDisplay.removeClass('hidden').text('Saving...');

        // Save the new title
        $.ajax({
            url: esperApi.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'esper_update_capture',
                nonce: esperApi.nonce,
                capture_id: captureId,
                title: newTitle
            }
        }).then(response => {
            if (response && response.success) {
                // Update title display
                $titleDisplay.text(newTitle);
                $titleDisplay.data('original-title', newTitle);
                
                // Update folder tree item title
                const $folderItem = $(`.folder-item[data-id="${captureId}"][data-type="capture"]`);
                $folderItem.find('> div > .text-white.truncate').text(newTitle);
                
                store.notificationManager.showSuccess('Capture name updated successfully');
            } else {
                const errorMsg = response && response.data ? response.data : 'Failed to update capture name';
                $titleDisplay.text($titleDisplay.data('original-title')); // Revert to original
                store.notificationManager.showError(errorMsg);
            }
        }).catch(error => {
            console.error('Error updating capture name:', error);
            $titleDisplay.text($titleDisplay.data('original-title')); // Revert to original
            store.notificationManager.showError('Failed to update capture name. Please try again.');
        });
    }

    // Click on title or edit button to start editing
    $titleDisplay.add($editButton).on('click', startEditing);

    // Handle input blur and Enter key
    $titleInput
        .on('blur', stopEditing)
        .on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                stopEditing();
            }
        })
        .on('keyup', function(e) {
            if (e.which === 27) { // Escape key
                $titleInput.val($titleDisplay.text()); // Revert to current display value
                $titleInput.addClass('hidden');
                $titleDisplay.removeClass('hidden');
            }
        });
}
}