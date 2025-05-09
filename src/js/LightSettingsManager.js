import $ from 'jquery';
import store from './Store.js';

export default class LightSettingsManager {
    constructor() {
        this.isPlaying = false;
        this.currentStageIndex = 0;
        this.playbackTimeout = null;
        this.originalModelingLight = {
            parallel: 0,
            cross: 0,
            neutral: 0
        };
        this.previousLedType = null;
        this.saveTimeout = null;
        this.init();
        this.render();
    }

    init() {
        // Delegate all clicks and inputs from document

        // Add Stage button
        $(document).on('click', '#stage-composer-wrapper #add-stage', e => {
            e.preventDefault();
            this.addRow();
            this.refreshNumbers();
            this.debouncedSave();
        });

        // Timeline scrubber
        $(document).on('input', '#timeline-scrubber', e => {
            if (this.isPlaying) return; // Don't allow scrubbing while playing
            
            const $scrubber = $(e.currentTarget);
            const value = parseInt($scrubber.val());
            const $cards = $('#stage-timeline .stage-card');
            const totalStages = $cards.length;
            
            if (totalStages === 0) return;
            
            // Calculate which stage to show based on scrubber position
            const stageIndex = Math.floor((value / 100) * totalStages);
            this.currentStageIndex = Math.min(stageIndex, totalStages - 1);
            
            // Update the stage display
            this.updateActiveStage();
            
            // Get current stage settings
            const $currentCard = $cards.eq(this.currentStageIndex);
            const direction = $currentCard.find('select[name^="direction"]').val();
            const brightness = parseInt($currentCard.find('input[name^="brightness"]').val());
            
            // Reset all regions to 0 first
            const regions = ['front', 'back', 'left', 'right', 'top', 'bottom'];
            regions.forEach(region => {
                window.setRegionBrightness(region, 0);
            });

            // Hide all regions first
            if (window.setClusterRegionVisibility) {
                window.setClusterRegionVisibility(regions, false);
            }

            // Set brightness based on direction
            if (direction === 'GI') {
                // Global illumination - set all regions to same brightness and show all
                regions.forEach(region => {
                    window.setRegionBrightness(region, brightness);
                });
                if (window.setClusterRegionVisibility) {
                    window.setClusterRegionVisibility(regions, true);
                }
            } else {
                // Set specific region brightness and show only that region
                const regionMap = {
                    'FRONT': 'front',
                    'BACK': 'back',
                    'LEFT': 'left',
                    'RIGHT': 'right',
                    'TOP': 'top',
                    'BOTTOM': 'bottom'
                };
                const region = regionMap[direction];
                if (region) {
                    window.setRegionBrightness(region, brightness);
                    if (window.setClusterRegionVisibility) {
                        window.setClusterRegionVisibility(region, true);
                    }
                }
            }

            // Update modeling light based on LED selection
            if (window.setLightTypeBrightness) {
                const ledType = $currentCard.find('input[type=radio][name^="led"]:checked').val();
                const modelingLightMap = {
                    'PARALLEL': 'parallel',
                    'CROSS': 'cross',
                    'NEUTRAL': 'neutral'
                };

                // Reset ALL modeling lights to 0 first
                Object.values(modelingLightMap).forEach(type => {
                    window.setLightTypeBrightness(type, 0);
                });
                
                // Set the selected LED type to the stage brightness
                const modelingType = modelingLightMap[ledType];
                if (modelingType) {
                    window.setLightTypeBrightness(modelingType, brightness);
                }
            }
        });

        // Save Stages button
        $(document).on('click', '#stage-composer-wrapper #save-stages', e => {
            e.preventDefault();
            this.saveStages();
        });

        // Playback controls
        $(document).on('click', '#play-timeline', e => {
            e.preventDefault();
            this.startPlayback();
        });

        $(document).on('click', '#pause-timeline', e => {
            e.preventDefault();
            this.pausePlayback();
        });

        $(document).on('click', '#stop-timeline', e => {
            e.preventDefault();
            this.stopPlayback();
        });

        // Brightness slider update
        $(document).on('input', '#stage-composer-wrapper input[type=range]', e => {
            const $input = $(e.currentTarget);
            const $card = $input.closest('.stage-card');
            $card.find('.text-right').text(`${$input.val()}%`);
            this.debouncedSave();
        });

        // Remove stage
        $(document).on('click', '#stage-composer-wrapper .remove-stage', e => {
            e.preventDefault();
            $(e.currentTarget).closest('.stage-card').remove();
            this.refreshNumbers();
            this.debouncedSave();
            this.updateTimelineControlsVisibility();
        });

        // Move up
        $(document).on('click', '#stage-composer-wrapper .move-up', e => {
            e.preventDefault();
            const $card = $(e.currentTarget).closest('.stage-card');
            const $prev = $card.prev();
            if ($prev.length) {
                $prev.before($card);
                this.refreshNumbers();
                this.debouncedSave();
            }
        });

        // Move down
        $(document).on('click', '#stage-composer-wrapper .move-down', e => {
            e.preventDefault();
            const $card = $(e.currentTarget).closest('.stage-card');
            const $next = $card.next();
            if ($next.length) {
                $next.after($card);
                this.refreshNumbers();
                this.debouncedSave();
            }
        });

        // Add below
        $(document).on('click', '#stage-composer-wrapper .add-below', e => {
            e.preventDefault();
            const $card = $(e.currentTarget).closest('.stage-card');
            this.addRow();
            $card.after($('#stage-timeline .stage-card:last'));
            this.refreshNumbers();
            this.debouncedSave();
        });

        // Add change handlers for all inputs
        $(document).on('change', '#stage-composer-wrapper select', () => {
            this.debouncedSave();
        });

        $(document).on('change', '#stage-composer-wrapper input[type=radio]', () => {
            this.debouncedSave();
        });

        $(document).on('change', '#stage-composer-wrapper input[type=number]', () => {
            this.debouncedSave();
        });

        // Mouse wheel horizontal scroll
        $('#stage-timeline').on('wheel', function(e) {
            if (e.originalEvent.deltaY !== 0) {
                e.preventDefault();
                $(this).scrollLeft($(this).scrollLeft() + e.originalEvent.deltaY);
            }
        });

        $(document).on('click', '.stage-summary', function (e) {
            //  Ignore clicks on any button inside the icon-bar
            if ($(e.target).closest('.icon-bar button').length) return;
    
            const $group = $(this).closest('.stage-group');
            $group.toggleClass('open');
            $group.find('.stage-details').toggle();      // show / hide row
        });
    }

