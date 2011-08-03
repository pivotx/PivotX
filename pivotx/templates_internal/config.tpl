[[include file="inc_header.tpl" ]]

    <div id="tabbedoptions">
        <ul>
            [[ foreach from=$form_titles key=key item=item ]]       
                <li><a href="#section-[[$key]]" tabindex="[[ 100 + $key ]]"><span>[[ $item ]]</span></a></li>
            [[ /foreach ]]
        </ul>
        
        [[ foreach from=$form_html key=key item=item ]]     
            <div id="section-[[$key]]">
                <h3>[[ $form_titles.$key ]]</h3>
                [[ $item ]]
            </div>
        [[ /foreach]]
    </div>


<script type='text/javascript'>
//<![CDATA[

// We make an array, that contains the current values of the forms.
var currentvalues = new Array();

jQuery(function($) {

    // Initialize the tabs
    $('#tabbedoptions').tabs();

    // Bind the updateConfig() function to all fields..
    $('input, select, textarea').not('.noautoupdate').bind('blur', function() { updateConfig(this); });
    $('input[type=checkbox], select').not('.noautoupdate').bind('change', function() { updateConfig(this); });

    // Fill the currentvalues array with the current values of the fields.
    $('input, select, textarea').each(function(i) {
        currentvalues[ this.id ] = encodeURIComponent($(this).fieldValue());
    });

});


/**
 * After editing a field, send it to ajaxhelper.
 */
function updateConfig(field) {

    // Make sure form validation is run..
    $(field).valid();

    var key = $(field).attr('name');
    var value = $(field).fieldValue();
    var csrfcheck =  $.cookie("pivotxsession");
    var error = $(field).hasClass('error');

    // Only send the ajaxy request if the value has changed.
    if ( !error && (String(value) != String(currentvalues[key])) ) {

        setMessageLoading();
        
        $.ajax({
            type: "POST",
            url: "ajaxhelper.php",
            data: "function=setConfig&id=" + escape(key) + "&value=" + encodeURIComponent(value)
                + "&csrfcheck=" + escape(csrfcheck),
            success: function(fetchedhtml) {
                currentvalues[ key ] = value;
                var msg = '[[t escape=js]]The configuration for "%key%" was successfully updated.[[/t]]';
                humanMsg.displayMsg(msg.replace("%key%", key));
            },
            error: function() {
                humanMsg.displayMsg('[[t escape=js]]The configuration could not be updated.[[/t]]');
            }
        });


    }

    if (key == 'offline_online') {
        if (value == '1') {
            $('body').removeClass('website-offline');
        }
        else {
            $('body').addClass('website-offline');
        }
    }
}

//]]>
</script>


[[include file="inc_footer.tpl" ]]
