
<!-- tinyMCE -->
<script type="text/javascript">
//<![CDATA[

tinyMCE_GZ.init({
    plugins : "paste,fullscreen", // add contextmenu if you don't want Firefox's spellchecker
    themes : 'advanced',
    languages : 'en', // FIXME: select the right language. 
    disk_cache : true,  
    debug : false
}, function() {

    tinyMCE.init({
        theme : "advanced",
        language : "en", // FIXME: select the right language. 
        mode : "exact",
        elements : "introduction",
        extended_valid_elements : "a[href|target|name|title|rel|class|id],hr[class|width|size|noshade],font[face|size|color|style|class]," +
            "span[class|align|style],img[id|class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],"+
            "br[class|clear|id|style|title],object[width|height|classid|codebase|id|data|type],param[name|value|_value],embed[name|src|type|wmode|width|height|style|allowScriptAccess|menu|quality|pluginspage],small," + 
            "form[name|id|action|method|enctype|accept-charset|onsubmit|onreset|target],option[name|id|value]," + 
            "input[id|name|type|value|size|maxlength|checked|accept|src|width|height|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect]," +
            "textarea[id|name|rows|cols|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect]," + 
            "select[id|name|type|value|size|maxlength|checked|accept|src|width|height|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect|length|options|selectedIndex]", 
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_path_location : "none",
        theme_advanced_resizing : false,
        theme_advanced_resize_horizontal : false,
        apply_source_formatting : false,
        theme_advanced_buttons1 : "formatselect,removeformat,fullscreen,|,bold,italic,underline,strikethrough,bullist,numlist,|," + 
            "link,unlink,charmap,code",
        theme_advanced_buttons2 : "",
        theme_advanced_buttons3 : "",
        debug : false,
        fix_list_elements : false,
        plugins : "paste,fullscreen", // add contextmenu if you don't want Firefox's spellchecker
        convert_urls : false
    });

});

//]]>
</script> 


<!-- end of tinyMCE -->

