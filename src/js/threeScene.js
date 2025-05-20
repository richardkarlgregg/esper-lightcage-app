import $ from 'jquery';
import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls';
import { EffectComposer } from 'three/examples/jsm/postprocessing/EffectComposer';
import { RenderPass } from 'three/examples/jsm/postprocessing/RenderPass';
import { UnrealBloomPass } from 'three/examples/jsm/postprocessing/UnrealBloomPass';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader';

const raycaster = new THREE.Raycaster();
const pointer   = new THREE.Vector2();

function onPointerDown(ev) {
  // NDC coords -1 … +1
  const rect = renderer.domElement.getBoundingClientRect();
  pointer.x = ((ev.clientX - rect.left) / rect.width)  * 2 - 1;
  pointer.y = (-(ev.clientY - rect.top)  / rect.height) * 2 + 1;

  raycaster.setFromCamera(pointer, camera);
  const hits = raycaster.intersectObjects(lightGroup.children, true); // bulbs live in lightGroup
  
  if (hits.length) {
    const hitObj = hits[0].object;
    const clusterId = hitObj.userData.clusterId;
    const bulbIndex = hitObj.userData.bulbIndex;
    const isCtrlClick = ev.ctrlKey || ev.metaKey; // metaKey for Mac support
    
    // Emit lightSelected event with the light ID and Ctrl+Click status
    $(document).trigger('lightSelected', [clusterId, isCtrlClick]);
    
    // Only focus on the bulb if we're not in color picker mode
    if (!document.querySelector('#color-picker-overlay')) {
      // focusOnBulbMesh(hitObj); // Commented out to disable cluster info overlay
    }
  }
}

let renderer, scene, camera, controls, lightGroup, lights = [], originalOpacities = {}, composer, bloomPass;
let rotationGroup; // Parent group for sphere and lights
let wireframeMesh; // The sphere mesh
let sculptureModel; // The loaded sculpture model
let axesRenderer, axesScene, axesCamera; // For the axes helper

// NEW: Rotation control variables
let sphereRotationEnabled = false;
const rotationSpeed = 0.001; // Adjust rotation speed
let rotationTimeout;

const regionNames   = ['left', 'right', 'top', 'bottom', 'front', 'back'];
const regionLights = Object.create(null);
let regionLightsVisible = true;   // default ON                  // { left: PointLight, … }
const typePercent   = { parallel: 100, cross: 100, neutral: 100 };   // current slider levels
const MASTER_GAIN   = 200;                    // tweak overall brightness
let beamHelper;
const BULB_LABEL = ['Parallel', 'Cross', 'Neutral'];   // bulbIndex → name


// ─── overlay / drag helpers ───────────────────────────────
let overlaySVG       = null;   // <svg> that holds the yellow line
let clusterLine      = null;   // <line> element itself
let currentClusterPos = null;  // THREE.Vector3 world-space centre of the cluster

// ─── hover helpers ───────────────────────────────
let hoverDiv      = null;      // <div> that shows live bulb info
let lastHoverObj  = null;      // THREE.Mesh we're currently over

// ─── focus-state ──────────────────────────────
let focused             = false;        // are we in a cluster zoom?
let savedCamPos         = null;         // camera.position before zoom
let savedControlsTarget = null;         // controls.target before zoom
let savedRotY           = 0;            // rotationGroup.rotation.y before zoom

// individual gain (0‒1) for each of the six region lights
const regionGain = {
     left: 1, right: 1, top: 1,
     bottom: 1, front: 1, back: 1
};

// --- cluster visibility by region -------------------------------------------
const regionClusters = {           // cluster-IDs that belong to each side
  left: [], right: [], top: [], bottom: [], front: [], back: []
};

