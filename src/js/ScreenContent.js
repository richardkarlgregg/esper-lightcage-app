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
    async loadPostContent(postId, postType) {
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
                post_type: postType
            });
            
            console.log('Server response:', response);
            
            if (response.success) {
                if (postType === 'capture') {
                    // Use the server's capture template
                    $('#content')
                        .hide()
                        .html(response.data.content)
                        .fadeIn(300);
                    
                    // Initialize capture handlers
                    store.captureManager.initCaptureHandlers();
                } else if (postType === 'take') {
                    // Ensure we have a title
                    const takeTitle = response.data && response.data.title ? response.data.title : `Take ${postId}`;
                    
                    // Get the first thumbnail URL for the default main image
                    const firstThumbnailUrl = 'https://placehold.co/1920x1080/333333/FFFFFF/png?text=1';
                    
                    // Create the take review layout
                    const takeContent = $(`
                        <div class="take-review flex flex-col bg-black" style="height: calc(100vh - 40px);">
                            <!-- Main container with resizable panes -->
                            <div class="flex-1 flex" id="takePanesContainer">
                                <!-- Main image pane -->
                                <div class="flex-1 relative bg-black flex items-center justify-center overflow-hidden" id="mainImagePane">
                                    <img src="${firstThumbnailUrl}" 
                                        alt="Main Image"
                                        class="w-full h-full object-contain">
                                </div>
                                
                                <!-- Vertical resize handle -->
                                <div class="w-1 bg-white bg-opacity-10 hover:bg-opacity-100 cursor-col-resize" id="verticalResizeHandle"></div>
                                
                                <!-- Right sidebar -->
                                <div class="w-64 bg-black/80 p-4" id="rightSidebarPane">
                                    <div class="flex items-center justify-between mb-4 group relative">
                                        <h3 class="text-lg font-semibold text-white take-title-display" data-take-id="${postId}">${takeTitle}</h3>
                                        <input type="text" class="hidden absolute inset-0 bg-black text-white text-lg font-semibold px-2 py-1 rounded take-title-input" value="${takeTitle}">
                                        <button class="ml-2 text-gray-400 hover:text-esper-yellow opacity-0 group-hover:opacity-100 transition-opacity">
                                            <span class="material-icons text-sm">edit</span>
                                        </button>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="bg-black/60 p-3 rounded">
                                            <h4 class="text-sm font-medium text-gray-300 mb-2">Details</h4>
                                            <p class="text-gray-400 text-sm">Created: ${response.data.date || 'Just now'}</p>
                                            <p class="text-gray-400 text-sm">Status: Active</p>
                                        </div>
                                        <div class="bg-black/60 p-3 rounded">
                                            <h4 class="text-sm font-medium text-gray-300 mb-2">Metadata</h4>
                                            <p class="text-gray-400 text-sm">Resolution: ${response.data.resolution || '1920x1080'}</p>
                                            <p class="text-gray-400 text-sm">Size: ${response.data.size || '2.4 MB'}</p>
                                            <p class="text-gray-400 text-sm">Format: ${response.data.format || 'PNG'}</p>
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
                                            ${store.takeManager.generateFilmstripThumbnails()}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);

                    // Add the content to the page
                    $('#content')
                        .hide()
                        .empty()
                        .append(takeContent)
                        .fadeIn(300);

                    // Initialize all handlers
                    store.takeManager.initializeResizeHandlers(takeContent);
                    store.takeManager.initializeThumbnailHandlers(takeContent);
                    store.takeManager.initializeTakeNameEditor(takeContent, postId, takeTitle);
                } else {
                    // Handle other post types normally
                    $('#content')
                        .hide()
                        .html(response.data.content)
                        .fadeIn(300);
                }
                
                // Initialize handlers based on post type
                if (postType === 'job') {
                    this.initJobContentHandlers();
                } else if (postType === 'capture') {
                    store.captureManager.initCaptureHandlers();
                }

                store.activePostID = postId;
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

