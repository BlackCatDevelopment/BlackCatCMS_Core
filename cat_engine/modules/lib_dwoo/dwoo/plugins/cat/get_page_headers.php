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

function Dwoo_Plugin_get_page_headers(Dwoo $dwoo,$current_section=false,$print_output=true)
{

    if(defined('CAT_HEADERS_SENT')) return false;
    $output = CAT_Helper_Page::getInstance()->getAssets('header',$current_section);
	if ( $print_output )
		echo $output;
	else
		return $output;
}