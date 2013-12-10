[[include file="mobile/inc_header.tpl" ]]

[[ hook name="mobile-dashboard-before-comments" ]]
    
[[ if count($comments)>0 ]]
<h2><span><a href="index.php?page=m_comments">[[t]]more[[/t]] &raquo;</a></span><img src="pics/star.png" alt="" height="16" width="16" style="border-width: 0px; margin-bottom: -2px;" />
        <strong>[[t]]The latest comments[[/t]]</strong></h2>

    [[ foreach from=$comments key=key item=item ]]

        [[if $item.allowedit]]
        <div class="dashboardlist [[ cycle values="even, odd"]][[ if $item.moderate ]] moderate[[/if]][[ if $item.blocked ]] blocked[[/if]]">
            <a href="index.php?page=m_moderatecomment&amp;uid=[[ $item.entry_uid ]]&amp;key=[[ $item.uid ]]" class="moderatelink"><img src="pics/lightbulb[[ if $item.moderate ]]_off[[/if]].png" /></a>
            <p class="three-lines">
                <strong>&raquo; <a href="index.php?page=m_editcomment&amp;uid=[[ $item.entry_uid ]]&amp;key=[[ $item.uid ]]" title="[[t]]Edit this comment[[/t]]">
            [[ $item.name|trimlen:34]][[if $item.name=="" ]][[t]]No name[[/t]][[/if]]</a></strong>
                - [[ $item.comment|strip_tags|trimlen:300:""|hyphenize ]]
            </p>
            <p class="meta">
            [[t]]On[[/t]]: [[ $item.title|trimlen:20]] -

            [[t]]Posted[[/t]]: [[ date date=$item.date format="%day%-%month%-'%ye% %hour24%:%minute%" ]]
            </p>
        </div>
        [[else]]
        <div class="dashboardlist [[ cycle values="even, odd"]][[ if $item.moderate ]] moderate[[/if]][[ if $item.blocked ]] blocked[[/if]]">
            <span class="moderatelink"><img src="pics/lightbulb[[ if $item.moderate ]]_off[[/if]].png" /></span>
            <p class="three-lines">
                <strong>&raquo; [[ $item.name|trimlen:34]][[if $item.name=="" ]][[t]]No name[[/t]][[/if]]</strong>
                - [[ $item.comment|strip_tags|trimlen:300:""|hyphenize ]]
            </p>
            <p class="meta">
            [[t]]On[[/t]]: [[ $item.title|trimlen:20]] -

            [[t]]Posted[[/t]]: [[ date date=$item.date format="%day%-%month%-'%ye% %hour24%:%minute%" ]]
            </p>
        </div>
        [[/if]]

    [[ /foreach]]

[[/if]]



[[ if is_array($entries) && count($entries)>0 ]]
<h2><span><a href="index.php?page=m_entries">[[t]]more[[/t]] &raquo;</a></span><img src="pics/star.png" alt="" height="16" width="16" style="border-width: 0px; margin-bottom: -2px;" />
        <strong>[[t]]The latest entries[[/t]]</strong></h2>

    [[ foreach from=$entries key=key item=item ]]

    [[ if $item.editable==1 ]]
        <div class="dashboardlist [[ cycle values="even, odd"]]">
            <p class="two-lines">
                <strong>&raquo; <a href="index.php?page=m_editentry&amp;uid=[[$item.uid]]" title="edit this entry">[[ $item.title|trimlen:24]]
                [[if $item.title=="" ]][[t]]No title[[/t]][[/if]]</a></strong> -
                [[ $item.excerpt|trimlen:100:""|hyphenize ]]
            </p>
            <p class="meta">
            [[t]]By[[/t]]: [[assign var=username value=$item.user]][[ if $users.$username != "" ]][[ $users.$username|trimlen:22 ]][[ else ]][[ $item.user|trimlen:22 ]][[/if]] -
            [[$item.commcount|intval]][[t]]c[[/t]] / [[$item.trackcount|intval]][[t]]t[[/t]] -
            [[t]]Posted[[/t]]: [[ date date=$item.date format="%day%-%month%-'%ye% %hour24%:%minute%" ]]
            </p>
        </div>
        
    [[ else]]
    
        <div class="dashboardlist [[ cycle values="even, odd"]]">
            <p class="two-lines">
                <strong>&raquo; [[ $item.title|trimlen:24]]
                [[if $item.title=="" ]][[t]]No title[[/t]][[/if]]</strong> -
                [[ $item.excerpt|trimlen:100:""|hyphenize ]]
            </p>
            <p class="meta">
            [[t]]By[[/t]]: [[assign var=username value=$item.user]][[ if $users.$username != "" ]][[ $users.$username|trimlen:22 ]][[ else ]][[ $item.user|trimlen:22 ]][[/if]] -
            [[$item.commcount|intval]][[t]]c[[/t]] / [[$item.trackcount|intval]][[t]]t[[/t]] -
            [[t]]Posted[[/t]]: [[ date date=$item.date format="%day%-%month%-'%ye% %hour24%:%minute%" ]]
            </p>
        </div>
    [[ /if ]]    


    [[ /foreach]]

[[/if]]
    
    



[[ if is_array($pages) && count($pages)>0 ]]
<h2><span><a href="index.php?page=m_pages">[[t]]more[[/t]] &raquo;</a></span><img src="pics/star.png" alt="" height="16" width="16" style="border-width: 0px; margin-bottom: -2px;" />
        <strong>[[t]]The latest pages[[/t]]</strong></h2>

    [[ foreach from=$pages key=key item=item ]]

    [[ if $item.editable==1 ]]
        <div class="dashboardlist [[ cycle values="even, odd"]]">
            <p class="two-lines">
                <strong>&raquo; <a href="index.php?page=m_editpage&amp;uid=[[$item.uid]]" title="edit this page">[[ $item.title|trimlen:24]]
                [[if $item.title=="" ]][[t]]No title[[/t]][[/if]]</a></strong> -
                [[ $item.excerpt|trimlen:100:""|hyphenize ]]
            </p>
            <p class="meta">
            [[t]]By[[/t]]: [[assign var=username value=$item.user]][[ if $users.$username != "" ]][[ $users.$username|trimlen:22 ]][[ else ]][[ $item.user|trimlen:22 ]][[/if]] -
            [[t]]Posted[[/t]]: [[ date date=$item.date format="%day%-%month%-'%ye% %hour24%:%minute%" ]]
            </p>
        </div>
        
    [[ else]]
    
        <div class="dashboardlist [[ cycle values="even, odd"]]">
            <p class="two-lines">
                <strong>&raquo; [[ $item.title|trimlen:24]]
                [[if $item.title=="" ]][[t]]No title[[/t]][[/if]]</strong> -
                [[ $item.excerpt|trimlen:100:""|hyphenize ]]
            </p>
            <p class="meta">
            [[t]]By[[/t]]: [[assign var=username value=$item.user]][[ if $users.$username != "" ]][[ $users.$username|trimlen:22 ]][[ else ]][[ $item.user|trimlen:22 ]][[/if]] -
            [[t]]Posted[[/t]]: [[ date date=$item.date format="%day%-%month%-'%ye% %hour24%:%minute%" ]]
            </p>
        </div>
    [[ /if ]]    


    [[ /foreach]]

[[/if]]



[[include file="mobile/inc_footer.tpl" ]]
