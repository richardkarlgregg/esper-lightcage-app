// Folder Tree functionality
export { initFolderTree, loadPostContent };

function initFolderTree() {
    let currentJobId = null;

    // Initialize menu functionality
    initializeMenu();

    // Show default message
    showDefaultContent();

    // Event handlers for folder tree items
    $(document).on('click', '.folder-item', function(e) {
        if ($(e.target).closest('.add-btn').length) return;
        
        // Handle arrow click
        if ($(e.target).closest('.material-icons').length && $(e.target).closest('.material-icons').text() === 'chevron_right') {
            e.stopPropagation();
            const $arrow = $(e.target).closest('.material-icons');
            const $children = $(this).children('div').last();
            
            if ($children.length) {
                $children.toggleClass('hidden');
                $arrow.toggleClass('rotate-90');
            }
            return;
        }
        
        // Regular click on folder item
        e.stopPropagation();
        const postId = $(this).data('id');
        const postType = $(this).data('type');
        loadPostContent(postId, postType);
        $('.folder-item').removeClass('bg-yellow-300 bg-opacity-10');
        $(this).addClass('bg-yellow-300 bg-opacity-10');
    });

    // Event delegation for add buttons
    $(document).on('click', '.add-btn', async function(e) {
        e.stopPropagation();
        const parentItem = $(this).closest('.folder-item');
        const parentType = parentItem.data('type');
        const parentId = parentItem.data('id');
        let childType;
        
        if (parentType === 'job') {
            childType = 'session';
        } else if (parentType === 'session') {
            childType = 'capture';
        } else if (parentType === 'capture') {
            childType = 'take';
        } else {
            return;
        }
        
        try {
            // Get current child count
            let $childrenContainer = parentItem.children('div').last();
            let childCount = 0;
            
            if ($childrenContainer.length && !$childrenContainer.hasClass('flex')) {
                childCount = $childrenContainer.children('.folder-item').length;
            } else {
                // Create children container if it doesn't exist
                $childrenContainer = $('<div>', {
                    'class': 'pl-1 mt-1 space-y-1 hidden'
                });
                parentItem.append($childrenContainer);
            }
            
            const newLabel = childType.charAt(0).toUpperCase() + childType.slice(1) + ' ' + (childCount + 1);
            const response = await createPost(childType, newLabel, parentType, parentId);
            
            if (response.success && response.data.id) {
                // Show parent's arrow
                const $arrow = parentItem.find('.material-icons').first();
                $arrow.removeClass('invisible');

                // Expand parent by removing hidden class
                $childrenContainer.removeClass('hidden');
                $arrow.addClass('rotate-90');

                // Add the new item
                const newItem = createFolderItem({
                    id: response.data.id,
                    title: newLabel,
                    type: childType,
                    children: []
                });
                $childrenContainer.append(newItem);

                // If this is a new session or capture, expand all parent items
                if (childType === 'session' || childType === 'capture') {
                    const $parents = parentItem.parents('.folder-item');
                    $parents.each(function() {
                        const $parent = $(this);
                        const $parentArrow = $parent.find('.material-icons').first();
                        const $parentChildren = $parent.children('div').last();
                        
                        $parentArrow.removeClass('invisible').addClass('rotate-90');
                        $parentChildren.removeClass('hidden');
                    });
                }
            }
        } catch (error) {
            console.error('Error creating item:', error);
        }
    });

    // Initialize menu functionality
    function initializeMenu() {
        // Open Job Modal
        $('#openJobBtn').on('click', function() {
            loadJobList();
            $('#jobModal').fadeIn(200);
        });

        // Close Modal - update to handle both click on overlay and close button
        $('#closeModal, #jobModal').on('click', function(e) {
            if (e.target === this || $(e.target).closest('#closeModal').length) {
                $('#jobModal').fadeOut(200);
            }
        });

        // New Job
        $('#newJobBtn').on('click', async function() {
            const jobCount = $('#folderTree').children('.folder-item').length + 1;
            const newJobLabel = 'Job ' + jobCount;
            
            try {
                const response = await createPost('job', newJobLabel);
                if (response.success && response.data.id) {
                    openJob(response.data.id, newJobLabel);
                }
            } catch (error) {
                console.error('Error creating job:', error);
            }
        });

        // Close Job
        $('#closeJobBtn').on('click', function() {
            closeCurrentJob();
        });

        // Job Search
        $('#jobSearch').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('#jobList li').each(function() {
                const title = $(this).text().toLowerCase();
                $(this).toggle(title.includes(searchTerm));
            });
        });
    }

    // Load and display job list in modal
    async function loadJobList() {
        try {
            const response = await $.post(esperApi.ajaxurl, {
                action: 'esper_get_jobs',
                nonce: esperApi.nonce
            });

            const $jobList = $('#jobList').empty();
            
            if (response.success && response.data.length > 0) {
                response.data.forEach(job => {
                    const $item = $('<li>', {
                        'class': 'px-3 py-2 hover:bg-black/50 rounded cursor-pointer flex items-center',
                        'data-id': job.id,
                        'html': `
                            <svg class="w-6 h-6 mr-2 text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                            </svg>
                            ${job.title}
                        `
                    }).on('click', function() {
                        openJob(job.id, job.title);
                        $('#jobModal').fadeOut(200);
                    });
                    
                    $jobList.append($item);
                });
            } else {
                $jobList.append('<li class="text-gray-500 px-3 py-2">No jobs found</li>');
            }
        } catch (error) {
            console.error('Error loading jobs:', error);
            $('#jobList').html('<li class="text-red-500 px-3 py-2">Error loading jobs</li>');
        }
    }

    // Open a specific job
    async function openJob(jobId, jobTitle) {
        currentJobId = jobId;
        $('#currentJobTitle').text(jobTitle);
        
        try {
            // First load the job content
            await loadPostContent(jobId, 'job');
            
            // Then load the hierarchy for the folder tree
            const response = await $.post(esperApi.ajaxurl, {
                action: 'esper_get_hierarchy',
                nonce: esperApi.nonce,
                job_id: jobId
            });
            
            $('#folderTree').empty();
            
            if (response.success && response.data.length > 0) {
                const job = response.data.find(j => j.id === jobId);
                if (job) {
                    $('#folderTree').append(createFolderItem(job));
                }
            }
        } catch (error) {
            console.error('Error loading job:', error);
            showError('Error loading job');
        }
    }

    // Close current job
    function closeCurrentJob() {
        currentJobId = null;
        $('#currentJobTitle').text('No job open');
        $('#folderTree').empty();
        showDefaultContent();
    }

    // Show default content
    function showDefaultContent() {
        // Only show the welcome screen if no job is currently open
        if (!currentJobId) {
            $('#content').html(`
                <div class="flex flex-col items-center justify-center h-full text-gray-500">
                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    <p class="text-lg mb-4">No job open</p>
                    <button id="welcomeOpenJob" class="bg-yellow-300 hover:bg-yellow-400 text-black px-4 py-2 rounded">
                        Open a Job
                    </button>
                </div>
            `);

            $('#welcomeOpenJob').on('click', function() {
                $('#openJobBtn').click();
            });
        }
    }

    // Show error message
    function showError(message) {
        $('#content').html(`
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <p class="font-bold">Error</p>
                <p>${message}</p>
            </div>
        `);
    }

    // Simple implementation for a draggable divider
    let isResizing = false;
    $('#divider').on('mousedown', function(e) {
        isResizing = true;
    });
    $(document).on('mousemove', function(e) {
        if (!isResizing) return;
        const newWidth = Math.min(Math.max(e.clientX, 200), 500);
        $('#sidebar').css('width', newWidth);
    }).on('mouseup', function() {
        isResizing = false;
    });
}

