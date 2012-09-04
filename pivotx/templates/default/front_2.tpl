[[ include file="`$templatedir`/_sub_header.tpl" ]]
    <div id="linkdump2">
        [[ include file="`$templatedir`/_sub_weblog_linkdump.tpl" ]]
      <!-- end of weblog 'linkdump' -->
    </div>

    <div id="entries2">
        <!-- begin of weblog 'standard' -->
        [[ subweblog name="standard" ]]
            [[ include file="`$templatedir`/_sub_weblog.tpl" ]]
        [[ /subweblog ]]
        <!-- end of weblog 'standard' -->
        [[ paging action="digg" ]]
    </div>
    <div style="clear:both">&nbsp;</div>
</div>

[[ include file="`$templatedir`/_sub_footer.tpl" ]]