<?php

if ( ! defined( '__SHAFI_FOLDER' ) ) {
    exit; // Exit if accessed directly
}

require_once(__SHAFI_INC . '/ops/edit.php');

class SHAFI_Op_UploadFile extends SHAFI_Op_Form {
    protected $op = 'upload';
    const PERMS=[ 'upload' ];

    protected $token_created = null;
    protected $file_uploaded = null;
    const RDONLY = SHAFI_Op_Edit::RDONLY + [ 2 => "file_uploaded", 3 => "token_created" ];

    protected function _get_file_struct($id) {
        if (isset($_FILES[$id]))
            return $_FILES[$id];
        return false;
    }

    protected function _get_valid_file_struct($id) {
        $f_struct = $this->_get_file_struct($id);
        if ($f_struct === false)
            return $this->add_error_message(__('Failed to get the information about the file'));

        if (($f_struct['error'] != 0) || ($f_struct['size'] == 0))
            return $this->add_error_message(__('File has not been properly transmitted to the server'));

        global $quota_manager;
        $max_filesize = $quota_manager->get_max_filesize();
        if (($max_filesize>=0) && ($f_struct['size'] > $max_filesize))
            return $this->add_error_message(sprintf(__('File size (%s) exceeds limits'), human_filesize($f_struct['size'])));

        if (!file_exists($f_struct['tmp_name']))
            return $this->add_error_message(__('Invalid file'));

        return $f_struct;
    }

    protected function _get_sanitized_token_values() {
        $passwd = null;
        $hits = null;
        $seconds = null;
        if (__ANONYMOUS_PASSWORDS) {
            // Get the value for the password (the rest will be ignored)
            $result = $this->_read_hits_and_seconds_from_post();

            if ($result === false) return false;
            [$hits, $seconds, $passwd] = $result;
        } 
        if (($hits === null) && ($seconds === null) && (!__ALLOW_INFINITE_TOKENS)) {
            $this->add_warning_message(__('Infinite tokens are not permitted'));
            $this->add_warning_message(__('A default token has been created'));            
            $hits = __DEFAULT_EXPIRATION_HITS;
            $seconds = __DEFAULT_EXPIRATION_SECONDS;
        }

        return [ $hits, $seconds, $passwd ];
    }
    public function _do() {
        global $current_user;
        global $DEBUG;

        $retval = false;
        $this->clear_messages();

        if (isset($_POST['compartir'])) {
            // Get the values for the expiration times
            $result = $this->_get_sanitized_token_values();

            if ($result === false) 
                return $this->add_error_message(__('Failed to obtain token parameters'));

            [$hits, $seconds, $passwd] = $result;
            
            // Now deal with the file
            $already_exists = false;

            // Get a valid file struct (typicial from PHP _FILE)
            $f_struct = $this->_get_valid_file_struct('fichero');
            if ($f_struct === false)
                return false;

            global $storage_backend;

            // Get the fileinfo structure from the storage backend; if the file already exists (same path and size), 
            //   it will be marked as so
            $fileinfo = $storage_backend->gen_fileinfo($f_struct['name'], $f_struct['tmp_name']);
            if ($fileinfo === false) 
                return $this->add_error_message(__('Failed to get information about the file'));
                
            // Now check for the quota
            $existing_files = SHAFile::search(['owner' => $current_user->get_username(), 'path' => $fileinfo->path, '!state' => 'd' ]);
            if (sizeof($existing_files) > 0) {
                // The file already exists in the system, and is owned by the user
                $retval = $existing_files[0];
                $already_exists = true;

                if (! $fileinfo->exists) {
                    // If, for some reason, the file has been lost, we'll copy it again
                    //   NOTE: this should not happen.
                    if (! $storage_backend->store($f_struct['tmp_name'], $fileinfo)) 
                        return $this->add_error_message(__('Failed to copy the file to the storage backend'));
                }    

                if ((! $retval->is_active()) && (! $retval->reactivate(true)))
                    return $this->add_error_message(__('Could not reactivate the file'));
                else {
                    if ($current_user->is_logged_in())
                        // Anonymous users do not have to know that a file already existed
                        $this->add_warning_message(__('File existed and has been reactivated. Please check the tokens that it may have associated.'));
                }
            } else {

                // Now check the quota to decide whether the file can be uploaded or not
                global $quota_manager;
                if (! $quota_manager->meets_quota($current_user, $fileinfo->size))
                    return $this->add_error_message(__('Quota exceeded'));

                if (! $fileinfo->exists) {
                    // If, for some reason, the file has been lost, we'll copy it again
                    //   NOTE: this should not happen.
                    if (! $storage_backend->store($f_struct['tmp_name'], $fileinfo)) 
                        return $this->add_error_message(__('Failed to copy the file to the storage backend'));
                }    
    
                $n_file = new SHAFile();
                $n_file->set_basic_info($fileinfo, $current_user->get_username());

                if ($n_file->create()) {
                    $retval = $n_file;
                    $this->file_uploaded = $n_file;
                    $this->add_success_message(_s('File %s successfully stored', $fileinfo->name));
                }
                else
                    return $this->add_error_message(__('Failed to update file information in the database'));                
            }

            $token = $retval->create_token($seconds, $hits, $passwd);
            if (! $token->create())
                $this->add_error_message(__('Failed to create token, but the file is properly stored in the system'));
            else
                $this->token_created = $token;
        }    
        return $retval;
    }
}

class SHAFI_Op_UploadFile_Chunked extends SHAFI_Op_UploadFile {
    use SHAFI_Upload_Chunked_Trait;
}