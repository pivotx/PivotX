[[include file="inc_header.tpl" ]]

[[ if $moderating ]]
    [[ if (is_array($modcomments) && count($modcomments)>0 ) ]]
    <h3>[[t]]Comments waiting for moderation[[/t]].</h3>
        <form action="index.php?page=comments" method="post">
        <table class='formclass tabular moderate-comments' cellspacing='0' border='0' width='800'>
            <tr>
                <th> &nbsp; </th>
                <th> [[t]]Name[[/t]] / [[t]]Email[[/t]]  /  [[t]]URL[[/t]]    </th>
                <th> [[t]]IP-address[[/t]]     </th>
                <th> [[t]]Date[[/t]]     </th>
                <th> &nbsp;       </th>
                <th> &nbsp;       </th>                       
                <th> &nbsp;       </th>                       
            </tr>

            [[assign var='oddeven' value='even']]
    
            [[ foreach from=$modcomments key=key item=comment ]]

                [[if $oddeven=='even']]
                    [[assign var='oddeven' value='odd']]
                [[else]]
                    [[assign var='oddeven' value='even']]
                [[/if]]
    
            <tr class="moderate comment-header comment-[[$comment.uid]]  moderate-[[$oddeven]][[ if $comment.blocked ]]blocked[[/if]]">
    
                <td rowspan="2" style='border-bottom: 1px solid #BBB; color: #777;'>
                    [[ if $comment.allowedit]]
                    <input type="checkbox" name="checked[]" value="[[ $comment.uid ]]" />
                    [[/if]]
                </td>
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
            </td>

            <td rowspan='2' class="buttons_small nowrap" style='border-bottom: 1px solid #BBB; color: #777;'>
                [[ if $comment.allowedit]]
                <a href="index.php?page=editcomment&amp;uid=[[ $comment.entry_uid ]]&amp;key=[[ $comment.uid ]]&amp;return=moderatecomments" class="dialog comment" title="[[t]]Edit this comment[[/t]]">
                    <img src="pics/comment_edit.png" alt="" />[[t]]Edit[[/t]]
                </a>
                [[ else ]]
                &nbsp;
                [[/if]]

                <br/><br/>

                [[ if $comment.allowedit]]
                <a href="#" onclick="return confirmme('index.php?page=comments&amp;uid=[[ $comment.entry_uid ]]&amp;del=[[ $comment.uid ]]&amp;return=moderatecomments', '[[t escape=js ]]Delete this comment?[[/t]]');" class="negative">
                    <img src="pics/comment_delete.png" width='16' height='16' style='border-width: 0px;' alt="" />[[t]]Delete[[/t]]
                </a>
                [[ else ]]
                &nbsp;
                [[/if]]
            </td>
            
            <td rowspan='2' class="buttons_small nowrap" style='border-bottom: 1px solid #BBB; color: #777;'>
                [[ if $comment.allowedit]]
                <a href="ajaxhelper.php?function=approveComment&amp;comment=[[$comment.uid]]" data:comment="[[$comment.uid]]" class="approve-comment positive comment" title="[[t]]Approve[[/t]]">
                    <img src="pics/comment_add.png" alt="" />[[t]]Approve[[/t]]
                </a>
                [[ else ]]
                &nbsp;
                [[/if]]

                <br/><br/>

                [[ if $allowblock ]]
                    [[ if $comment.blocked ]]
                    <a href="#" onclick="return confirmme('index.php?page=comments&amp;uid=[[ $comment.entry_uid ]]&amp;unblock=[[ $comment.uid ]]&amp;return=moderatecomments', '[[t escape=js ]]Unblock this IP-address?[[/t]]');" class="negative">
                        <img src="pics/cross.png" width='16' height='16' style='border-width: 0px;' alt="" />[[t]]Unblock IP[[/t]]
                    </a>
                    [[else]]
                    <a href="#" onclick="return confirmme('index.php?page=comments&amp;uid=[[ $comment.entry_uid ]]&amp;block=[[ $comment.uid ]]&amp;return=moderatecomments', '[[t escape=js ]]Block this IP-address?[[/t]]');" class="negative">
                        <img src="pics/world_delete.png" width='16' height='16' style='border-width: 0px;' alt="" />[[t]]Block IP[[/t]]
                    </a>
                    [[/if]]
                [[ else ]]
                &nbsp; else
                [[/if]]
            </td>         
    
            </tr>
    
            <tr class="moderate comment-content comment-[[$comment.uid]] moderate-[[$oddeven]] [[ if $comment.blocked ]]blocked[[/if]]">
                <td colspan='3' style='border-bottom: 1px solid #BBB; color: #777; padding-top: 0px;'>
                    <p class="short-comment comment-text" style='margin: 0px;'>
                        [[ $comment.comment|strip_tags|truncate:$truncate|wordwrap:70:' ':1 ]] 
                        [[ if $comment.title!="" ]]<em>([[t]]on[[/t]]: [[ $comment.title|truncate:40 ]])</em>[[ /if]]</p>
                    <p class="long-comment comment-text" style="margin: 0px;">
                        [[ $comment.comment|strip_tags]] 
                        [[ if $comment.title!="" ]]<em>([[t]]on[[/t]]: [[ $comment.title ]])</em>[[ /if]]</p>
                    </p>
                </td>
            </tr>
    
            [[ /foreach ]]
    
    
        </table>
    
    
        <p style="margin: 8px 0px;" class="buttons">
    
             <a onclick="commentsCheckAll();">
                <img src="pics/tick.png" alt="" />
                [[t]]Check all[[/t]]
            </a>
        
         <a onclick="commentsCheckNone();">
            <img src="pics/cross.png" alt="" />
            [[t]]Check none[[/t]]
        </a>
        
         <button type="submit" class="positive" name="action_approve">
            <img src="pics/accept.png" alt="" />
            [[t]]Approve comments[[/t]]
        </button>
        
         <button type="submit" class="negative" name="action_delete">
                <img src="pics/delete.png" alt="" />
                [[t]]Delete comments[[/t]]
            </button>
    
        </p>
    
        </form>
    
    [[ else ]]
    
    <p>[[t]]There are no comments waiting for moderation[[/t]].</p>
    
    [[ /if ]]<!-- /if is_array($modcomments) -->
[[ /if  ]]<!-- /if moderating -->

[[include file="inc_footer.tpl" ]]
