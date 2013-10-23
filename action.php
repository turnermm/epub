<?php
	/**
		
	*/
	// must be run within Dokuwiki
	if(!defined('DOKU_INC')) die();
	
	if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
	require_once(DOKU_PLUGIN.'action.php');
	
	class action_plugin_epub extends DokuWiki_Action_Plugin {
		private $helper;

		
		/**
			* Register callbacks
		*/
		function register($controller) { 
			$controller->register_hook( 'TPL_METAHEADER_OUTPUT', 'AFTER', $this, 'loadScript');
            $controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'create_ebook_button');
			$controller->register_hook('TPL_ACT_RENDER', 'AFTER', $this, 'get_epub');
			$controller->register_hook('TPL_ACT_RENDER', 'BEFORE', $this, 'check_scriptLoaded');
		}
		
		/**
	
		*/

        function create_ebook_button($event,$param) {
             global $INFO;
             global $ACT;
             if(!$this->getConf('permalink')) return;
             if($ACT != 'show') return;
            $this->helper = $this->loadHelper('epub', true);
			if (!$this->helper->is_inCache($INFO['id']))  return;
            
            $auth = auth_quickaclcheck($INFO['id']);
            if($auth) {
                $page_data = $this->helper->get_page_data($INFO['id']);    
                if(!$page_data) return;
                $ebook = $page_data['epub'];
                $link = ml($ebook);
                $title = $page_data['title'];                
                echo  $this->getLang('download');  //The most recent ebook for this page is:
                echo " <a href='$link' title='$title'>$title ($ebook)</a>.<br />";
                echo  $this->getLang('download_click');   // To download it, click on the link. 
                echo $this->getLang('download_alt');    //    If you have an ebook reader plugin installed, right-click on the link and select 'Save . . . As'.";
            }

          
        }
		function get_epub($event, $param) {
			global $ID;
			global $USERINFO;
			if(!isset($USERINFO)) return;
			$user = $USERINFO['name'];			
			global $ACT;
			global $INFO;
           
			if($ACT != 'show') return;			 
            if(!$this->helper) {
			    $this->helper = $this->loadHelper('epub', true);
            }
			if (!$this->helper->is_inCache($INFO['id']))  return;  //cache set in syntax.php 
            
			if(strpos($INFO['id'],'epub') === false) return;
			$wiki_file = wikiFN($INFO['id']);
			if(!@file_exists($wiki_file)) return;
	            $epub_group = $this->getConf('group');
        		$groups=$USERINFO['grps'];
			$auth = auth_quickaclcheck('epub:*');
            
            
			if($auth < 8 && !in_array($epub_group,$groups)) return;
			$auth = auth_quickaclcheck($INFO['id']);
			if($auth < 4) return;
			
			$client=$INFO['client'];
   
       $button_name = $this->getLang('button_start'); //"Start"; //$this->getLang('btn_generate');
       $button="<form class='button'>";
       $button .= "<div class='no' id='show_throbberbutton'><input type='button' value='$button_name' class='button' title='start'  onclick=\"_epub_show_throbber('$user','$client');\"/>";
       $button .="&nbsp;&nbsp;";
	   $button .=    $this->getLang('label_start');   //"Click the Start Button to Create your eBook";
	   $button .="</div></form>";     
       echo $button;
       $id = $INFO['id'];
       $button_name = $this->getLang('button_remove');
       $button="<p><form class='button'>";       
       $button .= "<div class='no' id='epub_remove_button'><input type='button' value='$button_name' class='button' title='start'  onclick=\"epub_remove_creator('$id');\"/></div></form>";
	   $button .= '</br>'. $this->locale_xhtml('remove') . '</p>'; 	   
       echo $button;

		}
		
    function loadScript(&$event) {
    echo <<<SCRIPT
    <script type="text/javascript">
    //<![CDATA[
    function epub_LoadScript( url )
    {
     document.write( '<scr' + 'ipt type="text/javascript" src="' + url + '"><\/scr' + 'ipt>' ) ;
    }
//]]>
  </script>
SCRIPT;
    }

    function check_scriptLoaded(&$event) {	
   $url = DOKU_URL . 'lib/plugins/epub/script.js';
    echo <<<SCRIPT
    <script type="text/javascript">
    //<![CDATA[

      if(!window._epub_show_throbber){	  
        epub_LoadScript("$url");
      }

    //]]>

    </script>
SCRIPT;

	
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
