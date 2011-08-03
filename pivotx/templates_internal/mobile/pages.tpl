[[include file="mobile/inc_header.tpl" ]]




[[ if is_array($pages) && count($pages)>0 ]]


    [[ foreach from=$pages key=key item=item ]]

    [[ if $item.editable==1 ]]
        <div class="dashboardlist [[ cycle values="even, odd"]]">
            <p class="two-lines">
                <strong>&raquo; <a href="index.php?page=m_editpage&amp;uid=[[$item.uid]]" title="edit this page">[[ $item.title|trimlen:24]]
                [[if $item.title=="" ]][[t]]No title[[/t]][[/if]]</a></strong> -
                [[ $item.excerpt|trimlen:100:""|hyphenize ]]
            </p>
            <p class="meta">
            [[t]]Chapter[[/t]]: [[ if $item.chaptername!="" ]][[ $item.chaptername|truncate:20 ]][[else]]<em>[[t]]none[[/t]]</em>[[/if]] -
            [[t]]Template[[/t]]: [[ if $item.template!="-"]][[ $item.template|truncate:25 ]][[else]]<em>([[t]]default template[[/t]])</em>[[/if]]<br />
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
