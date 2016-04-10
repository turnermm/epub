<?php	
	if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../../').'/');
	if(!defined('NOSESSION')) define('NOSESSION',true); 	
	if(!defined('EPUB_DIR')) define('EPUB_DIR',realpath(dirname(__FILE__).'/../').'/');
	require_once(DOKU_INC.'inc/init.php');
	require_once(EPUB_DIR.'/helper.php');
	global $INPUT;
	$helper = new  helper_plugin_epub();	
	$id = rawurldecode($INPUT->str('remove'));
    if(!$helper->is_inCache($id)) {
	  echo htmlentities($id) . " is not in the book creator list; you may use it for other purposes.\n";
	  echo "To restore it to the list you must make an edit, no matter how small, and re-save the page.";
	  exit;
	}

	$helper->remove_page($id);

    if(!$helper->is_inCache($id)) {
	   echo "$id has been successfully removed from the book creator list.\nTo restore it to the list you must make an edit,\nno matter how small, and re-save the page.";
	}
	else {
	 echo "$id was not removed from book creator list.\n";
	}
	