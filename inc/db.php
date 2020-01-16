<?php

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

    protected function __construct($db_servername, $db_username, $db_password, $db_database, $db_tables_prefix = null) {

        // Create connection
        $this->conn = new mysqli($db_servername, $db_username, $db_password, $db_database);    
        $this->is_connected = ! $this->conn->connect_error;
        $this->error = $this->conn->connect_error;
        $this->db_tables_prefix = $db_tables_prefix;
    }

    public function is_connected() { return $this->is_connected; }  

    public function query($query) {
        global $DEBUG;
        if ($this->is_connected()) {
            $DEBUG->debug($query);
            $result = $this->conn->query($query);
            $this->error = null;
            if ($result === false)
                $this->error = $this->conn->error;
            return $result;
        }
        $DEBUG->error("not connected to the db");
        return false;
    }

    protected function _build_values($values) {
        $names_a = array();
        $values_a = array();
        foreach ($values as $k => $v) {
            array_push($names_a, "`$k`");
            if ($v === null)
                array_push($values_a, "NULL");
            else
                if (is_int($v))
                    array_push($values_a, sprintf("%d", $v));    
                else
                    array_push($values_a, sprintf("'%s'", $this->conn->real_escape_string($v)));
        }
        return [$names_a, $values_a]; 
    }

    public function insert($table, $values, $formats = null) {
        list($names_a, $values_a) = $this->_build_values($values);
        $query = "INSERT INTO `$table` (" . implode(',', $names_a) . ') VALUES (' . implode(",", $values_a) . ');';
        $result = $this->query($query);
        if ($result !== false)
            $this->insert_id = $this->conn->insert_id;
        return $result;
    }

    protected function _build_where($where, $join = 'AND') {
        $terms = array();
        foreach ($where as $k => $v) {
            if ($v === null)
                array_push($terms, "`$k` IS NULL");
            else {
                if (is_int($v))
                    array_push($terms, sprintf("`$k` = %d", $v));    
                else
                    array_push($terms, sprintf("`$k` = '%s'", $this->conn->real_escape_string($v)));
            }
        }
        return implode(" $join ", $terms);
    }

    /**
     * Executes one query, but it is only oriented to SELECT queries
     */
    public function get_results($query, $output = OBJECT) {

        // Only accept to return objects (simplification from wpdb class)
        if ($output !== OBJECT) return false;

        $objects = array();
        $result = $this->query($query);

        $num_rows = 0;
        if ($result instanceof mysqli_result) {
            while ( $row = mysqli_fetch_object( $result ) ) {
                $objects[ $num_rows ] = $row;
                $num_rows++;
            }
        }
        return $objects;
    }

    public function delete($table, $where) {
        $query = "DELETE FROM `$table` WHERE " . $this->_build_where($where);
        return $this->query($query);
    }

    public function update($table, $new_values, $where, $formats = null) {
        list($names_a, $values_a) = $this->_build_values($new_values);
        $values = array();
        for ($i = 0; $i < sizeof($names_a); $i++)
            array_push($values, sprintf("%s = %s", $names_a[$i], $values_a[$i]));

        $query = "UPDATE `$table` SET " . implode($values, ' , ') . " WHERE " . $this->_build_where($where);
        return $this->query($query);
    }
}

$wpdb = DB::create_from_config();