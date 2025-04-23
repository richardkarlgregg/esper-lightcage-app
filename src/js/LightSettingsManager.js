import $ from 'jquery';
import store from './Store.js';

export default class LightSettingsManager {
    constructor() {
        this.init();
    }

    init() {
        // Once the wrapper is injected, populate saved rows exactly once
        $(document).on('ready ajaxComplete', () => {
            const $wrapper = $('#stage-composer-wrapper');
            if ( ! $wrapper.length || $wrapper.data('lsm-initialized') ) return;
            $wrapper.data('lsm-initialized', true);

            // Load initial saved stages
            (store.stages || []).forEach(stage => this.addRow(stage));
            this.refreshNumbers();
        });

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
            $(e.currentTarget).closest('tr').remove();
            this.refreshNumbers();
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
            $(tr).find('.stage-number').text(
                `Stage ${String(i + 1).padStart(2, '0')}`
            );
        });
    }

    addRow(data = {}) {
        // Always grab fresh template
        const tpl  = $('#sc-row-tpl').prop('outerHTML');
        const $row = $(tpl).removeAttr('id');

        // Populate LED radios
        if ( data.led ) {
            $row.find(`input[name="led[]"][value="${data.led}"]`)
                .prop('checked', true);
        }

        // Populate Direction select
        if ( data.direction ) {
            $row.find('select[name="direction[]"]').val(data.direction);
        }

        // Populate Brightness range
        const brightness = data.brightness != null ? data.brightness : 70;
        $row.find('input[name="brightness[]"]')
            .val(brightness)
            .trigger('input');

        // Populate Flash Duration number
        const duration = data.flash_duration != null ? data.flash_duration : 5.0;
        $row.find('input[name="flash_duration[]"]')
            .val(duration);

        // Append to table body
        $('#stage-composer tbody').append($row);
    }

    saveStages() {
        const $rows = $('#stage-composer tbody tr');
        const data = $rows.map((i, tr) => {
            const $tr = $(tr);
            return {
                led:            $tr.find('input[name="led[]"]:checked').val() || '',
                direction:      $tr.find('select[name="direction[]"]').val(),
                brightness:     $tr.find('input[name="brightness[]"]').val(),
                flash_duration: $tr.find('input[name="flash_duration[]"]').val(),
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
                    alert('Stages saved!');
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
