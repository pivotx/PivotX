[[ include file="inc_window_header.tpl" ]]

[[ literal ]]
<script type="text/javascript">
//<![CDATA[

//We need to submit this to the opener, that is to the editor
function do_submit_f_image(){

    var f_image = trim( $('#f_image').val() );
    var f_target = $('#f_target').val();

    top.$('#'+f_target).val(f_image);
    top.$('#dialogframe').dialog('close');

}


/**
 * Javascript for autocomplete on filename..
 */
jQuery(function($) {

    function formatItem(row) {
        var result = "<img src='../timthumb.php?src=" + row[0] + "&w=171&h=128' width='43' height='32' style='margin: 0 6px -6px 0' />";
        result += row[2] + "  <em>" + row[1] + "</em> ";
        return result;
    }

    function formatResult(row) {
        return row[0];
    }

    $("#f_image").autocomplete("../../ajaxhelper.php?function=autoComplete", {
        delay: 150,
        width: 460,
        formatItem: formatItem,
        formatResult: formatResult,
        selectFirst: false,
        matchSubset: false,
        minChars: 2,
        max: 50
    });

    $("#f_image").result(function(event, data, formatted) {
        //updatePreview(data[0]);
    });

    $("#f_image").blur(function(event, data, formatted) {
        //updatePreview();
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

    // Select the 'alt' form field by default..
    $('#f_image_alt').select();

    // Make sure the window is on top..
    self.focus();

});

// Small function to update the preview of the image..
/*function updatePreview(imagename) {

    var imagename = trim( $('#f_image').val() );

    if ( (imagename.length < 4) || ( (!imagename.match('.gif')) && (!imagename.match('.jpg')) && (!imagename.match('.jpeg')) && (!imagename.match('.png'))) ) {
        $('#imagepreview').html("");
    } else {
        $('#imagepreview').html("<img src='../timthumb.php?src=" + escape(imagename) + "&w=171&h=128' width='171' height='128' alt='preview' />");
    }
}*/


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
        <td colspan="3">
            <b>[[t]]Insert a file[[/t]]:</b> <br />
            <br />
            [[t]]To insert a file, you should upload one, or select a previously uploaded file.[[/t]]
        </td>

        <td rowspan="3">
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
                    <a href="#" id="upload-button">
                        <img src="../../pics/page_lightning.png" alt="" />[[t]]Upload a file[[/t]] <span style="font-size: 7pt;">(2 MB Max)</span>
                    </a>                    
                </span>
                
        
            </p>

        </form>
		<div id="divFileProgressContainer" style="width:330px; clear:both;"></div>


        [[upload_create_button browse_button='upload-button' container='upload-container' progress_selector='#divFileProgressContainer' input_selector='#f_image' filters='file' upload_type='file']]

        <a href="#" id="btnCancel"  onclick="swfu.cancelQueue();"></a> 

        </div></td>
    </tr>



    <tr>

        <td class="nowrap">
           <b>[[t]]File name[[/t]]:</b>
        </td>
        <td>
                <input type='text' name='f_image' id='f_image' size='25' value='[[ $imagename ]]' class='input' style='width: 230px;' />
		</td>
		<td class="buttons_small">
			 <a href="#" onclick="top.openFileSelector('[[t]]Select a file[[/t]]', $('#f_image'), 'gif,jpg,png,doc,ppt,xls,pdf,docx,xlsx,pptx,avi,flv,mp3');">
						<img src='../../pics/page.png' /> [[t]]Select[[/t]]
					</a>
			
        </td>
    </tr>


    <tr>
        <td colspan="3">

            <input type='hidden' name='f_target' id='f_target' value='[[ $target ]]' />

            <p style="margin: 8px 0px;" class="buttons">

            <a href="#" class="positive" id='button_submit'>
            <img src="../../pics/tick.png" alt="" />[[t]]Insert file![[/t]]</a>

            <a href="#" class="negative" id='button_cancel'>
            <img src="../../pics/delete.png" alt="" />[[t]]Cancel[[/t]]</a>
            </p>

        </td>
    </tr>

</table>
</form>




</body>
</html>

