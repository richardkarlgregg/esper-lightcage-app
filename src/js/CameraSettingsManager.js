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
            var cameraSettingsId = jQuery(this).data('id');
            
            if (!cameraSettingsId) {
                store.notificationManager.showError('No camera settings ID found.');
                return;
            }

            // Get the first row's data from the camera settings
            jQuery.ajax({
                url: esperApi.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_camera_settings',
                    nonce: esperApi.nonce,
                    camera_settings_id: cameraSettingsId
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var settings = response.data;
                        if (settings.length > 0) {
                            var presetData = settings[0];
                            showPresetNameInput(presetData);
                        } else {
                            store.notificationManager.showError('No camera settings found to save as preset.');
                        }
                    } else {
                        store.notificationManager.showError('Error retrieving camera settings: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    store.notificationManager.showError('AJAX error: ' + error);
                }
            });
        });

        function showPresetNameInput(presetData) {
            // Get the button that was clicked
            const $button = jQuery('[data-action="save_as_preset"]');
            
            // Create the input container
            const $inputContainer = jQuery('<div>', {
                class: 'flex items-center space-x-2 mt-2'
            });
            
            // Create the input field
            const $input = jQuery('<input>', {
                type: 'text',
                class: 'bg-black border border-white border-opacity-25 text-white p-2 rounded flex-grow',
                placeholder: 'Enter preset name',
                id: 'preset-name-input'
            });
            
            // Create the save button
            const $saveButton = jQuery('<button>', {
                class: 'bg-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded',
                text: 'Save'
            }).on('click', function() {
                const presetName = $input.val().trim();
                if (!presetName) {
                    store.notificationManager.showError('Please enter a preset name');
                    return;
                }
                
                // Add the preset name to the data
                presetData.preset_name = presetName;
                
                // Send AJAX request to save the preset
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
                        if (response.success) {
                            store.notificationManager.showSuccess('Preset saved successfully.');
                            // Remove the input container
                            $inputContainer.remove();
                            // Show the original button again
                            $button.show();
                            // Refresh the current screen using NavigationManager
                            store.navigationManager.refreshCurrentScreen();
                        } else {
                            store.notificationManager.showError('Error saving preset: ' + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        store.notificationManager.showError('AJAX error: ' + error);
                    }
                });
            });
            
            // Create the cancel button
            const $cancelButton = jQuery('<button>', {
                class: 'bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded',
                text: 'Cancel'
            }).on('click', function() {
                // Remove the input container
                $inputContainer.remove();
                // Show the original button again
                $button.show();
            });
            
            // Add the input and buttons to the container
            $inputContainer.append($input, $saveButton, $cancelButton);
            
            // Hide the original button
            $button.hide();
            
            // Add the input container after the button
            $button.after($inputContainer);
            
            // Focus the input field
            $input.focus();
        }

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
        
        jQuery(document).on('click', '[data-action="load_preset"]', function() {
            var presetId = jQuery('#preset-select').val();
            var cameraSettingsId = jQuery(this).data('camera-settings-id');
            
            if (!presetId) {
                store.notificationManager.showError('Please select a preset to load.');
                return;
            }
            
            if (!cameraSettingsId) {
                store.notificationManager.showError('No camera settings ID found.');
                return;
            }
            
            // Get the preset data
            jQuery.ajax({
                url: esperApi.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_preset_data',
                    nonce: esperApi.nonce,
                    preset_id: presetId
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var presetData = response.data;
                        
                        // Apply the preset to the camera settings
                        applyPresetToCameraSettings(presetData, cameraSettingsId);
                    } else {
                        store.notificationManager.showError('Error retrieving preset data: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    store.notificationManager.showError('AJAX error: ' + error);
                }
            });
        });
        
        function applyPresetToCameraSettings(presetData, cameraSettingsId) {
            jQuery.ajax({
                url: esperApi.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'apply_preset_to_camera_settings',
                    nonce: esperApi.nonce,
                    preset_data: JSON.stringify(presetData),
                    camera_settings_id: cameraSettingsId
                },
                success: function(response) {
                    if (response.success) {
                        store.notificationManager.showSuccess('Preset applied successfully.');
                        // Refresh the current screen using NavigationManager
                        store.navigationManager.refreshCurrentScreen();
                    } else {
                        store.notificationManager.showError('Error applying preset: ' + response.data);
                    }
                },
                error: function(xhr, status, error) {
                    store.notificationManager.showError('AJAX error: ' + error);
                }
            });
        }
    }
}
