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

$update_when_modified = true;

$val     = CAT_Helper_Validate::getInstance();
$user    = CAT_Users::getInstance();
$backend = CAT_Backend::getInstance('Pages', 'pages_modify');

// ===============
// ! Get page id
// ===============
$page_id    = $val->get('_REQUEST','page_id','numeric');
$section_id = $val->get('_REQUEST','section_id','numeric');

if ( !$page_id )
{
	header("Location: index.php");
	exit(0);
}

// =============
// ! Get perms
// =============
if ( CAT_Helper_Page::getPagePermission($page_id,'admin') !== true )
{
	$backend->print_error( 'You do not have permissions to modify this page!' );
}

// =================
// ! Get new content
// =================
$content = $val->sanitizePost('content'.$section_id);

// for non-admins only
if(!CAT_Users::getInstance()->ami_group_member(1))
{
    // if HTMLPurifier is enabled...
    $r = $backend->db()->get_one('SELECT * FROM `'.CAT_TABLE_PREFIX.'mod_wysiwyg_admin_v2` WHERE set_name="enable_htmlpurifier" AND set_value="1"');
    if($r)
    {
        // use HTMLPurifier to clean up the output
        $content = CAT_Helper_Protect::getInstance()->purify($content,array('Core.CollectErrors'=>true));
    }
}
else
{
    $content = $val->add_slashes($content);
}
/**
 *	searching in $text will be much easier this way
 */
$text = umlauts_to_entities(strip_tags($content), strtoupper(DEFAULT_CHARSET), 0);

/**
 *  save
 **/
$query = "REPLACE INTO `".CAT_TABLE_PREFIX."mod_wysiwyg` VALUES ( '$section_id', $page_id, '$content', '$text' );";
$backend->db()->query($query);
if ($backend->db()->isError())
    trigger_error(sprintf('[%s - %s] %s', __FILE__, __LINE__, $backend->db()->getError()), E_USER_ERROR);

$edit_page = CAT_ADMIN_URL.'/pages/modify.php?page_id='.$page_id.'#'.SEC_ANCHOR.$section_id;

// Check if there is a database error, otherwise say successful
if($backend->db()->isError())
{
	$backend->print_error($backend->db()->getError(), $js_back);
}
else
{
	$backend->print_success('Page saved successfully', $edit_page );
}

// Print admin footer
$backend->print_footer();

?>