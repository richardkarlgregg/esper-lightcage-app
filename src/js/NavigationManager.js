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

    pushScreen(postId, postType, context = {}) {

        if (postType !== 'export') {
            // Update the navigation stack based on the folder tree.
            this.updateHierarchyFromFolderItem($('.folder-item[data-id="' + postId + '"]'));
        }
    
        // Get the current screen (top of the stack)
        const currentScreen = this.getCurrentScreen();
        
        // Check if the current screen matches the new screen by postId and context.
        if (
            !currentScreen ||
            currentScreen.postId !== postId ||
            JSON.stringify(currentScreen.context) !== JSON.stringify(context)
        ) {
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
            store.screenContent.loadPostContent(currentScreen.postId, currentScreen.postType, currentScreen.context);
        }
    }

    // Get the current screen.
    getCurrentScreen() {
        return this.stack[this.stack.length - 1];
    }
    
    // New function: Get a postId from the stack by searching by postType and/or context.
    // If both searchPostType and searchContext are provided, both must match.
    // Returns the first matching postId or null if no match is found.
    getPostIdByCriteria(searchPostType, searchContext) {
        const match = this.stack.find(screen => {
            let typeMatches = true;
            let contextMatches = true;
            
            if (searchPostType !== undefined && searchPostType !== null) {
                typeMatches = screen.postType === searchPostType;
            }
            
            if (searchContext !== undefined && searchContext !== null) {
                // If screen.context is undefined, treat it as an empty object.
                contextMatches = JSON.stringify(screen.context || {}) === JSON.stringify(searchContext);
            }
            
            return typeMatches && contextMatches;
        });
        return match ? match.postId : null;
    }
}
