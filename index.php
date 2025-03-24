<?php get_header(); ?>

<div class="flex xl:hidden w-full h-screen fixed top-0 left-0 bg-black z-50 items-center content-center"><span class="w-full text-center opacity-50 text-white uppercase text-2xl">Please view on a larger screen</span> </div>

<div class="flex" style="height: calc(100vh - 40px);">

    <div id="sideMenu" class="hidden w-14 h-full flex flex-wrap justify-center content-start border-r p-3 border-color-white border-opacity-10">
        <span class="material-icons w-6 h-6 text-esper-yellow flex-none">folder</span>
        <span class="material-icons w-6 h-6 text-esper-yellow opacity-75 flex-none">file_export</span>
    </div>

    <!-- Left Pane: Folder Tree -->
    <div id="sidebar" class="scrollbar resizable bg-black border-r border-white border-opacity-10 p-2" style="width:330px;">
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