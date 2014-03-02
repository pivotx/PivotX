[[include file="inc_header.tpl" ]]

[[include file="inc_pagesbuttons.tpl"]]

[[ foreach from=$pages key=key item=item ]]

[[ if $item.chaptername!="" ]]
<a name="chapter-[[$key]]"></a><h1 style='margin-bottom: 0;'>
    [[ $item.chaptername ]] 
</h1>
[[ else ]]
<a name="chapter-orphans"></a><h1 style='margin-bottom: 0;'>
	[[t]]Orphaned Pages[[/t]] &nbsp; <em style='font-size: 12px;'>[[t]]These pages do not belong to any chapter, and will not show up in pagelists or menus.[[/t]]</em>
</h1>
[[ /if ]]


<table class='formclass tabular' cellspacing='0' style='margin: 8px 0px; width: 98%;'>
<tr>
<th colspan="4">[[ $item.description ]] [[if $item.sortorder]](&#8470; [[ $key ]] - [[t]]order[[/t]] [[ $item.sortorder ]])[[/if]]</th>
<th class="toplink"><a href="#logo" title="[[t]]back to top[[/t]]">&uArr;</a></th></tr>
[[ foreach from=$item.pages key=pagekey item=page name=loop ]]
    <tr class="[[cycle values='odd, even' name=$key ]]">
        <td class='tabular' width='1'>
            <span><small>&#8470; [[ $page.uid ]].</small></span></td>
		<td width='640' class='entriesclip'>
            <strong>
                [[ assign var=uid value=$page.uid ]]
                [[ if $page.editable ]]<a title="[[t]]Edit this page[[/t]]" href="index.php?page=page&amp;uid=[[$uid]]">[[ /if ]]
                [[ $page.title|strip_tags|truncate:35 ]][[ if $page.editable ]]</a>[[ /if ]]

            </strong>
            <span style="color:#555; font-size: 85%;">
                ([[$page.uri]] - [[t]]order[[/t]] [[ $page.sortorder ]]
                [[ if $page.status=="timed" ]]- <strong>[[t]]Timed Publish[[/t]]</strong>[[/if]]
                [[ if $page.status=="hold" ]]- <strong>[[t]]Hold[[/t]]</strong>[[/if]]
                [[ if $page.status=="publish" ]]- <a href="[[$page.link]]" [[if !$config.front_end_links_same_window]]target="_blank"[[/if]] class="front_end"><strong>[[t]]Published[[/t]]</strong></a>[[/if]]
                )</span><br />
            <div class="clip" style="width: 500px;">[[ $page.excerpt|hyphenize ]]</div>
        </td>
        <td width='170' class="nowrap">
            <span style="font-size: 90%;">
                [[ assign var=username value=$page.user ]]
                [[ if $users.$username != "" ]][[ $users.$username|trimtext:18]][[ else ]][[ $page.user|trimtext:18]][[/if]],
                [[ if $page.status=="publish" ]][[ date date=$page.date format="%day%-%month%-'%ye%" ]][[else]]-[[/if]]
            </span><br />
            <span style="font-size: 90%;">
                [[ if $page.template!="-"]]
                    [[ $page.template|truncate:35 ]]
                [[else]]
                    <em>([[t]]default template[[/t]])</em>
                [[/if]]
            </span>
        </td>
        <td width='70' align='right' class="buttons_small" style="padding: 2px 4px;">
            [[ if $page.editable ]]
                [[ button link="index.php?page=page&uid=$uid" icon="page_white_edit.png" ]] [[t]]Edit Page[[/t]] [[/button]]
            [[ else ]]
                &nbsp;
            [[ /if]]
        </td>

        <td width='70' align='right' class="buttons_small" style="padding: 2px 4px;">
            [[ if $page.editable ]]
            <a href="#" onclick="return confirmme('index.php?page=pagesoverview&amp;delpage=[[ $uid ]]', '[[t escape=js ]]Are you sure you wish to delete this Page?[[/t]]');"  class="negative">
               <img src="pics/page_white_delete.png" alt="" /> [[t]]Delete Page[[/t]] </a>
            </td>
            [[ /if ]]

    </tr>

[[/foreach]]



[[ if $item.chaptername!="" ]]
<tr><td colspan="5">
<p style="margin: 8px 0px;" class="buttons">
    [[ button link="index.php?page=page&chapter=$key" icon="page_white_add.png" ]] [[t]]Write a new Page[[/t]] [[/button]]

    [[ if $item.editable ]]
    <a href="index.php?page=chapter&amp;id=[[$key]]" title="[[t]]Edit Chapter[[/t]]" class="dialog chapter">
        <img src="pics/book_edit.png" alt="" /> [[t]]Edit Chapter[[/t]] </a>

     <a href="#" onclick="return confirmme('index.php?page=pagesoverview&amp;del=[[ $key ]]', '[[t escape=js ]]Are you sure you wish to delete this Chapter?[[/t]]');" class="negative">
        <img src="pics/book_delete.png" alt="" /> [[t]]Delete Chapter[[/t]] </a>
    [[ /if ]]
</p>
</td></tr>
[[ /if ]]




</table>
<br />
[[ /foreach ]]


[[ if $item.editable ]]
<p style="margin: 16px 0px">

    <span class="buttons">
    <a href="index.php?page=chapter&amp" title="[[t]]Add a Chapter[[/t]]" class="dialog chapter">
        <img src="pics/book_add.png" alt="" /> [[t]]Add a Chapter[[/t]] </a>
    </span>
</p>
<br />
<br />
[[ /if ]]

[[include file="inc_footer.tpl" ]]
