<hr size="1" noshade="noshade" />
<h2 id="navigation">[[t]]Navigation[[/t]]</h2>

<!-- Javascript enabled Jumpmenu for the pages -->
    <select id='pagemenu' style="display:none;">

        [[ pagelist
            chapterbegin="<optgroup label='%chaptername%'>"
            pages="<option %active% value='%link%'>%title%</option>"
            chapterend="</optgroup>"
            isactive="selected='selected'"
        ]]

    </select>

    <!-- Accessible version, for users without Javascript -->
    <noscript>
        [[ pagelist
            chapterbegin="<h3>%chaptername%</h3><ul>"
            pages="<li %active%><a href='%link%' title='%subtitle%'>%title%</a></li>"
            chapterend="</ul>"
            isactive="class='activepage'"
        ]]
    </noscript>


    <h3>[[t]]Archives[[/t]]</h3>

    <!-- Javascript enabled Jumpmenu for the archives -->
    <select id='archivemenu' style="display:none;">

    [[archive_list
        unit="month"
        order="desc"
        format="<option %active% value='%url%'>%st_monthname% %st_year%</option>"
        isactive="selected='selected'"
    ]]

    </select>

    <!-- Accessible version, for users without Javascript -->
    <noscript>
    <ul>
    [[archive_list
        unit="month"
        order="desc"
        format="<li %active%><a href='%url%'>%st_monname% %st_year%</a></li>"
        isactive="class='activepage'"
    ]]
     </ul>
    </noscript>

    <script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery('#archivemenu').show();
        jQuery('#archivemenu').bind('change', function(){
            document.location = jQuery('#archivemenu').val();
        });

        jQuery('#pagemenu').show();
        jQuery('#pagemenu').bind('change', function(){
            document.location = jQuery('#pagemenu').val();
        });

        jQuery('#catmenu').show();
        jQuery('#catmenu').bind('change', function(){
            document.location = jQuery('#catmenu').val();
        });

    });
    </script>

    [[ if $pagetype!="search" ]]
      <h3>[[t]]Search[[/t]]</h3>
      [[ search ]]
    [[ /if ]]

    <h3>[[t]]Stuff[[/t]]</h3>
    [[pivotxbutton]]
    [[rssbutton]]
    [[atombutton]]

<!-- a small 'hack' to make sure jquery is included.. -->
<!-- [[popup file='icon_pivotx.jpg']] -->

<hr size="1" noshade="noshade" />
<p><a href="#top">Top</a></p>
</body>
</html>
