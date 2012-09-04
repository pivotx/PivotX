[[ include file="`$templatedir`/_sub_header.tpl" ]]
<div id="content">
    <div id="main">
        <!-- begin of weblog 'standard' -->
        [[ subweblog name="standard" ]]
            [[ include file="`$templatedir`/_sub_weblog.tpl" ]]
        [[ /subweblog ]]
        <!-- end of weblog 'standard' -->
        <div class="pagenav">[[* pager does not work for archives *]]
            [[ paging action="prev" ]] |
            [[ paging action="curr" ]] |
            [[ paging action="next" ]]
            <!-- remove the stars to enable the Digg style paginator -->
            [[* paging action="digg" *]]    
        </div>
    </div><!-- #main -->
    [[ include file="`$templatedir`/_sub_sidebar.tpl" ]]
</div><!-- #content -->
[[ include file="`$templatedir`/_sub_footer.tpl" ]]