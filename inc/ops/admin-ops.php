<?php

if ( ! defined( '__SHAFI_FOLDER' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class to manage LOGIN operation. This class may be specialized to link to any authentication system
 */
class SHAFI_Op_Login extends SHAFI_Op {
    protected $op = 'login';
    const PERMS=[ 'login' ];

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

            session_regenerate_id();
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $_POST['username'];
            header(sprintf('Location: %s', add_query_var(['op' => null, 'id' => null], get_root_url())));
            die();            
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

        if (isset($_POST['create'])) {
            $user = SHAUser::search(['username' => $_POST['username']]);
            if (sizeof($user) > 0)
                return $this->add_error_message(__('User already exists'));
            if ($_POST['password'] !== "") {
                if ($_POST['password'] !== $_POST['passwordm']) 
                    return $this->add_error_message(__('Passwords do not match'));
            } 
            else 
                $_POST['password'] = null;
            
            $newuser = new SHAUser();
            $newuser->set_field('username', $_POST['username']);
            $newuser->set_password($_POST['password'], false);
            if (isset($_POST['admin']))
                $newuser->set_field('permissions', 'ua');
            else
                $newuser->set_field('permissions', 'u');

            if (! $newuser->create()) 
                return $this->add_error_message(__('Failed to create user'));

            return $this->add_success_message(__('User has been successfully created'));
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
