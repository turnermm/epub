function epub_show_throbber(user,client) {	
	var dom = document.getElementById('epub_throbber');  
	if(!dom || !user || !epub_title) return;
	dom.style.display='block'; 
	var params="user="+encodeURIComponent(user);

	params += "&location="+encodeURIComponent(window.location);
	params += "&title="+encodeURIComponent(epub_title);
	
	params+="&epub_ids="+epub_stringifyArray(epub_id);
    params+="&epub_titles="+epub_stringifyArray(epub_wikilink);
	
    if(client) {
	params += "&client="+encodeURIComponent(client);		
	}
	var url =  DOKU_BASE + 'lib/plugins/epub/scripts/ebook.php';
	epub_post(url,params,  function (data) {       	
		dom.innerHTML = '<div style="width:800;overflow:auto;text-aling:left"><pre>' +decodeURIComponent(decodeURIComponent(data)) + '</pre></div>';   
       regex=/Ebook:\s+(:?epub.*?\.epub)/;
       result = regex.exec(dom.innerHTML); 
	   if(result) {
           var epub='http://'+ location.host  + DOKU_BASE + '/lib/exe/fetch.php?media=' + result[1];
           dom.innerHTML +='<div><center><a href="' + epub + '" class="media mediafile mf_epub" title="' + result[1] +'">' + result[1] +'</a></center></div>';
	   }		
	});
}

function _epub_show_throbber(user,client) {
	      epub_show_throbber(user,client);
		  var dom = document.getElementById('show_throbberbutton');
		  dom.style.display='none';
}	  

function epub_remove_creator(id) {
    var params="remove="+encodeURIComponent(id);
  	var url =  DOKU_BASE + 'lib/plugins/epub/scripts/update_files.php';
    epub_post(url,params,  function (data) {   	    
           	            alert(data);
	    }
    );    
}

function epub_post(url,params,callback) {
     var s = new sack(url);
	 s.onCompletion = function() {
        	if (s.responseStatus && s.responseStatus[0] == 200) {   
                  callback(s.response);
        	}
         };
		s.runAJAX(params);	 
}	

function epub_stringifyArray(ar) {
    ar=ar.join(';;');
	ar= ar.replace(/^;;/,"");
	ar= ar.replace(/;;$/,"");	
    return encodeURIComponent(ar);
}
