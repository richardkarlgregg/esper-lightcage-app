<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <style>
        /* Resizable left pane */
        .resizable {
            resize: horizontal;
            overflow: auto;
            min-width: 200px;
            max-width: 500px;
        }
        /* Divider style for dragging */
        .divider {
            width: 4px;
            cursor: col-resize;
        }
        /* Menu styles */
        .menu-bar {
            user-select: none;
        }
        .menu-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 200px;
            z-index: 50;
        }
        .menu-item:hover .menu-dropdown {
            display: block;
        }
        /* Modal styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            z-index: 100;
        }
        .modal {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            min-width: 400px;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        /* Connection status indicator */
        .connection-status {
            width: 12px;
            height: 12px;
            background-color: green; /* Default to green */
            border-radius: 50%;
            margin-left: 8px;
        }


    </style>
</head>
<body <?php body_class('bg-black text-white h-screen overflow-hidden'); ?>>
<?php wp_body_open(); ?>

<!-- Menu Bar -->
<div class="menu-bar bg-black border-b border-esper-yellow text-white px-4 py-1 flex items-center justify-between space-x-4">
    <div class="w-auto flex flex-wrap items-center">
        <img src="<?php echo get_theme_file_uri('assets/images/esper-logo.svg'); ?>" alt="Esper Logo" class="h-4 mr-2">
        <div class="menu-item relative">
            <button class="hover:bg-esper-yellow px-3 py-0 rounded">File</button>
            <div class="menu-dropdown bg-black roundedx border border-white border-opacity-10 shadow-lg py-1">
                <button id="openJobBtn" class="w-full text-left px-4 py-2 hover:bg-esper-yellow hover:text-black flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"></path>
                    </svg>
                    Open Job...
                </button>
                <button id="newJobBtn" class="w-full text-left px-4 py-2 hover:bg-esper-yellow hover:text-black flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    New Job
                </button>
                <div class="border-t border-gray-700 my-1"></div>
                <button id="closeJobBtn" class="w-full text-left px-4 py-2 hover:bg-esper-yellow hover:text-black flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Close Job
                </button>
                <div class="border-t border-gray-700 my-1"></div>
                <button id="aboutBtn" class="w-full text-left px-3 py-0 hover:bg-esper-yellow hover:text-black flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    About
                </button>
            </div>
        </div>
    </div>

    <?php
        $current_user_id = get_current_user_id();
        $exportID = null;
        $exportHandler = new ExportHandler();
    
        // Retrieve export posts for the current user.
        $exports = $exportHandler->getExportsByUser($current_user_id);

        //echo '<pre>';
        //print_r($exports);
        //echo '</pre>';

        
    
        // If there is at least one export, get the first (most recent) one.
        if (!empty($exports)) {
            $exportID = $exports[0]->ID;
        } else {
            // No export found; create a new export post.
            $new_post_id = $exportHandler->createExport('New Export Title', $current_user_id);
            if ($new_post_id) {
                $exportID = $new_post_id;
            }
            
        }
    ?>
    <!-- Connection Status Indicator -->
    <div class="flex items-center space-x-4 ml-auto">
        <div class="uppercase text-white">Jobs</div>
        <div data-type="export" data-id="<?php echo $exportID;?>" data-action="export" class="uppercase text-white">Export</div>
         <div class="connection-status"></div>
         <!-- Simulated window icons -->
        <span class="opacity-25 transition cursor-pointer hover:opacity-100 material-icons window-icon" id="minimizeWindow">remove</span>
        <span class="opacity-25 transition cursor-pointer hover:opacity-100 material-icons window-icon" id="maximizeWindow">crop_square</span>
        <span class="opacity-25 transition cursor-pointer hover:opacity-100 material-icons window-icon" id="closeWindow">close</span>
    </div>
</div>

<!-- Job Selection Modal -->
<div id="jobModal" class="modal-overlay">
    <div class="modal bg-black rounded-lg shadow-xl">
        <div class="flex justify-between items-center p-4 border-b border-gray-700">
            <h2 class="text-lg font-semibold">Open Job</h2>
            <button id="closeModal" class="text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-4">
            <div class="mb-4">
                <input type="text" id="jobSearch" placeholder="Search jobs..." class="w-full bg-black text-white px-3 py-2 border border-gray-600 focus:border-esper-yellow focus:outline-none">
            </div>
            <ul id="jobList" class="space-y-1 max-h-60 overflow-y-auto">
                <!-- Jobs will be populated here -->
            </ul>
        </div>
    </div>
</div>

