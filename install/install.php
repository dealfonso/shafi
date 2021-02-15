<?php
    define('__SHAFI_FOLDER', realpath(dirname(__FILE__) . '/..'));
    define('__SHAFI_INC', __SHAFI_FOLDER . '/inc/');
    $error = array();
    $hint = array();
    $config_lines = array();
    $htaccess = array();

    require_once(__SHAFI_INC . '/i18n.php');

    function value($v) {
        global $info;
        if (isset($_POST[$v]) && ($_POST[$v] != '')) {
            echo "value='${_POST[$v]}'";
        }
    }

    function rand_string( $length ) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        return substr(str_shuffle($chars),0,$length);
    }

    $var = [ 'dbhost' => 'localhost', 'dbname' => 'shafi', 'dbuser', 'dbpassword', 'servername', 'rooturl' => '/', 'storageroot' => './uploads', 'adminuser' => 'shafi', 'adminpassword' => '123', 'enableanonymous' => null  ];
    foreach ($var as $k => $v) {
        $default = null;
        if (is_int($k)) {
            $k = $v;
            $v = null;
        } else {
            $default = $v;
        }
        if (isset($_POST[$k])) {
            $v = $_POST[$k];
            if ($v == "") $v = null;
        }
        if ($v !== null) $default = $v;

        $info[$k] = $default;
    }

    if (isset($_POST['install'])) {
        $db_servername = $info['dbhost'];
        $db_username = $info['dbuser'];
        $db_password = $info['dbpassword'];
        $db_database = $info['dbname']; 
        $db_tables_prefix = "";

        require_once('../inc/db.php');
        require_once(__SHAFI_INC . '/dbcreation.php');

        if ($wpdb !== null) {
            if (!create_db()) {
                array_push($error, "Failed to create tables in the database");
            } else {
                if (!create_first_user($info['adminuser'], $info['adminpassword'])) {
                    array_push($error, "Failed to create first user, but the tables have been created");
                } 
                $info['storageroot'] = rtrim($info['storageroot'], '/');
                if (substr($info['storageroot'],0,1) != '/')
                    $info['storageroot'] = __SHAFI_FOLDER . '/' . $info['storageroot'];

                $testfolder = $info['storageroot'] . "/" . rand_string(8);
                if (!mkdir($testfolder, 0700, true)) {
                    array_push($error, "Could not write in the storage root folder");
                } else {
                    $info['storageroot'] = realpath(dirname($testfolder));
                    rmdir($testfolder);
                    $info['rooturl'] = rtrim($info['rooturl']) . '/';

                    $config_lines = array(
                    "<?php",
                    "\$db_servername='${info['dbhost']}';",
                    "\$db_database='${info['dbname']}';",
                    "\$db_username='${info['dbuser']}';",
                    "\$db_password='${info['dbpassword']}';",
                    "define('__STORAGE_BASE_FOLDER', '${info['storageroot']}');",
                    "define('__SERVER_NAME', '${info['servername']}');",
                    "define('__ROOT_URL', '${info['rooturl']}');"
                    );

                    array_push($config_lines, 
                        "define('__ANONYMOUS_UPLOAD', " . ($info['enableanonymous'] === null?"false":"true") . ");"
                    );
                    
                    $htaccess = array(
                    "<IfModule mod_rewrite.c>",
                    "RewriteEngine On",
                    "RewriteBase ${info['rooturl']}",
                    "RewriteRule ^index\.php$ - [L]",
                    "RewriteRule ^favicon\.ico$ - [L]",
                    "RewriteCond %{REQUEST_FILENAME} -f",
                    "RewriteRule ^(.*)$ - [L,QSA]",
                    "RewriteCond %{REQUEST_FILENAME} !-f",
                    "RewriteCond %{REQUEST_FILENAME} !-d",
                    "RewriteRule ^(.*)$ index.php?f=$1 [L,QSA]",
                    "</IfModule>");
                }
            }
        } else {
            array_push($error, "Failed to connect to database");
        }
    }    
?>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Share Files (ShaFi)</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome-animation/0.2.1/font-awesome-animation.min.css">
        <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>        
        <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.4/clipboard.min.js"></script>
        <script>