// Load the entire hierarchy
async function loadHierarchy() {
    try {
        const response = await $.post(esperApi.ajaxurl, {
            action: 'esper_get_hierarchy',
            nonce: esperApi.nonce
        });
        
        $('#folderTree').empty();
        
        if (!response.success) {
            throw new Error(response.data || 'Error loading hierarchy');
        }

        if (response.data.length === 0) {
            $('#folderTree').append('<li class="text-gray-500">No jobs found</li>');
            return;
        }
        
        // Render each job and its children
        response.data.forEach(job => {
            $('#folderTree').append(createFolderItem(job));
        });
    } catch (error) {
        console.error('Error loading hierarchy:', error);
        $('#folderTree').append('<li class="text-red-500">Error loading hierarchy: ' + error.message + '</li>');
    }
}

// Create a folder item element with its children
function createFolderItem(item) {
    const $item = $('<div>', {
        'class': 'folder-item cursor-pointer pt-1 pb-1 rounded relative',
        'data-id': item.id,
        'data-type': item.type
    });

    const $header = $('<div>', {
        'class': 'flex items-center space-x-2 hover:bg-black/50 rounded group relative'
    });

    // Add collapse arrow for all items (will be hidden if no children)
    const $arrow = $('<span>')
        .addClass('material-icons w-4 flex-none text-yellow-300 transform transition-transform duration-200 ' + 
            ((!item.children || item.children.length === 0) ? 'invisible' : ''))
        .text('chevron_right');
    $header.append($arrow);

    // Add icon based on type
    const iconType = item.type === 'job' ? 'folder' :
                    item.type === 'session' ? 'calendar_today' :
                    item.type === 'capture' ? 'camera_alt' :
                    'movie';

    const $icon = $('<span>', {
        'class': 'material-icons w-6 h-6 text-black/60 flex-none',
        'text': iconType
    });
    $header.append($icon);

    // Add title
    $header.append(
        $('<span>', {
            'class': 'flex-1 text-white truncate ml-2',
            'text': item.title
        })
    );

    // Add action buttons based on type
    if (item.type === 'job') {
        const $addButton = $('<button>', {
            'class': 'add-btn ml-2 flex items-center text-black/60 hover:text-yellow-300 opacity-0 group-hover:opacity-100 transition-opacity duration-200 absolute right-2',
            'title': 'Add Session'
        }).append(
            $('<span>', {
                'class': 'material-icons',
                'text': 'add_circle'
            })
        );
        $header.append($addButton);
    } else if (item.type === 'session') {
        const $addButton = $('<button>', {
            'class': 'add-btn ml-2 flex items-center text-black/60 hover:text-yellow-300 opacity-0 group-hover:opacity-100 transition-opacity duration-200 absolute right-2',
            'title': 'Add Capture'
        }).append(
            $('<span>', {
                'class': 'material-icons',
                'text': 'add_circle'
            })
        );
        $header.append($addButton);
    }

    $item.append($header);

    // Add children container if there are children
    if (item.children && item.children.length > 0) {
        const $children = $('<div>', {
            'class': 'pl-1 mt-1 space-y-1 hidden'
        });
        
        item.children.forEach(child => {
            $children.append(createFolderItem(child));
        });
        
        $item.append($children);
    }

    return $item;
}

