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
            var $table = jQuery('.settings-set');
            // Assume the table has a data attribute "data-postid" with the Camera Settings post ID.
            var cameraSettingsId = jQuery(this).data('id');
            var rowsData = [];
            
            // Loop through each row in the table body.
            $table.find('.settings-row').each(function() {
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

                        store.notificationManager.showSuccess('Camera settings updated successfully.');
                    } else {
                        store.notificationManager.showSuccess('Error: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX error: ' + error);
                }
            });
        });

        jQuery(document).on('change input', '.cameraSettingsContent input, .cameraSettingsContent select, .cameraSettingsContent textarea', function() {
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
                    jQuery('.cameraSettingsContent input[type="radio"][name="' + name + '"]').each(function() {
                        jQuery(this).prop('checked', jQuery(this).val() === newVal);
                    });
                }
                // Handle checkboxes.
                else if (type === 'checkbox') {
                    var isChecked = $elem.is(':checked');
                    jQuery('.cameraSettingsContent input[type="checkbox"][name="' + name + '"]').prop('checked', isChecked);
                }
                // Handle other input types, selects, and textareas.
                else {
                    var newVal = $elem.val();
                    jQuery('.cameraSettingsContent [name="' + name + '"]').not(this).val(newVal);
                }
            }
        });
        
        jQuery(document).ready(function($) {
            // Listen for clicks on either the table or card icon.

            jQuery(document).on('click', '#tableViewIcon, #cardViewIcon, #nodeViewIcon', function(e) {
                e.preventDefault();
                // Determine the selected view based on the clicked element's id.
                var id = $(this).attr('id');
                var view;
                
                if (id === 'tableViewIcon') {
                    view = 'table';
                } else if (id === 'cardViewIcon') {
                    view = 'card';
                } else if (id === 'nodeViewIcon') {
                    view = 'node';
                }
                
                console.log('view');
                console.log(view);
                
                // Toggle an active state (optional)
                $('#tableViewIcon, #cardViewIcon, #nodeViewIcon').find('.material-icons').addClass('opacity-25');

                $(this).find('.material-icons').removeClass('opacity-25');
        
                // Use ajaxurl global variable provided by WordPress for AJAX calls.
                $.ajax({
                    url: esperApi.ajaxurl, // global AJAX URL for WordPress admin-ajax.php
                    type: 'POST',
                    data: {
                        action: 'get_camera_settings_view', // your AJAX action hook
                        view: view,
                        post_id: store.navigationManager.getPostIdByCriteria('capture') // Replace with your dynamic post ID, or pass it as a data attribute.
                    },
                    beforeSend: function() {
                        $('.cameraSettingsContent').html('<p>Loading...</p>');
                    },
                    success: function(response) {
                        $('.cameraSettingsContent').html(response);
                    },
                    error: function() {
                        $('.cameraSettingsContent').html('<p>Error loading view.</p>');
                    }
                });
            });
        });

        jQuery(document).on('change', '.camera-quick-settings input, .camera-quick-settings select, .camera-quick-settings textarea', function() {
            var fieldName  = jQuery(this).attr('name');
            var fieldValue = jQuery(this).val();
            console.log("Field name:", fieldName, "Field value:", fieldValue);
        
            // Get the capture post ID from your store object.
            const capturePostID = store.navigationManager.getPostIdByCriteria('capture');
            console.log("Capture Post ID:", capturePostID);
        
            // Send the AJAX request to update the ACF field.
            jQuery.ajax({
                url: esperApi.ajaxurl, // Make sure ajaxurl is defined in your script localization.
                method: 'POST',
                data: {
                    action: 'update_acf_field', // custom AJAX action.
                    fieldName: fieldName,
                    fieldValue: fieldValue,
                    capturePostID: capturePostID
                },
                success: function(response) {
                    console.log("ACF field updated", response);
                    store.notificationManager.showSuccess(fieldName +' updated to '+fieldValue);
                },
                error: function(error) {
                    console.error("Error updating ACF field", error);
                }
            });
        });
        
        
        
        
        
        
    }
}
