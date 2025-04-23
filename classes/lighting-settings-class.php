<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class StageComposer {

    /** @var int */
    protected $capture_id;

    /** @var WP_Post */
    protected $capture;

    /** the matched light_settings post ID */
    protected $light_settings_id;

    /** meta key we store our JSON under on the light_settings post */
    protected $meta_key = 'stages_json';

    /** array of saved rows */
    protected $rows;

    /** … baseFields definition stays the same … */

    /**
     * Now takes a capture post ID instead of a full WP_Post object.
     *
     * @param int $capture_id
     */
    public function __construct( $capture_id ) {
        $this->capture_id       = intval( $capture_id );
        $this->capture          = get_post( $this->capture_id );
        $this->light_settings_id= $this->get_light_settings_id();
        $this->rows             = $this->get_rows();
        $this->baseFields       = $this->get_base_fields();

        //add_action( 'wp_enqueue_scripts',           [ $this, 'enqueue_assets' ] );
        //add_action( 'wp_ajax_stage_composer_save',     [ $this, 'ajax_save' ] );
        //add_action( 'wp_ajax_nopriv_stage_composer_save', [ $this, 'ajax_save' ] );
    }

    protected function get_light_settings_id() {
        if ( ! $this->capture_id ) {
            return false;
        }
        $posts = get_posts([
            'post_type'      => 'light_settings',
            'meta_key'       => 'parent_capture',
            'meta_value'     => $this->capture_id,
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ]);
        return ! empty( $posts ) ? (int) $posts[0] : false;
    }

    protected function get_rows() {
        if ( ! $this->light_settings_id ) {
            return [];
        }
        $data = get_post_meta( $this->light_settings_id, $this->meta_key, true );
        return is_array( $data ) ? $data : [];
    }

    /**
     * Define the four “columns” in the table.
     *
     * @return array
     */
    protected function get_base_fields() {
        return [
            [
                'field_name' => 'LED',
                'field_slug' => 'led',
                'type'       => 'radio',
                'options'    => [
                    'CROSS'    => 'Cross',
                    'NEUTRAL'  => 'Neutral',
                    'PARALLEL' => 'Parallel',
                ],
            ],
            [
                'field_name' => 'Direction',
                'field_slug' => 'direction',
                'type'       => 'select',
                'options'    => [
                    'GI'     => 'GI',
                    'LEFT'   => 'Left',
                    'RIGHT'  => 'Right',
                    'TOP'    => 'Top',
                    'BOTTOM' => 'Bottom',
                    'FRONT'  => 'Front',
                    'BACK'   => 'Back',
                ],
            ],
            [
                'field_name'  => 'Brightness',
                'field_slug'  => 'brightness',
                'type'        => 'range',
                'attributes'  => [
                    'min'  => 0,
                    'max'  => 100,
                    'step' => 1,
                ],
            ],
            [
                'field_name'  => 'Flash Duration',
                'field_slug'  => 'flash_duration',
                'type'        => 'number',
                'attributes'  => [
                    'step' => 0.1,
                    'min'  => 0,
                ],
            ],
        ];
    }

    /**
     * Enqueue & localize our JS.
     */
    public function enqueue_assets() {
        wp_register_script(
            'stage-composer-js',
            get_stylesheet_directory_uri() . '/js/stage-composer.js',
            [ 'jquery' ],
            '1.0',
            true
        );

        wp_localize_script( 'stage-composer-js', 'SC', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'stage_composer' ),
            'post_id'  => $this->post->ID,
            'stages'   => $this->rows,
        ] );

        wp_enqueue_script( 'stage-composer-js' );
    }

    /**
     * Save Stages
     */
    public function save_stages() {

    }

    /**
     * Renders the entire composer: header + table + template row + buttons.
     */
    public function render() {
        ?>
        <div id="stage-composer-wrapper">
            <div class="composer-header" style="margin-bottom:1em;">
                <strong>Stage Composer</strong>
                <button id="save-stages" class="button button-primary" style="float:right;">Save Stages</button>
            </div>
            <table id="stage-composer" class="widefat">
                <thead>
                    <tr>
                        <th>Stage</th>
                        <th>Edit</th>
                        <?php foreach ( $this->baseFields as $f ) : ?>
                            <th><?php echo esc_html( $f['field_name'] ); ?></th>
                        <?php endforeach; ?>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
            <button id="add-stage" class="button" style="margin-top:.5em;">Add Stage</button>
        </div>

        <!-- hidden template row -->
        <table style="display:none;">
            <tbody>
                <tr id="sc-row-tpl">
                    <td class="stage-number"></td>
                    <td>
                        <button class="move-up" title="Up">↑</button>
                        <button class="move-down" title="Down">↓</button>
                        <button class="remove-stage" title="Remove">✕</button>
                    </td>
                    <?php foreach ( $this->baseFields as $f ) : ?>
                        <td>
                        <?php
                        switch( $f['type'] ) {
                            case 'radio':
                                foreach( $f['options'] as $val => $label ) {
                                    printf(
                                        '<label><input type="radio" name="%1$s[]" value="%2$s"> %3$s</label> ',
                                        esc_attr( $f['field_slug'] ),
                                        esc_attr( $val ),
                                        esc_html( $label )
                                    );
                                }
                                break;
                            case 'select':
                                echo '<select name="'. esc_attr( $f['field_slug'] ) .'[]">';
                                foreach( $f['options'] as $val => $label ) {
                                    printf(
                                        '<option value="%1$s">%2$s</option>',
                                        esc_attr( $val ),
                                        esc_html( $label )
                                    );
                                }
                                echo '</select>';
                                break;
                            case 'range':
                                printf(
                                    '<input type="range" name="%1$s[]" min="%2$d" max="%3$d" step="%4$d" value="70">
                                     <span class="brightness-label">70%%</span>',
                                    esc_attr( $f['field_slug'] ),
                                    intval( $f['attributes']['min'] ),
                                    intval( $f['attributes']['max'] ),
                                    intval( $f['attributes']['step'] )
                                );
                                break;
                            case 'number':
                                printf(
                                    '<input type="number" name="%1$s[]" step="%2$s" min="%3$s" value="5.0"> s',
                                    esc_attr( $f['field_slug'] ),
                                    esc_attr( $f['attributes']['step'] ),
                                    esc_attr( $f['attributes']['min'] )
                                );
                                break;
                        }
                        ?>
                        </td>
                    <?php endforeach; ?>
                    <td><button class="add-below" title="Add Below">＋</button></td>
                </tr>
            </tbody>
        </table>
        <?php
    }
}

// Register AJAX callbacks for both logged-in and anonymous users
add_action( 'wp_ajax_stage_composer_save',     'stage_composer_ajax_save' );
add_action( 'wp_ajax_nopriv_stage_composer_save', 'stage_composer_ajax_save' );

/**
 * AJAX handler proxy: instantiates StageComposer and calls ajax_save().
 */
function stage_composer_ajax_save() {

   check_ajax_referer( 'esper_ajax_nonce', 'nonce' );
   
    // Pull the capture/post ID from the request
    $capture_id = isset( $_POST['capture_id'] ) ? intval( $_POST['capture_id'] ) : 0;
    if ( ! $capture_id ) {
        wp_send_json_error( 'Missing post_id', 400 );
    }

    // Instantiate with the capture ID
    $composer = new StageComposer( $capture_id );
    // Delegate to the class method
    wp_send_json_success();
    // ajax_save() will handle permission checks and send JSON response
}
