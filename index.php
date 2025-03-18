<?php get_header(); ?>

<div class="flex" style="height: calc(100vh - 40px);">
    <!-- Left Pane: Folder Tree -->
    <div id="sidebar" class="scrollbar resizable bg-black border-r border-white border-opacity-10 p-2" style="width: 250px;">
        <ul id="folderTree" class="text-sm text-esper-yellow space-y-2">
        </ul>
    </div>

    <!-- Draggable Divider -->
    <div id="divider" class="divider w-1 hover:w-4 bg-black transition hover:bg-esper-yellow"></div>

    <!-- Right Pane: Content Area -->
    <div id="content" class="flex-1 bg-black overflow-auto scrollbar">
    </div>
</div>


<?php get_footer(); ?> 