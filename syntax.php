<?php
	
	if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
	if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
	require_once(DOKU_PLUGIN.'syntax.php');
	
	
	class syntax_plugin_epub extends DokuWiki_Syntax_Plugin {
		protected $title;
		private $helper;
		function getInfo() {
			return array(
            'author' => 'Myron Turner',
            'email'  => 'turnermm02@shaw.ca',
            'date'   => '2011-07-1',
            'name'   => 'epub',
            'desc'   => 'ebook creator',
            'url'    => 'http://www.dokuwiki.org/plugin:epub');
		} 
		
		function getType(){ return 'container'; }
		function getPType(){ return 'normal'; }
		function getAllowedTypes() { 
			return array();
		}
		function getSort(){ return 25; }
		
		function connectTo($mode) {
			$this->Lexer->addEntryPattern('<epub.*?>(?=.*?</epub>)',$mode,'plugin_epub');
			
		}
		function postConnect() {
			$this->Lexer->addExitPattern('</epub>','plugin_epub');
			
		}
		function __construct() {
		    $this->helper =& plugin_load('helper', 'epub');
		}
		function handle($match, $state, $pos, &$handler) {		 
            $match = str_replace(';','&#59;',$match);		  
		    $match = str_replace(',','&#44;',$match);
            
			switch ($state) {		
				case DOKU_LEXER_ENTER :       		  
				$title =  substr($match, 6, -1);  
				if($title)
				$this->title = $title;
				else 
				$this->title="Dokuwiki EBook";			
				return array($state, trim($title));          
				
				case DOKU_LEXER_UNMATCHED :				  
				return array($state, $match);
				case DOKU_LEXER_EXIT :            
				return array($state,$match);
				
				default:
				
				return array($state,$match);
			}
		}
		
		function render($mode, &$renderer, $data) {
			global $INFO;

			if($mode == 'xhtml'){
				$renderer->nocache();
				list($state, $match) = $data;
				
				switch ($state) {
					case DOKU_LEXER_ENTER :

				    $this->helper->writeCache($INFO['id']);
					$renderer->doc .= '<div>';
					break;
					
					case DOKU_LEXER_UNMATCHED :
					$id = $INFO['id'];
					$renderer->doc .= "\n<SCRIPT  type='text/javascript'>\n//<![CDATA[\n" ;
					$renderer->doc .= "\nvar book_id = '$id';";
					$renderer->doc .= "\nvar epub_wikilink = new Array();\nvar epub_id = new Array();\n";
					$files = explode("\n",$match);

					for($i=0;$i<count($files);$i++) {					      
	                    $file = trim($files[$i],'][');
		                list($file,$rest) = explode('|',$file);	
						$file=trim($file);
						$file=trim($file,'/');							 		
						if(!$file) continue;				  
 						if(!auth_quickaclcheck($file)) { 							
							continue;
						}	
						$renderer->doc .= "epub_id[$i]='" . str_replace('/',':',$file) . "';\n"	;			  
                        $rest = trim($rest," ][");
                        
                        if(!$rest) { 
                          $ar = explode(':',$file);
                          $n = count($ar) -1;
                          $rest = $ar[$n];
                         }
                        
                        $rest=hsc($rest);
                        $rest =  str_replace(':','&#58;',$rest);
                        $renderer->doc .= "epub_wikilink[$i]='" . str_replace('/',':',$rest) . "';\n"	;			  
					}
					
					$renderer->doc .= 'epub_title="' . $this->title . '";';
					$renderer->doc .= "\n// ]]>\n</SCRIPT>\n";	
					
					
					break;
					
					case DOKU_LEXER_EXIT :
				    $throbber = DOKU_BASE . 'lib/plugins/epub/throbber.gif';
				    $renderer->doc .= '<div id="epub_throbber" style="display:none;"><center><img src="' . $throbber .'"></center><br /><span id="epub_progress">progress</span></div>';
				    $renderer->doc .= "\n</div>";
				    break;
				}
				return true;
				
			} 
			// unsupported $mode
			return false;
		} 
		
		function write_debug($what) {  
			return;
	       if(is_array($what))   $what = print_r($what,true);
			$handle = fopen('epub.txt', 'a');
			fwrite($handle,"$what\n");
			fclose($handle);
		}
	}
	
	//Setup VIM: ex: et ts=4 enc=utf-8 :
?>