// Create a new post via AJAX
async function createPost(type, title, parentType = null, parentId = null) {
    const data = {
        action: 'esper_create_item',
        nonce: esperApi.nonce,
        type: type,
        title: title
    };
    
    if (parentType && parentId) {
        data.parent_type = parentType;
        data.parent_id = parentId;
    }
    
    try {
        return await $.post(esperApi.ajaxurl, data);
    } catch (error) {
        console.error('Error creating post:', error);
        throw error;
    }
}

// Load post content
async function loadPostContent(postId, postType) {
    try {
        // Show loading state
        $('#content').html(`
            <div class="flex items-center justify-center h-64">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-yellow-300"></div>
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
                initCaptureHandlers();
            } else if (postType === 'take') {
                // Ensure we have a title
                const takeTitle = response.data && response.data.title ? response.data.title : `Take ${postId}`;
                
                // Get the first thumbnail URL for the default main image
                const firstThumbnailUrl = 'https://placehold.co/1920x1080/333333/FFFFFF/png?text=' + encodeURIComponent(takeTitle);
                
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
                                    <button class="ml-2 text-gray-400 hover:text-yellow-300 opacity-0 group-hover:opacity-100 transition-opacity">
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
                                        ${generateFilmstripThumbnails()}
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
                initializeResizeHandlers(takeContent);
                initializeThumbnailHandlers(takeContent);
                initializeTakeNameEditor(takeContent, postId, takeTitle);
            } else {
                // Handle other post types normally
                $('#content')
                    .hide()
                    .html(response.data.content)
                    .fadeIn(300);
            }
            
            // Initialize handlers based on post type
            if (postType === 'job') {
                initJobContentHandlers();
            } else if (postType === 'capture') {
                initCaptureHandlers();
            }
        } else {
            const errorMessage = response.data || 'Error loading content';
            console.error('Server returned error:', errorMessage);
            showError(errorMessage);
        }
    } catch (error) {
        console.error('Error loading post content:', error);
        showError('Error loading content: ' + error.message);
    }
}

// Initialize job content interaction handlers
function initJobContentHandlers() {
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
                
                showSuccess('Job name updated successfully');
            } else {
                const errorMsg = response && response.data ? response.data : 'Failed to update job name';
                $titleDisplay.text($titleDisplay.data('original-title')); // Revert to original
                showError(errorMsg);
            }
        }).catch(error => {
            console.error('Error updating job name:', error);
            $titleDisplay.text($titleDisplay.data('original-title')); // Revert to original
            showError('Failed to update job name. Please try again.');
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
            showError('Tag already exists');
            return;
        }
        
        const $newTag = $('<span>', {
            'class': 'bg-gray-600 text-white px-2 py-1 rounded text-sm flex items-center',
            'html': `
                ${newTag}
                <button class="ml-2 text-gray-400 hover:text-white remove-tag" data-tag="${newTag}">&times;</button>
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

// Initialize capture screen handlers
function initCaptureHandlers() {
    const captureId = $('#triggerTake').data('capture-id');
    
    // Initialize capture name editor with the capture ID
    initializeCaptureNameEditor(captureId);
    
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
            const response = await createPost('take', `Take ${takeCount}`, 'capture', captureId);
            
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
                        'class': 'pl-1 mt-1 space-y-1'
                    });
                    $capture.append($captureChildren);
                }

                // Show capture's arrow and expand
                const $captureArrow = $capture.find('.material-icons').first();
                $captureArrow.removeClass('invisible').addClass('rotate-90');
                $captureChildren.removeClass('hidden');

                // Add the new take item
                const newTakeItem = createFolderItem({
                    id: response.data.id,
                    title: response.data.title,
                    type: 'take'
                });
                $captureChildren.append(newTakeItem);

                // Simulate progress (you can replace this with real progress updates)
                let progress = 0;
                const progressInterval = setInterval(() => {
                    progress += 2;
                    $progressBar.css('width', `${progress}%`);
                    $progressPercentage.text(`${progress}%`);
                    
                    if (progress >= 100) {
                        clearInterval(progressInterval);
                        // Load the new take content after progress reaches 100%
                        loadPostContent(response.data.id, 'take');
                    }
                }, 100);
                
                // Show success message
                showSuccess('Take created successfully');

                // Clean up after progress completes
                setTimeout(() => {
                    $progress.css('opacity', '0');
                    $progressBar.css('width', '0%');
                    $progressPercentage.text('0%');
                    $button.prop('disabled', false);
                }, 5500); // Wait for progress animation to complete (slightly longer than the progress simulation)
            }
        } catch (error) {
            console.error('Error creating take:', error);
            showError('Error creating take');
            $button.prop('disabled', false);
            $progress.css('opacity', '0');
            $progressBar.css('width', '0%');
            $progressPercentage.text('0%');
        }
    });
}

