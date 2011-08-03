[[include file="inc_header.tpl" ]]

<table class='formclass' cellspacing='0'>
    <tr>
        <th>[[t]]Username[[/t]]</th>
        <th>[[t]]Email[[/t]]</th>
        <th>[[t]]URL[[/t]]</th>
        <th>[[t]]Last Login[[/t]]</th>
        <th>[[t]]Verified[[/t]]</th>
        <th>[[t]]Disabled[[/t]]</th>
        <th>[[t]]Notifications[[/t]]</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
    </tr>

    [[ foreach from=$users key=key item=user ]]
    <tr class='[[ cycle values="even, odd"]]'>
        <td valign="top" ><strong>[[ $user.name ]]</strong></td>
        <td valign="top" >[[ $user.email ]]</td>
        <td valign="top" >[[ $user.url ]]</td>
        <td valign="top" >[[ $user.last_login ]]</td>
        <td valign="top" >[[ if $user.verified == 1 ]] [[t]]Yes[[/t]] [[else]] [[t]]No[[/t]] [[/if]]</td>
        <td valign="top" >[[ if $user.disabled == 1 ]] [[t]]Yes[[/t]] [[else]] [[t]]No[[/t]] [[/if]]</td>
        <td valign="top" >[[ if $user.notify_entries == 1 ]] [[t]]Yes[[/t]] [[else]] [[t]]No[[/t]] [[/if]]</td>
        <td valign="top" class="buttons_small" style="padding: 2px 0px">
            <a href="index.php?page=visitoredit&amp;user=[[ $key ]]"
                class="dialog" title="[[t]]Edit Visitor[[/t]]">
                <img src="pics/world_edit.png" alt="" />[[t]]Edit[[/t]]</a>
        </td>
        <td align='right' class="buttons_small" style="padding: 2px 4px 2px 4px;">
            <a href="#" onclick="return confirmme('index.php?page=visitors&amp;del=[[ $key ]]', '[[t escape=js 1=$user.name ]]You are about to delete visitor %1. Are you sure?[[/t]]');" class="negative" style="margin-left: 7px">
                <img src="pics/world_delete.png" alt="" />[[t]]Delete[[/t]]</a>
        </td>
    </tr>
    [[ /foreach ]]
</table>

[[include file="inc_footer.tpl" ]]
