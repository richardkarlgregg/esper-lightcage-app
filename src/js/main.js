import $ from 'jquery';
import 'jquery-ui/ui/widgets/datepicker';

// Make jQuery available globally
window.jQuery = window.$ = $;

//import { initThreeJS, destroyThreeJS, startSphereRotation, focusOnLight, resetLights, dimLightsExcept, addCubeAndSpawnLightsAndScreenshot, zoomIntoSphere, hideLightSpheres, showLightSpheres, resetZoom, startTriangleFade, stopTriangleFade, stopSphereRotation  } from './threeScene';

import store from './Store.js';
import UIManager from './UIManager.js';
import ScreenContent from './ScreenContent.js';
import CaptureManager from './CaptureManager.js';
import TakeManager from './TakeManager.js';
import NotificationManager from './NotificationManager.js';
import PostManager from './PostManager.js';

console.log(store);

$(document).ready(function () {
    
    const uiManager = new UIManager();
    const screenContent = new ScreenContent();
    const captureManager = new CaptureManager();
    const takeManager = new TakeManager();
    const notificationManager = new NotificationManager();
    const postManager = new PostManager();

    // Put the instance on the store
    store.uiManager = uiManager;
    store.screenContent = screenContent;
    store.captureManager = captureManager;
    store.takeManager = takeManager;
    store.notificationManager = notificationManager;
    store.postManager = postManager;
    
    store.uiManager.initFolderTree();
    store.captureManager.setupEventListeners();

});
