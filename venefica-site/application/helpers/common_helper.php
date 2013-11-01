<?php


define('BASE_PATH', base_url().'assets/'.TEMPLATES.'/');
define('JS_PATH',   BASE_PATH.'js/');
define('CSS_PATH',  BASE_PATH.'css/');
define('IMG_PATH',  BASE_PATH.'img/');

define('IMAGE_TYPE_AD',     'AD');
define('IMAGE_TYPE_USER',   'USER');

define('COMMENT_USER_IMAGE_SIZE',   42);
define('MESSAGE_USER_IMAGE_SIZE',   42);
define('LIST_USER_IMAGE_SIZE',      60);
define('VIEW_USER_IMAGE_SIZE',      90);
define('SELF_USER_IMAGE_SIZE',      112);
define('POST_AD_IMAGE_SIZE',        320);
define('FOLLOWING_AD_IMAGE_SIZE',   320);
define('LIST_AD_IMAGE_SIZE',        320);
define('VIEW_AD_IMAGE_SIZE',        640);

define('DEFAULT_USER_URL',  BASE_PATH.'temp-sample/ge-no-profile-picture.png');
define('DEFAULT_AD_URL',    BASE_PATH.'temp-sample/gifteng.png');


if ( ! function_exists('get_image_url')) {
    function get_image_url($url, $type, $size) {
        /**
        //Method for accessing images via WS.
        //Example: http://localhost:8080/venefica/images/img543/AD/220
        $address = IMAGE_SERVER_URL.$url.'/'.$type.'/'.$size;
        /**/
        
        /**
        //Method for accessing images from a local folder.
        //Note: the / sign is converted to %2F
        //Example: http://localhost/venefica-site/get_photo/544/L3RtcC9sb2NhbC9hZA/60
        $img_num = str_replace("/images/img", "", $url);
        //$folder = './cache';
        $folder = '/tmp/local/'.strtolower($type);
        $folder = base64_encode($folder);
        $address = APP_URL.'get_photo/'.$img_num.'/'.$folder.'/'.$size;
        /**/
        
        //Method to access images from the amazon server
        //Example: https://s3.amazonaws.com/gifteng/ad/502
        $img_num = str_replace("/images/img", "", $url);
        $address = AMAZON_URL.strtolower($type).'/'.$img_num.($size != null && trim($size) != '' ? '_'.$size : '');
        
        //log_message(ERROR, 'address: ' . $address);
        return $address;
    }
}


/**
 * Common helper.
 * 
 * Various useful functions.
 */

if ( ! function_exists('display')) {
    function display($value, $expected) {
        $display = isset($value) && $value == $expected ? "block" : "none";
        return " style=\"display: $display;\"";
    }
}

if ( ! function_exists('respond_ajax')) {
    function respond_ajax($status, $result) {
        $CI =& get_instance();
        
        $data = array();
        $data['obj'] = array($status => $result);
        
        $CI->load->view('json', $data);
    }
}

if ( ! function_exists('respond_ajax_array')) {
    function respond_ajax_array($responseArray) {
        $CI =& get_instance();
        
        $obj = array();
        foreach ( $responseArray as $status => $result ) {
            $obj[$status] = $result;
        }
        
        $data['obj'] = $obj;
        $CI->load->view('json', $data);
    }
}

if ( ! function_exists('safe_redirect')) {
    /**
     * Redirect page by copying the query string as well.
     * @param string $url
     */
    function safe_redirect($url = '') {
        $CI =& get_instance();
        $qs = $CI->input->server('QUERY_STRING');
        redirect($url . (trim($qs) == '' ? '' : '?'.$qs));
    }
}

if ( ! function_exists('get_current_url')) {
    function get_current_url() {
        /**
        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s;
        $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
        return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
        /**/
        
        $s = &$_SERVER;
        $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
        $sp = strtolower($s['SERVER_PROTOCOL']);
        $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
        $port = $s['SERVER_PORT'];
        $port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
        $host = isset($s['HTTP_X_FORWARDED_HOST']) ? $s['HTTP_X_FORWARDED_HOST'] : isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : $s['SERVER_NAME'];
        return $protocol . '://' . $host . $port . $s['REQUEST_URI'];
    }
}

