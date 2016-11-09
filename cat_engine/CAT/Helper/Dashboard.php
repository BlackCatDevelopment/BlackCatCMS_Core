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

if (!class_exists('CAT_Helper_Dashboard'))
{
    class CAT_Helper_Dashboard extends CAT_Object
    {
        private   static $instance;
        //protected static $loglevel = \Monolog\Logger::EMERGENCY;
        protected static $loglevel = \Monolog\Logger::DEBUG;

        public static function getInstance()
        {
            if (!self::$instance)
                self::$instance = new self();
            return self::$instance;
        }   // end function getInstance()

        /**
         *
         * @access public
         * @return
         **/
        public static function addWidget($id,$dash)
        {
            $self = self::getInstance();
            // check if widget exists
            if(CAT_Helper_Widget::exists($id))
            {
                // check if widget is already an the dashboard
                $sth = $self->db()->query(
                      'SELECT * FROM `:prefix:dashboard_has_widgets` AS t1 '
                    . 'WHERE `t1`.`dashboard_id`=? '
                    . 'AND `t1`.`widget_id`=?',
                    array($dash,$id)
                );
                if(!$sth->rowCount())
                {
                    $widget = CAT_Helper_Widget::getWidget($id);
                    $pos    = $self->db()->query(
                        'SELECT max(`position`) AS `position` FROM `:prefix:dashboard_has_widgets`'
                    )->fetch();

                    $position = ( $pos['position'] > 0 )
                              ? $pos['position'] +1
                              : 1;

                    $self->db()->query(
                          'INSERT INTO `:prefix:dashboard_has_widgets` '
                        . '(`dashboard_id`,`widget_id`,`column`,`position`) '
                        . 'VALUES(?,?,?,?)',
                        array($dash,$id,$widget['preferred_column'],$position)
                    );
                }
            }
        }   // end function addWidget()
        

        /**
         * checks if a dashboard exists; $dash can be an ID (integer) or
         * dashboard path (string)
         *
         * @access public
         * @param  mixed  $dash
         * @return boolean
         **/
        public static function exists($dash)
        {
            $sql = 'SELECT `id` FROM `:prefix:dashboards` WHERE ';
            if(is_numeric($dash)) $sql .= '`id`=?';
            else                  $sql .= '`path`=?';
            $sth  = self::getInstance()->db()->query(
                 $sql,array($dash)
            );
            $data = $sth->fetch();
            if(is_array($data) && isset($data['id'])) return true;
            else                                      return false;
        }   // end function exists()

        /**
         *
         * @access public
         * @return
         **/
        public static function getDashboard($path)
        {
            $config = self::getDashboardConfig($path);
            if(!headers_sent())
                header('Content-type: application/json');
            echo json_encode($config);
            return;
        }   // end function getDashboard()

        /**
         * returns the dashboard configuration; uses the ID of the currently
         * logged in user to find the dashboard
         *
         * if no $path is given, will try to resolve the dashboard path from
         * the current route
         *
         * @access public
         * @param  string  $path (optional) - example: backend/dashboard
         * @return array
         **/
        public static function getDashboardConfig($path=NULL)
        {
            $self = self::getInstance();
            if(!$path) $path = $self->router()->getRoute(); // global
            $sql  = 'SELECT `id`, `columns` FROM `:prefix:dashboards` WHERE `user_id`=? AND `path`=?';
            $sth  = self::getInstance()->db()->query(
                 $sql, array(self::getInstance()->user()->get('user_id'),$path)
            );
            $config = $sth->fetch();
            return $config;
        }   // end function getDashboardConfig()

        /**
         * gets dashboard ID by path; if no $path is passed, the current route
         * is used
         *
         * @access public
         * @param  string - $path (optional)
         * @return integer
         **/
        public static function getDashboardID($path=NULL)
        {
            $self = self::getInstance();
            if(!$path)
                $path = $self->router()->getRoute();
            $dash = self::getID($self->user()->getID(),$path);
            return $dash;
        }   // end function getDashboardID()

        /**
         * gets dashboard ID by given user ID and path (route)
         *
         * @access public
         * @param  integer  $user
         * @param  string   $path
         * @return mixed    dashboard ID or NULL
         **/
        public static function getID($user,$path)
        {
            $self = self::getInstance();
            $sth  = $self->db()->query(
                'SELECT `id` FROM `:prefix:dashboards` WHERE `user_id`=? AND `path`=?',
                array($user,$path)
            );
            $data = $sth->fetch();
            if(is_array($data) && count($data) &&  isset($data['id']))
                return $data['id'];
            else
                return NULL;
        }   // end function getID()
        
        /**
         * gets the widgets for a dashboard
         *
         * @access public
         * @param  integer  $dash - dashboard ID
         * @return array
         **/
        public static function getWidgets($dash)
        {
            $sql  = 'SELECT * FROM `:prefix:dashboard_has_widgets` AS `t1` '
                  . 'JOIN `:prefix:dashboard_widgets` AS `t2` '
                  . 'ON `t1`.`widget_id`=`t2`.`widget_id` '
                  . 'WHERE `t1`.`dashboard_id`=?';
            $sth  = self::getInstance()->db()->query(
                 $sql, array($dash)
            );
            $widgets = $sth->fetchAll();
            return $widgets;
        }   // end function getWidgets()

        /**
         *
         * @access public
         * @return
         **/
        public static function removeWidget($id,$dash)
        {
            $self = self::getInstance();
            // check if widget exists
            if(CAT_Helper_Widget::exists($id))
            {
                $self->db()->query(
                      'DELETE FROM `:prefix:dashboard_has_widgets` '
                    . 'WHERE `widget_id`=? AND `dashboard_id`=?',
                    array($id,$dash)
                );
            }
        }   // end function removeWidget()

        /**
         *
         * @access public
         * @return
         **/
        public static function renderDashboard($id)
        {
            // get widgets
            $widgets = self::getWidgets($id);
            if(is_array($widgets) && count($widgets))
            {
                foreach($widgets as $i => $w)
                {
                    $widget_settings = NULL;
                    // script based widgets
                    if(file_exists(CAT_ENGINE_PATH.$w['widget_controller']))
                    {
                        include CAT_ENGINE_PATH.$w['widget_controller'];
                        $name     = pathinfo($w['widget_controller'],PATHINFO_FILENAME);
                        $funcname = 'render_widget_blackcat_'.$name;
                        $widgets[$i]['content'] = $funcname();
                        if(is_array($widget_settings) && isset($widget_settings['widget_title']))
                        {
                            $widgets[$i]['widget_title'] = $widget_settings['widget_title'];
                        }
                    }
                }
            }
            return $widgets;
        }   // end function renderDashboard()

        /**
         *
         * @access public
         * @return
         **/
        public static function saveDashboardConfig($id,$user,$path,$cols)
        {
            $self = self::getInstance();
            if(!self::exists($id))
            {
                $sql = 'INSERT INTO `:prefix:dashboards` ( `user_id`, `path`, `columns` ) VALUES (?,?,?)';
                $sth = $self->db()->query(
                    $sql, array($user,$path,$cols)
                );
            }
            else
            {
                $sql = 'UPDATE `:prefix:dashboards` SET `columns`=? WHERE `user_id`=? AND `path`=?';
                $sth = $self->db()->query(
                    $sql, array($cols,$user,$path)
                );
            }
        }   // end function saveDashboardConfig()
        

    } // class CAT_Helper_Dashboard

} // if class_exists()