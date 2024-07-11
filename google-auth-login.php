<?php
if ( ! defined( '__SHAFI_FOLDER' ) ) {
    exit; // Exit if accessed directly
}

if (!defined('__GOOGLE_CLIENT_ID'))
    define('__GOOGLE_CLIENT_ID', 'INSERT-YOUR-GOOGLE_CLIENT_ID-HERE');
if (!defined('__GOOGLE_CLIENT_SECRET'))
    define('__GOOGLE_CLIENT_SECRET', 'INSERT-YOUR-GOOGLE_CLIENT_SECRET-HERE');
if (!defined('__GOOGLE_REDIRECT_URI'))
    define('__GOOGLE_REDIRECT_URI', 'http://localhost:8000/auth-google');
if (!defined('__GOOGLE_OAUTH_VERSION'))
    define('__GOOGLE_OAUTH_VERSION', 'v3');

define('__CUSTOM_ROUTES', function($router) {
    $router->add('auth-google', null, 'SHAFI_Op_Google_Auth', 'templates/error.php');

    $router->add_view_post_callback(function($route, $op) {
        if (($route === 'admin') && ($op === 'login')) {
            ?>
    <div class="container small text-center">
        <a class="btn btn-outline-dark" href="<?php echo add_query_var([], '/auth-google') ?>" role="button" style="text-transform:none">
            <i class="fab fa-google"></i>
            <?php _e('Login with Google'); ?>
        </a>
    </div>
            <?php
        }
    });
});