import $ from 'jquery';
import 'jquery-ui/ui/widgets/datepicker';

import store from './Store.js';

export default class PostManager {
    constructor() {
        this.init();
    }

    init() {
    }

    setupEventListeners() {

    }

    // Create a new post via AJAX
async createPost(type, title, parentType = null, parentId = null) {
    const data = {
        action: 'esper_create_item',
        nonce: esperApi.nonce,
        type: type,
        title: title
    };
    
    if (parentType && parentId) {
        data.parent_type = parentType;
        data.parent_id = parentId;
    }
    
    try {
        return await $.post(esperApi.ajaxurl, data);
    } catch (error) {
        console.error('Error creating post:', error);
        throw error;
    }
}

}