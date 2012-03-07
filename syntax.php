<?php
	
	if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
	if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
	require_once(DOKU_PLUGIN.'syntax.php');
	
	
	class syntax_plugin_epub extends DokuWiki_Syntax_Plugin {
		protected $title;
		function getInfo() {
			return array(
            'author' => 'Myron Turner',
            'email'  => 'turnermm02@shaw.ca',
            'date'   => '2011-07-1',
            'name'   => 'epub',
            'desc'   => 'ebook creator',
            'url'    => 'http://www.doluwiki.org/plugin:epub');
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
		
		function handle($match, $state, $pos, &$handler) {
			
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
			
			if($mode == 'xhtml'){
				
				list($state, $match) = $data;
				
				switch ($state) {
					case DOKU_LEXER_ENTER :
					$renderer->doc .= '<div>';
					break;
					
					case DOKU_LEXER_UNMATCHED :
					
					$renderer->doc .= "\n<SCRIPT  type='text/javascript'>\n//<![CDATA[\n" ;
					
					$renderer->doc .= "\nvar epub_wikilink = new Array();\nvar epub_id = new Array();\n";
					if(!strpos($match,','))  {
						$files = explode("\n",$match);
					}
					else $files = explode(',',$match);
					for($i=0;$i<count($files);$i++) {
						$file=trim($files[$i]);
						$file=trim($file,'/');		
						if(!$file) continue;				   						
						$renderer->doc .= "epub_id[$i]='" . str_replace('/',':',$file) . "';\n"	;			  
					}
					
					$renderer->doc .= 'epub_title="' . $this->title . '";';
					$renderer->doc .= "\n// ]]>\n</SCRIPT>\n";	
					
					
					break;
					
					case DOKU_LEXER_EXIT :
				    $throbber = DOKU_BASE . 'lib/plugins/epub/throbber.gif';
				    $renderer->doc .= '<div id="epub_throbber" style="display:none;"><center><img src="' . $throbber .'"</center></div>';
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
			$handle = fopen('epub.txt', 'a');
			fwrite($handle,"$what\n");
			fclose($handle);
		}
	}
	
	//Setup VIM: ex: et ts=4 enc=utf-8 :
?>