export function initThreeJS() {
    const container = document.getElementById('sphere');
    if (!container) {
        console.error("❌ #sphere div not found!");
        return;
    }
    if (renderer) {
        console.warn("⚠️ Three.js scene already initialized.");
        return;
    }

    // Create axes helper scene
    axesScene = new THREE.Scene();
    axesCamera = new THREE.OrthographicCamera(-2, 2, 2, -2, 0.1, 1000);
    axesCamera.position.set(2, 2, 2);
    axesCamera.lookAt(0, 0, 0);
    
    const orientationAxes = new THREE.AxesHelper(1.5);
    axesScene.add(orientationAxes);

    // Create axes renderer
    axesRenderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    axesRenderer.setSize(100, 100); // Small size for corner
    axesRenderer.setClearColor(0x000000, 0);
    axesRenderer.domElement.style.position = 'absolute';
    axesRenderer.domElement.style.bottom = '150px';
    axesRenderer.domElement.style.left = '30px';
    axesRenderer.domElement.style.zIndex = '1000';
    container.appendChild(axesRenderer.domElement);

    // Main scene setup
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(container.clientWidth, container.clientHeight);
    renderer.setClearColor(0x000000, 0);
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    //renderer.toneMapping = THREE.ACESFilmicToneMapping;
    //renderer.toneMappingExposure = 1.0;
    renderer.setClearAlpha(0);
    renderer.autoClear = false;
    container.appendChild(renderer.domElement);

    renderer.domElement.addEventListener('pointerdown', onPointerDown);
    renderer.domElement.addEventListener('pointermove', onPointerMove);   // NEW

    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(50, container.clientWidth / container.clientHeight, 0.1, 100);
    camera.position.set(0, 5, 12);

    controls = new OrbitControls(camera, renderer.domElement);
    controls.addEventListener('change', updateConnectionLine); 
    controls.enableDamping = true;
    controls.enableZoom = true;
    controls.enablePan = true;
    controls.addEventListener('change', () => {
        stopSphereRotation();
        clearTimeout(rotationTimeout);
        rotationTimeout = setTimeout(() => {
            // Optionally restart rotation after inactivity.
            // startSphereRotation();
        }, 2000);
    });

    // Create a parent group that will contain both the sphere and its lights.
    rotationGroup = new THREE.Group();
    scene.add(rotationGroup);
    createRegionLights();

    // Create the wireframe sphere and add it to the rotationGroup.
    const sphereGeometry = new THREE.IcosahedronGeometry(5, 1);
    const wireframeMaterial = new THREE.LineBasicMaterial({ color: 0xAAAAAA, opacity: 0.25, transparent: true });
    wireframeMesh = new THREE.LineSegments(new THREE.WireframeGeometry(sphereGeometry), wireframeMaterial);
    // Place the sphere on the bloom layer.
    wireframeMesh.layers.enable(1);
    rotationGroup.add(wireframeMesh);

    // Create and add lights to a separate group.
    lightGroup = new THREE.Group();
    createLights(sphereGeometry);
    // Add the lights group to the rotationGroup so both spin together.
    rotationGroup.add(lightGroup);

    // === centre cube =========================================================
    // Load the sculpture model
    const loader = new GLTFLoader();
    loader.load(
        '/wp-content/themes/esper-lightcage-app/assets/models/sculpture_bust_of_roza_loewenfeld/scene.gltf',
        (gltf) => {
            sculptureModel = gltf.scene;
            
            // Scale the model to fit nicely in the scene
            const box = new THREE.Box3().setFromObject(sculptureModel);
            const size = box.getSize(new THREE.Vector3());
            const maxDim = Math.max(size.x, size.y, size.z);
            const scale = 3.5 / maxDim;
            sculptureModel.scale.set(scale, scale, scale);
            
            // Center the model
            const center = box.getCenter(new THREE.Vector3());
            sculptureModel.position.sub(center.multiplyScalar(scale));
            
            // Add the model to the rotation group
            rotationGroup.add(sculptureModel);
        },
        (xhr) => {
            console.log((xhr.loaded / xhr.total * 100) + '% loaded');
        },
        (error) => {
            console.error('An error happened loading the model:', error);
        }
    );

    // soft ambient so the sculpture is visible even without spotlights
    scene.add(new THREE.AmbientLight(0xffffff, 0.1));

    // dummy object the spot-lights will look at
    const centreTarget = new THREE.Object3D();
    centreTarget.position.set(0, 0, 0);
    scene.add(centreTarget);

    // Initialize post-processing.
    composer = new EffectComposer(renderer);
    composer.addPass(new RenderPass(scene, camera));
    bloomPass = new UnrealBloomPass(new THREE.Vector2(window.innerWidth, window.innerHeight), 1.4, 0.25, 0.2);
    //composer.addPass(bloomPass);

    function animate() {
        requestAnimationFrame(animate);
        controls.update();

        // Rotate the entire group if rotation is enabled.
        if (sphereRotationEnabled && rotationGroup) {
            rotationGroup.rotation.y += rotationSpeed;
        }

        // Update axes helper to match main camera orientation
        // Invert the rotation to match intuitive view direction
        orientationAxes.rotation.x = -camera.rotation.x;
        orientationAxes.rotation.y = -camera.rotation.y;
        orientationAxes.rotation.z = -camera.rotation.z;
        axesRenderer.render(axesScene, axesCamera);
        updateConnectionLine();          // <─ NEW (keeps line "live")

        composer.render();
    }
    animate();

    window.addEventListener('resize', onWindowResize);
}

function onWindowResize() {
    if (!renderer) return;
    const container = document.getElementById('sphere');
    if (!container) return;
    renderer.setSize(container.clientWidth, container.clientHeight);
    camera.aspect = container.clientWidth / container.clientHeight;
    camera.updateProjectionMatrix();
    composer.setSize(container.clientWidth, container.clientHeight);
}

function createLights(sphereGeometry) {
    const vertexArray = sphereGeometry.attributes.position.array;
    const uniquePositions = new Set();

    // Add lights at vertices.
    for (let i = 0; i < vertexArray.length; i += 3) {
        const x = vertexArray[i];
        const y = vertexArray[i + 1];
        const z = vertexArray[i + 2];
        const key = `${x.toFixed(4)},${y.toFixed(4)},${z.toFixed(4)}`;
        if (!uniquePositions.has(key)) {
            uniquePositions.add(key);
            createLightCluster(x, y, z, 1.0);
        }
    }

    // Add lights at midpoints of edges.
    const uniqueEdges = new Set();
    const edges = new THREE.EdgesGeometry(sphereGeometry).attributes.position.array;
    for (let i = 0; i < edges.length; i += 6) {
        const ax = edges[i], ay = edges[i + 1], az = edges[i + 2];
        const bx = edges[i + 3], by = edges[i + 4], bz = edges[i + 5];
        const mx = (ax + bx) / 2;
        const my = (ay + by) / 2;
        const mz = (az + bz) / 2;
        const edgeKey = `${mx.toFixed(4)},${my.toFixed(4)},${mz.toFixed(4)}`;
        if (!uniqueEdges.has(edgeKey)) {
            uniqueEdges.add(edgeKey);
            createLightCluster(mx, my, mz, 0.5);
        }
    }
}

