<?php

if ( ! defined( '__SHAFI_FOLDER' ) ) {
    exit; // Exit if accessed directly
}

require_once(__SHAFI_INC . 'ops.php');

/**
 * Class to manage LOGIN operation. This class may be specialized to link to any authentication system
 */
class SHAFI_Op_Login extends SHAFI_Op {
    protected $op = 'login';
    const PERMS=[ 'login' ];

    protected function _login($username, $url = null) {
        session_regenerate_id();
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;
        if ($url === null)
            $url = add_query_var(['op' => null, 'id' => null], get_root_url());
        header(sprintf('Location: %s', $url));
        die();            
    }

    public function _do() {
        global $current_user;
        $this->clear_messages();

        if ($current_user->is_logged_in())
            return $this->add_error_message(__("User is already logged in"));

        if (isset($_POST['login'])) {
            $user = SHAUser::search(['username' => $_POST['username']]);
            if (sizeof($user) == 0)
                return $this->add_error_message(__('User does not exist'));
                
            $user = $user[0];
            if ( ! $user->check_password($_POST['password'])) 
                return $this->add_error_message(__('Invalid password'));

            return $this->_login($_POST['username']);
        }
        return false;
    }
}

class SHAFI_Op_Logout extends SHAFI_Op {
    protected $op = 'logout';
    const PERMS=[ 'logout' ];

    public function _do() {
        global $current_user;
        $this->clear_messages();

        if (! $current_user->is_logged_in())
            return $this->add_error_message(__('User is not authenticated'));

        session_destroy();
        $_SESSION['loggedin'] = false;
        $_SESSION['username'] = null;
        header(sprintf('Location: %s', add_query_var(['op' => null, 'id' => null], get_root_url())));
        die();            
    }
}

class SHAFI_Op_Setpass extends SHAFI_Op {
    protected $op = 'setpass';
    const PERMS=[ 'user' ];

    public function _do() {
        global $current_user;
        $this->clear_messages();

        if (isset($_POST['setpass'])) {
            if ($_POST['password'] == '')
                return $this->add_error_message(__('No empty passwords are allowed'));

            if ($_POST['password'] !== $_POST['passwordm'])
                return $this->add_error_message(__('Passwords do not match'));
            global $current_user;
            if ($current_user->set_password($_POST['password'], true) === null) 
                return $this->add_error_message(__('Failed to update password'));

            return $this->add_success_message(__('Password successfully updated'));
        }
    }
}

class SHAFI_Op_Users extends SHAFI_Op {
    protected $op = 'users';
    const PERMS = [ 'manage-users' ];

    public function _do() {
        global $current_user;
        $this->clear_messages();

        if (isset($_POST['userop']) && ($_POST['userop'] === 'create')) {
            $user = SHAUser::search(['username' => $_POST['username']]);
            if (sizeof($user) > 0)
                return $this->add_error_message(__('User already exists'));
            if ($_POST['password'] !== "") {
                if ($_POST['password'] !== $_POST['passwordm']) 
                    return $this->add_error_message(__('Passwords do not match'));
            } 
            else 
                $_POST['password'] = null;

            $perm_s = "";
            if (isset($_POST['perm'])) {
                if (!is_array($_POST['perm'])) $_POST['perm'] = [ $_POST['perm']];
                foreach ($_POST['perm'] as $p) {
                    if (!isset(__PERMISSIONS[$p]))
                        return $this->add_error_message(__('Invalid group'));
                }
                $perm_s = implode('', $_POST['perm']);
            }

            $newuser = new SHAUser();
            $newuser->set_field('username', $_POST['username']);
            $newuser->set_password($_POST['password'], false);
            $newuser->set_field('permissions', $perm_s);

            if (! $newuser->create()) 
                return $this->add_error_message(__('Failed to create user'));

            return $this->add_success_message(__('User has been successfully created'));
        }
        if (isset($_POST['userop']) && ($_POST['userop'] === 'update')) {
            $user = SHAUser::search(['username' => $_POST['username']]);
            if (sizeof($user) !== 1)
                return $this->add_error_message(__('User does not exist'));
            if ($_POST['password'] !== "") {
                if ($_POST['password'] !== $_POST['passwordm']) 
                    return $this->add_error_message(__('Passwords do not match'));
            } 
            else 
                $_POST['password'] = null;

            $user = $user[0];
            $perm_s = "";
            if (isset($_POST['perm'])) {
                if (!is_array($_POST['perm'])) $_POST['perm'] = [ $_POST['perm']];
                foreach ($_POST['perm'] as $p) {
                    if (!isset(__PERMISSIONS[$p]))
                        return $this->add_error_message(__('Invalid group'));
                }
                $perm_s = implode('', $_POST['perm']);
            }

            if ($_POST['password'] !== null)
                if (! $user->set_password($_POST['password'], true))
                    return $this->add_error_message(__('Failed to update user'));

            $user->set_field('permissions', $perm_s);
            if (! $user->save_i(['permissions'])) 
                return $this->add_error_message(__('Failed to update user'));

            return $this->add_success_message(__('User has been successfully updated'));
        }
        if (isset($_POST['deluser'])) {
            $user = SHAUser::search(['username' => $_POST['username']]);
            if (sizeof($user) !== 1)                 
                return $this->add_error_message(__('User does not exist'));

            $user = $user[0];
            if (! $user->delete()) 
                return $this->add_error_message(__('Failed to delete user'));

            return $this->add_success_message(_s('User %s successfully deleted', $_POST['username']));
        }
        return false;
    }
}
