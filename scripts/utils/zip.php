<?
if(!class_exists('ZipArchive')) {
  $system = php_uname('s') ;
  if(strpos('Windows',$system) !== false) {
    $which = exec('which zip');
	if($which != '/usr/bin/zip' ||  preg_match('/no\s+zip')) {
	    
	}
  }

}
exit;
$zip = new ZipArchive;
if ($zip->open('my-book.epub') === TRUE) {
    $zip->addFile('content.html','OEBPS/content.html');
    $zip->close();
    echo 'ok';
} else {
    echo 'failed'