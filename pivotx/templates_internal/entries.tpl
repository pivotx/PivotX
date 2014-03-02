[[* default entrieslist page template *]]
[[include file="inc_header.tpl" ]]
<div id="entriesgrid">
[[adminentrylist]]

[[hook name="entry-filter-before" value=$adminentryfilter]]
[[hook name="entry-pager-before" value=$adminentrypager]]
[[hook name="entry-list-before" value=$adminentrylist]]
[[assign var="adminentryextra" value="0"]]

[[include file="inc_adminentryfilter.tpl"]]
[[include file="inc_adminentrypager.tpl"]]

[[if $adminentrylist]]
<form name="entriesgridform" id="entriesgridform" action="" method="post">
<table class='formclass' cellspacing='0'>
    <tr class="sort">
        
        
        <th colspan="3"[[if $adminentrypager.orderby=='status']] class="active"[[/if]] style="text-align: right">
            <a href="?page=[[$adminentrytype.listpage]]&amp;sort=status&amp;reverse=1"[[if $adminentrypager.order=='asc']] class="on"[[/if]]>&uArr;</a>
             [[if $adminentrypager.orderby=='status']]<strong>[[t]]Status[[/t]]</strong>[[else]][[t]]Status[[/t]][[/if]]
            <a href="?page=[[$adminentrytype.listpage]]&amp;sort=status"[[if $adminentrypager.order!='asc']] class="on"[[/if]]>&dArr;</a>
        </th>
        <th[[if $adminentrypager.orderby=='title']] class="active"[[/if]] width="300" >
            <a href="?page=[[$adminentrytype.listpage]]&amp;sort=title&amp;reverse=1"[[if $adminentrypager.order=='asc']] class="on"[[/if]]>&uArr;</a>
             [[if $adminentrypager.orderby=='title']]<strong>[[t]]Title[[/t]]</strong>[[else]][[t]]Title[[/t]][[/if]]
            <a href="?page=[[$adminentrytype.listpage]]&amp;sort=title"[[if $adminentrypager.order!='asc']] class="on"[[/if]]>&dArr;</a>
        </th>
        <th width="50">[[t]]Category[[/t]]
        <!-- <a href="?page=entries&amp;sort=category[[if $adminentrypager.orderby=='category']]&amp;reverse=1[[/if]]">category</a> -->
        </th>
        <th[[if $adminentrypager.orderby=='user']] class="active"[[/if]] width="80">
            <a href="?page=[[$adminentrytype.listpage]]&amp;sort=user&amp;reverse=1"[[if $adminentrypager.order=='asc']] class="on"[[/if]]>&uArr;</a>
             [[if $adminentrypager.orderby=='user']]<strong>[[t]]Author[[/t]]</strong>[[else]][[t]]Author[[/t]][[/if]]
            <a href="?page=[[$adminentrytype.listpage]]&amp;sort=user"[[if $adminentrypager.order!='asc']] class="on"[[/if]]>&dArr;</a>
        </th>
        <th[[if $adminentrypager.orderby=='date']] class="active"[[/if]] width="80">
            <a href="?page=[[$adminentrytype.listpage]]&amp;sort=date&amp;reverse=1"[[if $adminentrypager.order=='asc']] class="on"[[/if]]>&uArr;</a>
             [[if $adminentrypager.orderby=='date']]<strong>[[t]]Date[[/t]]</strong>[[else]][[t]]Date[[/t]][[/if]]
            <a href="?page=[[$adminentrytype.listpage]]&amp;sort=date"[[if $adminentrypager.order!='asc']] class="on"[[/if]]>&dArr;</a>
        </th>
        <th width="20"><img src='pics/comment.png' width='16' height='14' alt='#[[t]]c[[/t]]' /></th>
        <th width="20"><img src='pics/trackback.png' width='16' height='14' alt='#[[t]]t[[/t]]' /></th>
        <th colspan="2">&nbsp;</th>
    </tr>
