[[ include file="`$templatedir`/_sub_header.tpl" ]]
[[ include file="`$templatedir`/_sub_sidebar.tpl" ]]

<div id="entries3">
    <!-- begin of weblog 'standard' -->
    [[ subweblog name="standard" ]]
        [[ include file="`$templatedir`/_sub_weblog.tpl" ]]
    [[ /subweblog ]]
    <!-- end of weblog 'standard' -->
    [[ paging action="digg" ]]
</div>

    <div id="linkdump3">
        [[ include file="`$templatedir`/_sub_weblog_linkdump.tpl" ]]
        <!-- end of weblog 'linkdump' -->
    </div>
    <div style="clear:both">&nbsp;</div>
</div>
<br />
</body>
</html>