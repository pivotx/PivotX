[[include file="inc_header.tpl" ]]

[[* We iterate through $menu, looking for $listing, that we need to display.. *]]
[[ foreach from=$menu item=item ]] [[* level 1 *]]
    [[ if $item.uri==$listing ]][[ assign var=displaymenu value=$item]][[/if]]
    [[foreach from=$item.menu item=subitem ]] [[* level 2 *]]
        [[ if $subitem.uri==$listing ]][[ assign var=displaymenu value=$subitem]][[/if]]
        [[foreach from=$subitem.menu item=subsubitem ]] [[* level 3 *]]
            [[ if $subsubitem.uri==$listing ]][[ assign var=displaymenu value=$subsubitem]][[/if]]
        [[ /foreach ]]
    [[ /foreach ]]
[[ /foreach ]]

[[* Display the menu.. *]]
[[ if is_array($displaymenu.menu) ]]
    <ul id="placeholder-menu">
    [[foreach from=$displaymenu.menu key=key item=item name=submenu]]
        [[ if $item.name!="" ]]
            <li>
            <a href='[[$item.href]]'>[[$item.name]]</a>
            <p>[[$item.description]]</p>
            </li>
        [[ /if ]]
        
        [[ if $item.is_divider!="" ]]
            <hr size="1" noshade="noshade" style="margin-left: 40px; margin-bottom: 10px; width: 500px;" />
        [[ /if ]]
        
    [[/foreach ]]
    </ul>
[[ /if ]]

[[include file="inc_footer.tpl" ]]