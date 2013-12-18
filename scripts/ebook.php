<?php
	
	if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../../').'/');
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
            $id = ltrim($id, ':');
            $id = ":$id";         
            $namespace= getNS($id);
            epub_save_namespace($namespace);
            $mode ='epub';
            $Renderer =& plugin_load('renderer',$mode);	    
            $Renderer->set_oebps() ;
            $Renderer->set_current_page(epub_clean_name(str_replace(':', '_', $id)) . '.html') ;
            $this->_renderer = $Renderer;
            if(is_null($Renderer)){
                msg("No renderer for $mode found",-1);  
                exit;
            }
		

			global $ID;
			$oldID = $ID;

			$ID = cleanID($id);

			$wiki_file = wikiFN($id);
			if(!file_exists($wiki_file)) {
                 epub_push_spine(array("",""));
			     echo "$id not found\n";
				 return false;
			}
            epub_update_progress("reading $id");
			$instructions = p_cached_instructions($wiki_file, false, $id);
			if(is_null($instructions)) return '';
			
			
			$Renderer->notoc();
			$Renderer->smileys = getSmileys();
			$Renderer->entities = getEntities();
			$Renderer->acronyms = array();
			$Renderer->interwiki = getInterwiki();
			epub_update_progress("rendering $id,  this could take some time");
			// Loop through the instructions
			foreach ( $instructions as $instruction ) {
				// Execute the callback against the Renderer
				call_user_func_array(array(&$Renderer, $instruction[0]),$instruction[1]);
			}
			$result = "";
			$result .= '<html xmlns="http://www.w3.org/1999/xhtml">' . "\n";
			$result .= "\n<head>\n";
			$result .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>' ."\n";
			$result .= '<link rel="stylesheet"  type="text/css" href="../Styles/style.css"/>';
            epub_check_for_mathjax($result);
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
            if($user_title) {
                $id = 'title.html';
            }
            else {
            $id = epub_clean_name(str_replace(':', '_', $id)) . '.html';
               }
             io_saveFile(epub_get_oebps() ."Text/$id",$result);
            
			if($user_title) {				
			    epub_write_zip('Text/title.html');
                $ID = $oldID;
				return true;
			}
			$item_num=epub_write_item("Text/$id", "application/xhtml+xml");
			epub_push_spine(array("Text/$id",$item_num));
            epub_save_namespace();

			$ID = $oldID;

			return true;
		}  
		
		function get_renderer	() {
			return $this->_renderer;
		}
		
			
	}	
	       
           
            // $epub_ids = 'ditaa:win_filebrowser;;introduction;;v06;;features;;index:site_inx';  
            if(isset ($_POST['epub_ids'])) $epub_ids = rawurldecode($_POST['epub_ids']);
            if(isset ($_POST['epub_titles'])) $e_titles = rawurldecode($_POST['epub_titles']);
			$epub_pages =  explode(';;',$epub_ids) ;
            $epub_titles = explode(';;',$e_titles) ;
            $epub_user_title = strpos($epub_pages[0], 'title') !== false ? true: false;
	   	    epub_setup_book_skel($epub_user_title) ;			
            epub_opf_header($epub_user_title);
            if($epub_user_title) {
                $creator = new epub_creator();
                $creator->create($epub_pages[0], $epub_user_title);
                array_shift($epub_pages);             
                echo "processed: title page \n";             
            }
            else {
                array_unshift($epub_titles, 'Title Page');
            }
            epub_checkfor_ns($epub_pages[0],$epub_pages, $epub_titles);      
            array_push($epub_titles,"Footnotes");
            epub_titlesStack($epub_titles);
            $page_num = 0;
            $creator = false;
            foreach($epub_pages as $page) {		
                epub_update_progress("processing: $page");
                $creator = new epub_creator();
                if($creator->create($page)) {
                if(isset ($_POST['epub_ids']))
                    echo rawurlencode("processed: $page \n");
                        else  
                        echo "processed: $page \n";		
                }
            }
			
            if(epub_footnote_handle(true)) {			
				epub_close_footnotes();
			}
			
            epub_css($creator); 
            epub_write_item('Styles/style.css',"text/css");
            epub_opf_write('</manifest>');
            epub_write_spine();
            epub_write_footer();
            epub_write_ncx();
            epub_finalize_zip() ;
            epub_update_progress("packing  ebook");
            epub_pack_book();
		    epub_update_progress();  // deletes progress file
		
			
			exit;			
	