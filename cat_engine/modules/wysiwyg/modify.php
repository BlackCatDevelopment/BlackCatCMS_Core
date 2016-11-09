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

$pg = CAT_Helper_Page::getInstance();

// the Router already checks for the pages_edit and module access permissions,
// so there's no need to do it here. we just check if the user is logged on.
if(!$pg->user()->is_authenticated())
    header('Location: '.CAT_ADMIN_URL.'/login');

// get content
$result = $pg->db()->query(
    "SELECT `content` FROM `:prefix:mod_wysiwyg` WHERE `section_id`=:section_id",
    array('section_id'=>$section_id)
);
if( $result && $result->rowCount() > 0 )
{
    $data    = $result->fetch();
    $content = htmlspecialchars($data['content']);
}
else
{
    $content = '';
}

// check if the editor is already available
if(!isset($wysiwyg_editor_loaded))
{
    // defaults
    $config = array('width'=>'100%','height'=>'250px');
    // get settings
    $result = $pg->db()->query(
        "SELECT * FROM `:prefix:mod_wysiwyg_admin_v2` WHERE `editor`=:name AND (`set_name`='width' OR `set_name`='height')",
        array('name'=>WYSIWYG_EDITOR)
    );
    while(false !== ($row = $result->fetch()) )
        $config[$row['set_name']] = $row['set_value'];

	if (!defined('WYSIWYG_EDITOR') || WYSIWYG_EDITOR=="none" || !file_exists(CAT_ENGINE_PATH.'/modules/'.WYSIWYG_EDITOR.'/include.php'))
    {
		function show_wysiwyg_editor( $name, $id, $content, $width = '100%', $height = '250px', $print = true)
        {
			$editor = '<textarea name="'.$name.'" id="'.$id.'" style="width: '.$width.'; height: '.$height.';">'.$content.'</textarea>';
            if($print)
                echo $editor;
            else
                return $editor;
		}
	}
    else
    {
	    $wysiwyg_editor_loaded = true;
		$id_list               = array();
		$result                = $pg->db()->query(
              "SELECT `section_id` FROM `:prefix:sections` "
            . "WHERE `page_id`= :page_id AND `module`= 'wysiwyg' "
            . "ORDER BY position",
            array('page_id'=>$page_id)
        );
		while( !false == ($wysiwyg_section = $result->fetch() ) )
        {
			$temp_id   = abs(intval($wysiwyg_section['section_id']));
			$id_list[] = 'content'.$temp_id;
		}
		require_once CAT_ENGINE_PATH.'/modules/'.WYSIWYG_EDITOR.'/include.php';
	}
}

if (isset($preview) && $preview == true) return false;

$pg->tpl()->setPath(dirname(__FILE__).'/templates/default');
$pg->tpl()->output(
    'modify',
    array(
        'section_id' => $section_id,
        'page_id'    => $page_id,
        'action'     => CAT_URL.'/modules/wysiwyg/save.php',
        'WYSIWYG'    => show_wysiwyg_editor('content'.$section_id,'content'.$section_id,$content,$config['width'],$config['height'],false)
    )
);