    render() {
        const $wrapper = $('#stage-composer-wrapper');
        
        // Add the scrubber next to the existing buttons
        const $buttonContainer = $wrapper.find('.flex.items-center.gap-2').first();
        if ($buttonContainer.length) {
            $buttonContainer.after(`
                <div class="flex-1 flex items-center gap-2 ml-4">
                    <input type="range" id="timeline-scrubber" class="flex-1" min="0" max="100" value="0" step="1">
                    <span id="timeline-position" class="text-sm">0%</span>
                </div>
            `);
        }
    }

    refreshNumbers() {
        $('#stage-timeline .stage-card').each((i, card) => {
            const $card = $(card);
            const stageNumber = `S${String(i + 1).padStart(2, '0')}`;
            $card.find('.bg-esper-yellow').contents().first().replaceWith(stageNumber);
            
            // Update input names
            $card.find(':input').each(function() {
                const name = $(this).attr('name');
                if (name && name.includes('__INDEX__')) {
                    $(this).attr('name', name.replace('__INDEX__', i));
                }
            });
        });
    }

    addRow(data = {}) {
        const $timeline = $('#stage-timeline');
        const nextIndex = $timeline.find('.stage-card').length;
        const stageNumber = `S${String(nextIndex + 1).padStart(2, '0')}`;
        
        // Create new card
        const $card = $('<div class="stage-card shrink-0 border border-esper-yellow rounded-sm min-w-[220px]">');
        
        // Add header
        $card.append(`
            <div class="h-7 bg-esper-yellow flex items-center justify-between px-1 text-[10px] font-bold text-black">
                ${stageNumber}
                <div class="icon-bar flex items-center gap-1">
                    <button class="hidden flex items-center move-up" title="Up"><span class="material-symbols-outlined text-[14px]">arrow_upward</span></button>
                    <button class="hidden flex items-center move-down" title="Down"><span class="material-symbols-outlined text-[14px]">arrow_downward</span></button>
                    <button class="flex items-center add-below" title="Add"><span class="material-symbols-outlined text-[14px]">add_row_below</span></button>
                    <button class="flex items-center remove-stage" title="Del"><span class="material-symbols-outlined text-[14px]">delete</span></button>
                </div>
            </div>
        `);

        // Add content grid
        const $grid = $('<div class="p-2 grid grid-rows-2 gap-y-1 text-[11px] leading-none">');
        
        // Row 1 - LED (triangle) + Direction
        const $row1 = $('<div class="flex gap-1 items-center">');
        // LED triangle overlay
        const ledVal = data.led || 'PARALLEL';
        const $ledTriangle = $('<div class="led-triangle relative w-[120px] h-[110px] mx-2 my-1"></div>');
        $ledTriangle.append('<img class="absolute inset-0 w-full h-full pointer-events-none" src="/wp-content/themes/esper-lightcage-app/assets/images/Light-Front.png" alt="LED layout">');
        const leds = {
            'CROSS':    { style: 'top:-15px;left:15px;', tooltip: 'Cross' },
            'PARALLEL': { style: 'top:-15px;right:-6px;', tooltip: 'Parallel' },
            'NEUTRAL':  { style: 'top:6px;left:4px;', tooltip: 'Neutral' },
        };
        Object.entries(leds).forEach(([value, opts]) => {
            $ledTriangle.append(`
                <label class="absolute" style="${opts.style}">
                    <input type="radio" value="${value}" name="led[__INDEX__]" class="led-radio" data-tooltip="${opts.tooltip}" ${ledVal === value ? 'checked' : ''}>
                </label>
            `);
        });
        $row1.append($ledTriangle);

        // Direction select
        $row1.append(`
            <select name="direction[__INDEX__]" class="ml-auto bg-black border border-white/20 px-1 py-0.5">
                <option value="GI">Global-Illumination</option>
                <option value="LEFT">Left</option>
                <option value="RIGHT">Right</option>
                <option value="TOP">Top</option>
                <option value="BOTTOM">Bottom</option>
                <option value="FRONT">Front</option>
                <option value="BACK">Back</option>
            </select>
        `);
        $grid.append($row1);

        // Row 2 - Brightness + Flash
        const $row2 = $('<div class="flex gap-1 items-center">');
        // Brightness range
        const brightness = data.brightness != null ? data.brightness : 70;
        $row2.append(`
            <input type="range" min="0" max="100" step="1" value="${brightness}" name="brightness[__INDEX__]" class="flex-1 h-1">
            <span class="text-[10px] w-6 text-right">${brightness}%</span>
        `);
        // Flash duration
        const duration = data.flash_duration != null ? data.flash_duration : 5.0;
        $row2.append(`
            <input type="number" step="0.1" min="0" value="${duration}" name="flash_duration[__INDEX__]" class="w-12 bg-black border border-white/20 px-1 py-0.5">
            <span class="text-[10px]">s</span>
        `);
        $grid.append($row2);

        $card.append($grid);
        $timeline.append($card);
        this.refreshNumbers();

        // Scroll to the new card with smooth animation
        $timeline.animate({
            scrollLeft: $card.offset().left - $timeline.offset().left + $timeline.scrollLeft()
        }, 500);

        // Update timeline controls visibility
        this.updateTimelineControlsVisibility();
    }

