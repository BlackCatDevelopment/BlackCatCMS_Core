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
   @package         wysiwyg

*/

// Get content
$result = CAT_Helper_Page::getInstance()->db()->query("SELECT `content` FROM `".CAT_TABLE_PREFIX."mod_wysiwyg` WHERE `section_id` = '".$section_id."'");
if($result&&$result->rowCount()>0)
{
    $fetch = $result->fetch(PDO::FETCH_ASSOC);
    echo $fetch['content'];
}
