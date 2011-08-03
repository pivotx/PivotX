[[include file="inc_header.tpl" ]]

<div id="container">


    <div class="homeleftcolumn">
    
        [[ $html ]]
        [[ $form ]]

    </div>

    <div class="homerightcolumn">
        
        <noscript>
            <div class='warning'><h2><img src='pics/error.png' alt='' height='16' width='16' style='border-width: 0px; margin-bottom: -3px;' />
                <strong>[[t]]Warning![[/t]]</strong></h2>
                <p>[[t]]Javascript and Cookies must be enabled to log in to PivotX.[[/t]]</p>
            </div>
        </noscript>

        [[ if is_array($warnings) && count($warnings)>0 ]]
            [[ foreach from=$warnings key=key item=item ]]
            <div class="warning">
                <h2><img src="pics/error.png" alt="" height="16" width="16" style="border-width: 0px; margin-bottom: -3px;" /> Warning!</h2>   
                [[ $item ]]
            </div>
            [[ /foreach ]]
        [[ /if ]]

    </div>

    &nbsp; <!-- Because Internet Explorer is a bug-filled piece of trash, it needs this space, because otherwise it won't show the contents of #errorbanner. Yes. Really. -->

    <div class="cleaner">&nbsp;</div>

</div><!-- end of 'container' -->


<script type="text/javascript">
//<![CDATA[

jQuery(function($) {

    // Check if we have a session cookie.
    if (!cookieEnabled()) {
        var html = "<div class='warning'><h2><img src='pics/error.png' alt='' height='16' width='16' style='border-width: 0px; margin-bottom: -3px;' />";
        html += "<strong>[[t]]Warning![[/t]]</strong></h2>";
        html += "<p>[[t]]PivotX couldn't set the session properly. Try logging out, and logging on again. You could also try clearing your browser's cache, and make sure no software on your computer is interfering with the cookies.[[/t]]</p>";
        html += "<p>[[t]]If the problem persists, ask for help on the forum.[[/t]]</p>";
        html += "</div>";

        $('.homerightcolumn').prepend(html);

    }

});

//]]>
</script>

[[include file="inc_footer.tpl" ]]