    saveStages() {
        this.refreshNumbers();

        const data = $('#stage-timeline .stage-card').map((i, card) => {
            const $card = $(card);
            return {
                led: $card.find('input[type=radio][name^="led"]:checked').val() || '',
                direction: $card.find('select[name^="direction"]').val(),
                brightness: $card.find('input[name^="brightness"]').val(),
                flash_duration: $card.find('input[name^="flash_duration"]').val(),
            };
        }).get();

        const captureId = store.navigationManager.getPostIdByCriteria('capture');

        $.ajax({
            url: esperApi.ajaxurl,
            type: 'POST',
            data: {
                action: 'stage_composer_save',
                capture_id: captureId,
                nonce: esperApi.nonce,
                data: JSON.stringify(data)
            },
            success: (response) => {
                if (response.success) {
                    //store.notificationManager.showSuccess('Stages updated successfully.');
                } else {
                    alert(`Error: ${response.data}`);
                }
            },
            error: () => {
                alert('Failed to save stages.');
            }
        });
    }

    startPlayback() {
        if (this.isPlaying) return;
        
        // Store original modeling light values
        const $root = $('#modeling-light');
        if ($root.length) {
            this.originalModelingLight = {
                parallel: parseFloat($root.find('#bulb-a').data('val')) || 0,
                cross: parseFloat($root.find('#bulb-b').data('val')) || 0,
                neutral: parseFloat($root.find('#bulb-c').data('val')) || 0
            };
        }
        
        this.isPlaying = true;
        $('#play-timeline').addClass('hidden');
        $('#pause-timeline').removeClass('hidden');
        $('#timeline-scrubber').prop('disabled', true);
        
        this.playNextStage();
    }

