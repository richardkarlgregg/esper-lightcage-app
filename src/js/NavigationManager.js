import $ from 'jquery';
import store from './Store.js';

export default class NavigationManager {
    constructor() {
        this.history = [];  // Array to store navigation history
        this.currentIndex = -1;  // Current position in history
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
            console.log(this.stack);
        }

        // Get the current screen
        const currentScreen = this.getCurrentScreen();
        
        // Check if the current screen matches the new screen
        if (
            !currentScreen ||
            currentScreen.postId !== postId ||
            JSON.stringify(currentScreen.context) !== JSON.stringify(context)
        ) {
            // If we're not at the end of the history, remove all future entries
            if (this.currentIndex < this.history.length - 1) {
                this.history = this.history.slice(0, this.currentIndex + 1);
            }

            // Add the new screen to history
            this.history.push({ postId, postType, context });
            this.currentIndex = this.history.length - 1;
        }
        
        this.loadCurrentScreen();
        console.log('Navigation history:', this.history);
        console.log('Current index:', this.currentIndex);
    }
    
    // Go back in history
    popScreen() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.loadCurrentScreen();
        }
    }

    // Go forward in history
    forwardScreen() {
        if (this.currentIndex < this.history.length - 1) {
            this.currentIndex++;
            this.loadCurrentScreen();
        }
    }

    // Load the current screen based on the current history index
    loadCurrentScreen() {
        const currentScreen = this.getCurrentScreen();
        if (currentScreen) {
            store.screenContent.loadPostContent(currentScreen.postId, currentScreen.postType, currentScreen.context);
        }
    }

    // Get the current screen from history
    getCurrentScreen() {
        return this.history[this.currentIndex];
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

    // Check if forward navigation is possible
    canGoForward() {
        return this.currentIndex < this.history.length - 1;
    }

    // Check if back navigation is possible
    canGoBack() {
        return this.currentIndex > 0;
    }

    refreshCurrentScreen() {
        this.loadCurrentScreen();
    }
}
