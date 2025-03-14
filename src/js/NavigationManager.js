import $ from 'jquery';
import store from './Store.js';

export default class NavigationManager {
    constructor() {
        this.stack = [];
    }

    // Update the navigation stack from the folder tree hierarchy.
    // Accepts a jQuery element representing the clicked folder item.
    updateHierarchyFromFolderItem($folderItem) {
        const hierarchy = [];
        let $currentItem = $folderItem.closest('.folder-item');
        while ($currentItem.length) {
            const postId = $currentItem.data('id');
            const postType = $currentItem.data('type');
            hierarchy.unshift({ postId, postType });
            $currentItem = $currentItem.parent().closest('.folder-item');
        }
        this.stack = hierarchy;
    }

    // Push a new screen onto the stack.
    // First updates the hierarchy from the clicked folder item, then adds the new screen
    // if it doesn't already exist (e.g. the current top's postId doesn't match).
    pushScreen( postId, postType, context = {} )  {
        // Update the navigation stack based on the folder tree.
        this.updateHierarchyFromFolderItem($('.folder-item[data-id="'+postId+'"]'));

        // Check if the current screen (top of the stack) matches the new post.
        const currentScreen = this.getCurrentScreen();
        if (!currentScreen || currentScreen.postId !== postId) {
            this.stack.push({ postId, postType, context });
        }
        this.loadCurrentScreen();
        console.log('Navigation stack:', this.stack);
    }

    // Pop the current screen from the stack.
    popScreen() {
        if (this.stack.length > 1) {
            this.stack.pop();
            this.loadCurrentScreen();
        }
    }

    // Load the current screen based on the top of the stack.
    loadCurrentScreen() {
        const currentScreen = this.getCurrentScreen();
        if (currentScreen) {
            // Load the screen using postId and postType.
            store.screenContent.loadPostContent(currentScreen.postId, currentScreen.postType);
        }
    }

    // Get the current screen.
    getCurrentScreen() {
        return this.stack[this.stack.length - 1];
    }
}
