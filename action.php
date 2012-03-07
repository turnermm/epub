<?php
	/**
		
	*/
	// must be run within Dokuwiki
	if(!defined('DOKU_INC')) die();
	
	if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
	require_once(DOKU_PLUGIN.'action.php');
	
	class action_plugin_epub extends DokuWiki_Action_Plugin {
		
		/**
			* Return some info
		*/
		function getInfo() {
			return array();
			//  return confToHash(dirname(__FILE__).'/plugin.info.txt');
		}
		
		/**
			* Register callbacks
		*/
		function register($controller) { 
			$controller->register_hook('TPL_ACT_RENDER', 'AFTER', $this, 'get_epub');
		}
		
		/**
			* Handle PARSER_CACHE_USE:BEFORE event
		*/
		function get_epub($event, $param) {
			global $ID;
			global $USERINFO;
			if(!isset($USERINFO)) return;
			$user = $USERINFO['name'];
			
			global $ACT;
			global $INFO;
			if(strpos($INFO['id'],'epub') === false) return;
			if(isset($ACT) && $ACT == 'edit') return;
			
			$auth = auth_quickaclcheck('epub:*');
			if($auth < 8) return;
			
			$client=$INFO['client'];
   
       $button_name = "Start"; //$this->getLang('btn_generate');
       $button="<form class='button'>";
       $button .= "<div class='no' id='show_throbberbutton'><input type='button' value='$button_name' class='button' title='start'  onclick=\"_epub_show_throbber('$user','$client');\"/></div></form>";     
       echo $button;
		}
		
		function write_debug($what) {  
			return;
			$what = print_r($what,true);
			$handle = fopen('epub-action.txt', 'a');
			fwrite($handle,"$what\n");
			fclose($handle);
		}
	}
	
	//Setup VIM: ex: et ts=4 enc=utf-8 :
