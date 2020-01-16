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

    protected function _grab_uploaded_file($id) {
        $f_struct = $this->_get_file_struct($id);

        if ($f_struct !== false) {
            // Check valid sizes
            if (($f_struct['error'] != 0) || ($f_struct['size'] == 0))
                return $this->add_error_message(__('File has not been properly transmitted to the server'));

            if ((__MAX_FILESIZE>=0) && ($f_struct['size'] > __MAX_FILESIZE))
                return $this->add_error_message(__('File size exceeds limits'));

            if (!file_exists($f_struct['tmp_name']))
                return $this->add_error_message(__('Invalid file'));

            // Use the storage backend to store the file
            global $storage_backend;
            $result = $storage_backend->store($f_struct['name'], $f_struct['tmp_name']);
            if ($result === false)
                return $this->add_error_message(__('Failed to store file in the backend'));

            // Ensure there is a name for the file
            $name=$f_struct['name'] == ""?$fname:$f_struct['name'];

            // Return the path (according to the storage backend), and the name of the file
            return $result;
        }
        return false;
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
            if (($fileinfo = $this->_grab_uploaded_file('fichero')) === false)
                return $this->add_error_message(__('Failed to store the file'));
            else {
                $f = SHAFile::search(['owner' => $current_user->get_username(), 'path' => $fileinfo->path, '!state' => 'd' ]);

                if (sizeof($f) > 0) {
                    $retval = $f[0];
                    $already_exists = true;

                    if ((! $retval->is_active()) && (! $retval->reactivate(true)))
                        return $this->add_error_message(__('Could not reactivate the file'));
                    else
                        $this->add_warning_message(__('File existed and has been reactivated. Please check the tokens that it may have associated.'));
                }
                else {
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

trait SHAFI_Upload_Chunked_Trait {
    protected function _get_file_struct($id) {
        if (!keys_in_array(['resumableIdentifier', 'resumableTotalChunks', 'resumableTotalSize', 'resumableFilename'], $_POST)) {
            return $this->add_error_message("No se han recibido todos los datos necesarios");
        }

        $uploaded_filename = SHAFI_Op_UploadChunked::get_filename($_POST['resumableIdentifier'], $_POST['resumableTotalChunks']);
        if (!file_exists($uploaded_filename)) 
            return $this->add_error_message(__("The server failed to receive the file"));

        $_FILES[$id] = array(
            'size' => filesize($uploaded_filename),
            'error' => 0,
            'tmp_name' => $uploaded_filename,
            'name' => $_POST['resumableFilename'],
            'type' => mime_content_type($uploaded_filename)
        );
        return $_FILES[$id];
    }
}

class SHAFI_Op_UploadFile_Chunked extends SHAFI_Op_UploadFile {
    use SHAFI_Upload_Chunked_Trait;
}