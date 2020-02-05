<?php
if ( ! defined( '__SHAFI_FOLDER' ) ) {
    exit; // Exit if accessed directly
}

require_once(__SHAFI_INC . 'dbobject.php');

class SHAFI_User extends Object_ROProps {
    protected $username = "anonymous";
    protected $authorized = false;
    const RDONLY = ['username', 'authorized'];

    public function __construct() {
        // This method should obtain the username and other properties according to the plugin method (e.g. UPV's SSO or session user)
        $this->username = 'caralla';
        $this->authorized = true;
    }
}

$__LEGACY_PERMISSIONS = [
    'l' => __('authorized'), // Special permissions
    'o' => __('owner'), // Special permissions
    'u' => __('user'), 
    'a' => __('admin') // Custom groups
];

if (! isset($__CUSTOM_GROUPS))
    $__CUSTOM_GROUPS = [];

define('__PERMISSIONS', $__LEGACY_PERMISSIONS + $__CUSTOM_GROUPS);

class SHAUser extends SCPM_DBObject {
    protected static $db_tablename = 'users';

    // We won't store any other field, just to ease legal issues
    const FIELDS = [
        'username',
        'password',
        'permissions'
    ];

    protected $username = null;
    protected $password = null;
    protected $permissions = null;
    protected $loggedin = false;

    public function is_logged_in() {
        return $this->get_id() !== null;
    }

    public function __construct($id = null) {
        parent::__construct('user', $id);
    }

    public function get_username() {
        if ($this->username === null) return "anonymous";
        return $this->username;
    }

    public function is_a($c) {
        return strpos($this->permissions, $c) !== false;
    }

    public function is_user() {
        return strpos($this->permissions, 'u') !== false;
    }

    public function is_admin() {
        return strpos($this->permissions, 'a') !== false;
    }

    public function check_password($password) {
        if ($this->password === null) 
            return false;
        return password_verify($password, $this->password);
    }

    public function set_password($password, $autosave = false) {
        if ($password === null)
            $this->password = null;
        else
            $this->password = password_hash($password, PASSWORD_BCRYPT);

        if ($autosave === true) 
            return $this->save_i(['password']);
            
        return true;
    }        
}

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    $current_user = SHAUser::search([ 'username' => $_SESSION['username']]);
    if (sizeof($current_user) !== 1)
        $current_user = new SHAUser();        
    else
        $current_user = $current_user[0];
} else {
    $current_user = new SHAUser();    
}

class ACLManager {

    protected $_perms = null;

    public function __construct() {
        $this->_perms = array();
    }

    /**
     * This function should check whether $req is a valid group or not
     */
    public static function valid_req($req) {
        return isset(__PERMISSIONS[$req]);
    }

    /**
     * Function that gets a valid entry array from a entry string
     */
    protected static function valid_entry($entry) {

        if (!is_string($entry)) throw new Exception("Invalid ACL entry: $entry");

        $negative_entries = explode('!', $entry);

        if ($first_negative = ($negative_entries[0] === ''))
            array_shift($negative_entries);

        $valid_entry = array();
        $i = 0;
        foreach ($negative_entries as $negative_entry) {
            $group_a = str_split($negative_entry);
            foreach ($group_a as $a)
                if (!ACLManager::valid_req($a))
                    throw new Exception("$a is not a valid requirement in entry $entry");

            if (($i > 0) || ($first_negative))
                $group_a[0] = "!" . $group_a[0];

            $valid_entry = array_merge($valid_entry, $group_a);
            $i++;
        }

        return $valid_entry;
    }

    public function set_perm($perm, $entry) {
        $this->_perms[$perm] = ACLManager::valid_entry($entry);
    }

    public function clear_perm($perm) {
        $this->_perms[$perm] = array();
    }

    public function add_perm_acl($perm, $entry) {
        if (!isset($this->_perms[$perm])) 
            $this->_perms[$perm] = array();

        array_push($this->_perms[$perm], ACLManager::valid_entry($entry));
    }

    /**
     * Evaluates an authorization entry. Every item in the entry MUST be met.
     * 
     *  - true: accept always
     *  - false: reject always
     *  - l: accept if is logged in
     *  - u: accept if is a user
     *  - a: accept if is an admin
     *  - o: accept if is the owner (as defined in the op by the call to is_owner)
     *  - !l: accept if is not logged in
     *  - !u: accept if is not an user
     *  - !a: accept if is not an admin
     *  - !o: accept if is not the owner
     * 
     * e.g.
     *   - _eval_auth_entry(['u', 'o']) means "if is a registered user and he is the owner"
     *   - _eval_auth_entry(['a']) means "if is an admin"
     * 
     * @return met true if all the requested profiles are met (an empty entry evaluates to 
     *              "true"); false if any of them fails).
     */
    private function _eval_auth_entry($entry, $object = null) {
        if (!is_array($entry)) $entry = [ $entry ];

        global $current_user;
        foreach ($entry as $e) {
            if ($e === false) return false;
            if ($e === true) continue;
            switch ($e) {
                case '!l': if ($current_user->is_logged_in() === true) return false; break;
                case 'l': if ($current_user->is_logged_in() === false) return false; break;
                case '!o': 
                    if ($object === null) throw new Exception('owner permission required but no object is provided');
                    if ($object->is_owner() === true) return false; break;
                case 'o': 
                    if ($object === null) throw new Exception('owner permission required but no object is provided');
                    if ($object->is_owner() === false) return false; break;
                default:
                    $negate = substr($e, 0, 1) === '!';
                    if ($negate)
                        $e = substr($e, 1);

                    $result = $current_user->is_a($e);
                    if ($result === $negate) return false;

                    break;
            }
        }
        return true;
    }

    public function check($perm, $object = null) {
        if (!isset($this->_perms[$perm])) return false;
        foreach ($this->_perms[$perm] as $req) {
            if ($this->_eval_auth_entry($req, $object)) {
                return true;
            }
        }
        return false;
    }
    public function raw_check($req, $object = null) {
        if ($this->_eval_auth_entry(ACLManager::valid_entry($req), $object))
            return true;
        return false;
    }
}

$acl_manager = new ACLManager();
