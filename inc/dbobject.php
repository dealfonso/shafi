<?php

if (!defined('OBJECT'))
    define('OBJECT', 1000);


include_once('db.php');

class SCPM_DBObject {
    private $id = null;
    private $type = null;
    private $_id_function = null;

    protected function set_id_function($id_function = null) {
        $this->_id_function = $id_function;
    }
    protected function get_id_function() {
        return $this->_id_function;
    }

    /**
     * @param db_tablename The name of the table that stores the object data
     * @param db_tablename_meta The name of the table that stores the metadata for the object
     * @param db_id The name of the field that contains the ID (to search using ID)
     */
    protected static $db_tablename = null;
    protected static $db_tablename_meta = null;
    protected static $db_id = "id";
    const FIELDS = array();

    public static function get_db_tablename() {
        global $wpdb;

        // Get the specific table names
        $class = get_called_class();
        $prefix = isset($wpdb->prefix)?$wpdb->prefix:"";
        return  $prefix . $class::$db_tablename;
    }

    public static function get_db_tablename_meta() {
        global $wpdb;

        // Get the specific table names
        $class = get_called_class();
        if ($class::$db_tablename_meta === null) return null;
        $prefix = isset($wpdb->prefix)?$wpdb->prefix:"";
        return $prefix . $class::$db_tablename_meta;
    }


    /**
     * The constructor for the class. It is protected to not be able to create the object directly.
     * @param type
     * @param id
     */
    protected function __construct($type, $id = null) {
        // Get the type of the object and the identifier
        $this->id = $id;
        $this->type = $type;
    }

    /**
     * Gets the type of the object (it is a readonly attribute)
     * @return type
     */
    public function get_type() {
        return $this->type;
    }

    /**
     * Gets the id of the object (it is a readonly attribute)
     * @return id
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Sets the id of the object. It can be called only from inside the class to avoid ID changes
     * @return type
     */
    protected function set_id($id) {
        $this->id = $id;
    }

    public static function p_search($condition = array(), $loadmetadata = false, $conditioncompose = 'AND', $orderby = null) {
        global $wpdb;

        $class = get_called_class();
        $db_tablename = self::get_db_tablename();

        $objs = $wpdb->p_search($db_tablename, $condition, $conditioncompose, $orderby);

        $result = array();
        foreach ($objs as $dbobj) {

            // Create the object using the id just in case the other functions do not set the id
            $id = null;
            if (isset($dbobj->id)) $id = $dbobj->id;

            // Instantiate the object from the current class
            $new_obj = new $class($id);

            // Load the data
            if ($new_obj->_initialize_data($dbobj)) {
                if ($loadmetadata)
                    $new_obj->load_metadata();

                array_push($result, $new_obj);
            }
        }

        // Finally return the objects
        return $result;
    }

