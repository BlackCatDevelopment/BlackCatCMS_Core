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

if (!class_exists('CAT_Frontend', false))
{
    if (!class_exists('CAT_Object', false))
    {
        @include dirname(__FILE__) . '/Object.php';
    }

    class CAT_Frontend extends CAT_Object
    {
        protected static $loglevel    = \Monolog\Logger::EMERGENCY;
        private   static $instance    = array();
        private   static $maintenance = NULL;
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// Spaeter konfigurierbar machen!
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        private   static $asset_paths = array(
            'css','js','images','eot','fonts'
        );

        public static function getInstance()
        {
            if (!self::$instance)
                self::$instance = new self();
            return self::$instance;
        }   // end function getInstance()

        /**
         * dispatch frontend route
         **/
        public static function dispatch()
        {

            // forward to backend router
            if(CAT_Backend::isBackend())
                return CAT_Backend::dispatch();

            $self   = self::getInstance();

            // forward to assets controller (for css/js/...)
            if($self->router()->match('~^('.implode('|',self::$asset_paths).')~i'))
                return CAT_Helper_Assets::assets();

            // forward to modules
            if($self->router()->match('~^modules/~i'))
                require CAT_ENGINE_PATH.'/'.$self->router()->getRoute();

            // check if the system is in maintenance mode
            if(self::isMaintenance())
            {
                $result = CAT_Registry::getInstance()->db()->query(
                    'SELECT `value` FROM `:prefix:settings` WHERE `name`="maintenance_page"'
                );
            }
            else
            {
                $route = $self->router()->getRoute();
                // no route -> get default page
                if($route == '')
                {
                    $page_id = CAT_Helper_Page::getDefaultPage();
                }
                else // find page by route
                {
                    // remove suffix from route
                    $route  = str_ireplace(CAT_Registry::get('PAGE_EXTENSION'), '', $route);
                    $result = $self->db()->query(
                        'SELECT `page_id` FROM `:prefix:pages` WHERE `link`=?',
                        array('/'.$route)
                    );
                    $data    = $result->fetch();
                    $page_id = $data['page_id'];
                }
            }
            // get page handler
            $page   = CAT_Page::getInstance($page_id);
            // hand over to page handler
            $page->show();
        }   // end function dispatch()

        /**
         * check if system is in maintenance mode
         *
         * @access public
         * @return boolean
         **/
        public static function isMaintenance()
        {
            if(!self::$maintenance)
            {
                $self = self::getInstance();
                self::$maintenance
                    = CAT_Registry::get('maintenance_mode') == 'true'
                    ? true
                    : false;
            }
            return self::$maintenance;
        }   // end function isMaintenance()

    }   // end class CAT_Frontend
}