[[foreach from=$adminentrylist item="entry" name="entriesgrid"]]
    <tr id="row-[[$entry.uid]]" class="[[if $smarty.foreach.entriesgrid.index%2]]even[[else]]odd[[/if]]">
        <td width="1">
            <input type="checkbox" onclick="rowSelect([[$entry.uid]])" id="check-[[$entry.uid]]" name="check[ [[$entry.uid]] ]" />
        </td>
        <td class="tabular" width="1">
            <span><small>&#8470; [[$entry.uid]].</small></span>
        </td>
              
        <td width="1" class="nowrap">
            [[if $entry.status=='publish']]
            <a href="[[$entry.link]]" [[if !$config.front_end_links_same_window]]target="_blank"[[/if]] class="front_end">[[t]]Published[[/t]]</a>
            [[elseif $entry.status=='timed']]
            <span class="timedpublishindicator">[[t]]Timed[[/t]]</span>
            [[else]]
            <span class="unpublishedindicator">[[t]]Held[[/t]]</span>
            [[/if]]
        </td>
        <td class="entriesclip">
            <div class="clip extraclip" style='width: 300px;'>
            [[ if $entry.editable ]]
                <a title="[[t]]Edit this entry[[/t]]" href="index.php?page=[[$adminentrytype.editpage]]&amp;uid=[[$entry.uid]]"><strong>[[ $entry.title|strip_tags|truncate:35 ]]</strong></a>
            [[else]]
                <strong>[[$entry.title|strip_tags|truncate:35]]</strong>
            [[/if]]
            [[$entry.excerpt|truncate:100]]
            </div>
        </td>
        <td class="tabular" width="1">
            <span>[[if $entry.categorycount==1]]
            [[$entry.categorynames]]
            [[elseif $entry.categorycount>1]]
        <acronym title="[[$entry.categorynames]]">[[$entry.categorycount]] [[t]]categories[[/t]]</acronym>
        [[else]]
            0 categories
            [[/if]]</span>
        </td>
        <td class="tabular" width="1">
            [[if $entry.author]]
                [[$entry.author]]
            [[else]]
                [[$entry.user]]
            [[/if]]
        </td>
        <td class="tabular" width="1">
            <span>[[if $entry.status=='publish']]
            [[date format="%day%-%month%-'%ye% %hour24%:%minute%" date=$entry.date]]
            [[elseif $entry.status=='timed']]
            <span class="timedpublishindicator">[[date format="%day%-%month%-'%ye% %hour24%:%minute%" date=$entry.publish_date]]</span>
            [[else]]
            -
            [[/if]]</span>    
        </td>
        <td class="tabular">[[if $entry.commeditable && $entry.commcount>0]]
        <a href='index.php?page=comments&amp;uid=[[$entry.uid]]' title="[[$entry.commnames]]">[[$entry.commcount]][[t]]c[[/t]]</a>
        [[else]][[$entry.commcount]][[t]]c[[/t]][[/if]]</td>
        <td class="tabular">[[if $entry.trackeditable && $entry.trackcount>0]]
        <a href='index.php?page=trackbacks&amp;uid=[[$entry.uid]]'>[[$entry.trackcount]][[t]]t[[/t]]</a>
        [[else]][[$entry.trackcount]][[t]]t[[/t]][[/if]]</td>
        
        [[if $entry.editable]]
        <td width="1">
            <a href="index.php?page=[[$adminentrytype.editpage]]&amp;uid=[[$entry.uid]]"><img height="16" width="16" alt="Edit" src="pics/page_edit.png" /></a>
        </td>
        <td width="1">
            <a onclick="return confirmme('index.php?page=entries&amp;del=[[$entry.uid]]', 'Are your sure you wish to delete this entry?');" href="#"><img height="16" width="16" alt="Delete" src="pics/page_delete.png" /></a>
        </td>
        [[else]]
        <td width="1">
            <img src='pics/page_edit_dim.png' width='16' height='16' alt='-' />
        </td>
        <td width="1">
            <img src='pics/page_delete_dim.png' width='16' height='16' alt='-' />
        </td>
        [[/if]]
    </tr>
[[/foreach]]
</table>

<table id="quickstatusform" class='formclass' cellspacing='0'>
	<tr>
		<th colspan="11">
            <input type="hidden" name="page" value="[[$adminentrytype.listpage]]" />
            <input type="hidden" name="pivotxsession" value="[[$adminentrycsrf]]"/>
			<img height="14" border="0" width="29" alt="" src="pics/arrow_ltr.gif" />
			<a onclick="rowSelectAll(); return false;" href="#">[[t]]Check All[[/t]]</a> /
			<a onclick="rowSelectNone(); return false;" href="#">[[t]]Uncheck All[[/t]]</a> - [[t]]With the checked entries, do:[[/t]]
			<select class="input" id="entriesaction" name="action">
				<option selected="selected" value="">[[t]]- select an option -[[/t]]</option>
				<option value="publish">[[t]]Set Status to "publish"[[/t]]</option>
				<option value="hold">[[t]]Set Status to "hold"[[/t]]</option>
				<option value="delete">[[t]]Delete them[[/t]]</option>
			</select>
            <button type="submit" onclick="return entriesActionSubmit();" class="positive" value="Go!">[[t]]Go![[/t]]</button>
		</th>
	</tr>
</table>
</form>
[[include file="inc_adminentrypager.tpl" extraclass="entriesnavbottom"]]
[[else]]
<form name="entriesgridform" id="entriesgridform" action="" method="post">
<table class='formclass' cellspacing='0'>
    <tr>
        <th>&nbsp;</th>
    </tr>
    <tr>
        <td>
            <p class="error">
                [[t]]No entries found.[[/t]]
            </p>
            [[if $adminentrysearch || $adminentryfilter]]
            <form name="entriesFilterform" id="entriesFilterform" action="" method="post">
                <input type="hidden" name="page" value="[[$adminentrytype.listpage]]" />
                <input type="hidden" name="pivotxsession" value="[[$adminentrycsrf]]"/>
                <p class="helpful">
                    [[t]]Your search or filter settings gave no results.[[/t]]<br />
                [[t]]You may reset the filters with the following link:[[/t]]
                <button type="submit" name="clear" value="clear" class="button" title="[[t]]Reset search[[/t]]">[[t]]Show all[[/t]]</button>
                </p>
            </form>
            [[/if]]
            <p class="error">
                [[t]]You can also create a[[/t]] <a href="?page=[[$adminentrytype.addpage]]">[[t]]New Entry[[/t]]</a>.
            </p>
        </td>
    </tr>
</table>
</form>
[[/if]]

[[include file="inc_footer.tpl" ]]