    pausePlayback() {
        if (!this.isPlaying) return;
        
        this.isPlaying = false;
        $('#play-timeline').removeClass('hidden');
        $('#pause-timeline').addClass('hidden');
        $('#timeline-scrubber').prop('disabled', false);
        
        if (this.playbackTimeout) {
            clearTimeout(this.playbackTimeout);
            this.playbackTimeout = null;
        }
    }

    stopPlayback() {
        this.pausePlayback();
        this.currentStageIndex = 0;
        $('#timeline-scrubber').val(0);
        $('#timeline-position').text('0%');
        // Remove all active classes
        $('#stage-timeline .stage-card').removeClass('border-2 border-white bg-esper-yellow bg-opacity-50');
        
        // Show all regions again
        const regions = ['front', 'back', 'left', 'right', 'top', 'bottom'];
        if (window.setClusterRegionVisibility) {
            window.setClusterRegionVisibility(regions, true);
        }

        // Restore original modeling light values and visuals
        if (window.setLightTypeBrightness) {
            window.setLightTypeBrightness('parallel', this.originalModelingLight.parallel);
            window.setLightTypeBrightness('cross', this.originalModelingLight.cross);
            window.setLightTypeBrightness('neutral', this.originalModelingLight.neutral);

            // Restore modeling light visuals
            const $root = $('#modeling-light');
            if ($root.length) {
                const bulbMap = {
                    'parallel': '#bulb-a',
                    'cross': '#bulb-b',
                    'neutral': '#bulb-c'
                };

                // Reset all bulbs and sliders first
                $root.find('.bulb').each(function() {
                    const $bulb = $(this);
                    const $glow = $bulb.find('.glow');
                    $bulb.css('background', this.toColor(0))
                         .find('.percent').text('0%')
                         .end().data('val', 0);
                    $glow.css({ opacity: this.toOpacity(0), transform: `scale(${this.toScale(0)})` });
                });

                // Reset all sliders to 0
                $root.find('.ctrl .range').val(0);
                $root.find('.ctrl .number').val('0.00');

                // Restore original values
                Object.entries(this.originalModelingLight).forEach(([type, value]) => {
                    const $bulb = $root.find(bulbMap[type]);
                    if ($bulb.length) {
                        const $glow = $bulb.find('.glow');
                        $bulb.css('background', this.toColor(value))
                             .find('.percent').text(value + '%')
                             .end().data('val', value);
                        $glow.css({ opacity: this.toOpacity(value), transform: `scale(${this.toScale(value)})` });

                        // Restore the corresponding slider and number input
                        const $ctrl = $root.find(`.ctrl[data-target="${$bulb.attr('id')}"]`);
                        if ($ctrl.length) {
                            $ctrl.find('.range').val(value);
                            $ctrl.find('.number').val(value.toFixed(2));
                        }
                    }
                });
            }
        }
    }

