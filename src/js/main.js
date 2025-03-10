import $ from 'jquery';
import 'jquery-ui/ui/widgets/datepicker';
import * as THREE from 'three';
import { initFolderTree } from './folder-tree';

// Make jQuery available globally
window.jQuery = window.$ = $;

// Example Three.js setup
const scene = new THREE.Scene();
const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
const renderer = new THREE.WebGLRenderer();

// Your custom JavaScript code here
$(document).ready(function() {
    console.log('Document ready!');
    initFolderTree();
}); 