function createLight(x, y, z, intensity) {
    const index = lights.length;
    // Create a small sphere to represent the light.
    const sphereGeo = new THREE.SphereGeometry(0.1, 16, 16);
    const sphereMat = new THREE.MeshBasicMaterial({ color: 0xFFC715, opacity: intensity, transparent: true });
    const sphere = new THREE.Mesh(sphereGeo, sphereMat);
    sphere.position.set(x, y, z);
    // Place the light sphere on the bloom layer.
    sphere.layers.enable(1);
    lightGroup.add(sphere);

    lights.push({ index, sphere, x, y, z });
    originalOpacities[index] = intensity;

    // Optional: Create a numbered label for the light.
    const canvas = document.createElement("canvas");
    canvas.width = 64;
    canvas.height = 64;
    const ctx = canvas.getContext("2d");
    ctx.fillStyle = "white";
    ctx.font = "8px Arial";
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillText(index, 32, 32);
    const texture = new THREE.CanvasTexture(canvas);
    // Optionally set texture filtering.
    texture.format = THREE.RGBAFormat;
    texture.minFilter = THREE.LinearFilter;
    texture.generateMipmaps = false;

    const labelMaterial = new THREE.SpriteMaterial({ map: texture });
    const label = new THREE.Sprite(labelMaterial);
    label.position.set(x, y + 0.5, z);
    // Keep the label on the default layer so it's excluded from bloom.
    label.layers.set(0);
    //lightGroup.add(label);
}

function createLightCluster(x, y, z, intensity) {
    const base     = new THREE.Vector3(x, y, z);
    const { tangent, normal } = getTangentBasis(base);
  
    const spread   = 0.25;                        // radius of the triangle
    const offsets  = [
      tangent.clone().multiplyScalar(spread),                         // vertex #1
      tangent.clone().applyAxisAngle(normal,  2 * Math.PI / 3).multiplyScalar(spread), // vertex #2 (120°)
      tangent.clone().applyAxisAngle(normal,  4 * Math.PI / 3).multiplyScalar(spread)  // vertex #3 (240°)
    ];
  
    const cluster = { id: lights.length, spheres: [] };
  
    offsets.forEach(off => {
      const p = base.clone().add(off);
      cluster.spheres.push(addLightSphere(p, intensity));
    });

    // tag bulbs with their cluster-id and bulb-index
cluster.spheres.forEach((s, i) => {
  s.userData.clusterId = cluster.id;
  s.userData.bulbIndex = i;
});
  
    lights.push(cluster);                 // one entry per *cluster*
    originalOpacities[cluster.id] = intensity;

    // ── remember which side of the sphere this cluster lives on ──────────────
 const region = getRegionForPosition(base.x, base.y, base.z);
 regionClusters[region].push(cluster.id);
  }

  function getRegionForPosition(x, y, z) {
    const ax = Math.abs(x), ay = Math.abs(y), az = Math.abs(z);
  
    if (ax >= ay && ax >= az) return (x >= 0) ? 'right'  : 'left';
    if (ay >= ax && ay >= az) return (y >= 0) ? 'top'    : 'bottom';
    /* otherwise */           return (z >= 0) ? 'front'  : 'back';
  }
  
  function createRegionLights() {
    const R = 6;                            // radius just outside the sphere
    const positions = {
      left  : new THREE.Vector3(-R, 0, 0),
      right : new THREE.Vector3( R, 0, 0),
      top   : new THREE.Vector3( 0, R, 0),
      bottom: new THREE.Vector3( 0,-R, 0),
      front : new THREE.Vector3( 0, 0, R),
      back  : new THREE.Vector3( 0, 0,-R)
    };
  
    const gizmoGeo = new THREE.SphereGeometry(0.15, 12, 12);
    const gizmoMat = new THREE.MeshBasicMaterial({ color: 0xffffff, emissive: 0xffffff });
  
    Object.entries(positions).forEach(([name, pos]) => {
      // ─── real light ───────────────────────────
      const beam = new THREE.SpotLight(0xffffff, 1, 8, Math.PI / 8);
      beam.position.copy(pos);
      beam.target.position.set(0, 0, 0);
      scene.add(beam); 
      scene.add( beam.target );
// 2. Create and add the helper
//beamHelper = new THREE.SpotLightHelper( beam );
//scene.add( beamHelper );
  
      // ─── visible gizmo sphere ────────────────
      const gizmo = new THREE.Mesh(gizmoGeo, gizmoMat.clone());
      gizmo.position.copy(pos);
      //rotationGroup.add(gizmo);
  
      regionLights[name] = { beam, gizmo };
    });
  
    updateRegionLightIntensities();    // set initial brightness/visibility
  }
  

  function updateRegionLightIntensities() {
    const mix = (typePercent.parallel + typePercent.cross + typePercent.neutral) / 300;
    const baseI = regionLightsVisible ? mix * MASTER_GAIN : 0;
  
    Object.values(regionLights).forEach(({ beam, gizmo }) => {
        const name = Object.keys(regionLights).find(k => regionLights[k].beam === beam);
        beam.intensity = baseI * (regionGain[name] ?? 1);
        gizmo.visible  = regionLightsVisible;
    });
  }
  
  /**
 * Set brightness for one or more region lights.
 *
 * @param {string|string[]|Object<string,number>} regions
 *        • "left" – single region  
 *        • ["left","right"] – array  
 *        • { left: 80, top: 30 } – map
 * @param {number=} percent  Optional when the first arg is a map.
 *                           Range 0‒100 (will be clamped)
 *
 * Examples
 *   setRegionBrightness('front',  60);
 *   setRegionBrightness(['left','right'], 20);
 *   setRegionBrightness({ top: 100, bottom: 0 });
 */
