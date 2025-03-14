import store from './Store.js';

export default class NavigationManager {
    constructor() {
        this.stack = [];
    }

    // Push a new screen onto the stack
    pushScreen(postId, postType, context = {}) {
        this.stack.push({ postId, postType, context });
        this.loadCurrentScreen();
        console.log('this.stack');
        console.log(this.stack);
    }

    // Pop the current screen from the stack
    popScreen() {
        if (this.stack.length > 1) {
            this.stack.pop();
            this.loadCurrentScreen();
        }
    }

    // Load the current screen based on the top of the stack
    loadCurrentScreen() {
        const currentScreen = this.stack[this.stack.length - 1];
        if (currentScreen) {
            // Load the screen using postId and postType
            store.screenContent.loadPostContent(currentScreen.postId, currentScreen.postType);
            // Additional logic for context can be added here
        }
    }

    // Get the current screen
    getCurrentScreen() {
        return this.stack[this.stack.length - 1];
    }
} 