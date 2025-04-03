import $ from 'jquery';
import 'jquery-ui/ui/widgets/datepicker';

import store from './Store.js';

export default class TakeManager {
    constructor() {
        // Track resizing state
        this.isResizing = false;
        this.currentHandle = null;
        this.startX = 0;
        this.startY = 0;
        this.startWidth = 0;
        this.startHeight = 0;
    }

    /**
     * Sets up all main event listeners.
     */
    setupEventListeners() {
       
    }

    /**
     * Initializes drag-to-resize behavior on vertical/horizontal handles in the Take card.
     */
    initializeResizeHandlers($takeCard) {
        // Cache elements
        const $verticalHandle   = $takeCard.find('#verticalResizeHandle');
        const $horizontalHandle = $takeCard.find('#horizontalResizeHandle');
        const $rightSidebar     = $takeCard.find('#rightSidebarPane');
        const $mainPane         = $takeCard.find('#mainImagePane');
        const $thumbnailsPane   = $takeCard.find('#thumbnailsPane');
        const $mainContainer    = $takeCard.find('#takePanesContainer');
        const $thumbnailsContent = $thumbnailsPane.find('.flex-1');
        const $thumbnailsContainer = $thumbnailsContent.find('.flex');

        // Ensure horizontal handle has a fixed height
        $horizontalHandle.css({ height: '4px', minHeight: '4px' });

        // Begin vertical resize
        $verticalHandle.on('mousedown', (e) => {
            this.isResizing = true;
            this.currentHandle = 'vertical';
            this.startX = e.clientX;
            this.startWidth = $rightSidebar.width();
            e.preventDefault();
        });

        // Begin horizontal resize
        $horizontalHandle.on('mousedown', (e) => {
            this.isResizing = true;
            this.currentHandle = 'horizontal';
            this.startY = e.clientY;
            this.startHeight = $thumbnailsPane.height();
            e.preventDefault();
        });

        // Mousemove handles the actual resize
        $(document).on('mousemove', (e) => {
            if (!this.isResizing) return;

            if (this.currentHandle === 'vertical') {
                // Restrict width between 200-500
                const width = Math.max(200, Math.min(500, this.startWidth + (this.startX - e.clientX)));
                $rightSidebar.css('width', `${width}px`);

                // Keep main image filling space
                $mainPane.find('img').css({
                    width: '100%',
                    height: '100%',
                    objectFit: 'contain',
                    objectPosition: 'center'
                });
            } 
            else if (this.currentHandle === 'horizontal') {
                const containerHeight = $takeCard.height();
                const handleHeight = $horizontalHandle.height();
                // Restrict height between 100-300
                const newHeight = Math.max(100, Math.min(300, this.startHeight + (this.startY - e.clientY)));

                // Adjust thumbnails and main pane
                $thumbnailsPane.css('height', `${newHeight}px`);
                $mainContainer.css('height', `${containerHeight - newHeight - handleHeight}px`);

                // Account for any top toolbar inside the thumbnails pane
                const toolbarHeight = $thumbnailsPane.find('.bg-black').outerHeight();
                const availableHeight = newHeight - toolbarHeight - 16; // 16px for padding
                $thumbnailsContent.css('height', `${availableHeight}px`);

                // Force each thumbnail's img to fill
                $thumbnailsContainer.find('.flex-none img').css({
                    width: '100%',
                    height: '100%',
                    objectFit: 'cover'
                });

                // Reflow main image
                requestAnimationFrame(() => {
                    $mainPane.find('img').css({
                        width: '100%',
                        height: '100%',
                        objectFit: 'contain',
                        objectPosition: 'center'
                    });
                });
            }
        });

        // Mouseup finalizes the resize
        $(document).on('mouseup', () => {
            if (this.isResizing) {
                requestAnimationFrame(() => {
                    $mainPane.find('img').css({
                        width: '100%',
                        height: '100%',
                        objectFit: 'contain',
                        objectPosition: 'center'
                    });
                });
            }
            this.isResizing = false;
            this.currentHandle = null;
        });

        // Initial layout
        const setupInitialAspectRatios = () => {
            const containerHeight = $takeCard.height();
            const thumbsHeight    = $thumbnailsPane.height();
            const mainContainerHeight = containerHeight - thumbsHeight - $horizontalHandle.height();
            $mainContainer.css('height', `${mainContainerHeight}px`);

            // Main image aspect ratio (fill space)
            $mainPane.find('img').css({
                width: '100%',
                height: '100%',
                objectFit: 'contain',
                objectPosition: 'center'
            });
        };

        setupInitialAspectRatios();
        $(window).on('resize', setupInitialAspectRatios);
    }