export function setRegionBrightness(regions, percent) {
    const apply = (name, p) => {
      if (regionGain[name] !== undefined)
        regionGain[name] = THREE.MathUtils.clamp(p, 0, 100) / 100;
    };
  
    if (typeof regions === 'string') {
      apply(regions, percent);
    } else if (Array.isArray(regions)) {
      regions.forEach(r => apply(r, percent));
    } else if (regions && typeof regions === 'object') {
      Object.entries(regions).forEach(([name, p]) => apply(name, p));
    }

    console.log(regionGain);
  
    updateRegionLightIntensities();          // re-compute the real intensities
  }
  
  // expose to non-ESM scripts (matches your pattern)
  if (typeof window !== 'undefined') window.setRegionBrightness = setRegionBrightness;
  

/**
 * Return two unit vectors that form an orthonormal basis of the plane
 * tangent to the sphere at point p (so they're perpendicular to p).
 */
function getTangentBasis(p) {
    const normal = p.clone().normalize();           // ⟂ to the plane
    // Pick any vector that is *not* parallel to the normal
    let tangent = new THREE.Vector3(0, 1, 0).cross(normal);
    if (tangent.lengthSq() < 1e-6) tangent = new THREE.Vector3(1, 0, 0).cross(normal);
    tangent.normalize();
    const bitangent = normal.clone().cross(tangent).normalize();
    return { tangent, bitangent, normal };
  }
  
  /** Single sphere that represents one light "bulb" */
  function addLightSphere(pos, opacity = 1) {
    const geo  = new THREE.SphereGeometry(0.08, 16, 16);
    const mat  = new THREE.MeshBasicMaterial({ color: 0xffc715, transparent: true, opacity });
    const s    = new THREE.Mesh(geo, mat);
    s.position.copy(pos);
    s.layers.enable(1);           // bloom
    lightGroup.add(s);
    return s;
  }

/**
 * Smoothly move the camera/controls so the clicked bulb fills the screen.
 * The animation mimics focusOnLight(): first reset the rotation group,
 * then lerp the camera & controls to their targets.
 */
export function focusOnBulbMesh(mesh) {

  if (!focused) {
        savedCamPos         = camera.position.clone();
        savedControlsTarget = controls.target.clone();
        savedRotY           = rotationGroup.rotation.y;
        focused = true;
      }


  // ───── 1.  Pause auto-spin & reset rotation group ─────
  stopSphereRotation();

  const defaultRotY   = 0;                          // 'home' rotation
  const startRotY     = rotationGroup.rotation.y;
  let   rotProgress   = 0;

  (function animateRotationBack() {
    rotProgress = Math.min(rotProgress + 0.05, 1);  // linear ease
    rotationGroup.rotation.y =
      THREE.MathUtils.lerp(startRotY, defaultRotY, rotProgress);

    if (rotProgress < 1) requestAnimationFrame(animateRotationBack);
  })();


  // ───── 2.  Work out final camera & target positions ─────
  const targetPos   = mesh.getWorldPosition(new THREE.Vector3());

  // Move the camera a bit "behind" the bulb (along its normal) and
  // tilt slightly upward so the object sits nicely in frame.
  const cameraTarget = targetPos
    .clone()
    .normalize()
    .multiplyScalar(targetPos.length() + 4)   // pull back 4 units
    .add(new THREE.Vector3(0, 1, 0));         // gentle up-tilt

  const camStart = camera.position.clone();
  const tgtStart = controls.target.clone();

  let focusProgress = 0;
  (function animateFocus() {
    focusProgress = Math.min(focusProgress + 0.05, 1);

    // same style as focusOnLight – lerp on every frame
    camera.position.lerpVectors(camStart, cameraTarget, focusProgress);
    controls.target.lerpVectors(tgtStart, targetPos, focusProgress);
    controls.update();

    if (focusProgress < 1) requestAnimationFrame(animateFocus);
  })();

  // (Optional) emphasise the clicked cluster
  dimLightsExcept(mesh.userData.clusterId);

  const centre = getClusterCentre(mesh.userData.clusterId);
   showClusterInfo(
     mesh.userData.clusterId,
     centre,
     mesh.userData.bulbIndex            // <─ NEW
   );

}