// Add capture name editor functionality
function initializeCaptureNameEditor(captureId) {
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
                
                showSuccess('Capture name updated successfully');
            } else {
                const errorMsg = response && response.data ? response.data : 'Failed to update capture name';
                $titleDisplay.text($titleDisplay.data('original-title')); // Revert to original
                showError(errorMsg);
            }
        }).catch(error => {
            console.error('Error updating capture name:', error);
            $titleDisplay.text($titleDisplay.data('original-title')); // Revert to original
            showError('Failed to update capture name. Please try again.');
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

// Function to generate placeholder gallery HTML
function generatePlaceholderGallery() {
    const colors = ['4a5f6a', '6a4a5f', '5f6a4a', '4a6a5f', '5f4a6a', '6a5f4a'];
    let html = `
        <div class="bg-black/60 rounded-lg shadow-lg p-6 mt-6">
            <h3 class="text-lg font-semibold text-white mb-4">Images</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
    `;
    
    // Generate 12 placeholder images
    for (let i = 1; i <= 12; i++) {
        const color = colors[i % colors.length];
        html += `
            <div class="bg-black/80 rounded-lg overflow-hidden">
                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iIzRhNWY2YSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMjAiIGZpbGw9IiNmZmYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5ObyBJbWFnZTwvdGV4dD48L3N2Zz4=" 
                     alt="Image ${i}"
                     class="w-full h-48 object-cover">
                <div class="p-4">
                    <h4 class="text-white font-semibold">Image ${i}</h4>
                    <p class="text-black/60 text-sm">Placeholder</p>
                </div>
            </div>
        `;
    }
    
    html += `
            </div>
        </div>
    `;
    
    return html;
}

// Show success message in a toast notification
function showSuccess(message) {
    const $toast = $('<div>', {
        'class': 'fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded shadow-lg z-50',
        'text': message
    });
    
    $('body').append($toast);
    
    setTimeout(() => {
        $toast.fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}

// Show error message in a toast notification
function showError(message) {
    const $toast = $('<div>', {
        'class': 'fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded shadow-lg z-50',
        'text': message
    });
    
    $('body').append($toast);
    
    setTimeout(() => {
        $toast.fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}

// Add this function near the bottom of the file
function initializeResizeHandlers($takeCard) {
    let isResizing = false;
    let currentHandle = null;
    let startX, startY;
    let startWidth, startHeight;
    
    // Vertical resize handle
    const $verticalHandle = $takeCard.find('#verticalResizeHandle');
    const $rightSidebar = $takeCard.find('#rightSidebarPane');
    const $mainPane = $takeCard.find('#mainImagePane');
    
    // Horizontal resize handle
    const $horizontalHandle = $takeCard.find('#horizontalResizeHandle');
    const $thumbnailsPane = $takeCard.find('#thumbnailsPane');
    const $mainContainer = $takeCard.find('#takePanesContainer');
    const $thumbnailsContent = $thumbnailsPane.find('.flex-1');
    const $thumbnailsContainer = $thumbnailsContent.find('.flex');
    const $thumbnails = $thumbnailsContainer.find('.flex-none');
    
    // Store initial aspect ratios
    const mainImageRatio = 16/9; // 1920x1080 aspect ratio
    const thumbRatio = 16/9; // Same as main image
    
    // Vertical resize
    $verticalHandle.on('mousedown', function(e) {
        isResizing = true;
        currentHandle = 'vertical';
        startX = e.clientX;
        startWidth = $rightSidebar.width();
        e.preventDefault();
    });
    
    // Horizontal resize
    $horizontalHandle.on('mousedown', function(e) {
        isResizing = true;
        currentHandle = 'horizontal';
        startY = e.clientY;
        startHeight = $thumbnailsPane.height();
        e.preventDefault();
    });
    
    // Handle resize
    $(document).on('mousemove', function(e) {
        if (!isResizing) return;
        
        if (currentHandle === 'vertical') {
            const width = Math.max(200, Math.min(500, startWidth + (startX - e.clientX)));
            $rightSidebar.css('width', width + 'px');
            
            // Update main image to fill available space
            $mainPane.find('img').css({
                'width': '100%',
                'height': '100%',
                'object-fit': 'contain',
                'object-position': 'center'
            });
        } else if (currentHandle === 'horizontal') {
            const containerHeight = $takeCard.height();
            const newHeight = Math.max(100, Math.min(300, startHeight + (startY - e.clientY)));
            
            // Update thumbnails pane height
            $thumbnailsPane.css('height', newHeight + 'px');
            
            // Update main container height to fill remaining space
            const mainContainerHeight = containerHeight - newHeight - $horizontalHandle.height();
            $mainContainer.css('height', mainContainerHeight + 'px');
            
            // Calculate available height for thumbnails (subtract toolbar height)
            const toolbarHeight = $thumbnailsPane.find('.bg-black').outerHeight();
            const availableHeight = newHeight - toolbarHeight - 16; // 16px for padding
            
            // Update thumbnails container height
            $thumbnailsContent.css('height', availableHeight + 'px');
            
            // Update thumbnail dimensions based on available height while maintaining 11:7 aspect ratio
            $thumbnails.each(function() {
                // Calculate width based on 11:7 aspect ratio using the container height
                const thumbWidth = (availableHeight * 11) / 7;
                
                // Only set the width and flex basis
                //$(this).css({
                    //'width': thumbWidth + 'px',
                   // 'flex': '0 0 ' + thumbWidth + 'px'
               // });
                
                // Ensure the image inside maintains aspect ratio
                $(this).find('img').css({
                    'width': '100%',
                    'height': '100%',
                    'object-fit': 'cover'
                });
            });
            
            // Update main image to fill the new available space
            requestAnimationFrame(() => {
                $mainPane.find('img').css({
                    'width': '100%',
                    'height': '100%',
                    'object-fit': 'contain',
                    'object-position': 'center'
                });
            });
        }
    }).on('mouseup', function() {
        if (isResizing) {
            // Final update to ensure main image fills the space
            requestAnimationFrame(() => {
                $mainPane.find('img').css({
                    'width': '100%',
                    'height': '100%',
                    'object-fit': 'contain',
                    'object-position': 'center'
                });
            });
        }
        isResizing = false;
        currentHandle = null;
    });
    
    // Initial aspect ratio setup
    function setupInitialAspectRatios() {
        // Set initial heights
        const containerHeight = $takeCard.height();
        const thumbnailsHeight = $thumbnailsPane.height();
        const mainContainerHeight = containerHeight - thumbnailsHeight - $horizontalHandle.height();
        $mainContainer.css('height', mainContainerHeight + 'px');
        
        // Main image aspect ratio - always fill available space
        $mainPane.find('img').css({
            'width': '100%',
            'height': '100%',
            'object-fit': 'contain',
            'object-position': 'center'
        });
        
        // Thumbnail aspect ratios
        const availableHeight = $thumbnailsContent.height();
        $thumbnails.each(function() {
            const thumbWidth = availableHeight * thumbRatio;
           // $(this).css('width', thumbWidth + 'px');
        });
    }
    
    // Call initial setup
    setupInitialAspectRatios();
    
    // Handle window resize
    $(window).on('resize', setupInitialAspectRatios);
}

// Add this function for generating filmstrip thumbnails
function generateFilmstripThumbnails() {
    const colors = ['333333', '444444', '555555', '666666', '777777', '888888'];
    let thumbnails = '';
    
    // Generate 12 placeholder thumbnails with proper aspect ratio
    for (let i = 1; i <= 12; i++) {
        const color = colors[i % colors.length];
        thumbnails += `
            <div class="flex-none group">
                <div class="h-full bg-black/60 overflow-hidden relative cursor-pointer hover:ring-2 hover:ring-yellow-300 transition-all duration-200 ${i === 1 ? 'ring-2 ring-yellow-300' : ''}">
                    <img src="https://placehold.co/1920x1080/${color}/FFFFFF/png?text=Take+${i}" 
                         alt="Thumbnail ${i}"
                         class="w-full h-full object-cover"
                         loading="lazy">
                    <div class="absolute bottom-0 left-0 right-0 bg-black/80 text-white text-xs py-1 px-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        Take ${i}
                    </div>
                </div>
            </div>
        `;
    }
    
    return thumbnails;
}

// Add this function to handle thumbnail interactions
function initializeThumbnailHandlers($takeCard) {
    const $thumbnails = $takeCard.find('#thumbnailsPane .flex-none');
    const $mainImage = $takeCard.find('#mainImagePane img');
    const $filmstripScroll = $takeCard.find('.filmstrip-scroll');
    
    // Set first thumbnail as selected by default
    const $firstThumbnail = $thumbnails.first();
    $firstThumbnail.find('div').first().addClass('ring-2 ring-yellow-300');
    
    // Add horizontal scroll with mouse wheel
    $filmstripScroll.on('wheel', function(e) {
        e.preventDefault();
        
        // Get scroll amount from wheel delta
        const scrollAmount = e.originalEvent.deltaY || e.originalEvent.deltaX;
        
        // Scroll horizontally with smooth animation
        $(this).stop().animate({
            scrollLeft: $(this).scrollLeft() + (scrollAmount * 1.5)
        }, 50);
    });
    
    // Existing thumbnail click handler
    $thumbnails.on('click', function() {
        // Remove highlight from all thumbnails
        $thumbnails.find('.ring-2').removeClass('ring-2 ring-yellow-300');
        
        // Add highlight to clicked thumbnail
        $(this).find('div').first().addClass('ring-2 ring-yellow-300');
        
        // Get the thumbnail number and create a larger version URL
        const thumbnailSrc = $(this).find('img').attr('src');
        const takeNumber = thumbnailSrc.match(/Take\+(\d+)/)[1];
        const color = thumbnailSrc.match(/\/([0-9a-f]{6})\//)[1];
        
        // Create high-res version URL
        const mainImageSrc = `https://placehold.co/1920x1080/${color}/FFFFFF/png?text=Take+${takeNumber}`;
        
        // Update main image with loading state
        $mainImage.css('opacity', '0.5').css('transition', 'opacity 0.3s ease');
        const newImage = new Image();
        newImage.onload = function() {
            $mainImage
                .attr('src', mainImageSrc)
                .css('opacity', '1')
                .css({
                    'width': '100%',
                    'height': '100%',
                    'object-fit': 'contain',
                    'object-position': 'center'
                });
        };
        newImage.src = mainImageSrc;
    });
}

// Replace the initializeTakeNameEditor function
function initializeTakeNameEditor($takeCard, takeId, initialTitle) {
    const $titleDisplay = $takeCard.find('.take-title-display');
    const $titleInput = $takeCard.find('.take-title-input');
    const $editButton = $titleDisplay.siblings('button');

    // Ensure initial values are set
    $titleDisplay.text(initialTitle);
    $titleInput.val(initialTitle);

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

        // Save the new title with proper data formatting
        $.ajax({
            url: esperApi.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'esper_update_take',
                nonce: esperApi.nonce,
                take_id: takeId,
                title: newTitle,
                post_type: 'take'
            }
        }).then(response => {
            if (response && response.success) {
                // Update title display
                $titleDisplay.text(newTitle);
                
                // Update folder tree item title
                const $folderItem = $(`.folder-item[data-id="${takeId}"]`);
                $folderItem.find('.text-white.truncate').text(newTitle);
                
                // Update main image text
                const mainImageUrl = `https://placehold.co/1920x1080/333333/FFFFFF/png?text=${encodeURIComponent(newTitle)}`;
                $('#mainImagePane img').attr('src', mainImageUrl);
                
                showSuccess('Take name updated successfully');
            } else {
                const errorMsg = response && response.data ? response.data : 'Failed to update take name';
                $titleDisplay.text($titleDisplay.data('original-title') || initialTitle); // Revert to original
                showError(errorMsg);
            }
        }).catch(error => {
            console.error('Error updating take name:', error);
            $titleDisplay.text($titleDisplay.data('original-title') || initialTitle); // Revert to original
            showError('Failed to update take name. Please try again.');
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