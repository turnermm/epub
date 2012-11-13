<?php
	    if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../../').'/');
        if(!defined('NOSESSION')) define('NOSESSION',true); 		
	    require_once(DOKU_INC.'inc/init.php');

        function epub_get_progress_dir($temp_user=null) {
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
		    }
				 
		    return $dir;    
	    }
        
        $user= rawurldecode($_POST['user']);
        if($user) $user=cleanID($user); 
        $dir = epub_get_progress_dir($user); 
        $dir = rtrim($dir,'/');
        $dir = dirname($dir . ".meta") . '/';
        $progress_file = $dir . "progress.meta";
        $content =  io_readFile($progress_file);
        if($content) {
          echo "$content\n";
        }
        else echo "";
        
