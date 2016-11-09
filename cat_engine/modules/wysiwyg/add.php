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

$pg = CAT_Page::getInstance();
$pg->db()->query(
    "INSERT INTO `:prefix:mod_wysiwyg` (`page_id`, `section_id`, `content`, `text`) VALUES (?,?,?,?)",
    array($page_id, $section_id, '', '')
);
if($pg->db()->isError())
{
    CAT_Object::printFatalError(
        $pg->lang()->t(
            'Unable to add the section: {error}',
            array('error'=>$pg->db()->getError())
        )
    );
}