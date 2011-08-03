[[include file="inc_header.tpl" ]]

[[ if (is_array($comments) && count($comments)>0 )]]
    [[ if $uid==0 ]]<h3>[[t]]The latest comments[[/t]].</h3>[[/if]]
    <table class='formclass tabular' cellspacing='0' border='0' width='800'>
        <tr>

            <th> [[t]]Name[[/t]] / [[t]]Email[[/t]]  /  [[t]]URL[[/t]]    </th>
            <th> [[t]]IP-address[[/t]]     </th>
            <th> [[t]]Date[[/t]]     </th>
            <th> &nbsp;       </th>
            <th> &nbsp;       </th>            
            <th> &nbsp;       </th>            
        </tr>

        [[ foreach from=$comments key=key item=comment ]]

        <tr class="[[ if $comment.moderate ]]moderate [[/if]][[ if $comment.blocked ]]blocked[[/if]]">

            <td class="nowrap">&#8470; [[ $key ]]. 
                <strong>
                    [[ $comment.name|truncate:28 ]]
                </strong>
                <span style="color:#888; font-size: 11px;">
                   [[ if $comment.email!="" ]]
                   / <a href='mailto:[[ $comment.email ]]'>[[ $comment.email|truncate:28 ]]</a>
                    [[ /if ]]
                    [[ if $comment.url!="" ]]
                    / <a href='[[ $comment.url|addhttp ]]'>[[ $comment.url|trimhttp|truncate:28 ]]</a>
                    [[ /if ]]

                    [[ if $comment.registered==1 ]](registered)[[ /if ]]
                    [[ if $comment.discreet==1 ]](discreet)[[ /if ]]
                    [[ if $comment.notify==1 ]](notify)[[ /if ]]
                    [[ if $comment.moderate==1 ]](in moderation)[[ /if ]]


                </span>
            </td>
            <td class="nowrap">
            <span style="font-size: 11px;">
                [[ $comment.ip ]]
                </span>
            </td>
            <td class="nowrap">
            <span style="font-size: 11px;">
                [[ date date=$comment.date format="%ordday% %monthname% '%ye% - %hour24%:%minute%" ]]
                </span>
            </td>

            <td rowspan='2' class="buttons_small nowrap" style='border-bottom: 1px solid #BBB; color: #777;'>
                [[ if $comment.allowedit]]
                <a href="index.php?page=editcomment&amp;uid=[[ $comment.entry_uid ]]&amp;key=[[ $comment.uid ]]" class="dialog comment" title="[[t]]Edit this comment[[/t]]">
                    <img src="pics/world_edit.png" alt="" />[[t]]Edit[[/t]]
                </a>
                [[ else ]]
                &nbsp;
                [[/if]]
            </td>

            <td rowspan='2' class="buttons_small nowrap" style='border-bottom: 1px solid #BBB; color: #777;'>
                [[ if $comment.allowedit]]
                <a href="#" onclick="return confirmme('index.php?page=comments&amp;uid=[[ $comment.entry_uid ]]&amp;del=[[ $comment.uid ]]', '[[t escape=js ]]Delete this comment?[[/t]]');" class="negative">
                    <img src="pics/world_delete.png" width='16' height='16' style='border-width: 0px;' alt="" />[[t]]Delete[[/t]]
                </a>
                [[ else ]]
                &nbsp;
                [[/if]]
            </td>
            
            <td rowspan='2' class="buttons_small nowrap" style='border-bottom: 1px solid #BBB; color: #777;'>
                [[ if $allowblock ]]
                    [[ if $comment.blocked ]]
                    <a href="#" onclick="return confirmme('index.php?page=comments&amp;uid=[[ $comment.entry_uid ]]&amp;unblock=[[ $comment.uid ]]', '[[t escape=js ]]Unblock this IP-address?[[/t]]');" class="negative">
                        <img src="pics/cross.png" width='16' height='16' style='border-width: 0px;' alt="" />[[t]]Unblock IP[[/t]]
                    </a>
                    [[else]]
                    <a href="#" onclick="return confirmme('index.php?page=comments&amp;uid=[[ $comment.entry_uid ]]&amp;block=[[ $comment.uid ]]', '[[t escape=js ]]Block this IP-address?[[/t]]');" class="negative">
                        <img src="pics/cross.png" width='16' height='16' style='border-width: 0px;' alt="" />[[t]]Block IP[[/t]]
                    </a>
                    [[/if]]
                [[ else ]]
                &nbsp; 
                [[/if]]
            </td>       

        </tr>

        <tr class="[[ if $comment.moderate ]]moderate [[/if]][[ if $comment.blocked ]]blocked[[/if]]">
            <td colspan='3' style='border-bottom: 1px solid #BBB; color: #777; padding-top: 0px;'>
                <p style='margin: 0px;'>
                    [[ $comment.comment|strip_tags|truncate:$truncate|wordwrap:70:' ':1 ]] 
                    [[ if $comment.entrytitle!="" ]]<em>([[t]]on[[/t]]: [[ $comment.entrytitle|truncate:40 ]])</em>[[ /if]]</p>
            </td>
        </tr>

        [[ /foreach ]]


    </table>



[[ else ]]

<p>[[ if $uid==0 ]][[t]]There are no latest comments[[/t]][[else]][[t]]No comments[[/t]][[ /if ]].</p>

[[ /if ]]    

[[include file="inc_footer.tpl" ]]
