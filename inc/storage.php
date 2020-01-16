<?php

if ( ! defined( '__SHAFI_FOLDER' ) ) {
    exit; // Exit if accessed directly
}

require_once(__SHAFI_INC . 'mime.php');

class FileInfo extends Object_ROProps {
    const RDONLY = [ 'stid', 'path', 'name', 'meta' ];
    protected $stid = null;
    protected $path = null;
    protected $name = null;
    protected $meta = null;

    public function __construct($stid, $path, $fname, $meta = null) {
        $this->stid = $stid;
        $this->path = $path;
        $this->name = $fname;
        $this->meta = $meta;
    }
}

class StorageBackend {
    /**
     * Gets a local path and stores it in the storage backend
     * @param fname the name of the file
     * @param local_path the local path in which it is temporary stored
     * @return remote_path the remote path of the file (dependent on the storage backend); e.g. a path, s3 bucket, etc. or false (if failed)
     */
    public function store($fname, $local_path) {
        return false;
    }
    /**
     * Gets a remote path and retrieves the file
     * @param fname the name of the file
     * @param remote_path the remote path in which it is stored (according to the storage backend)
     * @return NOTHING: the function should retrieve the file using the proper headers; e.g. not found or not authorized, etc.
     */
    public function retrieve($fileinfo) {
        header("HTTP/1.0 404 Not Found");
        die();
    }
}

class StorageFileSystem extends StorageBackend {
    public function __construct($base_path = '.', $secret = '') {
        $this->base_path = rtrim($base_path, '/');
        $this->secret = $secret;
    }

    public function store($fname, $local_path, $meta = null) {
        $st_fname = md5_file($local_path);
        $stid = md5("$st_fname$meta$this->secret");
        $dest_path = $this->base_path . '/' . $st_fname[0];
        @mkdir($dest_path, 0770, true);
        $dest_path .= '/' . $st_fname;

        $remote_path = $st_fname[0] . '/' . $st_fname;

        // Sometimes the file may not be uploaded (e.g. chunked upload), so we cannot use "move_uploaded_file"
        /* if (is_uploaded_file($local_path)) {
            if (move_uploaded_file($local_path, $dest_path))
                return new FileInfo($stid, $remote_path, $fname, $meta);
        } else {*/

        if (rename($local_path, $dest_path))
            return new FileInfo($stid, $remote_path, $fname, $meta);
    
        return false;
    }

    public function getfilesize($remote_path) {
        $remote_path = $this->base_path . '/' . $remote_path;
        if (!file_exists($remote_path)) 
            return false;
        return filesize($remote_path);
    }

    public function retrieve($fileinfo) {
        $file_path = $this->base_path . '/' . $fileinfo->path;
        if (!file_exists($file_path)) {
            header("HTTP/1.0 404 Not Found");
            die();
        }

        $content_type = mime_content_type($file_path);
        $extension = DDN_mime_to_ext($content_type);
        header("Content-type: $content_type");
        header("Content-Disposition: attachment; filename=\"$fileinfo->name.$extension\"");
        readfile($file_path);
        die();        
    }
}

if ( ! defined( '__STORAGE_FILESYSTEM_SECRET') )
    define('__STORAGE_FILESYSTEM_SECRET', '');

$storage_backend = new StorageFileSystem(__STORAGE_BASE_FOLDER, __STORAGE_FILESYSTEM_SECRET);