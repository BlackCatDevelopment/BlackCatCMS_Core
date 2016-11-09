<?php

/*
   ____  __      __    ___  _  _  ___    __   ____     ___  __  __  ___
  (  _ \(  )    /__\  / __)( )/ )/ __)  /__\ (_  _)   / __)(  \/  )/ __)
   ) _ < )(__  /(__)\( (__  )  (( (__  /(__)\  )(    ( (__  )    ( \__ \
  (____/(____)(__)(__)\___)(_)\_)\___)(__)(__)(__)    \___)(_/\/\_)(___/

   @author          Black Cat Development
   @copyright       2016 Black Cat Development
   @link            http://blackcat-cms.org
   @license         http://www.gnu.org/licenses/gpl.html
   @category        CAT_Core
   @package         CAT_Core

*/

if ( ! class_exists( 'CAT_Helper_Array' ) )
{

    if ( ! class_exists( 'CAT_Object', false ) ) {
	    @include dirname(__FILE__).'/../Object.php';
	}
	
	class CAT_Helper_Array extends CAT_Object
	{

        protected static $loglevel  = \Monolog\Logger::EMERGENCY;
        private   static $Needle    = NULL;
        private   static $Key       = NULL;
        private   static $instance  = NULL;

        public function __call($method, $args)
        {
            if ( ! isset($this) || ! is_object($this) )
                return false;
            if ( method_exists( $this, $method ) )
                return call_user_func_array(array($this, $method), $args);
        }

        public static function getInstance()
        {
            if (!self::$instance)
                self::$instance = new self();
            return self::$instance;
        }   // end function getInstance()

        private static function filter_callback($v)
        {
            return !isset($v[self::$Key]) || $v[self::$Key] !== self::$Needle;
        }   // end function filter_callback()

        /**
         * allows to reorder the $_FILES array if the 'multiple' attribute
         * was set on the file upload field; see
         * http://de1.php.net/manual/de/reserved.variables.files.php#109958
         * for details
         *
         * @access public
         * @param  array  $vector
         * @return array
         **/
        public function ArrayDiverse($vector) {
            $result = array();
            foreach($vector as $key1 => $value1)
                foreach($value1 as $key2 => $value2)
                    $result[$key2][$key1] = $value2;
            return $result;
        }   // end function ArrayDiverse()

        /**
         * encode all entries of an multidimensional array into utf8
         * http://de1.php.net/manual/de/function.json-encode.php#100492
         *
         * @access public
         * @param  array  $dat
         * @return array
         **/
        public static function ArrayEncodeUTF8($dat) // -- It returns $dat encoded to UTF8
        {
            if (is_string($dat)) return utf8_encode($dat);
            if (!is_array($dat)) return $dat;
            $ret = array();
            foreach($dat as $i=>$d) $ret[$i] = self::ArrayEncodeUTF8($d);
            return $ret;
        }   // end function ArrayEncodeUTF8()

        /**
         * filters an multidimensional array by given key, returns the filtered
         * elements
         *
         * This means, all elements that have a key $key with value $value will
         * be removed from &$array and returned as result
         *
         * @access public
         * @param  array  $array (reference!)
         * @param  string $key
         * @param  string $value
         * @return array
         **/
        public static function ArrayFilterByKey(&$array, $key, $value)
        {
            if(!is_array($array)) return false;
            $result = array();
            foreach ($array as $k => $elem) {
                if (isset($elem[$key]) && $elem[$key] == $value) {
                    $result[] = $array[$k];
                    unset($array[$k]);
                }
            }
            return $result;
        }   // end function ArrayFilterByKey()

        /**
         *
         * @access public
         * @return
         **/
        public static function ArrayKeyExists($key,&$array)
        {
            if(!is_array($array))   return false;
            if(isset($array[$key])) return true;
            foreach($array as $elem)
            {
                if(is_array($elem))
                {
                    return self::ArrayKeyExists($elem,$key);
                }
            }
            return false;
        }   // end function ArrayKeyExists()
        

        /**
         * removes an element from an array
         *
         * @access public
         * @param  string $Needle
         * @param  array  $Haystack
         * @param  mixed  $NeedleKey
         **/
        public static function ArrayRemove( $Needle, &$Haystack, $NeedleKey="" )
        {
            if( ! is_array( $Haystack ) ) {
                return false;
            }
            reset($Haystack);
            self::$Needle = $Needle;
            self::$Key    = $NeedleKey;
            $Haystack     = array_filter($Haystack, 'self::filter_callback');
        }   // end function ArrayRemove()

        /**
         * sort an array
         *
         * @access public
         * @param  array   $array          - array to sort
         * @param  mixed   $index          - key to sort by
         * @param  string  $order          - 'asc' (default) || 'desc'
         * @param  boolean $natsort        - default: false
         * @param  boolean $case_sensitive - sort case sensitive; default: false
         *
         **/
        public static function ArraySort ( $array, $index, $order='asc', $natsort=FALSE, $case_sensitive=FALSE )
        {
            if( is_array($array) && count($array)>0 )
            {
                 foreach(array_keys($array) as $key)
                     $temp[$key]=$array[$key][$index];
                 if(!$natsort)
                 {
                     ($order=='asc')? asort($temp) : arsort($temp);
                 }
                 else
                 {
                     ($case_sensitive)? natsort($temp) : natcasesort($temp);
                     if($order!='asc')
                         $temp=array_reverse($temp,TRUE);
                 }
                 foreach(array_keys($temp) as $key)
                     (is_numeric($key))? $sorted[]=$array[$key] : $sorted[$key]=$array[$key];
                 return $sorted;
            }
            return $array;
        }   // end function ArraySort()

        /**
         * search multidimensional array for $Needle
         *
         * @access public
         * @param  string  $Needle
         * @param  array   $Haystack
         * @param  string  $NeedleKey - optional
         * @param  boolean $Strict    - optional, default: false
         * @param  array   $Path      - needed for recursion
         * @return mixed   array (path) or false (not found)
         **/
        public static function ArraySearchRecursive( $Needle, $Haystack, $NeedleKey="", $Strict=false, $Path=array() )
        {

            if( ! is_array( $Haystack ) ) {
                return false;
            }
            reset($Haystack);
            foreach ( $Haystack as $Key => $Val ) {
                if (
                    is_array( $Val )
                    &&
                    $SubPath = self::ArraySearchRecursive($Needle,$Val,$NeedleKey,$Strict,$Path)
                ) {
                    $Path = array_merge($Path,Array($Key),$SubPath);
                    return $Path;
                }
                elseif (
                    ( ! $Strict && $Val  == $Needle && $Key == ( strlen($NeedleKey) > 0 ? $NeedleKey : $Key ) )
                    ||
                    (   $Strict && $Val === $Needle && $Key == ( strlen($NeedleKey) > 0 ? $NeedleKey : $Key ) )
                ) {
                    $Path[]=$Key;
                    return $Path;
                }
            }
            return false;
        }   // end function ArraySearchRecursive()
        
        /**
         * make multidimensional array unique
         *
         * @access public
         * @param  array
         * @return array
         **/
        public static function ArrayUniqueRecursive($array) {
    		$set = array();
    		$out = array();
    		foreach ( $array as $key => $val )
            {
                if ( is_array($val) )
                {
                    $out[$key] = self::ArrayUniqueRecursive($val);
                }
                elseif ( ! isset( $set[$val] ) )
                {
                    $out[$key] = $val;
                    $set[$val] = true;
                }
                else
                {
                    $out[$key] = $val;
                }
    		}
    		return $out;
   		}   // end function ArrayUniqueRecursive()
	}   // ----- end class CAT_Helper_Array -----
}