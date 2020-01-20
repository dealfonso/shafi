<?php

if (file_exists(__SHAFI_FOLDER . 'config.php'))
    require_once(__SHAFI_FOLDER . 'config.php');

define('_DEBUG', true);

if (!defined('__STORAGE_BASE_FOLDER'))
    define('__STORAGE_BASE_FOLDER', './uploads');

if (!defined('__STORAGE_FILESYSTEM_SECRET'))
    define('__STORAGE_FILESYSTEM_SECRET', 'ao8n47clia3ucwk');

/**
 * Period after which an inactive file (file that has any token expired) expires (and can be deleted)
 */
if (!defined('__GRACE_PERIOD'))
    define('__GRACE_PERIOD', 60*60*24*7);
// define('__GRACE_PERIOD', 0);

/**
 * Default values for the automatic tokens for registered users (e.g. when not requested a token).
 *   Used if not requested any token when uploading a file and also for oneshot renew.
 */
if (!defined('__DEFAULT_EXPIRATION_SECONDS'))
    define('__DEFAULT_EXPIRATION_SECONDS', 60*60*24*7);
if (!defined('__DEFAULT_EXPIRATION_HITS'))
    define('__DEFAULT_EXPIRATION_HITS', null);

if (!defined('__SERVER_NAME'))
    define('__SERVER_NAME', 'http://localhost');

if (!defined('__ROOT_URL'))
    define('__ROOT_URL', '/');

define('__ADMIN_URL', rtrim(__ROOT_URL, '/') . '/admin');
define('__LEGACY_UPLOAD_URL', rtrim(__ROOT_URL, '/') . '/up');
define('__UPLOAD_URL', rtrim(__ROOT_URL, '/') . '/upload');


if (!defined('__ALLOW_INFINITE_TOKENS'))
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


if (!isset($db_servername)) $db_servername = "localhost";
if (!isset($db_username)) $db_username = "shafiuser";
if (!isset($db_password)) $db_password = "vOajLWzJ6MPetpyq";
if (!isset($db_database)) $db_database = "shafi";

require_once(__SHAFI_INC . 'dbcreation.php');
?>
