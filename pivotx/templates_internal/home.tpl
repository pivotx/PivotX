[[include file="inc_header.tpl" ]]


<div id="container">


    <div class="homeleftcolumn">

    [[ hook name="dashboard-top" ]]

    [[ if !$config.hide_dashboard_welcome ]]
        <p style='margin: 0 0 12px 0; width: 440px;'>
            [[t escape="no" x=$build ]]Hi, and welcome to %1! Below this bit of text you can see a short overview of the latest <a href='index.php?page=entries'>entries</a> and <a href='index.php?page=pagesoverview'>pages</a>. And perhaps even <a href='index.php?page=comments'>new comments</a> that are in the moderation queue.[[/t]]
        </p>
    [[ /if]]

    [[ if !$config.hide_dashboard_quicklinks ]]
        <p style="margin: 0px 0px 12px;" class="buttons">
            [[ button link="index.php?page=entry" icon="page_white_add.png" ]] [[t]]New Entry[[/t]] [[/button]]
            [[ if $user.userlevel>=2 ]]
                [[ button link="index.php?page=page" icon="page_white_add.png" ]] [[t]]New Page[[/t]] [[/button]]
            [[ /if ]]
            [[ if $user.userlevel>=3 ]]
                [[ button link="index.php?page=configuration" icon="cog.png" ]] [[t]]Configuration[[/t]] [[/button]]
            [[/if]]
            [[ if $user.userlevel>=2 ]]
                [[ button link="index.php?page=media" icon="image.png" ]] [[t]]Manage Media[[/t]] [[/button]]
            [[/if]]
        </p>
    [[ /if]]
    
    [[ hook name="dashboard-before-entries" ]]

[[ if is_array($entries) && count($entries)>0 ]]
<table class='formclass' cellspacing='0'  style='width: 100%; padding-top: 0;'>
    <tbody>
    <tr>
        <th class="sectiontitle"><a href="index.php?page=entries"><img src="pics/page_white_go.png" alt="[[t]]overview of entries[[/t]]" title="[[t]]overview of entries[[/t]]" /></a>[[t]]The latest entries[[/t]]</th>
        <th>[[t]]Author[[/t]]</th>
        <th><img width="16" height="14" alt="[[t]]Comment[[/t]]s" src="pics/comment.png" /></th>
        <th>[[t]]Date[[/t]]</th>
        <th colspan="2" style="text-align:right"><a href="index.php?page=entries">[[t]]more[[/t]] &raquo;</a></th>
    </tr>


    [[ foreach from=$entries key=key item=item ]]
        <tr class='[[ cycle values="even, odd"]]'>

    [[ if $item.editable ]]
        <td class='dashboardclip1'><div class="clip" style='width: 260px;'>
            &#8470; [[ $item.code ]]. <strong><a href="index.php?page=entry&amp;uid=[[$item.code]]" title="[[t]]Edit this entry[[/t]]">[[ $item.title|strip_tags|trimlen:26]]</a></strong> - [[ $item.excerpt|hyphenize ]]
        </div></td>
        
        <td class="tabular">[[assign var=username value=$item.user]][[ if $users.$username != "" ]][[ $users.$username|trimlen:22 ]][[ else ]][[ $item.user|trimlen:22 ]][[/if]]</td>
        <td class="tabular" style="text-align:center;">
            <a href="index.php?page=comments&amp;uid=[[$item.code]]" title="">[[$item.commcount|intval]]</a>
        </td>
        <td class="tabular">
        [[ if $item.status=="publish"]][[ date date=$item.date format="%day%-%month%-'%ye% %hour24%:%minute%" ]]
        [[elseif $item.status=="timed"]]
        <span title="[[t]]Timed[[/t]]" class="timedpublishindicator">[[ date date=$item.publish_date format="%day%-%month%-'%ye% %hour24%:%minute%" ]]</span>
        [[else]][[t]]Held[[/t]][[/if]] </td><!--  -->
        <td width="1" align="left">
            <a href="index.php?page=entry&amp;uid=[[$item.code]]"><img src="pics/page_edit.png" alt="[[t]]edit[[/t]]" height="16" width="16" /></a>
        </td>
        <td width="1">
            <a href="#" onclick="return confirmme('index.php?page=entries&amp;del=[[$item.code]]', '[[t escape=js ]]Are you sure you wish to delete this entry?[[/t]]');"><img src="pics/page_delete.png" alt="[[t]]edit[[/t]]" height="16" width="16" /></a>
        </td>
    [[ else]]
        <td class='dashboardclip1'><div class="clip" style='width: 260px;'>
            &#8470; [[ $item.code ]]. <strong>[[ $item.title|strip_tags|trimlen:26]]</strong> - [[ $item.excerpt|hyphenize ]]
        </div></td>
        <td class="tabular">[[assign var=username value=$item.user]][[ if $users.$username != "" ]][[ $users.$username|trimlen:22 ]][[ else ]][[ $item.user|trimlen:22 ]][[/if]]</td>
        <td class="tabular">
            [[$item.commcount|intval]][[t]]c[[/t]] /
            [[$item.trackcount|intval]][[t]]t[[/t]]
        </td>
        <td class="tabular">[[ date date=$item.date format="%day%-%month%-'%ye% %hour24%:%minute%" ]] </td>
        <td width="1"><img src="pics/page_edit_dim.png" alt="-" height="16" width="16" /></td>
        <td width="1"><img src="pics/page_delete_dim.png" alt="-" height="16" width="16" /></td>  
    [[ /if ]]    
    </tr>


    [[ /foreach]]
    </tbody>
