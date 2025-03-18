<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class CameraSettings {

    protected $post;
    protected $cameraSettingsId;
    protected $rows;
    protected $baseFields;

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
                    'placeholder'=> 'Enter camera name',
                    'beforeHTML' => '<td class="break-words border-r border-esper-yellow">',
                    'afterHTML'  => '</td>',
                ),
                array(
                    'field_name' => 'Serial Number',
                    'field_slug' => 'serial_number',
                    'type'       => 'text',
                    'display'    => true,
                    'value'      => '',
                    'hide_label' => true,
                    'placeholder'=> 'Enter serial number',
                    'beforeHTML' => '<td class="break-words border-r border-esper-yellow">',
                    'afterHTML'  => '</td>',
                ),
                array(
                    'field_name' => 'Camera Model',
                    'field_slug' => 'camera_model',
                    'type'       => 'text',
                    'display'    => true,
                    'value'      => '',
                    'hide_label' => true,
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
                    'options'    => get_white_balance_options(),
                    'beforeHTML' => '<td class="break-words border-r border-esper-yellow">',
                    'afterHTML'  => '</td>',
                ),
                array(
                    'field_name' => 'Colour Temp',
                    'field_slug' => 'colour_temp',
                    'type'       => 'range',
                    'hide_label' => true,
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

        $html .= '<div class="flex items-center space-x-4">';
            $html .= '<label for="syncSettings">';
                $html .= '<input type="checkbox" id="syncSettings" name="syncSettings" value="1"> Sync Settings';
            $html .= '</label>';

            $html .= 'First screen needs to be option to choose sync settings or individual controls';

            $html .= 'For sync control need ability to add controls to capture screen for quick control?';

            // Add view switcher icons using Google Material Icons.
            $html .= '<div class="view-switcher flex items-center space-x-2">';
                $html .= '<div id="tableViewIcon" class="w-auto flex flex-wrap">';
                    $html .= '<div class="w-5 h-5 material-icons text-white cursor-pointer">table_chart</div>';
                $html .= '</div>';
                $html .= '<div id="cardViewIcon" class="w-auto flex flex-wrap">';
                    $html .= '<div class="w-5 h-5 material-icons text-white cursor-pointer">view_module</div>';
                $html .= '</div>';
                $html .= '<div id="nodeViewIcon" class="w-auto flex flex-wrap">';
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
        return '</tbody></table></div></div>';
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
                $html .= $this->renderTable();
            $html .= '</div>';
            
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

    // Start the card container with a 4-column grid layout.
    $html .= '<div class="settings-set card-view grid grid-cols-4 gap-4">';

    // If there are rows, render each as a separate card.
    if ( ! empty( $this->rows ) ) {
        foreach ( $this->rows as $row ) {
            // Copy the base configuration.
            $fieldsCopy = $this->baseFields;

            // Update each field's configuration for card view.
            if ( isset( $fieldsCopy['fieldSets'] ) && is_array( $fieldsCopy['fieldSets'] ) ) {
                foreach ( $fieldsCopy['fieldSets'] as $key => $field ) {
                    $slug = isset( $field['field_slug'] ) ? $field['field_slug'] : '';
                    // Set the value from the current row.
                    $fieldsCopy['fieldSets'][ $key ]['value'] = isset( $row[ $slug ] ) ? $row[ $slug ] : '';

                    // Optionally hide fields if the 'camera_name' is empty and this field is not camera_name.
                    if ( empty( $row['camera_name'] ) && $slug !== 'camera_name' ) {
                        if ( ! isset( $fieldsCopy['fieldSets'][ $key ]['attributes'] ) || ! is_array( $fieldsCopy['fieldSets'][ $key ]['attributes'] ) ) {
                            $fieldsCopy['fieldSets'][ $key ]['attributes'] = array();
                        }
                        $fieldsCopy['fieldSets'][ $key ]['attributes']['style'] = 'display:none;';
                    }

                    // Change the HTML wrappers to use divs for card view.
                    $fieldsCopy['fieldSets'][ $key ]['beforeHTML'] = '<div class="field mb-2">';
                    $fieldsCopy['fieldSets'][ $key ]['afterHTML']  = '</div>';
                }
            }

            // Wrap the rendered input set within a card container.
            $html .= '<div class="settings-row card border p-4">';
            ob_start();
            render_input_sets( array( $fieldsCopy ) );
            $html .= ob_get_clean();
            $html .= '</div>'; // end single card
        }
    } else {
        // If there are no rows, render a single card with empty inputs.
        $fieldsCopy = $this->baseFields;
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

