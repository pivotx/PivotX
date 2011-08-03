[[include file="inc_header.tpl" ]]

<p>
[[t]]The default category is:[[/t]] <strong>[[ $defaultcategory ]]</strong>. <br />
[[t]]This means that new entries will have this category, unless you edit the selected categories.[[/t]]
</p>

[[ if count($categories) > 7 ]]
<p class="buttons">
    <a href="index.php?page=categoryedit" class="dialog" title="[[t]]Create New Category[[/t]]">
        <img src="pics/shape_square_add.png" />
        [[t]]Create New Category[[/t]]
    </a>
</p>
[[/if]]

<table class='formclass' cellspacing='0'>
    <tr>
        <th>[[t]]Name[[/t]]</th>
        <th>[[t]]Display Name[[/t]]</th>
        <th>[[t]]Users[[/t]]</th>
        <th>[[t]]Number of Entries[[/t]]</th>
        <th>[[t]]Hidden Category[[/t]]</th>
        <th>[[t]]Sorting Order[[/t]]</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>        
    </tr>
    [[ foreach from=$categories item=cat ]]
    <tr class='[[ cycle values="even, odd"]]'>
        <td><strong><a href="index.php?page=categoryedit&amp;cat=[[ $cat.name ]]" class="dialog" title="[[t]]Edit Category[[/t]]">[[ $cat.name ]]</a></strong></td>
        <td><strong>[[ $cat.display ]]</strong></td>
        <td>[[ if count($cat.users)>5 ]] <acronym title="[[ implode array=$cat.nicknames ]]">[[ count array=$cat.users ]] [[t]]users[[/t]]</acronym> [[ else ]][[ implode array=$cat.nicknames ]][[/if]]</td>
        <td><a href="index.php?page=entries&filterCategory=[[ $cat.name ]]"
                title="[[t]]View entries[[/t]]">[[ $cat.no_of_entries ]]</a></td>
        <td>[[ yesno value=$cat.hidden ]]</td>
        <td>[[ $cat.order ]]</td>
        <td align='right' class="buttons_small" style="padding: 2px 0px">
            <a href="index.php?page=categoryedit&amp;cat=[[ $cat.name ]]" class="dialog" title="[[t]]Edit Category[[/t]]">
                <img src="pics/shape_square_edit.png" alt="" />
                [[t]]Edit[[/t]]
            </a>
        </td>
        <td align='right' class="buttons_small" style="padding: 2px 4px 2px 4px;">
            <a href="#" onclick="return confirmme('index.php?page=categories&amp;del=[[ $cat.name ]]', '[[t escape=js ]]Are you sure you wish to delete this category?[[/t]]');" class="negative" style="margin-left: 7px">
                <img src="pics/shape_square_delete.png" alt="" />
                [[t]]Delete[[/t]]
            </a>
        </td>

    </tr>
    [[ /foreach ]]
</table>

<p class="buttons">
    <a href="index.php?page=categoryedit" class="dialog" title="[[t]]Create New Category[[/t]]">
        <img src="pics/shape_square_add.png" alt="" />
        [[t]]Create New Category[[/t]]
    </a>
</p>

[[ $script ]]

[[include file="inc_footer.tpl" ]]
