import $ from 'jquery';
import 'jquery-ui/ui/widgets/datepicker';

import store from './Store.js';

export default class ScreenContent {
    constructor() {
        this.init();
    }

    init() {
    }

    setupEventListeners() {

    }

    // Load post content
async loadPostContent(postId, postType, context) {
    try {
        // Show loading state
        $('#content').html(`
            <div class="flex items-center justify-center h-64">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-esper-yellow"></div>
            </div>
        `);

        console.log('Loading post content:', { postId, postType, nonce: esperApi.nonce });

        const response = await $.post(esperApi.ajaxurl, {
            action: 'esper_get_content',
            nonce: esperApi.nonce,
            post_id: postId,
            post_type: postType,
            context : JSON.stringify(context)
        });

        console.log('Server response:', response);

        if (response.success) {
            switch (postType) {
                case 'capture':
                    $('#content')
                        .hide()
                        .html(response.data.content)
                        .fadeIn(300);
                    // Initialize capture handlers
                    store.captureManager.initCaptureHandlers();
                    break;

                case 'take': {
                    $('#content')
                        .hide()
                        .html(response.data.content)
                        .fadeIn(300);
                    const takeTitle = response.data.title ? response.data.title : `Take ${postId}`;
                    // Initialize take-specific handlers
                    store.takeManager.initializeResizeHandlers($('#content'));
                    store.takeManager.initializeThumbnailHandlers($('#content'));
                    store.takeManager.initializeTakeNameEditor($('#content'), postId, takeTitle);
                    break;
                }

                case 'job':
                    $('#content')
                        .hide()
                        .html(response.data.content)
                        .fadeIn(300);
                    // Initialize job-specific handlers
                    this.initJobContentHandlers();
                    break;

                default:
                    $('#content')
                        .hide()
                        .html(response.data.content)
                        .fadeIn(300);
                    break;
            }

            // Set Active
            store.activePostID = postId;
            store.postType = postType;
        } else {
            const errorMessage = response.data || 'Error loading content';
            console.error('Server returned error:', errorMessage);
            store.notificationManager.showError(errorMessage);
        }
    } catch (error) {
        console.error('Error loading post content:', error);
        store.notificationManager.showError('Error loading content: ' + error.message);
    }
}



    // Initialize job content interaction handlers
initJobContentHandlers() {
    let saveTimeout;
    const jobId = $('.job-title-display').data('job-id');
    
    // Initialize job title editor
    const $titleDisplay = $('.job-title-display');
    const $titleInput = $('.job-title-input');
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
                action: 'esper_update_job',
                nonce: esperApi.nonce,
                job_id: jobId,
                title: newTitle
            }
        }).then(response => {
            if (response && response.success) {
                // Update title display
                $titleDisplay.text(newTitle);
                $titleDisplay.data('original-title', newTitle);
                
                // Update folder tree item title
                const $folderItem = $(`.folder-item[data-id="${jobId}"][data-type="job"]`);
                $folderItem.find('> div > .text-white.truncate').text(newTitle);
                
                // Update current job title in header
                $('#currentJobTitle').text(newTitle);
                
                store.notificationManager.showSuccess('Job name updated successfully');
            } else {
                const errorMsg = response && response.data ? response.data : 'Failed to update job name';
                $titleDisplay.text($titleDisplay.data('original-title')); // Revert to original
                store.notificationManager.showError(errorMsg);
            }
        }).catch(error => {
            console.error('Error updating job name:', error);
            $titleDisplay.text($titleDisplay.data('original-title')); // Revert to original
            store.notificationManager.showError('Failed to update job name. Please try again.');
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
    
    // Auto-save notes when typing stops
    $('#jobNotes').on('input', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => saveJobDetails(), 1000);
    });
    
    // Add new tag
    $('#addTag').on('click', async function() {
        const newTag = $('#newTag').val().trim();
        if (!newTag) return;
        
        const $tagContainer = $('#tagContainer');
        const currentTags = getTags();
        
        if (currentTags.includes(newTag)) {
            store.notificationManager.showError('Tag already exists');
            return;
        }
        
        const $newTag = $('<span>', {
            'class': 'bg-esper-yellow text-black px-2 py-1 rounded text-sm flex items-center',
            'html': `
                ${newTag}
                <button class="ml-2 text-black hover:text-black remove-tag" data-tag="${newTag}">&times;</button>
            `
        });
        
        $tagContainer.append($newTag);
        $('#newTag').val('');
        
        await saveJobDetails();
    });
    
    // Remove tag
    $(document).on('click', '.remove-tag', async function() {
        $(this).parent().remove();
        await saveJobDetails();
    });
    
    // Handle Enter key in new tag input
    $('#newTag').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#addTag').click();
        }
    });
    
    // Save all job details
    async function saveJobDetails() {
        const data = {
            action: 'esper_update_job',
            nonce: esperApi.nonce,
            job_id: jobId,
            notes: $('#jobNotes').val(),
            tags: getTags()
        };
        
        const response = await $.post(esperApi.ajaxurl, data);
        
        if (!response.success) {
            throw new Error(response.data || 'Error saving job details');
        }
    }
    
    // Get current tags
    function getTags() {
        return $('#tagContainer span').map(function() {
            return $(this).contents().first().text().trim();
        }).get();
    }
}

}