    playNextStage() {
        if (!this.isPlaying) return;

        const $cards = $('#stage-timeline .stage-card');
        if ($cards.length === 0) {
            this.stopPlayback();
            return;
        }

        // Update active stage
        this.updateActiveStage();

        // Get current stage settings
        const $currentCard = $cards.eq(this.currentStageIndex);
        const direction = $currentCard.find('select[name^="direction"]').val();
        const brightness = parseInt($currentCard.find('input[name^="brightness"]').val());
        const duration = parseFloat($currentCard.find('input[name^="flash_duration"]').val()) * 1000; // Convert to milliseconds

        // Reset all regions to 0 first
        const regions = ['front', 'back', 'left', 'right', 'top', 'bottom'];
        regions.forEach(region => {
            window.setRegionBrightness(region, 0);
        });

        // Hide all regions first
        if (window.setClusterRegionVisibility) {
            window.setClusterRegionVisibility(regions, false);
        }

        // Set brightness based on direction
        if (direction === 'GI') {
            // Global illumination - set all regions to same brightness and show all
            regions.forEach(region => {
                window.setRegionBrightness(region, brightness);
            });
            if (window.setClusterRegionVisibility) {
                window.setClusterRegionVisibility(regions, true);
            }
        } else {
            // Set specific region brightness and show only that region
            const regionMap = {
                'FRONT': 'front',
                'BACK': 'back',
                'LEFT': 'left',
                'RIGHT': 'right',
                'TOP': 'top',
                'BOTTOM': 'bottom'
            };
            const region = regionMap[direction];
            if (region) {
                window.setRegionBrightness(region, brightness);
                if (window.setClusterRegionVisibility) {
                    window.setClusterRegionVisibility(region, true);
                }
            }
        }

        // Update modeling light based on LED selection
        if (window.setLightTypeBrightness) {
            const ledType = $currentCard.find('input[type=radio][name^="led"]:checked').val();
            const modelingLightMap = {
                'PARALLEL': 'parallel',
                'CROSS': 'cross',
                'NEUTRAL': 'neutral'
            };

            // Reset ALL modeling lights to 0 first
            Object.values(modelingLightMap).forEach(type => {
                window.setLightTypeBrightness(type, 0);
                
                // Reset all bulb visuals
                const $root = $('#modeling-light');
                if ($root.length) {
                    const bulbMap = {
                        'parallel': '#bulb-a',
                        'cross': '#bulb-b',
                        'neutral': '#bulb-c'
                    };
                    const $bulb = $root.find(bulbMap[type]);
                    if ($bulb.length) {
                        const $glow = $bulb.find('.glow');
                        $bulb.css('background', this.toColor(0))
                             .find('.percent').text('0%')
                             .end().data('val', 0);
                        $glow.css({ opacity: this.toOpacity(0), transform: `scale(${this.toScale(0)})` });

                        // Reset the corresponding slider and number input
                        const $ctrl = $root.find(`.ctrl[data-target="${$bulb.attr('id')}"]`);
                        if ($ctrl.length) {
                            $ctrl.find('.range').val(0);
                            $ctrl.find('.number').val('0.00');
                        }
                    }
                }
            });
            
            // Set the selected LED type to the stage brightness
            const modelingType = modelingLightMap[ledType];
            if (modelingType) {
                window.setLightTypeBrightness(modelingType, brightness);
            }

            // Update modeling light visuals for active bulb
            const $root = $('#modeling-light');
            if ($root.length) {
                const bulbMap = {
                    'PARALLEL': '#bulb-a',
                    'CROSS': '#bulb-b',
                    'NEUTRAL': '#bulb-c'
                };
                const $activeBulb = $root.find(bulbMap[ledType]);
                if ($activeBulb.length) {
                    const $glow = $activeBulb.find('.glow');
                    $activeBulb.css('background', this.toColor(brightness))
                             .find('.percent').text(brightness + '%')
                             .end().data('val', brightness);
                    $glow.css({ opacity: this.toOpacity(brightness), transform: `scale(${this.toScale(brightness)})` });

                    // Update the corresponding slider and number input
                    const $ctrl = $root.find(`.ctrl[data-target="${$activeBulb.attr('id')}"]`);
                    if ($ctrl.length) {
                        $ctrl.find('.range').val(brightness);
                        $ctrl.find('.number').val(brightness.toFixed(2));
                    }
                }
            }

            // Store current LED type for next iteration
            this.previousLedType = ledType;
        }

        // Move to next stage
        this.currentStageIndex = (this.currentStageIndex + 1) % $cards.length;

        // Schedule next stage
        this.playbackTimeout = setTimeout(() => {
            this.playNextStage();
        }, duration);
    }

