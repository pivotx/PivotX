

// Don't break on browsers without console.log();
if (typeof(console) === 'undefined') { console = { log: function() {}, assert: function() {} }; }

/**
 * Some events that are initialized during page load.
 */
jQuery(function($) {

    // Initialise the main menu
    if($("ul.sf-menu").is('*')) { 
        $("ul.sf-menu").superfish({ 
            pathClass:  'current' 
        });
    }

    // Make 'suggested tags' clickable in 'new entry' screen.
    $("a[rel=tag]").click( function(tag){

        var keywords = $('#f_keywords').val();
        var tagfield = $('#f_tag').val();
        var tag = $(this).html();

        if (keywords=="") {
            $('#f_keywords').val(tag);
        } else {
            $('#f_keywords').val(keywords + ", " + tag);
        }

    });

    // Add calendar popups to all inputs with class 'date-picker'
    if($('input.date-picker').is('*')) { 
        $('input.date-picker').datepicker({
            yearRange:'1970:2099', dateFormat: 'dd-mm-yy', gotoCurrent: true
        });
    }
    
    // Handler for Dialog links..
    $('.dialog').bind('click', function(e) {
                
        e.preventDefault();
        
        // If we passed an extra class to set the type of dialog, we use it here
        // to set the size of the window.
        if ($(this).hasClass('editor')) {
            var dialogwidth='800';
            var dialogheight='540';
        } else if ($(this).hasClass('user')) {
            var dialogwidth='540';
            var dialogheight='620';
        } else if ($(this).hasClass('chapter')) {
            var dialogwidth='540';
            var dialogheight='240';
        } else if ($(this).hasClass('comment')) {
            var dialogwidth='540';
            var dialogheight='560';
        } else {
            var dialogwidth='460';
            var dialogheight='440';            
        }
         
        if (dialogheight > ($('body').height()-30)) {
            dialogheight = $('body').height()-30;
        }
        
        openDialog($(this).attr('title'), $(this).attr('href'), dialogwidth, dialogheight);
        
        return false;

    });


    // Make all textareas with class='resizeable' automagically resizeable
    makeResizeable();

    // A nasty fix, so that IE recognizes the 'mouseover'
    if($.browser.msie) {
        $('#nav li').hover(function(){ $(this).addClass("sfhover"); },function(){
            $(this).removeClass("sfhover");
        });
    }


    // Highlight the active form element
    $(".formclass input, .formclass select, .formclass textarea").focus( function(){
        $(this).addClass("activeinput");
    });
    $(".formclass input, .formclass select, .formclass textarea").blur( function(){
        $(this).removeClass("activeinput");
    });

    // Add an onclick event to all checkboxes that have a 'rowselect' class.
    $("input.rowselect").click( function() { rowSelect($(this)) });


    // Add masks to some inputs when writing/editing a new post or entry.
    if( $('input.date-picker').is('*') || $("#date1").is('*') ) {
        $("#publish_date1").inputmask("d-m-y");
        $("#publish_date2").inputmask("H-i");
        $("#date1").inputmask("d-m-y");
        $("#date2").inputmask("H-i");
    }

    // Add 'no wrap' to .clip-ed text, but only in FF and Opera..
    if ($.browser.mozilla || $.browser.opera) { $('.clip').addClass('extraclip'); }


    // If there's an input with id='keywords', we add tagging autocomplete to it.
    if ($("#keywords").is('*')) {
        // Make the #keywords an autocomplete field..    

        $("#keywords").autocomplete("./ajaxhelper.php?function=getTagSuggest", {
            multiple: true,
            multipleSeparator: " ",
            autoFill: true,
            delay: 150,
            width: 250,
            selectFirst: true,
            minChars: 2,
            max: 50
        });
        
        // Make sure pressing 'enter' in the autocompleter doesn't submit the form. 
        $("#keywords").bind("keypress", function(event) {
            if(event.keyCode == 13) { return false; }
        });
    
        // Get a small cloud of the 40 most populair tags. 
        getAllTags(40);

    }
        
    if($('#chapterjumpform')) {
        $('#chapterjumpform').bind('submit', function(e){e.preventDefault();});
        $('#pagesjumplist').bind('change', function(e, o) {
            //console.log($(this).val());
            window.location.hash = $(this).val();
        });
    }

    $('.moderate .comment-text').bind('click',function(e){
        e.preventDefault();

        if ($(this).hasClass('short-comment')) {
            $(this).closest('td').find('.long-comment').show();
        }
        else {
            $(this).closest('td').find('.short-comment').show();
        }
        $(this).hide();
    });
    $('a.approve-comment').bind('click',function(e){
        e.preventDefault();

        var comment_uid = jQuery(this).attr('data:comment');
        jQuery.ajax({
            url: jQuery(this).attr('href'),
            success: function(data){
                    if (data == 'ok') {
                        jQuery('.comment-'+comment_uid).hide();
                        highlightFirstComment();
                    }
                }
        });
    });
    highlightFirstComment();

    // Attach the handler for resizing the browser window. Call it almost immediately
    // as well. (yes, we have to do this with a timeout, and yes, we have to do it multiple times. Stupid Safari!)
    $(window).bind('resize', function(){ pivotxResizeWindow(true); });
    setTimeout( function(){ pivotxResizeWindow(false); }, 50 ); 


});


