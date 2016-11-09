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
   @category        CAT_Modules
   @package         dwoo

*/

global $__dwoo_plugin_lang;

function Dwoo_Plugin_translate(Dwoo $dwoo, $msg, $args = array())
{
	global $__dwoo_plugin_lang;
	// just to be sure
	if(!is_object($__dwoo_plugin_lang))
    {
        $__dwoo_plugin_lang = CAT_Helper_I18n::getInstance();
    }
	return $__dwoo_plugin_lang->translate($msg, $args);
}
