[[include file="inc_header.tpl" ]]

[[ if count($weblogs) > 7 ]]
<p class="buttons">
    <a href="index.php?page=weblognew">
        <img src="pics/world_add.png" alt=""/>
        [[t]]New Weblog[[/t]]
    </a>
</p>
[[/if]]

<table class='formclass' cellspacing='0'>
    <tr>
        <th>[[t]]Weblog Name[[/t]]</th>
        <th>[[t]]Categories[[/t]]</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
    </tr>

    [[ foreach from=$weblogs key=key item=weblog ]]
    <tr class='[[ cycle values="even, odd"]]'>
        <td valign="top" ><strong><a href="index.php?page=weblogedit&amp;weblog=[[ $key ]]">[[ $weblog.name ]]</a></strong>
            <span style="color:#555; font-size: 85%;"> ([[ $key ]] - [[t]]order[[/t]] [[ $weblog.sortorder ]])</span><br />
            <div style='padding-top: 4px; font-size: 11px'>[[ $weblog.payoff|wordwrap:60:"<br />\n" ]]</div></td>
        <td valign="top" >
            [[ if count($weblog.categories)>5 ]] <acronym title="[[ category name=$weblog.categories ]]">[[ count array=$weblog.categories ]] [[t]]categories[[/t]]</acronym> [[ else ]][[ category name=$weblog.categories ]][[/if]]
            &nbsp;
        </td>

        <td valign="top" class="buttons_small" style="padding: 2px 0px">
            <a href="[[ $weblog.link ]]" [[if !$config.front_end_links_same_window]]target="_blank"[[/if]] class="front_end">
                <img src="pics/world.png" alt="" />[[t]]View[[/t]]</a>
        </td>
        <td valign="top" class="buttons_small" style="padding: 2px 0px">
            <a href="index.php?page=weblogedit&amp;weblog=[[ $key ]]" style="margin-left: 7px">
                <img src="pics/world_edit.png" alt="" />[[t]]Edit[[/t]]</a>
        </td>
        <td valign="top" class="buttons_small" style="padding: 2px 0px">
            <a href="#" onclick="return confirmme('index.php?page=weblogs&amp;del=[[ $key ]]', '[[t escape=js 1=$weblog.name]]You are about to delete weblog %1. Are you sure?[[/t]]');" class="negative" style="margin-left: 7px">
                <img src="pics/world_delete.png" alt="" />[[t]]Delete[[/t]]</a>
        </td>
        <td valign="top" class="buttons_small" style="padding: 2px 4px 2px 0px;">
            <a href="index.php?page=weblogs&amp;export=[[ $key ]]" style="margin-left: 7px">
                <img src="pics/cog_go.png" alt="" />[[t]]Export as theme[[/t]]</a>
        </td>

    </tr>
    [[ /foreach ]]
</table>


<p class="buttons">
    <a href="index.php?page=weblognew">
        <img src="pics/world_add.png" alt=""/>
        [[t]]New Weblog[[/t]]
    </a>
</p>


[[include file="inc_footer.tpl" ]]
