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

        jQuery(document).on('click', '[data-action="save_as_preset"]', function() {
            var $table = jQuery('.settings-set');
            var cameraSettingsId = jQuery(this).data('id');
            var rowsData = [];
            
            // Get the first row's data
            var $firstRow = $table.find('.settings-row').first();
            var presetData = {
                camera_name: $firstRow.find('input[name="camera_name"]').val() || '',
                serial_number: $firstRow.find('input[name="serial_number"]').val() || '',
                camera_model: $firstRow.find('input[name="camera_model"]').val() || '',
                iso: $firstRow.find('select[name="iso"]').val() || '',
                aperture: $firstRow.find('select[name="aperture"]').val() || '',
                white_balance: $firstRow.find('select[name="white_balance"]').val() || '',
                colour_temp: $firstRow.find('input[name="colour_temp"]').val() || '',
                shutter_speed: $firstRow.find('select[name="shutter_speed"]').val() || '',
                file_type: $firstRow.find('select[name="file_type"]').val() || '',
                jpeg_quality: $firstRow.find('select[name="jpeg_quality"]').val() || '',
                drive_mode: $firstRow.find('select[name="drive_mode"]').val() || '',
                focus_mode: $firstRow.find('select[name="focus_mode"]').val() || ''
            };
            
            // Send AJAX request to create the preset
            jQuery.ajax({
                url: esperApi.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'save_camera_preset',
                    nonce: esperApi.nonce,
                    preset_data: JSON.stringify(presetData)
                },
                success: function(response) {
                    if(response.success) {
                        store.notificationManager.showSuccess('Preset saved successfully.');
                    } else {
                        store.notificationManager.showError('Error: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    store.notificationManager.showError('AJAX error: ' + error);
                }
            });
        });

        jQuery(document).on('change input', '.cameraSettingsContent input, .cameraSettingsContent select, .cameraSettingsContent textarea', function() {
            var $elem = jQuery(this);
            var name = $elem.attr('name');
        
            // Define a list of field names to ignore for sync.
            var ignoredFields = ['camera_name', 'serial_number', 'camera_model'];
            if (ignoredFields.indexOf(name) !== -1) {
                return;
            }
        
            // Get the current row container and its sync checkbox.
            var $currentRow = $elem.closest('.settings-row');
            var $currentSyncCheckbox = $currentRow.find('.syncSetting');
        
            // If the current row's sync checkbox is not checked, don't perform any sync.
            if (!$currentSyncCheckbox.is(':checked')) {
                return;
            }
        
            var type = $elem.attr('type');
            var newVal = $elem.val();
        
            // Loop over each row with a checked syncSetting.
            jQuery('.settings-row').each(function() {
                var $row = jQuery(this);
                var $rowSyncCheckbox = $row.find('.syncSetting');
        
                // Only sync rows where the syncSetting checkbox is checked.
                if ($rowSyncCheckbox.is(':checked')) {
                    if (type === 'radio') {
                        $row.find('input[type="radio"][name="' + name + '"]').each(function() {
                            jQuery(this).prop('checked', jQuery(this).val() === newVal);
                        });
                    }
                    else if (type === 'checkbox') {
                        $row.find('input[type="checkbox"][name="' + name + '"]').prop('checked', $elem.is(':checked'));
                    }
                    else {
                        // For text inputs, selects, and textareas, update all except the source.
                        $row.find('[name="' + name + '"]').not($elem).val(newVal);
                    }
                }
            });
        });

        // Event delegation on document body for dynamic elements
        jQuery(document.body).on('change', '#syncSettings', function() {
            const isChecked = jQuery(this).is(':checked');

            // Update all checkboxes with id 'syncSetting'
            jQuery('input.syncSetting[type="checkbox"]').each(function() {
                jQuery(this).prop('checked', isChecked).trigger('change');
            });
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
                $('#tableViewIcon, #cardViewIcon, #nodeViewIcon').find('.material-symbols-outlined').addClass('opacity-25');

                $(this).find('.material-symbols-outlined').removeClass('opacity-25');
        
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
        
           // Get the field label using the input's id and the matching label's "for" attribute.
            var fieldId = jQuery(this).attr('id');
            var $label = jQuery("label[for='" + fieldId + "']").clone();

            // Remove any Google icon spans (like Material Icons)
            $label.find('.material-symbols-outlined, .google-icon, .icon').remove();

            // Get clean label text
            var fieldLabel = $label.text().trim();

            console.log("Field label:", fieldLabel);

            // Get the capture post ID from your store object.
            const capturePostID = store.navigationManager.getPostIdByCriteria('capture');
            console.log("Capture Post ID:", capturePostID);
        
            // Send the AJAX request to update the ACF field.
            jQuery.ajax({
                url: esperApi.ajaxurl, // ajaxurl is localized.
                method: 'POST',
                data: {
                    action: 'update_acf_field', // custom AJAX action.
                    fieldName: fieldName,
                    fieldValue: fieldValue,
                    capturePostID: capturePostID
                },
                success: function(response) {
                    console.log("ACF field updated", response);
                    // Use the label in the success message.
                    store.notificationManager.showSuccess(fieldLabel + ' updated to ' + fieldValue);
                },
                error: function(error) {
                    console.error("Error updating ACF field", error);
                }
            });
        });
        
    }
}
