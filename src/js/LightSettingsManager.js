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
            $input.siblings('.brightness-label').text(`${$input.val()}%`);
        });

        // Remove row
        $(document).on('click', '#stage-composer-wrapper .remove-stage', e => {
            e.preventDefault();
            // Hide any visible tooltips
            $('.tooltip').fadeOut(100);
            $(e.currentTarget).closest('tr').remove();
            this.refreshNumbers();

            // Check if any stages remain
            if ($('#stage-composer tbody tr').length === 0) {
                $('#stage-composer').addClass('hidden');
                $('#no-stages-message').show();
            }
        });

        // Move up
        $(document).on('click', '#stage-composer-wrapper .move-up', e => {
            e.preventDefault();
            const $tr = $(e.currentTarget).closest('tr');
            const $prev = $tr.prev();
            if ( $prev.length ) {
                $prev.before($tr);
                this.refreshNumbers();
            }
        });

        // Move down
        $(document).on('click', '#stage-composer-wrapper .move-down', e => {
            e.preventDefault();
            const $tr = $(e.currentTarget).closest('tr');
            const $next = $tr.next();
            if ( $next.length ) {
                $next.after($tr);
                this.refreshNumbers();
            }
        });

        // Add below
        $(document).on('click', '#stage-composer-wrapper .add-below', e => {
            e.preventDefault();
            const tpl  = $('#sc-row-tpl').prop('outerHTML');
            const $new = $(tpl).removeAttr('id');
            $(e.currentTarget).closest('tr').after($new);
            this.refreshNumbers();
        });
    }

    refreshNumbers() {
        $('#stage-composer tbody tr').each((i, tr) => {
            // update label
            $(tr).find('.stage-number').text(`Stage ${String(i + 1).padStart(2,'0')}`);
      
            // fix every [__INDEX__] => [i]
            $(tr).find(':input').each(function(){
                const name = $(this).attr('name');
                if (name && name.includes('__INDEX__')) {
                    $(this).attr('name', name.replace('__INDEX__', i));
                }
            });
        });
      }
      

      addRow(data = {}) {
        // Hide no stages message and show table
        $('#no-stages-message').hide();
        $('#stage-composer').removeClass('hidden');

        const tpl  = $('#sc-row-tpl').prop('outerHTML');
        const $row = $(tpl).removeAttr('id');
    
        /* LED radios ------------- */
        if (data.led) {
            $row.find(`input[type=radio][value="${data.led}"]`).prop('checked', true);
        }
    
        /* Direction -------------- */
        if (data.direction) {
            $row.find('select[name^="direction"]').val(data.direction);
        }
    
        /* Brightness ------------- */
        const b = data.brightness != null ? data.brightness : 70;
        $row.find('input[name^="brightness"]')
            .val(b).trigger('input');
    
        /* Flash duration --------- */
        const d = data.flash_duration != null ? data.flash_duration : 5.0;
        $row.find('input[name^="flash_duration"]').val(d);
    
        $('#stage-composer tbody').append($row);
        this.refreshNumbers();                 // ensure placeholders are numbered
    }
    

    saveStages() {
        this.refreshNumbers(); // make sure names are led[0], led[1] …

    const data = $('#stage-composer tbody tr').map((i, tr) => {
        const $tr = $(tr);
        return {
            led:            $tr.find('input[type=radio][name^="led"]:checked').val() || '',
            direction:      $tr.find('select[name^="direction"]').val(),
            brightness:     $tr.find('input[name^="brightness"]').val(),
            flash_duration: $tr.find('input[name^="flash_duration"]').val(),
        };
    }).get();

        console.log(data);

        const captureId = store.navigationManager.getPostIdByCriteria('capture');

        $.ajax({
            url: esperApi.ajaxurl,
            type: 'POST',
            data: {
                action:  'stage_composer_save',
                capture_id: captureId,
                nonce: esperApi.nonce,
                data:    JSON.stringify(data)
            },
            success: (response) => {
                if (response.success) {
                    console.log(response);
                    store.notificationManager.showSuccess('Stages updated successfully.');
                } else {
                    alert(`Error: ${response.data}`);
                }
            },
            error: () => {
                alert('Failed to add export to queue.');
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
  