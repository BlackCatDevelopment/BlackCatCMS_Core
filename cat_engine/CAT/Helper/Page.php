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

if (!class_exists('CAT_Helper_Page'))
{
    if (!class_exists('CAT_Object', false))
    {
        @include dirname(__FILE__) . '/../Object.php';
    }

    class CAT_Helper_Page extends CAT_Object
    {
        protected static $loglevel            = \Monolog\Logger::EMERGENCY;
        private   static $instance            = NULL;

        private   static $meta_tpl            = '<meta %%content%% />';
        private   static $css_tpl             = '<link rel="stylesheet" href="%%file%%" media="%%media%%" />';
        private   static $js_tpl              = '<script type="text/javascript" src="%%file%%">%%code%%</script>';

        private   static $pages               = array();
        private   static $id_to_index         = array();
        private   static $pages_sections      = array();

        private   static $jquery_enabled      = false;
        private   static $jquery_seen         = false;
        private   static $jquery_ui_enabled   = false;
        private   static $jquery_ui_seen      = false;

        private   static $scan_paths          = array();
        // header js files
        private   static $js                  = array();
        // footer js files
        private   static $f_js                = array();
        // header static js
        private   static $header_js           = array();

        private   static $meta                = array();
        private   static $css                 = array();

        /**
         * the constructor loads the available pages from the DB and stores it
         * in internal arrays
         *
         * @access private
         * @return void
         **/
        public static function getInstance($skip_init=false)
        {
            if (!self::$instance)
            {
                self::$instance = new self();
                if(!$skip_init) self::init();
            }
            return self::$instance;
        }   // end function getInstance()

        /**
         * allow methods to be called as object
         **/
        public function __call($method, $args)
        {
            if ( ! isset($this) || ! is_object($this) )
                return false;
            if ( method_exists( $this, $method ) )
                return call_user_func_array(array($this, $method), $args);
        }

        /**
         * allows to add a JS file programmatically
         *
         * @access public
         * @param  string  $url
         * @param  string  $pos   - 'header' (default) or 'footer'
         * @return void
         **/
        public static function addJS($url,$position='header')
        {
            if ($position == 'header')
                $ref =& CAT_Helper_Page::$js;
            else
                $ref =& CAT_Helper_Page::$f_js;
            $ref[] = $url;
        }   // end function addJS()

        /**
         * checks if a page exists; checks access file and database entry
         *
         * @access public
         * @return
         **/
        public static function exists($id)
        {
            // search by ID
            if(is_numeric($id))
            {
                $page = self::properties($id);
                if($page && is_array($page) && count($page))
                    return true;
            }
            else
            {
                $sth = self::$instance->db()->query(
                    "SELECT `page_id` FROM `:prefix:pages` WHERE link=:link",
                    array('link'=>$id)
                );
                if ($sth->rowCount() > 0)
                    return true;
            }
            return false;
        }   // end function exists()

        /**
         * load headers|footers.inc.php
         *
         * @access public
         * @param  string  $for      - frontend (default) or backend
         * @param  string  $position - header (default) or footer
         * @param  string  $section  - optional
         * @return mixed
         **/
        public static function getAssets($position='header',$section=NULL)
        {
            $self  = self::$instance;

            // check params
            if(!in_array($position,array('header','footer')))
                $self::printFatalError('Invalid data!');

            // don't do this twice
            if ($position=='header' && defined('CAT_HEADERS_SENT'))
                return;

            // find the paths to scan
            if(CAT_Backend::isBackend())
            {
                array_push(self::$scan_paths,CAT_ENGINE_PATH.'/templates/'.DEFAULT_THEME);
                if($self->router()->match('~\/tool\/~i'))
                {
                    $route   = $self->router()->getRoute();
                    $tool    = (explode('/',$route))[-1];
                    array_push(self::$scan_paths,CAT_ENGINE_PATH.'/modules/'.$tool);
                }
            }
            else
            {
                array_push(self::$scan_paths,CAT_ENGINE_PATH.'/templates/'.DEFAULT_TEMPLATE);
            }

            // sections
            $page_id = CAT_Page::getID();
            if($page_id)
            {
                $sections = self::getSections($page_id);
                if(count($sections))
                {
                    foreach($sections as $block => $items)
                    {
                        foreach($items as $item)
                        {
                            array_push(self::$scan_paths,CAT_ENGINE_PATH.'/modules/'.$item['module']);
                        }
                    }
                }
            }

            // load *.inc.php
            foreach(array_values(self::$scan_paths) as $path)
            {
                $file = CAT_Helper_Directory::sanitizePath($path.'/'.$position.'s.inc.php');
                if(file_exists($file))
                {
                    self::getIncludes($file,$position);
                }
            }

            $output = '';
            if ($position=='header')
            {
                define('CAT_HEADERS_SENT',true);
                $output = self::renderMeta()
                        . self::renderCSS();
            }
            $output .= self::renderJS($position);
            return $output;
        }   // end function getAssets()

        /**
         * get properties for page $page_id
         *
         * @access public
         * @param  integer  $page_id
         * @param  string   $type
         * @param  string   $key
         * @return
         **/
        public static function getPageSettings($page_id,$type='internal',$key=NULL)
        {
            $set = self::properties($page_id,'settings');
            if($type)
            {
                if($key)
                {
                    if( isset($set[$type][$key]) )
                    {
                        if(is_array($set[$type][$key]) && count($set[$type][$key]) == 1)
                            return $set[$type][$key][0];
                        return $set[$type][$key];
                    }
                    else
                    {
                        return NULL;
                    }
                }
                else
                {
                    return ( isset($set[$type]) ? $set[$type] : NULL );
                }
            }
            return $set;
        }   // end function getPageSettings()

        /**
         * returns complete pages array
         *
         * @access public
         * @param  boolean $all - show all page or only visible (default:false)
         * @return array
         **/
        public static function getPages($all=false)
        {
            if(!count(self::$pages)) self::getInstance();
            if($all)
                return self::$pages;
            // only visible for current lang
            $pages = array();
            foreach(self::$pages as $pg)
                if(self::isVisible($pg['page_id']))
                    $pages[] = $pg;
            return $pages;
        }   // end function getPages()

        /**
         * returns pages array for given menu number
         *
         * @access public
         * @param  integer  $id    - menu id
         * @return array
         **/
        public static function getPagesForMenu($id)
        {
            if(!count(self::$pages)) self::getInstance();
            $menu = array();
            foreach(self::$pages as $pg)
            {
                if( $pg['menu'] == $id && self::isVisible($pg['page_id']) )
                    $menu[] = $pg;
            }
            return $menu;
        }   // end function getPagesForMenu()

        /**
         * returns the template for the given page
         *
         * @access public
         * @param  integer  $page_id
         * @return string
         **/
        public static function getPageTemplate($page_id)
        {
            $tpl = self::properties($page_id,'template');
            return ( $tpl != '' ) ? $tpl : DEFAULT_TEMPLATE;
        }   // end function getPageTemplate()

        /**
         * get the path of the given page
         *
         * @access public
         * @param  integer  $page_id
         * @param  boolean  $skip_zero
         * @param  boolean  $as_array
         * @return mixed
         **/
        public static function getPageTrail($page_id,$skip_zero=false,$as_array=false)
        {
            $ids = array_reverse(self::getParentIDs($page_id));
            if($skip_zero) array_shift($ids);
            $ids[] = $page_id;
            return (
                $as_array ? $ids : implode(',',$ids)
            );
        }   // end function getPageTrail()

        /**
         * resolves the path to root and returns the list of parent IDs
         *
         * @access public
         * @return
         **/
        public static function getParentIDs($page_id)
        {
            $ids = array();
            while ( self::properties($page_id,'parent') !== NULL )
            {
                if ( self::properties($page_id,'level') == 0 )
                    break;
                $ids[]   = self::properties($page_id,'parent');
                $page_id = self::properties($page_id,'parent');
            }
            return $ids;
        }   // end function getParentIDs()

        /**
         * returns the sections of a page
         *
         * to get all sections of all pages, leave param empty
         *
         * @access public
         * @param  integer  $page_id
         * @return array
         **/
        public static function getSections($page_id=NULL)
        {
            if(!count(self::$pages)) self::getInstance();
            if(!count(self::$pages_sections))
                self::$pages_sections = CAT_Sections::getActiveSections();

            if($page_id)
                return
                      isset(self::$pages_sections[$page_id])
                    ? self::$pages_sections[$page_id]
                    : array();
                else
                    return self::$pages_sections;
        }   // end function getSections()

        /**
         * Check whether a page is visible or not
         * This will check page-visibility, user- and group permissions
         *
         * @access public
         * @param  integer  $page_id
         * @return boolean
         **/
        public static function isVisible($page_id)
        {
            $show_it = false;
            $page    = self::properties($page_id);

            switch ($page['visibility'])
            {
                // never shown in FE
                case 'none':
                case 'deleted':
                    $show_it = false;
                    break;
                // shown if called, but not in menu; skip intro page (selectPage(true))
                case 'hidden':
#                    if(self::selectPage(true)==$page_id)
                        $show_it = true;
                    break;
                // always visible
                case 'public':
                    $show_it = true;
                    break;
                // shown if user is allowed
                case 'private':
                case 'registered':
                    if (CAT_User::getInstance()->is_authenticated() == true)
                    {
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// TODO: ANPASSEN FUER NEUES BERECHTIGUNGSZEUGS
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
/*
                        // check language
                        if(CAT_Registry::get('PAGE_LANGUAGES')=='false'||(self::properties($page_id,'language')==''||self::properties($page_id,'language')==LANGUAGE))
                        $show_it = (
                               CAT_Users::is_group_match(CAT_Users::get_groups_id(), $page['viewing_groups'])
                            || CAT_Users::is_group_match(CAT_Users::get_user_id(), $page['viewing_users'])
                            || CAT_Users::is_root()
                        );
*/
                    }
                    else
                    {
                        $show_it = false;
                    }
                    break;
            }
            return $show_it;
        } // end function isVisible()

        /**
         * returns the properties for the given page ID
         *
         * @access public
         * @param  integer $page_id
         * @param  string  $key      - optional property name
         * @return mixed
         **/
        public static function properties($page_id,$key=NULL)
        {
            if(!$page_id)
                return NULL;
            if(!count(self::$pages) && !CAT_Registry::exists('CAT_HELPER_PAGE_INITIALIZED'))
                self::init();
            // get page data
            $page = isset(self::$id_to_index[$page_id])
                  ? self::$pages[self::$id_to_index[$page_id]]
                  : array();
            if(count($page))
            {
                if($key)
                {
                    if(isset($page[$key]))
                    return $page[$key];
                    else
                        return NULL;
                }
                else
                {
                    return $page;
                }
            }
            return array();
        }   // end function properties()

        /**
         * returns the items of static array $css as HTML link markups
         *
         * @access public
         * @return HTML
         **/
        public static function renderCSS($as_array=false)
        {
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// TODO: Conditionals
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            $output = array();
            if(count(self::$css))
            {
                foreach(array_keys(self::$css) as $media)
                {
                    $line = str_replace(
                        array('%%file%%','%%media%%'),
                        array(CAT_URL.'/css?files='.(implode(',',self::$css[$media])),$media),
                        self::$css_tpl
                    );
                    $output[] = $line;
                }
            }
            return implode("\n",$output);
        }   // end function renderCSS()

        /**
         *
         * @access public
         * @return
         **/
        public static function renderJS($position='header')
        {
            // reference the appropriate JS array (header or footer)
            if ($position == 'header')
            {
                $ref =& self::$js;
                $js  = ( CAT_Backend::isBackend() ? 'backend' : 'frontend' );
            }
            else
            {
                $ref =& self::$f_js;
                $js  = ( CAT_Backend::isBackend() ? 'backend' : 'frontend' ).'_body';
            }

            self::$scan_paths = array_unique(self::$scan_paths);

            foreach (self::$scan_paths as $directory)
            {
                foreach(array_values(array('js','')) as $subdir)
                {
                    $file = CAT_Helper_Directory::sanitizePath($directory.'/'.$subdir.'/'.$js.'.js');
                    if (file_exists($file))
                    {
                        $ref[] = CAT_Helper_Validate::path2uri($file);
                    }
                }
            }

            $ref = array_unique($ref);

            $output = str_replace(
                array('%%file%%','%%code%%'),
                array(CAT_URL.'/js?files='.implode(',',$ref),''),
                self::$js_tpl
            );

            if($position=='header')
            {
                // add static js
                self::$header_js[] = 'var CAT_URL = "'.CAT_URL.'";';
                if(CAT_Backend::isBackend())
                {
                    array_push(
                        self::$header_js,
                        #'var CAT_THEME_URL = "'.CAT_THEME_URL. '";',
	                    'var CAT_ADMIN_URL = "'.CAT_ADMIN_URL. '";'
                    );
                }
                $output = str_replace(
                    array(' src="%%file%%"','%%code%%'),
                    array('',implode("\n",self::$header_js)),
                    self::$js_tpl
                ) . $output;
            }

            return $output;
        }   // end function renderJS()

        /**
         *
         * @access public
         * @return
         **/
        public static function renderMeta($droplets_config=array())
        {
            $output = array();

            // Frontend only: get page properties
            if(!CAT_Backend::isBackend())
            {
                $properties = self::properties(CAT_Page::getID());
                // droplets may override page title and description and/or
                // add meta tags

                // check page title
                if(isset($droplets_config['page_title']))
                    $title = $droplets_config['page_title'];
                elseif(defined('WEBSITE_TITLE'))
                    $title = WEBSITE_TITLE . (isset($properties['page_title']) ? ' - ' . $properties['page_title'] : '' );
                elseif(isset($properties['page_title']))
                    $title = $properties['page_title'];
                else
                    $title = '-';
                if($title)
                    $output[] = '<title>' . $title . '</title>';

                // check description
                if(isset($droplets_config['description']))
                    $description = $droplets_config['description'];
                elseif(isset($properties['description']) && $properties['description'] != '' )
                    $description = $properties['description'];
                else
                    $description = CAT_Registry::get('WEBSITE_DESCRIPTION');
                if ($description!='')
                    $output[] = '<meta name="description" content="' . $description . '" />';

                // check other meta tags set by droplets
                if(isset($droplets_config['meta']))
                    $output[] = $droplets_config['meta'];

// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// TODO: SEO
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            }

// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// TODO:
/*
    <link rel="apple-touch-icon" href="/bootstrap/img/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/bootstrap/img/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/bootstrap/img/apple-touch-icon-114x114.png">
*/
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

            // if the current page has linked pages in other languages
            $linked = array();
            //$linked = self::getLinkedByLanguage($page_id);
            if(is_array($linked) && count($linked))
            {
                foreach($linked as $page)
                {
                    $output[] = '<link rel="alternate" hreflang="'.strtolower($page['lang']).'" href="'.self::getLink($page['page_id']).'">';
                }
            }

            return implode("\n",array_unique($output));
        } // end function renderMeta()

        /**
         *
         * @access private
         * @return
         **/
        private static function checkPath($path)
        {
            // check for internal path, f.e. /CAT/Backend/js/session.js
            if(!substr_compare($path,'/CAT/',0,5,true))
                return $path;
            $path  = preg_replace('~^/~','',$path); // remove leading /
            $parts = explode('/',$path);
            if(in_array($parts[0],array('templates','modules')))
            {
                //array_shift($parts); // remove?
                return CAT_Helper_Directory::sanitizePath(implode('/',$parts));
            }
            if(preg_match('~^https?\:~i',$path))
                return CAT_Helper_Validate::sanitize_url($path);
            return false;
        }   // end function checkPath()
        
        /**
         * evaluate correct item path; this resolves
         *    ./plugins/<name>.min.js
         *    ./plugins/<name>.js
         *    ./plugins/<name>/<name>.min.js
         *    ./plugins/<name>/<name>.js
         *
         * @access private
         * @param  string  $item
         * @return mixed
         **/
        private static function findJQueryPlugin($item)
        {
            $plugin_path = '/modules/lib_jquery/plugins';
            // check suffix
            if(pathinfo($item,PATHINFO_EXTENSION) != 'js')
                $item .= '.js';

            // prefer minimized
            $minitem = pathinfo($item,PATHINFO_FILENAME).'.min.js';

            // just there?
            if (!file_exists(CAT_Helper_Directory::sanitizePath(CAT_ENGINE_PATH.'/'.$plugin_path.'/'.$minitem)))
            {
                if (!file_exists(CAT_Helper_Directory::sanitizePath(CAT_ENGINE_PATH.'/'.$plugin_path.'/'.$item)))
                {
                    $dir = pathinfo($item,PATHINFO_FILENAME);
                    // prefer minimized
                    $minitem = pathinfo($item,PATHINFO_FILENAME).'.min.js';
                    if (file_exists(CAT_Helper_Directory::sanitizePath(CAT_ENGINE_PATH.'/'.$plugin_path.'/'.$dir.'/'.$minitem)))
                        return $plugin_path.'/'.$dir.'/'.$minitem;
                    if (file_exists(CAT_Helper_Directory::sanitizePath(CAT_ENGINE_PATH.'/'.$plugin_path.'/'.$dir.'/'.$item)))
                        return $plugin_path.'/'.$dir.'/'.$item;
                }
            }
            else
            {
                return $item;
            }
            return false;
        }   // end function findJQueryPlugin()

        /**
         * analyzes the contents of the headers.inc.php
         *
         * @access private
         * @param  string  $file - path to headers.inc.php
         * @param  string  $for  - frontend | backend
         * @return void
         **/
        private static function getIncludes($file,$position='header')
        {
            // load file
            require $file;

            $array   =& ${'mod_'.$position.'s'};
            $for     =  ( CAT_Backend::isBackend() ? 'backend' : 'frontend' );
            $page_id =  CAT_Page::getID();

            // if there are any entries...
            if (isset($array[$for]) && is_array($array[$for]) && count($array[$for]))
            {

                // reference the appropriate JS array (header or footer)
                if ($position == 'header') $ref =& self::$js;
                else                       $ref =& self::$f_js;

                if($position=='header') // header only
                {
                    // ----- check META -----
                    if(    isset($array[$for]['meta'])
                        && is_array($array[$for]['meta'])
                        && count($array[$for]['meta'])
                    ) {
                        $arr =& $array[$for]['meta']; // shorter :)
                        foreach($arr as $el)
                        {
                            if(!is_array($el) || !count($el)) continue;
                            $str = '';
                            foreach($el as $key => $val)
                                $str .= $key.'="'.$val.'" ';

                            self::$meta[] = str_replace(
                                '%%content%%',
                                $str,
                                self::$meta_tpl
                            );
                        }
                    }
                    // ----- check CSS -----
                    if(isset($array[$for]['css']) && is_array($array[$for]['css']) && count($array[$for]['css']))
                    {
                        // check the paths
                        foreach($array[$for]['css'] as $item)
                        {
                            if(isset($item['file'])) // skip invalid entries
                            {
                                $media = (isset($item['media']) ? $item['media'] : 'screen,projection');
                                if(!isset(self::$css[$media])) self::$css[$media] = array();
                                if(false!==($file=self::checkPath($item['file'])))
                                {
                                    if(isset($item['conditional']) && $item['conditional'] != '')
                                    {
                                        $file = '<!--[if '.$item['conditional'].']>'."\n"
                                              . $file
                                              . '<![endif]-->'."\n"
                                              ;
                                    }
                                    self::$css[$media][] = $file;
                                }
                            }
                        }
                    }
                }   // end if($position=='header')

                // ----- check jQuery components -----
                if (isset($array[$for]['jquery']) && is_array($array[$for]['jquery']) && count($array[$for]['jquery']) && !self::$jquery_seen)
                {
                    $arr = $array[$for]['jquery']; // shorter :)
                    // scan for plugins
                    if (isset($arr['plugins']) && is_array($arr['plugins']))
                    {
                        foreach ($arr['plugins'] as $item)
                        {
                            if(false!==($file=self::findJQueryPlugin($item)))
                            {
                                $ref[] = $file;
                            }
                        }
                    }
                    if(
                           isset($arr['ui'])
                        && $arr['ui'] === true
                    ) {
                        self::$jquery_ui_enabled = true;
                        self::$jquery_enabled = true;
                    }
                    if(
                           !self::$jquery_enabled && count($ref)
                        || (isset($arr['core']) && $arr['core'] === true)
                    ) {
                        self::$jquery_enabled = true;
                    }

                }   // end if (isset($array[$for]['jquery']))

                // ----- other JS -----
                if(isset($array[$for]['js']) && is_array($array[$for]['js']) && count($array[$for]['js']))
                {
                    $temp_arr = ( is_array($array[$for]['js'][0]) ? $array[$for]['js'][0] : $array[$for]['js'] );
                    foreach(array_values($temp_arr) as $item)
                    {
                        if(false!==($file=self::checkPath($item)))
                        {
                            $ref[] = $file;
                        }
                    }
                }   // end if(isset($array[$for]['js']))

                if(self::$jquery_ui_enabled && !self::$jquery_ui_seen)
                {
                    array_unshift($ref,'/modules/lib_jquery/jquery-ui/ui/jquery-ui.min.js');
                    array_unshift($ref,'/modules/lib_jquery/jquery-ui/ui/i18n/jquery-ui-i18n.min.js');
                    self::$jquery_ui_seen = true;
                }
                if(self::$jquery_enabled && !self::$jquery_seen)
                {
                    array_unshift($ref,'/modules/lib_jquery/jquery-core/jquery-core.min.js');
                    self::$jquery_seen = true;
                }
            }
            else
            {
                self::$instance->log()->addDebug(sprintf('no $array for [%s]',$for));
            }

            return ( isset($ref) ? $ref : false );
        }   // end function getIncludes()

        /**
         * initialize; fills the internal pages array
         *
         * @access private
         * @param  boolean $force - always reload
         * @return void
         **/
        private static function init($force=false)
        {
            if(CAT_Registry::exists('CAT_HELPER_PAGE_INITIALIZED') && !$force)
                return;

            if(!self::$instance) self::getInstance(true);

            // fill pages array
            if(count(self::$pages)==0 || $force)
            {
                $result = self::$instance->db()->query(
                    'SELECT * FROM `:prefix:pages` ORDER BY `level` ASC, `position` ASC'
                );
                self::$pages = $result->fetchAll();
                // map index to page id
                foreach(self::$pages as $index => $page)
                    self::$id_to_index[$page['page_id']] = $index;
            }

            CAT_Registry::register('CAT_HELPER_PAGE_INITIALIZED',true);
        }   // end function init()

    }   // end class CAT_Helper_Page
}
