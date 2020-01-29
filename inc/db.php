<?php

require_once(__SHAFI_INC . 'roprops.php');

/**
 * This is a simple class that wraps database access for some functions. It tries to be compatible with wordpress wpdb class
 *   in the functions that it implements
 */
class DB extends Object_ROProps {
    const RDONLY=['error'];

    public static function create_from_config() {
        global $db_servername, $db_username, $db_password, $db_database, $db_tables_prefix;
        
        $dbobject = new DB($db_servername, $db_username, $db_password, $db_database, $db_tables_prefix);
        if ($dbobject->is_connected) return $dbobject;
        return null;
    } 

    public $prefix = null;
    protected $is_connected = false;
    protected $error = null;
    protected $conn = null;
    public $insert_id = null;
    protected $_results = null;

    protected function __construct($db_servername, $db_username, $db_password, $db_database, $db_tables_prefix = null) {

        // Create connection
        $this->conn = new mysqli($db_servername, $db_username, $db_password, $db_database);    
        $this->is_connected = ! $this->conn->connect_error;
        $this->error = $this->conn->connect_error;
        $this->prefix = $db_tables_prefix;
    }

    public function is_connected() { return $this->is_connected; }  

    public function query($query) {
        if ($this->is_connected()) {
            $result = $this->conn->query($query);
            $this->_results = $result;

            $this->error = null;
            if ($result === false)
                $this->error = $this->conn->error;

            return $result !== false;
        }
        return false;
    }

    public function p_query($query_str, $types_s, $values_a) {
        if ($this->is_connected()) {

            $stmt = $this->conn->prepare($query_str);
            $this->error = null;

            if ($stmt === false) {
                $this->error = $this->conn->error;
                return false;
            }

            if (sizeof($values_a) > 0)
                $stmt->bind_param($types_s, ...$values_a);

            $result = false;
            if ($stmt->execute() !== false) {
                $result = true;
                $this->_results = $stmt->get_result();
            } else
                $this->error = $this->conn->error;

            $stmt->close();
            return $result;
        }
        return false;
    }

    protected function _type_c($v) {
        if (is_int($v)) return 'd';
        if (is_double($v)) return 'f';
        return 's';
    }

    protected function _prepare_markers($value) {
        $keys_a = array();
        $markers_a = array();
        $values_a = array();
        $types_s = "";
        foreach ($value as $k => $v) {
            array_push($keys_a, $k);
            if ($v === null)
                array_push($markers_a, 'null');
            else {
                array_push($markers_a, '?');
                array_push($values_a, $v);
                $types_s .= $this->_type_c($v);
            }
        }
        return [ $markers_a, $types_s, $values_a, $keys_a ];
    }

    protected function _p_build_where($condition, $conditioncompose) {
        $where_a = array();
        $types_s = "";
        $values_a = array();

        foreach ($condition as $key => $value) {
            $negative = false;
            if ($key[0] === '!') {
                $negative = true;
                $key = substr($key, 1);
            }

            $op = '=';
            $nop = '<>';

            if ($key[0] === '!') {
                $negative = true;
                $key = substr($key, 1);
            }

            if (substr($key, 0, 2) == '<>') {
                $op = '<>'; $nop = '='; $key = substr($key, 2);
            } elseif (substr($key, 0, 2) == '<=') {
                $op = '<='; $nop = '>'; $key = substr($key, 2);
            } elseif (substr($key, 0, 2) == '>=') {
                $op = '>='; $nop = '<'; $key = substr($key, 2);
            } else {
                switch ($key[0]) {
                    case '<':
                        $op = '<'; $nop = '>='; $key = substr($key, 1); break;
                    case '>':
                        $op = '>'; $nop = '<='; $key = substr($key, 1); break;
                    case '=':
                        $op = '='; $nop = '<>'; $key = substr($key, 1); break;
                }
            }

            if ($value === null) {
                if ($negative)
                    array_push( $where_a, "`$key` is not null");
                else 
                    array_push( $where_a, "`$key` is null");
            }
            else {
                if (is_array($value)) {
                    list($array_s, $a_types, $a_values, $keys_a) = $this->_prepare_markers($value);
                    $values_a = array_merge($values_a, $a_values);
                    $types_s .= $a_types;
                    if ($negative)
                        array_push($where_a, "`$key` not in (". implode(', ', $array_s).")");
                    else
                        array_push($where_a, "`$key` in (". implode(', ', $array_s).")");
                } else {
                    if ($negative)
                        array_push($where_a, "`$key` $nop ?");
                    else
                        array_push($where_a, "`$key` $op ?");

                    array_push($values_a, $value);
                    $types_s .= $this->_type_c($value);
                }
            }
        }

        $query_str = "";
        if (sizeof($where_a) > 0)
            $query_str .= " WHERE " . implode(" $conditioncompose ", $where_a);

        return [ $query_str, $types_s, $values_a ];
    }