if ( !function_exists('clear_cache') ) {
    //References:
    //http://stackoverflow.com/questions/8860953/codeigniter-session-problems
    //http://stackoverflow.com/questions/4781737/how-to-avoid-browser-cache-using-codeigniter
    //http://stackoverflow.com/questions/5429386/browser-cache-issue-in-codeigniter
    function clear_cache() {
        header("HTTP/1.0 200 OK");
        header("HTTP/1.1 200 OK");
        header('Last-Modified: Sat, 26 Jul 1997 05:00:00 GMT'); //date in the  past
        header("Cache-Control: no-store, no-cache, must-revalidate, no-transform, max-age=0, post-check=0, pre-check=0");
        header("Pragma: no-cache");
    }
}


// making a safer world

//if ( ! function_exists('jsEscape')) {
//    function jsEscape($str) { 
//        return addcslashes($str, "\\\'\"&\n\r<>"); 
//    }
//}

if ( ! function_exists('safe_content')) {
    function safe_content($str) {
        if ( $str == null ) {
            return "";
        }
        
        $str = trim($str);
        $str = trim(strip_tags($str));
        /**
        $str = preg_replace("/\n+/", "\n", $str);
        $str = preg_replace("/\r+/", "\r", $str);
        $str = preg_replace("/[\r\n]+/", "\r\n", $str);
        $str = preg_replace("/[\n\r]+/", "\n\r", $str);
        //$str = str_replace("\n", "<br />", $str);
        /**/
        $str = nl2br($str);
        return $str;
    }
}
if ( ! function_exists('safe_parameter')) {
    function safe_parameter($str) {
        $str = safe_content($str);
        //$str = jsEscape($str);
        $str = str_replace("\"", "&quot;", $str); //&#34;
        //$str = str_replace("'", "&#39;", $str); //somehow does not work as CI does some conversion before output (?)
        $str = str_replace("'", "\\'", $str);
        return $str;
    }
}

//if ( ! function_exists('autolink')) {
//    //
//    //Reference: http://code.seebz.net/p/autolink-php/
//    //
//    function autolink($str, $attributes = array()) {
//        $attrs = '';
//        foreach ($attributes as $attribute => $value) {
//            $attrs .= " {$attribute}=\"{$value}\"";
//        }
//
//        $str = ' ' . $str;
//        $str = preg_replace(
//                '`([^"=\'>])((http|https|ftp)://[^\s<]+[^\s<\.)])`i',
//                '$1<a href="$2"'.$attrs.'>$2</a>',
//                $str
//        );
//        $str = substr($str, 1);
//        return $str;
//    }
//}

if ( ! function_exists('is_empty')) {
    function is_empty($obj) {
        if ( $obj == null ) {
            return true;
        }
        if ( is_array($obj) ) {
            if ( sizeof($obj) == 0 ) {
                return true;
            }
            return false;
        }
        if ( is_object($obj) ) {
            $object_vars = get_object_vars($obj);
            if ( $object_vars == null || empty($object_vars) ) {
                return true;
            }
            return false;
        }
        return empty($obj);
    }
}

// object and properties related helpers

if ( ! function_exists('getField')) {
    function getField($obj, $fieldName) {
        if ( $obj == null || $fieldName == null ) {
            return null;
        }
        if ( property_exists($obj, $fieldName) ) {
            return $obj->$fieldName;
        }
        return null;
    }
}

if ( ! function_exists('hasField')) {
    function hasField($obj, $fieldName) {
        if ( $obj == null || $fieldName == null ) {
            return FALSE;
        }
        return property_exists($obj, $fieldName);
    }
}

// associative array related helpers

if ( ! function_exists('getElement')) {
    function getElement($array, $elem) {
        if ( $array == null || $elem == null ) {
            return null;
        }
        if (array_key_exists($elem, $array) ) {
            return $array[$elem];
        }
        return null;
    }
}

if ( ! function_exists('hasElement')) {
    function hasElement($array, $elem) {
        if ( $array == null || $elem == null ) {
            return FALSE;
        }
        return array_key_exists($elem, $array);
    }
}

// various converters

if ( ! function_exists('readFileAsString')) {
    function readFileAsString($file) {
        $content = fread(fopen($file, "r"), filesize($file));
        return $content;
    }
}


// various string related helpers

if ( ! function_exists('startsWith')) {
    function startsWith($haystack, $needle) {
        return !strncmp($haystack, $needle, strlen($needle));
    }
}
if ( ! function_exists('endsWith')) {
    function endsWith($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }
}
