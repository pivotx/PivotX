    <br />
    <br />

    [[ hook name="content-end" ]]
</div><!-- end of 'content' -->

[[ hook name="content-after" ]]

<br />

[[ hook name="footer-before" ]]
<div id="footer">
    [[ hook name="footer-begin" ]]
     <small>
         [[ $now ]]
         [[ if $config.debug==1 ]]
            - <a href="modules/module_debug.php#bottom" onclick="void(debugwin = window.open('modules/module_debug.php#bottom', 'debugwin', 'status=yes, scrollbars=yes, resizable=yes, width=700, height=300')); return false;">[[t]]View debug logs[[/t]]</a>
            - [[ $timetaken ]] [[t]]sec.[[/t]], [[ $memtaken ]]
            - [[t]]using[[/t]] [[ $config.db_model ]] db[[ if $config.db_model=="mysql" ]]
            - [[ $query_count ]] [[t]]queries[[/t]] ([[ $timetaken_sql ]] [[t]]sec.[[/t]])[[/if]]
            - [[t]]build[[/t]] #[[$svnbuild]].
        [[/if]]
     </small>

    <em>[[ $build ]]</em> &nbsp; - &nbsp; &copy; [[ $year ]], <a href="http://www.pivotx.net" target="_blank">[[t]]The PivotX Team[[/t]]</a>
        - <a href="index.php?page=about">[[t]]About[[/t]]</a>.

    [[ hook name="footer-end" ]]
</div><!-- end of 'footer' -->
[[ hook name="footer-after" ]]


[[ hook name="body-end" ]]
</body>
[[ hook name="html-end" ]]
</html>
