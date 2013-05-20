<?php
/**
 * 
 * Admin Panle for epub plugin
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Myron Turner <turnermm02@shaw.ca>
 */

class admin_plugin_epub extends DokuWiki_Admin_Plugin {

    var $output = '';
    private $helper;
    private $cache;
    private $req;
    private $metadir;
    private $dbg=false;
    private $results = "";
    function __construct () {
        $this->helper = $this->loadHelper('epub', true);
        $this->cache = $this->helper->getCache() ;
        $this->metadir = metaFN('epub',"");
    }
    /**
     * handle user request
     */
    function handle() {
    
      if (!isset($_REQUEST['cmd'])) return;   // first time - nothing to do

     $msg="";
      if (!checkSecurityToken()) return;
      if (!is_array($_REQUEST['cmd'])) return;
      $epub_deletions = array();
      $which = key($_REQUEST['cmd']);
      foreach($_REQUEST['book_id'] as $md5=>$id) {      
            if($which == 'media') {             
                 $epub_deletions[] = $this->helper->delete_media($md5);                         
            }
            $this->helper->delete_page($md5);         
      }
      if(is_array($_REQUEST['book_id'])) $this->cache = $this->helper->getCache() ;
      if(count($epub_deletions)) {
        $this->results = "<b>Results</b><br />";
        $this->results .= implode('<br />', $epub_deletions);
      }
      /* if debugging */
     if($this->dbg) {
        $this->req = print_r($_REQUEST,true) . $msg . "which=$which\n";
        $this->req .= print_r($epub_deletions,true);
        $this->req = str_replace("\n","<br />",$this->req);
     }
     
    }
 
   
    function html() {
    
     ptln( $this->locale_xhtml('admin_header'));
    
     $cache = $this->cache;
      $current_books = $this->cache['current_books'];
      unset ($cache['current_books']); 
      
      //ptln('<form action="'.wl($ID).'" method="post" onsubmit="return epub_admin_confirm(this);" id="epub_admin" name="epub_admin">' ); 
      ptln('<form action="'.wl($ID).'" method="post"  id="epub_admin" name="epub_admin">' ); 
      echo "<ul>\n";
      foreach($cache as $md5=>$id) {
      $id=trim($id);
       if(!$id) $id = '&lt;undefined&gt;';
         ptln('<li style="list-style-type:none; color: #333;"><input name="book_id[' . $md5 .']" type="checkbox" value="' . $id. '">&nbsp;'  . $id );     
         ptln('   <ul><li style="color:#333">' . $current_books[$md5]['title'] . "\n" . '   <li style="color:#333">' . $current_books[$md5]['epub']);
         ptln("   <!--input type = 'hidden' name='$md5' value='" . $current_books[$md5]['epub'] ."'/ -->\n   </ul>");
         
         
      }
      echo "  </ul>\n";
      
      ptln('  <input type="hidden" name="do"   value="admin" />');
      ptln('  <input type="hidden" name="page" value="'.$this->getPluginName().'" />');
      formSecurityToken();   
      ptln('  <input type="submit" name="cmd[cache]" onclick="return epub_admin_confirm(\'cache\');"  id="epub_cache_btn" value="'.$this->getLang('btn_submit').'" />'); 
      ptln('&nbsp;&nbsp;&nbsp;<input type="submit" onclick="return epub_admin_confirm(\'media\');" name="cmd[media]" id="epub_media_btn" value="'.$this->getLang('btn_del').'" />'); 
      ptln('</form>');
      
      if($this->dbg) {
         echo $this->req . "<br />";
       } 
       if($this->results) {
        ptln('<p><br />' .$this->results . '</p>');
       }
    }
 
}
