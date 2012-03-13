[[ include file="inc_window_header.tpl" ]]

[[ literal ]]
<script type="text/javascript">
//<![CDATA[

//We need to submit this to the opener, that is to the editor
function do_submit_f_image(){

    var f_image = trim( $('#f_image').val() );
    var f_text = $('#f_text').val();
    var f_title = $('#f_title').val();
    var f_icon = '';
    var f_target = $('#f_target').val();

    var selected_popup = getValue('f_popup');
    if(selected_popup != 'text') {
        f_icon = 'icon';
        f_text = '';
    }

    if ($.browser.safari && $.browser.version<500) {
        // safari 2 can't use the wysiwyg to dynamically insert stuff in the entry...
        var code = '[[download file="'+f_image+'" icon='+f_icon+' text="'+f_text+'" title="'+f_title+'" ]]';
        var msg = "Dynamically inserting code does not work properly on Safari 2. Copy the code below, and insert it in your entry manually.";

        prompt(msg, code);
        window.close();

    } else {

        // If window.opener is unknown, we've opened the download inserter as a Dialog. then 
        /// we can use top.frames[0]
        if (window.opener==null) {
            top.doDownload(f_image, f_icon, f_text, f_title, f_target);
        } else {            
            window.opener.doDownload(f_image, f_icon, f_text, f_title, f_target);
            window.close();
        }
        
    }

}

/**
 * Javascript for autocomplete on filename..
 */
jQuery(function($) {


    function formatResult(row) {
        return row[0];
    }

    $("#f_image").autocomplete("../../ajaxhelper.php?function=autoComplete", {
        delay: 150,
        width: 460,
        formatResult: formatResult,
        selectFirst: false,
        matchSubset: false,
        minChars: 2,        
        max: 50
    });

    $("#f_image").result(function(event, data, formatted) {
        updatePreview(data[0]);
    });

    $("#f_image").blur(function(event, data, formatted) {
        updatePreview();
    });


});

/**
 * End of Javascript for autocomplete on filename..
 */


// Submitting and cancelling..
jQuery(function($) {

    // Attach event for 'insert image' button.
    $("#button_submit").click(function(event, data, formatted) {
        do_submit_f_image();
    });

    // Attach event for 'cancel' button.
    $("#button_cancel").click(function(event, data, formatted) {
        if (window.opener==null) {
            top.$('#dialogframe').dialog('close');
        } else {
            self.close();
        }
    });

    // Make sure the window is on top..
    self.focus();

});

// Small function to update the preview of the image..
function updatePreview(imagename) {

    return;

    // ! does nothing
    var imagename = trim( $('#f_image').val() );

    if ( (imagename.length < 4) || !imagename.match(/\.(gif|png|jpg|jpeg)/i)) {
        $('#imagepreview').html("");
    } else {
        $('#imagepreview').html("<img src='../timthumb.php?src=" + escape(imagename) + "&w=171&h=128' width='171' height='128' alt='preview' />");
    }
}


//]]>
</script>
[[/literal]]
</head>
<body style="margin: 0 12px 0 0; background-image: none; background-color: #FFF;">

[[ if $msg != "" ]]
    <p style="background-color:#FFF6BF; border-bottom:1px solid #FFD324; border-top:1px solid #FFD324; margin-bottom:6px;   min-height:16px; padding:6px;">[[ $msg ]]</p>
[[ /if ]]

<table border="0" cellspacing="0" cellpadding="2" class="formclass" style="border: 0px;">

    <tr>
        <td colspan="2">
            <b>[[t]]Insert a Download[[/t]]:</b> <br />
            <br />
            [[t]]To make a file download, you should upload a file, or select a previously uploaded file. Then select whether you want an icon or text link that triggers the download.[[/t]]
        </td>

    </tr>

    <tr>
        <td class="nowrap">
            <b>[[t]]Upload[[/t]]:</b>
        </td>
        <td colspan="2"><div id="upload-container">


        <form style="clear:both;">
        
            <p style="margin: 2px 0px;" class="buttons">
    

                <span id="spanButtonPlaceHolder">
                    <a href="#"i id="upload-button">
                        <img src="../../pics/page_lightning.png" alt="" />[[t]]Upload a file[[/t]]
                        <span style="font-size: 7pt;">([[t]]2 MB Max[[/t]])</span>
                    </a>                    
                    
                    
                    
                </span>
                
        
            </p>

        </form>
		<div id="divFileProgressContainer" style="width:330px; clear:both;"></div>

        [[upload_create_button browse_button='upload-button' container='upload-container' progress_selector='#divFileProgressContainer' input_selector='#f_image' filters='any' upload_type='file']]

        <a href="#" id="btnCancel"  onclick="swfu.cancelQueue();"></a> 

        </div></td>
    </tr>


    <tr>

        <td class="nowrap">
           <b>[[t]]File Name[[/t]]:</b>
        </td>
        <td>
                <input type='text' name='f_image' id='f_image' size='25' value='[[ $imagename ]]' class='input' style='width: 230px;' />
		</td>
		<td class="buttons_small">
			 <a href="#" onclick="top.openFileSelector('[[t escape=js]]Select an image[[/t]]', $('#f_image'), 'gif,jpg,png');">
						<img src='../../pics/page.png' /> [[t]]Select[[/t]]
					</a>
			
        </td>
    </tr>


    <tr>
        <td colspan="3">
            <hr size="1" noshade><form name="pick_f_image" action="" method="post">
        </td>
    </tr>



    <tr>
        <td valign="top">
           <b>[[t]]Link Look[[/t]]:</b>
        </td>
        <td colspan="2">
            <input name="f_popup" id="f_popup1" type="radio" value="icon" [[$thumb ]]><label for="f_popup1">
            [[t]]Use Icon[[/t]]</label>

            </label><br />
            <input name="f_popup" id="f_popup2"  type="radio" value="text" [[ $notthumb ]]><label for="f_popup2">
            [[t]]Use Text[[/t]]: </label>&nbsp;<input type="text" id="f_text" name="f_text" class="input" value="[[ $text ]]"><br />
        </td>
    </tr>

    <tr>
        <td class="nowrap">
            <b>[[t]]Link Title[[/t]]:</b>
        </td>
        <td colspan="2">
            <input type='text' name='f_title' id='f_title' size='25' value='' class='input' />
       </td>
    </tr>

    <tr>
        <td colspan="3">

            <input type='hidden' name='f_target' id='f_target' value='[[ $target ]]' />

            <p style="margin: 8px 0px;" class="buttons">

            <a href="#" class="positive" id='button_submit'>
            <img src="../../pics/tick.png" alt="" />[[t]]Insert download![[/t]]</a>

            <a href="#" class="negative" id='button_cancel'>
            <img src="../../pics/delete.png" alt="" />[[t]]Cancel[[/t]]</a>
            </p>

        </td>
    </tr>

</table>
</form>




</body>
</html>
