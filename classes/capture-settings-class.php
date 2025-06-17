<?php

function esper_get_capture_template($post) {
    ob_start();
    ?>
    <div class="capture-template bg-black min-h-screen p-6">
        <div class="w-full">
            <!-- Capture Header -->
            <div class="bg-black p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <div class="flex items-center mb-2 group relative">
                            <h2 class="text-2xl font-bold text-white title-display" data-type="capture" data-id="<?php echo esc_attr($post->ID); ?>"><?php echo esc_html($post->post_title); ?></h2>
                            <input type="text" class="hidden absolute inset-0 bg-black text-white text-2xl font-bold px-2 py-1 title-input" value="<?php echo esc_attr($post->post_title); ?>">
                            <button class="ml-2 text-gray-400 hover:text-esper-yellow opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="material-symbols-outlined">edit</span>
                            </button>
                        </div>
                        <div class="text-gray-400 text-sm">
                            <?php 
                            $parent_session_id = get_post_meta($post->ID, 'parent_session', true);
                            $parent_session = get_post($parent_session_id);
                            ?>
                            <p>Session: <?php echo esc_html($parent_session->post_title); ?></p>
                            <p>Created: <?php echo get_the_date('F j, Y g:i a', $post); ?></p>

                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button id="triggerTake" 
                                class="bg-esper-yellow text-black px-6 py-3 rounded-lg font-semibold flex items-center"
                                data-capture-id="<?php echo esc_attr($post->ID); ?>">

                                <span class="material-symbols-outlined w-6 h-6 mr-2 text-black flex-none">camera</span>
                                
                            Trigger Take
                        </button>
                    </div>

                </div>

                <div class="flex flex-wrap mt-3">
                    <div class="flex items-center cursor-pointer mr-4 bg-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded" data-action="openScreen" data-id="<?php echo esc_attr($post->ID); ?>" data-type="capture" data-context="camera_settings"><span class="material-symbols-outlined w-6 h-6 mr-2 text-black flex-none">photo_camera</span> Advanced Camera Settings</div>
                    <div class="flex items-center cursor-pointer mr-4 bg-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded" data-action="openScreen" data-id="<?php echo esc_attr($post->ID); ?>" data-type="capture" data-context="light_settings"><span class="material-symbols-outlined w-6 h-6 mr-2 text-black flex-none">light_mode</span> Advanced Light Settings</div>
                    <div class="flex items-center cursor-pointer mr-4 bg-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded" data-action="save_as_preset" data-id="<?php echo esc_attr(get_post_meta($post->ID, 'capture_camera_settings', true)); ?>"><span class="material-symbols-outlined w-6 h-6 mr-2 text-black flex-none">save</span> Save as Preset</div>
                    <div class="flex items-center mr-4 preset-dropdown-container">
                        <select id="preset-select" class="bg-black text-white border border-esper-yellow rounded-l px-3 py-2 focus:outline-none focus:ring-2 focus:ring-esper-yellow">
                            <option value="">Select Preset</option>
                            <?php
                            $presets = get_posts(array(
                                'post_type' => 'preset',
                                'posts_per_page' => -1,
                                'orderby' => 'title',
                                'order' => 'ASC'
                            ));
                            foreach ($presets as $preset) {
                                echo '<option value="' . esc_attr($preset->ID) . '">' . esc_html($preset->post_title) . '</option>';
                            }
                            ?>
                        </select>
                        <button class="flex items-center bg-esper-yellow borderx border-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded-r" data-action="load_preset" data-camera-settings-id="<?php echo esc_attr(get_post_meta($post->ID, 'capture_camera_settings', true)); ?>">
                            <span class="material-symbols-outlined w-6 h-6 text-black flex-none">download</span> Load
                        </button>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div id="takeProgress" class="mt-4 opacity-0 transition-opacity duration-200">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm text-gray-400">Capturing Take...</span>
                        <span class="text-sm text-gray-400" id="progressPercentage">0%</span>
                    </div>
                    <div class="w-full h-2 bg-gray-600 rounded-full overflow-hidden">
                        <div id="takeProgressBar" class="h-full bg-esper-yellow transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-black p-6">
    <div class="w-full mb-4">
        <h3>Current Stage Settings</h3>
    </div>

    <?php $composer = new StageComposer( $post->ID ); ?>

    <?php if ( empty( $composer->rows ) ) : ?>
        <p class="opacity-70">No stage settings found.</p>
    <?php else : ?>
        <div class="-mx-2 flex overflow-x-auto pb-4">

            <?php foreach ( $composer->rows as $i => $row ) :
                $target    = strtoupper( $row['target']   ?? '' );
                $led       = strtoupper( $row['led']      ?? '' );
                $direction = strtoupper( $row['direction']?? '' );

                /* icon for direction  */
                $dir_icon = 'start';
                if ( $direction === 'TOP'    ) $dir_icon = 'north';
                if ( $direction === 'BOTTOM' ) $dir_icon = 'south';

                /* helper for lamp dots */
                $active   = 'bg-esper-yellow';
                $inactive = 'bg-black border border-esper-yellow';
            ?>
                <!-- Card -->
                <div class="mx-2 w-64 flex-shrink-0 bg-black text-white border border-white border-opacity-10 rounded-lgx">

                    <!-- Header -->
                    <h4 class="text-black font-semibold bg-esper-yellow uppercase w-full text-xs px-3 py-2 flex justify-between items-center">
                        <span>Stage&nbsp;<?php echo $i + 1; ?></span>
                        <span><?php echo esc_html( $led ); ?></span>
                    </h4>

                    <!-- Body: LED diagram + details -->
                    <div class="flex">

                        <!-- LED tri-dot diagram -->
                        <div class="flex flex-col items-center justify-center w-20 p-3">
                            <div class="flex justify-between w-full">
                                <span class="inline-block w-4 h-4 rounded-full <?php echo ( $led === 'PARALLEL' ) ? $active : $inactive; ?>"></span>
                                <span class="inline-block w-4 h-4 rounded-full <?php echo ( $led === 'CROSS'    ) ? $active : $inactive; ?>"></span>
                            </div>
                            <div class="flex justify-center w-full mt-4">
                                <span class="inline-block w-4 h-4 rounded-full <?php echo ( $led === 'NEUTRAL'  ) ? $active : $inactive; ?>"></span>
                            </div>
                        </div>

                        <!-- Details -->
                        <dl class="text-sm p-4 space-y-2 flex-1">
                            <?php if ( $target === 'REGION' ) : ?>
                                <div class="flex items-center">
                                    <span class="material-symbols-outlined text-esper-yellow text-base mr-2" aria-hidden="true">
                                        <?php echo $dir_icon; ?>
                                    </span>
                                    <span class="sr-only">Direction:</span>
                                    <?php echo esc_html( $row['direction'] ); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( $target === 'INDIVIDUAL' ) : ?>
                                <div class="flex items-center">
                                    <span class="material-symbols-outlined text-esper-yellow text-base mr-2" aria-hidden="true">
                                        lightbulb
                                    </span>
                                    <span class="sr-only">Light ID:</span>
                                    <?php echo esc_html( $row['light_id'] ?: '—' ); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Brightness -->
                            <div>
                                <div class="flex items-center mb-1">
                                    <span class="material-symbols-outlined text-esper-yellow text-base mr-2" aria-hidden="true">
                                        wb_incandescent
                                    </span>
                                    <span class="sr-only">Brightness:</span>
                                    <?php echo intval( $row['brightness'] ); ?> %
                                </div>
                                <div class="relative w-full bg-black bg-opacity-25 h-1 rounded">
                                    <div class="bg-esper-yellow bg-opacity-75 h-1 rounded"
                                         style="width: <?php echo min( 100, max( 0, intval( $row['brightness'] ) ) ); ?>%;"></div>
                                </div>
                            </div>

                            <!-- Flash duration -->
                            <div class="flex items-center">
                                <span class="material-symbols-outlined text-esper-yellow text-base mr-2" aria-hidden="true">
                                    flash_on
                                </span>
                                <span class="sr-only">Flash duration:</span>
                                <?php echo esc_html( $row['flash_duration'] ); ?> s
                            </div>

                            <!-- Target type -->
                            <div class="flex items-center">
                                <span class="material-symbols-outlined text-esper-yellow text-base mr-2" aria-hidden="true">
                                    my_location
                                </span>
                                <span class="sr-only">Target:</span>
                                <?php echo esc_html( $target ); ?>
                            </div>
                        </dl>
                    </div> <!-- /flex -->
                </div> <!-- /card -->
            <?php endforeach; ?>

        </div>
    <?php endif; ?>
</div>






            <?php



            ?>

            <div class="bg-black p-6">
                <div class="w-full mb-4">
                    <h3>Current Camera Settings</h3>
                </div>
                <?php
                    $cameraSettings = new CameraSettings( $post );
                    //echo $cameraSettings->renderSettings();

                    $common = $cameraSettings->findCommonFields($cameraSettings->rows);

                    echo $cameraSettings->renderCommonFields( $common );
                ?>
            </div>

           <!-- Takes Gallery -->
<div class="bg-black shadow-lg p-6">
    <div class="mb-4">
        <h3 id="toggleTakes" class="text-lg font-semibold text-white cursor-pointer inline-flex items-center">
            Takes
            <span id="toggleTakesIcon" class="material-symbols-outlined transition-transform duration-300 ml-2">
                keyboard_arrow_down
            </span>
        </h3>
    </div>
    <?php 
        $takes = get_posts(array(
            'post_type'      => 'take',
            'meta_key'       => 'parent_capture',
            'meta_value'     => $post->ID,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC'
        ));
        // Only add grid classes if takes exist.
        $galleryClasses = !empty($takes) ? "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4" : "";
    ?>
    <div id="takesGallery" class="<?php echo esc_attr($galleryClasses); ?>">
        <?php 
        if ( empty( $takes ) ) {
            echo '<p class="text-white">No takes found, please trigger some.</p>';
        } else {
            foreach ( $takes as $take ) {
                $thumbnail = get_the_post_thumbnail_url( $take->ID, 'medium' );
                if ( ! $thumbnail ) {
                    $thumbnail = 'https://placehold.co/600x400';
                }
                ?>
                <div class="take-card bg-black overflow-hidden cursor-pointer border border-white border-opacity-10 hover:bg-white hover:bg-opacity-10 transition" 
                     data-take-id="<?php echo esc_attr( $take->ID ); ?>">
                    <img src="<?php echo esc_url( $thumbnail ); ?>" 
                         alt="<?php echo esc_attr( $take->post_title ); ?>"
                         class="w-full aspect-w-16 aspect-h-9 object-cover">
                    <div class="p-4">
                        <h4 class="text-white font-semibold"><?php echo esc_html( $take->post_title ); ?></h4>
                        <p class="text-gray-400 text-sm">
                            <?php echo get_the_date( 'F j, Y g:i a', $take ); ?>
                        </p>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>
</div>

<style>
/* Class to rotate the icon */
.rotate-180 {
    transform: rotate(180deg);
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#toggleTakes').on('click', function() {
        $('#takesGallery').slideToggle(300);
        $('#toggleTakesIcon').toggleClass('rotate-180');
    });
});
</script>


        </div>
    </div>
    <?php
    return ob_get_clean();
}