<?php

if ( ! defined( '__SHAFI_FOLDER' ) ) {
    exit; // Exit if accessed directly
}

require_once(__SHAFI_INC . 'mime.php');

class FileInfo extends Object_ROProps {
    const RDONLY = [ 'stid', 'path', 'name', 'meta', 'size', 'exists' ];
    protected $stid = null;
    protected $path = null;
    protected $name = null;
    protected $meta = null;
    protected $size = 0;
    protected $exists = false;

    public function mark_existing() {
        $this->exists = true;
    }

    public function __construct($stid, $path, $fname, $size, $meta = null) {
        $this->stid = $stid;
        $this->path = $path;
        $this->name = $fname;
        $this->size = $size;
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

    private $base_path;
    private $secret;

    public function __construct($base_path = '.', $secret = '') {
        $this->base_path = rtrim($base_path, '/');
        $this->secret = $secret;
    }

    public function gen_fileinfo($fname, $local_path, $meta = null) {
        // If the file does not exist or cannot get information about it, return fail
        if (!file_exists($local_path)) 
            return false;

        $filesize = @filesize($local_path);
        if ($filesize === false) 
            return false;
    
        // Get a hash for the file (will be used as the filename)
        $st_fname = md5_file($local_path);

        // Generate a unique identifier for the file
        $stid = md5("$st_fname$meta$this->secret");

        // Prepare path in the remote storage
        $remote_path = $st_fname[0] . '/' . $st_fname;

        $fileinfo = new FileInfo($stid, $remote_path, $fname, $filesize, $meta);

        // Get the actual path for the file in the filesystem
        $dest_path = $this->base_path . '/' . $fileinfo->path;

        // If the file exists and the size is the same, it already exists
        if (file_exists($dest_path)) {
            $remote_filesize = @filesize($dest_path);
            if ($remote_filesize == $filesize)
                $fileinfo->mark_existing();
        }

        return $fileinfo;
    }

    public function store($local_path, $fileinfo) {
        // Check if the file exists
        if (!file_exists($local_path)) 
            return false;

        // Get the actual folder in which the file is going to be stored
        $dest_path = $this->base_path . '/' . $fileinfo->path;
        $dest_folder = dirname($dest_path);

        // Make sure that the folder exists
        if (!file_exists($dest_folder)) {
            if (@mkdir($dest_folder, 0770, true) === false)
                return false;
        }

        // Move the file to the destination
        if (rename($local_path, $dest_path)) {
            $fileinfo->mark_existing();
            return $fileinfo;
        }
        return false;
    }

    public function _store($fname, $local_path, $meta = null) {
        $st_fname = md5_file($local_path);
        $stid = md5("$st_fname$meta$this->secret");
        $dest_path = $this->base_path . '/' . $st_fname[0];
        @mkdir($dest_path, 0770, true);
        $dest_path .= '/' . $st_fname;

        $remote_path = $st_fname[0] . '/' . $st_fname;

        if (!file_exists($local_path)) 
            return false;

        $filesize = @filesize($local_path);
        if ($filesize === false) 
            return false;

        if (rename($local_path, $dest_path))
            return new FileInfo($stid, $remote_path, $fname, $filesize, $meta);
    
        return false;
    }

    public function getfilesize($remote_path) {
        $remote_path = $this->base_path . '/' . $remote_path;
        if (!file_exists($remote_path)) 
            return false;
        return @filesize($remote_path);
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