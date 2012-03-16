
function epub_show_throbber(user,client) {
		
	var dom = document.getElementById('epub_throbber');  
	if(!dom || !user || !epub_title) return;
	dom.style.display='block'; 
	var params="user="+encodeURIComponent(user);

	params += "&location="+encodeURIComponent(window.location);
	params += "&title="+encodeURIComponent(epub_title);
	
	epub_id=epub_id.join(';;');
	epub_id= epub_id.replace(/^;;/,"");
	epub_ids= epub_id.replace(/;;$/,"");	
	params+="&epub_ids="+encodeURIComponent(epub_id);
	
    if(client) {
	params += "&client="+encodeURIComponent(client);		
	}
	jQuery.post(
    DOKU_BASE + 'lib/plugins/epub/scripts/ebook.php',
    params,
    function (data) {       	
		dom.innerHTML = '<div style="width:800;overflow:auto;text-aling:left"><pre>' +decodeURIComponent(decodeURIComponent(data)) + '</pre></div>';   
       regex=/Ebook:\s+(:?epub.*?\.epub)/;
       result = regex.exec(dom.innerHTML); 
	   if(result) {
           var epub='http://'+ location.host  + DOKU_BASE + '/lib/exe/fetch.php?media=' + result[1];
           dom.innerHTML +='<div><center><a href="' + epub + '" class="media mediafile mf_epub" title="' + result[1] +'">' + result[1] +'</a></center></div>';
	   }
	},
    'html'
	);
	
	//epub_wikilink
	//epub_id
}

function epub_remove_creator(id) {
    var params="remove="+encodeURIComponent(id);
	jQuery.post(
    DOKU_BASE + 'lib/plugins/epub/scripts/update_files.php',
    params,
    function (data) {       	
alert(data);
	},
    'html'
	);

}


function _epub_show_throbber(user,client) {
	      epub_show_throbber(user,client);
		  var dom = document.getElementById('show_throbberbutton');
		  dom.style.display='none';
}	  
