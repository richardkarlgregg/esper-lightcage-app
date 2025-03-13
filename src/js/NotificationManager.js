import $ from 'jquery';
import 'jquery-ui/ui/widgets/datepicker';

import store from './Store.js';

export default class NotificationManager {
    constructor() {
        this.init();
    }

    init() {
    }

    setupEventListeners() {

    }

    // Show success message in a toast notification
showSuccess(message) {
    const $toast = $('<div>', {
        'class': 'fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded shadow-lg z-50',
        'text': message
    });
    
    $('body').append($toast);
    
    setTimeout(() => {
        $toast.fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}

// Show error message in a toast notification
showError(message) {
    const $toast = $('<div>', {
        'class': 'fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded shadow-lg z-50',
        'text': message
    });
    
    $('body').append($toast);
    
    setTimeout(() => {
        $toast.fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}

}