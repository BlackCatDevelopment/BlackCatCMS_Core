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

if (!class_exists('CAT_Backend_Page'))
{
    if (!class_exists('CAT_Object', false))
    {
        @include dirname(__FILE__) . '/../../Object.php';
    }

    class CAT_Backend_Page extends CAT_Object
    {
        protected static $loglevel = \Monolog\Logger::EMERGENCY;
        protected static $instance = NULL;

        /**
         *
         * @access public
         * @return
         **/
        public static function getInstance()
        {
            if(!is_object(self::$instance))
                self::$instance = new self();
            return self::$instance;
        }   // end function getInstance()

        /**
         * returns the pages list, sorted by parent -> children -> position
         *
         * @access public
         * @return
         **/
        public static function index()
        {
            // note: the access rights for index() are checked by the Backend
            // class, so there's no need to do it here
            $self  = self::getInstance();
            $pages = self::list();
            // sort pages by children
            $tpl_data = array(
                'pages' => \wblib\wbList::sort($pages,0),
            );
            CAT_Backend::print_header();
            $self->tpl()->output('backend_pages', $tpl_data);
            CAT_Backend::print_footer();
        }   // end function index()

        /**
         *
         * @access public
         * @return
         **/
        public static function edit()
        {
            $self    = self::getInstance();
            $page_id = $self->router()->getParam(-1);

            if(!$page_id || !is_numeric($page_id) || !CAT_Helper_Page::exists($page_id))
                CAT_Object::printFatalError('Invalid data');

            // the user needs to have the global pages_edit permission plus
            // permissions for the current page
            if(!$self->user()->hasPerm('pages_edit') || !$self->user()->hasPagePerm($page_id,'pages_edit'))
                CAT_Object::printFatalError('You are not allowed for the requested action!');

            $curr_tpl   = CAT_Helper_Page::getPageTemplate($page_id);
            $page       = CAT_Page::getInstance($page_id);
            $sections   = CAT_Helper_Page::getSections($page_id);
            $blockcount = 0;
            $tpl_data   = array(
                'meta'      => array(
                    'menus'     => CAT_Helper_Template::get_template_menus($curr_tpl),
                    'templates' => CAT_Helper_Addons::get_addons('template','template'),
                    'languages' => CAT_Helper_I18n::getLanguages(),
                    'variants'  => CAT_Helper_Template::getVariants($curr_tpl),
                    'pages'     => \wblib\wbList::sort(CAT_Helper_Page::getPages(1),0),
                    'page'      => CAT_Helper_Page::properties($page_id),
                ),
                'blocks'    => array(),
            );

            foreach ($sections as $block => $items)
            {
                foreach($items as $section)
                {
                    $module     = $section['module'];
                    $section_id = $section['section_id'];
                    // silently skip modules the user does not have access to
                    if($self->user()->hasModulePerm($section['module']))
                    {
                        if(file_exists(CAT_ENGINE_PATH.'/modules/'.$module.'/modify.php'))
                        {
                            // catch module output
                            ob_start();
                                require(CAT_ENGINE_PATH.'/modules/'.$module.'/modify.php');
                                $tpl_data['blocks'][] = array(
                                    'meta'    => array_merge(
                                        $section,
                                        array('blockname'=>$self->tpl()->get_template_block_name($curr_tpl, $section['block']))
                                    ),
                                    'content' => ob_get_contents(),
                                );
                            ob_clean(); // allow multiple buffering for csrf-magic
                        }
                        $blockcount++;
                    }
                }
            }

            // if the user is allowed to add sections...
            if($self->user()->hasPerm('pages_add_section'))
            {
                // ...get the list of modules the user has access to
                $available = CAT_Helper_Addons::get_addons(NULL,'module','page');
                $accessible = array();
                if(is_array($available) && count($available))
                {
                    foreach($available as $i => $item)
                    {
                        if($self->user()->hasModulePerm($item['addon_id']))
                        {
                            $accessible[] = $item;
                        }
                    }
                }
                $tpl_data['addons'] = $accessible;
            }

            CAT_Backend::print_header();
            $self->tpl()->output('backend_page_modify', $tpl_data);
            CAT_Backend::print_footer();
        }   // end function edit()

        /**
         *
         *
         *
         *
         **/
        public static function list($recursive=false)
        {
            $self = self::getInstance();
            if(!$self->user()->hasPerm('pages_list'))
                CAT_Object::json_error('You are not allowed for the requested action!');
            # get the page tree
            $pages = CAT_Helper_Page::getPages(true);
            if($recursive)
                $pages = $self->lb()->buildRecursion($pages);
            if(self::asJSON())
            {
                echo header('Content-Type: application/json');
                echo json_encode($pages,true);
                return;
            }
            return $pages;
        }   // end function list()

        /**
         *
         * @access public
         * @return
         **/
        public static function visibility()
        {
            $self = self::getInstance();
            if(!$self->user()->hasPerm('pages_edit'))
                CAT_Object::json_error('You are not allowed for the requested action!');
            $params  = $self->router()->getParams();
            $page_id = $params[0];
            $newval  = $params[1];
            if(!is_numeric($page_id)) {
                CAT_Object::json_error('Invalid value');
            }
            if(!in_array($newval,array('public','private','hidden','none','deleted','registered')))
            {
                CAT_Object::json_error('Invalid value');
            }
            $self->db()->query(
                'UPDATE `:prefix:pages` SET `visibility`=? WHERE `page_id`=?',
                array($newval,$page_id)
            );
            echo CAT_Object::json_result(
                $self->db()->isError(),
                '',
                true
            );
        }   // end function visibility()
        

    } // class CAT_Backend_Page

} // if class_exists()