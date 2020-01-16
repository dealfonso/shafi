<?php
class DDN_PageComm {
    const COOKIE_NAME = 'page-comm';

    protected $messages = array();
    protected $messages_cookie = array();
    protected $free_type = false;

    public function __construct($free_type = false) {
        $this->free_type = $free_type;
        if (isset($_COOKIE[DDN_PageComm::COOKIE_NAME])) {
            $this->retrieve_messages();
            $this->clear_cookie();
        }
    }

    public function get_messages($ddn_notices = false) {
        if (! $ddn_notices)
            return $this->messages;

        $notices = array();
        foreach ($this->messages as $message) 
            array_push($notices, new DDN_Notice($message['text'], $message['type'], false));
            
        return $notices;
    }

    public function clear_cookie() {
        setcookie(DDN_PageComm::COOKIE_NAME, "", time() - 3600);
    }

    public function add_message($type, $message) {
        // Only some types are allowed
        switch ($type) {
            case 'error':
            case 'info':
            case 'success':
            case 'warning': break;
            default: 
                if (! $this->free_type)
                    return false; 
                break;
        }
        $message = base64_encode($message);
        array_push($this->messages_cookie, "$type:$message");
        setcookie(DDN_PageComm::COOKIE_NAME, implode(';', $this->messages_cookie), time() + 60);
    }

    protected function retrieve_messages() {
        // Clear the messages
        $this->messages = array();

        // Get the messages in an array
        $message_set = $_COOKIE[DDN_PageComm::COOKIE_NAME];
        $message_enc = explode(';', $message_set);

        foreach ($message_enc as $message_enc) {

            // Each message is in the form type:base64(txt)
            $message_parts = explode(':', $message_enc);

            // If the format is not valid, skip
            if (count($message_parts) != 2) continue;

            // If the type is valid, decode the message and store
            $message_type = $message_parts[0];
            switch ($message_type) {
                case 'error':
                case 'info':
                case 'success':
                case 'warning': 
                    break;        
                default:
                    if (! $this->free_type )
                        $message_type = null;
                    break;
            }

            if ($message_type !== null) {
                $message_dec = base64_decode($message_parts[1]);
                array_push($this->messages, ['type'=>$message_type, 'text'=>$message_dec]); 
            }
        }
    }
}
$pagecomm = new DDN_PageComm();