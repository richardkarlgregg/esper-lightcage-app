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
     * Define the four "columns" in the table.
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

    /* =========================================================
 *  ONE STAGE  ➜  card inside horizontal timeline
 * =======================================================*/
private function render_stage_card( array $row, int $i ): void {

    $idx   = $i;
    $title = 'S' . str_pad( $i + 1, 2, '0', STR_PAD_LEFT );   // "S01", "S02" …

    echo '<div class="stage-card shrink-0 border border-esper-yellow rounded-sm min-w-[220px]">';

        /* ---------- 20 px header ---------- */
        echo '<div class="h-7 bg-esper-yellow flex items-center justify-between px-1 text-[10px] font-bold text-black">';

            echo $title;

            echo '<div class="icon-bar flex items-center gap-1">';
                echo '<button class="hidden flex items-center move-up"      title="Up"><span class="material-symbols-outlined text-[14px]">arrow_upward</span></button>';
                echo '<button class="hidden flex items-center move-down"    title="Down"><span class="material-symbols-outlined text-[14px]">arrow_downward</span></button>';
                echo '<button class="flex items-center add-below"    title="Add"><span class="material-symbols-outlined text-[14px]">add_row_below</span></button>';
                echo '<button class="flex items-center remove-stage" title="Del"><span class="material-symbols-outlined text-[14px]">delete</span></button>';
            echo '</div>';

        echo '</div>';

        /* ---------- two-row input grid ---------- */
        echo '<div class="p-2 grid grid-rows-2 gap-y-1 text-[11px] leading-none">';

            /* ROW 1 ─ LED + Direction */
            echo '<div class="flex gap-1 items-center">';

                /* LED radios (C / N / P) */
                $name = 'led[' . $idx . ']';
                $val  = $row['led'] ?? 'CROSS';
                foreach ( [ 'CROSS' => 'C', 'NEUTRAL' => 'N', 'PARALLEL' => 'P' ] as $v => $lbl ) {
                    echo '<label class="flex items-center gap-[2px]">';
                    echo '<input type="radio" value="'. $v .'" name="'. $name .'" '. checked( $val, $v, false ) .'>';
                    echo $lbl;
                    echo '</label>';
                }

                /* Direction select (tiny) */
                echo '<select name="direction['. $idx .']" class="ml-auto bg-black border border-white/20 px-1 py-0.5">';
                foreach ( $this->baseFields[1]['options'] as $v => $lbl ) {
                    echo '<option value="'. $v .'" '. selected( $row['direction'] ?? 'GI', $v, false ) .'>'. $lbl .'</option>';
                }
                echo '</select>';

            echo '</div>';

            /* ROW 2 ─ Brightness + Flash */
            echo '<div class="flex gap-1 items-center">';

                /* Brightness range */
                $b = $row['brightness'] ?? 70;
                echo '<input type="range" min="0" max="100" step="1" value="'. $b .'" name="brightness['. $idx .']" class="flex-1 h-1">';
                echo '<span class="text-[10px] w-6 text-right">'. $b .'%</span>';

                /* Flash duration */
                $f = $row['flash_duration'] ?? 5.0;
                echo '<input type="number" step="0.1" min="0" value="'. $f .'" name="flash_duration['. $idx .']" class="w-12 bg-black border border-white/20 px-1 py-0.5">';
                echo '<span class="text-[10px]">s</span>';

            echo '</div>';

        echo '</div>';  // grid

    echo '</div>';      // card
}


    /* =========================================================
 *  ONE STAGE  ➜  pure-<div> markup (collapsible)
 * =======================================================*/
