import $ from 'jquery';
import 'jquery-ui/ui/widgets/datepicker';

// Make jQuery available globally
window.jQuery = window.$ = $;

import { initThreeJS, destroyThreeJS, startSphereRotation, focusOnLight, resetLights, dimLightsExcept, addCubeAndSpawnLightsAndScreenshot } from './threeScene';


import store from './Store.js';
import UIManager from './UIManager.js';
import ScreenContent from './ScreenContent.js';
import CaptureManager from './CaptureManager.js';
import TakeManager from './TakeManager.js';
import NotificationManager from './NotificationManager.js';
import PostManager from './PostManager.js';
import SessionManager from './SessionManager.js';
import NavigationManager from './NavigationManager.js';
import CameraSettingsManager from './CameraSettingsManager.js';
import ExportManager from './ExportManager.js';
import LightSettingsManager from './LightSettingsManager.js';

console.log(store);

$(document).ready(function () {
    
    const uiManager = new UIManager();
    const screenContent = new ScreenContent();
    const captureManager = new CaptureManager();
    const takeManager = new TakeManager();
    const notificationManager = new NotificationManager();
    const postManager = new PostManager();
    const sessionManager = new SessionManager();
    const navigationManager = new NavigationManager();
    const cameraSettingsManager = new CameraSettingsManager();
    const exportManager = new ExportManager();
    const lightSettingsManager = new LightSettingsManager();

    // Put the instance on the store
    store.uiManager = uiManager;
    store.screenContent = screenContent;
    store.captureManager = captureManager;
    store.takeManager = takeManager;
    store.notificationManager = notificationManager;
    store.postManager = postManager;
    store.sessionManager = sessionManager;
    store.navigationManager = navigationManager;
    store.cameraSettingsManager = cameraSettingsManager;
    store.exportManager = exportManager;
    store.lightSettingsManager = lightSettingsManager;

    store.activePostID = null;
    
    store.uiManager.initFolderTree();
    store.uiManager.setupEventListeners();
    store.captureManager.setupEventListeners();
    store.cameraSettingsManager.setupEventListeners();
    store.takeManager.setupEventListeners();
    store.exportManager.setupEventListeners();

    //store.uiManager.openJob(646, 'Job 1');

});
