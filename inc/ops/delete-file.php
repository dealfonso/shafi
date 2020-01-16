<?php

if ( ! defined( '__SHAFI_FOLDER' ) ) {
    exit; // Exit if accessed directly
}

class SHAFI_Op_DeleteFile extends SHAFI_Op_File {
    protected $op = 'delete-file';
    const PERMS=[ 'delete-file' ];

    public function _do() {
        if ($this->file === null)
            return $this->add_error_message(__('Invalid file'));

        $tokens = $this->file->get_active_tokens();

        $failed = 0;
        foreach ($tokens as $token)
            if ($token->cancel(true) !== true) $failed++;

        if ($failed > 0)
            return $this->add_error_message(_s('Some tokens (%d) could not be cancelled', $failed));

        if (!$this->file->cancel(true))
            return $this->add_error_message(__('File could not be cancelled, but its token have been cancelled'));

        global $pagecomm;
        $pagecomm->add_message('success', _s('File %s successfully cancelled', htmlspecialchars($this->file->get_field('name'))));

        header("Location: " . add_query_var(['op' => 'edit', 'id' => $this->file->get_id()], '/'), true, 301);        
        die();
    }
}