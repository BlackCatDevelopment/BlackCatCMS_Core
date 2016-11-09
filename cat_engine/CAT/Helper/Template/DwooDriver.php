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

if(!class_exists('Dwoo',false))
{
    include_once CAT_ENGINE_PATH.'/modules/lib_dwoo/dwoo/dwooAutoload.php';
}

if(!class_exists('CAT_Helper_Template_DwooDriver',false))
{
    class CAT_Helper_Template_DwooDriver extends Dwoo
    {

        protected static $loglevel = \Monolog\Logger::EMERGENCY;
        public    static $_globals = array();
        public    $workdir         = NULL;
        public    $path            = NULL;
        public    $fallback_path   = NULL;
        protected $logger          = NULL;

        public function __construct()
        {
            $cache_path = CAT_ENGINE_PATH.'/temp/cache';
            if (!file_exists($cache_path)) mkdir($cache_path, 0755, true);
            $compiled_path = CAT_ENGINE_PATH.'/temp/compiled';
            if (!file_exists($compiled_path)) mkdir($compiled_path, 0755, true);
            parent::__construct($compiled_path, $cache_path);
            // we need our own logger instance here as the driver does not
            // inherit from CAT_Object
            $this->logger = CAT_Object::log();
        }   // end function __construct()

        public function output($_tpl, $data = array(), Dwoo_ICompiler $compiler = NULL)
        {
            echo $this->get($_tpl,$data,$compiler);
        }   // end function output()

        /**
         * this overrides and extends the original get() method Dwoo provides:
         * - use the template search and fallback paths
         *
         * @access public
         * @param  see original Dwoo docs
         * @return see original Dwoo docs
         *
         **/
        public function get($_tpl, $data = array(), $_compiler = null, $_output = false)
        {
            // add globals to $data array
            if(is_array(self::$_globals) && count(self::$_globals) && is_array($data))
            {
                $this->logger->addDebug('Adding globals to data');
                $data = array_merge(self::$_globals, $data);
            }
            if(!is_object($_tpl))
            {
                if(!file_exists($_tpl) || is_dir($_tpl))
                {
                    $file = CAT_Object::tpl()->findTemplate($_tpl);
                    $this->logger->addDebug(sprintf('Template file [%s]',$file));
                    if($file)
                        return parent::get( realpath($file), $data, $_compiler, $_output);
                    else
                        $this->logger->addWarning('No such template file! (given filename: {file})',array('file'=>$_tpl));
                }
                else
                {
                	return parent::get($_tpl, $data, $_compiler, $_output);
                }
            }
            else {
                return parent::get($_tpl, $data, $_compiler, $_output);
            }
        }   // end function get()

    }   // end class CAT_Helper_Template_DwooDriver
}
