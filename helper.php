<?php
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

class helper_plugin_epub extends Dokuwiki_Plugin { 
	private $script_file;
	private $cache;
    function __construct() {
            $this->script_file = metaFN('epub:cache', '.ser');
            $this->cache = unserialize(io_readFile($this->script_file,false));
            if(!$this->cache) $this->cache = array();  
		
    }
    
	function msg($text) {
	    if(is_array($text)) {
		   $text = '<pre>' . print_r($text,true) . '</pre>';
		}		
		echo "$text\n";
	}
	

    function is_inCache($id) {        
         $md5 = md5($id);
         if(isset($this->cache[$md5])) return true;
         return false;
    }
    
	function remove_page($id) {
	     $md5 = md5($id);
         $this->delete_page($md5);
    }
    
    function delete_page($md5) {    
		 unset($this->cache[$md5]);
         if(isset($this->cache['current_books'][$md5])) {
            unset($this->cache['current_books'][$md5]);
         }         
		 io_saveFile($this->script_file,serialize($this->cache));	
	}

    function delete_media($md5) {
        $epub = $this->cache['current_books'][$md5]['epub'];
        $file =  mediaFN($this->cache['current_books'][$md5]['epub']);
        if(file_exists($file)) {
            if(unlink($file)) {
               return "Removed: " . $epub;
            }
            else return "error unlinking $epub";
        }
        return "File not found: " . $this->cache['current_books'][$md5]['epub'] ;
    }
    function writeCache($id) {
         if(!$this->is_inCache($id)) {
            $this->cache[md5($id)] = $id;
            io_saveFile($this->script_file,serialize($this->cache));	
             return true;
         }
         return false;
    }
    
    function getCache() {
         return  $this->cache;
    }   
    
    
    function addBook($id,$epub,$title) {
         $md5 = md5($id);
         if(!$this->is_inCache($id)) {
            $this->cache[$md5] = $id;            
         }
         if(!isset($this->cache['current_books'])) {
            $this->cache['current_books'] = array();
         }
         $this->cache['current_books'][$md5] = array('title'=>$title, 'epub'=>$epub);
         io_saveFile($this->script_file,serialize($this->cache));
    }
    
    function get_page_data($id) {
         $md5 = md5($id);
         if(isset($this->cache['current_books'][$md5])) {
            return $this->cache['current_books'][$md5];
         }
         return false;
    }
}
	 
