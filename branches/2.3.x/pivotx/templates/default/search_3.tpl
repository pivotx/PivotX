    [[ include file="`$templatedir`/_sub_header.tpl" ]]
    
    [[ include file="`$templatedir`/_sub_sidebar.tpl" ]]
    
    <div id="entries3">
        <div class="entry">
        <!-- begin of 'content' displayed on the search / tagpage -->
            [[ content ]]
            <!-- end of 'content' -->
        </div>
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