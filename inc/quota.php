<?php

if (!defined('__STORAGE_QUOTA_USER'))
    define('__STORAGE_QUOTA_USER', array());

if (!defined('__STORAGE_QUOTA_GROUP'))
    define('__STORAGE_QUOTA_GROUP', array());

if (!defined('__STORAGE_QUOTA_DEFAULT'))
    define('__STORAGE_QUOTA_DEFAULT', 0);

if (!defined('__STORAGE_QUOTA_ANONYMOUS'))
    define('__STORAGE_QUOTA_ANONYMOUS', 0);

if (!defined('__MAX_FILESIZE_DEFAULT'))
    define('__MAX_FILESIZE_DEFAULT', 0);

if (!defined('__MAX_FILESIZE'))
    define('__MAX_FILESIZE', array());


class QuotaManager {
    public function get_user_filessize($user) {
        $username = $user->get_username();

        global $wpdb;
        $result = $wpdb->get_results("SELECT sum(size) as total from files where `owner`='$username' AND `state` not in ('c', 'd')");

        if ($result === false) return false;

        if (sizeof($result) != 1) 
            return false;

        return $result[0]->total;
    }

    public function get_user_quota($user) {
        // Users that are not logged in have a special quota
        if (! $user->is_logged_in()) 
            return __STORAGE_QUOTA_ANONYMOUS;

        // Check the quota, based on the username and the groups to which it belongs. The user will get the maximum amount of those availables
        $username = $user->get_username();

        $quota = __STORAGE_QUOTA_DEFAULT;
        if ((isset(__STORAGE_QUOTA_USER[$username])) && (__STORAGE_QUOTA_USER[$username] > $quota))
            $quota = __STORAGE_QUOTA_USER[$username];

        if ((isset(__STORAGE_QUOTA_GROUP[''])) && ( __STORAGE_QUOTA_GROUP[''] > $quota))
            $quota =  __STORAGE_QUOTA_GROUP[''];

        foreach (__PERMISSIONS as $k => $g) {
            if ((isset(__STORAGE_QUOTA_GROUP[$k])) && ($user->is_a($k)) && (__STORAGE_QUOTA_GROUP[$k] > $quota)) 
                $quota = __STORAGE_QUOTA_GROUP[$k];
        }
        return $quota;
    }

    public function size_available($user) {
        $quota = $this->get_user_quota($user);
        // If the user has no quota, return null
        if ($quota < 0) return null;

        $used = $this->get_user_filessize($user);

        // If I could not get the space used, there is an error
        if ($used === false) return 0;

        // Return the remaining size
        return $quota - $used;
    }

    public function meets_quota($user, $filesize) {
        $sizeavail = $this->size_available($user);

        // User has no quota
        if ($sizeavail === null) return true;

        // File size is less than the size available
        if ($filesize <= $sizeavail) return true;
        return false;
    }

    public function get_max_filesize($user = null) {
        global $current_user;
        if ($user === null) $user = $current_user;
    
        // Check the quota, based on the username and the groups to which it belongs. The user will get the maximum amount of those availables
        $username = $user->get_username();

        $maxfilesize = __MAX_FILESIZE_DEFAULT;

        if ((isset(__MAX_FILESIZE[''])) && ( __MAX_FILESIZE[''] > $maxfilesize))
            $maxfilesize =  __MAX_FILESIZE[''];
            
        foreach (__PERMISSIONS as $k => $g) {
            if ((isset(__MAX_FILESIZE[$k])) && ($user->is_a($k)) && (__MAX_FILESIZE[$k] > $maxfilesize)) 
                $maxfilesize = __MAX_FILESIZE[$k];
        }
        return $maxfilesize;
    }
};

// This is a variable for future improvement (e.g. adding a table in which quotas can be modified)
$quota_manager = new QuotaManager();