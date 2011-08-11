[[include file="inc_header.tpl" ]]

    <div id="tabbedoptions">
        <ul>
            <li><a href="#section-1" tabindex="101"><span>[[t]]General[[/t]]</span></a></li>
            <li><a href="#section-2" tabindex="103"><span>[[t]]Templates[[/t]]</span></a></li>
            <li><a href="#section-3" tabindex="104"><span>[[t]]Subweblogs[[/t]]</span></a></li>
            <li><a href="#section-4" tabindex="105"><span>[[t]]XML feeds[[/t]]</span></a></li>
            <li><a href="#section-5" tabindex="106"><span>[[t]]Commenting[[/t]]</span></a></li>
        </ul>

    
        <div id="section-1" class="fragment">
            [[ $form1 ]]
        </div>
    
        <div id="section-2" class="fragment">
            [[ $form2 ]]
        </div>
    
        <div id="section-3" class="fragment">
            <div id="form3">
                [[ $form3 ]]
            </div>
        </div>
    
        <div id="section-4" class="fragment">
            [[ $form4 ]]
        </div>
    
        <div id="section-5" class="fragment">
            [[ $form5 ]]
        </div>
        
    </div>    

<p class="buttons" style="clear: both;">
    <button onclick="saveConfig();" type="button" class="disabled" id="saveButton">
        <img src="./pics/tick.png" alt=""/>
        [[t]]Save changes[[/t]]
    </button>   
    <a href="index.php?page=weblogs">
        <img src="pics/world.png" alt=""/>
        [[t]]Back to Weblogs[[/t]]
    </a>
</p>


<script type="text/javascript">
//<![CDATA[

// We make an array, that contains the current values of the forms.
var currentvalues = new Array();

// Store the values that need to be updated. 
var updatequeue = new Array();

jQuery(function($) {

    // Initialize the tabs
    $('#tabbedoptions').tabs();

    // Bind the updateWeblog() function to all fields. 
    $('input, select, textarea').bind('blur', function() { updateWeblog(this); });
    $('input[type=checkbox], select').bind('click', function() { updateWeblog(this); });

    // Fill the currentvalues array with the current values of the fields.
    $('input, select, textarea').each(function(i) {
        currentvalues[ this.id ] = encodeURIComponent($(this).fieldValue());
    });


});



/**
 * After editing a field, add it to the queue to be updated.
 */
function updateWeblog(field) {

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

    if (key=="front_template") { loadSubWeblogs(); }

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
    }

    // Only save, if there's actually anything to save. 
    if (values != "") {
    
        setMessageLoading();
            
        var csrfcheck =  $.cookie("pivotxsession");
        var weblog = $('#internalname').val();
        
        $.ajax({
            type: "POST",
            url: "ajaxhelper.php",
            data: "function=updateWeblogBatch&" + values + "weblog=" + weblog + "&csrfcheck=" + escape(csrfcheck),
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


/**
 * Dynamically load the form for the subweblog settings. This is needed, because
 * we need to update this form on the fly, since the contents are dependant
 * on the value for the frontpage template.
 */
function loadSubWeblogs() {

    var weblog = $('#internalname').val();

    $.ajax({
        type: "POST",
        url: "ajaxhelper.php",
        data: "function=loadSubWeblogs&weblog=" + weblog,
        success: function(fetchedhtml) {
                $('#form3').html(fetchedhtml);
                JT_init();
                $('input, select, textarea').bind('blur', function() { updateWeblog(this); });
            },
        error: function() { }
    });


}

//]]>
</script>



[[include file="inc_footer.tpl" ]]
