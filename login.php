<?php
/*
Template Name: Login Page
*/

// If user is logged in, redirect to home
if (is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

// Don't load header for login page
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body class="bg-black">

<div class="min-h-screen bg-black flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 bg-black/80 p-8 border border-white border-opacity-10 rounded-lg">
        <!-- Logo/Header -->
        <div class="text-center">
            <img src="<?php echo get_theme_file_uri('assets/images/esper-logo.svg'); ?>" alt="Esper Logo" class="h-8 mx-auto mb-4">
            <h2 class="text-2xl font-bold text-white">Welcome to Esper Light Cage</h2>
            <p class="mt-2 text-sm text-gray-400">Please sign in to continue</p>
        </div>

        <!-- Login Form -->
        <form id="loginForm" class="mt-8 space-y-6">
            <!-- Alert Container -->
            <div id="loginAlert" class="hidden">
                <div class="p-4 rounded-md">
                    <p class="text-sm"></p>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label for="username" class="sr-only">Username or Email</label>
                    <input id="username" name="username" type="text" required 
                           class="appearance-none relative block w-full px-3 py-2 border border-white border-opacity-10 
                                  bg-black text-white rounded-md focus:outline-none focus:ring-esper-yellow 
                                  focus:border-esper-yellow focus:z-10 sm:text-sm" 
                           placeholder="Username or Email">
                </div>
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" required 
                           class="appearance-none relative block w-full px-3 py-2 border border-white border-opacity-10 
                                  bg-black text-white rounded-md focus:outline-none focus:ring-esper-yellow 
                                  focus:border-esper-yellow focus:z-10 sm:text-sm" 
                           placeholder="Password">
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember-me" name="remember-me" type="checkbox" 
                           class="h-4 w-4 bg-black border-white border-opacity-10 rounded">
                    <label for="remember-me" class="ml-2 block text-sm text-gray-400">
                        Remember me
                    </label>
                </div>

                <div class="text-sm">
                    <a href="<?php echo wp_lostpassword_url(); ?>" class="text-esper-yellow hover:text-esper-yellow/80">
                        Forgot your password?
                    </a>
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent 
                               text-sm font-medium rounded-md text-black bg-esper-yellow hover:bg-esper-yellow/80 
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-esper-yellow">
                    <span id="loginButtonText">Sign in</span>
                    <span id="loginSpinner" class="hidden">
                        <svg class="animate-spin h-5 w-5 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        // Show spinner, hide button text
        $('#loginButtonText').addClass('hidden');
        $('#loginSpinner').removeClass('hidden');
        
        // Reset alert
        $('#loginAlert').addClass('hidden');
        
        $.ajax({
            url: esperApi.ajaxurl,
            type: 'POST',
            data: {
                action: 'esper_ajax_login',
                username: $('#username').val(),
                password: $('#password').val(),
                remember: $('#remember-me').is(':checked'),
                nonce: esperApi.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('#loginAlert')
                        .removeClass('hidden')
                        .find('.p-4')
                        .removeClass('bg-red-500/10')
                        .addClass('bg-green-500/10')
                        .find('p')
                        .removeClass('text-red-400')
                        .addClass('text-green-400')
                        .text('Login successful! Loading application...');
                    
                    // Redirect to home page after successful login
                    setTimeout(function() {
                        window.location.href = '<?php echo home_url(); ?>';
                    }, 1000);
                } else {
                    // Show error message
                    $('#loginAlert')
                        .removeClass('hidden')
                        .find('.p-4')
                        .removeClass('bg-green-500/10')
                        .addClass('bg-red-500/10')
                        .find('p')
                        .removeClass('text-green-400')
                        .addClass('text-red-400')
                        .text(response.data.message || 'An error occurred');
                    
                    // Reset button
                    $('#loginButtonText').removeClass('hidden');
                    $('#loginSpinner').addClass('hidden');
                }
            },
            error: function() {
                // Show error message
                $('#loginAlert')
                    .removeClass('hidden')
                    .find('.p-4')
                    .removeClass('bg-green-500/10')
                    .addClass('bg-red-500/10')
                    .find('p')
                    .removeClass('text-green-400')
                    .addClass('text-red-400')
                    .text('A server error occurred. Please try again.');
                
                // Reset button
                $('#loginButtonText').removeClass('hidden');
                $('#loginSpinner').addClass('hidden');
            }
        });
    });
});
</script>

<?php wp_footer(); ?>
</body>
</html> 