    /**
     * Searches for objects using an AND condition. It returns effective objects of the calling subclass
     *   whose data has been loaded, by using the function _initialize_data(...)
     * 
     * @param condition Array of fields => value that will compose the WHERE clause (using the condition: default is AND)
     * @param limit Limit the number of objects to query
     * @param skip Skip an amount of object
     * @param loadmetadata Whether the metadata has to be automatically loaded (true) or not (false). It should be avoided if possible in order to get more performance.
     * @param conditioncompose The keyword to compose the condition (default AND)
     */
    public static function search($condition = array(), $limit = 0, $skip = 0, $loadmetadata = false, $conditioncompose = 'AND', $orderby = null) {
        // Prepare the conditions
        $query_cond = "1";
        $query_a = array();

        foreach ($condition as $key => $value) {
            $negative = false;
            if ($key[0] === '!') {
                $negative = true;
                $key = substr($key, 1);
            }
            if ($value === null) {
                if ($negative) {
                    $query_cond .= " $conditioncompose `$key` is not null";
                    array_push($query_a, "`$key` is not null");
                }
                else {
                    $query_cond .= " $conditioncompose `$key` is null";
                    array_push($query_a, "`$key` is null");
                }
            }
            else {

                if (is_array($value)) {
                    $value = array_map(function($v) { 
                        if ($v === null) return 'null';
                        if (is_numeric($v)) return $v;
                        return "'$v'";
                    }, $value);
                    if ($negative) {
                        $query_cond .= " $conditioncompose `$key` not in (". implode(', ', $value).")";
                        array_push($query_a, "`$key` not in (". implode(', ', $value).")");
                    }
                    else {
                        $query_cond .= " $conditioncompose `$key` in (". implode(', ', $value).")";
                        array_push($query_a, "`$key` in (". implode(', ', $value).")");
                    }
                } else {
                    if ($negative) {
                        $query_cond .= " $conditioncompose `$key` <> '$value'";
                        array_push($query_a, "`$key` <> '$value'");
                    }
                    else {
                        $query_cond .= " $conditioncompose `$key` = '$value'";
                        array_push($query_a, "`$key` = '$value'");
                    }
                }
            }
        }

        // TODO: we are keeping both ways of creating the query condition, but it should be only needed this one
        $query_cond = "";
        if (sizeof($query_a) > 0)
            $query_cond = "WHERE " . implode(" $conditioncompose ", $query_a);

        $limit_cond = "";
        if ($limit > 0) {
            $limit_cond = " LIMIT $limit";
            if ($skip > 0) $limit_cond .= ', $skip';
        } else
            if ($skip > 0) $limit_cond = " LIMIT 0, $skip";

        global $wpdb;

        // Get the specific table names
        $class = get_called_class();
        $db_tablename = self::get_db_tablename();

        //
        $orderby_cond = '';
        if ($orderby !== null) {
            if (is_array($orderby))
                $orderby_cond = ' ORDER BY ' . implode(',', $orderby);
            else
                $orderby_cond = ' ORDER BY ' . $orderby;
        }

        //pre_var_dump("SELECT * FROM $db_tablename WHERE $query_cond$limit_cond$orderby_cond");
        // Get the results
        $objs = $wpdb->get_results(
            "SELECT * FROM $db_tablename $query_cond$limit_cond$orderby_cond", OBJECT
        );

        $result = array();
        foreach ($objs as $dbobj) {

            // Create the object using the id just in case the other functions do not set the id
            $id = null;
            if (isset($dbobj->id)) $id = $dbobj->id;

            // Instantiate the object from the current class
            $new_obj = new $class($id);

            // Load the data
            if ($new_obj->_initialize_data($dbobj)) {
                if ($loadmetadata)
                    $new_obj->load_metadata();

                array_push($result, $new_obj);
            }
        }

        // Finally return the objects
        return $result;
    }


    /**
     * Gets an object from the database, using its id
     */
    public static function get($id, $condition = array()) {
        $class = get_called_class();

        // Search using the ID field, and limit to 1
        $results = $class::search(array_merge($condition, array($class::$db_id => "$id")), 1);
        
        // If there are not any result, return null
        if (sizeof($results) != 1) return null;

        // Load the metadata (if any);
        $results[0]->load_metadata();

        // Otherwise return the object
        return $results[0];
    }

    public function load_metadata() {
        global $wpdb;

        // There is no metadata table, so there is no metadata
        $db_tablename_meta = self::get_db_tablename_meta();
        if ($db_tablename_meta === null) return true;

        // Get the metadata from the DB
        $type = $this->get_type();

        $type_str = "";
        if ($type !== null) 
            $type_str = " AND `type` = '$type'";

        $meta = $wpdb->get_results(
            "SELECT `meta_key` as `key`, `meta_value` as `value` FROM $db_tablename_meta WHERE `o_id` = '$id'$type_str", OBJECT
        );

        // Get the metadata into the object
        $this->_initialize_metadata($meta);
        return true;        
    }

    /**
     * Gets the object coming from the database and transform the data to attributes in the object
     * @return correct whether the data has been properly imported or not
     */
    protected function _initialize_data($data) {
        $class = get_called_class();

        $this->_copy_fields($data, $class::FIELDS);

        // Set the ID at last just in case that any other field fails
        $this->set_id($data->id);
        return true;
    }

    /**
     * Gets the metadata array coming from the database and transforms it into attributes in the object
     * @return correct whether the data has been properly imported or not
     */
    protected function _initialize_metadata($meta) {
        return true;
    }

