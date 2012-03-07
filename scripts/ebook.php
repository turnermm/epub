<?php
	
	if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../../').'/');
	if(!defined('NOSESSION')) define('NOSESSION',true); 
	if(!defined('NL')) define('NL',"\n");
	if(!defined('EPUB_DIR')) define('EPUB_DIR',realpath(dirname(__FILE__).'/../').'/');
	require_once(DOKU_INC.'inc/init.php');
	
	global $entities;
	$entities = unserialize(file_get_contents(EPUB_DIR . 'scripts/epub_ents.ser'));
	
	class epub_creator {
		private $_renderer;
		function create($id) {
			
			ob_start();
			
			$mode ='epub';
			$Renderer =& plugin_load('renderer',$mode);	    
			$Renderer->set_oebps() ;
			$this->_renderer = $Renderer;
            if(is_null($Renderer)){
                msg("No renderer for $mode found",-1);              
			}
					
			
			//  $id = ':example';
			global $ID;
			$id = $id;
			$wiki_file = wikiFN($id);
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
											// single quotes are essential here,
											// or alternative escape all $ as \$
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
			$item_num=epub_write_item($id, "application/xhtml+xml");
			epub_push_spine(array($id,$item_num));
		}  
		function get_renderer	() {
			return $this->_renderer;
		}
		
			
	}	
	

			$epub_ids = 'introduction;;v06;;features;;index:site_inx'; //media;;configuration';
			if(isset ($_POST['epub_ids'])) $epub_ids = rawurldecode($_POST['epub_ids']);
			$epub_pages =  explode(';;',$epub_ids) ;
		
	   	    epub_setup_book_skel() ;
			epub_opf_header();
	
			foreach($epub_pages as $page) {
			    $creator = new epub_creator();
				$creator->create($page);
				echo "page: -->$page \n";
			}

			epub_css(); 
			epub_write_item('style.css',"text/css");
			epub_opf_write('</manifest>');
			epub_write_spine();
			epub_write_footer();
			epub_write_ncx();
			epub_pack_book();
			
			echo str_replace("[","<br />[",print_r($_POST,true));
			echo epub_get_data_media() . "\n";	
			
			exit;			
			
			/**
			utilities
			*/

		function epub_opf_header() {
			global $conf;	
			$lang = $conf['lang'];
			$user= rawurldecode($_POST['user']);
			$url=rawurldecode($_POST['location']); 
			$url=dirname($url);
			
			$title=rawurldecode($_POST['title']); 
			$uniq_id = str_replace('/','', DOKU_BASE) . "_id";
			
			$outp = <<<OUTP
<?xml version='1.0' encoding='utf-8'?>
<package xmlns="http://www.idpf.org/2007/opf" xmlns:dc="http://purl.org/dc/elements/1.1/" 
   unique-identifier="$uniq_id" version="2.0">
<metadata>
<dc:title>$title</dc:title>
<dc:creator>$user</dc:creator>
<dc:identifier id="$uniq_id">$url</dc:identifier>
<dc:language>$lang</dc:language>
</metadata>
<manifest>
<item id="ncx" href="toc.ncx" media-type="application/x-dtbncx+xml"/> 

OUTP;
			
			$dir =  epub_get_metadirectory() .  'OEBPS/';
			io_saveFile($dir . 'content.opf',$outp);
			
			flush();
			
        $ncx=<<<NCX
<!DOCTYPE ncx PUBLIC '-//NISO//DTD ncx 2005-1//EN' 'http://www.daisy.org/z3986/2005/ncx-2005-1.dtd'>
<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1" xml:lang="en">
  <head>    
     <meta content="$url" name="dtb:uid"/>
    <meta content="1" name="dtb:depth"/>  
    <meta content="0" name="dtb:totalPageCount"/>
    <meta content="0" name="dtb:maxPageNumber"/>
  </head>
   <docTitle>
    <text>$title</text>
  </docTitle>
  <navMap>

NCX;
          io_saveFile($dir . 'toc.ncx',$ncx);
		
		}	
			
		function epbub_entity_replace($matches) {
		global $entities;	  	 
		
		if(array_key_exists($matches[0], $entities)) {
		return $entities[$matches[0]]; 
		}
		return $matches[0];	   
		}   
		
		function epub_css() {
		    require_once('css2.php');  
		    epub_css_out(epub_get_oebps()); 
		}
	 
	    function epub_write_spine() {
		    $items = epub_push_spine();
			epub_opf_write('<spine toc="ncx">');	        
			foreach($items as $page) {
	            epub_opf_write('<itemref idref="' . $page[1] . '" linear="yes"/>');
			}
			epub_opf_write('</spine>');
		}
		
		function epub_write_footer() {
		$footer=<<<FOOTER
</package>
FOOTER;
	     epub_opf_write($footer);
		 $handle = epub_opf_write(null);
		 fclose($handle);
	   }
	   
		function is_epub_pageid($id) {
		    static $ep_ids;
			if(!$ep_ids)  {	
			    if(isset($_POST['epub_ids'])) {
				    $ep_ids =explode(';;',rawurldecode($_POST['epub_ids']));							
				}
				else {
				    return false;
				}
			}
			
			return in_array($id,$ep_ids);
		}
		
        function epub_get_metadirectory($temp_user=null) {
	        static $dir;
			//$temp_user='tower_1';
		    if(!$dir) {
                  if(isset($_POST['client'])) {
				      $user= rawurldecode($_POST['client']);
				  }
			      else {		
				     $user=$temp_user?$temp_user:md5(rawurldecode($_POST['user']) . rand() );
				  }
				  $dir = dirname(metaFN("epub:$user:tmp",'.meta')) . '/'; 
		    }
				 
		    return $dir;    
	    }


	   function epub_get_data_media() {
	         static $dir;
             global $conf;			
			 if(!$dir) {
			     $dir = init_path($conf['savedir']) . '/media/';			
			 }
			 
			 return $dir;
			 
	   }

   	   function epub_get_oebps() {
	         static $dir;
			 if(!$dir) {
			      $dir=epub_get_metadirectory() . 'OEBPS/';
				 }			 
			 return $dir;
			 
	   }

	    function epub_itemid() {
		  static $num = 0;		    
		     return 'item' . ++$num;
		}
	    function epub_write_item($url,$mime_type) {
		   $item_num = epub_itemid() ;
		    $item='<item href="' . $url .'" id="' . $item_num  . '" media-type="' . $mime_type . '" />'; 
		    epub_opf_write($item);
			return $item_num;
		}
		
		function epub_write_ncx() {
		    $toc  = epub_get_oebps()  . 'toc.ncx';      
			echo $toc ,"\n";
	        $opf_handle= fopen($toc, 'a');
		    if(!$opf_handle) {
		        echo "unable to open file: $toc\n";
		        exit;
	        }  
		    $items = epub_push_spine();
			
            $num = 0;
			foreach($items as $page) {
			    $num++;
				$page = $page[0];			
				$navpoint=<<<NAVPOINT
 <navPoint id="np-$num" playOrder="$num">
  <navLabel>
	<text>$page</text>
  </navLabel>
  <content src="$page"/>
</navPoint>
NAVPOINT;
             fwrite($opf_handle,"$navpoint\n");
			}
		   fwrite($opf_handle,"</navMap>\n</ncx>\n");	
		   fflush($opf_handle);
           fclose($opf_handle);
		   
		}
		
		
	    function epub_opf_write($data=null) {
		    static $opf_handle;
			static $opf_content;
			if(!$opf_handle) {			
			    $opf_content  = epub_get_oebps()  . 'content.opf';
				$opf_handle= fopen($opf_content, 'a');
				if(!$opf_handle) {
				   echo "unable to open file: $opf_content\n";
				   exit;
				 }  
			}
			
		    if($data) {
				if( fwrite($opf_handle,"$data\n") == false) {
					echo "cannot write to $opf_content\n";
					exit;
				}
			}
			
	        return $opf_handle;		
		}
		
		
	    function epub_setup_book_skel() {
		    $dir=epub_get_metadirectory();
		    $meta = $dir . 'META-INF';
		    $oebps = epub_get_oebps(); 
			$media_dir = epub_get_data_media() . 'epub';
            io_mkdir_p($meta);
			io_mkdir_p($oebps);			
			io_mkdir_p($media_dir);
			 
			copy(EPUB_DIR . 'scripts/package/my-book.epub', $dir . 'my-book.epub');
			copy(EPUB_DIR . 'scripts/package/container.xml', $dir . 'META-INF/container.xml');			
			copy(EPUB_DIR . 'scripts/package/title.html', $oebps . 'title.html');			
			echo "copying\n";
		}
		
        function epub_push_spine($page=null) {
		    static $spine = array();
			if(!$page) return $spine;
			$spine[] = $page;
			
		}	
		function epub_pack_book() {
		    $meta = epub_get_metadirectory() ;
			chdir($meta);
			$cmd =  'zip -Xr9Dq my-book.epub *';
			exec($cmd);
			$media_dir = epub_get_data_media() . 'epub/';
			$oldname = $meta . 'my-book.epub';	        
			$newname = $media_dir .  strtolower(date("Y_F_j_h-i-s") ) . '.epub';

			if(rename ($oldname , $newname )) {
			   echo "moved $oldname  to $newname\n" ;
			}
		}	 