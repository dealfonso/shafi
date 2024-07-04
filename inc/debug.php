<?php

class DebugInfo {
    public $debug;

    public function __construct() {
        $this->debug = array();
    }
    public function debug($message) {
        if (is_string($message))
            array_push($this->debug, "[DEBUG] $message");
        else
            array_push($this->debug, "[DEBUG] " . var_export($message, true));
    }
    public function info($message) {
        array_push($this->debug, "[INFO] $message");
    }
    public function error($message) {
        array_push($this->debug, "[ERROR] $message");
    }
    public function __toString() {
        $result = "<pre>" . implode('<br>', $this->debug) . "</pre>";
        return $result;
    }
}

$DEBUG = new DebugInfo();