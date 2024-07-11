<?php
    define('__SHAFI_FOLDER', dirname(__FILE__) . '/');
    define('__SHAFI_INC', __SHAFI_FOLDER . 'inc/');
    define('__SHAFI_CONF_FOLDER', __SHAFI_FOLDER . 'config/');

    // Get rid of the favicon.ico request for the rest of the application
    if ($_SERVER['REQUEST_URI'] == '/favicon.ico') {
        header('Content-Type: image/x-icon');
        echo file_get_contents(__SHAFI_FOLDER . 'favicon.ico');
        exit();
    }

    // Initialize the session
    session_start();

    require_once(__SHAFI_INC . 'i18n.php');
    require_once(__SHAFI_INC . 'debug.php');
    require_once(__SHAFI_FOLDER . 'defaultconfig.php');
    require_once(__SHAFI_INC . 'ops.php');

    require_once(__SHAFI_INC . 'notice.php');
    require_once(__SHAFI_INC . 'roprops.php');
    require_once(__SHAFI_INC . 'helpers.php');
    require_once(__SHAFI_INC . 'file.php');
    require_once(__SHAFI_INC . 'token.php');
    require_once(__SHAFI_INC . 'user.php');
    require_once(__SHAFI_INC . 'storage.php');
    require_once(__SHAFI_INC . 'list.php');
    require_once(__SHAFI_INC . 'router.php');
    require_once(__SHAFI_INC . 'quota.php');

    // Enable to use a custom layout so that the main divs are not included (from the views)
    global $CUSTOM_LAYOUT;
    $CUSTOM_LAYOUT = false;

    function custom_layout() {
        global $CUSTOM_LAYOUT;
        $CUSTOM_LAYOUT = true;
    }

    // Enable to hide the toolbar (from the views)
    global $SHOW_TOOLBAR;
    $SHOW_TOOLBAR = true;
    
    function hide_toolbar() {
        global $SHOW_TOOLBAR;
        $SHOW_TOOLBAR = false;
    }
    
    // A function to show the "official" title of the application, along with the messages that are pending (even from other pages, using the $pagecomm plugin)
    function show_title() {
        global $__messages;
        global $pagecomm;
        ?>
            <div class="container text-center">
        <?php 
            echo implode(' ', $pagecomm->get_messages(true));
            echo $__messages; 
        ?>  
        <h2>Share Files (ShaFi) <i class="fas fa-share-alt"></i></h2>
        </div>
    <?php
    }
    
    // Get a new background image to set it as the background of the page
    function get_background($force = false) {
        $_SESSION['bgCounter'] = (($_SESSION['bgCounter']??0) + 1) % __SHAFI_BG_REFRESH_RATE;
        $bg_url = $_SESSION['bgImage']??null;
        if (($_SESSION['bgCounter'] == 0) || ($bg_url === null)) {
            $url_content = file_get_contents(__SHAFI_CONF_FOLDER . "background-list.json");
            $background_urls = [];
            if ($url_content !== false) {
                $background_urls = json_decode($url_content);
            }
            $bg_url = $background_urls[array_rand($background_urls)];
        }
        $_SESSION['bgImage'] = $bg_url;
        return $bg_url;
    }

    // Force the refresh of the background image for the next request
    function refresh_background() {
        unset($_SESSION['bgCounter']);
        unset($_SESSION['bgImage']);
    }

    // TODO:
    // - enable mail sending of links

    $op = isset($_GET['op'])?$_GET['op']:'';
    $token = isset($_GET['f'])?$_GET['f']:'';

    // First define the set of permissions
    $acl_manager->add_perm_acl('login', '!l');
    $acl_manager->add_perm_acl('logout', 'l');
    $acl_manager->add_perm_acl('user', 'l');
    $acl_manager->add_perm_acl('admin', 'a');
    $acl_manager->add_perm_acl('manage-users', 'a');
    $acl_manager->add_perm_acl('upload', 'l');
    $acl_manager->add_perm_acl('upload-anonymous', '!l');
    $acl_manager->add_perm_acl('list', 'u');
    $acl_manager->add_perm_acl('list-all', 'a');
    $acl_manager->add_perm_acl('download', 'uo');
    $acl_manager->add_perm_acl('download', 'a');
    $acl_manager->add_perm_acl('edit', 'uo');
    $acl_manager->add_perm_acl('edit', 'a');
    $acl_manager->add_perm_acl('delete-file', 'uo');
    $acl_manager->add_perm_acl('delete-file', 'a');

    // First create the router for our application; it is a simple router thet we must manage
    $router = new Router();

    // Add a static route
    // WARNING: means that these files will be included INSIDE the template
    $router->add_static_folder('static');

    $router->add_post_callback(function($route, $op, $op_o) {
        global $__messages;
        if ($op_o !== null)
            $__messages = $op_o->messages;
    });

    $router->add(null, null, 'SHAFI_Op_Get', function($handler) {
        if ($handler->needs_password) {
            if (! $handler->correct_password) {
                include_once('templates/passwd.php');
                return;
            }
        }
        if ($handler->show_info)
            include_once('templates/fileinfo.php');
        else {
            // If we get to this point, we should show an error
            include_once('templates/error.php'); 
        }
    });

    $router->add('upload', null, 'SHAFI_Op_UploadChunked', 'templates/error.php');

    if ($current_user->is_logged_in()) {
        if ($current_user->is_user() || $current_user->is_admin()) {
            $router->add('', '', 'SHAFI_Op_UploadFile_Chunked', 'templates/upload-file.php');
            $router->add('up', null, 'SHAFI_Op_UploadFile', 'templates/upload-file-legacy.php');

            $router->add('', 'download', 'SHAFI_Op_DownloadFile', 'templates/error.php');
            $router->add('', 'edit', 'SHAFI_Op_Edit', 'templates/edit.php');
            $router->add('admin', null, 'SHAFI_Op_UpdateProfile', 'templates/userinfo.php');
            $router->add('admin', 'list', 'SHAFI_Op_List', 'templates/list.php');
            $router->add('admin', 'logout', 'SHAFI_Op_Logout', 'templates/error.php');
            $router->add('admin', 'del', 'SHAFI_Op_DeleteFile', 'templates/error.php');
        }
        if ($current_user->is_admin()) {
            $router->add('admin', 'listall', 'SHAFI_Op_ListAll', 'templates/list-all.php');
            $router->add('admin', 'users', 'SHAFI_Op_Users', 'templates/users.php');
        }
    } else {
        if (__ANONYMOUS_UPLOAD) {
            $router->add('', null, 'SHAFI_Op_UploadFile_Anonymous_Chunked', 'templates/upload-file-anonymous.php');
            $router->add('up', null, 'SHAFI_Op_UploadFile_Anonymous', 'templates/upload-file-anonymous-legacy.php');
        }
        else
            $router->add('', null, 'SHAFI_Op', 'templates/home.php');

        $router->add('admin', 'login', 'SHAFI_Op_Login', 'templates/login.php');
    }

    if (defined('__CUSTOM_ROUTES') && (is_callable(__CUSTOM_ROUTES)))
        call_user_func_array(__CUSTOM_ROUTES, [ $router ]);

    $router->exec($token, $op);
