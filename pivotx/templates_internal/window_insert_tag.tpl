[[ include file="inc_window_header.tpl" ]]

[[ literal ]]
<script type="text/javascript">
//<![CDATA[

//We need to submit this to the opener, that is to the editor
function do_submit_f_tag(tag){

    var f_tag = trim( $('#f_tag').val() );
    if (tag) {
         f_tag = trim( tag );
    } else {
         f_tag = trim( $('#f_tag').val() );
    }
    var f_link = trim( $('#f_link').val() );
    var f_target = $('#f_target').val();

    if ($.browser.safari && $.browser.version<500) {
        // safari 2 can't use the wysiwyg to dynamically insert stuff in the entry...
        var code = '[[tt tag="' + f_tag + '" link="' + f_link + '" ]]';
        var msg = "Dynamically inserting code does not work properly on Safari 2. Copy the code below, and insert it in your entry manually.";

        prompt(msg, code);
        window.close();

    } else {

        // If window.opener is unknown, we've opened the tag inserter as a Dialog. then 
        /// we can use top.frames[0]
        if (window.opener==null) {
            top.doTag(f_tag, f_link, f_target);
        } else {
            window.opener.doTag(f_tag, f_link, f_target);
            window.close();
        }
        
    }

}



// Submitting and cancelling..
jQuery(function($) {

    // Make 'suggested tags' clickable in 'add tag' dialogs.
    $("a[rel=dialogtag]").click( function(tag){
        do_submit_f_tag($(this).html());
    });

    // Attach event for 'insert tag' button.
    $("#button_submit").click(function(event, data, formatted) {
        do_submit_f_tag();
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


//]]>
</script>
[[/literal]]
</head>
<body style="margin: 0 12px 0 0; background-image: none; background-color: #FFF;">

[[ if $msg != "" ]]
    <p style="background-color:#FFF6BF; border-bottom:1px solid #FFD324; border-top:1px solid #FFD324; margin-bottom:6px;   min-height:16px; padding:6px;">[[ $msg ]]</p>
[[ /if ]]

<form name="pick_f_tag" action="" method="post">
<table border="0" cellspacing="0" cellpadding="2" class="formclass" style="border: 0px;">

    <tr>
        <td colspan="2">
            <b>[[t]]Insert a Tag[[/t]]:</b> <br />
            <br />
            [[t]]Insert a tag in your entry with an optional link. You can also select a tag from the (partial) Tag Cloud below.[[/t]]
        </td>
    </tr>

    <tr>

        <td class="nowrap">
           <b>[[t]]Tag[[/t]]:</b>
        </td>
        <td>
                <input type='text' name='f_tag' id='f_tag' size='25' value='' class='input' />
        </td>
    </tr>


    <tr>
        <td class="nowrap">
            <b>[[t]]URL[[/t]]:</b>
        </td>
        <td>
            <input type='text' name='f_link' id='f_link' size='25' value='' class='input' />
       </td>
    </tr>

    <tr>
        <td colspan="2">

            <input type='hidden' name='f_target' id='f_target' value='[[ $target ]]' />

            <p style="margin: 8px 0px;" class="buttons">

            <a href="#" class="positive" id='button_submit'>
            <img src="../../pics/tick.png" alt="" />[[t]]Insert tag![[/t]]</a>

            <a href="#" class="negative" id='button_cancel'>
            <img src="../../pics/delete.png" alt="" />[[t]]Cancel[[/t]]</a>
            </p>

        </td>
    </tr>

    <tr>
        <td colspan="2">
            <p><strong>[[t]]Suggested Tags[[/t]]:</strong></p>
            <p><span id='suggestedtags'> 
            [[ $suggestedtags ]]
            </span></p>
        </td>
    </tr>
    
</table>
</form>

</body>
</html>
