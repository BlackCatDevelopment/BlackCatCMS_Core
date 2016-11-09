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

if (!class_exists('CAT_Backend_Dashboard'))
{
    if (!class_exists('CAT_Object', false))
    {
        @include dirname(__FILE__) . '/../Object.php';
    }

    class CAT_Backend_Dashboard extends CAT_Object
    {
        protected static $instance = NULL;
        //protected static $loglevel = \Monolog\Logger::EMERGENCY;
        protected static $loglevel = \Monolog\Logger::DEBUG;

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
         *
         * @access public
         * @return
         **/
        public static function add($dash=NULL)
        {
            $self = self::getInstance();
            // validate path
            if(!$dash)
                $dash = CAT_Helper_Dashboard::getDashboardID($self->router()->getParam(-1));
            // check if dashboard exists
            if(!CAT_Helper_Dashboard::exists($dash))
                echo $self::json_error('error');
            $widget = CAT_Helper_Validate::sanitizePost('widget_id');
            CAT_Helper_Dashboard::addWidget($widget,$dash);
            echo $self::json_success('ok');
        }   // end function add()

        /**
         *
         * @access public
         * @return
         **/
        public static function get()
        {
            $self = self::getInstance();
            $page = CAT_Helper_Validate::sanitizePost('page');
            CAT_Helper_Dashboard::getDashboard($page);
        }   // end function getDashboard()
        
        /**
         * show dashboard; if no path is given, will try to resolve the
         * dashboard path from the current route
         *
         * will throw a fatal error if the dashboard does not exist
         *
         * @access public
         * @return
         **/
        public static function index($path=NULL)
        {
            $self = self::getInstance();

            // validate path
            if(!$path)
                $path = $self->router()->getRoute();
            $dash = CAT_Helper_Dashboard::getDashboardID($path);

            // check if dashboard exists
            if(!CAT_Helper_Dashboard::exists($dash))
            {
                if($path)
                {
                    CAT_Helper_Dashboard::saveDashboardConfig(
                        NULL,
                        $self->user()->getID(),
                        $path,
                        2
                    );
                }
                else
                {
                    $self->log()->addAlert(sprintf('No such dashboard! [id: %d; path: %s]',$dash,$path));
                    $self->printFatalError('Access denied');
                }
            }

            // get the template contents
            $tpl_data = array(
                'dashboard' => array_merge(
                    array(
                        'widgets' => CAT_Helper_Dashboard::renderDashboard($dash),
                    ),
                    CAT_Helper_Dashboard::getDashboardConfig($path)
                ),
                'MAIN_MENU' => CAT_Backend::getMainMenu(),
            );

            CAT_Backend::print_header();
            $self->tpl()->output('backend_dashboard', $tpl_data);
            CAT_Backend::print_footer();
        }   // end function index()
        
        /**
         * re-order dashboard widgets
         *
         * @access public
         * @return
         **/
        public static function order()
        {
            $self = self::getInstance();
            $dash = CAT_Helper_Validate::sanitizePost('dashboard');
            $id   = CAT_Helper_Validate::sanitizePost('id');
            $col  = CAT_Helper_Validate::sanitizePost('col');
            $pos  = CAT_Helper_Validate::sanitizePost('row');
            if(!$col>0) $col = 1;
            if(!$pos>0) $pos = 1;
            
            if($dash)
            {
                // update position
                $self->db()->query(
                    'UPDATE `:prefix:dashboard_has_widgets` SET `column`=?, `position`=? WHERE `dashboard_id`=? AND `widget_id`=?',
                    array($col,$pos,$dash,$id)
                );
                $self->log()->addDebug(sprintf(
                    'updated dash [%s] widget [%s] col [%s] pos [%s]',
                    $dash,$id,$col,$pos
                ));
                // update order
                $self->db()->query(
                      'SET @pos := ?; '
                    . 'UPDATE `:prefix:dashboard_has_widgets` '
                    . 'SET `position` = ( SELECT @pos := @pos + 1 ) '
                    . 'WHERE `column`=? AND `position`>? AND `widget_id`!=? AND `dashboard_id`=? '
                    . 'ORDER BY `position` ASC;',
                    array($pos,$col,$pos,$id,$dash)
                );
                // update order
                $self->db()->query(
                      'SET @pos := ?; '
                    . 'UPDATE `:prefix:dashboard_has_widgets` '
                    . 'SET `position` = ( SELECT @pos := @pos - 1 ) '
                    . 'WHERE `column`=? AND `position`<=? AND `widget_id`!=? AND `dashboard_id`=? '
                    . 'ORDER BY `position` ASC;',
                    array($pos,$col,$pos,$id,$dash)
                );
                $result = $self::json_success('ok');
            }
            else {
                $self->log()->addWarn(sprintf('no such dashboard: [%s]',$dash));
                $result = $self::json_error('not ok');
            }

            if(self::asJSON())
            {
                echo header('Content-Type: application/json');
                echo $result;
                return;
            }
        }   // end function order()

        /**
         *
         * @access public
         * @return
         **/
        public static function remove($dash=NULL)
        {
            $self = self::getInstance();
            // validate path
            if(!$dash)
                $dash = CAT_Helper_Dashboard::getDashboardID($self->router()->getParam(-1));
            // check if dashboard exists
            if(!CAT_Helper_Dashboard::exists($dash))
                echo $self::json_error('error');
            $widget = CAT_Helper_Validate::sanitizePost('widget_id');
            CAT_Helper_Dashboard::removeWidget($widget,$dash);
            echo $self::json_success('ok');
        }   // end function remove()

        /**
         *
         * @access public
         * @return
         **/
        public static function toggle()
        {
            $self = self::getInstance();
            $id   = CAT_Helper_Validate::sanitizePost('id');
            $vis  = CAT_Helper_Validate::sanitizePost('vis');
            $dash = CAT_Helper_Validate::sanitizePost('dashboard');
            if($dash)
            {
                $self->db()->query(
                    'UPDATE `:prefix:dashboard_has_widgets` SET `open`=? WHERE `dashboard_id`=? AND `widget_id`=?',
                    array($vis,$dash,$id)
                );
                $result = $self::json_success('ok');
            }
            else {
                $result = $self::json_error('not ok');
            }
            if(self::asJSON())
            {
                echo header('Content-Type: application/json');
                echo $result;
                return;
            }
        }   // end function toggle()

        /**
         * returns a list of widgets that are not already on the current
         * dashboard
         *
         * @access public
         * @param  mixed  $dash - id or dashboard path
         * @return mixed
         **/
        public static function widgets($dash=NULL)
        {
            $self = self::getInstance();
            // validate path
            if(!$dash)
                $dash = CAT_Helper_Dashboard::getDashboardID($self->router()->getParam(-1));
            // check if dashboard exists
            if(!CAT_Helper_Dashboard::exists($dash))
                echo $self::json_error('error');
            // get list of widgets the user is allowed to see
            $all  = CAT_Helper_Widget::getAllowed();
            // get list of widgets already an the dashboard
            $vis  = CAT_Helper_Dashboard::renderDashboard($dash);
            // filter array $all
            $diff = array_diff(array_column($all,'widget_id'),array_column($vis,'widget_id'));

            $result = array_filter(
                $all,
                function ($e) use($diff) {
                    return (
                        in_array($e['widget_id'],$diff)
                        ? true
                        : false
                    );
                }
            );
            echo $self::json_success(array_values($result));
        }   // end function widgets()
        
        
    } // class CAT_Helper_Dashboard

} // if class_exists()