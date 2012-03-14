<?php
	
	if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../../').'/');
	if(!defined('NOSESSION')) define('NOSESSION',true); 
	if(!defined('NL')) define('NL',"\n");
	if(!defined('EPUB_DIR')) define('EPUB_DIR',realpath(dirname(__FILE__).'/../').'/');
	require_once(DOKU_INC.'inc/init.php');
	require_once(EPUB_DIR.'scripts/epub_utils.php');
	global $entities;
	$entities = unserialize(file_get_contents(EPUB_DIR . 'scripts/epub_ents.ser'));
	
	class epub_creator {
		private $_renderer;
		function create($id, $user_title=false) {
			
			ob_start();
			
			$mode ='epub';
			$Renderer =& plugin_load('renderer',$mode);	    
			$Renderer->set_oebps() ;
			$this->_renderer = $Renderer;
            if(is_null($Renderer)){
                msg("No renderer for $mode found",-1);  
				exit;
			}
					
		
			$id = $id;
			$wiki_file = wikiFN($id);
			if(!file_exists($wiki_file)) {
			     echo "$id not found\n";
				 return false;
			}
			$text=io_readFile($wiki_file);
			$instructions = p_get_instructions($text);
			if(is_null($instructions)) return '';
			
			
			$Renderer->notoc();
			$Renderer->smileys = getSmileys();
			$Renderer->entities = getEntities();
			$Renderer->acronyms = array();
			$Renderer->interwiki = getInterwiki();
			
			// Loop through the instructions
			foreach ( $instructions as $instruction ) {
				// Execute the callback against the Renderer
				call_user_func_array(array(&$Renderer, $instruction[0]),$instruction[1]);
			}
			$result = "";
			$result .= '<html xmlns="http://www.w3.org/1999/xhtml">' . "\n";
			$result .= "\n<head>\n";
			$result .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>' ."\n";
			$result .= '<link rel="stylesheet"  type="text/css" href="style.css"/>';
			$result .= "\n<title>";
			$result .= "</title>\n</head><body>\n";
			$result .= "<div class='dokuwiki'>\n";
			$info = $Renderer->info;       
			$data = array($mode,& $Renderer->doc);
			trigger_event('RENDERER_CONTENT_POSTPROCESS',$data);
			
			$xhtml = $Renderer->doc;
			$result .= $xhtml;
			$result .= "\n</div></body></html>\n";		
			$result =  preg_replace_callback("/&(\w+);/m", "epbub_entity_replace", $result );  				
			$result = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/m", "\n", $result);	
			$result = preg_replace("/^\s+/m", "", $result );  				
			$result = preg_replace_callback(
			                          '|<p>([\s\n]*)(.*?<div.*?/div>.*?)([\s\n])*<\/p>|im',
									   create_function(
											'$matches',
											'$result = $matches[1] . $matches[2] . $matches[3];
											//echo "$result\n";
											return $result;'
										),
										$result
                            );

			ob_end_flush();
			$id = str_replace(':', '@', $id) . '.html';
			io_saveFile(epub_get_oebps() .$id,$result);
			if($user_title) {				
			    epub_write_zip($id);
				return true;
			}
			$item_num=epub_write_item($id, "application/xhtml+xml");
			epub_push_spine(array($id,$item_num));
			return true;
		}  
		
		function get_renderer	() {
			return $this->_renderer;
		}
		
			
	}	
	       
			if(!class_exists ('ZipArchive')) {        
				if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
					echo "Windows systems require the ZipArchive extension for creating ebooks\n";
					exit;
				}
			}

			$epub_ids = 'introduction;;v06;;features;;index:site_inx';
			if(isset ($_POST['epub_ids'])) $epub_ids = rawurldecode($_POST['epub_ids']);
			$epub_pages =  explode(';;',$epub_ids) ;
		
		    $epub_user_title = $epub_pages[0] == 'title' ? true: false;
	   	    epub_setup_book_skel($epub_user_title) ;			
			epub_opf_header($epub_user_title);
            if($epub_user_title) {
			    $creator = new epub_creator();
			    $creator->create($epub_pages[0], $epub_user_title);
				array_shift($epub_pages);
				echo "processed: title \n";
            }
			
			foreach($epub_pages as $page) {			  
			    $creator = new epub_creator();
				if($creator->create($page)) {
				   if(isset ($_POST['epub_ids']))
				        echo rawurlencode("processed: $page \n");
				   else 
				      echo "processed: $page \n";		
				}
			}

			epub_css(); 
			epub_write_item('style.css',"text/css");
			epub_opf_write('</manifest>');
			epub_write_spine();
			epub_write_footer();
			epub_write_ncx();
			epub_finalize_zip() ;
		    epub_pack_book();
		
		
			
			//echo str_replace("[","<br />[",print_r($_POST,true));			
			
			exit;			
	