[[ include file="`$templatedir`/_sub_header.tpl" ]]
<div id="content">
    <div id="main">
        <!-- begin of weblog 'standard' -->
        [[ subweblog name="standard" ]]
            [[* similar include in archive.tpl -- when you want different lay-outs be aware of this *]]
            [[ include file="`$templatedir`/_sub_weblog.tpl" ]]
        [[ /subweblog ]]
        <!-- end of weblog 'standard' -->
        <div class="pagenav">
            [[ paging action="prev" ]] |
            [[ paging action="curr" ]] |
            [[ paging action="next" ]]
        </div>
        <!-- remove the stars to enable the Digg style paginator -->
        [[* paging action="digg" *]]
    </div><!-- #main -->
    [[ include file="`$templatedir`/_sub_sidebar.tpl" ]]
</div><!-- #content -->
[[ include file="`$templatedir`/_sub_footer.tpl" ]]