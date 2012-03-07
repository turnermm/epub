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
	},
    'html'
	);
	
	//epub_wikilink
	//epub_id
}

function _epub_show_throbber(user,client) {
	      epub_show_throbber(user,client);
		  var dom = document.getElementById('show_throbberbutton');
		  dom.style.display='none';
}	  