    /**
     * Utility function that copies fields in $data to the $this object
     * @param data The source of data
     * @param fields Array of fields to copy. It may be array('field1', 'field2', ...) that will copy $data->field1 into $this->field1, or array('field1'=>'fielda', ...) that will copy $data->fielda into $this->field1
     */
    protected function _copy_fields($data, $fields) {
        foreach ($fields as $field => $type) {
            if (is_int($field)) {
                $this->{$type} = $data->{$type};
            }
            else {
                $value = $data->{$field};

                // If it is a null, raw copy it; otherwise try to convert the type
                if ($value !== null) {
                    switch ($type) {
                        case 'int': $value = (int)$value; break;
                        case 'bool': $value = (bool)$value; break;
                        case 'datetime': 
                                        $value_n = DateTime::createFromFormat('Y-m-d H:i:s', $value); 
                                        if ($value_n === false) $value_n = DateTime::createFromFormat('Y-m-d H:i', $value);
                                        $value = $value_n;
                                        break;
                        default: break;
                    }
                }
                $this->{$field} = $value;
            }
        }
    }

    protected function _prepare_fields_for_sql($fields) {
        $sql_values = array();
        $sql_formats = array();

        foreach ($fields as $field => $type) {
            if (is_int($field)) {
                $field = $type;
                $type = "string";
            }

            $field_value = $this->{$field};

            if ($field_value === null) {
                $value = null;
                $type = '%s';
            }
            else {
                switch ($type) {
                    case "bool": $value = $field_value==true; $type='%s'; break;
                    case "int": $value = $field_value; $type = '%d'; break;
                    case "datetime": 
                        switch (gettype($field_value)) {
                            case 'object':
                                if (get_class($field_value) == 'DateTime')
                                    $value = $field_value->format('Y-m-d H:i'); break;
                            default:
                                $value = $field_value; break;
                        }
                        $type='%s';
                        break;
                    default: $type='%s'; $value = $field_value; break;
                }
            }
            $sql_values = array_merge($sql_values, array($field => $value));
            array_push($sql_formats, $type);
        }        

        return array($sql_values, $sql_formats);
    }

    protected function _indirect_fields_to_fields($fields) {
        $fields_to_save = array();

        $class = get_called_class();
        $fff_fields = $class::FIELDS;

        foreach ($fields as $field) {
            if (isset($fff_fields[$field])) 
                $fields_to_save[$field] = $fff_fields[$field];
            else {
                if (($pos = array_search($field, $fff_fields)) === false) throw new Exception("Field $field is not in the field list");
                $fields_to_save[$pos] = $fff_fields[$pos];
            }
        }
        return $fields_to_save;
    }

    /**
     * This function takes an array of names of fields and gets the properties from the class::FIELDS array. Then calls the "save"
     *   function. That avoids to duplicate the definition of the fields.
     * 
     * @param fields an array that contains the names of the fields. All these fields must exist in the class::FIELDS array
     * @return id the ID of the object (if succeeded) or null (if failed)
     */
    public function save_i($fields) {
        return $this->save($this->_indirect_fields_to_fields($fields));
    }

    /**
     * This function takes an array of parameters (with the form of ( "field" => "type", "field"); being "string" the default value),
     *   gets the values from the properties of the object, and saves them to the database in the table class::db_tablename.
     * 
     *   If the object does not have an id (using the get_id function), the information is inserted into the database and the new id
     *   is returned. If it has an id, the information is saved using the "update" function.
     * 
     * @param fields the fields to save
     * @return id the id of the object if suceeded, or null if failed
     */
    public function save($fields = null) {
        global $wpdb;

        $sql_values = array();
        $sql_formats = array();
        $class = get_called_class();

        if ($fields === null)
            $fields = $class::FIELDS;

        list($sql_values, $sql_formats) = $this->_prepare_fields_for_sql($fields);

        global $__DDN_DEBUG;
        if ($__DDN_DEBUG) $wpdb->show_errors(); 

        $id = $this->get_id();

        $count = $wpdb->p_update(self::get_db_tablename(), $sql_values, array($class::$db_id => $this->get_id()), $sql_formats);
        if ($count === false) {
            if ($__DDN_DEBUG) $wpdb->print_error();
            $id = null;
        }
        return $id;
    }

