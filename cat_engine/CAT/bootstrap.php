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

if(!defined('CAT_ENGINE_PATH')) die;

define('CAT_BACKEND_FOLDER','backend');

// Composer autoloader
require __DIR__ . '/vendor/autoload.php';

// we require UTF-8
ini_set('default_charset','UTF-8');

//******************************************************************************
// register autoloader
//******************************************************************************
spl_autoload_register(function($class)
{
#echo "autoloading class -$class-<br />";
    if(!substr_compare($class, 'wblib', 0, 4)) // wblib2 components
    {
        $file = str_replace(
            '\\',
            '/',
            CAT_Helper_Directory::sanitizePath(
                CAT_ENGINE_PATH.'/modules/lib_wblib/'.str_replace(
                    array('\\','_'),
                    array('/','/'),
                    $class
                ).'.php'
            )
        );
        if (file_exists($file))
            @require $file;
    }
    else                                       // BC components
    {
        $file = '/'.str_replace('_', '/', $class);
        $file = CAT_ENGINE_PATH.'/'.$file.'.php';
        if (file_exists($file))
            @require_once $file;
    }
    // next in stack
});

//******************************************************************************
// Get website settings and register as globals
//******************************************************************************
$sql = 'SELECT `name`, `value` FROM `:prefix:settings` ORDER BY `name`';
if (($result = CAT_Helper_DB::getInstance()->query($sql)) && ($result->rowCount() > 0))
{
    while (false != ($row = $result->fetch()))
    {
        if (preg_match('/^[0-7]{1,4}$/', $row['value']) == true)
            $value = $row['value'];
        elseif (preg_match('/^[0-9]+$/S', $row['value']) == true)
            $value = intval($row['value']);
        elseif ($row['value'] == 'false')
            $value = false;
        elseif ($row['value'] == 'true')
            $value = true;
        else
            $value = $row['value'];
        $temp_name = strtoupper($row['name']);
        CAT_Registry::register($temp_name, $value, true, true);
    }
    unset($row);
}
else
{
    CAT_Object::printFatalError("No settings found in the database, please check your installation!");
}


if(!CAT_Registry::exists('LANGUAGE'))
    CAT_Registry::register('LANGUAGE',DEFAULT_LANGUAGE,true);

//**************************************************************************
// Set theme
//**************************************************************************
CAT_Registry::register('CAT_THEME_URL', CAT_URL.'/'.CAT_Registry::get('backend_folder'), true, true);
CAT_Registry::register('CAT_THEME_PATH', CAT_PATH . '/templates/' . DEFAULT_THEME, true);

//**************************************************************************
// Start a session
//**************************************************************************
if (!defined('SESSION_STARTED'))
{
    session_name(APP_NAME.'sessionid');
	$cookie_settings = session_get_cookie_params();
	session_start();
    // extend the session lifetime on each action
    setcookie(
        session_name(),
        session_id(),
        time()+ini_get('session.gc_maxlifetime'),
        $cookie_settings["path"],
        $cookie_settings["domain"],
        (strtolower(substr($_SERVER['SERVER_PROTOCOL'], 0, 5)) === 'https'),
        true
    );
    CAT_Registry::register('SESSION_STARTED', true, true);
}
if (defined('ENABLED_ASP') && ENABLED_ASP && !isset($_SESSION['session_started']))
    $_SESSION['session_started'] = time();