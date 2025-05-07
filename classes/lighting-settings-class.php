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
    protected $meta_key = 'composer_stages';

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

    public function get_light_settings_id() {
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
                    'GI'     => 'Global-Illumination',
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
 * Output one <tr>.  
 * $row   – associative array [slug ⇒ value] (empty for template)  
 * $i     – index (null for template)  
 * $tmpl  – true = hidden template row
 */
private function render_row( array $row, ?int $i, bool $tmpl = false ) {

    $rowKey = $tmpl ? '__INDEX__' : $i;   // <─── unique key / placeholder
    echo '<tr' . ( $tmpl ? ' id="sc-row-tpl"' : '' ) . ' class="border-b border-esper-yellow">';

        /* stage # or blank (template) */
        echo '<td class="stage-number break-wordsx border-r border-esper-yellow">';
            echo $tmpl ? '' : 'Stage&nbsp;' . str_pad( $i + 1, 2, '0', STR_PAD_LEFT );
        echo '</td>';

        /* edit buttons */
        echo '
        <td class="break-wordsx border-r border-esper-yellow">
          <button class="move-up"   title="Up">↑</button>
          <button class="move-down" title="Down">↓</button>
          <button class="remove-stage" title="Remove">✕</button>
        </td>';

        foreach ( $this->baseFields as $f ) {

            $slug  = $f['field_slug'];
            $name  = esc_attr( $slug ) . '[' . $rowKey . ']';   // ← here
            $val   = $row[ $slug ] ?? '';
    
            echo '<td class="break-wordsx border-r border-esper-yellow">';
    
            /* RADIO */
            if ( $f['type'] === 'radio' ) {
                echo '<div class="w-48">';
                foreach ( $f['options'] as $v => $label ) {
                    echo '<label>';
                    echo '<input type="radio" name="'. $name .'" value="'. esc_attr( $v ) .'"'
                       . ( $tmpl ? '' : checked( $val, $v, false ) ) .'> '. esc_html( $label );
                    echo '</label> ';
                }
                echo '</div>';
            }
    
            /* SELECT */
            elseif ( $f['type'] === 'select' ) {
                echo '<select class="w-full bg-black border p-2 border-white border-opacity-25 text-white"'
                   . ' name="'. $name .'">';
                   foreach ( $f['options'] as $v => $label ) {
                       echo '<option value="'. esc_attr( $v ) .'"'
                          . ( $tmpl ? '' : selected( $val, $v, false ) ) .'>'
                          . esc_html( $label ) .'</option>';
                   }
                echo '</select>';
            }
    
            /* RANGE */
            elseif ( $f['type'] === 'range' ) {
                $v = $tmpl ? 70 : ( $val !== '' ? $val : 70 );
                echo '<input type="range" name="'. $name .'" min="'. $f['attributes']['min'] .'"'
                   . ' max="'. $f['attributes']['max'] .'" step="'. $f['attributes']['step'] .'" value="'. $v .'">';
                echo '<span class="brightness-label">'. $v .'%</span>';
            }
    
            /* NUMBER */
            elseif ( $f['type'] === 'number' ) {
                $v = $tmpl ? 5.0 : ( $val !== '' ? $val : 5.0 );
                echo '<input class="w-1/2 bg-black border p-2 border-white border-opacity-25 text-white"'
                   . ' type="number" name="'. $name .'" step="'. $f['attributes']['step'] .'"'
                   . ' min="'. $f['attributes']['min'] .'" value="'. $v .'"> s';
            }
    
            echo '</td>';
        }

        echo '<td><button data-tooltip="Add Below" class="bg-esper-yellow text-black px-2 py-1 rounded flex items-center text-sm add-below" title="Add Below"><span class="material-symbols-outlined">add_row_below</span></button></td></tr>';

    echo '</tr>';
}

/* ====================== MAIN RENDER ====================== */
public function render() { ?>
<div id="stage-composer-wrapper">
  <div class="composer-header mb-4"><strong>Stage Composer</strong></div>

  <table id="stage-composer" class="table-fixedx settings-set w-full text-xs border border-esper-yellow" border="1" cellpadding="5" cellspacing="0">
    <thead>
      <tr class="bg-esper-yellow">
        <th class="sync-col w-11 font-normal text-center text-black text-left">Stage</th><th class="sync-col w-11 font-normal text-center text-black text-left">Edit</th>
        <?php foreach ( $this->baseFields as $f ) : ?>
          <th class="sync-col w-11 font-normal text-center text-black text-left"><?php echo esc_html( $f['field_name'] ); ?></th>
        <?php endforeach; ?>
        <th class="sync-col w-11 font-normal text-center text-black text-left"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ( $this->rows as $i => $row ) $this->render_row( $row, $i, false ); ?>
    </tbody>
  </table>

  <div class="w-full flex justify-between mt-3">
    <button id="add-stage"  class="flex items-center cursor-pointer bg-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded">Add Stage</button>
    <button id="save-stages" class="flex items-center cursor-pointer bg-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded button-primary">Save Stages</button>
  </div>
 
</div>

<!-- hidden template -->
<table style="display:none"><tbody>
  <?php $this->render_row( [], null, true ); ?>
</tbody></table>
<?php }


    
}

