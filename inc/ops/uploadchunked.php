<?php

if ( ! defined( '__SHAFI_FOLDER' ) ) {
    exit; // Exit if accessed directly
}

if ( ! defined( '__PARTIAL_UPLOADS_FOLDER')) {
    define('__PARTIAL_UPLOADS_FOLDER', rtrim(__STORAGE_BASE_FOLDER,'/') . '/parts');
}

class SHAFI_Op_UploadChunked extends SHAFI_Op {

    public static function get_chunk_part_filename($a = null) {
        global $current_user;

        if ($a === null) $a = $_POST;
        $file_id = $a['resumableIdentifier'];
        $chunk_n = $a['resumableChunkNumber'];
        $chunk_total = $a['resumableTotalChunks'];

        $folder = __PARTIAL_UPLOADS_FOLDER . "/" . sanitize_text($current_user->get_username()) . "/" . session_id() . "/" . md5("$chunk_total-$file_id");
        $destfile = "$folder/chunk-$chunk_n.part";
        return $destfile;
    }

    public static function get_filename($file_id, $chunk_total) {
        global $current_user;

        $folder = __PARTIAL_UPLOADS_FOLDER . "/" . sanitize_text($current_user->get_username()) . "/" . session_id() . "/" . md5("$chunk_total-$file_id");
        $destfile = "$folder/file";
        return $destfile;
    }

    protected function create_file($folder, $chunk_total, $expected_size, $fileid, $removeparts = true) {
        $total_size = 0;
        $files = glob("$folder/chunk-*.part");

        if (sizeof($files) != $chunk_total) return false;

        foreach ($files as $file) {    
            $file_size = filesize($file);
            $total_size += $file_size;
        }

        if ($total_size < $expected_size) return false;

        if ($filename === null) 
            $filename = SHAFI_Op_UploadChunked::get_filename($fileid, $chunk_total);

        if (($fp = fopen("$filename", 'w')) !== false) {
            // Resumable.js makes 1-based count (not zero-based)
            for ($i=1; $i <= $chunk_total; $i++)
                fwrite($fp, file_get_contents("$folder/chunk-$i.part"));
            fclose($fp);

            if ($removeparts) {
                foreach ($files as $file)
                    @unlink($file);
            }
        } else
            throw new Exception('failed to create the file');

        return true;
    }

    public function _do() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (keys_in_array(['resumableIdentifier', 'resumableTotalChunks', 'resumableChunkNumber', 'resumableTotalSize', 'resumableFilename'], $_GET)) {
                $destfile = SHAFI_Op_UploadChunked::get_chunk_part_filename($_GET);
                if (file_exists($destfile))
                    header("HTTP/1.0 200 Ok");
                else
                    header("HTTP/1.0 404 Not Found");
                die();    
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_FILES['uploadchunkedfile'])) {
                $file = $_FILES['uploadchunkedfile'];

                // make sure that we have the values that we need
                $error = ! keys_in_array(['resumableIdentifier', 'resumableTotalChunks', 'resumableChunkNumber', 'resumableTotalSize', 'resumableFilename'], $_POST);
                if ($file['error'] != 0) $error = true;

                if ($error === false) {
                    $destfile = SHAFI_Op_UploadChunked::get_chunk_part_filename($_POST);
                    $folder = dirname($destfile);
                    @mkdir($folder, 0770, true);

                    if (!move_uploaded_file($file['tmp_name'], $destfile)) {
                        header("HTTP/1.0 500 Internal Server Error");
                        die();
                    }

                    try {
                        $this->create_file($folder, $_POST['resumableTotalChunks'], $_POST['resumableTotalSize'], $_POST['resumableIdentifier']);
                    } catch (Exception $e) {
                        header("HTTP/1.0 500 Internal Server Error");
                        die();
                    }

                    header("HTTP/1.0 200 OK");
                    die();
                }
            }
        }

        // Otherwise, the request is bad
        header("HTTP/1.0 400 Bad Request");
        die();
    }
}

trait SHAFI_Upload_Chunked_Trait {
    protected function _get_file_struct($id) {
        if (!keys_in_array(['resumableIdentifier', 'resumableTotalChunks', 'resumableTotalSize', 'resumableFilename'], $_POST)) {
            return $this->add_error_message(__("Incomplete request"));
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