export function focusOnLight(lightId) {
    // Pause automatic rotation
    stopSphereRotation();

    // Animate the rotation group back to its default rotation (assumed 0 here)
    const defaultRotation = 0;
    const initialRotation = rotationGroup.rotation.y;
    let rotationProgress = 0;
    function animateRotationBack() {
        rotationProgress += 0.05;
        rotationGroup.rotation.y = THREE.MathUtils.lerp(initialRotation, defaultRotation, rotationProgress);
        if (rotationProgress < 1) {
            requestAnimationFrame(animateRotationBack);
        }
    }
    animateRotationBack();

    // Find the selected light for focus.
    const selectedLight = lights.find(l => l.index === lightId);
    if (!selectedLight) {
        console.error(`❌ Light with ID ${lightId} not found!`);
        return;
    }

    // Calculate target positions for camera and controls.
    const targetPosition = new THREE.Vector3(selectedLight.x, selectedLight.y, selectedLight.z);
    const cameraTarget = targetPosition.clone().add(new THREE.Vector3(0, 2, 5));
    let cameraProgress = 0;
    function animateFocus() {
        cameraProgress += 0.05;
        camera.position.lerpVectors(camera.position, cameraTarget, cameraProgress);
        controls.target.lerpVectors(controls.target, targetPosition, cameraProgress);
        controls.update();
        if (cameraProgress < 1) {
            requestAnimationFrame(animateFocus);
        }
    }
    animateFocus();
}

export function dimLightsExcept(selectedId) {
    lights.forEach(cl => {
      cl.spheres.forEach(s => {
        s.material.opacity = (cl.id === selectedId) ? 1.0 : 0.25;
      });
    });
  }
  
  export function resetLights() {
    lights.forEach(cl => {
      cl.spheres.forEach(s => {
        s.material.opacity = originalOpacities[cl.id];
      });
    });
  }

export function destroyThreeJS() {
    if (!renderer) {
        console.warn("⚠️ Three.js already removed.");
        return;
    }
    console.log("🗑️ Removing Three.js scene...");
    
    // Clean up axes renderer
    if (axesRenderer) {
        axesRenderer.dispose();
        axesRenderer.domElement.remove();
        axesRenderer = null;
        axesScene = null;
        axesCamera = null;
    }

    renderer.dispose();
    renderer.domElement.remove();
    if (scene) {
        scene.traverse((object) => {
            if (object.geometry) object.geometry.dispose();
            if (object.material) {
                if (Array.isArray(object.material)) {
                    object.material.forEach(mat => mat.dispose());
                } else {
                    object.material.dispose();
                }
            }
        });
    }
    renderer = null;
    scene = null;
    camera = null;
    controls = null;
    lightGroup = null;
    lights = [];
    console.log("✅ Three.js scene removed successfully.");
}

// NEW: Functions to control sphere (rotation group) rotation.
export function startSphereRotation() {
    sphereRotationEnabled = true;
    console.log('Sphere rotation started');
}
export function stopSphereRotation() {
    sphereRotationEnabled = false;
    console.log('Sphere rotation stopped');
}
export function toggleSphereRotation() {
    sphereRotationEnabled = !sphereRotationEnabled;
    console.log('Sphere rotation toggled to ' + sphereRotationEnabled);
}

