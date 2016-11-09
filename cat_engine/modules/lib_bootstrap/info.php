<?php

/**
 *
 *   @author          Black Cat Development
 *   @copyright       2013 - 2016 Black Cat Development
 *   @link            http://blackcat-cms.org
 *   @license         http://www.gnu.org/licenses/gpl.html
 *   @category        CAT_Modules
 *   @package         lib_bootstrap
 *
 */

if (defined('CAT_PATH')) {
	include CAT_PATH.'/framework/class.secure.php';
} else {
	$root = "../";
	$level = 1;
	while (($level < 10) && (!file_exists($root.'/framework/class.secure.php'))) {
		$root .= "../";
		$level += 1;
	}
	if (file_exists($root.'/framework/class.secure.php')) {
		include $root.'/framework/class.secure.php';
	} else {
		trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
	}
}

$module_name = "Bootstrap";
$module_directory = "lib_bootstrap";
$module_type = "module";
$module_function = "library";
$module_description = "Bootstrap 3.3.7 für BlackCat CMS, including Bootswatch Themes, Fuel UX 3.15.8, X-editable 1.5.1, Font Awesome 4.7.0";
$module_version = "0.6";
$module_platform = "1.x";
$module_author = "Black Cat Development";
$module_guid = "3929179D-0D39-5C16-97FC-17E72AFCD5D1";
$module_license = "GNU General Public License";
