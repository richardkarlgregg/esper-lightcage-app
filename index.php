<?php get_header(); ?>

<div class="flex h-screen">
    <!-- Left Pane: Folder Tree -->
    <div id="sidebar" class="resizable bg-black p-4" style="width: 250px;">
        <h2 class="text-lg font-bold mb-4" translate="no">Esper</h2>
        <ul id="folderTree" class="space-y-2">

        </ul>
    </div>

    <!-- Draggable Divider -->
    <div id="divider" class="divider bg-yellow-300"></div>

    <!-- Right Pane: Content Area -->
    <div id="content" class="flex-1 bg-white bg-opacity-10 p-4 overflow-auto">
    </div>
</div>


<?php get_footer(); ?> 