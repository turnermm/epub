<?php
/**
 * DokuWiki StyleSheet creator
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../../').'/');	
if(!defined('NOSESSION')) define('NOSESSION',true); // we do not use a session or authentication here (better caching)
if(!defined('DOKU_DISABLE_GZIP_OUTPUT')) define('DOKU_DISABLE_GZIP_OUTPUT',1); // we gzip ourself here
if(!defined('EPUB_DIR')) define('EPUB_DIR',realpath(dirname(__FILE__).'/../').'/');		
if(!defined('DOKU_TPL')) define('DOKU_TPL', DOKU_BASE.'lib/tpl/'.$conf['template'].'/'); 
if(!defined('DOKU_TPLINC')) define('DOKU_TPLINC', DOKU_INC.'lib/tpl/'.$conf['template'].'/');
require_once(DOKU_INC.'inc/init.php');



// ---------------------- functions ------------------------------

/**
 * Output all needed Styles
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function epub_css_out($path){
    global $conf;
    global $lang;
    global $config_cascade;

    $mediatype = 'screen';
        
	$tpl = trim(preg_replace('/[^\w-]+/','',$conf['template']));
	
    if($tpl){
        $tplinc = DOKU_INC.'lib/tpl/'.$tpl.'/';
        $tpldir = DOKU_BASE.'lib/tpl/'.$tpl.'/';
    }else{
        $tplinc = DOKU_TPLINC;
        $tpldir = DOKU_TPL;
    }

    // load template styles
    $tplstyles = array();
    if(@file_exists($tplinc.'style.ini')){
        $ini = parse_ini_file($tplinc.'style.ini',true);
        foreach($ini['stylesheets'] as $file => $mode){
            $tplstyles[$mode][$tplinc.$file] = $tpldir;
        }
    }

    // Array of needed files and their web locations, the latter ones
    // are needed to fix relative paths in the stylesheets
    $files   = array();
    
      $files[DOKU_INC.'lib/styles/style.css'] = DOKU_BASE.'lib/styles/';
        // load plugin, template, user styles
        $files = array_merge($files, css_pluginstyles('screen'));
        $files = array_merge($files, css_pluginstyles('all'));
        if (isset($tplstyles['screen'])) $files = array_merge($files, $tplstyles['screen']);
        if($lang['direction'] == 'rtl'){
            if (isset($tplstyles['rtl'])) $files = array_merge($files, $tplstyles['rtl']);
        }
        if(isset($config_cascade['userstyle']['default'])){
            $files[$config_cascade['userstyle']['default']] = DOKU_BASE;
        }
     if (isset($tplstyles[$mediatype])) {
        $files = array_merge($files, $tplstyles[$mediatype]);
     }

    // load user styles
    if(isset($config_cascade['userstyle'][$mediatype])){
        $files[$config_cascade['userstyle'][$mediatype]] = DOKU_BASE;
    }
 
    // load files
    $css = "";
    foreach($files as $file => $location){
        $css .= css_loadfile($file, $location);
    }

    // apply style replacements
    $css = css_applystyle($css,$tplinc);

    // place all @import statements at the top of the file
    $css = css_moveimports($css);
    io_saveFile($path . 'Styles/style.css' ,$css);
 
}



/**
 * Does placeholder replacements in the style according to
 * the ones defined in a templates style.ini file
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function css_applystyle($css,$tplinc){
    if(@file_exists($tplinc.'style.ini')){
        $ini = parse_ini_file($tplinc.'style.ini',true);
        $css = strtr($css,$ini['replacements']);
    }
    return $css;
}


/**
 * Prints classes for file download links
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */

/**
 * Loads a given file and fixes relative URLs with the
 * given location prefix
 */
function css_loadfile($file,$location=''){
    if(!@file_exists($file)) return '';
    $css = io_readFile($file);
    if(!$location) return $css;

    $css = preg_replace('#(url\([ \'"]*)(?!/|http://|https://| |\'|")#','\\1'.$location,$css);
    $css = preg_replace('#(@import\s+[\'"])(?!/|http://|https://)#', '\\1'.$location, $css);
    return $css;
}


/**
 * Move all @import statements in a combined stylesheet to the top so they
 * aren't ignored by the browser.
 *
 * @author Gabriel Birke <birke@d-scribe.de>
 */
function css_moveimports($css)
{
    if(!preg_match_all('/@import\s+(?:url\([^)]+\)|"[^"]+")\s*[^;]*;\s*/', $css, $matches, PREG_OFFSET_CAPTURE)) {
        return $css;
    }
    $newCss  = "";
    $imports = "";
    $offset  = 0;
    foreach($matches[0] as $match) {
        $newCss  .= substr($css, $offset, $match[1] - $offset);
        $imports .= $match[0];
        $offset   = $match[1] + strlen($match[0]);
    }
    $newCss .= substr($css, $offset);
    return $imports.$newCss;
}

function css_pluginstyles($mode='screen'){
    global $lang;
    $list = array();
    $plugins = plugin_list();
    foreach ($plugins as $p){
        if($mode == 'all'){
            $list[DOKU_PLUGIN."$p/all.css"]  = DOKU_BASE."lib/plugins/$p/";
        }elseif($mode == 'print'){
            $list[DOKU_PLUGIN."$p/print.css"]  = DOKU_BASE."lib/plugins/$p/";
        }elseif($mode == 'feed'){
            $list[DOKU_PLUGIN."$p/feed.css"]  = DOKU_BASE."lib/plugins/$p/";
        }else{
            $list[DOKU_PLUGIN."$p/style.css"]  = DOKU_BASE."lib/plugins/$p/";
            $list[DOKU_PLUGIN."$p/screen.css"] = DOKU_BASE."lib/plugins/$p/";
        }
        if($lang['direction'] == 'rtl'){
            $list[DOKU_PLUGIN."$p/rtl.css"] = DOKU_BASE."lib/plugins/$p/";
        }
    }
    return $list;
}

//echo epub_css_out(EPUB_DIR);
//Setup VIM: ex: et ts=4 :
