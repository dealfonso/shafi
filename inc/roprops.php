<?php

if ( ! defined( '__SHAFI_FOLDER' ) ) {
    exit; // Exit if accessed directly
}

class Object_ROProps {
    const RDONLY = array();

    public function __get($field) {
        $class = get_called_class();
        foreach ($class::RDONLY as $rdprop)
            if ($rdprop === $field)
                return $this->{$field};
        throw new Exception('Invalid property: ' . $field);
    }
}