// Utility: Sleep function for delays.
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
  
  /**
   * NEW: addCubeAndSpawnLightsAndScreenshot
   * - Accepts activeStageId and captureId so that the images are saved in:
   *     stages/stage-<activeStageId>/captures/<captureId>/
   * - Uses 5 fixed camera angles and 5 randomly selected lights (from the existing lights array).
   * - For each shot, it spawns a spotlight at the light's position (targeting the central object),
   *   renders a screenshot, uploads it to the server, and then updates a progress UI.
   * - The progress bar and text are updated based on the total number of shots.
   * - Returns a promise that resolves when all shots are complete.
   */
  export async function addCubeAndSpawnLightsAndScreenshot(activeStageId, captureId, progressCallback) {
    // Stop automatic rotation.
    stopSphereRotation();
  
    // Hide all light spheres.
    lights.forEach(lightObj => { lightObj.sphere.visible = false; });
  
    // Store original camera state.
    const originalCameraPosition = camera.position.clone();
    const originalControlsTarget = controls.target.clone();
  
    // Fixed distance and fixed camera direction vectors.
    const fixedDistance = 3;
    const cameraDirections = [
      new THREE.Vector3(0, 1, 1),
      new THREE.Vector3(2, 1, 1),
      new THREE.Vector3(-2, 1, 1),
      new THREE.Vector3(0, 2, 1),
      new THREE.Vector3(0, 1, 0.5)
    ];
  
    // Set initial camera.
    camera.position.copy(
      cameraDirections[0].clone().normalize().multiplyScalar(fixedDistance)
    );
    controls.target.set(0, 0, 0);
    controls.update();
  
    // Randomize central geometry.
    const geometries = [
      new THREE.BoxGeometry(1, 1, 1),
      new THREE.SphereGeometry(0.5, 32, 32),
      new THREE.ConeGeometry(0.5, 1, 32),
      new THREE.TorusGeometry(0.5, 0.2, 16, 100)
    ];
    const randomIndex = Math.floor(Math.random() * geometries.length);
    const selectedGeometry = geometries[randomIndex];
    const centralMaterial = new THREE.MeshStandardMaterial({ color: 0x00ff00 });
    const centralMesh = new THREE.Mesh(selectedGeometry, centralMaterial);
    centralMesh.position.set(0, 0, 0);
    scene.add(centralMesh);
  
    // Add ambient light.
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
    scene.add(ambientLight);
  
    if (lights.length < 5) {
      console.error("Not enough lights available in lights array.");
      return;
    }
  
    // Randomly select 5 lights.
    const shuffled = lights.slice().sort(() => 0.5 - Math.random());
    const selectedLights = shuffled.slice(0, 5);
  
    // Total number of shots.
    const totalShots = cameraDirections.length * selectedLights.length;
    let shotCounter = 0;
  
    // Outer loop: For each fixed camera angle.
    for (let j = 0; j < cameraDirections.length; j++) {
      const newCamPos = cameraDirections[j].clone().normalize().multiplyScalar(fixedDistance);
      camera.position.copy(newCamPos);
      controls.target.set(0, 0, 0);
      controls.update();
      await sleep(500);
  
      // Inner loop: For each selected light.
      for (let i = 0; i < selectedLights.length; i++) {
        const sel = selectedLights[i];
        console.log(`Angle ${j + 1}: Processing light ${sel.index}`);
  
        // Spawn spotlight.
        const spotLight = new THREE.SpotLight(0xffffff, 1);
        spotLight.position.set(sel.x, sel.y, sel.z);
        spotLight.angle = Math.PI / 6;
        spotLight.penumbra = 0.2;
        spotLight.target = centralMesh;
        scene.add(spotLight.target);
        scene.add(spotLight);
        await sleep(1000);
  
        // Capture screenshot.
        renderer.render(scene, camera);
        const dataURL = renderer.domElement.toDataURL("image/png");
  
        
        // Upload image.
        try {
            shotCounter++;
            await uploadCaptureImage(activeStageId, captureId, shotCounter, dataURL);
          console.log(`Uploaded screenshot for angle ${j + 1}, light ${sel.index}`);
        } catch (err) {
          console.error("Error uploading image:", err);
        }
  
        // Remove spotlight.
        scene.remove(spotLight);
        scene.remove(spotLight.target);
        
        if (progressCallback) {
          progressCallback(shotCounter, totalShots);
        }
        await sleep(300);
      }
      await sleep(500);
    }
  
    // Restore camera state.
    camera.position.copy(originalCameraPosition);
    controls.target.copy(originalControlsTarget);
    controls.update();
  
    // Show light spheres.
    lights.forEach(lightObj => { lightObj.sphere.visible = true; });
  
    return { success: true, totalShots, shotCounter };
  }
  
  
  /**
   * NEW: Upload captured image to the server.
   * Sends a POST request with activeStageId, captureId, angleIndex, lightId, and imageData.
   */
function uploadCaptureImage(activeStageId, captureId, shotIndex, imageData) {
    return $.ajax({
      url: "stages_loader.php?action=saveCaptureImage&activeStageId=" + activeStageId,
      type: "POST",
      contentType: "application/json",
      data: JSON.stringify({
        captureId: captureId,
        shotIndex: shotIndex,
        imageData: imageData
      }),
      dataType: "json"
    });
  }
  
  // ─── add near the other exports ───
/**
 * Change brightness for all bulbs of a given type.
 * @param {'parallel'|'cross'|'neutral'} type
 * @param {number} percent   // 0‒100
 */
export function setLightTypeBrightness(type, percent) {
    if (!(type in typePercent)) return;
  
    typePercent[type] = THREE.MathUtils.clamp(percent, 0, 100);
  
    // still tint the little mesh bulbs so the UI looks alive
    const idx = { parallel: 0, cross: 1, neutral: 2 }[type];
    const opacity = 0.05 + 0.95 * (percent / 100);
  
    lights.forEach(cl => {
      const s = cl.spheres[idx];
      if (s) s.material.opacity = opacity;
    });
  
    // recompute the six real lights
    updateRegionLightIntensities();
  }
  
  
  /* make it available to non-ESM scripts that run in WP */
  if (typeof window !== 'undefined') window.setLightTypeBrightness = setLightTypeBrightness;
  
  export function showRegionLights()  { regionLightsVisible = true;  updateRegionLightIntensities(); }
export function hideRegionLights()  { regionLightsVisible = false; updateRegionLightIntensities(); }

if (typeof window !== 'undefined') {
  window.showRegionLights = showRegionLights;
  window.hideRegionLights = hideRegionLights;
}
  
// 50 % brightness on the front light only
//setRegionBrightness('front', 50);

/**
 * Show / hide bulb-clusters that lie in one or more regions.
 *
 * @param {string|string[]} regions  e.g. "front" | ["left","right"]
 * @param {boolean}         visible  true = show, false = hide
 */
export function setClusterRegionVisibility(regions, visible) {
  const list = (typeof regions === 'string') ? [regions] : regions;
  list.forEach(region => {
    (regionClusters[region] || []).forEach(id => {
      lights[id].spheres.forEach(s => { s.visible = visible; });
    });
  });
}

