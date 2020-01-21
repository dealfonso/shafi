<?php

require_once(__SHAFI_INC . 'pagecomm.php');
require_once(__SHAFI_INC . 'op.php');

/**
 * This is a helper class that includes information for reading token-data from POST
 * @return data false if an error occurred or [hist, seconds, password]
 */
class SHAFI_Op_Form extends SHAFI_Op {
    protected function _read_hits_and_seconds_from_post() {
        $hits = null;
        $seconds = null;
        $passwd = null;
        global $DEBUG;
        if (isset($_POST['exp_time'])) {
            if ($_POST['hours'] == -1) {
                $seconds = intval($_POST['seconds']);
                if (is_nan($seconds)) 
                    return $this->add_error_message(__('Invalid value for seconds'));

                $multiplier = 1;
                switch ($_POST['units']) {
                    case 's': $multiplier = 1; break;
                    case 'm': $multiplier = 60; break;
                    case 'd': $multiplier = 60*60*24; break;
                    case 'M': $multiplier = 60*60*24*30; break;
                    case 'a': $multiplier = 60*60*24*365; break;
                        break;
                    default:
                        return $this->add_error_message(__('Invalid value for units'));break;
                }
                $seconds = $seconds * $multiplier;
            } else {
                $hours = intval($_POST['hours']);
                if (is_nan($hours)) 
                    return $this->add_error_message(__('Invalid value for hours'));

                $seconds = $hours * 60 * 60;
            }
        }
        if (isset($_POST['exp_hits'])) {
            $hits = (int)$_POST['hitcount'];
            if (is_nan($hits)) 
                return $this->add_error_message(__('Invalid value for hits'));

        }          
        if (isset($_POST['setpasswd']))
            $passwd = $_POST['password'];

        return [$hits, $seconds, $passwd];  
    }
}

/**
 * A helper class that grabs the identifier of a file from the id var in the URL and checks its existence
 */
class SHAFI_Op_File extends SHAFI_Op_Form {
    const RDONLY = SHAFI_Op_Form::RDONLY + [ 2 => "file" ];

    protected $fileid = null;
    protected $file = null;

    public function is_owner() {
        global $current_user;
        if ($this->file === null) return false;

        return $current_user->get_username() === $this->file->get_field('owner');
    }

    public function __construct() {
        $this->fileid = $_GET['id'];
        $this->file = null;

        // The admin users are allowed to deal with deleted files; the other users no because the information is kept for historical purposes
        global $current_user;
        if ($current_user->is_admin())
            $file = SHAFile::search(['id' => $this->fileid ]);
        else
            $file = SHAFile::search(['id' => $this->fileid, '!state' => 'd' ]);

        if (sizeof($file) === 1) {
            $this->file = $file[0];
            
            // Check for expiration of tokens
            $c_tokens = $this->file->get_tokens();
            foreach ($c_tokens as $token)
                $token->expiration_check(true);

            // Now update the state of the file
            $this->file->update_state(true);
        } else {
            // Force a bad request, because someone is trying to tamper or cheat our server
            header("HTTP/1.0 400 Bad Request");
            die();
        }
    }    
}

class SHAFI_Op_Authorized extends SHAFI_Op {
    protected $op = '-';
    const PERMS = [ 'user' ];    
}

class SHAFI_Op_List extends SHAFI_Op {
    protected $op = 'list';
    const PERMS = [ 'list' ];    
}

class SHAFI_Op_ListAll extends SHAFI_Op {
    protected $op = 'list-all';
    const PERMS = [ 'list-all' ];    
}

foreach (glob(__SHAFI_INC . '/ops/*.php') as $filename) {
    require_once $filename;
}
