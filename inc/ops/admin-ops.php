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

class SHAFI_Op_UpdateProfile extends SHAFI_Op {
    protected $op = 'updateprofile';
    const PERMS=[ 'user' ];

    public function _do() {
        global $current_user;
        $this->clear_messages();

        if (isset($_POST['updateprofile'])) {
            if ($_POST['password']??"" !== "") {
                if ($_POST['password'] !== $_POST['passwordm']??"") 
                    return $this->add_error_message(__('Passwords do not match'));

                if ($current_user->set_password($_POST['password'], true) === null) 
                    return $this->add_error_message(__('Failed to update password'));        
            } 
            else 
                $_POST['password'] = null;

            if ($_POST['removepassword']??"" !== "") {
                if (! $current_user->set_password(null, true)) 
                    return $this->add_error_message(__('Failed to remove password'));
            }

            $current_user->set_field('email', $_POST['email']);
            if (! $current_user->save_i(['email'])) 
                return $this->add_error_message(__('Failed to update user'));

            return $this->add_success_message(__('Profile updated successfully'));
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
            $newuser->set_field('email', $_POST['email']);
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

            $user->set_field('email', $_POST['email']);
            $user->set_field('permissions', $perm_s);
            if (! $user->save_i(['permissions', 'email'])) 
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

class SHAFI_Op_Google_Auth extends SHAFI_Op_Login {
    public function google_auth() {
        if (isset($_GET['code']) && !empty($_GET['code'])) {

            // Execute cURL request to retrieve the access token
            $params = [
                'code' => $_GET['code'],
                'client_id' => __GOOGLE_CLIENT_ID,
                'client_secret' => __GOOGLE_CLIENT_SECRET,
                'redirect_uri' => __GOOGLE_REDIRECT_URI,
                'grant_type' => 'authorization_code'
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://accounts.google.com/o/oauth2/token');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            $response = json_decode($response, true);
            // Make sure access token is valid
            if (isset($response['access_token']) && !empty($response['access_token'])) {
                // Execute cURL request to retrieve the user info associated with the Google account
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/' . __GOOGLE_OAUTH_VERSION . '/userinfo');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $response['access_token']]);
                $response = curl_exec($ch);
                curl_close($ch);
                $profile = json_decode($response, true);
                // Make sure the profile data exists
                if (isset($profile['email'])) {
                    return [ true, $profile['email'] ];
                } else {
                    return [ false, __("Could not retrieve profile information! Please try again later!")];
                }
            } else {
                return [ false, __("Invalid access token! Please try again later!") ];
            }
        } else {
            // Define params and redirect to Google Authentication page
            $params = [
                'response_type' => 'code',
                'client_id' => __GOOGLE_CLIENT_ID,
                'redirect_uri' => __GOOGLE_REDIRECT_URI,
                // We only requested the e-mail, but could request more data (see https://developers.google.com/identity/protocols/oauth2/scopes)
                'scope' => 'https://www.googleapis.com/auth/userinfo.email',
                'access_type' => 'offline',
                'prompt' => 'consent'
            ];
            header('Location: https://accounts.google.com/o/oauth2/auth?' . http_build_query($params));
            die();
        }        
    }

    public function _do() {
        global $current_user;
        $this->clear_messages();

        if ($current_user->is_logged_in())
            return $this->add_error_message(__("User is already logged in"));

        [ $success, $email ] = $this->google_auth();

        if ($email !== null) {
            $user = SHAUser::search([ 'email' => $email]);
            if (sizeof($user) !== 1) {
                session_destroy();
                $user = new SHAUser();
                return $this->add_error_message(__("User does not exist"));
            }
            else
                return $this->_login($user[0]->get_field('username'));
        }

        return $this->add_error_message($email);
    }
}