/** Convenience helpers */
export const showClusterRegions = (r) => setClusterRegionVisibility(r, true);
export const hideClusterRegions = (r) => setClusterRegionVisibility(r, false);

/* Expose to vanilla scripts (like you did elsewhere) */
if (typeof window !== 'undefined') {
  window.setClusterRegionVisibility = setClusterRegionVisibility;
  window.showClusterRegions         = showClusterRegions;
  window.hideClusterRegions         = hideClusterRegions;
}

// ────────────────────────────────────────────────────────────────────────────
// Single-bulb helpers  (clusterId === lights[index].id,  bulbIndex ∈ 0‥2)
// ────────────────────────────────────────────────────────────────────────────

/**
 * Change visibility of one bulb.
 * @param {number}  clusterId   lights[clusterId]
 * @param {number}  bulbIndex   0, 1 or 2
 * @param {boolean} visible
 */
export function setBulbVisibility(clusterId, bulbIndex, visible = true) {
  const bulb = lights?.[clusterId]?.spheres?.[bulbIndex];
  if (bulb) bulb.visible = visible;
}

/**
 * Toggle one bulb on/off.
 * @param {number} clusterId
 * @param {number} bulbIndex
 */
export function toggleBulb(clusterId, bulbIndex) {
  const bulb = lights?.[clusterId]?.spheres?.[bulbIndex];
  if (bulb) bulb.visible = !bulb.visible;
}

/**
 * Set opacity/brightness (0‒100 %) of one bulb.
 * @param {number} clusterId
 * @param {number} bulbIndex
 * @param {number} percent     0‒100
 */
export function setBulbBrightness(clusterId, bulbIndex, percent) {
  const bulb = lights?.[clusterId]?.spheres?.[bulbIndex];
  if (bulb) {
    bulb.material.opacity = THREE.MathUtils.clamp(percent, 0, 100) / 100;
    bulb.visible = bulb.material.opacity > 0;          // hide if fully dimmed
  }
}

/* Make helpers available to vanilla scripts =================================*/
if (typeof window !== 'undefined') {
  window.setBulbVisibility  = setBulbVisibility;
  window.toggleBulb         = toggleBulb;
  window.setBulbBrightness  = setBulbBrightness;
  window.lights            = lights;  // Expose lights array
  window.regionClusters    = regionClusters;  // Expose region mapping
}

function showClusterInfo(id, pos, bulbIdx) {
  let div = document.getElementById('clusterInfo');
  if (!div) {
    div = document.createElement('div');
    div.id = 'clusterInfo';
    Object.assign(div.style, {
      position: 'absolute',
      top: '20px', right: '20px',
      padding: '12px 16px',
      background: 'rgba(0,0,0,.80)',
      color: '#fff', borderRadius: '8px',
      fontFamily: 'sans-serif', cursor: 'move',
      zIndex: 2000,
      width: '180px',            // fixed size
      height: '120px',
    });
    document.body.appendChild(div);

    /* ——— DRAG LOGIC ——— */
    let dragging = false, offX = 0, offY = 0;
    div.addEventListener('pointerdown', e => {
     // start drag **only** when user clicks the panel itself,
    // not its children (e.g. the Close button)
      if (e.target !== div) return;
      dragging = true;
      offX = e.clientX - div.offsetLeft;
      offY = e.clientY - div.offsetTop;
      div.setPointerCapture(e.pointerId);
    });
    div.addEventListener('pointermove', e => {
      if (!dragging) return;
      div.style.left = (e.clientX - offX) + 'px';
      div.style.top  = (e.clientY - offY) + 'px';
      updateConnectionLine();                 // ▲ keep line glued to panel
    });
    div.addEventListener('pointerup',   () => dragging = false);
    div.addEventListener('pointercancel', () => dragging = false);
  }

  div.innerHTML = `
    <strong>Cluster ${id}</strong><br/>
    <em>${BULB_LABEL[bulbIdx] ?? 'Unknown'}</em><br/>   <!-- NEW -->
    X ${pos.x.toFixed(2)}<br/>
    Y ${pos.y.toFixed(2)}<br/>
    Z ${pos.z.toFixed(2)}<br/>
    <button id="clusterCloseBtn"
+         style="margin-top:8px; display:block; width:100%;">Close</button>
  `;

  document.getElementById('clusterCloseBtn').onclick = closeClusterInfo;

  /* ——— yellow line ——— */
  const svg = getOverlaySVG();
  removeConnectionLine();                     // in case an old one exists
  clusterLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
  clusterLine.setAttribute('stroke', 'rgba(255, 199, 21, 1)');  // same golden hue
  clusterLine.setAttribute('stroke-width', '1');
  svg.appendChild(clusterLine);

  currentClusterPos = pos.clone();
  requestAnimationFrame(updateConnectionLine);  // wait 1 frame

}


function closeClusterInfo() {
  const div = document.getElementById('clusterInfo');
  if (div) div.remove();
  removeConnectionLine();
  unhideHoverInfo(); 
  unfocusView();
}

