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
    if(book_id) {
        params+= "&book_page="  + encodeURIComponent(book_id);		      
    }
	jQuery.post(
    DOKU_BASE + 'lib/plugins/epub/scripts/ebook.php',
    params,
    function (data) {       	
        dom.innerHTML = '<div style="width:800;overflow:auto;text-align:left"><pre>' +decodeURIComponent(decodeURIComponent(data)) + '</pre></div>';   
       regex=/Ebook:\s+(:?epub.*?\.epub)/;
       result = regex.exec(dom.innerHTML); 
	   if(result) {
           var epub='http://'+ location.host  + DOKU_BASE + 'lib/exe/fetch.php?media=' + result[1];
           dom.innerHTML +='<div><center><a href="' + epub + '" class="media mediafile mf_epub" title="' + result[1] +'">' + result[1] +'</a></center></div>';
	   }
	},
    'html'
	);
	
}

function epub_stringifyArray(ar) {
    ar=ar.join(';;');
	ar= ar.replace(/^;;/,"");
	ar= ar.replace(/;;$/,"");	
    return encodeURIComponent(ar);
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
     epub_check_progress(client,user);
}	  


function epub_check_progress(client,user) {
   var dom = document.getElementById('epub_progress');
     var params="user="+encodeURIComponent(user);
     if(client) {
  	   params += "&client="+encodeURIComponent(client);		
	}
    var secs = 0;
    var t =window.setInterval(function() {
        jQuery.post(
        DOKU_BASE + 'lib/plugins/epub/scripts/check_progess.php',
        params,
        function (data) {      
            if(!data) window.clearInterval(t);
            secs++;
            dom.innerHTML = data + '[seconds:  ' + secs*15 + ']';        
        },
        'html'
        );
        },
        15000);
}	  

function epub_admin_confirm(which) {
 
    var f =document.epub_admin;
    var epubs = new Array();    
    var msg="";    
    for(var i=0; i < f.length; i++) {  
         if(f[i].checked) {
             matches =  f[i].name.match(/\[([a-z0-9]+)\]/);
             msg += f[i].value + "\n" ;           
         }         
    }
    var confirm_msg = "Please confirm that you want to delete the following cache entries"; 
    if(which == 'media') {
       confirm_msg +=" and their ebooks";     
    }
    if(!confirm(confirm_msg + ":\n" + msg)) return false;
    return true;
}    
