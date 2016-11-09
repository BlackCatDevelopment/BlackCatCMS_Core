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

function wysiwyg_search($func_vars)
{
	extract($func_vars, EXTR_PREFIX_ALL, 'func');
	
	// how many lines of excerpt we want to have at most
	$max_excerpt_num = $func_default_max_excerpt;
	$divider         = ".";
	$result          = false;
	
	// we have to get 'content' instead of 'text', because strip_tags()
    // doesn't remove scripting well.
	// scripting will be removed later on automatically
	$query = $func_database->query(sprintf(
        "SELECT content FROM `%smod_wysiwyg` WHERE section_id='%d'",
        CAT_TABLE_PREFIX, $func_section_id
	));

	if($query->rowCount() > 0)
    {
		if($res = $query->fetchRow())
        {
            if(CAT_Helper_Addons::isModuleInstalled('kit_framework'))
            {
                // remove all kitCommands from the content
                preg_match_all('/(~~)( |&nbsp;)(.){3,512}( |&nbsp;)(~~)/', $res['content'], $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $res['content'] = str_replace($match[0], '', $res['content']);
                }
            }
			$mod_vars = array(
				'page_link'          => $func_page_link,
				'page_link_target'   => SEC_ANCHOR."#section_$func_section_id",
				'page_title'         => $func_page_title,
				'page_description'   => $func_page_description,
				'page_modified_when' => $func_page_modified_when,
				'page_modified_by'   => $func_page_modified_by,
				'text'               => $res['content'].$divider,
				'max_excerpt_num'    => $max_excerpt_num
			);
			if(print_excerpt2($mod_vars, $func_vars)) {
				$result = true;
			}
		}
	}
	return $result;
}

?>