    /**
     * Simple helper to generate placeholder thumbnails (for demonstration).
     */
    generateFilmstripThumbnails() {
        const colors = ['333333', '444444', '555555', '666666', '777777', '888888'];
        let thumbnails = '';
        
        // Generate 12 placeholders
        for (let i = 1; i <= 12; i++) {
            const color = colors[i % colors.length];
            thumbnails += `
                <div class="flex-none group">
                    <div class="h-full bg-black/60 overflow-hidden relative cursor-pointer 
                                hover:ring-2 hover:ring-esper-yellow transition-all duration-200 
                                ${i === 1 ? 'ring-2 ring-esper-yellow' : ''}">
                        <img src="https://placehold.co/1920x1080/${color}/FFFFFF/png?text=${i}" 
                             alt="Thumbnail ${i}"
                             class="w-full h-full object-cover"
                             loading="lazy">
                        <div class="absolute bottom-0 left-0 right-0 bg-black/80 text-white text-xs py-1 px-2 
                                   opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            ${i}
                        </div>
                    </div>
                </div>
            `;
        }
        return thumbnails;
    }

    /**
     * Sets up event handlers on thumbnails, including context menu, multi-select, etc.
     */
    initializeThumbnailHandlers($takeCard) {

        /**
         * Updates the "selected" true/false field in ACF via AJAX.
         *
         * @param {number} imageId
         * @param {boolean} isSelected
         */
        function updateSelectedState(imageId, isSelected) {
            $.ajax({
                url: esperApi.ajaxurl,
                type: 'POST',
                data: {
                    action: 'esper_update_image_selected',  // Name your action
                    nonce: esperApi.nonce,
                    image_id: imageId,
                    selected: isSelected ? 1 : 0,           // ACF true/false fields are typically 1 or 0
                },
                success: (response) => {
                    if (!response.success) {
                        console.error('Failed to update selection state:', response);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error:', error);
                }
            });
        }

        const $filmstripScroll = $takeCard.find('.filmstrip-scroll');
        const $thumbnails      = $takeCard.find('#thumbnailsPane .flex-none');
        const $mainImage       = $takeCard.find('#mainImagePane img');

        // Pre-select the first
        const $firstThumbnail = $thumbnails.first();
        $firstThumbnail.find('div').first().addClass('ring-2 ring-esper-yellow');

        // Horizontal scroll with mouse wheel
        $filmstripScroll.on('wheel', function(e) {
            e.preventDefault();
            const scrollAmount = e.originalEvent.deltaY || e.originalEvent.deltaX;
            $(this).stop().animate({
                scrollLeft: $(this).scrollLeft() + (scrollAmount * 1.5)
            }, 50);
        });

        // Build a context menu element
        const $contextMenu = $(`
            <div class="context-menu bg-black border border-white border-opacity-20 rounded-lg shadow-lg p-2 hidden">
                <div class="text-white text-sm mb-2">Set Rating:</div>
                <div class="flex space-x-2 mb-2">
                    <button class="rating-btn bg-green-500 hover:bg-green-600 w-8 h-8 rounded-full" data-rating="green"></button>
                    <button class="rating-btn bg-yellow-500 hover:bg-yellow-600 w-8 h-8 rounded-full" data-rating="yellow"></button>
                    <button class="rating-btn bg-red-500 hover:bg-red-600 w-8 h-8 rounded-full" data-rating="red"></button>
                </div>
                <div class="border-t border-white border-opacity-20 my-2"></div>
                <button class="export-selected text-white text-sm hover:text-esper-yellow w-full text-left px-2 py-1 rounded">
                    Export Selected
                </button>
            </div>
        `).appendTo('body');

        /**
         * Context menu - Right-click
         */
        $thumbnails.on('contextmenu', function(e) {
            e.preventDefault();
            const takeId  = $(this).closest('.take-review').data('take-id');
            const imageId = $(this).data('image-id');
            const rect    = this.getBoundingClientRect();

            $contextMenu
                .css({
                    position: 'fixed',
                    left: rect.left,
                    top:  rect.top - $contextMenu.outerHeight() - 5, // 5px gap
                    zIndex: 1000
                })
                .removeClass('hidden')
                .data('take-id', takeId)
                .data('image-id', imageId);
        });

        /**
         * Export selected from context menu
         */
        $contextMenu.on('click', '.export-selected', () => {
            const takeId = $('.take-review').data('take-id');

            if (!takeId) {
                alert('Error: Could not find take ID');
                return;
            }
            // Gather selected thumbs
            const selectedThumbnails = $thumbnails.filter(function() {
                return $(this).find('div').first().hasClass('ring-2');
            });
            if (selectedThumbnails.length === 0) {
                alert('Please select at least one thumbnail to export');
                return;
            }

            // Collect image IDs
            const imageIds = selectedThumbnails.map(function() {
                return $(this).data('image-id');
            }).get();

            // Build extra IDs from store
            const jobId     = store.navigationManager.getPostIdByCriteria('job');
            const captureId = store.navigationManager.getPostIdByCriteria('capture');
            const sessionId = store.navigationManager.getPostIdByCriteria('session');

            // Create export post
            $.ajax({
                url: esperApi.ajaxurl,
                type: 'POST',
                data: {
                    action: 'esper_create_export',
                    nonce: esperApi.nonce,
                    take_id: takeId,
                    image_ids: imageIds,
                    job_id: jobId,
                    capture_id: captureId,
                    session_id: sessionId
                },
                success: (response) => {
                    if (response.success) {
                        //alert('Export created successfully!');

                        console.log('Show export manager');
                        $('#sideMenu span').addClass('opacity-25');
                        $('[data-action="export"]').removeClass('opacity-25');
                        $('#sidebar').hide();
                        $('#divider').hide();

                        store.navigationManager.pushScreen( $('.menu-bar [data-action="export"]').data('id'), 'export', null );
                    } else {
                        console.error('Failed to create export:', response);
                        alert(response.data.message || 'Failed to create export. Please try again.');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error:', error);
                    alert('An error occurred while creating the export.');
                }
            });

            $contextMenu.addClass('hidden');
        });

        /**
         * Handle rating button clicks
         */
        $contextMenu.on('click', '.rating-btn', function() {
            const rating = $(this).data('rating');
            const takeId = store.navigationManager.getPostIdByCriteria('take');

            if (!takeId) {
                alert('Error: Could not find take ID');
                return;
            }

            // Find selected thumbs or fallback to the right-clicked single
            const $selectedThumbnails = $thumbnails.filter(function() {
                return $(this).find('div').first().hasClass('ring-2');
            });

            if ($selectedThumbnails.length === 0) {
                const imageId = $contextMenu.data('image-id');
                updateThumbnailRating([{ image_id: imageId, rating }], takeId);
            } else {
                const thumbsData = $selectedThumbnails.map(function() {
                    return {
                        image_id: $(this).data('image-id'),
                        rating
                    };
                }).get();
                updateThumbnailRating(thumbsData, takeId);
            }
            $contextMenu.addClass('hidden');
        });

        /**
         * Updates thumbnail rating on the server
         */
        const updateThumbnailRating = (thumbnailsData, takeId) => {
            $.ajax({
                url: esperApi.ajaxurl,
                type: 'POST',
                data: {
                    action: 'esper_update_image_rating',
                    nonce: esperApi.nonce,
                    take_id: takeId,
                    thumbnails: thumbnailsData
                },
                success: (response) => {
                    if (response.success) {
                        // Add or replace rating indicators
                        thumbnailsData.forEach((data) => {
                            const $thumb = $thumbnails.filter(`[data-image-id="${data.image_id}"]`);
                            $thumb.find('.rating-indicator').remove();
                            $thumb.find('div').first().append(`
                                <div class="rating-indicator absolute top-2 right-2 w-3 h-3 rounded-full bg-${data.rating}-500"></div>
                            `);
                        });
                    } else {
                        console.error('Failed to update ratings:', response);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error:', error);
                }
            });
        };

        // Hide context menu when clicking outside
        $(document).on('click', (e) => {
            if (!$(e.target).closest('.context-menu, .flex-none').length) {
                $contextMenu.addClass('hidden');
            }
        });

        /**
         * Click on thumbnail to handle multi-select or single-select
         */
        $thumbnails.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const $thumbnail = $(this);
            const $thumbDiv  = $thumbnail.find('div').first();

            // Multi-select if Ctrl/Cmd
            const isMultiSelect = e.ctrlKey || e.metaKey;
            const isShiftSelect = e.shiftKey;
            const clickedIndex  = $thumbnails.index($thumbnail);

            if (isShiftSelect) {
                // Range selection
                const $lastSelected = $thumbnails.find('div.ring-2').closest('.flex-none');
                if ($lastSelected.length) {
                    const lastIndex = $thumbnails.index($lastSelected);
                    const start = Math.min(clickedIndex, lastIndex);
                    const end   = Math.max(clickedIndex, lastIndex);
                    $thumbnails.slice(start, end + 1).each(function() {
                        $(this).find('div').first().addClass('ring-2 ring-esper-yellow');

                        // IMPORTANT: also call the Ajax helper
                        updateSelectedState($(this).data('image-id'), true);
                    });
                } else {
                    $thumbDiv.addClass('ring-2 ring-esper-yellow');

                    updateSelectedState($thumbnail.data('image-id'), true);
                }
            } 
            else if (!isMultiSelect) {
               // Single select: remove ring from all, add to the clicked
                $thumbnails.find('.ring-2').each(function() {
                    const $div = $(this);
                    $div.removeClass('ring-2 ring-esper-yellow');
                    
                    // The parent .flex-none has the data('image-id')
                    const imageId = $div.closest('.flex-none').data('image-id');
                    updateSelectedState(imageId, false);
                });

                $thumbDiv.addClass('ring-2 ring-esper-yellow');
                updateSelectedState($thumbnail.data('image-id'), true);
            } 
            else {
                // Toggle multi-select
                const wasSelected = $thumbDiv.hasClass('ring-2');
                $thumbDiv.toggleClass('ring-2 ring-esper-yellow');
                updateSelectedState($thumbnail.data('image-id'), !wasSelected);
            }

            // Update main image with higher-res version
            const thumbnailSrc = $thumbnail.find('img').attr('src');
            const numberMatch  = thumbnailSrc.match(/text=(\d+)/);
            const colorMatch   = thumbnailSrc.match(/\/([0-9a-f]{6})\//);
            if (!numberMatch || !colorMatch) return;

            const number = numberMatch[1];
            const color  = colorMatch[1];
            const mainImageSrc = `https://placehold.co/1920x1080/${color}/FFFFFF/png?text=${number}`;

            // Show loading fade
            $mainImage.css({ opacity: 0.5, transition: 'opacity 0.3s ease' });
            const newImage = new Image();
            newImage.onload = () => {
                $mainImage
                    .attr('src', mainImageSrc)
                    .css({
                        opacity: 1,
                        width: '100%',
                        height: '100%',
                        objectFit: 'contain',
                        objectPosition: 'center'
                    });
            };
            newImage.src = mainImageSrc;
        });

        /**
         * Rating filter functionality
         */
        const filterButtons = document.querySelectorAll('.rating-filter');
        
        // Set 'All' as default active state
        const defaultFilter = document.querySelector('.rating-filter[data-rating="all"]');
        defaultFilter.classList.remove('bg-opacity-20', 'text-gray-500');
        defaultFilter.classList.add('bg-opacity-50', 'bg-white', 'text-black');
        
        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const rating = button.dataset.rating;

                // Toggle button states
                filterButtons.forEach((btn) => {
                    btn.classList.remove('bg-opacity-50', 'text-black');
                    btn.classList.add('bg-opacity-20');
                    
                    // Remove any background color classes
                    btn.classList.remove('bg-white', 'bg-green-500', 'bg-yellow-500', 'bg-red-500');
                    
                    // Restore original text colors
                    if (btn.dataset.rating === 'green') {
                        btn.classList.add('text-green-500');
                    } else if (btn.dataset.rating === 'yellow') {
                        btn.classList.add('text-yellow-500');
                    } else if (btn.dataset.rating === 'red') {
                        btn.classList.add('text-red-500');
                    } else if (btn.dataset.rating === 'all') {
                        btn.classList.add('text-gray-500');
                    }
                });
                
                // Set active state with appropriate background color
                button.classList.remove('bg-opacity-20');
                button.classList.add('bg-opacity-50');
                
                // Add background color based on rating
                if (rating === 'all') {
                    button.classList.add('bg-white', 'text-black');
                    button.classList.remove('text-gray-500');
                } else {
                    button.classList.add(`bg-${rating}-500`);
                }

                const allThumbs = document.querySelectorAll('.filmstrip-scroll .flex-none');
                allThumbs.forEach((thumb) => {
                    const ratingIndicator = thumb.querySelector('.rating-indicator');
                    if (
                        rating === 'all' ||
                        (ratingIndicator && ratingIndicator.classList.contains(`bg-${rating}-500`))
                    ) {
                        thumb.style.display = '';
                    } else {
                        thumb.style.display = 'none';
                        // Deselect hidden thumbs
                        $(thumb).find('div').first().removeClass('ring-2 ring-esper-yellow');
                    }
                });

                // Update "X images" text
                const visibleThumbs = document.querySelectorAll('.filmstrip-scroll .flex-none[style=""]')
                    .length;
                const countDisplay = document
                    .querySelector('.filmstrip-scroll')
                    .closest('.h-full')
                    .querySelector('.text-gray-400');
                countDisplay.textContent = `${visibleThumbs} images`;
            });
        });

        /**
         * Select all visible thumbnails
         */
        $('.select-all-visible').on('click', () => {
            const $visibleThumbs = $('.filmstrip-scroll .flex-none').filter(function() {
                return $(this).css('display') !== 'none';
            });
            $visibleThumbs.each(function() {
                $(this).find('div').first().addClass('ring-2 ring-esper-yellow');
            });
        });
    }
}
