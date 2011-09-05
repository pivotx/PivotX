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

<p class="buttons" style="clear: both;">
    <button onclick="saveConfig();" type="button" class="disabled" id="saveButton">
        <img src="./pics/tick.png" alt=""/>
        [[t]]Save changes[[/t]]
    </button>
</p>


<script type='text/javascript'>
//<![CDATA[

// We make an array, that contains the current values of the forms.
var currentvalues = new Array();

// Store the values that need to be updated. 
var updatequeue = new Array();

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
 * After editing a field, add it to the queue to be updated.
 */
function updateConfig(field) {

    // Make sure form validation is run..
    $(field).valid();

    var key = $(field).attr('name');
    var value = $(field).fieldValue();
    var error = $(field).hasClass('error');

    // Only save it later, if the value has changed.
    if ( !error && (encodeURIComponent(String(value)) != String(currentvalues[key])) ) {
        updatequeue[ key ] = encodeURIComponent(value);
        $('#saveButton').removeClass('disabled');
        setOnUnload("[[t escape=js]]You have unsaved changes. Do you wish to continue?[[/t]]");
    }

}

/**
 * Save the updated values in the configuration..
 */ 
function saveConfig() {
    
    var values = "";
    
    // Iterate through the values, building querystring..   
    for ( key in updatequeue ) {
        //console.log(key + ' = ' + updatequeue[key] );
        values += key + "=" + updatequeue[key] + "&";
        currentvalues[ key ] = updatequeue[key];

        if (key == 'offline_online') {
            if (updatequeue[key] == '1') {
                $('body').removeClass('website-offline');
            }
            else {
                $('body').addClass('website-offline');
            }
        }        
        
    }

    // Only save, if there's actually anything to save. 
    if (values != "") {
    
        setMessageLoading();
            
        var csrfcheck =  $.cookie("pivotxsession");
        
        $.ajax({
            type: "POST",
            url: "ajaxhelper.php",
            data: "function=setConfigBatch&" + values + "csrfcheck=" + escape(csrfcheck),
            success: function(fetchedhtml) {
                humanMsg.displayMsg('[[t escape=js]]The configuration was successfully updated.[[/t]]');
                updatequeue = new Array();
                clearOnUnload();
            },
            error: function() {
                humanMsg.displayMsg('[[t escape=js]]The configuration could not be updated.[[/t]]');
            }
        });

    }
    
    $('#saveButton').addClass('disabled');
    $('#saveButton').blur();
}

//]]>
</script>


[[include file="inc_footer.tpl" ]]
