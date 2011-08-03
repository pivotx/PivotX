function openPivotXmarklet(){

    if(typeof jQuery!='undefined') {
        if (!jQuery('#PivotXmarklet').is('*')) {
        
            var url = encodeURIComponent(location.href)
            var title = encodeURIComponent(document.title);
                
            var sel = "";
            if (window.getSelection) { sel = window.getSelection(); }
            else if (document.getSelection) { sel = document.getSelection(); }
            else if (document.selection) { sel = document.selection.createRange().text; }
            sel = encodeURIComponent(sel);
              
            var url = PX_URI + 'index.php?page=bookmarklet&url='+url+'&title='+title+'&selection='+sel;
        
            jQuery('body').append('<div style="border:2px solid #666; width: 416px; height: 322px; position: fixed; right: 24px; top: 22px; background-color: #EEE; z-index: 100000;" id="PivotXmarklet"></div>');
            jQuery('body').append('<div style="background-color: #666; position: fixed; right: 24px; top: 6px; padding: 2px;" id="PivotXclose"><a href="javascript:closePivotXmarklet();" style="font-family: Arial, Helvetica; font-size: 11px; color: #FFF; text-decoration: none;">close</a></div>' );
            jQuery('#PivotXmarklet').append('<iframe src="'+url+'" border="0" frameborder="0" width="416" height="322" id="PivotXframe"></iframe>');
        }
    } else {
        window.setTimeout('openPivotXmarklet()', 250);
        var s=document.createElement('script');
        s.setAttribute('src', 'http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.js');            
        document.getElementsByTagName('head')[0].appendChild(s);
    }
    
};


function closePivotXmarklet(){
    jQuery('#PivotXmarklet').remove();
    jQuery('#PivotXclose').remove();
}

openPivotXmarklet();
