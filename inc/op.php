<?php

require_once(__SHAFI_INC . 'roprops.php');

class SHAFI_Op extends Object_ROProps {
    const RDONLY=["op", "messages"];
    
    protected $op = null;
    protected $messages = "";

    /**
     * Executes the operation
     * @return object the result of the operation
     */
    public function do() {
        // Do nothing
        if (! $this->_auth()) {
            header("HTTP/1.0 403 Unauthorized");
            include_once("templates/unauthorized.php");
            die();
        }
        return $this->_do();
    }

    // Permisions required to execute this operation
    const PERMS=[];

    protected function _auth() {
        global $acl_manager;
        $class = get_called_class();
        foreach ($class::PERMS as $perm) {
            $raw = false;
            if (substr($perm, 0, 1) === '@') {
                $perm = substr($perm, 1);
                $raw = true;
            }

            $negate = false;
            if (substr($perm, 0, 1) === '!') {
                $perm = substr($perm, 1);
                $negate = true;
            }

            if ($raw)
                $res = $acl_manager->raw_check($perm, $this);
            else
                $res = $acl_manager->check($perm, $this);

            if ($res === $negate) return false;
        }
        return true;
    }

    /**
     * Function that is called in case that one of the profiles required is "owner". The operation MUST decide whether 
     *   the current user is the owner of the object with which it is dealing.
     * @return is_owner true in case that the user should be recognised as "owner"; false in other case.
     */
    protected function is_owner() {
        return false;
    }

    /** Function that carries out with the operation
     * @return result false in case that the operation failed; any other object if the operation suceeded
     */
    protected function _do() {
        return null;
    }

    protected function add_message($message) {
        $this->messages .= $message;
    }

    protected function add_error_message($message, $retval = false) {
        $this->messages .= new DDN_Notice_Error($message);
        return $retval;
    }

    protected function add_warning_message($message, $retval = false) {
        $this->messages .= new DDN_Notice_Warning($message);
        return $retval;
    }

    protected function add_success_message($message, $retval = true) {
        $this->messages .= new DDN_Notice_Success($message);
        return $retval;
    }

    protected function clear_messages() {
        $this->messages = "";
    }
}

?>