private function render_stage_div( array $row, int $i ): void {

    $idx   = $i;                                          // numeric index
    $title = 'Stage&nbsp;' . str_pad( $i + 1, 2, '0', STR_PAD_LEFT );

    echo '<div class="stage-group border border-esper-yellow" data-index="'. $idx .'">';

        /* ---------- collapsed summary bar ---------- */
        echo '<div class="stage-summary bg-esper-yellow text-black cursor-pointer select-none p-2 flex items-center justify-between">';

            echo '<strong>'. $title .'</strong>';

            echo '<div class="icon-bar flex gap-2">';
                echo '<button class="move-up"       title="Move Up"><span  class="material-symbols-outlined">arrow_upward</span></button>';
                echo '<button class="move-down"     title="Move Down"><span class="material-symbols-outlined">arrow_downward</span></button>';
                echo '<button class="add-below"     title="Add Below"><span class="material-symbols-outlined">add_row_below</span></button>';
                echo '<button class="remove-stage"  title="Remove Stage"><span class="material-symbols-outlined">delete</span></button>';
            echo '</div>';

        echo '</div>';

        /* ---------- detail panel (hidden by default) ---------- */
        echo '<div class="stage-details hidden bg-black/50 px-4 py-3 space-y-3">';

            foreach ( $this->baseFields as $f ) {

                $slug = $f['field_slug'];
                $name = esc_attr( $slug ) . '[' . $idx . ']';
                $val  = $row[ $slug ] ?? '';

                echo '<div class="field flex flex-col gap-1">';

                    /* label */
                    echo '<label class="text-xs text-gray-300">'. esc_html( $f['field_name'] ) .'</label>';

                    /* INPUT TYPES (same logic you already had) */
                    if ( $f['type'] === 'radio' ) {

                        echo '<div class="flex gap-4 text-sm">';
                        foreach ( $f['options'] as $v => $label ) {
                            echo '<label class="flex items-center gap-1">';
                            echo '<input type="radio" name="'. $name .'" value="'. esc_attr( $v ) .'"'
                                 . checked( $val, $v, false ) .'>';
                            echo esc_html( $label );
                            echo '</label>';
                        }
                        echo '</div>';

                    } elseif ( $f['type'] === 'select' ) {

                        echo '<select class="bg-black border p-2 border-white/25 text-white" name="'. $name .'">';
                        foreach ( $f['options'] as $v => $label ) {
                            echo '<option value="'. esc_attr( $v ) .'"'
                                 . selected( $val, $v, false ) .'>'. esc_html( $label ) .'</option>';
                        }
                        echo '</select>';

                    } elseif ( $f['type'] === 'range' ) {

                        $v = $val === '' ? 70 : $val;
                        echo '<input type="range" min="'. $f['attributes']['min'] .'" max="'. $f['attributes']['max'] .'"'
                             .' step="'. $f['attributes']['step'] .'" value="'. $v .'" name="'. $name .'"'
                             .' class="w-full">';
                        echo '<span class="brightness-label text-right block text-xs">'. $v .'%</span>';

                    } elseif ( $f['type'] === 'number' ) {

                        $v = $val === '' ? 5.0 : $val;
                        echo '<input type="number" step="'. $f['attributes']['step'] .'" min="'. $f['attributes']['min'] .'"'
                             .' value="'. $v .'" name="'. $name .'"'
                             .' class="bg-black border p-2 border-white/25 text-white w-24"> <span>s</span>';
                    }

                echo '</div>'; // .field
            }

        echo '</div>'; // .stage-details

    echo '</div>';     // .stage-group
}


/* =========================================================
 *  RENDER A SINGLE STAGE AS A COLLAPSIBLE <tbody>
 * =======================================================*/
