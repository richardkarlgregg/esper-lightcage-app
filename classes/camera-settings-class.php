<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class CameraSettings {

    public $post;
    public $cameraSettingsId;
    public $rows;
    public $baseFields;

    /**
     * Constructor.
     *
     * @param WP_Post $post The parent capture post.
     */
    public function __construct( $post ) {
        $this->post = $post;
        $this->cameraSettingsId = $this->getCameraSettingsId();
        $this->rows = $this->getRepeaterRows();
        $this->baseFields = $this->getBaseInputSet();
    }

    /**
     * Get the camera settings post ID from the parent capture.
     *
     * @return mixed The camera settings post ID or false if not found.
     */
    protected function getCameraSettingsId() {
        return get_post_meta( $this->post->ID, 'capture_camera_settings', true );
    }

    /**
     * Retrieve the repeater rows for the camera settings.
     *
     * @return array The repeater rows.
     */
    protected function getRepeaterRows() {
        $rows = get_field( 'camera_settings_repeater', $this->cameraSettingsId );
        return is_array( $rows ) ? $rows : array();
    }

    /**
     * Return the base input set configuration for camera settings.
     *
     * @return array
     */

    protected function getBaseInputSet() {
        return array(
            'set_name'  => 'Camera Settings',
            'set_slug'  => 'camera_settings',
            'fieldSets' => array(
                array(
                    'field_name' => 'Camera Name',
                    'field_slug' => 'camera_name',
                    'type'       => 'text',
                    'display'    => true,
                    'value'      => '',
                    'hide_label' => true,
                    'icon' => null,
                    'show_icon' => false,
                    'placeholder'=> 'Enter camera name',
                    'beforeHTML' => '<td class="break-words border-r border-esper-yellow">',
                    'afterHTML'  => '</td>',
                ),
                array(
                    'field_name' => 'Serial Number',
                    'field_slug' => 'serial_number',
                    'type'       => 'readOnly',
                    'display'    => true,
                    'value'      => '',
                    'hide_label' => true,
                    'icon' => null,
                    'show_icon' => false,
                    'placeholder'=> 'Enter serial number',
                    'beforeHTML' => '<td class="break-words border-r border-esper-yellow">',
                    'afterHTML'  => '</td>',
                ),
                array(
                    'field_name' => 'Camera Model',
                    'field_slug' => 'camera_model',
                    'type'       => 'readOnly',
                    'display'    => true,
                    'value'      => '',
                    'hide_label' => true,
                    'icon' => null,
                    'show_icon' => false,
                    'placeholder'=> 'Enter camera model',
                    'beforeHTML' => '<td class="break-words border-r border-esper-yellow">',
                    'afterHTML'  => '</td>',
                ),
                array(
                    'field_name' => 'ISO',
                    'field_slug' => 'iso',
                    'type'       => 'select',
                    'value'      => '',
                    'display'    => true,
                    'hide_label' => true,
                    'icon' => 'filter_tilt_shift',
                    'show_icon' => false,
                    'options'    => get_iso_options(),
                    'beforeHTML' => '<td class="break-words border-r border-esper-yellow">',
                    'afterHTML'  => '</td>',
                ),
                array(
                    'field_name' => 'Aperture',
                    'field_slug' => 'aperture',
                    'type'       => 'select',
                    'value'      => '',
                    'display'    => true,
                    'hide_label' => true,
                    'icon' => 'camera',
                    'show_icon' => false,
                    'options'    => get_aperture_options(),
                    'beforeHTML' => '<td class="break-words border-r border-esper-yellow">',
                    'afterHTML'  => '</td>',
                ),
                array(
                    'field_name' => 'White Balance',
                    'field_slug' => 'white_balance',
                    'type'       => 'select',
                    'value'      => '',
                    'display'    => true,
                    'hide_label' => true,
                    'icon' => 'wb_sunny',
                    'show_icon' => false,
                    'options'    => get_white_balance_options(),
                    'beforeHTML' => '<td class="break-words border-r border-esper-yellow">',
                    'afterHTML'  => '</td>',
                ),
                array(
                    'field_name' => 'Colour Temp',
                    'field_slug' => 'colour_temp',
                    'type'       => 'range',
                    'hide_label' => true,
                    'icon' => 'heat',
                    'show_icon' => false,
                    'value'      => '3000',
                    'display'    => true,
                    'attributes' => array(
                        'min'  => '1000',
                        'max'  => '5000',
                        'step' => '100',
                    ),
                    'beforeHTML' => '<td class="break-words border-r border-esper-yellow">',
                    'afterHTML'  => '</td>',
                ),
                array(
                    'field_name' => 'Shutter Speed',
                    'field_slug' => 'shutter_speed',
                    'type'       => 'select',
                    'value'      => '',
                    'display'    => true,
                    'hide_label' => true,
                    'icon' => 'shutter_speed',
                    'show_icon' => false,
                    'options'    => get_shutter_speed_options(),
                    'beforeHTML' => '<td class="break-words border-r border-esper-yellow">',
                    'afterHTML'  => '</td>',
                ),
                array(
                    'field_name' => 'File Type',
                    'field_slug' => 'file_type',
                    'type'       => 'select',
                    'value'      => '',
                    'display'    => true,
                    'hide_label' => true,
                    'icon' => 'image',
                    'show_icon' => false,
                    'options'    => get_file_type_options(),
                    'beforeHTML' => '<td class="break-words border-r border-esper-yellow">',
                    'afterHTML'  => '</td>',
                ),
                array(
                    'field_name' => 'JPEG Quality',
                    'field_slug' => 'jpeg_quality',
                    'type'       => 'select',
                    'value'      => '',
                    'display'    => true,
                    'hide_label' => true,
                    'icon' => 'photo_size_select_large',
                    'show_icon' => false,
                    'options'    => get_jpeg_quality_options(),
                    'beforeHTML' => '<td class="break-words border-r border-esper-yellow">',
                    'afterHTML'  => '</td>',
                ),
                
                array(
                    'field_name' => 'Drive Mode',
                    'field_slug' => 'drive_mode',
                    'type'       => 'select',
                    'value'      => '',
                    'display'    => true,
                    'hide_label' => true,
                    'icon' => 'toggle_off',
                    'show_icon' => false,
                    'options'    => get_drive_mode_options(),
                    'beforeHTML' => '<td class="break-words border-r border-esper-yellow">',
                    'afterHTML'  => '</td>',
                ),
                array(
                    'field_name' => 'Focus Mode',
                    'field_slug' => 'focus_mode',
                    'type'       => 'select',
                    'value'      => '',
                    'display'    => true,
                    'hide_label' => true,
                    'icon' => 'center_focus_weak',
                    'show_icon' => false,
                    'options'    => get_focus_mode_options(),
                    'beforeHTML' => '<td class="break-words border-r border-esper-yellow">',
                    'afterHTML'  => '</td>',
                ),
            ),
        );
    }

    /**
     * Renders the header with Back and Save Settings buttons.
     *
     * @return string
     */
    public function renderHeader() {
        if ( ! $this->cameraSettingsId ) {
            return '<p>No camera settings found.</p>';
        }
        $html = '<div class="w-full space-y-6">';
            $html .= '<div class="w-full flex justify-between flex-wrap mb-4">';
            $html .= '<div class="bg-esper-yellow cursor-pointer text-black px-6 py-3 rounded-lg font-semibold flex items-center" data-action="back">Back</div>';
            $html .= '<div class="bg-esper-yellow cursor-pointer text-black px-6 py-3 rounded-lg font-semibold flex items-center" data-id="' . esc_attr( $this->cameraSettingsId ) . '" data-action="save_camera_settings">Save Settings</div>';
        $html .= '</div>';

        $html .= '<div class="flex items-center justify-between text-xs">';

           

            $html .= '<style>
    /* Custom checkbox styling */
    .custom-checkbox input:checked + .checkmark {
        display: flex;
    }
    .custom-checkbox .checkmark {
        display: none;
    }
</style>';

            $html .= '<div class="custom-checkbox flex items-center justify-between text-xs">';
            $html .= '  <label for="syncSettings" class="uppercase text-esper-yellow relative flex justify-start items-center cursor-pointer">';
            $html .= '    <input type="checkbox" id="syncSettings" name="syncSettings" value="1" class="appearance-none h-5 w-5 bg-black border border-esper-yellow focus:outline-none">';
            $html .= ' <div class="checkmark absolute inset-0 items-center justify-start text-esper-yellow pointer-events-none -ml-1 material-icons cursor-pointer">check</div>';
            $html .= '    <span class="ml-2">Sync Settings</span>';
            $html .= '  </label>';
            $html .= '</div>';


            //$html .= 'First screen needs to be option to choose sync settings or individual controls';

            //$html .= 'For sync control need ability to add controls to capture screen for quick control?';

            // Add view switcher icons using Google Material Icons.
            $html .= '<div class="view-switcher flex items-center space-x-2">';
               
                $html .= '<div id="cardViewIcon" class="w-auto flex flex-wrap">';
                    $html .= '<div class="w-5 h-5 material-icons text-esper-yellow cursor-pointer">view_module</div>';
                $html .= '</div>';

                $html .= '<div id="tableViewIcon" class="w-auto flex flex-wrap">';
                    $html .= '<div class="w-5 h-5 material-icons text-esper-yellow cursor-pointer opacity-25">table_chart</div>';
                $html .= '</div>';

                $html .= '<div id="nodeViewIcon" class="hidden w-auto flex flex-wrap">';
                    $html .= '<div class="w-5 h-5 material-icons text-white cursor-pointer">account_tree</div>';
                $html .= '</div>';

            $html .= '</div>';
        $html .= '</div>';

        return $html;
    }    
    
    /**
     * Renders the table header based on the base field configuration.
     *
     * @return string
     */
    public function renderTableHeader() {
        $html  = '<table class="table-fixed settings-set w-full text-xs border border-esper-yellow" border="1" cellpadding="5" cellspacing="0">';
        $html .= '<thead><tr class="bg-esper-yellow">';
        foreach ( $this->baseFields['fieldSets'] as $field ) {
            $html .= '<th class="break-words font-normal text-black text-left">' . esc_html( $field['field_name'] ) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        return $html;
    }

    /**
     * Renders the table body by processing each repeater row.
     *
     * @return string
     */
    public function renderTableBody() {
        $html = '';
        if ( ! empty( $this->rows ) ) {
            foreach ( $this->rows as $row ) {
                $fieldsCopy = $this->baseFields;
                $fieldsCopy['beforeHTML'] = '<tr class="settings-row border-b border-esper-yellow">';
                $fieldsCopy['afterHTML']  = '</tr>';

                if ( isset( $fieldsCopy['fieldSets'] ) && is_array( $fieldsCopy['fieldSets'] ) ) {
                    foreach ( $fieldsCopy['fieldSets'] as $key => $field ) {
                        $slug = isset( $field['field_slug'] ) ? $field['field_slug'] : '';
                        $fieldsCopy['fieldSets'][ $key ]['value'] = isset( $row[ $slug ] ) ? $row[ $slug ] : '';
                        if ( empty( $row['camera_name'] ) && $slug !== 'camera_name' ) {
                            if ( ! isset( $fieldsCopy['fieldSets'][ $key ]['attributes'] ) || ! is_array( $fieldsCopy['fieldSets'][ $key ]['attributes'] ) ) {
                                $fieldsCopy['fieldSets'][ $key ]['attributes'] = array();
                            }
                            $fieldsCopy['fieldSets'][ $key ]['attributes']['style'] = 'display:none;';
                        }
                    }
                }
                ob_start();
                render_input_sets( array( $fieldsCopy ) );
                $html .= ob_get_clean();
            }
        } else {
            $emptySet = $this->baseFields;
            $emptySet['beforeHTML'] = '<tr>';
            $emptySet['afterHTML']  = '</tr>';
            ob_start();
            render_input_sets( array( $emptySet ) );
            $html .= ob_get_clean();
        }
        return $html;
    }

    /**
     * Renders the table footer (closing tags).
     *
     * @return string
     */
    public function renderTableFooter() {
        return '</tbody></table>';
    }

    /**
     * Renders the complete table.
     *
     * @return string
     */
    public function renderTable() {
        if ( ! $this->cameraSettingsId ) {
            return '<p>No camera settings found.</p>';
        }
        $html = $this->renderTableHeader();
        $html .= $this->renderTableBody();
        $html .= $this->renderTableFooter();
        return $html;
    }

    /**
     * Renders the settings.
     *
     * @return string
     */
    public function renderSettings() {
        if ( ! $this->cameraSettingsId ) {
            return '<p>No camera settings found.</p>';
        }

        $html  = '<div class="capture-template bg-black min-h-screen p-6">';

            $html  .= $this->renderHeader();

            $html .= '<div class="w-full cameraSettingsContent">';
                $html .= $this->renderCards();
            $html .= '</div>';

            //$common = $this->findCommonFields($this->rows);

            //$html .= $this->renderCommonFields( $common );

            //$html .= '<pre>';
            //$html .= print_r($common, true);
            //$html .= '</pre>';
            
        $html .= '</div>';

        return $html;
    }

    /**
 * Renders the carded view of camera settings with actual input fields.
 *
 * @return string
 */
public function renderCards() {
    if ( ! $this->cameraSettingsId ) {
        return '<p>No camera settings found.</p>';
    }

    $html = '';

    // Start the card container with a 6-column grid layout.
    $html .= '<div class="settings-set card-view flex flex-wrap w-full">';

    // Define the array of field slugs to remove.
    $fieldsToRemove = array('camera_name', 'serial_number', 'camera_model'); // Update with actual field names

    // If there are rows, render each as a separate card.
    if ( ! empty( $this->rows ) ) {

        //echo '<pre>';
        //print_r($this->rows);
        //echo '</pre>';
        foreach ( $this->rows as $row ) {
            // Copy the base configuration.
            $fieldsCopy = $this->baseFields;

            // Remove unwanted fields based on provided field slugs.
            if ( isset( $fieldsCopy['fieldSets'] ) && is_array( $fieldsCopy['fieldSets'] ) ) {
                foreach ( $fieldsCopy['fieldSets'] as $key => $field ) {
                    if ( isset( $field['field_slug'] ) && in_array( $field['field_slug'], $fieldsToRemove ) ) {
                        unset( $fieldsCopy['fieldSets'][ $key ] );
                    }
                }
            }

            // Update each remaining field's configuration for card view.
            if ( isset( $fieldsCopy['fieldSets'] ) && is_array( $fieldsCopy['fieldSets'] ) ) {
                foreach ( $fieldsCopy['fieldSets'] as $key => $field ) {
                    $slug = isset( $field['field_slug'] ) ? $field['field_slug'] : '';
                    // Set the value from the current row.
                    $fieldsCopy['fieldSets'][ $key ]['value'] = isset( $row[ $slug ] ) ? $row[ $slug ] : '';

                    $fieldsCopy['fieldSets'][ $key ]['hide_label'] = false;

                    $fieldsCopy['fieldSets'][ $key ]['label_class'] = 'text-white uppercase w-1/2';
                    $fieldsCopy['fieldSets'][ $key ]['input_class'] = 'w-1/2 bg-black border p-2 border-white border-opacity-25 text-white';

                    // Optionally hide fields if the 'camera_name' is empty and this field is not camera_name.
                    if ( empty( $row['camera_name'] ) && $slug !== 'camera_name' ) {
                        if ( ! isset( $fieldsCopy['fieldSets'][ $key ]['attributes'] ) || ! is_array( $fieldsCopy['fieldSets'][ $key ]['attributes'] ) ) {
                            $fieldsCopy['fieldSets'][ $key ]['attributes'] = array();
                        }
                        $fieldsCopy['fieldSets'][ $key ]['attributes']['style'] = 'display:none;';
                    }

                    // Change the HTML wrappers to use divs for card view.
                    $fieldsCopy['fieldSets'][ $key ]['beforeHTML'] = '<div class="flex flex-wrap items-center text-xs field mb-2">';
                    $fieldsCopy['fieldSets'][ $key ]['afterHTML']  = '</div>';
                }
            }

            // Wrap the rendered input set within a card container.
            $html .= '<div class="settings-row w-full flex flex-wrap card border border-white mb-6 border-opacity-10 p-4">';
            $html .= '<div class="w-2/3 flex flex-wrap">';
                $html .= 'Camera image name etc';
                $html .= '<div class="w-3/4">';
                    $html .= '<div class="aspect-w-16 aspect-h-9 w-full h-full border border-white border-opacity-10 flex items-center align-center">';
                        $html .= '<div class="uppercase opacity-25 w-full text-center">Camera Preview</div>';
                    $html .= '</div>';
                $html .= '</div>';
            $html .= '</div>';
                $html .= '<div class="w-1/3">';
                    ob_start();
                        render_input_sets( array( $fieldsCopy ) );
                    $html .= ob_get_clean();
                $html .= '</div>';
            $html .= '</div>'; // end single card
        }
    } else {
        // If there are no rows, render a single card with empty inputs.
        $fieldsCopy = $this->baseFields;

        // Remove unwanted fields based on provided field slugs.
        if ( isset( $fieldsCopy['fieldSets'] ) && is_array( $fieldsCopy['fieldSets'] ) ) {
            foreach ( $fieldsCopy['fieldSets'] as $key => $field ) {
                if ( isset( $field['field_slug'] ) && in_array( $field['field_slug'], $fieldsToRemove ) ) {
                    unset( $fieldsCopy['fieldSets'][ $key ] );
                }
            }
        }

        if ( isset( $fieldsCopy['fieldSets'] ) && is_array( $fieldsCopy['fieldSets'] ) ) {
            foreach ( $fieldsCopy['fieldSets'] as $key => $field ) {
                $fieldsCopy['fieldSets'][ $key ]['beforeHTML'] = '<div class="field mb-2">';
                $fieldsCopy['fieldSets'][ $key ]['afterHTML']  = '</div>';
            }
        }
        $html .= '<div class="card border p-4">';
        ob_start();
        render_input_sets( array( $fieldsCopy ) );
        $html .= ob_get_clean();
        $html .= '</div>';
    }

    $html .= '</div>'; // end card view grid

    return $html;
}

/**
 * Finds fields that have the same value across all rows.
 *
 * @param array $data Array of associative arrays representing rows.
 *
 * @return array Associative array of fields and their common value.
 */
public function findCommonFields(array $data) {
    // If the array is empty, return an empty result.
    if (empty($data)) {
        return [];
    }

    // Use the first row as the baseline for common values.
    $commonFields = $data[0];

    // Loop through each row.
    foreach ($data as $row) {
        // Loop through each field in our current common set.
        foreach ($commonFields as $field => $value) {
            // If the field does not exist in the current row, or its value differs, remove it.
            if (!array_key_exists($field, $row) || $row[$field] !== $value) {
                unset($commonFields[$field]);
            }
        }
    }

    return $commonFields;
}

/**
 * Renders the common fields input based on fields that have the same value across all rows.
 * Only fields present in the common array will be rendered.
 *
 * @return string Rendered HTML output for common fields.
 */
public function renderCommonFields( $common ) {
    if ( ! $this->cameraSettingsId ) {
        return '<p>No camera settings found.</p>';
    }
    
    // Make a copy of the base fields configuration.
    $fieldsCopy = $this->baseFields;

    // Define the array of field slugs to remove.
    $fieldsToRemove = array('camera_name', 'serial_number', 'camera_model', 'colour_temp'); // Update with actual field names

     // Remove unwanted fields based on provided field slugs.
     if ( isset( $fieldsCopy['fieldSets'] ) && is_array( $fieldsCopy['fieldSets'] ) ) {
        foreach ( $fieldsCopy['fieldSets'] as $key => $field ) {
            if ( isset( $field['field_slug'] ) && in_array( $field['field_slug'], $fieldsToRemove ) ) {
                unset( $fieldsCopy['fieldSets'][ $key ] );
            }
        }
    }
    
    // Loop through each field configuration in baseFields and update or remove.
    if ( isset( $fieldsCopy['fieldSets'] ) && is_array( $fieldsCopy['fieldSets'] ) ) {
        foreach ( $fieldsCopy['fieldSets'] as $key => $field ) {
            // If the field's slug is not present in the common array, remove it.
            if ( ! isset( $field['field_slug'] ) || ! array_key_exists( $field['field_slug'], $common ) ) {
                unset( $fieldsCopy['fieldSets'][ $key ] );
            } else {
                // Otherwise, update the field value with the common value.
                $fieldsCopy['fieldSets'][ $key ]['value'] = $common[ $field['field_slug'] ];

                $fieldsCopy['fieldSets'][ $key ]['hide_label'] = false;

                    $fieldsCopy['fieldSets'][ $key ]['label_class'] = 'text-black bg-esper-yellow uppercase w-full text-xs pl-3 pr-3 pt-2 pb-2';
                    $fieldsCopy['fieldSets'][ $key ]['input_class'] = 'w-full bg-black text-2xl border p-4 border-white border-opacity-25 text-white';

                    // Change the HTML wrappers to use divs for card view.
                    $fieldsCopy['fieldSets'][ $key ]['beforeHTML'] = '<div class="bg-black flex flex-wrap items-center field mb-2 flex-col-reversex">';
                    $fieldsCopy['fieldSets'][ $key ]['afterHTML']  = '</div>';

                    if ( isset( $fieldsCopy['fieldSets'][ $key ]['icon'])) {
                        $fieldsCopy['fieldSets'][ $key ]['show_icon']  = true;   
                    }
            }
        }
    }
    
    // Render the input fields using the render_input_sets helper.
    ob_start();
    echo '<div class="camera-quick-settings grid grid-cols-8 gap-4">';
        render_input_sets( array( $fieldsCopy ) );
    echo '</div>';
    return ob_get_clean();
}




}

add_action( 'wp_ajax_get_camera_settings_view', 'handle_get_camera_settings_view' );
add_action( 'wp_ajax_nopriv_get_camera_settings_view', 'handle_get_camera_settings_view' );

function handle_get_camera_settings_view() {
    // Sanitize input.
    $view = isset( $_POST['view'] ) ? sanitize_text_field( $_POST['view'] ) : 'table';
    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

    $post = get_post( $post_id );
    if ( ! $post ) {
        wp_send_json_error( 'Invalid post.' );
    }

    // Instantiate your CameraSettings class.
    $cameraSettings = new CameraSettings( $post );

    switch ( $view ) {
        case 'card':
echo $cameraSettings->renderCards();
        break;
        case 'node':
            echo 'node view?';
        break;
        default:
echo $cameraSettings->renderTable();
        break;
    }


    wp_die();
}

add_action( 'wp_ajax_update_acf_field', 'update_acf_field_callback' );
add_action( 'wp_ajax_nopriv_update_acf_field', 'update_acf_field_callback' );

function update_acf_field_callback() {
    // Check for required parameters.
    if ( ! isset( $_POST['fieldName'], $_POST['fieldValue'], $_POST['capturePostID'] ) ) {
        wp_send_json_error( 'Missing parameters.' );
        wp_die();
    }

    // Sanitize and retrieve the values.
    $fieldName    = sanitize_text_field( $_POST['fieldName'] );
    $fieldValue   = sanitize_text_field( $_POST['fieldValue'] ); // Adjust sanitization if needed.
    $capturePostID = intval( $_POST['capturePostID'] );
    
    // Retrieve the camera settings post ID from the capture post meta.
    $cameraSettingsPostID = get_post_meta( $capturePostID, 'capture_camera_settings', true );
    if ( empty( $cameraSettingsPostID ) ) {
        wp_send_json_error( 'No camera settings post found.' );
        wp_die();
    }

    // Get the repeater field rows.
    $rows = get_field( 'camera_settings_repeater', $cameraSettingsPostID );
    if ( ! is_array( $rows ) || empty( $rows ) ) {
        wp_send_json_error( 'No repeater field rows found.' );
        wp_die();
    }

    // Loop through each row and update the field with the provided field name.
    foreach ( $rows as $i => $row ) {
        if ( array_key_exists( $fieldName, $row ) ) {
            $rows[ $i ][ $fieldName ] = $fieldValue;
        }
    }

    // Update the repeater field with the modified rows.
    $updated = update_field( 'camera_settings_repeater', $rows, $cameraSettingsPostID );

    if ( $updated ) {
        wp_send_json_success( 'Field updated successfully.' );
    } else {
        wp_send_json_error( 'Error updating field.' );
    }

    wp_die();
}

add_action( 'wp_ajax_update_acf_field', 'update_acf_field_callback' );
add_action( 'wp_ajax_nopriv_update_acf_field', 'update_acf_field_callback' );


