<?php

/**
 *
 *   @author          Black Cat Development
 *   @copyright       2013 - 2016 Black Cat Development
 *   @link            http://blackcat-cms.org
 *   @license         http://www.gnu.org/licenses/gpl.html
 *   @category        CAT_Core
 *   @package         backstrap
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

$template_directory			= 'backstrap';
$template_name				= 'BackStrap - Bootstrap based backend theme';
$template_function			= 'theme';
$template_version			= '1.0.0';
$template_platform			= '2.0';
$template_author			= 'BlackBird Webprogrammierung';
$template_license			= '<a href="http://www.gnu.org/licenses/gpl.html">GNU General Public License</a>';
$template_license_terms		= '-';
$template_description		= 'BackStrap - Bootstrap based backend theme';
$template_engine			= 'dwoo';
$template_guid				= '';

// get variants
$dirs = CAT_Helper_Directory::getDirectories(CAT_PATH.'/modules/lib_bootstrap/vendor/css');
echo "<textarea style=\"width:100%;height:200px;color:#000;background-color:#fff;\">";
print_r( $dirs );
echo "</textarea>";