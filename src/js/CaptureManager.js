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

        
        
        
        
      
        
    }

    // Initialize capture screen handlers
 initCaptureHandlers() {
    const captureId = $('#triggerTake').data('capture-id');
    
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
                $job.find('.material-symbols-outlined').first().addClass('rotate-90');

                // Expand session
                $session.children('div').last().removeClass('hidden');
                $session.find('.material-symbols-outlined').first().addClass('rotate-90');

                // Ensure capture has a children container
                let $captureChildren = $capture.children('div').last();
                if (!$captureChildren.length || $captureChildren.hasClass('flex')) {
                    $captureChildren = $('<div>', {
                        'class': 'pl-3 mt-1 space-y-1'
                    });
                    $capture.append($captureChildren);
                }

                // Show capture's arrow and expand
                const $captureArrow = $capture.find('.material-symbols-outlined').first();
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

}