</table>

    <br />
[[/if]]
    
    [[ hook name="dashboard-before-comments" ]]
    
[[ if count($latestcomments)>0 ]]
<table class='formclass' cellspacing='0'  style='border: 1px solid #CCC; width: 100%; padding-top: 0;'>
    <tbody>
    <tr>
         <th class="sectiontitle"><a href="index.php?page=comments"><img src="pics/comment-go.png" alt="[[t]]overview of comments[[/t]]" title="[[t]]overview of comments[[/t]]" /></a>[[t]]The latest comments[[/t]]</th>
        <th>[[t]]Entry[[/t]]</th>
        <th>[[t]]Date[[/t]]</th>
        <th colspan="2" style="text-align:right"><a href="index.php?page=comments">[[t]]more[[/t]] &raquo;</a></th>
    </tr>


    [[ foreach from=$latestcomments key=key item=item ]]
    <tr class='[[ cycle values="even, odd"]][[ if $item.moderate ]] moderate[[/if]][[ if $item.blocked ]] blocked[[/if]]' >
        [[ if $item.allowedit ]]
        <td class='dashboardclip3'><div class="clip" style='width: 260px;'>
            <strong><a href="index.php?page=comments&amp;uid=[[$item.entry_uid]]" title="[[t]]Edit this comment[[/t]]">[[ $item.name|trimlen:16]]</a> </strong> -
            <span>[[ $item.comment|strip_tags|trim|trimlen:100 ]]</span>
        </div></td>
        <td class="tabular"><span style="color:#666666; font-size:11px;">&#8470; [[ $item.entry_uid ]].</span></td>
        <td class="tabular">[[ date date=$item.date format="%day%-%month%-'%ye% %hour24%:%minute%" ]] </td>
        <td width="1" align="left">    
            <a href="index.php?page=editcomment&amp;uid=[[ $item.entry_uid ]]&amp;key=[[ $item.uid ]]&return=dashboard" class="dialog comment" title="[[t]]Edit this comment[[/t]]">
                <img src="pics/page_edit.png" alt="[[t]]edit[[/t]]" height="16" width="16" />
            </a>
        </td>
        <td width="1" align="left">    
            <a href="#" onclick="return confirmme('index.php?page=comments&amp;uid=[[ $item.entry_uid ]]&amp;del=[[ $item.uid ]]&return=dashboard', '[[t escape=js ]]Delete this comment?[[/t]]');" class="negative">
                <img src="pics/page_delete.png" alt="[[t]]delete[[/t]]" height="16" width="16" />
            </a>

        </td>
        [[else]]
        <td class='dashboardclip3'><div class="clip" style='width: 260px;'>
            <strong>[[ $item.name|trimlen:16]]</strong> - <span>[[ $item.comment|strip_tags|trim|trimlen:100 ]]</span>
        </div></td>
        <td class="tabular"><span style="color:#666666; font-size:11px;">&#8470; [[ $item.entry_uid ]].</span></td>
        <td class="tabular">[[ date date=$item.date format="%day%-%month%-'%ye% %hour24%:%minute%" ]] </td>
        <td width="1" align="left">
            <img src="pics/page_edit_dim.png" alt="[[t]]edit[[/t]]" height="16" width="16" />
        </td>
        <td width="1" align="left">
            <img src="pics/page_delete_dim.png" alt="[[t]]delete[[/t]]" height="16" width="16" />
        </td>
        [[/if]]

  
    </tr>


    [[ /foreach]]
    </tbody>
