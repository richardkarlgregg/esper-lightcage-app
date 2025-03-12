<?php get_header(); ?>

<div class="flex" style="height: calc(100vh - 40px);">
    <!-- Left Pane: Folder Tree -->
    <div id="sidebar" class="resizable bg-black border-r border-white border-opacity-10 p-4" style="width: 250px;">
        <ul id="folderTree" class="text-sm text-yellow-300 space-y-2">
        </ul>
    </div>

    <!-- Draggable Divider -->
    <div id="divider" class="divider w-1 hover:w-4 bg-black transition hover:bg-yellow-300"></div>

    <!-- Right Pane: Content Area -->
    <div id="content" class="flex-1 bg-black overflow-auto">
    </div>
</div>


<?php get_footer(); ?> 