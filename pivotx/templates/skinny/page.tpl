[[ include file="`$templatedir`/_sub_header.tpl" ]]
<div id="content">
    <div id="main">
        <div class="entry">
            <h2><a href="[[ link hrefonly=1 ]]">[[ title ]]</a></h2>
            <h3>[[ subtitle ]]</h3>
            [[ introduction ]]
            [[ body ]]
            <div class="meta" style='clear:both;'>
                [[ user field=emailtonick ]] |
                [[ date format="%dayname% %day% %monthname% %year% - %hour12%&#58;%minute% %ampm%" ]]
                [[ editlink format="Edit" prefix=" | " ]]
            </div>
        </div><!-- .entry -->
        <div class="pagenav">
            [[previouspage text="&laquo; <a href='%link%'>%title%</a>" cutoff=20 ]] | 
            <a href="[[webloghome]]">[[t]]Home[[/t]]</a> | 
            [[nextpage text="<a href='%link%'>%title%</a> &raquo;" cutoff=20 ]]
        </div>
    </div><!-- #main -->
    [[ include file="`$templatedir`/_sub_sidebar.tpl" ]]
</div><!-- #content -->
[[ include file="`$templatedir`/_sub_footer.tpl" ]]