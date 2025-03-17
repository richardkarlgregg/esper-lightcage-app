import $ from 'jquery';
import 'jquery-ui/ui/widgets/datepicker';

import store from './Store.js';

export default class TakeManager {
    constructor() {
        this.init();
    }

    init() {
    }

    setupEventListeners() {

    }

    // Add this function near the bottom of the file
initializeResizeHandlers($takeCard) {
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

    // Ensure handle maintains its height
    $horizontalHandle.css('height', '4px').css('min-height', '4px');
    
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
            const handleHeight = $horizontalHandle.height();
            const newHeight = Math.max(100, Math.min(300, startHeight + (startY - e.clientY)));
            
            
            
            // Update thumbnails pane height
            $thumbnailsPane.css('height', newHeight + 'px');
            
            // Update main container height to fill remaining space while accounting for handle height
            const mainContainerHeight = containerHeight - newHeight - handleHeight;
            $mainContainer.css('height', mainContainerHeight + 'px');
            
            // Calculate available height for thumbnails (subtract toolbar height)
            const toolbarHeight = $thumbnailsPane.find('.bg-black').outerHeight();
            const availableHeight = newHeight - toolbarHeight - 16; // 16px for padding
            
            // Update thumbnails container height
            $thumbnailsContent.css('height', availableHeight + 'px');
            
            // Update thumbnail dimensions
            $thumbnails.each(function() {
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
 generateFilmstripThumbnails() {
    const colors = ['333333', '444444', '555555', '666666', '777777', '888888'];
    let thumbnails = '';
    
    // Generate 12 placeholder thumbnails with proper aspect ratio
    for (let i = 1; i <= 12; i++) {
        const color = colors[i % colors.length];
        thumbnails += `
            <div class="flex-none group">
                <div class="h-full bg-black/60 overflow-hidden relative cursor-pointer hover:ring-2 hover:ring-esper-yellow transition-all duration-200 ${i === 1 ? 'ring-2 ring-esper-yellow' : ''}">
                    <img src="https://placehold.co/1920x1080/${color}/FFFFFF/png?text=${i}" 
                         alt="Thumbnail ${i}"
                         class="w-full h-full object-cover"
                         loading="lazy">
                    <div class="absolute bottom-0 left-0 right-0 bg-black/80 text-white text-xs py-1 px-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        ${i}
                    </div>
                </div>
            </div>
        `;
    }
    
    return thumbnails;
}

// Add this function to handle thumbnail interactions
initializeThumbnailHandlers($takeCard) {
    const $thumbnails = $takeCard.find('#thumbnailsPane .flex-none');
    const $mainImage = $takeCard.find('#mainImagePane img');
    const $filmstripScroll = $takeCard.find('.filmstrip-scroll');
    
    // Set first thumbnail as selected by default
    const $firstThumbnail = $thumbnails.first();
    $firstThumbnail.find('div').first().addClass('ring-2 ring-esper-yellow');
    
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
        $thumbnails.find('.ring-2').removeClass('ring-2 ring-esper-yellow');
        
        // Add highlight to clicked thumbnail
        $(this).find('div').first().addClass('ring-2 ring-esper-yellow');
        
        // Get the thumbnail number and create a larger version URL
        const thumbnailSrc = $(this).find('img').attr('src');
        const number = thumbnailSrc.match(/text=(\d+)/)[1];
        const color = thumbnailSrc.match(/\/([0-9a-f]{6})\//)[1];
        
        // Create high-res version URL
        const mainImageSrc = `https://placehold.co/1920x1080/${color}/FFFFFF/png?text=${number}`;
        
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

}