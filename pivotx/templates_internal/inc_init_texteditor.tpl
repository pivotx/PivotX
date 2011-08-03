
<!-- start of texteditor -->
<script language="javascript" type="text/javascript">
[[ literal ]]

// This function gets called after placing an image..
function doImage(image_name, image_alt, image_title, image_align, name) {

    $('#f_image').val(image_name);

    var text = '[[image file="'+image_name+'" ';
    if (image_alt != "") { text += 'alt="'+image_alt+'" '; }
    if (image_title != "") { text += 'title="'+image_title+'" '; }
    if (image_align != "center") { text += 'align="'+image_align+'" '; }
    text += ' ]]';

    setSel(text);

    $('#dialogframe').dialog('close');
    
}

// This function gets called after inserting a popupimage..
function doPopupImage(image_name, image_alt, image_align, f_popup_descr, name) {

    $('#f_image').val(image_name);
    $('#f_hasthumb').val(f_popup_descr);

    var text = '[[popup file="'+image_name+'" ';
    if (f_popup_descr != "") { text += 'description="'+f_popup_descr+'" '; }
    if (image_alt != "") { text += 'alt="'+image_alt+'" '; }
    if (image_align != "center") { text += 'align="'+image_align+'" '; }
    text += ' ]]';

    setSel(text);

    $('#dialogframe').dialog('close');
    
}

// This function gets called after inserting a download..
function doDownload(file_name, f_icon, f_text, f_title, name) {
    if (f_icon == 'icon') { f_text = ''; }
    text = '[[download file="'+file_name+'" icon="'+f_icon+'" text="'+f_text+'" title="'+f_title+'" ]]';
    setSel(text);
    
    $('#dialogframe').dialog('close');
    
}

// This function gets called after inserting a tag..
function doTag(tagname, link) {
    if (link == '') {
        text = '[[tt tag="' + tagname + '" ]]';
    } else {
        text = '[[tt tag="' + tagname + '" link="' + link + '" ]]';
    }
    setSel(text);
    
    $('#dialogframe').dialog('close');
    
}


var targetField = "";

/**
 * Get the current selection from the markitup 'target'
 *
 * @param string target
 */
function getSel(target) {

    targetField = $("textarea[name="+target+"]")[0];

    targetField.focus();
    scrollPos = targetField.scrollTop;
    if (document.selection) {
        selection = document.selection.createRange().text;
        if ($.browser.msie) { // ie
            var range = document.selection.createRange();
            var rangeCopy = range.duplicate();
            rangeCopy.moveToElementText(targetField);
            openPos = -1;
            while(rangeCopy.inRange(range)) { // fix most of the ie bugs with linefeeds... but not all :'(
                rangeCopy.moveStart("character");
                openPos ++;
            }
        } else { // opera
            openPos = targetField.selectionStart;
            closePos =  targetField.selectionEnd;
        }
    } else if (targetField.selectionStart || targetField.selectionStart == "0") { // gecko
        openPos = targetField.selectionStart;
        closePos = targetField.selectionEnd;
        selection = targetField.value.substring(openPos, closePos);
    } else {
        selection = "";
    }

    return selection;
}

/**
 * Set the current selection of the markitup 'targetField' to 'content'
 *
 * @param string target
 * @param string content
 */
function setSel(content) {

    $.markItUp({ target:targetField, replaceWith: content } );

}



[[/literal]]

</script>
<!-- end of texteditor -->

