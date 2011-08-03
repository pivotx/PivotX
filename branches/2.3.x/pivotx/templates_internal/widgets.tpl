[[include file="inc_header.tpl" ]]


<p style="margin-top: 0;"><label for='compactviewcheckbox'>[[t]]Compact view[[/t]]:<input type='checkbox' id='compactviewcheckbox' value='1' /></label></p>


<div id="widgets">
    <div id="active" class="panel">
        <p>[[t]]Active widgets[[/t]]</p>
        <ul class="widgetlist">
        [[ $active ]]
        </ul>
    </div>


    <div id="available" class="panel" style="margin-left: 20px;">
        <p>[[t]]Available Widgets[[/t]]</p>
        <ul class="widgetlist">
        [[ $inactive ]]
        </ul>
    </div>
 </div>
 
<div style="clear: both;">&nbsp;</div>
 
<p class="buttons">
    <a href="#" class="positive" id="savewidgets">
        <img src="./pics/tick.png" alt="" />
        [[t]]Save[[/t]]
    </a>
</p>


<script type="text/javascript">
//<![CDATA[

var identifiers = "[[$identifiers]]";

jQuery(function($) {

    // Check for extension updates..
    $.ajax({
        type: "GET",
        url: "./ajaxhelper.php",
        data: "function=getExtensionUpdates&ids="+identifiers,
        dataType: "json",
        success: function(data){

            $.each(data, function() {

                var currentversion = $('#update-'+this.id).attr('rel');

                // Only compare versions if we have a version ..
                if (this.version && !versionCompare(this.version, currentversion)) {                
                    $('#update-'+this.id).html("<span class='updateavailable'><a href='http://extensions.pivotx.net/entry/"+ this.uid + "' target='_blank'>[[t]]Update available[[/t]]. [[t]]Version[[/t]] " + this.version + "</a></span>");
                }

            });

        }
    });

    // Make the widgets drag-, drop- and sortable..
    $("ul.widgetlist").sortable({ connectWith: ['ul.widgetlist'] });

    // Bind a 'click' event to save the widgets.
    $("#savewidgets").click(function(){saveWidgets(); return false;});

    // Handle the 'compact view' checkbox..
    $('#compactviewcheckbox').bind('click, change', function(){

        var status = $('#compactviewcheckbox').attr('checked')?1:0;

        // Hide or show content..
        if (status) {
            $('li.widget p').hide();
            var updateas = "set";
        } else {
            $('li.widget p').show();
            var updateas = "clear";
        }

        // Save it in settings.
        $.ajax({
            type: "GET",
            url: "./ajaxhelper.php",
            data: "function=setExtensionCompact&" + updateas + "=1",
            dataType: "json",
            succes: function(data){
                console.log( data );
            }
        });

    });

    // Set the default for 'compact view'..
    if ([[ $compactview ]]) {
        $('li.widget p').hide();
        $('#compactviewcheckbox').attr('checked', true);
    }

});

function saveWidgets(s) {

    serial = $("#active ul.widgetlist").sortable('serialize', { expression: "([a-z0-9-]+)_([a-z0-9-._]+)"}  );
    
    if (serial=="") { serial = "widget=0"; }
    
    self.location = "index.php?page=widgets&" + serial; 

}

[[include file="inc_js_extensions.tpl" ]]

//]]>
</script>

[[include file="inc_footer.tpl" ]]
