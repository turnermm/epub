<?php	
	if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../../').'/');	
	if(!defined('EPUB_DIR')) define('EPUB_DIR',realpath(dirname(__FILE__).'/../').'/');		
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
			
			$seed = md5(rawurldecode($_POST['user']).time());  
		    if(!$dir) {
                  if(isset($_POST['client'])) {
				      $user= rawurldecode($_POST['client']) . ":$seed";
				  }
			      else {					
					 $user=$temp_user?"$temp_user:$seed":$seed;
				  }
				  $dir = dirname(metaFN("epub:$user:tmp",'.meta')) . '/'; 
				  echo "working directory: $dir\n";
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
			epub_write_zip($url);
			return $item_num;
		}

		 function epub_zip_handle($path=null) {
		    static $zip; 
			if(!class_exists ('ZipArchive'))  return false;
			if($path && !$zip) {
			       $zip = new ZipArchive;
                   $zip->open($path);			    
		    }
			
			return $zip;
		 }

		 function epub_write_zip($url) {
		     static $zip;
			 static $oebps;
			 
			 if(!$zip)  { 
			    $zip = epub_zip_handle();
				if($zip) 	$oebps = epub_get_oebps(); 			 
			}	
			 if($zip) {
			      $file = $oebps . $url;
			      $zip->addFile($file, "OEBPS/$url");
			 }
			 
		 }
		 
	    function epub_finalize_zip() {
	        $zip = epub_zip_handle() ;
		    if(!$zip)  return false;			
            epub_write_zip('content.opf');
            epub_write_zip('toc.ncx');
		    $zip->close();
		    return true;
        }
		
		function epub_write_ncx() {
		    $toc  = epub_get_oebps()  . 'toc.ncx';      
			
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
		     if(isset($_POST['client'])) {
				  $user= rawurldecode($_POST['client']) . '/';				  
				  io_mkdir_p($media_dir. '/'. $user);
			  }
			
			copy(EPUB_DIR . 'scripts/package/my-book.epub', $dir . 'my-book.epub');
			copy(EPUB_DIR . 'scripts/package/container.xml', $dir . 'META-INF/container.xml');					
		    $zip = epub_zip_handle($dir . 'my-book.epub');
			if($zip) {
			    $zip->addFile(EPUB_DIR . 'scripts/package/container.xml', 'META-INF/container.xml');
			}
		}
		
        function epub_push_spine($page=null) {
		    static $spine = array();
			if(!$page) return $spine;
			$spine[] = $page;
			
		}	
		function epub_pack_book() {		  
		    echo "packing epub\n";
		
		     $user = "";
		     if(isset($_POST['client'])) {
				  $user= rawurldecode($_POST['client']) . '/';
			  }
		    $meta = epub_get_metadirectory() ;
			 
			 
			 if(!epub_zip_handle()) {
			    chdir($meta);			 
			    echo rawurlencode("*nix zip command used \n");
			    $cmd =  'zip -Xr9Dq my-book.epub *';
			    exec($cmd);
			} 
			else echo "ziparchive used\n";
			$media_dir = epub_get_data_media() . 'epub/' . $user;			
			$oldname = $meta . 'my-book.epub';	        
			$epub_file = strtolower(date("Y_F_j_h-i-s") ) . '.epub';
			$newname = $media_dir .  $epub_file;

			if(rename ($oldname , $newname )) {
			   if($user) $user= str_replace('/',':',$user);
			   echo "New Ebook: epub:" . $user . "$epub_file\n" ;
			}
		}	 
		