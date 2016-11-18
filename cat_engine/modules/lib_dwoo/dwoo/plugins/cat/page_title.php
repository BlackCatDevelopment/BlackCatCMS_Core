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

function Dwoo_Plugin_page_title(Dwoo $dwoo, $spacer=' - ', $template='[WEBSITE_TITLE][SPACER][PAGE_TITLE]', $return=true)
{
	$vars   = array(
        '[WEBSITE_TITLE]',
        '[PAGE_TITLE]',
        '[MENU_TITLE]',
        '[SPACER]'
    );
	$values = array(
        CAT_Registry::get('WEBSITE_TITLE'),
        CAT_Helper_Page::properties(NULL,'page_title'),
        CAT_Registry::get('MENU_TITLE'),
        $spacer
    );
	$temp = str_ireplace($vars, $values, $template);
	if ( true === $return ) {
		return $temp;
	} else {
		echo $temp;
		return true;
	}
}