// Textarea resizer variables.
var startpos, diffpos=0, currentresizer = "", currentheight="", resizehandled=false;


// Make all textareas with class='resizeable' automagically resizeable
function makeResizeable() {

    // Safari 3 has this built in, so don't do it again.
    if ($.browser.safari) { return; }

    $('.resizable').each(function(i) {

        var width = $(this).width();
        if ($.browser.mozilla) { width = width + 8; }
        $(this).after("<div class='resizer' style='width:" + width + "px;' title='Drag me..'></div>");

        $(this).next().mousedown( function(e) {

            startpos = e.pageY;
            currentresizer = $(this).prev();
            currentheight = $(currentresizer).height();
            resizehandled = true;

            // Make the document trigger on mouseup, to stop resizing of textarea's. We bind this event only when one
            // of the resizers is clicked.
            $(document).mouseup(function() {
                resizehandled = false;
            });

            // Resize the textarea if 'resizehandled' is true. We bind this event only when one
            // of the resizers is clicked.
            $(document).mousemove(function(e) {
                if (resizehandled) {
                    curpos = e.pageY;
                    diffpos = startpos - curpos;
                    if (diffpos > -800 && diffpos < 400) {
                        $(currentresizer).height(currentheight - diffpos + 'px');
                    }
                }
            });

        });

    });

}



function openDialog(title, href, dialogwidth, dialogheight, vars) {
    
    // Make sure we have a fresh #dialog..
    $('#dialog').remove();
    $('body').prepend("<div id='dialog'><div id='dialog-inner'><img src='./pics/loadingAnimation.gif' style='display: block; margin: 50px auto;'/></div></div>");

    // Dependant on whether vars is set, use either POST (with vars) or GET (without vars).
    if (typeof(vars)=="undefined") {
        var requesttype = "GET";
        var vars = "";
    } else {
        var requesttype = "POST";
    }

    // Load the contents of the dialog.
    $.ajax({
       type: requesttype,
       url: href,
       data: vars,
       success: function(html){
           $('#dialog-inner').html(html);
           JT_init();
       }
   });

    // Open the dialog..
    $('#dialog').show().dialog({
        bgiframe:true, 
        resizable: true,
        modal: true,
        draggable: true, 
        width: parseInt(dialogwidth),
        height: parseInt(dialogheight),
        title: title
    });

}



