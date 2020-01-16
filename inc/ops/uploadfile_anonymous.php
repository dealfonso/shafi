<?php

if ( ! defined( '__SHAFI_FOLDER' ) ) {
    exit; // Exit if accessed directly
}

require_once(__SHAFI_INC . '/ops/uploadfile.php');

class SHAFI_Op_UploadFile_Anonymous extends SHAFI_Op_UploadFile {
    protected $op = 'upload-anonymous';
    const PERMS=[ 'upload-anonymous' ];

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
        $hits = __ANONYMOUS_UPLOAD_DEFAULT_HITS;
        $seconds = __ANONYMOUS_UPLOAD_DEFAULT_SECONDS;
        return [ $hits, $seconds, $passwd ];
    }
}

class SHAFI_Op_UploadFile_Anonymous_Chunked extends SHAFI_Op_UploadFile_Anonymous {
    use SHAFI_Upload_Chunked_Trait;
}