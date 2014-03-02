[[include file="inc_header.tpl" ]]

[[ if count($users) > 7 ]]
<p class="buttons">
    <a href="index.php?page=useredit" class="dialog" title="[[t]]Create New User[[/t]]">
        <img src="pics/user_add.png" />
        [[t]]Create New User[[/t]]
    </a>
</p>
[[/if]]

<table class='formclass' cellspacing='0'>
    <tr>
        <th>[[t]]Username[[/t]]</th>
        <th>[[t]]Nickname[[/t]]</th>
        <th>[[t]]Userlevel[[/t]]</th>
        <th>[[t]]Last Login[[/t]]</th>
        <th>[[t]]Email[[/t]]</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
    </tr>

    [[ foreach from=$users item=user ]]
    <tr class='[[ cycle values="even, odd"]]'>
        <td><strong><a href="index.php?page=useredit&amp;user=[[ $user.username ]]" class="dialog user" title="[[t]]Edit User[[/t]]">[[ $user.username ]]</strong></a></td>
        <td><strong>[[ $user.nickname|escape:'html' ]]</strong></td>
        <td>[[ $user.userlevel ]]</td>
        <td>[[ $user.lastseen ]]</td>
        <td>[[ $user.email|escape:'html' ]]</td>
        <td align='right' class="buttons_small" style="padding: 2px 0px">
        [[ if $user.allow_edit ]] 
            <a href="index.php?page=useredit&amp;user=[[ $user.username ]]" class="dialog user" title="[[t]]Edit User[[/t]]">
                <img src="pics/user_edit.png" alt="" />[[t]]Edit[[/t]]</a>
        [[ /if ]]
        </td>            
        <td align='right' class="buttons_small" style="padding: 2px 4px 2px 4px;">
        [[ if $user.allow_edit ]] 
            <a href="#" onclick="return confirmme('index.php?page=users&amp;del=[[ $user.username ]]', '[[t escape=js 1=$user.username]]You\'re about to remove access for %1. Are you sure you want to do this?[[/t]]');" class="negative" style="margin-left: 7px">
                <img src="pics/user_delete.png" alt="" />[[t]]Delete[[/t]]</a>
        [[ /if ]]
        </td>

    </tr>
    [[ /foreach ]]
</table>


<p class="buttons">
    <a href="index.php?page=useredit" class="dialog" title="[[t]]Create New User[[/t]]">
        <img src="pics/user_add.png" alt="" />
        [[t]]Create New User[[/t]]
    </a>
</p>

[[ $script ]]

[[include file="inc_footer.tpl" ]]