    updateActiveStage() {
        // Remove active class from all cards
        $('#stage-timeline .stage-card').removeClass('border-2 border-white bg-esper-yellow bg-opacity-50');
        
        // Add active class to current card
        const $currentCard = $('#stage-timeline .stage-card').eq(this.currentStageIndex);
        $currentCard.addClass('border-2 border-white bg-esper-yellow bg-opacity-50');

        // Scroll to active card
        const $timeline = $('#stage-timeline');
        $timeline.animate({
            scrollLeft: $currentCard.offset().left - $timeline.offset().left + $timeline.scrollLeft()
        }, 300);
    }

    // Visual helper functions
    toColor(p) {
        return `hsl(55 100% ${20 + 70 * (p / 100)}%)`;
    }

    toOpacity(p) {
        return 0.05 + 0.95 * (p / 100);
    }

    toScale(p) {
        return 0.8 + 0.7 * (p / 100);
    }

    // Add debounce utility
    debounce(func, wait) {
        return (...args) => {
            clearTimeout(this.saveTimeout);
            this.saveTimeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Create debounced save method
    debouncedSave = this.debounce(function() {
        this.saveStages();
    }, 1000); // Wait 1 second after last change before saving

    // Add this new method
    updateTimelineControlsVisibility() {
        const $timeline = $('#stage-timeline');
        const $controls = $('.timeline-controls');
        const hasStages = $timeline.find('.stage-card').length > 0;
        
        if (hasStages) {
            $controls.removeClass('opacity-0').addClass('opacity-100');
        } else {
            $controls.removeClass('opacity-100').addClass('opacity-0');
        }
    }
}


/**
 * Modeling-Light controller with autosave
 *  – Drag on bulb, sliders, number boxes remain in sync
 *  – Saves to WP/ACF via admin-ajax.php after user stops moving (debounced)
 *
 *  Requires:
 *      1.  jQuery (bundled with WP)
 *      2.  A global  `esperApi` object with:
 *              { ajaxurl, nonce }          // you already have this for stage-composer
 *      3.  `store.navigationManager.getPostIdByCriteria('capture')` returning capture ID
 *
 *  PHP side (example) – register an ajax handler "modeling_light_save"
 *      update_post_meta( $light_id, 'light_brightness_parallel', $_POST['parallel'] );
 *      update_post_meta( $light_id, 'light_brightness_cross',    $_POST['cross']    );
 *      update_post_meta( $light_id, 'light_brightness_neutral',  $_POST['neutral']  );
 */
(function ($) {

   // window.setRegionBrightness('front', 50);

    /* ───── visual helpers ───── */
    const toColor   = p => `hsl(55 100% ${20 + 70 * (p / 100)}%)`,
          toOpacity = p => 0.05 + 0.95 * (p / 100),
          toScale   = p => 0.8  + 0.7  * (p / 100),
          clamp     = v => Math.max(0, Math.min(100, v)),
          valOf     = $b => parseFloat($b.data('val')) || 0;
  
    /* ───── debounce util ───── */
    const debounce = (fn, ms = 800) => {
        let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, a), ms); };
    };
  
