[[include file="mobile/inc_header.tpl" ]]


[[ $html ]]

[[ $form ]]

<noscript>
    <div class='warning'><h2><img src='pics/error.png' alt='' height='16' width='16' style='border-width: 0px; margin-bottom: -3px;' />
        <strong>[[t]]Warning![[/t]]</strong></h2>
        <p>[[t]]Javascript and Cookies must be enabled to log in to PivotX.[[/t]]</p>
    </div>
</noscript>


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

        $('#content').append(html);

    }

});

//]]>
</script>

[[include file="mobile/inc_footer.tpl" ]]
