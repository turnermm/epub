<?php
	/**
		
	*/
	
	// must be run within Dokuwiki
	if (!defined('DOKU_INC')) die();
	
	if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
	if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
	if(!defined('EPUB_DIR')) define('EPUB_DIR',realpath(dirname(__FILE__).'/../').'/');
	require_once DOKU_INC.'inc/parser/xhtml.php';
	
	class renderer_plugin_epub extends Doku_Renderer_xhtml {
		private $opf_handle;		
		private $oebps;
		private $current_page;
		private $allow_url_fopen; 
		private $isWin;
		function getInfo() {
			return array(
            'author' => 'Myron Turner',
            'email'  => 'turnermm02@shaw.ca',
            'date'   => '2011-07-1',
            'name'   => 'epub',
            'desc'   => 'renderer for ebook creator',
            'url'    => 'http://www.dokuwiki.org/plugin:epub');
		}
		
		function __construct() {     
            $this->allow_url_fopen=ini_get ( 'allow_url_fopen' ) ;			
            $this->isWin=function_exists('epub_isWindows') ? epub_isWindows() : false;
			
		}
		
		/**
			* Make available as XHTML replacement renderer
		*/
		public function canRender($format){
			if($format == 'xhtml') return true;
			return false;
		}
		
		function set_oebps() {
		   $this->oebps = epub_get_oebps(); 
		}
        function set_current_page($page) {
		   $this->current_page=$page;
		}
		
		function opf_handle() {
			if(!$this->opf_handle) {
				$this->opf_handle= fopen($this->oebps. 'content.opf', 'a');
			}
			return $this->opf_handle;
		}
		
		function is_epubid($id) {
			return is_epub_pageid($id);
		}	
		/**
			* Simplified header printing with PDF bookmarks
		*/
		function header($text, $level, $pos) {
			if(!$text) return; //skip empty headlines
			
			// print header
			$this->doc .= DOKU_LF."<h$level>";
			$this->doc .= $this->_xmlEntities($text);
			$this->doc .= "</h$level>".DOKU_LF;
		}
		
		/**
			* Wrap centered media in a div to center it
		*/
		function _media ($src, $title=NULL, $align=NULL, $width=NULL,
		$height=NULL, $cache=NULL, $render = true) {
			$mtype = mimetype($src);
			if(!$title) $title = $src;
		    $external = false;       
            $src = trim($src);
            if(strpos($src,'http://') === 0) $external = true;
            if($external && !$this->allow_url_fopen)  {
                $link = $this->create_external_link($src);
                return $this->_formatLink($link);                
            }
			$src = $this->copy_media($src,$external);
			
			if($align == 'center'){
				$out .= '<div align="center" style="text-align: center">';
			}
			if(strpos($mtype[1],'image') !== false)       {	             
				$out .= $this->set_image($src,$width,$height);                
			}
			else {		 		 
				$out .= "<a href='$src'>$title</a>";
			}
			
			
			if($align == 'center'){
				$out .= '</div>';
			}
			
			return $out;
		}
		
        function create_external_link($name) {
            return array(
                "target" => "",
                "style" => "",
                "pre" => "",
                "suf" => "",
                  "type"=>'ext_media',
                "more" =>  'rel="nofollow"',
                "class" => 'urlextern',
                "url" => $name,
                "name" => basename($name),
                "title" => $name
              );  
        }
        
		/**
			* hover info makes no sense in PDFs, so drop acronyms
		*/
		function acronym($acronym) {
			$this->doc .= $this->_xmlEntities($acronym);
		}
		
		
		function is_image($link,&$type) {
			
			if(strpos($link['class'],'media') === false)  {
				$type=$link['class'];
				return false;
			}
			
			$mime_type = mimetype($link['title']);
			if(!$mime_type[1] ) {
				list($url,$rest) = explode('?', $link['url']); 		      
				$mime_type = mimetype($url);
			}
			
			if(!$mime_type[1]) {
				$type = 'other';
				return false;
			}
			
			list($type,$ext) = explode('/', $mime_type[1] );
			
			if($type != 'image') {
				$type='media';
				return false;
			}
			
			return true;
		}
		/**
			* reformat links if needed
		*/
		
		function _formatLink($link){
			$type = "";           
           
			if($this->is_image($link,$type)) {
				if(preg_match('#media\s*=\s*http#',$link['url'])) {
					$name = $this->copy_media($link['title'],true);
					if($name) {
  		  			    return $this->set_image($name) ;
					}
					else return $this->set_image('');
				}
				elseif(strpos($link['name'],'<img') !== false) {
					$name = $this->copy_media($link['title']);
					if(strpos($link['name'],'fetch.php') !== false) {		
                        $t = $link['title'];
						$link['name'] = preg_replace("#src=.*?$t\"#", "src=\"$name\"",$link['name']);
						if(strpos($link['name'],'alt=""') !==false) {
							$link['name'] = str_replace('alt=""','alt="'. $link['title'] . '"', $link['name']);
						}                       
						return $this->clean_image_link($link['name']);
					}
                          
                    $link['name'] = $this->clean_image_link($link['name']);                   
                            
					return $link['name'];
				}
				
				$name = $this->copy_media($link['title']);
				return $this->set_image($name);
			}            
            elseif($link['class'] == 'media' && strpos($link['name'],'<img') !== false) {  
                $this->doc .= '<a href="'  .  $link['url']  .'" class="media" title="' . $link['title'] . '"  rel="nofollow">' . $link['name'] . '</a>';
                return;
            }
            
			if((strpos($link['class'],'wikilink') !== false ) && $type!='media') {  //internal link	
				$orig = "";
				$name = $this->local_name($link,$orig);			
				if(!$this->is_epubid($orig)) {		    
					$doku_base = DOKU_BASE;
					$doku_base = trim($doku_base,'/');							
					$fnote =  DOKU_URL .  "doku.php?id=$orig";	
					return $this->set_footnote($link,$fnote);
				}
              
               $name = epub_clean_name($name);
				$name .='.html';
                if($link['class'] == 'wikilink2') {
                    $wfn =  wikiFN($orig);
                    if( file_exists($wfn) ) $link['class'] =  'wikilink1';                    
                }
                
			}
			else if($type=='media') {  //internal media
				$orig = "";				
				$name = $this->local_name($link,$orig);			
			    $note_url =  DOKU_URL .  "lib/exe/fetch.php?media=" . $orig;
                $link['class'] = 'wikilink1';
				$out = $this->set_footnote($link,$note_url);
				$out=preg_replace('/<a\s+href=\'\'>(.*?)<\/a>(?=<a)/',"$1",$out);		//remove link markup from link name					
				return $out;			   				
			}
			elseif($link['class'] != 'media') {   //  or urlextern	or samba share or . . .	
                $out = $this->set_footnote($link,trim($link['url']));		// creates an entry in output for the link  with a live footnote to the link	
                if($link['type'] == 'ext_media') {
                    $this->doc .= $out;
                }
                else return $out;			  
			}
			
			if(!$name) return;
			$link['url'] = $name;			

			return parent::_formatLink($link);
		}
		
         function clean_image_link($link) {
                $link = str_replace('Images/Images',"Images",$link);
                $link = preg_replace('#[\.\/]*Images#', "../Images", $link );
                return $link;
         }
         function set_footnote($link, $note_url="") {
 					$out = $link['name'];
					$fn_id = epub_fn();
					$link['name'] = "[$fn_id]";
                    if(preg_match("/media\s*=\s*(http.*)/", $link['url'],$matches)) {   //format external  urls
                        $note_url = urldecode($matches[1]);
                    }
					$link['url'] = 'footnotes.html#' .$this->current_page;					
					$link['class'] = 'wikilink1';
                    $id = 'backto_' . $fn_id;
					$hash_link="<a id='$id' name='$id'>";
					$out .= $hash_link . parent::_formatLink($link) . '</a>';
					epub_write_footnote($fn_id,$this->current_page,$note_url);
					return $out;

         }
		
        function smiley($smiley) {
            static $smileys;            
             if(!$smileys) $smileys = getSmileys();
             
             if ( array_key_exists($smiley, $this->smileys) ) {
                 $spath = DOKU_INC . 'lib/images/smileys/'.$smileys[$smiley];
                 $name = $this->copy_media($spath,true);
                 $this->doc .= $this->_media($name);
             } 
         }        
        
        function local_name($link,&$orig="") {
            $base_name= basename($link['url']);
            $title = $link['title']? ltrim($link['title'],':'): "";
            if ($title) {
                $name = $title;
            }  
            elseif($base_name) {
                list($name,$rest) = explode('?',$base_name);
            }

            if($name) {
                $orig = ltrim($name,':');               
                return epub_clean_name(str_replace(':','_',$name));
            }
            return false;
        }
	
		function copy_media($media,$external=false) {
			$name =  epub_clean_name(str_replace(':','_',basename($media)));		
            $ret_name = $name;  
           
			$mime_type = mimetype($name);
			list($type,$ext) = explode('/', $mime_type[1] );
			if($type !== 'image') return;
			if($external) {  
                if(!$this->allow_url_fopen) return;
                $tmp =  str_replace('https://',"",$media);       
                $tmp =  str_replace('http://',"",$media);
                $tmp =  str_replace('www.',"",$tmp);
				if($this->isWin) {
				    $tmp =  preg_replace('/^[A-Z]:/',"",$tmp);
				}
                $tmp=ltrim($tmp,'/\/');
                
                $elems=explode('/',$tmp);                       
				if($this->isWin) {
				    $elems=explode('\\',$tmp);                       
				}
                if(count($elems && $elems[0])) {
                    $elems[0] = preg_replace('#/\W#','_',$elems[0]);
                    $name = $elems[0]. "_" . $name;
                }
            }
           
            if(!preg_match("/^Images/", $name)) {
                $name = "Images/$name";			    
            }

		    $file = $this->oebps . $name;
			if(file_exists($file)) return $name;
			if(!$external) {            
				$media = mediaFN($media);
			}
		    
			if(copy ($media ,  $file)) {			
				epub_write_item($name,$mime_type[1]) ;
				return $name;
			}
            else if(!$this->isWin && epub_save_image($media ,  $file)) {
            	epub_write_item($name,$mime_type[1]) ;
				return $name;
            }
			return false;
		}
	
		function set_image($img,$width=null,$height=null) {
			$w="";
			$h="";
			if($width)   $w= ' width="' . $width . '"';
			if($height)   $h= ' height="' .$height . '"';
            $img = $this->clean_image_link($img);
			//echo hsc('<img src="' . $img . '"' .  "$h $w " . ' alt="'. $img . '" class="media" />') ."\n";
			return '<img src="' . $img . '"' .  "$h $w " . ' alt="'. $img . '" class="media" />';
		}
	
        function plugin($name,$data) {		
		
		    if($name !='mathpublish' && $name !='ditaa') return parent::plugin($name,$data);
		    $mode ='xhtml';
		    $renderer =p_get_renderer($mode );		
            $plugin =& plugin_load('syntax',$name);
            if($plugin != null) {			
                $plugin->render($mode,$renderer,$data);
	            
		        if($name == 'ditaa') {
			        epub_check_for_ditaa($renderer->doc,$this);					    
                }
				else if($name =='mathpublish') {
					
					epub_check_for_math($renderer->doc,$this);					
				}
				$this->doc .= $renderer->doc;
		    }
        }

		/**
			* no obfuscation for email addresses
		*/
		function emaillink($address, $name = NULL) {
			global $conf;
			$old = $conf['mailguard'];
			$conf['mailguard'] = 'none';
			parent::emaillink($address, $name);
			$conf['mailguard'] = $old;
		}
		
		function write($what,$handle) {
			fwrite($handle,"$what\n");
			fflush($handle);
		}
	}
	
