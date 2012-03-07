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
			
			$src = $this->copy_media($src);
			
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
						return $link['name'];
					}
					$link['name'] = str_replace($link['title'],$name,$link['name']);
					return $link['name'];
				}
				
				$name = $this->copy_media($link['title']);
				return $this->set_image($name);
			}
			
			
			
			if(strpos($link['class'],'wikilink') !== false ) {  //internal link	
				$orig = "";
				$name = $this->local_name($link,$orig);			
				if(!$this->is_epubid($orig)) {		    
					$doku_base = DOKU_BASE;
					$doku_base = trim($doku_base,'/');		
					$out = $link['name'] . ' (URL: ' . DOKU_URL . $doku_base  . "?id=$orig)";
					//echo "$out\n";
					return $out;
				}
				$name .='.html';
			}
			else if($type=='media') {  //internal media
				$name = $this->local_name($link); 	
			}
			elseif($link['class'] != 'media') {   //  or urlextern	or samba share or . . .		
			   if($link['name']  == $link['url']) return $link['url'];
				return   $link['name']  . ' ['. $link['url']  . ']';
			}
			
			if(!$name) return;
			$link['url'] = $name;			

			return parent::_formatLink($link);
		}
		
		function local_name($link,&$orig="") {
			$base_name= basename($link['url']);
			$title = $link['title']? ltrim($link['title'],':'): "";
			if($name) {
				list($name,$rest) = explode('?',$base_name);
				$name=ltrim($name,':');
				if($title && ($name != $title)) $name = $title;
			}
			else if ($title) $name = $title;
			if($name) {
				$orig = ltrim($name,':');
				return str_replace(':','@',$name);
			}
			return false;
		}
		
		function copy_media($media,$external=false) {
			
			$name =  str_replace(':','@',basename($media));		
			$file = $this->oebps . $name;
		
			if(file_exists($file)) return $name;
			if(!$external) {
				$media = mediaFN($media);
			}
			
			if(copy ($media ,  $file)) {
				$mime_type = mimetype($name);
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
			
			return '<img src="' . $img . '"' .  "$h $w " . ' alt="'. $img . '" class="media" />';
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
	
