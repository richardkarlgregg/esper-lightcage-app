import $ from 'jquery';
import 'jquery-ui/ui/widgets/datepicker';
import store from './Store.js';

export default class UIManager {
    constructor() {
        this.init();
    }

    init() {
    }

    setupEventListeners() {
        $(document).on('click', '[data-action]', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const action = $(this).data('action');
            switch (action) {
                case 'openScreen':
                    const postId = $(this).data('id');
                    const postType = $(this).data('type');
                    const context = $(this).data('context');
                    store.navigationManager.pushScreen( postId, postType, context );
                    break;
                case 'back':
                    // Handle action2
                    store.navigationManager.popScreen();
                    break;
                // Add more cases as needed
                default:
                    console.log('Unknown action:', action);
            }
        });
    }

    initFolderTree() {
        const self = this; // Capture the UIManager instance
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
            store.navigationManager.pushScreen(postId, postType);
            $('.folder-item').removeClass('bg-esper-yellow bg-opacity-10');
            $(this).addClass('bg-esper-yellow bg-opacity-10');
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
                        'class': 'pl-3 mt-1 space-y-1 hidden'
                    });
                    parentItem.append($childrenContainer);
                }
                
                const newLabel = childType.charAt(0).toUpperCase() + childType.slice(1) + ' ' + (childCount + 1);
                const response = await store.postManager.createPost(childType, newLabel, parentType, parentId);
                
                if (response.success && response.data.id) {
                    // Show parent's arrow
                    const $arrow = parentItem.find('.material-icons').first();
                    $arrow.removeClass('invisible');
    
                    // Expand parent by removing hidden class
                    $childrenContainer.removeClass('hidden');
                    $arrow.addClass('rotate-90');
    
                    // Add the new item
                    const newItem = self.createFolderItem({
                        id: response.data.id,
                        title: newLabel,
                        type: childType,
                        children: []
                    });
                    $childrenContainer.append(newItem);
    
                    // Load the new capture or session
                    store.navigationManager.pushScreen(response.data.id, childType);
                    $('.folder-item').removeClass('bg-esper-yellow bg-opacity-10');
                    $(`[data-id="${response.data.id}"]`).addClass('bg-esper-yellow bg-opacity-10');

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
                    const response = await store.postManager.createPost('job', newJobLabel);
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
    
            // About
            $('#aboutBtn').on('click', function() {
                $('#content').html(`
                    <div class="flex flex-col items-center justify-center h-full text-gray-500">
                        <p class="text-lg">Not potato farmers</p>
                    </div>
                `);
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
                                <svg class="w-6 h-6 mr-2 text-esper-yellow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                await store.navigationManager.pushScreen(jobId, 'job');
                
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
                        $('#folderTree').append(self.createFolderItem(job));
                    }
                }
            } catch (error) {
                console.error('Error loading job:', error);
                store.notificationManager.showError('Error loading job');
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
                        <button id="welcomeOpenJob" class="bg-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded">
                            Open a Job
                        </button>
                    </div>
                `);
    
                $('#welcomeOpenJob').on('click', function() {
                    $('#openJobBtn').click();
                });
            }
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

    // Create a folder item element with its children
    createFolderItem(item) {
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
            .addClass('material-icons w-4 flex-none text-esper-yellow transform transition-transform duration-200 ' + 
                ((!item.children || item.children.length === 0) ? 'invisible' : ''))
            .text('chevron_right');
        $header.append($arrow);

        // Add icon based on type
        const iconType = item.type === 'job' ? 'folder' :
                        item.type === 'session' ? 'calendar_today' :
                        item.type === 'capture' ? 'camera_alt' :
                        'movie';

        const $icon = $('<span>', {
            'class': 'material-icons w-6 h-6 text-esper-yellow flex-none',
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
                'class': 'add-btn ml-2 flex items-center text-black/60 hover:text-esper-yellow opacity-0 group-hover:opacity-100 transition-opacity duration-200 absolute right-2',
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
                'class': 'add-btn ml-2 flex items-center text-black/60 hover:text-esper-yellow opacity-0 group-hover:opacity-100 transition-opacity duration-200 absolute right-2',
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
                'class': 'pl-3 mt-1 space-y-1 hidden'
            });
            
            item.children.forEach(child => {
                $children.append(this.createFolderItem(child));
            });
            
            $item.append($children);
        }

        return $item;
    }
}