    /* global debounced saver (shared by all widgets) */
    const debouncedSave = debounce(saveBrightness);
  
    /* ───── main widget init (once per #modeling-light) ───── */
    function init($root) {
        if (!$root.length || $root.data('ml-ready')) return;
        $root.data('ml-ready', true);
  
        // inside init($root) – remove the hard-coded 0 loop and add:
$root.find('.ctrl .range').each(function () {
    const id  = $(this).closest('.ctrl').data('target');
    const val = parseFloat(this.value) || 0;
    sync($root, id, val);           // draw bulb + glow to saved level
});
  
        /* ---------------- DRAG ---------------- */
        let $drag = null, startX = 0, startVal = 0;
        $root.on('pointerdown', '.bulb', e => {
            if (e.button !== 0) return;
            $drag   = $(e.currentTarget);
            startX  = e.clientX;
            startVal= valOf($drag);
            $drag[0].setPointerCapture(e.pointerId);
        }).on('pointermove', e => {
            if (!$drag) return;
            const v = clamp(startVal + (e.clientX - startX) / 2);   // 2 px = 1 %
            sync($root, $drag.attr('id'), v, true);
        }).on('pointerup pointercancel', () => $drag = null);
  
        /* ------------- sliders / numbers ------------- */
        $root.on('input change', '.ctrl .range', function () {
            sync($root, $(this).closest('.ctrl').data('target'), parseFloat(this.value), true);
        });
        $root.on('input change', '.ctrl .number', function () {
            let v = parseFloat(this.value); if (isNaN(v)) v = 0;
            sync($root, $(this).closest('.ctrl').data('target'), clamp(v), true);
        });
    }
  
    /* ───── sync everything for one bulb ───── */
    function sync($root, id, v, queueSave = false) {
        const $bulb = $root.find('#' + id);
        const $glow = $bulb.find('.glow');
        const $ctrl = $root.find(`.ctrl[data-target="${id}"]`);
  
        $bulb.css('background', toColor(v))
             .find('.percent').text(v.toFixed(2) + '%')
             .end().data('val', v);
        $glow.css({ opacity: toOpacity(v), transform: `scale(${toScale(v)})` });

        /* NEW: broadcast to the 3-D scene */
       const map = { 'bulb-a': 'parallel', 'bulb-b': 'cross', 'bulb-c': 'neutral' };
        if (window.setLightTypeBrightness) {
            window.setLightTypeBrightness(map[id], v);

           
        }
  
        $ctrl.find('.range').val(v);
        $ctrl.find('.number').val(v.toFixed(2));
  
        if (queueSave) debouncedSave();
    }
  
    /* ───── AJAX save to WP/ACF ───── */
    function saveBrightness() {
        const $root = $('#modeling-light');
        if (!$root.length) return;
  
        const payload = {
            parallel : valOf($root.find('#bulb-a')),
            cross    : valOf($root.find('#bulb-b')),
            neutral  : valOf($root.find('#bulb-c'))
        };
  
        const captureId = store.navigationManager.getPostIdByCriteria('capture');
  
        $.post(esperApi.ajaxurl, {
            action     : 'modeling_light_save',
            nonce      : esperApi.nonce,
            capture_id : captureId,
            ...payload                      // parallel, cross, neutral
        }).fail(() => console.error('modeling-light save failed'));
    }
  
    /* ───── auto-initialise widgets ───── */
    $( () => init($('#modeling-light')) );
    new MutationObserver(m => m.forEach(r => r.addedNodes.forEach(n => {
        if (n.nodeType !== 1) return;
        init($(n).is('#modeling-light') ? $(n) : $(n).find('#modeling-light'));
    }))).observe(document.body, { childList: true, subtree: true });
  
  })(jQuery);
  