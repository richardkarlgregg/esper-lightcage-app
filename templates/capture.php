            <div class="flex space-x-2">
                <button data-action="save_as_preset" 
                        data-camera-settings-id="<?php echo esc_attr(get_post_meta($post->ID, 'capture_camera_settings', true)); ?>"
                        class="bg-esper-yellow hover:bg-esper-yellow/80 text-black px-2 py-1 rounded flex items-center text-sm">
                    <span class="material-symbols-outlined">add</span>
                </button>
                <?php
                $presets = get_posts(array(
                    'post_type' => 'preset',
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC'
                ));
                if (!empty($presets)): ?>
                    <div class="flex space-x-1">
                        <select class="bg-gray-700 text-white px-2 py-1 rounded text-sm">
                            <option value="">Select Preset</option>
                            <?php foreach ($presets as $preset): ?>
                                <option value="<?php echo esc_attr($preset->ID); ?>"><?php echo esc_html($preset->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button data-action="load_preset" 
                                data-camera-settings-id="<?php echo esc_attr(get_post_meta($post->ID, 'capture_camera_settings', true)); ?>"
                                class="bg-esper-yellow hover:bg-esper-yellow/80 text-black px-2 py-1 rounded flex items-center text-sm">
                            <span class="material-symbols-outlined">download</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div> 