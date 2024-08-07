<?php

if (file_exists(__SHAFI_FOLDER . 'config.php'))
    require_once(__SHAFI_FOLDER . 'config.php');

if (!defined('__STORAGE_BASE_FOLDER'))
    define('__STORAGE_BASE_FOLDER', './uploads');

if (!defined('__STORAGE_FILESYSTEM_SECRET'))
    define('__STORAGE_FILESYSTEM_SECRET', 'ao8n47clia3ucwk');

/**
 * Integrate with Google Auth
 */
if (!defined('__GOOGLE_CLIENT_ID'))
    define('__GOOGLE_CLIENT_ID', 'INSERT-YOUR-GOOGLE-CLIENT-ID-HERE');
if (!defined('__GOOGLE_CLIENT_SECRET'))
    define('__GOOGLE_CLIENT_SECRET', 'INSERT-YOUR-GOOGLE-CLIENT-SECRET-HERE');
if (!defined('__GOOGLE_REDIRECT_URI'))
    define('__GOOGLE_REDIRECT_URI', 'http://localhost:8000/auth-google');
if (!defined('__GOOGLE_OAUTH_VERSION'))
    define('__GOOGLE_OAUTH_VERSION', 'v3');

/**
 * The number of requests that the background will be kept the same (i.e. after this number, a new BG will be generated)
 */
if (!defined('__SHAFI_BG_REFRESH_RATE'))
    define('__SHAFI_BG_REFRESH_RATE', 5);

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

/**
 * Files sizes and quotas
 */

// Max file size. Have in mind that it must be compatible with "upload_max_filesize" in php.ini
if (! defined('__MAX_FILESIZE'))
    define('__MAX_FILESIZE', array(
        '' => 1*1024*1024,
        'u' => 10*1024*1024,
        'a' => 100*1024*1024
    ));

if (! defined('__STORAGE_QUOTA_GROUP'))
    define('__STORAGE_QUOTA_GROUP', array(
        'u' => 10*1024*1024,
        'a' => 100*1024*1024
    ));

if (! defined('__STORAGE_QUOTA_ANONYMOUS'))
    define('__STORAGE_QUOTA_ANONYMOUS', 10 * 1024 * 1024);

/**
 * Anonymous uploads: this kind of users do not need to provide any credentials; somehow the free part of wetransfer.
 *   - the size will be limited by using quotas
 */
// Enable anonymous upload or not
if (! defined('__ANONYMOUS_UPLOAD'))
    define('__ANONYMOUS_UPLOAD', true);
// Anonymous links will expire in 1 week
if (! defined('__ANONYMOUS_UPLOAD_DEFAULT_SECONDS'))
    define('__ANONYMOUS_UPLOAD_DEFAULT_SECONDS', 60*60*24*7); 
// No expiration with hits. Maybe we want to protect the system, setting it to a reasonable amount of times (let's say) 100.
if (! defined('__ANONYMOUS_UPLOAD_DEFAULT_HITS'))
    define('__ANONYMOUS_UPLOAD_DEFAULT_HITS', null); 
// Allow anonymous passwords (if changed, passwords will be kept)
if (! defined('__ANONYMOUS_PASSWORDS'))
    define('__ANONYMOUS_PASSWORDS', false);

if (!isset($db_servername)) $db_servername = "localhost";
if (!isset($db_username)) $db_username = "shafiuser";
if (!isset($db_password)) $db_password = "vOajLWzJ6MPetpyq";
if (!isset($db_database)) $db_database = "shafi";


if (! defined('__TOKEN_GENERATOR_FUNCTION'))
    define('__TOKEN_GENERATOR_FUNCTION', 'UUID::v4');

require_once(__SHAFI_INC . 'dbcreation.php');
?>