// Register AJAX callbacks for both logged-in and anonymous users
add_action( 'wp_ajax_stage_composer_save',     'stage_composer_ajax_save' );
add_action( 'wp_ajax_nopriv_stage_composer_save', 'stage_composer_ajax_save' );


function stage_composer_ajax_save() {
    $debug = [];

    

    // 1) Nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'esper_ajax_nonce' ) ) {
        wp_send_json_error( [ 'message' => 'Invalid nonce', 'debug' => $debug ], 403 );
    }

    

    // 2) Capture ID
    $capture_id = isset( $_POST['capture_id'] ) ? intval( $_POST['capture_id'] ) : 0;
    if ( ! $capture_id ) {
        wp_send_json_error( [ 'message' => 'Missing capture_id', 'debug' => $debug ], 400 );
    }

    // 3) Decode payload
    if ( empty( $_POST['data'] ) ) {
        wp_send_json_error( [ 'message' => 'No data provided', 'debug' => $debug ], 400 );
    }
    $rows = json_decode( wp_unslash( $_POST['data'] ), true );
    if ( ! is_array( $rows ) ) {
        wp_send_json_error( [ 'message' => 'Invalid data format', 'debug' => $debug ], 400 );
    }

    

    // 4) Find the light_settings post
    $composer = new StageComposer( $capture_id );
    $light_id = $composer->get_light_settings_id();
    $debug['$light_id'] = $light_id;

   
    if ( ! $light_id ) {
        wp_send_json_error( [ 'message' => 'No light_settings found', 'debug' => $debug ], 404 );
    }

    $debug['rpws'] = $rows;
    //wp_send_json_success( [ 'message' => 'Saved to composer_stages', 'debug' => $debug ] );

    // 5) Save to meta_key 'composer_stages'
    update_field('composer_stages', $rows, $light_id );

    // 6) Return success
    wp_send_json_success( [ 'message' => 'Saved to composer_stages', 'debug' => $debug ] );
}

add_action( 'wp_ajax_modeling_light_save',        'ml_save' );
add_action( 'wp_ajax_nopriv_modeling_light_save', 'ml_save' );
function ml_save() {
    check_ajax_referer( 'esper_ajax_nonce', 'nonce' );

    $cap = intval( $_POST['capture_id'] ?? 0 );
    if ( ! $cap ) wp_send_json_error( 'No capture_id', 400 );

    // 4) Find the light_settings post
    $composer = new StageComposer( $cap );
    $ls_id= $composer->get_light_settings_id();

    if ( ! $ls_id ) wp_send_json_error( 'No light_settings', 404 );

    update_post_meta( $ls_id, 'light_brightness_parallel', $_POST['parallel']  );
    update_post_meta( $ls_id, 'light_brightness_cross',    $_POST['cross']     );
    update_post_meta( $ls_id, 'light_brightness_neutral',  $_POST['neutral']   );

    wp_send_json_success();
}