    public function create($fields = null) {
        global $wpdb;

        $sql_values = array();
        $sql_formats = array();
        $class = get_called_class();

        if ($fields === null)
            $fields = $class::FIELDS;

        list($sql_values, $sql_formats) = $this->_prepare_fields_for_sql($fields);

        $current_id = $this->get_id();

        global $__DDN_DEBUG;
        if ($__DDN_DEBUG) $wpdb->show_errors(); 

        if ($this->_id_function === null) {
            $result = $wpdb->p_insert(self::get_db_tablename(), $sql_values, $sql_formats);
            if ($result !== false) {
                $id = $wpdb->insert_id;

                // Autoincrement fields cannot be set when creating, so we update the new object to have the id value
                if ($current_id !== null) {
                    $count = $wpdb->update(self::get_db_tablename(), array($class::$db_id => $current_id), array($class::$db_id => $id));                
                    if ($count === false) {
                        $wpdb->p_delete(self::get_db_tablename(), array($class::$db_id => $id));
                        $id = null;
                    } else
                        $id = $current_id;
                } else
                    $this->set_id($id);
            }
            else {
                $id = null;
                if ($__DDN_DEBUG) $wpdb->print_error();
            }
        } else {
            // Using this version, we add a custom function to generate IDs
            // WARNING: the table needs to be prepared and have a field that fits the ID, and also ID cannot be of type autoincrement (because it fails to insert the value)
            if ($current_id === null)
                $current_id = call_user_func($this->_id_function);

            // Add the 
            if (!isset($sql_values[$class::$db_id]))
                array_push($sql_formats, '%s');
            $sql_values[$class::$db_id] = $current_id;

            $result = $wpdb->p_insert(self::get_db_tablename(), $sql_values, $sql_formats);
            if ($result !== false) {
                $id = $current_id;
                // Set the proper insert_id
                $wpdb->insert_id = $id;
                // Just in case it was null
                $this->set_id($current_id);
            } else {
                $id = null;
                if ($__DDN_DEBUG) $wpdb->print_error();
            }
        }
        return $id;
    }

    public function create_i($fields) {
        return $this->create($this->_indirect_fields_to_fields($fields));
    }

    public function delete_i($fields) {
        return $this->delete($this->_indirect_fields_to_fields($fields));
    }

    public function delete($fields = null) {
        global $wpdb;

        $sql_values = array();
        $sql_formats = array();
        $class = get_called_class();

        if ($fields === null)
            $fields = array($class::$db_id);
            // $fields = $class::FIELDS;

        list($sql_values, $sql_formats) = $this->_prepare_fields_for_sql($fields);

        if ($wpdb->p_delete(self::get_db_tablename(), $sql_values, $sql_formats) === false) {
            return false;
        } else {
            return true;
        }
    }  
    
    public function has_field($field) {
        $class = get_called_class();
        return (in_array($field, $class::FIELDS) || (isset($class::FIELDS[$field])));
    }

    public function get_field($field) {
        $class = get_called_class();

        if (in_array($field, $class::FIELDS) || (isset($class::FIELDS[$field]))) return $this->{$field};
        return null;
    }

    public function set_field($field, $value) {
        $class = get_called_class();

        if (in_array($field, $class::FIELDS)|| (isset($class::FIELDS[$field]))) {
            $this->{$field} = $value;
            return true;
        }
        return false;
    }    

    public function to_simple_object() {
        $o = new SCPM_SimpleObject();

        $class = get_called_class();

        foreach ($class::FIELDS as $field)
            $o->set_field($field, $this->{$field});

        return $o;
    }
}

class SCPM_SimpleObject {
    public function __construct($values = null) {
        if ($values !== null)
            $this->load_values($values);
    }

    public function get_field($f_name) {
        $vars = get_object_vars($this);
        if (isset($vars[$f_name])) return $vars[$f_name];
        return null;
    }

    public function set_field($f_name, $f_value) {
        $this->{$f_name} = $f_value;
    }

    public function has_field($field) {
        $vars = get_object_vars($this);
        return (isset($vars[$field]));
    }

    public function load_values($values) {
        if (! is_array($values)) return;
        foreach ($values as $k => $v) {
            if (! is_int($k))
                $this->set_field($k, $v);
        }
    }
}