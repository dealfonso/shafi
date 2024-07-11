<?php
    if ( ! defined( '__SHAFI_FOLDER' ) ) {
        exit; // Exit if accessed directly
    }

    if (!function_exists('gettext')) {
        function gettext($str) {
            return $str;
        }
    }

    function get_root_url() {
        return rtrim(__ROOT_URL, '/') . '/';
    }

    function get_rel_url($url) {
        return rtrim(__ROOT_URL, '/') . '/' . ltrim($url, '/');
    }

    function sanitize_text($text) {
        return preg_replace('/[^a-z0-9]+/', '-', strtolower( $text ));
    }

    function get_var_dump($var) {
        ob_start();
        var_dump($var);
        return ob_get_clean();    
    }

    /**
    * Delete a directory RECURSIVELY
    * @param string $dir - directory path
    * @link http://php.net/manual/en/function.rmdir.php
    */
   function rrmdir($dir) {
       if (is_dir($dir)) {
           $objects = scandir($dir);
           foreach ($objects as $object) {
               if ($object != "." && $object != "..") {
                   if (filetype($dir . "/" . $object) == "dir") {
                       rrmdir($dir . "/" . $object); 
                   } else {
                       unlink($dir . "/" . $object);
                   }
               }
           }
           reset($objects);
           rmdir($dir);
       }
   }

    function keys_in_array($keys, $arr) {
        foreach ($keys as $k)
            if (!isset($arr[$k])) return false;
        return true;
    }
    
    function pre_var_dump(...$vars) {
        foreach ($vars as $var) {
            echo "<pre>";
            var_dump($var);
            echo "</pre>";
        }
    }

    function SCPM_datetime_to_string($value, $dateonly = false) {
        if ($value == null) return "";
        if ($dateonly) return $value->format('d/m/Y');
        return $value->format('d/m/Y H:i');
    }

    function human_filesize($bytes, $decimals = 2) {
        if ($bytes === null) return "";
        if ($bytes === 0) return "0 B";
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes??"") - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
      }

    function add_query_var($values, $uri = null) {
        if ($uri === null) $uri = $_SERVER['REQUEST_URI'];

        $uri_parts = parse_url($uri);
        if (!isset($uri_parts['query'])) 
            $uri_parts['query'] = "";

        parse_str($uri_parts['query'], $query_vars);

        // $values has precedence over $query_vars
        $uri_parts['query'] = http_build_query($values + $query_vars);

        $result = '';
        if (isset($uri_parts['scheme'])) $result .= $uri_parts['scheme']. "://";
        if (isset($uri_parts['user'])) $result .= $uri_parts['user'];
        if (isset($uri_parts['pass'])) $result .= ':' . $uri_parts['pass'];
        if (isset($uri_parts['user']) || isset($uri_parts['pass'])) $result .= '@';
        if (isset($uri_parts['host'])) $result .= $uri_parts['host'];
        if (isset($uri_parts['port'])) $result .= ':' . $uri_parts['port'];
        if (isset($uri_parts['path'])) $result .= $uri_parts['path'];
        if (isset($uri_parts['fragment'])) $result .= '#' . $uri_parts['fragment'];
        if (isset($uri_parts['query'])) $result .= '?' . $uri_parts['query'];

        return $result;
    }
    
 /**
  * https://stackoverflow.com/q/1634782
  * Retrieves the best guess of the client's actual IP address.
  * Takes into account numerous HTTP proxy headers due to variations
  * in how different ISPs handle IP addresses in headers between hops.
  */
   function get_ip_address() {
    // Check for shared internet/ISP IP
    if (!empty($_SERVER['HTTP_CLIENT_IP']) && validate_ip($_SERVER['HTTP_CLIENT_IP']))
     return $_SERVER['HTTP_CLIENT_IP'];
  
    // Check for IPs passing through proxies
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
     // Check if multiple IP addresses exist in var
      $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
      foreach ($iplist as $ip) {
       if (validate_ip($ip))
        return $ip;
      }
     }
    
    if (!empty($_SERVER['HTTP_X_FORWARDED']) && validate_ip($_SERVER['HTTP_X_FORWARDED']))
     return $_SERVER['HTTP_X_FORWARDED'];
    if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && validate_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
     return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && validate_ip($_SERVER['HTTP_FORWARDED_FOR']))
     return $_SERVER['HTTP_FORWARDED_FOR'];
    if (!empty($_SERVER['HTTP_FORWARDED']) && validate_ip($_SERVER['HTTP_FORWARDED']))
     return $_SERVER['HTTP_FORWARDED'];
  
    // Return unreliable IP address since all else failed
    return $_SERVER['REMOTE_ADDR'];
   }
  
   /**
    * https://stackoverflow.com/q/1634782
    * Ensures an IP address is both a valid IP address and does not fall within
    * a private network range.
    *
    * @access public
    * @param string $ip
    */
    function validate_ip($ip) {
       if (filter_var($ip, FILTER_VALIDATE_IP, 
                           FILTER_FLAG_IPV4 | 
                           FILTER_FLAG_IPV6 |
                           FILTER_FLAG_NO_PRIV_RANGE | 
                           FILTER_FLAG_NO_RES_RANGE) === false)
           return false;
       return true;
   }

   /**
    * https://stackoverflow.com/a/13733588
    * Generate a token of limited size, using an alphabet; a replacement for the id function
    */
   function get_random_string($length = 8){

    // 218340105584896 combinations (> 2*10^14) vs UUID (> 5*10^36)
    $token = "";
    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
    $codeAlphabet.= "0123456789";
    $max = strlen($codeAlphabet);

   for ($i=0; $i < $length; $i++) {
       $token .= $codeAlphabet[random_int(0, $max-1)];
   }

   return $token;
}   