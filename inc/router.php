<?php

class Router {
    const __ALL_ROUTES = '_ANY_OTHER_ROUTE_';
    const __ALL_OPS = '_ANY_OTHER_OP_';

    private $_routes;
    private $_precall;
    private $_postcall;
    private $_executed;
    private $_last_result;
    private $_last_handler;
    private $_view_precall;
    private $_view_postcall;
    public $static_folders;

    public function __construct() {
        $this->_routes = [];
        $this->_precall = null;
        $this->_postcall = null;
        $this->_executed = false;
        $this->_last_result = null;
        $this->_last_handler = null;
        $this->_view_precall = null;
        $this->_view_postcall = null;
        $this->static_folders = array();
    }

    public function add_pre_callback($call) {
        $this->_precall = $call;
    }

    public function add_post_callback($call) {
        $this->_postcall = $call;
    }

    public function add_view_pre_callback($call) {
        $this->_view_precall = $call;
    }

    public function add_view_post_callback($call) {
        $this->_view_postcall = $call;
    }


    public function add_static_folder($folder) {
        if (!in_array($folder, $this->static_folders))
            array_push($this->static_folders, $folder);
    }

    public function is_static($route) {
        if ($route == "") return false;

        foreach ($this->static_folders as $folder) {
            if (file_exists("$folder/$route")) 
                return "$folder/$route";
        }
        return false;
    }

    /**
     * A route consists of an exact route (e.g. index)
     *  - null means "any route"
     * An op, that should usually be a parameter in the URI (e.g. ?op=myop)
     *  - null means "any op"
     * The class is an object of type SHAFI_Op that implements method ->do() and will be called upon the call of method 'exec'
     * The view is a file that will be included using the method include_one($view), upon the call of method 'view'
     *  - if $view is a function, it will be called and its output will be echoed to the standard output
     * onsuccess will be called in case that the call to ->do() returns anything except "false"
     * onerror will be called in case that the call to ->do() returns "false"
     */
    public function add($route, $op, $class, $view, $onsuccess = null, $onerror = null) {
        if ($route === null)
            $route = Router::__ALL_ROUTES;

        if (!isset($this->_routes[$route]))
            $this->_routes[$route] = [];

        if ($op === null)
            $op = Router::__ALL_OPS;

        $this->_routes[$route][$op] = [
            'class' => $class,
            'onsuccess' => $onsuccess,
            'onerror' => $onerror,
            'view' => $view
        ];  
    }

    /**
     * Function that makes match-making between the existing routes and the route and operation provided
     * 
     * @param route the route to be called
     * @param op the opereation to be called
     */
    protected function _effective_route($route, $op) {
        if (! isset($this->_routes[$route])) {
            if (isset($this->_routes[Router::__ALL_ROUTES]))
                $route = Router::__ALL_ROUTES;
            else
                return false;
        }

        if (! isset($this->_routes[$route][$op])) {
            if (isset($this->_routes[$route][Router::__ALL_OPS]))
                $op = Router::__ALL_OPS;
            else {
                if (isset($this->_routes[Router::__ALL_ROUTES]) && isset($this->_routes[Router::__ALL_ROUTES][Router::__ALL_OPS])) {
                    $route = Router::__ALL_ROUTES;
                    $op = Router::__ALL_OPS;
                } else
                    return false;
            }
        }
        return $this->_routes[$route][$op];        
    }

    /**
     * This function carries out the functionality of a route. It should be called previous to send any header. The workflow for the functions is
     *  1. find the effective route (using multi match, default, etc.).
     *  2. call to the router "precall" function (if defined)
     *  3. if provided a class name, create an object that will manage the operation
     *     3.1. call ->do() method
     *     3.2. call "onsuccess" or "onerror" route's callbacks if provided, depending on the result of 3.1
     *  4. call to the router "postcall" function (if defined)
     * 
     * @param route the route to be called
     * @param op the opereation to be called
     */
    public function exec($route, $op) {
        $static_route = $this->is_static($route);

        // skip executing on static routes
        if ($static_route !== false) return false;

        $this->_executed = false;

        $op = $this->_effective_route($route, $op);
        if ( $op === false) return false;

        if (is_callable($this->_precall)) {
            $result = call_user_func_array($this->_precall, [ $route, $op ] );
            if ($result === false) return false;
        }

        $op_o = null;
        if ($op['class'] !== null) {
            $op_o = new $op['class']();
            $result_op = $op_o->do();

            if (is_callable($op['onsuccess']) && ($result_op !== false))
                call_user_func_array($op['onsuccess'], [ $result_op, $op ] );
            if (is_callable($op['onerror']) && ($result_op === false))
                call_user_func_array($op['onerror'], [ $result_op, $op ] );

            $this->_executed = true;
            $this->_last_result = $result_op;
            $this->_last_handler = $op_o;
        }

        if (is_callable($this->_postcall)) {
            $result = call_user_func_array($this->_postcall, [ $route, $op, $op_o ] );
            if ($result === false) return false;
        }
    
        return $result_op;
    }

    /**
     * This function generates the view that correspond to the route and operation. There are two possibilities to generate the view:
     *   if the parameter passed during the creation was a script, it will be called and the output (if anyu) will be echoed. Instead, 
     *   if the view parameter was a string, it will be included by using a call to "include_once".
     * 
     * @param route the route to be called
     * @param op the opereation to be called
     */
    public function view($route, $op_o) {
        $static_route = $this->is_static($route);

        // skip executing on static routes
        if ($static_route !== false) {
            include_once($static_route);
            return false;
        }

        $op = $this->_effective_route($route, $op_o);
        if ( $op === false) return false;

        if (is_callable($this->_view_precall)) {
            $result = call_user_func_array($this->_view_precall, [ $route, $op_o, $op ] );
            if ($result !== null) echo $result;
        }

        if (is_callable($op['view'])) {
            $result = call_user_func_array($op['view'], [ $this->_last_handler, $this->_last_result, $op ] );
            if ($result === false) return false;
            // Show the result
            echo $result;
            return;
        }

        // "$handler" will be usable INSIDE the script, and so it will be usable "$op"
        $handler = $this->_last_handler;
        include_once($op['view']);

        if (is_callable($this->_view_postcall)) {
            $result = call_user_func_array($this->_view_postcall, [ $route, $op_o, $op ] );
            if ($result !== null) echo $result;
        }
    }
}