</table>

    <br />    
[[/if]]    
    
[[ hook name="dashboard-before-pages" ]]

[[ if is_array($pages) && count($pages)>0 ]]
<table class='formclass' cellspacing='0'  style='border: 1px solid #CCC; width: 100%; padding-top: 0;'>
    <tbody>
    <tr>
         <th class="sectiontitle"><a href="index.php?page=pagesoverview"><img src="pics/page_white_go.png" alt="[[t]]overview of pages[[/t]]" title="[[t]]overview of pages[[/t]]" /></a>[[t]]The latest pages[[/t]]</th>
        <th>[[t]]Author[[/t]]</th>
        <th>[[t]]Chapter[[/t]]</th>
        <th>[[t]]Date[[/t]]</th>
        <th colspan="2" style="text-align:right"><a href="index.php?page=pagesoverview">[[t]]more[[/t]] &raquo;</a></th>
    </tr>

    [[ foreach from=$pages key=key item=item ]]
        <tr class='[[ cycle values="even, odd"]]'>

    [[ if $item.editable==1 ]]
        <td class='dashboardclip2'><div class="clip" style="width: 200px;">
            &#8470; [[ $item.uid ]]. <strong><a href="index.php?page=page&amp;uid=[[$item.uid]]" title="[[t]]Edit this page[[/t]]"> [[ $item.title|strip_tags|trimlen:25]]</a></strong> - [[ $item.excerpt|hyphenize ]]
        </div></td>
        <td class="tabular">[[assign var=username value=$item.user]][[ if $users.$username != "" ]][[ $users.$username|trimlen:22 ]][[ else ]][[ $item.user|trimlen:22 ]][[/if]]</td>
        <td class="tabular">[[ $item.chaptername|trimlen:24 ]]</td>
        <td class="tabular">[[ if $item.status=="publish"]][[ date date=$item.date format="%day%-%month%-'%ye% %hour24%:%minute%" ]][[else]]-[[/if]] </td>
        <td width="1">
            <a href="index.php?page=page&amp;uid=[[$item.uid]]"><img src="pics/page_edit.png" alt="[[t]]edit[[/t]]" height="16" width="16" /></a>
        </td>
        <td width="1">
            <a href="#" onclick="return confirmme('index.php?page=pagesoverview&amp;delpage=[[ $item.uid ]]', '[[t escape=js ]]Are you sure you wish to delete this page?[[/t]]');"><img src="pics/page_delete.png"  alt="[[t]]delete[[/t]]"  height="16" width="16" /></a>
        </td>
    [[ else ]]
        <td class='dashboardclip2'><div class="clip" style="width: 200px;">&#8470; [[ $item.uid ]]. <strong>[[ $item.title|strip_tags|trimlen:25]]</strong> [[ $item.excerpt|hyphenize ]]</div></td>
        <td class="tabular">[[assign var=username value=$item.user]][[ if $users.$username != "" ]][[ $users.$username|trimlen:22 ]][[ else ]][[ $item.user|trimlen:22 ]][[/if]]</td>
        <td class="tabular">[[ $item.chaptername|trimlen:24 ]]</td>
        <td class="tabular">[[ date date=$item.date format="%day%-%month%-'%ye% %hour24%:%minute%" ]] </td>
        <td width="1"><img src="pics/page_edit_dim.png" alt="-" height="16" width="16" /></td>
        <td width="1"><img src="pics/page_delete_dim.png" alt="-" height="16" width="16" /></td>        
    [[ /if ]]
    </tr>


    [[ /foreach]]
    </tbody>
