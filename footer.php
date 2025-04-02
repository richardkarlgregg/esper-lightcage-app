    <footer class="hidden">
        <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.</p>
    </footer>
</div><!-- .container -->
        </div><!-- #content -->
    </div><!-- .flex.h-screen -->
<?php wp_footer(); ?>

<!-- Overlay  Dialog -->
<div class="fixed inset-0 z-50 flex items-center justify-center bg-darkestgrey bg-opacity-90 overlayDialog" style="display: none;">
    <div class="bg-white p-6 overlayDialogInner">
        <!-- Dialog Header -->
        <div class="flex justify-between items-center pb-2">
            <h2 id="dialogTitle" class="w-full text-2xl font-bold uppercase"></h2>
            <button id="dialogCloseButton" class="text-darkestgrey text-4xl">
                &times;
            </button>
        </div>

		<!-- Feedback Notification -->
        <div id="dialogFeedback" class="hidden text-sm text-red-600"></div>

        <!-- Dialog Body -->
        <div id="dialogBody" class="mb-3 text-gray-700"></div>

        <!-- Dialog Footer -->
        <div id="dialogFooter" class="flex justify-start space-x-3 mt-4"></div>
    </div>
</div>
</body>
</html> 