function unfocusView() {
  if (!focused) return;

  stopSphereRotation();                       // optional

  const cam0  = camera.position.clone();
  const tgt0  = controls.target.clone();
  const rot0  = rotationGroup.rotation.y;

  let t = 0;
  (function animateBack() {
    t = Math.min(t + 0.05, 1);
    camera.position.lerpVectors(cam0, savedCamPos,         t);
    controls.target.lerpVectors(tgt0, savedControlsTarget, t);
    rotationGroup.rotation.y = THREE.MathUtils.lerp(rot0, savedRotY, t);
    controls.update();

    if (t < 1) {
      requestAnimationFrame(animateBack);
    } else {
      resetLights();                          // restore bulb opacities
      focused = false;
      // startSphereRotation();               // restart auto-spin if you like
    }
  })();
}

/*───────────────────────────────────────────────────────────*
 *  DRAGGABLE PANEL  +  SCREEN-SPACE LINE TO CLUSTER
 *───────────────────────────────────────────────────────────*/

// return (and lazily create if necessary) a full-window SVG
function getOverlaySVG() {
  if (!overlaySVG) {
    overlaySVG = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    Object.assign(overlaySVG.style, {
      position: 'absolute', top: 0, left: 0,
      width: '100%', height: '100%',
      pointerEvents: 'none',   // let clicks pass through
      zIndex: 1500
    });
    document.body.appendChild(overlaySVG);
  }
  return overlaySVG;
}

// called every frame (from animate) + on drag
function updateConnectionLine() {
  if (!clusterLine || !currentClusterPos) return;

  // Project world-space position into screen-space
  const { x: sx, y: sy } = worldToScreen(currentClusterPos);   // <─ NEW

  // anchor point: centre-right edge of the panel
  const panel = document.getElementById('clusterInfo');
  if (!panel) return;
  const rect = panel.getBoundingClientRect();
  const px = rect.left + rect.width  / 2;
  const py = rect.top  + rect.height / 2;

  clusterLine.setAttribute('x1', px);
  clusterLine.setAttribute('y1', py);
  clusterLine.setAttribute('x2', sx);
  clusterLine.setAttribute('y2', sy);
}

// remove line & overlay when we unfocus
function removeConnectionLine() {
  if (clusterLine && overlaySVG) {
    overlaySVG.removeChild(clusterLine);
    clusterLine  = null;
    // keep the SVG; it's inexpensive and may be reused
  }
}

/*───────────────────────────────────────────────*
 *   SCREEN-SPACE PROJECTION with canvas offset
 *───────────────────────────────────────────────*/
function worldToScreen(pos) {
  const v = pos.clone().project(camera);
  const rect = renderer.domElement.getBoundingClientRect();   // <─ NEW
  return {
    x: ( v.x * 0.5 + 0.5) * rect.width  + rect.left,
    y: (-v.y * 0.5 + 0.5) * rect.height + rect.top
  };
}

function getClusterCentre(clusterId) {                        // <─ NEW
  const spheres = lights[clusterId].spheres;
  const centre  = new THREE.Vector3();
  spheres.forEach(s => centre.add(s.getWorldPosition(new THREE.Vector3())));
  return centre.multiplyScalar(1 / spheres.length);
}

function showHoverInfo(obj) {
  if (!hoverDiv) {
    hoverDiv = document.createElement('div');
    Object.assign(hoverDiv.style, {
      position: 'absolute',
      bottom: '20px', left: '20px',
      padding: '6px 10px',
      fontFamily: 'sans-serif',
      fontSize: '12px',
      color: '#fff',
      background: 'rgba(0,0,0,.7)',
      borderRadius: '4px',
      pointerEvents: 'none',
      zIndex: 1200
    });
    document.body.appendChild(hoverDiv);
  }

  const clId   = obj.userData.clusterId;
  const bulbIx = obj.userData.bulbIndex;
  const pos    = obj.getWorldPosition(new THREE.Vector3());

  hoverDiv.innerHTML =
    `Cluster ${clId}&nbsp;•&nbsp;${BULB_LABEL[bulbIx]}<br>` +
    `X ${pos.x.toFixed(2)} Y ${pos.y.toFixed(2)} Z ${pos.z.toFixed(2)}`;
}

function hideHoverInfo() {
  if (hoverDiv) hoverDiv.style.display = 'none';
}

function unhideHoverInfo() {
  if (hoverDiv) hoverDiv.style.display = 'block';
}

function onPointerMove(ev) {
  const rect = renderer.domElement.getBoundingClientRect();
  pointer.x = ((ev.clientX - rect.left) / rect.width)  * 2 - 1;
  pointer.y = (-(ev.clientY - rect.top)  / rect.height) * 2 + 1;

  raycaster.setFromCamera(pointer, camera);
  const hits = raycaster.intersectObjects(lightGroup.children, true);

  if (hits.length) {
    const hitObj = hits[0].object;

    // pointer cursor
    renderer.domElement.style.cursor = 'pointer';

    // update tooltip only if we switched bulbs
    if (hitObj !== lastHoverObj) {
      lastHoverObj = hitObj;
      showHoverInfo(hitObj);
      unhideHoverInfo();
    }
  } else {
    renderer.domElement.style.cursor = 'auto';
    lastHoverObj = null;
    hideHoverInfo();
  }
}