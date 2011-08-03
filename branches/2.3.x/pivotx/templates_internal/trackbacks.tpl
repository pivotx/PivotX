[[include file="inc_header.tpl" ]]

[[ if (is_array($trackbacks) && count($trackbacks)>0 )]]
    [[ if $uid==0 ]]<h3>[[t]]The latest trackbacks[[/t]].</h3>[[/if]]
    <table class='formclass' cellspacing='0' border='0' width='800'>
        <tr>

            <th> [[t]]Blog Name[[/t]] / [[t]]URL[[/t]]    </th>
            <th> [[t]]IP-address[[/t]]     </th>
            <th> [[t]]Date[[/t]]     </th>
            <th> &nbsp;       </th>
            <th> &nbsp;       </th>            
            <th> &nbsp;       </th>
        </tr>

        [[ foreach from=$trackbacks key=key item=trackback ]]

            <tr class="[[ if $trackback.blocked ]]blocked[[/if]]">

            <td class="nowrap">&#8470; [[ $key ]]. 
                <strong>
                    [[ $trackback.name|truncate:28 ]]
                </strong>
                <span style="color:#888; font-size: 11px;">
                    [[ if $trackback.url!="" ]]
                    / <a href='[[ $trackback.url|addhttp ]]'>[[ $trackback.url|trimhttp|truncate:28 ]]</a>
                    [[ /if ]]
                </span>
            </td>
            <td class="nowrap">
            <span style="font-size: 11px;">
                [[ $trackback.ip ]]
                </span>
            </td>
            <td class="nowrap">
            <span style="font-size: 11px;">
                [[ date date=$trackback.date format="%ordday% %monthname% '%ye% - %hour24%:%minute%" ]]
                </span>
            </td>

            <td rowspan='2' class="buttons_small nowrap" style='border-bottom: 1px solid #BBB; color: #777;'>
                [[ if $trackback.allowedit]]
                <a href="index.php?page=edittrackback&amp;uid=[[ $trackback.entry_uid ]]&amp;key=[[ $trackback.uid ]]" class="dialog comment" title="[[t]]Edit this trackback[[/t]]"><img src="pics/world_edit.png" alt="" />[[t]]Edit[[/t]]</a>
                [[ else ]]
                &nbsp;
                [[/if]]
            </td>
            
            <td rowspan='2' class="buttons_small nowrap" style='border-bottom: 1px solid #BBB; color: #777;'>
    
                [[ if $trackback.allowedit]]
                <a href="#" onclick="return confirmme('index.php?page=trackbacks&amp;uid=[[ $trackback.entry_uid ]]&amp;del=[[ $trackback.uid ]]', '[[t escape=js ]]Delete this trackback?[[/t]]');" class="negative"><img src="pics/world_delete.png" width='16' height='16' style='border-width: 0px;' alt="" />[[t]]Delete[[/t]]</a>
                [[ else ]]
                &nbsp;
                [[/if]]
            </td>

            <td rowspan='2' class="buttons_small nowrap" style='border-bottom: 1px solid #BBB; color: #777;'>
                [[ if $allowblock ]]
                    [[ if $trackback.blocked ]]
                    <a href="#" onclick="return confirmme('index.php?page=trackbacks&amp;uid=[[ $trackback.entry_uid ]]&amp;unblock=[[ $trackback.uid ]]', '[[t escape=js ]]Unblock this IP-address?[[/t]]');" class="negative">
                        <img src="pics/cross.png" width='16' height='16' style='border-width: 0px;' alt="" />[[t]]Unblock IP[[/t]]
                    </a>
                    [[else]]
                    <a href="#" onclick="return confirmme('index.php?page=trackbacks&amp;uid=[[ $trackback.entry_uid ]]&amp;block=[[ $trackback.uid ]]', '[[t escape=js ]]Block this IP-address?[[/t]]');" class="negative">
                        <img src="pics/cross.png" width='16' height='16' style='border-width: 0px;' alt="" />[[t]]Block IP[[/t]]
                    </a>
                    [[/if]]
                [[ else ]]
                &nbsp;
                [[/if]]
            </td>

        </tr>

            <tr class="[[ if $trackback.blocked ]]blocked[[/if]]">
            <td colspan='3' style='border-bottom: 1px solid #BBB; color: #777; padding-top: 0px;'>
                <p style='margin: 0px;'>
                    [[ $trackback.excerpt|truncate:110 ]] 
                    [[ if $trackback.entrytitle!="" ]]<em>([[t]]on[[/t]]: [[ $trackback.entrytitle|truncate:40 ]])</em>[[ /if]]</p>
            </td>
        </tr>

        [[ /foreach ]]


    </table>



[[ else ]]

<p>[[ if $uid==0 ]][[t]]There are no latest trackbacks[[/t]][[else]][[t]]No trackbacks[[/t]][[ /if ]].</p>

[[ /if ]]    

[[include file="inc_footer.tpl" ]]
