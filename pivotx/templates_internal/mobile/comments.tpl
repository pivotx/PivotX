[[include file="mobile/inc_header.tpl" ]]


[[ foreach from=$comments key=key item=item ]]

    [[if $item.allowedit]]
    <div class="dashboardlist [[ cycle values="even, odd"]][[ if $item.moderate ]] moderate[[/if]][[ if $item.blocked ]] blocked[[/if]]">
        <a href="index.php?page=m_moderatecomment&amp;uid=[[ $item.entry_uid ]]&amp;key=[[ $item.uid ]]" class="moderatelink"><img src="pics/lightbulb[[ if $item.moderate ]]_off[[/if]].png" alt="moderate" /></a>
        <p class="four-lines">
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
        <span class="moderatelink"><img src="pics/lightbulb[[ if $item.moderate ]]_off[[/if]].png" alt="moderate" /></span>
        <p class="four-lines">
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





[[include file="mobile/inc_footer.tpl" ]]
