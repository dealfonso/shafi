<?php

if ( ! defined( '__SHAFI_FOLDER' ) ) {
    exit; // Exit if accessed directly
}

class SHAFI_Op_DownloadFile extends SHAFI_Op_File {
    protected $op = 'download';

    const PERMS=[ 'download' ];

    public function _do() {
        if ($this->file === null) {
            header("HTTP/1.0 404 Not Found");
            return $this->add_error_message(__('File not found'));
        }
        if ($this->file->is_downloadable()) {
            global $storage_backend;
            return $storage_backend->retrieve($this->file->get_fileinfo());
        } else {
            header("HTTP/1.0 404 Not Found");
            return $this->add_error_message(__('File cannot be downloaded'));
        }
    }
}