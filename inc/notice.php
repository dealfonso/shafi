<?php

class DDN_Notice {
    public function __construct($message, $type = "info", $dismissible = true) {
        $this->message = $message;
        $this->type = $type;
        $this->dismissible = $dismissible;
    }

    public function __toString() {
        return $this->render();
    }

    public function render() {
        $class = "";
        switch ($this->type) {
            case 'error': $class = "alert-error"; break;
            case 'warning': $class = "alert-warning"; break;
            case 'info': $class = "alert-info"; break;
            case 'success': $class = "alert-success"; 
                            if ($this->dismissible) $class .= " autohide";
                            break;
        }

        $result = "<div class='alert $class'>";
        if ($this->dismissible)
            $result .= "<span class='closebtn' onclick='this.parentElement.style.display=\"none\";'>&times;</span>";
        $result .= "$this->message</div>";
        return $result;
    }
}    

if ( ! class_exists('DDN_Notice_Error')) {
    class DDN_Notice_Error extends DDN_Notice {
        public function __construct($message, $dismissible = false) {
            parent::__construct($message, 'error', $dismissible);
        }
    }
}

if ( ! class_exists('DDN_Notice_Warning')) {
    class DDN_Notice_Warning extends DDN_Notice {
        public function __construct($message, $dismissible = false) {
            parent::__construct($message, 'warning', $dismissible);
        }
    }
}

if ( ! class_exists('DDN_Notice_Info')) {
    class DDN_Notice_Info extends DDN_Notice {
        public function __construct($message, $dismissible = false) {
            parent::__construct($message, 'info', $dismissible);
        }
    }
}

if ( ! class_exists('DDN_Notice_Success')) {
    class DDN_Notice_Success extends DDN_Notice {
        public function __construct($message, $text = null, $autourl = -1, $dismissible = true) {
            parent::__construct($message, 'success', $dismissible);
        }
    }
}