<?php

define('_DEBUG', true);
define('__STORAGE_BASE_FOLDER', './uploads');
define('__STORAGE_FILESYSTEM_SECRET', 'ao8n47clia3ucwk');

/**
 * Period after which an inactive file (file that has any token expired) expires (and can be deleted)
 */
define('__GRACE_PERIOD', 60*60*24*7);
// define('__GRACE_PERIOD', 0);

/**
 * Default values for the automatic tokens for registered users (e.g. when not requested a token).
 *   Used if not requested any token when uploading a file and also for oneshot renew.
 */
define('__DEFAULT_EXPIRATION_SECONDS', 60*60*24*7);
define('__DEFAULT_EXPIRATION_HITS', null);

define('__SERVER_NAME', 'http://localhost');
define('__ROOT_FOLDER', '/');
define('__ALLOW_INFINITE_TOKENS', true);

// Max file size. Have in mind that it must be compatible with "upload_max_filesize" in php.ini
define('__MAX_FILESIZE', 10*1024*1024);

/**
 * Anonymous uploads: this kind of users do not need to provide any credentials; somehow the free part of wetransfer.
 *   - the size will be limited by using quotas
 */
// Enable anonymous upload or not
define('__ANONYMOUS_UPLOAD', true);
// Anonymous links will expire in 1 week
define('__ANONYMOUS_UPLOAD_DEFAULT_SECONDS', 60*60*24*7); 
// No expiration with hits. Maybe we want to protect the system, setting it to a reasonable amount of times (let's say) 100.
define('__ANONYMOUS_UPLOAD_DEFAULT_HITS', null); 
// Allow anonymous passwords (if changed, passwords will be kept)
define('__ANONYMOUS_PASSWORDS', true);

$db_servername = "localhost";
$db_username = "shafiuser";
$db_password = "vOajLWzJ6MPetpyq";
$db_database = "shafi";

$db_tables_prefix = '';
$db_files_table_name = $db_tables_prefix . 'files';
$db_token_table_name = $db_tables_prefix . 'tokens';
$db_users_table_name = $db_tables_prefix . 'users';
$db_log_table_name = $db_tables_prefix . 'log';

if (!defined('_DEBUG'))
    define('_DEBUG', false);

function create_db() {
    global $wpdb;
    global $db_files_table_name;
    global $db_token_table_name;
    global $db_users_table_name;

    // oid is the ID used to reference the file. It is unique so it could be used as ID; the problem is that
    $sql = <<<EOT
    CREATE TABLE $db_files_table_name (
        id varchar(36) NOT NULL,
        owner text NOT NULL,
        time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        expired datetime DEFAULT NULL,
        name text NOT NULL,
        path text NOT NULL,
        state VARCHAR(1) DEFAULT 'a' NOT NULL,
        stid VARCHAR(36) NOT NULL,
        PRIMARY KEY (id)
    );           
EOT;    

    $result = $wpdb->query($sql);
    if ($result === false) {
        echo __('failed to create database');
        die();
    }

    // Using id because it is easier to manage with my classes; token is a uuidv4 (32bytes + 4 hypens 8-4-4-4-12)
    $sql = <<<EOT
    CREATE TABLE $db_token_table_name (
        id bigint NOT NULL AUTO_INCREMENT,
        oid varchar(36) NOT NULL,
        password varchar(255) DEFAULT NULL,
        time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        expired datetime DEFAULT NULL,
        exp_secs int DEFAULT NULL,
        exp_hits int DEFAULT NULL,
        hits int DEFAULT 0 NOT NULL,
        fileid varchar(36) NOT NULL,
        state VARCHAR(1) DEFAULT 'a' NOT NULL,
        PRIMARY KEY (id)
    );
EOT;

    $result = $wpdb->query($sql);
    if ($result === false) {
        echo __('failed to create database' . $wpdb->error);
        die();
    }

    $sql = <<<EOT
    CREATE TABLE $db_users_table_name (
        id bigint NOT NULL AUTO_INCREMENT,
        username varchar(32) NOT NULL,
        password varchar(255) DEFAULT NULL,
        permissions varchar(5) DEFAULT 'u',
        PRIMARY KEY (id), UNIQUE (username)
    );           
EOT;    

    $result = $wpdb->query($sql);
    if ($result === false) {
        pre_var_dump($wpdb->error);
        echo __('failed to create database');
        die();
    }
    
    // Create the first user
    $firstuser = new SHAUser();
    $firstuser->set_field('username', 'shafi');
    $firstuser->set_field('permissions', 'ua');
    $firstuser->set_password('123', true);
}
?>