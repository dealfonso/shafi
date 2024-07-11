<?php

if ( ! defined( '__SHAFI_FOLDER' ) ) {
    exit; // Exit if accessed directly
}

class SHAFI_Op_Get extends SHAFI_Op {
    protected $op = 'get';
    public $needs_password = false;
    public $correct_password = null;
    public $token_id = null;
    public $show_info = true;
    public $file = null;

    public function _do() {
        global $current_user;
        $this->clear_messages();

        $tid = $_GET['f'];
        $token_id = $tid;

        if ($tid == '')
            return false;

        $tokens = SHAToken::search([ 'oid' => $tid, '!state' => 'd' ]);
        if (sizeof($tokens) == 0) {
            header("HTTP/1.0 404 Not Found");
            return $this->add_error_message(__('Token not found'));
        }

        // Check the expiration of the tokens
        foreach ($tokens as $token)
            $token->expiration_check(true);

        $valid_tokens = array();
        foreach ($tokens as $token)
            if ($token->is_active())
                array_push($valid_tokens, $token);

        // If there were tokens but no valid tokens, the requested token has expired or has been cancelled
        if (sizeof($valid_tokens) == 0) {
            header("HTTP/1.0 410 Gone");
            return $this->add_error_message(__('Token expired'));
        }

        // Multiple valid tokens mean an internal error
        if (sizeof($valid_tokens) > 1) {
            header("HTTP/1.0 500 Internal Server Error");
            return $this->add_error_message(__('Multiple active tokens found'));
        }

        // At this point the unique token is the one that we should retrieve so get the related file
        $token = $valid_tokens[0];
        $file = SHAFile::get($token->get_field('fileid'));

        // If the file is missing notify and return
        if ($file === null) {
            header("HTTP/1.0 410 Gone");
            return $this->add_error_message(__('Missing file'));
        }

        // Update the state of the file
        $file->update_state(true);

        // If the file is not active, notify and return (this should not happen, because any token should be cancelled when a file is cancelled)
        if (! $file->is_active()) {
            header("HTTP/1.0 410 Gone");
            return $this->add_error_message(__('File has been cancelled or has expired'));
        }

        // Now password check
        $this->file = $file;
        $passwd = $token->get_field('password');
        if ($passwd !== null) {
            $this->needs_password = true;
            $this->correct_password = false;
            if (isset($_POST['download'])) {
                if (password_verify($_POST['passwd'], $passwd)) {
                    $this->correct_password = true;
                } else {
                    return $this->add_error_message(__('Invalid password'));
                }
            } else
                return false;
        }

        if ($_GET["d"]??"false" !== "") {
            return true;
        }

        $this->show_info = false;

        // Store the hit count
        // TODO: store stats?
        $token->add_hit(true);

        // Retrieve the file
        global $storage_backend;
        $storage_backend->retrieve($file->get_fileinfo());
        return true;
    }
}    
