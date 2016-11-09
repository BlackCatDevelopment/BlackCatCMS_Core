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

if(!class_exists('CAT_Object',false))
{
    class CAT_Object
    {
        // log level
        private   static $loglevel   = \Monolog\Logger::EMERGENCY;

        protected static $objects    = array();

        protected        $_config    = NULL;
        // current error state
        protected static $errorstate = 500;
        // HTTP status
        protected static $state      = array(
            '200' => 'Success',
            '201' => 'Created',
            '202' => 'Accepted',
            '301' => 'Moved permanently',
            '400' => 'Bad request',
            '401' => 'Access denied',
            '403' => 'Forbidden',
            '404' => 'Not found',
            '409' => 'Conflict',
            '429' => 'Too many requests',
            '500' => 'Internal Server Error',
        );

        /**
         * inheritable constructor; allows to set object variables
         **/
        public function __construct($options=array())
        {
            if(is_array($options))
            {
                $this->config($options);
            }
            // allow to set log level on object creation
            if ( isset( $this->_config['loglevel'] ) ) {
                $this->setLogLevel($this->_config['loglevel']);
            }
            // allow to enable debugging on object creation; this will override
            // 'loglevel' if both are set
            if ( isset( $this->_config['debug'] ) ) {
                $this->debug(true);
            }
        }   // end function __construct()

        /**
         * inheritable __destruct
         **/
        public function __destruct() {}

        /**
         * inheritable __call
         **/
        public function __call($method, $args)
        {
            if(!isset($this) || !is_object($this))
                return false;
            if(method_exists($this, $method))
                return call_user_func_array(array($this, $method), $args);
        }   // end function __call()

// =============================================================================
//   Accessor functions
// =============================================================================

        /**
         * returns a database connection handle
         *
         * This function must be used by all classes, as we plan to replace
         * the database class in later versions!
         *
         * @access public
         * @return object
         **/
        public function db()
        {
            if(!isset(CAT_Object::$objects['db']) || !is_object(CAT_Object::$objects['db']) )
               self::storeObject('db',CAT_Helper_DB::getInstance());
            return CAT_Object::$objects['db'];
        }   // end function db()

        /**
         *
         * @access public
         * @return
         **/
        public static function fileinfo()
        {
            if(!isset(CAT_Object::$objects['getid3']) || !is_object(CAT_Object::$objects['getid3']))
            {
                require_once CAT_ENGINE_PATH.'/modules/lib_getid3/getid3/getid3.php';
        	    CAT_Object::$objects['getid3'] = new getID3;
            }
            return CAT_Object::$objects['getid3'];
        }   // end function fileinfo()
        

        /**
         * accessor to I18n helper
         *
         * @access public
         * @return object
         **/
        public static function lang()
        {
            if(!isset(CAT_Object::$objects['lang']) || !is_object(CAT_Object::$objects['lang']) )
            {
                self::storeObject('lang',CAT_Helper_I18n::getInstance(CAT_Registry::get('LANGUAGE',NULL,'EN')));
            }
            return CAT_Object::$objects['lang'];
        }   // end function lang()

        /**
         *
         * @access public
         * @return
         **/
        public static function lb()
        {
            if(!isset(CAT_Object::$objects['list']) || !is_object(CAT_Object::$objects['list']) )
                self::storeObject('list',\wblib\wbList::getInstance(array('__id_key'=>'page_id')));
            return CAT_Object::$objects['list'];
        }   // end function list()
        

        /**
         * accessor to Monolog logger
         **/
        public static function log()
        {
            // global logger
            if(!isset(CAT_Object::$objects['logger']) || !is_object(CAT_Object::$objects['logger']) )
            {
                // default logger; will set the log level to the global default
                // set in CAT_Object
                $logger = new CAT_Object_LoggerDecorator(new \Monolog\Logger('CAT'));

                $bubble = false;
                $errorStreamHandler = new \Monolog\Handler\StreamHandler(
                    CAT_ENGINE_PATH.'/temp/logs/core_error.log', \Monolog\Logger::ERROR, $bubble
                );
                $emergStreamHandler = new \Monolog\Handler\StreamHandler(
                    CAT_ENGINE_PATH.'/temp/logs/core_critical.log', \Monolog\Logger::CRITICAL, $bubble
                );

                $logger->pushHandler($errorStreamHandler);
                $logger->pushHandler($emergStreamHandler);

                $logger->pushProcessor(new \Monolog\Processor\PsrLogMessageProcessor());

                self::storeObject('logger',$logger);

                CAT_Registry::set('CAT.logger.CAT_Object',$logger);
            }
            // specific logger
            $class    = get_called_class();
            $loglevel = self::getLogLevel();
#echo "<br /><br />loglevel for class -$class- from class::getLogLevel() - $loglevel<br />";
            if($loglevel != CAT_Object::$loglevel || $loglevel == \Monolog\Logger::DEBUG)
            {
#echo "enable loglevel -$loglevel- for class -$class-<br />";
                $logger = CAT_Registry::get('CAT.logger.'.$class);
#echo "registry logger -$logger-<br />";
                if(!$logger)
                {
#echo "creating new logger<br />";
                    $logger = new CAT_Object_LoggerDecorator(new \Monolog\Logger('CAT.'.$class));
                    $stream = new \Monolog\Handler\StreamHandler(
                        CAT_ENGINE_PATH.'/temp/logs/core_'.$class.'_'.date('m-d-Y').'.log',$class::$loglevel,false
                    );
                    $stream->setFormatter(new \Monolog\Formatter\LineFormatter(
                        "[%datetime%] [%channel%.%level_name%]  %message%  [%extra%]\n"
                    ));
                    $logger->pushHandler($stream);
                    $logger->pushProcessor(new \Monolog\Processor\PsrLogMessageProcessor());
                    #$logger->pushProcessor(new \Monolog\Processor\IntrospectionProcessor());
#echo "saving new logger to registry<br />";
                    CAT_Registry::set('CAT.logger.'.$class,$logger);
#echo "registry logger after creation<br />";
#echo "<textarea style=\"width:100%;height:200px;color:#000;background-color:#fff;\">";
#print_r( CAT_Registry::get('CAT.logger.'.$class) );
#echo "</textarea>";
                }
                return $logger;
            }
            else {
#echo "returning default logger<br />";
                return CAT_Object::$objects['logger'];
            }
        }   // end function log ()

        /**
         * accessor to permissions
         *
         * @access public
         * @return object
         **/
        public function perms()
        {
            if(!isset(CAT_Object::$objects['perms']) || !is_object(CAT_Object::$objects['perms']) )
                self::storeObject('perms',CAT_Permissions::getInstance());
            return CAT_Object::$objects['perms'];
        }   // end function perms()

        /**
         * accessor to current user object
         *
         * @access public
         * @return object
         **/
        public function roles()
        {
            if(!isset(CAT_Object::$objects['roles']) || !is_object(CAT_Object::$objects['roles']) )
                self::storeObject('roles',CAT_Roles::getInstance());
            return CAT_Object::$objects['roles'];
        }   // end function roles()

        /**
         *
         * @access public
         * @return
         **/
        public static function router()
        {
            if(!isset(CAT_Object::$objects['router']) || !is_object(CAT_Object::$objects['router']) )
                self::storeObject('router',CAT_Helper_Router::getInstance());
            return CAT_Object::$objects['router'];
        }   // end function router()

        /**
         * accessor to current template object
         *
         * @access public
         * @return object
         **/
        public static function tpl()
        {
            if(!isset(CAT_Object::$objects['tpl']) || !is_object(CAT_Object::$objects['tpl']) )
            {
                CAT_Object::$objects['tpl'] = CAT_Helper_Template::getInstance('Dwoo');
                CAT_Object::$objects['tpl']->setGlobals(array(
                    'WEBSITE_DESCRIPTION' => CAT_Registry::get('WEBSITE_DESCRIPTION'),
                    'CAT_CORE'            => 'BlackCat CMS',
                    'CAT_VERSION'         => CAT_Registry::get('CAT_VERSION'),
                    'CAT_BUILD'           => CAT_Registry::get('CAT_BUILD'),
                    'CAT_DATE_FORMAT'     => CAT_Registry::get('CAT_DATE_FORMAT'),
                    'LANGUAGE'            => CAT_Registry::get('LANGUAGE'),
                ));
            }
            return CAT_Object::$objects['tpl'];
        }   // end function tpl()

        /**
         * accessor to current user object
         *
         * @access public
         * @return object
         **/
        public function user()
        {
            if(!isset(CAT_Object::$objects['user']) || !is_object(CAT_Object::$objects['user']) )
                self::storeObject('user',CAT_User::getInstance());
            return CAT_Object::$objects['user'];
        }   // end function user()

// =============================================================================
// various helper functions
// =============================================================================

        /**
         * set config values
         *
         * This method allows to set object variables at runtime.
         * If $option is an array, the array keys are treated as object var
         * names, the array values as their values. The second param $value
         * is ignored in this case.
         * If $option is a string, it is treated as object var name; in this
         * case, $value must be set.
         *
         * @access public
         * @param  mixed    $option
         * @param  string   $value
         * @return void
         *
         **/
        public function config( $option, $value = NULL ) {
            if(!is_array($this->_config))
                $this->_config = array();
            if(is_array($option))
                $this->_config = array_merge($this->_config, $option);
            else
                $this->_config[$option] = $value;
            return $this;
        }   // end function config()

        /**
         * create a guid; used by the backend, but can also be used by modules
         *
         * @access public
         * @param  string  $prefix - optional prefix
         * @return string
         **/
        public static function createGUID($prefix='')
        {
            if(!$prefix||$prefix='') $prefix=rand();
            $s = strtoupper(md5(uniqid($prefix,true)));
            $guidText =
                substr($s,0,8) . '-' .
                substr($s,8,4) . '-' .
                substr($s,12,4). '-' .
                substr($s,16,4). '-' .
                substr($s,20);
            return $guidText;
        }   // end function createGUID()

// =============================================================================
//   JSON output helper functions
// =============================================================================

        /**
         * checks for 'ACCEPT' request header; returns true if exists and
         * value is 'application/json'
         *
         * @access public
         * @return boolean
         **/
        public static function asJSON()
        {
            $headers = getallheaders();
            if(isset($headers['Accept']) && preg_match('~application/json~i',$headers['Accept']))
                return true;
            else
                return false;
        }   // end function asJSON()

        /**
         * calls json_result() to format a success message
         *
         * @access public
         * @param  string  $message
         * @param  boolean $exit
         * @return JSON
         **/
        public static function json_success($message,$exit=true)
        {
            self::json_result(true,$message,$exit);
        }   // end function json_success()

        /**
         * calls json_result() to format an error message
         *
         * @access public
         * @param  string  $message
         * @param  boolean $exit
         * @return JSON
         **/
        public static function json_error($message,$exit=true)
        {
            self::json_result(false,$message,$exit);
        }   // end function json_error()

        /**
         * creates an array with 'success' and 'message' keys and encodes it
         * to JSON using json_encode(); $message will be translated using the
         * lang() method
         *
         * the JSON result is echo'ed; if $exit is set to true, exit()
         * is called
         *
         * if no header was sent, sets 'application/json' as content-type
         *
         * @access public
         * @param  boolean $success
         * @param  string  $message
         * @param  boolean $exit
         * @return void
         **/
        public static function json_result($success,$message,$exit=true)
        {
            if(!headers_sent())
                header('Content-type: application/json');
            $field = (
                is_scalar($message)
                ? 'message'
                : 'data'
            );
            $content = (
                is_scalar($message)
                ? self::lang()->translate($message)
                : $message
            );
            echo json_encode(array(
                'success' => $success,
                $field    => $content
            ));
            if($exit) exit();
        }   // end function json_result()

// =============================================================================
//  LOGGING / DEBUGGING
// =============================================================================

        /**
         * enable or disable debugging at runtime
         *
         * @access public
         * @param  boolean  enable (TRUE) / disable (FALSE)
         *
         **/
        public function debug($bool)
        {
            $class = get_called_class();
            if ($bool === true)
            {
                self::log()->addDebug('enable debugging for class {class}',array('class'=>$class));
                $class::$loglevel = \Monolog\Logger::DEBUG;
            }
            else
            {
                self::log()->addDebug('resetting loglevel to default for class {class}',array('class'=>$class));
                $class::$loglevel = CAT_Object::$loglevel;
            }
        }   // end function debug()

        /**
         *
         * @access public
         * @return
         **/
        public static function getLogLevel()
        {
            $class = get_called_class();
            return $class::$loglevel;
        }

        /**
         *
         * @access public
         * @return
         **/
        public static function setLogLevel($level='EMERGENCY')
        {
#echo "setLogLevel()<br />";
echo "<pre>";
print_r(debug_backtrace());
echo "</pre>";
            // map old KLogger levels
            if(is_numeric($level))
            {
                switch($level) {
                    case 8:
                        $level = 'EMERGENCY';
                        break;
                    default:
                        $level = 'DEBUG';
                        break;
                }
            }
            $class = get_called_class();
echo "setLogLevel called for class $class, old level ", $class::getLogLevel(), ", new level $level<br />";
            $class::$loglevel = constant('\Monolog\Logger::'.$level);
echo "level now: ", $class::$loglevel, "<br />";
        }   // end function setLogLevel()
        

// =============================================================================
//  ERROR HANDLING
// =============================================================================

        public static function errorstate($id=NULL)
        {
            if($id)
                CAT_Object::$errorstate = $id;
            return CAT_Object::$errorstate;
        }   // end function errorstate()

        /**
         * print an error message; this will set the HTTP status code to 500
         *
         * the error message will be translated (if translation is available)
         *
         * @access public
         * @param  string   $message
         * @param  string   $link         - URL for forward to
         * @param  boolean  $print_header - wether to print the page header
         * @param  array    $args
         * @return void
         **/
        public static function printError($message=NULL, $link='index.php', $print_header=true, $args=NULL)
        {
            if(!$message)
                'unknown error';
            self::log()->addError($message);
            self::errorstate(500);

            if(self::asJSON())
            {
                echo self::json_error($message,true);
                exit; // should never be reached
            }

            $message = CAT_Object::lang()->translate($message);
            $errinfo = CAT_Object::lang()->t(self::$state[self::errorstate()]);

            $print_footer = false;
            if(!headers_sent() && $print_header)
            {
                $print_footer = true; // print header also means print footer
                if (
                       !isset(CAT_Object::$objects['tpl'])
                    || !is_object(CAT_Object::$objects['tpl'])
                    || ( !CAT_Backend::isBackend() && !defined('CAT_PAGE_CONTENT_DONE'))
                ) {
                    self::err_page_header();
                }
            }

            if (
                   !isset(CAT_Object::$objects['tpl'])
                || !is_object(CAT_Object::$objects['tpl'])
                || CAT_Backend::isBackend()
            )
            //if (!is_object(CAT_Object::$objects['tpl']) || ( !CAT_Backend::isBackend() && !defined('CAT_PAGE_CONTENT_DONE')) )
            {
                require dirname(__FILE__).'/templates/error_content.php';
            }

            if ($print_footer && (!isset(CAT_Object::$objects['tpl']) || !is_object(CAT_Object::$objects['tpl'])))
            {
                self::err_page_footer();
            }

        }   // end function printError()

        /**
         * wrapper to printError(); print error message and exit
         *
         * see printError() for @params
         *
         * @access public
         * @return void
         **/
        public static function printFatalError($message=NULL, $link='index.php', $print_header=true, $args=NULL) {
            CAT_Object::printError($message, $link, $print_header, $args);
            exit;
        }   // end function printFatalError()

        /**
         *  Print a message and redirect the user to another page
         *
         *  @access public
         *  @param  mixed   $message     - message string or an array with a couple of messages
         *  @param  string  $redirect    - redirect url; default is "index.php"
         *  @param  boolean $auto_footer - optional flag to 'print' the footer. Default is true.
         *  @param  boolean $auto_exit   - optional flag to call exit() (default) or not
         *  @return void    exit()s
         */
    	public static function printMsg($message, $redirect='index.php', $auto_footer=true, $auto_exit=true)
    	{
            $self = self::getInstance();

    		if (true === is_array($message))
    			$message = implode("<br />", $message);

    		$self->tpl()->setPath(CAT_THEME_PATH.'/templates');
    		$self->tpl()->setFallbackPath(CAT_THEME_PATH.'/templates');

    		$self->tpl()->output('success',array(
                'MESSAGE'        => CAT_Object::lang()->translate($message),
                'REDIRECT'       => $redirect,
                'REDIRECT_TIMER' => CAT_Registry::get('REDIRECT_TIMER'),
            ));

    		if ($auto_footer == true)
    		{
                $caller       = debug_backtrace();
                // remove first item (it's the printMsg() method itself)
                array_shift($caller);
                $caller_class
                    = isset( $caller[0]['class'] )
                    ? $caller[0]['class']
                    : NULL;
    			if ($caller_class && method_exists($caller_class, "print_footer"))
    			{
                    if( is_object($caller_class) )
    				    $caller_class->print_footer();
                    else
                        $caller_class::print_footer();
    			}
                else {
                    self::log()->error("unable to print footer - no such method $caller_class -> print_footer()");
                }
                if($auto_exit)
                    exit();
    		}
        }   // end function printMsg()

        /**
         *
         * @access public
         * @return
         **/
        public static function storeObject($name,$obj)
        {
            CAT_Object::$objects[$name] = $obj;
        }   // end function storeObject()
        

        /**
         * prints (requires) error_footer.php
         *
         * @access private
         * @return void
         **/
        private static function err_page_footer()
        {
            require dirname(__FILE__).'/templates/error_footer.php';
            return;
        }   // end function err_page_footer()

        /**
         * prints (requires) error_header.php; also sets HTTP status header
         * and $_SERVER['REDIRECT_STATUS']
         *
         * @access private
         * @return void
         **/
        private static function err_page_header()
        {
            header('HTTP/1.1 '.self::$errorstate.' '.self::$state[self::$errorstate]);
		    header('Status: '.self::$errorstate.' '.self::$state[self::$errorstate]);
		    $_SERVER['REDIRECT_STATUS'] = self::$errorstate;
            require dirname(__FILE__).'/templates/error_header.php';
            return;
        }   // end function err_page_header()
    }
}


/**
 * This class adds the old logging method names to the new Monolog logger
 * used since BlackCat version 2.0
 **/
if(!class_exists('CAT_Object_LoggerDecorator',false))
{
    class CAT_Object_LoggerDecorator extends \Monolog\Logger
    {
        private $logger = NULL;
        public function __construct(\Monolog\Logger $logger) {
            parent::__construct($logger->getName());
            $this->logger = $logger;
        }
        public function logDebug ($msg,$args=array()) {
            if(!is_array($args)) $args = array($args);
            return $this->logger->addDebug($msg,$args);
        }
        public function logInfo  () {
        }
        public function logNotice() {
        }
        public function logWarn  () {
        }
        public function logError () {}
        public function logFatal () {}
        public function logAlert () {}
        public function logCrit  () {}
        public function logEmerg () {}
    }
}