</table>
[[/if]]


    </div>

    <div class="homerightcolumn">

        [[ hook name="dashboard-before-warnings" ]]
        [[ if is_array($warnings) && count($warnings)>0 ]]
            [[ foreach from=$warnings key=key item=item ]]
            <div class="warning">
            	<h2 class="sectiontitle"><img src="pics/error.png" alt="" />[[t]]Warning![[/t]]</h2>
            
                [[ $item ]]
            </div>
            [[ /foreach ]]
        [[ /if ]]

        [[ hook name="dashboard-before-news" ]]

        <div class="news">
        <h2 class="sectiontitle"><img src="pics/newspaper.png" alt="" />[[t]]PivotX News[[/t]]<span><a href="http://pivotx.net">[[t]]Visit the website[[/t]] &raquo;</a></span></h2>
            <div id="newsholder">
                <img src='pics/loadingAnimation.gif' alt='Loading...' width='208' height='13' style='margin: 20px;' />
            </div>
        </div>


        [[ hook name="dashboard-before-events" ]]

        [[ if is_array($events) && count($events)>0 ]]
            <div class="news" style="margin-top: 16px;">
            
             <h2 class="sectiontitle"><img src="pics/page_white.png" alt="" />[[t]]The latest events[[/t]]</h2>

            [[ foreach from=$events key=key item=item ]]
                <p class="events">[[$item]]</p>
                [[ if $key==5 ]]
                <p id='eventsmoreclick'><a onclick='moreEvents();'>[[t]]Show more items[[/t]]</a></p><div id='eventsmore'>
                [[/if]]
            [[ /foreach ]]

            [[ if $key>=5]]
                </div>
            [[/if]]

            </div>
        [[ /if ]]
            

        [[ hook name="dashboard-before-forumposts" ]]

        [[ if not $config.hide_forumposts ]]
        <div class="news" style="margin-top: 16px;">
        
        <h2 class="sectiontitle"><img src="pics/users_forum.png" alt="" />[[t]]The latest Forum posts[[/t]]<span><a href="http://forum.pivotx.net">[[t]]Visit the website[[/t]] &raquo;</a></span></h2>

            <div id="forumpostholder">
                &nbsp;
            </div>
        </div>
        [[ /if ]]

    </div>


    <div class="cleaner">&nbsp;</div>

</div>

[[ hook name="dashboard-bottom" ]]

<script type="text/javascript">
//<![CDATA[

jQuery(function($) {
    // Fetch the latest news..
    getPivotxNews();
    
    // Check if we have a session cookie.  
    if (!cookieEnabled()) {
        var html = "<div class='warning'><h2><img src='pics/error.png' alt='' height='16' width='16' style='border-width: 0px; margin-bottom: -3px;' />";
        html += "<strong>[[t]]Warning![[/t]]</strong></h2>";
        html += "<p>[[t]]PivotX couldn't set the session properly. Try logging out, and logging on again. You could also try clearing your browser's cache, and make sure no software on your computer is interfering with the cookies.[[/t]]</p>";
        html += "<p>[[t]]If the problem persists, ask for help on the forum.[[/t]]</p>";
        html += "</div>";
        
        $('.homerightcolumn').prepend(html);
        
    }
    
});

//]]>
</script>


[[include file="inc_footer.tpl" ]]