private function render_stage( array $row, int $i ): void {

    $title = 'Stage&nbsp;' . str_pad( $i + 1, 2, '0', STR_PAD_LEFT );

    echo '<tbody class="stage-group border border-esper-yellow" data-index="'. $i .'">';

        /* ---------- Collapsed summary row ---------- */
        echo '<tr class="stage-summary bg-esper-yellow text-black cursor-pointer select-none">';
            echo '<td colspan="100" class="px-3 py-1">';
                echo '<div class="flex items-center justify-between">';

                    /* stage title */
                    echo '<strong>'. $title .'</strong>';

                    /* all icons live here */
                    echo '<div class="icon-bar flex gap-2">';
                        echo '<button class="move-up"       title="Move Up"><span  class="material-symbols-outlined">arrow_upward</span></button>';
                        echo '<button class="move-down"     title="Move Down"><span class="material-symbols-outlined">arrow_downward</span></button>';
                        echo '<button class="add-below"     title="Add Below"><span class="material-symbols-outlined">add_row_below</span></button>';
                        echo '<button class="remove-stage"  title="Remove Stage"><span class="material-symbols-outlined">delete</span></button>';
                    echo '</div>';

                echo '</div>';
            echo '</td>';
        echo '</tr>';

        /* ---------- Detail row (hidden by default) ---------- */
        echo '<tr class="stage-details hidden">';
            echo '<td colspan="100" class="p-0">';

                /* mini-table that groups the light settings */
                echo '<table class="w-full text-xs light-settings-table">';

                    /* headings (LED / Direction / Brightness / Flash Duration) */
                    echo '<thead><tr>';
                    foreach ( $this->baseFields as $f ) {
                        echo '<th class="font-normal text-left px-2 py-1">'. esc_html( $f['field_name'] ) .'</th>';
                    }
                    echo '</tr></thead><tbody><tr>';

                    /* 4 input controls – same code you already had, just inside cells */
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

                    echo '</tr></tbody>';
                echo '</table>';

            echo '</td>';
        echo '</tr>';

    echo '</tbody>';
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
          <div class="flex gap-2">
            <button data-tooltip="Move Up" class="move-up bg-esper-yellow text-black px-2 py-1 rounded flex items-center text-sm" title="Move Up"><span class="material-symbols-outlined">arrow_upward</span></button>
            <button data-tooltip="Move Down" class="move-down bg-esper-yellow text-black px-2 py-1 rounded flex items-center text-sm" title="Move Down"><span class="material-symbols-outlined">arrow_downward</span></button>
            <button data-tooltip="Remove Stage" class="remove-stage bg-esper-yellow text-black px-2 py-1 rounded flex items-center text-sm" title="Remove Stage"><span class="material-symbols-outlined">delete</span></button>
          </div>
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
/* ========================== MAIN RENDER ========================== */
public function render() { ?>
    <div id="stage-composer-wrapper" class="absolute bottom-0 left-0 w-full z-50 bg-black/60">
      <div class="flex justify-end items-center gap-2 px-4 py-2">
        <div class="flex items-center gap-2">
          <button id="play-timeline" class="bg-esper-yellow text-black px-3 py-1 rounded flex items-center gap-1 text-sm">
            <span class="material-symbols-outlined text-[18px]">play_arrow</span>
            Play
          </button>
          <button id="pause-timeline" class="bg-esper-yellow text-black px-3 py-1 rounded flex items-center gap-1 text-sm hidden">
            <span class="material-symbols-outlined text-[18px]">pause</span>
            Pause
          </button>
          <button id="stop-timeline" class="bg-esper-yellow text-black px-3 py-1 rounded flex items-center gap-1 text-sm">
            <span class="material-symbols-outlined text-[18px]">stop</span>
            Stop
          </button>
        </div>
        <button id="add-stage" class="bg-esper-yellow text-black px-3 py-1 rounded flex items-center gap-1 text-sm">
          <span class="material-symbols-outlined text-[18px]">add</span>
          Add Stage
        </button>
      </div>

      <div id="stage-timeline" class="flex gap-2 px-4 py-0 overflow-x-auto whitespace-nowrap">
        <?php foreach ( $this->rows as $i => $row ) $this->render_stage_card( $row, $i ); ?>
      </div>
  
      <div class="hidden flex justify-between px-4 py-3">
        <!-- "Add Stage" / "Save Stages" buttons – unchanged -->
      </div>
  
    </div><?php
  }
  
  
  


    
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


