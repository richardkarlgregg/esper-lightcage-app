import $ from 'jquery';
import store from './Store.js';

export default class CameraSettingsManager {

    constructor() {
        this.init();
    }

    init() {
    }

    setupEventListeners() {
        jQuery(document).on('click', '[data-action="save_camera_settings"]', function() {
            var $table = jQuery('.input-set-table');
            // Assume the table has a data attribute "data-postid" with the Camera Settings post ID.
            var cameraSettingsId = jQuery(this).data('id');
            var rowsData = [];
            
            // Loop through each row in the table body.
            $table.find('tbody tr').each(function() {
                var $row = jQuery(this);
                var rowData = {
                    camera_name: $row.find('input[name="camera_name"]').val() || '',
                    serial_number: $row.find('input[name="serial_number"]').val() || '',
                    camera_model: $row.find('input[name="camera_model"]').val() || '',
                    iso: $row.find('select[name="iso"]').val() || '',
                    aperture: $row.find('select[name="aperture"]').val() || '',
                    white_balance: $row.find('select[name="white_balance"]').val() || '',
                    colour_temp: $row.find('input[name="colour_temp"]').val() || '',
                    shutter_speed: $row.find('select[name="shutter_speed"]').val() || '',
                    file_type: $row.find('select[name="file_type"]').val() || '',
                    jpeg_quality: $row.find('select[name="jpeg_quality"]').val() || '',
                    drive_mode: $row.find('select[name="drive_mode"]').val() || '',
                    focus_mode: $row.find('select[name="focus_mode"]').val() || ''
                };
                rowsData.push(rowData);
            });
            
            // Send AJAX request to update the repeater field.
            jQuery.ajax({
                url: esperApi.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'update_camera_settings_repeater',
                    nonce: esperApi.nonce,
                    post_id: cameraSettingsId,
                    rows: JSON.stringify(rowsData)
                },
                success: function(response) {
                    if(response.success) {
                        alert('Camera settings updated successfully.');
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX error: ' + error);
                }
            });
        });

        jQuery(document).on('change input', 'table input, table select, table textarea', function() {
            // Only sync if the checkbox is checked.
            if (jQuery('#syncSettings').is(':checked')) {
                var $elem = jQuery(this);
                var name = $elem.attr('name');
        
                // Define a list of field names to ignore for sync.
                var ignoredFields = ['camera_name', 'serial_number', 'camera_model']; // Replace with your actual field names.
        
                // If the field name is in the ignored list, exit.
                if (ignoredFields.indexOf(name) !== -1) {
                    return;
                }
        
                var type = $elem.attr('type');
        
                // Handle radio buttons.
                if (type === 'radio') {
                    var newVal = $elem.val();
                    jQuery('table input[type="radio"][name="' + name + '"]').each(function() {
                        jQuery(this).prop('checked', jQuery(this).val() === newVal);
                    });
                }
                // Handle checkboxes.
                else if (type === 'checkbox') {
                    var isChecked = $elem.is(':checked');
                    jQuery('table input[type="checkbox"][name="' + name + '"]').prop('checked', isChecked);
                }
                // Handle other input types, selects, and textareas.
                else {
                    var newVal = $elem.val();
                    jQuery('table [name="' + name + '"]').not(this).val(newVal);
                }
            }
        });
        
        
        
        
    }
}