?>
<?php 
    ob_start();
    $router->view($token, $op);
    $content = ob_get_clean();
?>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Share Files (ShaFi)</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <!-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" crossorigin="anonymous"> -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome-animation/0.2.1/font-awesome-animation.min.css">
        <link rel="stylesheet" href="<?php echo get_root_url() ?>css/notice.css">
        <link rel="stylesheet" href="<?php echo get_root_url() ?>css/ddn-table-smart.css">
        <link rel="stylesheet" href="<?php echo get_root_url() ?>css/shafi.css">
        <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        <!-- <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>         -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.4/clipboard.min.js"></script>
        <script src="<?php echo get_root_url() ?>js/notice.js"></script>
        <script src="<?php echo get_root_url() ?>js/ddn-table-smart.js"></script>
        <script src="<?php echo get_root_url() ?>js/collapsible.js"></script>
        <script src="<?php echo get_root_url() ?>js/resumable.js"></script>
        <script src="<?php echo get_root_url() ?>js/resumable-link.js"></script>
        <script src="<?php echo get_root_url() ?>js/shafi.js"></script>
        <style>
            body {
                background-size: cover;
                background-repeat: no-repeat;
                background-position: center;
                background-image: url("<?php echo get_background(); ?>");
            }
            .rounded {
                border-radius: 1rem !important;
            }

            .bg-white {
                background-color: white !important;
                opacity: 0.9;
            }
        </style>
    </head>
    <body>	
        <div class="debugbar">
            <?php echo $DEBUG; ?>
        </div>
        <?php if ($SHOW_TOOLBAR) { ?>
        <div class="toolbar">
            <div class="left"><a href="<?php echo get_root_url() ?>"><i class="fas fa-home"></i><span class="label">ShaFi</span></a></div>
            <div class="right w-25">
                <?php if ($current_user->is_logged_in()) { ?>
                    <?php if ($current_user->is_admin()) { ?>
                        <a href="<?php echo add_query_var(['op' => 'users', 'id' => null], __ADMIN_URL); ?>">
                        <i class="fas fa-users-cog"></i><span><?php _e('Manage users') ?></span>
                        </a> 
                        <a href="<?php echo add_query_var(['op' => 'listall', 'id' => null], __ADMIN_URL); ?>">
                        <i class="fas fa-archive"></i><span><?php _e('Files from all users') ?></span>
                        </a> 
                        <br>
                    <?php } ?>
                <a href="<?php echo add_query_var(['op' => 'list', 'id' => null], __ADMIN_URL); ?>">
                    <i class="fas fa-list"></i><span><?php _e('Files') ?></span>
                </a> 
                <a href="<?php echo add_query_var(['op' => null, 'id' => null], __ADMIN_URL); ?>">
                    <i class="fas fa-user"></i><span class="label"><?php _e('User') ?>: </span><?php echo $current_user->get_username() ?>
                </a>
                <a href="<?php echo add_query_var(['op' => 'logout', 'id' => null], __ADMIN_URL); ?>">
                    <span><?php _e('Log out') ?></span><i class="fas fa-sign-out-alt"></i>
                </a>
                <?php } else {?>
                    <a href="<?php echo add_query_var(['op' => 'login', 'id' => null], __ADMIN_URL); ?>">
                    <i class="fas fa-user-slash"></i><span><?php _e('Log in') ?></span>
                </a>
                <?php } ?>
                <?php
                    if ($current_user->is_logged_in()) {
                        $filessize = $quota_manager->get_user_filessize($current_user);
                        $quota = $quota_manager->get_user_quota($current_user);
                        $pct = 0;
                        if ($quota > 0)
                            $pct = 100.0 * (float)$filessize / (float)$quota;
                        
                        $fillclass = 'bg-success';
                        if ($pct > 40) $fillclass = 'bg-info';
                        if ($pct > 75) $fillclass = 'bg-warning';
                        if ($pct > 90) $fillclass = 'bg-danger';

                        $pctstyle = sprintf("%.2f", min($pct, 100));
                        $pct = sprintf("%.2f", $pct, 100);
                        $intext = human_filesize($filessize) . "($pct %)";                    
                        echo "<div class='progress'><div class='progress-bar $fillclass' role='progressbar' style='width: $pctstyle%'>$intext</div></div>";
                    }
                ?>
            </div>
        </div>
        <?php } ?>
        <div class="container h-100 d-flex">
            <?php if (!$CUSTOM_LAYOUT) { ?>
                <div class="v-center w-100 bg-white rounded px-3 py-3 rounded rounded-lg shadow-lg">
                    <div class="col-md-6 offset-md-3">
                        <?php
                            show_title();
                        ?>
                    </div>
                    <?php echo $content; ?>
                </div>
            <?php } else { ?>
                <?php echo $content; ?>
            <?php } ?>
        </div>
    </body>
    <script>
        $(function() {
            $('.modal').appendTo('body');
        })
    </script>
</html>
