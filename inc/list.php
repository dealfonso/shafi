<?php

if (! function_exists('esc_html')) {
    function esc_html($t) { return $t; }
}

class DDN_List {

    /*
    This object is used to create arbitrary lists, defined by a list of objects and a list of column definition.

    @param $columns:
        The column definition is an array where the key corredponds to a field of the objects and the value is an array that builds an object 
        with the following structure:
        ['fieldname'] = array (
            'title': the title of the column (a string)
            'function': a user defined function that will be called to get the content of the cell (is called for each object at the corresponding cell)
                prototype: function(
                                $obj, // the object that is being evaluated
                                $desc, // the definition of the column
                                $i_object, // the position of the object
                                $n_objects // the total amount of objects
                            )
            'fmt': a user defined string that will be used with sprintf
        )

        ** 'fmt' defaults to '%s', and the existence of 'function' will override 'fmt'.

        ** The 'fieldname' may exist in the object or be an artificial one. If no function is provided, the fieldname will be obtained from the object;
            so if it does not exist, the list will fail. If you are using a function, the fieldname may be artificial to be able to use the same fieldname
            again.

    @param $pagesize: the size of the page when using pagination (0 means no pagination)

    @param $offsetpages: the page that corresponds to object 0 in the overall number of pages (used to retrieve less objects from the database)

        e.g. if in page 2, will retrieve objects from 20 to 30 from the DB, so the first object in the array will be object #20 in the overall set of objects
                and offset page should be 2.

    */
    public function __construct($objects, $columns, $pagesize= 0, $totalpages = -1, $offsetpages = 0 /** not implemented */) {
        $this->objects = $objects;
        $this->columns = $columns;
        $this->pagesize = $pagesize;
        $this->offsetpages = $offsetpages;
        if (($totalpages < 0) && ($pagesize > 0)) 
            $totalpages = sizeof($objects) / $pagesize;

        $this->totalpages = (int)($totalpages);
        $this->_title_field = array('title');
    }

    public function set_title_field($title_field) {
        $this->_title_field = $title_field;
        if (! is_array($this->_title_field)) $this->_title_field = array($this->_title_field);
    }

    public function object_count() {
        return sizeof($this->objects);
    }

    public function set_objects($objects) {
        $this->objects = $objects;
    }

    public function set_columns($columns) {
        $this->columns = $columns;
    }

    public function __toString() {    
        return $this->render(); 
    }         

    public function render($classes, $extrahtml='') {
        $n_objects = sizeof($this->objects);
        $i_object = 0;

        $tableid = uniqid("table");
        $retval .= "<table id='$tableid' class='ddn-table-smart $classes' $extrahtml>";
        /*
        if ($this->_enable_search) {
            $tableid = uniqid("table");
            $retval .= "<table id='$tableid' class='ddn-table-smart filtrable sortable paginable' $extrahtml>";
        } else
            $retval = "<table class='ddn-table-smart'>";
            */

        $retval .= "
        <thead><tr>";

        foreach ($this->columns as $field => $desc) {
            if (is_array($desc)) {
                if (isset($desc['title'])) $desc = $desc['title'];
                else
                $desc="";
            } else if (!is_string($desc))
                $desc = "";
            $retval .= "<th>" . $desc . "</th>";
        }

        $retval .= "
        </tr>
        </thead>
        <tbody>";

        $i_min = 0;
        $i_max = $n_objects;

        for ($i_current = $i_min; $i_current < $i_max; $i_current++) {
            $obj = $this->objects[$i_current];
            $retval .= "<tr>";
            foreach ($this->columns as $field => $desc) { 
                $value = null;
                if (is_array($desc) && isset($desc['value'])) {
                    if (is_callable($desc['value']))
                        $value = call_user_func_array($desc['value'], array($obj, $desc, $i_object, $n_objects));
                    else 
                        $value = $desc['value'];
                }
                $value_s = $value === null?'':sprintf(' value="%s"', esc_html($value));
                if (is_callable($desc) || isset($desc['function'])) {
                    $res = call_user_func_array(is_callable($desc)?$desc:$desc['function'], array($obj, $desc, $i_object, $n_objects));
                } else if (isset($desc['fmt'])) {
                    $res = sprintf($desc['fmt'], $obj->get_field($field));
                } else if (is_array($desc) && (sizeof($desc) == 1)) {
                    $res = sprintf($desc[0], $obj->get_field($field));
                } else
                    $res = $obj->get_field($field);
                $retval .= "<td" . $value_s . ">" . $res . "</td>";
            }
            $retval .= "</tr>";
            $i_object++;
        }
        $retval .= "</tbody>
        </table>";

        return $retval;
    }
}