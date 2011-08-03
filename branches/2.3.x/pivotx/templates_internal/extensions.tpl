[[include file="inc_header.tpl" ]]

<div id="container">


    <div class="homeleftcolumn">

        <p style="margin: 0;"><label for='compactviewcheckbox'>[[t]]Compact view[[/t]]:<input type='checkbox' id='compactviewcheckbox' value='1' /></label></p>

        [[ $html ]]
        [[ $form ]]

    </div>

    <div class="cleaner">&nbsp;</div>



</div><!-- end of 'container' -->


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


    // Handle the 'compact view' checkbox..
    $('#compactviewcheckbox').bind('click, change', function(){

        var status = $('#compactviewcheckbox').attr('checked')?1:0;

        // Hide or show content..
        if (status) {
            $('p.extension-desc, p.extension-metadata').hide();
            var updateas = "set";
        } else {
            $('p.extension-desc, p.extension-metadata').show();
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
        $('p.extension-desc, p.extension-metadata').hide();
        $('#compactviewcheckbox').attr('checked', true);
    }

});


[[include file="inc_js_extensions.tpl" ]]

//]]>
</script>


[[include file="inc_footer.tpl" ]]