$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})        
        </script>
    </head>
    <body>	
        <div class="container h-100">
            <div class="row h-100">
                <div class="v-center w-100">
                    <div class="col-md-8 offset-md-2">
                        <div class="container text-left">
                            <h2 class="text-center">Share Files (ShaFi) <i class="fas fa-share-alt"></i></h2>
                            <?php
                            foreach ($error as $e) {
                                echo "<div class='alert alert-danger' role='alert'>$e</div>";
                            }
                            ?>
                            
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <p>Use this script with caution. It will modify the database and try to create the tables needed for ShaFi to work, and may modify some files.</p>
                                <p>Detailed steps</p>
                                <ul>
                                <li>Connect to the database (using the provided username and password).</li>
                                <li>Create the tables needed for ShaFi to work in the database.</li>
                                <li>Create an admin user for ShaFi, in its tables (with the provided credentials)</li>
                                <li>Generate the configuration files needed, according to your installation (they will not be written to disk unless you mark the corresponding checkboxes)</li>
                                </ul>
                                <p class="text-center"><button data-dismiss="alert" role="button" aria-expanded="true" class="btn btn-danger btn-lg">Acknowledge</button></p>
                            </div>
                            <?php
                            if (sizeof($config_lines) > 0) {
                            ?>
                            <h3>File 'config.php'</h3>
                            <p>You have to create file <span class="font-weight-bold"><?php echo __SHAFI_FOLDER; ?>/config.php</span> at the web server, with the following content:</p>
                            <pre class="text-left"><code><?php echo htmlentities(implode(PHP_EOL, $config_lines));?></code></pre>
                            <?php
                            if (isset($_POST['createconfig']))
                                file_put_contents(__SHAFI_FOLDER . "/config.php", implode(PHP_EOL, $config_lines));
                            ?>
                            <h3>File '.htaccess'</h3>
                            <p>You have to create file <span class="font-weight-bold"><?php echo __SHAFI_FOLDER; ?>/.htaccess</span> at the web server, with the following content:</p>
                            <pre><code><?php echo htmlentities(implode(PHP_EOL, $htaccess));?></code></pre>
                            <?php
                            if (isset($_POST['createhtaccess']))
                                file_put_contents(__SHAFI_FOLDER . "/.htaccess", implode(PHP_EOL, $htaccess));
                            ?>
                            <h3>Configuration of apache2</h3>
                            <p>You need to make sure that you have mod_rewrite enabled in apache2. You can make sure by executing the next commands:</p>
                            <pre><code>$ a2enmod rewrite
$ apachectl restart
</code></pre>
                            <p>And also you have enabled the usage of .htaccess for this folder. Please configure the folder for ShaFi application with the next settings (e.g. in /etc/apache2/apache2.conf or in the proper web server)</p>
                            <pre><code>
&lt;Directory /var/www/html/shafi<&gt;
    AllowOverride All
&lt;/Directory&gt;
                            </code></pre>
                            <?php
                                $info['servername'] = rtrim($info['servername'], '/');
                                $info['rooturl'] = ltrim($info['rooturl']);
                                $url = $info['servername'] . $info['rooturl'];
                            ?>
                            <h1 class="text-center"><a href="<?php echo $url  ?>"><i class="fas fa-home"></i><span class="label"> Get to your ShaFi installation</span></a></h1>
                            <?php
                            } else {
                            ?>
                            <form method=post class="text-left" oninput='adminpassword2.setCustomValidity(adminpassword.value != adminpassword2.value ? "Passwords do not match": "")'>                            
                                <h3>Write files</h3>
                                <p>Warning: writing files may damage your existing installation</p>
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="createconfig" id="createconfig">
                                        <label class="form-check-label" for="createconfig">Write config.php</label>
                                    </div>                  
                                </div>              
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="createhtaccess" id="createhtaccess">
                                        <label class="form-check-label" for="createhtaccess">Write .htaccess</label>
                                    </div>                                
                                </div>
                                <h3>Database information</h3>
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="dbhost">Database host</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name='dbhost' id="dbhost" placeholder="e.g. localhost:3306 (default: localhost:3306)" <?php value('dbhost'); ?>></<input>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label"  for="dbname">Database name</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name='dbname' id="dbname" placeholder="database must exist (default: shafi)" <?php value('dbname'); ?>></<input>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label"  for="dbuser">Database user</label>
                                    <div class="col-sm-9">
                                    <input type="text" required="true" class="form-control" name='dbuser' id="dbuser" placeholder="has to have permissions to create tables" <?php value('dbuser'); ?>></<input>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label"  for="dbpassword">Password</label>
                                    <div class="col-sm-9">
                                        <input type="password" required="true" class="form-control" name='dbpassword' id="dbpassword" placeholder="password for the user"></<input>
                                    </div>
                                </div>
                                <div class="alert alert-info" role="alert">
                                    <span class="font-weight-bold">hint
                                    <a data-toggle="collapse" href="#hintdb" role="button" aria-expanded="false">
                                    <i class="fas fa-info-circle"></i>
                                    </a>
                                    </span>
                                    <div class="collapse" id="hintdb">
                                    $ mysql -u root -p<br>
                                    mysql> create database <span class="font-weight-bold" data-toggle="tooltip" title="database name">shafi<sup><i class="far fa-question-circle"></i></sup></span><br>
                                    mysql> create user <span class="font-weight-bold" data-toggle="tooltip" title="username for the database">shafi<sup><i class="far fa-question-circle"></i></sup></span>@'localhost' identified by '<span class="font-weight-bold" data-toggle="tooltip" title="password for the database"><?php echo rand_string(8) ?><sup><i class="far fa-question-circle"></i></sup></span>';<br>
                                    mysql> grant all privileges on shafi.* to 'shafi'@'localhost';<br>
                                    </div>
                                </div>
                                <h3>Admin user</h3>
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label"  for="adminuser">Admin username</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name='adminuser' id="adminuser" placeholder="A username to be the admin of the application (default: shafi)" <?php value('adminuser'); ?>>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label"  for="adminpassword">Password</label>
                                    <div class="col-sm-9">
                                        <input type="password" class="form-control" name='adminpassword' id="adminpassword" placeholder="Password for the admin user (default: 123)"></<input>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label"  for="adminpassword2">Confirm password</label>
                                    <div class="col-sm-9">
                                        <input type="password" class="form-control" name='adminpassword2' id="adminpassword2" placeholder="Re-type the password"></<input>
                                    </div>
                                </div>
                                <h3>Web server information</h3>
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label"  for="servername">Web server URL</label>
                                    <div class="col-sm-9">
                                        <input type="text" required="true" class="form-control" name='servername' id="servername" placeholder="Web server name (e.g. http://my.server.com)" <?php value('servername'); ?>>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label"  for="rooturl">URL path for SHAFI</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name='rooturl' id="rooturl" placeholder="Path after http://servername.com/<url> (default: /)" <?php value('rooturl'); ?>></<input>
                                    </div>
                                </div>
                                <div class="alert alert-warning" role="alert">
                                    <span class="font-weight-bold">hint
                                    <a data-toggle="collapse" href="#hintweb" role="button" aria-expanded="false">
                                    <i class="fas fa-info-circle"></i>
                                    </a>
                                    </span>
                                    <div class="collapse" id="hintweb">
                                    In http://my.server.com/shafiapp, <span class="font-weight-bold">http://my.server.com</span> will be the Web server URL, and <span class="font-weight-bold">/shafiapp</span> will be the URL path.<br>
                                    If you plan to dedicate a server (e.g. shafi.myhosting.com), then you can set <span class="font-weight-bold">http://shafi.myhosting.com</span> as the Web server URL, and <span class="font-weight-bold">/</span> as the path.
                                    </div>
                                </div>                                
                                <h3>Anonymous uploads</h3>
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="enableanonymous" id="enableanonymous" value="yes" <?php echo ($info['enableanonymous']===null?"":"checked"); ?>
                                        <label class="form-check-label" for="enableanonymous">Enable anonymous upload</label>
                                    </div>                  
                                </div>              
                                <h3>File storage</h3>
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label"  for="storageroot">Folder to store files </label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name='storageroot' id="storageroot" placeholder="Needs write permissions (default: ./uploads)" <?php value('storageroot'); ?>></<input>
                                    </div>
                                </div>
                                <div class="alert alert-info" role="alert">
                                    <span class="font-weight-bold">hint
                                    <a data-toggle="collapse" href="#hintfile" role="button" aria-expanded="false">
                                    <i class="fas fa-info-circle"></i>
                                    </a>
                                    </span>
                                    <div class="collapse" id="hintfile">
                                    Make sure that the user that runs the web server has permissions to write in that folder. If you cannot manage to get running with this setting, you can set the following permissions as a work-around (but they are not safe).<br>
                                    $ chmod -R 777 /var/www/html/shafi/uploads
                                    </div>
                                </div>                                
                                <p class="text-center"><button type="submit" class="btn btn-primary btn-lg" id="install" name="install">Generate installation files</button></p>
                            </form>
                            <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
