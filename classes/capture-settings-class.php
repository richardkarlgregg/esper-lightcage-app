<?php
/**
 * Render a Capture page (stage settings, camera settings, optional pose image, takes gallery).
 *
 * @param WP_Post $post  Capture post object.
 * @return string        Rendered HTML.
 */
function esper_get_capture_template( $post ) {

	// ─────────────────────────────────────────────────────
	// Pose image (replace the meta-key if yours differs)
	// ─────────────────────────────────────────────────────
	$pose_image = get_post_meta( $post->ID, 'pose_image', true );

    $pose_image = 'https://media.sketchfab.com/models/bf7eb5f7f2504f7bbdda4ab00a71e23c/thumbnails/669ba8be92e74e1e9f2369ff854d1a3f/bd980852a837479bb2bf53bb431efcf2.jpeg';

    /* Generate unique IDs so multiple Capture blocks never clash */
	$thumb_id = 'poseThumb-' . $post->ID;
	$modal_id = 'poseModal-' . $post->ID;
	$close_id = 'closePose-' . $post->ID;

    $pose_image = null;

	ob_start();
	?>
	<div class="capture-template bg-black min-h-screen p-6">
		<div class="w-full">

			<!-- ╔════════════════════════════════════════════════╗
			     ║                CAPTURE HEADER                ║
			     ╚════════════════════════════════════════════════╝ -->
			<div class="bg-black p-6">
				<div class="flex items-start justify-between mb-4">
					<div class="flex-1">
						<!-- Title + inline editor -->
						<div class="flex items-center mb-2 group relative">
							<h2 class="text-2xl font-bold text-white title-display"
								data-type="capture"
								data-id="<?php echo esc_attr( $post->ID ); ?>">
								<?php echo esc_html( $post->post_title ); ?>
							</h2>

							<input type="text"
								   class="hidden absolute inset-0 bg-black text-white text-2xl font-bold px-2 py-1 title-input"
								   value="<?php echo esc_attr( $post->post_title ); ?>">

							<button class="ml-2 text-gray-400 hover:text-esper-yellow opacity-0 group-hover:opacity-100 transition-opacity">
								<span class="material-symbols-outlined">edit</span>
							</button>
						</div>

						<!-- Meta -->
						<div class="text-gray-400 text-sm">
							<?php
							$parent_session_id = get_post_meta( $post->ID, 'parent_session', true );
							$parent_session    = get_post( $parent_session_id );
							?>
							<p>Session: <?php echo esc_html( $parent_session->post_title ); ?></p>
							<p>Created: <?php echo get_the_date( 'F j, Y g:i a', $post ); ?></p>
						</div>
					</div>

					<!-- Trigger Take -->
					<div class="flex space-x-2">
						<button id="triggerTake"
								class="bg-esper-yellow text-black px-6 py-3 rounded-lg font-semibold flex items-center"
								data-capture-id="<?php echo esc_attr( $post->ID ); ?>">
							<span class="material-symbols-outlined w-6 h-6 mr-2 text-black flex-none">camera</span>
							Trigger Take
						</button>
					</div>
				</div><!-- /.top bar -->

				<!-- Action buttons -->
				<div class="flex flex-wrap mt-3">

					<!-- Advanced Camera -->
					<div class="flex items-center cursor-pointer mr-4 bg-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded"
						 data-action="openScreen"
						 data-id="<?php echo esc_attr( $post->ID ); ?>"
						 data-type="capture"
						 data-context="camera_settings">
						<span class="material-symbols-outlined w-6 h-6 mr-2 text-black flex-none">photo_camera</span>
						Advanced Camera Settings
					</div>

					<!-- Advanced Light -->
					<div class="flex items-center cursor-pointer mr-4 bg-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded"
						 data-action="openScreen"
						 data-id="<?php echo esc_attr( $post->ID ); ?>"
						 data-type="capture"
						 data-context="light_settings">
						<span class="material-symbols-outlined w-6 h-6 mr-2 text-black flex-none">light_mode</span>
						Advanced Light Settings
					</div>

					<!-- Save as Preset -->
					<div class="flex items-center cursor-pointer mr-4 bg-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded"
						 data-action="save_as_preset"
						 data-id="<?php echo esc_attr( get_post_meta( $post->ID, 'capture_camera_settings', true ) ); ?>">
						<span class="material-symbols-outlined w-6 h-6 mr-2 text-black flex-none">save</span>
						Save as Preset
					</div>

					<!-- Load Preset -->
					<div class="flex items-center mr-4 preset-dropdown-container">
						<select id="preset-select"
								class="bg-black text-white border border-esper-yellow rounded-l px-3 py-2 focus:outline-none focus:ring-2 focus:ring-esper-yellow">
							<option value="">Select Preset</option>
							<?php
							$presets = get_posts( [
								'post_type'      => 'preset',
								'posts_per_page' => -1,
								'orderby'        => 'title',
								'order'          => 'ASC',
							] );
							foreach ( $presets as $preset ) {
								echo '<option value="' . esc_attr( $preset->ID ) . '">' . esc_html( $preset->post_title ) . '</option>';
							}
							?>
						</select>

						<button class="flex items-center bg-esper-yellow border border-esper-yellow hover:bg-esper-yellow/80 text-black px-4 py-2 rounded-r"
								data-action="load_preset"
								data-camera-settings-id="<?php echo esc_attr( get_post_meta( $post->ID, 'capture_camera_settings', true ) ); ?>">
							<span class="material-symbols-outlined w-6 h-6 text-black flex-none">download</span>
							Load
						</button>
					</div>
				</div><!-- /.action buttons -->

				<!-- Progress bar -->
				<div id="takeProgress" class="mt-4 opacity-0 transition-opacity duration-200">
					<div class="flex items-center justify-between mb-1">
						<span class="text-sm text-gray-400">Capturing Take...</span>
						<span class="text-sm text-gray-400" id="progressPercentage">0%</span>
					</div>
					<div class="w-full h-2 bg-gray-600 rounded-full overflow-hidden">
						<div id="takeProgressBar"
							 class="h-full bg-esper-yellow transition-all duration-300"
							 style="width:0%"></div>
					</div>
				</div>
			</div><!-- /HEADER -->



			<!-- ╔══════════════════════════════════════════════╗
			     ║                MAIN CONTENT                ║
			     ╚══════════════════════════════════════════════╝ -->
			<div class="bg-black p-6 flex flex-col lg:flex-row gap-6">

				<!-- ░░ LEFT column ░░ (Stage & Camera Settings) -->
				<div class="flex-1 space-y-6">

					<!-- ── Stage Settings ── -->
					<section>
						<h3 class="w-full mb-4">Current Stage Settings</h3>

						<?php $composer = new StageComposer( $post->ID ); ?>

						<?php if ( empty( $composer->rows ) ) : ?>
							<p class="opacity-70">No stage settings found.</p>
						<?php else : ?>
							<div class="-mx-2 flex overflow-x-auto pb-4">
								<?php foreach ( $composer->rows as $i => $row ) :

									$target     = strtoupper( $row['target']     ?? '' );
									$led        = strtoupper( $row['led']       ?? '' );
									$direction  = strtoupper( $row['direction'] ?? '' );
									$brightness = max( 0, min( 100, intval( $row['brightness'] ) ) );

									$dir_icon = 'start';
									if ( $direction === 'TOP'    ) $dir_icon = 'north';
									if ( $direction === 'BOTTOM' ) $dir_icon = 'south';

									$inactive  = 'bg-black border border-esper-yellow';
								?>
								<div class="mx-2 w-64 flex-shrink-0 bg-black text-white border border-white border-opacity-10 rounded-lg relative">

									<!-- big bg number -->
									<span class="absolute inset-0 flex items-center justify-center font-extrabold text-esper-yellow opacity-10 text-7xl leading-none pointer-events-none select-none">
										<?php echo $i + 1; ?>
									</span>

									<!-- header -->
									<h4 class="relative z-10 text-black font-semibold bg-esper-yellow uppercase w-full text-xs px-3 py-2 flex justify-between items-center">
										<span>Stage&nbsp;<?php echo $i + 1; ?></span>
										<span><?php echo esc_html( $led ); ?></span>
									</h4>

									<!-- body -->
									<div class="relative z-10 flex items-center justify-center">

										<!-- LED diagram -->
										<div class="flex flex-col items-center justify-center w-20 p-3">
											<div class="flex justify-between w-full">
												<span class="inline-block w-4 h-4 rounded-full <?php echo ( $led === 'PARALLEL' ) ? 'bg-esper-yellow' : $inactive; ?>"
													  <?php if ( $led === 'PARALLEL' ) echo 'style="opacity:' . ( $brightness / 100 ) . ';"'; ?>></span>

												<span class="inline-block w-4 h-4 rounded-full <?php echo ( $led === 'CROSS' ) ? 'bg-esper-yellow' : $inactive; ?>"
													  <?php if ( $led === 'CROSS' ) echo 'style="opacity:' . ( $brightness / 100 ) . ';"'; ?>></span>
											</div>
											<div class="flex justify-center w-full mt-4">
												<span class="inline-block w-4 h-4 rounded-full <?php echo ( $led === 'NEUTRAL' ) ? 'bg-esper-yellow' : $inactive; ?>"
													  <?php if ( $led === 'NEUTRAL' ) echo 'style="opacity:' . ( $brightness / 100 ) . ';"'; ?>></span>
											</div>
										</div>

										<!-- details -->
										<dl class="text-sm p-4 space-y-2">

											<?php if ( $target === 'REGION' ) : ?>
												<div class="flex items-center">
													<span class="material-symbols-outlined text-esper-yellow text-base mr-2"><?php echo $dir_icon; ?></span>
													<span class="sr-only">Direction:</span>
													<?php echo esc_html( $row['direction'] ); ?>
												</div>
											<?php endif; ?>

											<?php if ( $target === 'INDIVIDUAL' ) : ?>
												<div class="flex items-center">
													<span class="material-symbols-outlined text-esper-yellow text-base mr-2">lightbulb</span>
													<span class="sr-only">Light ID:</span>
													<?php echo esc_html( $row['light_id'] ?: '—' ); ?>
												</div>
											<?php endif; ?>

											<div class="flex items-center">
												<span class="material-symbols-outlined text-esper-yellow text-base mr-2">wb_incandescent</span>
												<span class="sr-only">Brightness:</span>
												<?php echo $brightness; ?> %
											</div>

											<div class="flex items-center">
												<span class="material-symbols-outlined text-esper-yellow text-base mr-2">flash_on</span>
												<span class="sr-only">Flash duration:</span>
												<?php echo esc_html( $row['flash_duration'] ); ?> s
											</div>

											<div class="flex items-center">
												<span class="material-symbols-outlined text-esper-yellow text-base mr-2">my_location</span>
												<span class="sr-only">Target:</span>
												<?php echo esc_html( $target ); ?>
											</div>
										</dl>
									</div>
								</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</section><!-- /stage -->

					<!-- ── Camera Settings ── -->
					<section>
						<h3 class="w-full mb-4">Current Camera Settings</h3>
						<?php
						$cameraSettings = new CameraSettings( $post );
						$common         = $cameraSettings->findCommonFields( $cameraSettings->rows );
						echo $cameraSettings->renderCommonFields( $common );
						?>
					</section>

				</div><!-- /.left column -->


				<!-- ░░ RIGHT column (thumbnail + modal) ░░ -->
				<?php if ( ! empty( $pose_image ) ) : ?>
					<aside class="w-full lg:w-1/3 shrink-0 bg-black border border-white border-opacity-10 rounded-lg p-4">
						<h3 class="text-lg font-semibold text-white mb-4">Pose Image</h3>

						<!-- Thumbnail -->
						<img id="<?php echo esc_attr( $thumb_id ); ?>"
							 src="<?php echo esc_url( $pose_image ); ?>"
							 alt="Pose image"
							 class="w-full rounded cursor-zoom-in">

						<!-- Full-screen modal -->
						<div id="<?php echo esc_attr( $modal_id ); ?>"
							 class="fixed inset-0 bg-black/90 flex items-center justify-center z-50 hidden">

							<!-- Close button -->
							<button id="<?php echo esc_attr( $close_id ); ?>"
									class="absolute top-4 right-4 text-white text-4xl leading-none font-bold">
								&times;
							</button>

							<!-- Large image -->
							<img src="<?php echo esc_url( $pose_image ); ?>"
								 alt="Pose image large"
								 class="w-full h-screen bg-cover shadow-lg">
						</div>
					</aside>
				<?php endif; ?>

			</div><!-- /.MAIN CONTENT -->



			<!-- ╔══════════════════════════════════════════════╗
			     ║                TAKES GALLERY                ║
			     ╚══════════════════════════════════════════════╝ -->
			<div class="bg-black shadow-lg p-6">
				<div class="mb-4">
					<h3 id="toggleTakes" class="text-lg font-semibold text-white cursor-pointer inline-flex items-center">
						Takes
						<span id="toggleTakesIcon" class="material-symbols-outlined transition-transform duration-300 ml-2">keyboard_arrow_down</span>
					</h3>
				</div>

				<?php
				$takes = get_posts( [
					'post_type'      => 'take',
					'meta_key'       => 'parent_capture',
					'meta_value'     => $post->ID,
					'posts_per_page' => -1,
					'orderby'        => 'date',
					'order'          => 'DESC',
				] );

				$galleryClasses = ! empty( $takes ) ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4' : '';
				?>
				<div id="takesGallery" class="<?php echo esc_attr( $galleryClasses ); ?>">

					<?php if ( empty( $takes ) ) : ?>
						<p class="text-white">No takes found, please trigger some.</p>

					<?php else : ?>
						<?php foreach ( $takes as $take ) :

							$thumbnail = get_the_post_thumbnail_url( $take->ID, 'medium' ) ?: 'https://placehold.co/600x400';

							// Does this take have any images marked red?
							$has_red_image = ! empty( get_posts( [
								'post_type'      => 'take_image',
								'posts_per_page' => 1,
								'fields'         => 'ids',
								'meta_query'     => [
									'relation' => 'AND',
									[
										'key'   => 'take_id',
										'value' => $take->ID,
									],
									[
										'key'   => 'colour_rating',
										'value' => 'red',
									],
								],
							] ) );

							$red_badge   = $has_red_image
								? '<span class="absolute top-2 right-2 w-3 h-3 rounded-full bg-red-500" title="Contains at least one red-rated image"></span>'
								: '';

							$borderClass = $has_red_image ? 'border-red-500' : 'border-white border-opacity-10';
							?>
							<div class="take-card relative bg-black overflow-hidden cursor-pointer border <?php echo $borderClass; ?> hover:bg-white hover:bg-opacity-10 transition"
								 data-take-id="<?php echo esc_attr( $take->ID ); ?>">

								<?php echo $red_badge; ?>

								<img src="<?php echo esc_url( $thumbnail ); ?>"
									 alt="<?php echo esc_attr( $take->post_title ); ?>"
									 class="w-full aspect-w-16 aspect-h-9 object-cover">

								<div class="p-4">
									<h4 class="text-white font-semibold"><?php echo esc_html( $take->post_title ); ?></h4>
									<p class="text-gray-400 text-sm"><?php echo get_the_date( 'F j, Y g:i a', $take ); ?></p>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>

				</div><!-- /#takesGallery -->
			</div><!-- /TAKES GALLERY -->

		</div><!-- /.w-full -->
	</div><!-- /.capture-template -->

	<style>
	.rotate-180 { transform: rotate(180deg); }
	/* Prevent background scroll when modal open */
	body.modal-open { overflow:hidden; }
	</style>

	<script>
	jQuery( function ( $ ) {

		/* toggle takes gallery (existing) */
		$( '#toggleTakes' ).on( 'click', function () {
			$( '#takesGallery' ).slideToggle( 300 );
			$( '#toggleTakesIcon' ).toggleClass( 'rotate-180' );
		} );

		/* ───────── pose image light-box ───────── */
		<?php if ( ! empty( $pose_image ) ) : ?>
			let $thumb = $( '#<?php echo esc_js( $thumb_id ); ?>' ),
				$modal = $( '#<?php echo esc_js( $modal_id ); ?>' ),
				$close = $( '#<?php echo esc_js( $close_id ); ?>' );

			/* open */
			$thumb.on( 'click', function () {
				$modal.removeClass( 'hidden' );
				$( 'body' ).addClass( 'modal-open' );
			} );

			/* close by button or backdrop click */
			function closeModal( e ) {
				if ( e.target === this ) {
					$modal.addClass( 'hidden' );
					$( 'body' ).removeClass( 'modal-open' );
				}
			}
			$close.on( 'click', closeModal );
			$modal.on( 'click', closeModal );
		<?php endif; ?>

	} );
	</script>
	<?php
	return ob_get_clean();
}