function openDialogFrame(title, href, dialogwidth, dialogheight) {
    
    // Make sure we have a fresh #dialogframe..
    $('#dialogframe').remove();

    // Prepend the Dialog to the page..
    $('body').prepend('<iframe id="dialogframe" src="" border="0" scrolling="no" frameborder="0" style="width: ' +
        (parseInt(dialogwidth)-2) + 'px !important; display:none;" ><img src="./pics/loadingAnimation.gif" ' +
        'style="display: block; margin: 50px auto;" alt="Loading.." /></iframe>');

    // Set the correct source, to load the actual dialog contents.
    $('#dialogframe').attr('src', href);

    // Open the dialog..
    $('#dialogframe').dialog({
        bgiframe:true, 
        resizable: true,
        modal: true,
        draggable: true, 
        width: parseInt(dialogwidth),
        height: parseInt(dialogheight),
        title: title,
        open: function() {  $('#dialogframe').width( parseInt(dialogwidth) ); }
    });

}


var fileSelectionTarget = "";

/**
 * Open the File selector
 */
function openFileSelector(title, target, filetypes) {
    
    // Make sure we have a fresh #filedialog..
    $('#filedialog').remove();
    $('body').prepend("<div id='filedialog'><img src='./pics/loadingAnimation.gif' style='display: block; margin: 50px auto;'/></div>");

    fileSelectionTarget = target;

    $.ajax({
        type: "POST",
        url: "ajaxhelper.php",
        data: "function=fileSelector",
        success: function(html){
            $('#filedialog').html(html);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            
            // Sometimes (mostly in Safari, it seems) the path is incorrect.. Try
            // to get it again, by guesstimating.
            $.ajax({
                type: "POST",
                url: "/pivotx/ajaxhelper.php",
                data: "function=fileSelector",
                success: function(html){
                    $('#filedialog').html(html);
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {

                    $.ajax({
                        type: "POST",
                        url: "../ajaxhelper.php",
                        data: "function=fileSelector",
                        success: function(html){
                            $('#filedialog').html(html);
                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
                            alert('couldn\'t load the fileselector..');
                        }
                    });

                }
            });

       }
    });
    
    // Open the dialog..
    $('#filedialog').dialog({
        bgiframe:true, 
        resizable: true,
        modal: true,
        draggable: true, 
        width: 600,
        height: 320,
        title: title,
        overlay: { opacity: 0.75, background: "#789" }
    });
    
    return false;
}

/**
 * Select a file in the Fileselector dialog, and place it in the 'target'. We also
 * Call a bunch of events, to make sure that if the target has some attached
 * events they will be processed.
 */ 
function fileSelectorChoose(file) {
    console.log(file);
    $(fileSelectionTarget).val(file).trigger('click').trigger('focus').trigger('blur');    
    $('#filedialog').dialog('close');
    return false;
}


function fileSelectorChangefolder(folder) {
    $.ajax({
       type: "POST",
       url: "ajaxhelper.php",
       data: "function=fileSelector&folder="+escape(folder),
       success: function(html){
           $('#filedialog').html(html);
       }
    });
    return false;
}

/**
 * Set the 'on unload' handler, to warn the user when leaving a page with
 * unsaved changes.
 */
function setOnUnload(message) {
    $(window).bind("beforeunload", function(event){
        return message;
    }); 
}


/**
 * Set the 'on unload' handler, to warn the user when leaving a page with
 * unsaved changes.
 */
function clearOnUnload() {
    $(window).unbind("beforeunload"); 
}


/** 
 * Get the PivotX news to display on the dashboard.
 */
function getPivotxNews() {
    
    $.ajax({
        type: "POST",
        url: "./ajaxhelper.php",
        data: "function=getPivotxNews",
        success: function(html){
            html = html.split(/--split--/g);
            jQuery('#newsholder').html( html[0] );
            jQuery('#forumpostholder').html( html[1] );
        }
    });
    
} 


/**
 * Show more newsitems..
 */
function moreNews() {
    jQuery('#newsmoreclick').slideUp('slow');
    jQuery('#newsmore').slideDown('slow');
    return false;
}


/**
 * Show more events..
 */
function moreEvents() {
    jQuery('#eventsmoreclick').slideUp('slow');
    jQuery('#eventsmore').slideDown('slow');
    return false;
}


/**
 * Show more forumposts..
 */
function moreForumPosts() {
    jQuery('#forumpostsmoreclick').slideUp('slow');
    jQuery('#forumpostsmore').slideDown('slow');
    return false;
}


/**
 * Make the 'all' link in suggested tags fetch all tags..
 */
function getAllTags(amount, path) {

    amount = parseInt(amount);

    if (typeof(path)!="undefined") {       
        var url = "../../ajaxhelper.php";
    } else {
        var url = "./ajaxhelper.php";
    }

    $.ajax({
        type: "POST",
        url: url,
        
        data: "function=getAllTags&amount="+amount,
        success: function(fetchedhtml) {
            $("#suggestedtags").html(fetchedhtml);
            $('#suggestedtags a[rel=tag]').bind('click', function() {
                var tagname=$(this).html();
                $('#keywords').val($('#keywords').val() + " " + tagname);
            });
        },
        error: function() {
            alert("Error fetching tags.")
        }

    });

}


/**
 * Simple javascript confirmation for various actions. If the user clicks cancel,
 * nothing happens.. If the user clicks 'ok', the browser is redirected to the
 * link, which is padded with the 'pivotxsession' cookie.
 */
function confirmme(link, str) {

    if (confirm(str)) {

        var cookie = "&pivotxsession=" + $.cookie('pivotxsession')

        self.location = link + cookie;

        return true;
    } else {
        return false;
    }

}



/**
 * Simple javascript question for various actions. If the user clicks cancel,
 * nothing happens.. If the user clicks 'ok', the browser is redirected to the
 * link, which is padded with the 'pivotxsession' cookie and the given answer
 */
function askme(link, str) {

    var answer = prompt(str, '');

    if (answer!=null) {

        var cookie = "&pivotxsession=" + $.cookie('pivotxsession') + "&answer=" + answer

        self.location = link + cookie;
    } else {

        return false;

    }



}



/**
 * Select a row in a tabular-nav table, such as the one we use for the entries.
 */
function rowSelect(rownum) {

    if ($('#check-'+rownum)[0].checked) {
        $('#row-'+rownum).addClass('selectedrow');
    } else {
        $('#row-'+rownum).removeClass('selectedrow')
    }

}

/**
 * Select all rows in a tabular-nav table..
 */
function rowSelectAll() {

    $("input[type='checkbox']").each(function() {
        if (!this.checked) { this.click(); }
    });


}

/**
 * Select no rows in a tabular-nav table..
 */
function rowSelectNone() {

    $("input[type='checkbox']").each(function() {
        if (this.checked) { this.click(); }
    });


}


/**
 * Ask for confirmation if we're deleting multiple entries..
 */
function entriesActionSubmit() {

    if ($('#entriesaction').val() == "delete" ) {
    
        return confirm("Are you sure you wish to delete these entries?");
    
    } else { 
    
        return true;
    
    }
    
} 


/**
 * Save an edited file (from the ajaxy editor)
 */
function saveEdit() {


    var basedir = $('#editBasedir').val();
    var file =  $('#editFile').val();
    var csrfcheck =  $.cookie("pivotxsession");

    // if we're using a MarkItUp editor, get the contents from that.
    if ($('textarea[class*="markItUpEditor"]').is('*')) {
        var contents =  $('textarea[class*="markItUpEditor"]').val();
    } else {
        var contents =  $('#editContents').val();
    }

    $.ajax({
        type: "POST",
        url: "ajaxhelper.php",
        data: "function=save&csrfcheck=" + encodeURIComponent(csrfcheck) +
            "&basedir=" + encodeURIComponent(basedir) +
            "&file=" + encodeURIComponent(file) + 
            "&contents=" + encodeURIComponent(contents),
        success: function(fetchedhtml) { humanMsg.displayMsg(fetchedhtml); $('#dialog').dialog('close'); },
        error: function() { alert("Error saving file.") }
    });

}

/**
 * Save an edited file (from the ajaxy editor), and keep it open..
 */
function saveEditAndContinue() {


    var basedir = $('#editBasedir').val();
    var file =  $('#editFile').val();
    var csrfcheck =  $.cookie("pivotxsession");

    // if we're using a MarkItUp editor, get the contents from that.
    if ($('textarea[class*="markItUpEditor"]').is('*')) {
        var contents =  $('textarea[class*="markItUpEditor"]').val();
    } else {
        var contents =  $('#editContents').val();
    }

    $.ajax({
        type: "POST",
        url: "ajaxhelper.php",
        data: "function=save&csrfcheck=" + encodeURIComponent(csrfcheck) +
            "&basedir=" + encodeURIComponent(basedir) +
            "&file=" + encodeURIComponent(file) + 
            "&contents=" + encodeURIComponent(contents),
        success: function(fetchedhtml) { humanMsg.displayMsg(fetchedhtml); },
        error: function() { alert("Error saving file.") }
    });

}


/**
 * Close a file (from the ajaxy editor)
 */
function closeEdit() {

    $('#dialog').dialog('close');
    
}


/**
 * Show the message box with loading gif to the user..
 */
function setMessageLoading() {

    humanMsg.displayMsg("<img src='pics/loadingAnimation.gif' alt='Loading...' width='208' height='13'>", true, true);

}


/**
 * Fills the 'internal name' field with the URI for the page/entry..
 */
function setSafename(from, to, text) {

    // Get the string..
    var str = $('#'+from).val();

    // Declare the characters that need replacement.. Using hexadecimal encoding to prevent breakage.
    var accent = unescape("%C0%C1%C2%C3%C4%C5%C7%C8%C9%CA%CB%CC%CD%CE%CF%D0%D1%D2%D3%D4%D5%D6%D7%D8%D9%DA%DB%DC%DD%E0%E1%E2%E3%E4%E5%E7%E8%E9%EA%EB%EC%ED%EE%EF%F1%F2%F3%F4%F5%F6%F8%F9%FA%FB%FC%FD%FF%u0100%u0101%u0102%u0103%u0104%u0105%u0106%u0107%u0108%u0109%u010A%u010B%u010C%u010D%u010E%u010F%u0110%u0111%u0112%u0113%u0114%u0115%u0116%u0117%u0118%u0119%u011A%u011B%u011C%u011D%u011E%u011F%u0120%u0121%u0122%u0123%u0124%u0125%u0126%u0127%u0128%u0129%u012A%u012B%u012C%u012D%u012E%u012F%u0130%u0131%u0134%u0135%u0136%u0137%u0138%u0139%u013A%u013B%u013C%u013D%u013E%u013F%u0140%u0141%u0142%u0143%u0144%u0145%u0146%u0147%u0148%u0149%u014A%u014B%u014C%u014D%u014E%u014F%u0150%u0151%u0154%u0155%u0156%u0157%u0158%u0159%u015A%u015B%u015C%u015D%u015E%u015F%u0160%u0161%u0162%u0163%u0164%u0165%u0166%u0167%u0168%u0169%u016A%u016B%u016C%u016D%u016E%u016F%u0170%u0171%u0172%u0173%u0174%u0175%u0176%u0177%u0178%u0179%u017A%u017B%u017C%u017D%u017E");
    var sansAccent = "AAAAAACEEEEIIIIDNOOOOOxOUUUUYaaaaaaceeeeiiiinoooooouuuuyyAaAaAaCcCcCcCcDdDdEeEeEeEeEeGgGgGgGgHhHhIiIiIiIiIiJjKkkLlLlLlLlLlNnNnNnnNnOoOoOoRrRrRrSsSsSsSsTtTtTtUuUuUuUuUuUuWwYyYZzZzZz";
   
    // Loop through the string.. Not the most efficient way, but this is done client-side and not on long strings.
    for(var i=0; i<accent.length; i++) {
        var r = new RegExp(accent.charAt(i), 'g');
        str = str.replace(r, sansAccent.charAt(i));
    }

    // Some characters that need to be replaced by two letters..
    str = str.replace(unescape("%C6"), 'AE');
    str = str.replace(unescape("%E6"), 'ae');
    str = str.replace(unescape("%FE"), 'th');
    str = str.replace(unescape("%DE"), 'Th');
    str = str.replace(unescape("%DF"), 'ss');


    // If you're wondering why we replace uppercase accented letters, and then do a .toLowerCase:
    // This is because IE has problems lowercasing accented characters, so they would be stripped
    // out otherwise instead of replaced by their US-ASCII counterpart.    
    str = str.toLowerCase();
        
    str = str.replace(/^\s*/, '').replace(/\s*$/, ''); 
    str = str.replace(/[ _]/g, "-");
    str = str.replace(/[^a-z0-9-]/g, "");
    str = str.replace(/-+/g, "-");
    
    $('#'+to).val( str );

    if (typeof(text)!="undefined") {
        $('#'+text).html(str);
    }

}



/**
 * Open the image editor window
 */
function imageEdit(imagename) {

    window.open("./modules/module_image.php?image="+ encodeURIComponent(imagename), 'thumbnail',
        "toolbar=no,resizable=yes,scrollbars=yes,width=920,height=570");


}


/**
 * Open a window with the comments or trackbacks for an entry.
 */
function openEntryExtra(type,uid){

    var oldaction = $('#form1').attr('action');

    // Open the preview.
    $('#form1').attr('target', '_blank');
    $('#form1').attr('action' , location.pathname + '?page=' + type + '&uid=' + uid);
    $('#form1').submit();

    // Reset the form action..
    $('#form1').attr('target', '_self');
    $('#form1').attr('action', oldaction);

}

/**
 * Open the preview for an Entry.
 */
function openEntryPreview() {

    var oldaction = $('#form1').attr('action');

    // Open the preview.
    $('#form1').attr('target', '_blank');
    $('#form1').attr('action' , 'render.php?previewentry=true');
    $('#form1').submit();

    // Reset the form action..
    $('#form1').attr('target', '_self');
    $('#form1').attr('action', oldaction);

}



/**
 * Open the preview for an Page.
 */
function openPagePreview() {

    var oldaction = $('#form1').attr('action');

    // Open the preview.
    $('#form1').attr('target', '_blank');
    $('#form1').attr('action' , 'render.php?previewpage=true');
    $('#form1').submit();

    // Reset the form action..
    $('#form1').attr('target', '_self');
    $('#form1').attr('action', oldaction);

}

/**
 * Save an entry and continue editing..
 */
function saveEntryAndContinue() {

    // Submit to the hidden frame.
    $('#form1').attr('target', 'posthere');
    $('#postedfrom').val('continue');
    $('#form1').submit();

    // Reset the form action..
    $('#postedfrom').val('');    
    $('#form1').attr('target', '_self');
    
    humanMsg.displayMsg("The Entry has been saved.");

    clearOnUnload();
    
}


/**
 * Save a page and continue editing..
 */
function savePageAndContinue() {

    // Submit to the hidden frame.
    $('#form1').attr('target', 'posthere');
    $('#postedfrom').val('continue');    
    $('#form1').submit();

    // Reset the form action..
    $('#postedfrom').val('');    
    $('#form1').attr('target', '_self');
    
    humanMsg.displayMsg("The Page has been saved.");
    
    clearOnUnload();

}


/**
 * Delete an entry from the 'edit entry' screen.
 */
function deleteEntry(msg) {
    
    if (confirm(msg)) {     
        self.location = "index.php?page=entries&del=" + $('#uid').val() + "&pivotxsession=" + $.cookie('pivotxsession');
    }
    
}


/**
 * Delete a page from the 'edit page' screen.
 */
function deletePage(msg) {
    
    if (confirm(msg)) {     
        self.location = "index.php?page=pagesoverview&delpage=" + $('#uid').val() + "&pivotxsession=" + $.cookie('pivotxsession');
    }
    
}


/**
 * Open the thickbox window to upload more than three files at once.
 */
function openUploadMore() {

     self.parent.tb_remove();
     setTimeout("$('#uploadmore').click();", 200);
     return false;

}


/**
 * General rebuild routine.
 */
function ajaxRebuildCall(func, start, time) {
    $.ajax({
        type: "POST",
        url: "ajaxhelper.php",
        data: "function="+func+"&start="+start+"&time="+time,
        dataType: "json",
        success: rebuildHelper,
        error: function() { humanMsg.displayMsg("Error rebuilding index.") }
    });
}


/**
 * Helper rebuild routine.
 */
function rebuildHelper(data) {
    if (data.done) {
        humanMsg.displayMsg(data.text);
    } else {
        humanMsg.displayMsg(data.text);
        ajaxRebuildCall(data.func,data.start,data.time);
    }
}

/**
 * Check all buttons in the comments moderation window..
 */
function commentsCheckAll() {
    $("tr:visible input[name^='checked']").attr('checked', true);
    return false;
}


/**
 * Uncheck all buttons in the comments moderation window..
 */
function commentsCheckNone() {
    $("input[name^='checked']").attr('checked', false);
    return false;
}


/**
 * Open the window to insert (and/or upload) an image in an entry or page.
 */
function openImageWindow(target) {

    var f_text = encodeURIComponent(getSel(target));
    
    if ($('#f_image').is('*')) {
        var f_image = $('#f_image').val();
    } else {
        var f_image = "";
    }

    var my_url = 'includes/editor/insert_image.php?f_image='+f_image+'&f_text='+ f_text +'&f_target='+target;
    
    openDialogFrame("Insert an Image", my_url, 680, 390);

}

/**
 * Open the window to insert (and/or upload) an image as popup in an entry or page.
 */
function openImagePopupWindow(target) {

    var f_text = encodeURIComponent(getSel(target));

    if ($('#f_image').is('*')) {
        var f_image= $('#f_image').val();
        var f_hasthumb= $('#f_hasthumb').val();
    } else {
        var f_image = "";
        var f_hastumb = "";
    }

    var my_url = 'includes/editor/insert_popup.php?f_image='+f_image+'&f_text='+ f_text +'&f_hasthumb='+f_hasthumb+'&f_target='+target;
    
    openDialogFrame("Insert an Image Popup", my_url, 680, 470);

}

/**
 * Open the window to insert a download in an entry or page.
 */
function openDownloadWindow(target) {

    var f_text = encodeURIComponent(getSel(target));

    if ($('#f_image').is('*')) {
        var f_image= $('#f_image').val();
        var f_hasthumb= $('#f_hasthumb').val();
    } else {
        var f_image = "";
        var f_hastumb = "";
    }

    var my_url = 'includes/editor/insert_download.php?f_image='+f_image+'&f_text='+ f_text +'&f_hasthumb='+f_hasthumb+'&f_target='+target;
    
    openDialogFrame("Insert a Download", my_url, 480, 440);

}


/**
 * Open the window to insert a tag in an entry or page.
 */
function openTagWindow(target) {

    var f_text = encodeURIComponent(getSel(target));

    var my_url = 'includes/editor/insert_tag.php?text='+ f_text +'&f_target='+target;
    
    openDialogFrame("Insert a Tag", my_url, 480, 350);

}


var uploadWindowTarget = "";


/**
 * Open the window to insert (and/or upload) an image.
 */
function openUploadWindow(title, target, filter) {

    var my_url = 'includes/editor/insert_upload.php?f_image=' + $(target).val() +"&f_target=" + $(target).attr('id');
    
    openDialogFrame(title, my_url, 660, 340);

}


/**
 * Open the window to insert (and/or upload) a file.
 */
function openFileUploadWindow(title, target, filter) {

    var my_url = 'includes/editor/insert_upload.php?f_image=' + $(target).val() +"&f_target=" + $(target).attr('id')+"&f_type=file";

    openDialogFrame(title, my_url, 660, 340);

}


/**
 * Get the value of a radiobutton. Return the val() of the one
 * that is 'checked'..
 */
function getValue(name) {
    var value = $("input[name='"+name+"']:checked").val();
    return value;
}

/**
 * Trims a text: remove leading and trailing spaces..
 */
function trim(value) {
    value = String(value);
    value = value.replace(/^\s+/,'');
    value = value.replace(/\s+$/,'');
    return value;
}

// We use a counter to keep track of the number of times we've resized the excerpts.
var resizecounter = 0;

/**
 * Do various things when the browser window is resized..
 */
function pivotxResizeWindow(resetsize) {

    // don't do this when we're displaying the bookmarklet.
    if ($('#bookmarklet').is('*')) {
        return "";
    }

    // If the screen is less than 1020px wide, lose the borders.
    if ($('body').width()<1020) {
        $('body').css('margin-left', '0');
        $('body').css('margin-right', '0');
        $('#footer, #mainmenu').css('left', '0');
        $('#footer').css('right', '0');
    }
    if ($('td.dashboardclip1').is('*')) {
        // Set the width of the 'clipped' excerpts on the Dashboard.
        
        if (resetsize) {
            // We need to reset the size, after a browser window resize..
            resizecounter = 0;
            $('td.dashboardclip1 .clip').css('width', "250px");
            $('td.dashboardclip2 .clip').css('width', "210px");
            $('td.dashboardclip3 .clip').css('width', "290px");
        }
        
        var width = $('td.dashboardclip1').width();
        
        // If the difference between the width of the excerpt and the width of
        // the body is less than 550, we re-set the timeout, because the page
        // has not been properly rendered yet.
        if ( (width + 550) > $('body').width() ) {
            setTimeout( function(){ pivotxResizeWindow(false); }, 100 ); 
        } else {
            $('td.dashboardclip1 .clip').css('width', width+"px");
            width = $('td.dashboardclip2').width();
            $('td.dashboardclip2 .clip').css('width', width+"px");
            width = $('td.dashboardclip3').width();
            $('td.dashboardclip3 .clip').css('width', width+"px");
            
            // run again..
            resizecounter++;
            if (resizecounter<5) { pivotxResizeWindow(false); }
        }
        
    } else if ($('td.entriesclip').is('*')) {
        // Set the width of the 'clipped' excerpts on the Entries/Pages page.
                
        if (resetsize) {
            // We need to reset the size, after a browser window resize..
            resizecounter = 0;
            $('td.entriesclip .clip').css('width', "300px");
            //alert('reset');
        }
        
        // For Opera we need a few pixels correction when fixing the widths.
        if ($.browser.opera) {
            var delta = 20;        
        } else {
            var delta = 0;
        }
        
        var width = $('td.entriesclip').width() + delta;
        
        // If the difference between the width of the excerpt and the width of
        // the body is less than 550, we re-set the timeout, because the page
        // has not been properly rendered yet.
        if ( (width + 300) > $('body').width() ) {
            setTimeout( function(){ pivotxResizeWindow(); }, 100 );
        } else {
            //alert('width: ' + width);
            $('td.entriesclip .clip').css('width', width+"px");
            // run again..
            resizecounter++;
            if (resizecounter<6) { pivotxResizeWindow(); }
        }
    }

}

/**
 * Check if cookies are enabled or disabled.
 *
 * @return boolean
 */
function cookieEnabled() {
    
    var cookieEnabled = (navigator.cookieEnabled) ? true : false;
    
    if (typeof navigator.cookieEnabled == "undefined" && !cookieEnabled) { 
        document.cookie="testcookie";
        cookieEnabled = (document.cookie.indexOf("testcookie") != -1) ? true : false;
    }
    
    return (cookieEnabled);
}

/**
 */
function highlightFirstComment() {
    var code = '';
    jQuery('table.moderate-comments tr:visible').each(function(){
        if (code == '') {
            var c = jQuery(this).attr('class');
            if (c) {
                var r = c.match(/comment-([0-9]+)/);
                if (r && (r.length == 2)) {
                    code = '.' + r[0];
                }
            }
        }
    });

    if (code != '') {
        jQuery(code).addClass('highlight');
        jQuery(code + ' .long-comment').show();
        jQuery(code + ' .short-comment').hide();
    }
}

