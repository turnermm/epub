<?php
/**
 * DokuWiki StyleSheet creator
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',dirname(__FILE__).'/../../');
if(!defined('NOSESSION')) define('NOSESSION',true); // we do not use a session or authentication here (better caching)
if(!defined('DOKU_DISABLE_GZIP_OUTPUT')) define('DOKU_DISABLE_GZIP_OUTPUT',1); // we gzip ourself here
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
    // load template styles
    if (isset($tplstyles[$mediatype])) {
        $files = array_merge($files, $tplstyles[$mediatype]);
    }

    // load user styles
    if(isset($config_cascade['userstyle'][$mediatype])){
        $files[$config_cascade['userstyle'][$mediatype]] = DOKU_BASE;
    }
 
    // load files
    foreach($files as $file => $location){
        $css .= css_loadfile($file, $location);
    }

    // apply style replacements
    $css = css_applystyle($css,$tplinc);

    // place all @import statements at the top of the file
    $css = css_moveimports($css);

   

    io_saveFile($path . 'style.css',$css);
 
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



//Setup VIM: ex: et ts=4 :
