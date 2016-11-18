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

if (!class_exists('CAT_Object', false))
{
    @include dirname(__FILE__) . '/Object.php';
}

if (!class_exists('CAT_Page', false))
{
    class CAT_Page extends CAT_Object
    {
        // ID of last instantiated page
        private   static $curr_page  = NULL;
        // helper handle
        private   static $helper     = NULL;
        // singleton, but one instance per page_id!
        private   static $instances  = array();
        // loglevel
        protected static $loglevel   = \Monolog\Logger::EMERGENCY;

        //
        protected        $page_id    = NULL;

        /**
         * get instance for page with ID $page_id
         *
         * @access public
         * @param  integer $page_id
         * @return object
         **/
        public static function getInstance($page_id=NULL)
        {
            if (!self::$helper)
                self::$helper = CAT_Helper_Page::getInstance();
            if($page_id)
            {
                if(!isset(self::$instances[$page_id]))
                {
                    self::$instances[$page_id] = new self($page_id);
                    self::$instances[$page_id]->page_id = $page_id;
                    self::init($page_id);
                }
                return self::$instances[$page_id];
            }
            else
            {
                return new self(0);
            }
        }   // end function getInstance()

        /**
         *
         * @access public
         * @return
         **/
        public static function getID()
        {
            return self::$curr_page;
        }   // end function getID()

        /**
         * get page sections for given block
         *
         * @access public
         * @param  integer $block
         * @return void (direct print to STDOUT)
         **/
        public static function getPageContent($block = 1)
        {
            $page_id = self::getID();

            // check if the page exists, is not marked as deleted, and has
            // some content at all
            if(
                   !$page_id                           // no page id
                || !self::$helper->exists($page_id)    // page does not exist
                || !self::$helper->isActive($page_id)  // page not active
            ) {
                return self::print404();
            }

// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// TODO: Maintenance page
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

            // check if user is allowed to see this page
            if(!self::$helper->user()->is_root())
            {
                // global perm
                if(self::$helper->user()->hasPagePerm($page_id,'pages_view'))
                {
                    self::$helper->printFatalError('You are not allowed to view this page!');
                }
            }

            // get active sections
            $sections = CAT_Sections::getActiveSections($page_id,$block);
            if(!count($sections)) // no content for this block
                return false;

            $output = array();
            foreach ($sections as $section)
            {
                // spare some typing
                $section_id = $section['section_id'];
                $module     = $section['module'];
                $handler    = CAT_Helper_Directory::sanitizePath(CAT_ENGINE_PATH.'/modules/'.$module.'/view.php');
                $langfile   = CAT_Helper_Directory::sanitizePath(CAT_ENGINE_PATH.'/modules/'.$module.'/languages/'.CAT_Registry::get('LANGUAGE').'.php');

                if (file_exists($handler))
                {
                    // load language file (if any)
                    if(file_exists($langfile) && self::$helper->lang()->checkFile($langfile,'LANG',true))
                        $this->lang()->addFile(CAT_Registry::get('LANGUAGE').'.php', CAT_Helper_Directory::sanitizePath(CAT_ENGINE_PATH.'/modules/'.$module.'/languages'));

                    // set template path
                    if (file_exists(CAT_Helper_Directory::sanitizePath(CAT_ENGINE_PATH.'/modules/'.$module.'/templates')))
                        self::$helper->tpl()->setPath(CAT_Helper_Directory::sanitizePath(CAT_ENGINE_PATH.'/modules/'.$module.'/templates'));
                    if (file_exists(CAT_Helper_Directory::sanitizePath(CAT_ENGINE_PATH.'/modules/'.$module.'/templates/default')))
                        self::$helper->tpl()->setPath(CAT_Helper_Directory::sanitizePath(CAT_ENGINE_PATH.'/modules/'.$module.'/templates/default'));
                    if (file_exists(CAT_Helper_Directory::sanitizePath(CAT_ENGINE_PATH.'/modules/'.$module.'/templates/'.DEFAULT_TEMPLATE)))
                    {
                        self::$helper->tpl()->setFallbackPath(CAT_Helper_Directory::sanitizePath(CAT_ENGINE_PATH.'/modules/'.$module.'/templates/default'));
                        self::$helper->tpl()->setPath(CAT_Helper_Directory::sanitizePath(CAT_ENGINE_PATH.'/modules/'.$module.'/templates/'.DEFAULT_TEMPLATE));
                    }

                    // fetch original content
                    ob_start();
                        require $handler;
                        $output[] = ob_get_clean();
                }
                else
                {
                    self::log()->addError(sprintf('non existing module [%s] (or no view.php), called on page [%d], block [%d]',$module,$page_id,$block));
                }
            }
            echo implode("\n", $output);
        }   // end function getPageContent()

        /**
         * initialize current page
         **/
        final private static function init($page_id)
        {
            self::$curr_page = $page_id;
        }   // end function init()

        /**
         * Figure out which template to use
         *
         * @access public
         * @return void   sets globals
         **/
        public function setTemplate()
        {
/*
            if(!defined('TEMPLATE'))
            {
                $prop = $this->getProperties();
                // page has it's own template
                if(isset($prop['template']) && $prop['template'] != '') {
                    if(file_exists(CAT_PATH.'/templates/'.$prop['template'].'/index.php')) {
                        CAT_Registry::register('TEMPLATE', $prop['template'], true);
                    } else {
                        CAT_Registry::register('TEMPLATE', DEFAULT_TEMPLATE, true);
                    }
                // use global default
                } else {
                    CAT_Registry::register('TEMPLATE', DEFAULT_TEMPLATE, true);
                }
            }
            $dir = '/templates/'.TEMPLATE;
            // Set the template dir (which is, in fact, the URL, but for backward
            // compatibility, we have to keep this irritating name)
            CAT_Registry::register('TEMPLATE_DIR', CAT_URL.$dir, true);
            // This is the REAL dir
            CAT_Registry::register('CAT_TEMPLATE_DIR', CAT_PATH.$dir, true);
*/
        }   // end function setTemplate()

        /**
         * shows the current page
         *
         * @access public
         * @return void
         **/
        public function show()
        {
            // send appropriate header
            if(CAT_Frontend::isMaintenance() || CAT_Registry::get('maintenance_page') == $this->page_id)
            {
                $this->log()->addDebug('Maintenance mode is enabled');
                header('HTTP/1.1 503 Service Temporarily Unavailable');
                header('Status: 503 Service Temporarily Unavailable');
                header('Retry-After: 7200'); // in seconds
            }

            $this->setTemplate();

            // including the template; it may calls different functions
            // like page_content() etc.
            $this->log()->addDebug('including template');

            ob_start();
                require CAT_TEMPLATE_DIR.'/index.php';
                $output = ob_get_contents();
            ob_clean();

            echo $output;
        }

    } // end class CAT_Page

}
