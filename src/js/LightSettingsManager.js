import $ from 'jquery';
import store from './Store.js';

export default class LightSettingsManager {
    constructor() {
        this.init();
    }

    init() {
        // Delegate all clicks and inputs from document

        // Add Stage button
        $(document).on('click', '#stage-composer-wrapper #add-stage', e => {
            e.preventDefault();
            this.addRow();
            this.refreshNumbers();
        });

        // Save Stages button
        $(document).on('click', '#stage-composer-wrapper #save-stages', e => {
            e.preventDefault();
            this.saveStages();
        });

        // Brightness slider update
        $(document).on('input', '#stage-composer-wrapper input[type=range]', e => {
            const $input = $(e.currentTarget);
            const $card = $input.closest('.stage-card');
            $card.find('.text-right').text(`${$input.val()}%`);
        });

        // Remove stage
        $(document).on('click', '#stage-composer-wrapper .remove-stage', e => {
            e.preventDefault();
            $(e.currentTarget).closest('.stage-card').remove();
            this.refreshNumbers();
        });

        // Move up
        $(document).on('click', '#stage-composer-wrapper .move-up', e => {
            e.preventDefault();
            const $card = $(e.currentTarget).closest('.stage-card');
            const $prev = $card.prev();
            if ($prev.length) {
                $prev.before($card);
                this.refreshNumbers();
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
            }
        });

        // Add below
        $(document).on('click', '#stage-composer-wrapper .add-below', e => {
            e.preventDefault();
            const $card = $(e.currentTarget).closest('.stage-card');
            this.addRow();
            $card.after($('#stage-timeline .stage-card:last'));
            this.refreshNumbers();
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
        
        // Add the header with Add Stage button
        $wrapper.prepend(`
            <div class="flex justify-end px-4 py-2">
                <button id="add-stage" class="bg-esper-yellow text-black px-3 py-1 rounded flex items-center gap-1 text-sm">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Add Stage
                </button>
            </div>
        `);
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
        
        // Row 1 - LED + Direction
        const $row1 = $('<div class="flex gap-1 items-center">');
        
        // LED radios
        const $ledGroup = $('<div class="flex items-center gap-[2px]">');
        ['CROSS', 'NEUTRAL', 'PARALLEL'].forEach((value, i) => {
            const label = ['C', 'N', 'P'][i];
            $ledGroup.append(`
                <label class="flex items-center gap-[2px]">
                    <input type="radio" value="${value}" name="led[__INDEX__]" ${data.led === value ? 'checked' : ''}>
                    ${label}
                </label>
            `);
        });
        $row1.append($ledGroup);

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
                    store.notificationManager.showSuccess('Stages updated successfully.');
                } else {
                    alert(`Error: ${response.data}`);
                }
            },
            error: () => {
                alert('Failed to save stages.');
            }
        });
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

    window.setRegionBrightness('front', 50);

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
  