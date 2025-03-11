<?php get_header(); ?>

<div class="flex h-screen">
    <!-- Left Pane: Folder Tree -->
    <div id="sidebar" class="resizable bg-black p-4" style="width: 250px;">
        <ul id="folderTree" class="space-y-2">
        </ul>
    </div>

    <!-- Draggable Divider -->
    <div id="divider" class="divider bg-yellow-300"></div>

    <!-- Right Pane: Content Area -->
    <div id="content" class="flex-1 bg-black bg-opacity-10 overflow-auto">
    </div>
</div>


<?php get_footer(); ?> 