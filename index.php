<?php
    // Initialize the session
    session_start();

    define('__SHAFI_FOLDER', dirname(__FILE__) . '/');
    define('__SHAFI_INC', __SHAFI_FOLDER . 'inc/');

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

    if (!defined(__CUSTOM_ROUTES))
        define('__CUSTOM_ROUTES', null);

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

    $router->add_post_callback(function($op_o, $op) {
        global $__messages;
        if ($op_o !== null)
            $__messages = $op_o->messages;
    });

    $router->add(null, null, 'SHAFI_Op_Get', function($handler) {
        if ($handler->needs_password)
            include_once('templates/passwd.php');
        else
            include_once('templates/error.php'); 
    });

    $router->add('upload', null, 'SHAFI_Op_UploadChunked', 'templates/error.php');

    if ($current_user->is_logged_in()) {
        if ($current_user->is_user() || $current_user->is_admin()) {
            $router->add('', '', 'SHAFI_Op_UploadFile_Chunked', 'templates/upload-file.php');
            $router->add('up', null, 'SHAFI_Op_UploadFile', 'templates/upload-file-legacy.php');

            $router->add('', 'download', 'SHAFI_Op_DownloadFile', 'templates/error.php');
            $router->add('', 'edit', 'SHAFI_Op_Edit', 'templates/edit.php');
            $router->add('admin', null, 'SHAFI_Op_Setpass', 'templates/userinfo.php');
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

    if (is_callable(__CUSTOM_ROUTES))
        call_user_func_array(__CUSTOM_ROUTES, [ $router ]);

    $router->exec($token, $op);
?>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Share Files (ShaFi)</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome-animation/0.2.1/font-awesome-animation.min.css">
        <link rel="stylesheet" href="<?php echo get_root_url() ?>css/notice.css">
        <link rel="stylesheet" href="<?php echo get_root_url() ?>css/ddn-table-smart.css">
        <link rel="stylesheet" href="<?php echo get_root_url() ?>css/shafi.css">
        <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>        
        <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.4/clipboard.min.js"></script>
        <script src="<?php echo get_root_url() ?>js/notice.js"></script>
        <script src="<?php echo get_root_url() ?>js/ddn-table-smart.js"></script>
        <script src="<?php echo get_root_url() ?>js/collapsible.js"></script>
        <script src="<?php echo get_root_url() ?>js/resumable.js"></script>
        <script src="<?php echo get_root_url() ?>js/resumable-link.js"></script>
        <script src="<?php echo get_root_url() ?>js/shafi.js"></script>
    </head>
    <body>	
        <div class="debugbar">
            <?php echo $DEBUG; ?>
        </div>
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
        <div class="container h-100">
            <div class="row h-100">
                <div class="v-center w-100">
                    <div class="col-md-6 offset-md-3">
                        <div class="container text-center">
                            <?php 
                                echo implode(' ', $pagecomm->get_messages(true));
                                echo $__messages; 
                            ?>
                            <h2>Share Files (ShaFi) <i class="fas fa-share-alt"></i></h2>
                        </div>
                    </div>
                    <?php 
                    $router->view($token, $op);   
                    ?>
                </div>
            </div>
        </div>
    </body>
</html>