    public function p_search($table, $condition = array(), $conditioncompose = 'AND', $orderby = null) {
        list($where_s, $types_s, $values_a) = $this->_p_build_where($condition, $conditioncompose);

        $query_str = "SELECT * FROM $table $where_s";

        if ($orderby !== null) {
            if (is_array($orderby))
                $query_str .= ' ORDER BY ' . implode(',', $orderby);
            else
                $query_str .= ' ORDER BY ' . $orderby;
        }
    
        $result = $this->p_query($query_str, $types_s, $values_a);
        if ($result === false) return array();

        $objects = array();
        $num_rows = 0;
        while ($obj = $this->_results->fetch_object()) {
            $objects[ $num_rows ] = $obj;
            $num_rows++;
        }
        return $objects;
    }

    public function p_insert($table, $values) {
        list($markers_a, $types_s, $values_a, $fields_a) = $this->_prepare_markers($values);
        $query_str = "INSERT INTO `$table` (" . implode(',', $fields_a) . ") VALUES (" . implode("," , $markers_a) . ");";

        $result = $this->p_query($query_str, $types_s, $values_a);

        // An insert does not return anything but false
        if ($result !== false)
            $this->insert_id = $this->conn->insert_id;

        return $result;
    }

    public function get_results($query = null, $output = OBJECT) {
        // Only accept to return objects (simplification from wpdb class)
        if ($output !== OBJECT) return false;

        $result = null;
        if ($query !== null)
            if ($this->query($query) === false)
                return false;
        
        if ($this->_results === null) return false;
        if ($this->_results === false) return false;

        $objects = array();
        $num_rows = 0;
        while ($obj = $this->_results->fetch_object()) {
            $objects[ $num_rows ] = $obj;
            $num_rows++;
        }
        return $objects;
    }

    public function p_delete($table, $where) {
        list($where_s, $types_s, $values_a) = $this->_p_build_where($where, 'AND');

        $query_str = "DELETE FROM `$table` $where_s";
        
        $result = $this->p_query($query_str, $types_s, $values_a);

        return $result;
    }

    public function p_update($table, $new_values, $where) {
        list($markers_a, $types_s, $values_a, $fields_a) = $this->_prepare_markers($new_values);

        // Prepare the update
        $set_a = array();
        for ($i = 0; $i < sizeof($markers_a); $i++)
            array_push($set_a, sprintf("%s = %s", $fields_a[$i], $markers_a[$i]));

        $query_str = "UPDATE `$table` SET " . implode($set_a, ' , ');

        // Now build where
        list($where_s, $types_w_s, $values_w_a) = $this->_p_build_where($where, 'AND');
        $query_str .= " $where_s";
    
        $types_s .= $types_w_s;
        $values_a = array_merge($values_a, $values_w_a);

        return $this->p_query($query_str, $types_s, $values_a);
    }
}

$wpdb = DB::create_from_config();
