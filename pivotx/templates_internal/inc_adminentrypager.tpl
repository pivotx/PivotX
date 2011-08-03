[[if $adminentrypager.numpages>1]]
<table class="entriesnav [[$extraclass]]" cellspacing='0'><tr>
    <td class="entriespager ep-first">
        <ul class="ep">
            [[if $adminentrypager.currentpage>1]]<li><a href="index.php?page=[[$adminentrytype.listpage]]&amp;go=first">&laquo; [[t]]first[[/t]]</a></li>[[/if]]
            [[if $adminentrypager.currentpage>1]]<li><a href="index.php?page=[[$adminentrytype.listpage]]&amp;go=[[$adminentrypager.currentpage-1]]">&lsaquo; [[t]]previous[[/t]]</a></li>[[/if]]
        </ul>
    </td>
	
    <td class="entriespager">
        <ul class="ep">
        [[section name=pg loop=$adminentrypager.numpages]]
            [[if $smarty.section.pg.iteration==$adminentrypager.currentpage-5]]
            <li><span>...</span></li>[[/if]]
            [[if $smarty.section.pg.iteration>$adminentrypager.currentpage-5&&$smarty.section.pg.iteration<$adminentrypager.currentpage+5]]
            [[if $adminentrypager.currentpage==$smarty.section.pg.iteration]]
            <li><span>[[$smarty.section.pg.iteration]]</span></li>
            [[else]]
            <li><a href="index.php?page=[[$adminentrytype.listpage]]&amp;go=[[$smarty.section.pg.iteration]]">[[$smarty.section.pg.iteration]]</a></li>
            [[/if]]
            [[/if]]
            [[if $smarty.section.pg.iteration==$adminentrypager.currentpage+5]]
            <li><span>...</span></li>
            <li><a href="index.php?page=[[$adminentrytype.listpage]]&amp;go=[[$adminentrypager.numpages]]">[[$adminentrypager.numpages]]</a></li>
			[[/if]]
        [[/section]]
        </ul>
	</td>

    <td class="entriespager ep-last">
        <ul class="ep">
            [[if $adminentrypager.currentpage<$adminentrypager.numpages]]<li><a href="index.php?page=[[$adminentrytype.listpage]]&amp;go=[[$adminentrypager.currentpage+1]]">[[t]]next[[/t]] &rsaquo;</a></li>[[/if]]
            [[if $adminentrypager.currentpage<$adminentrypager.numpages]]<li><a href="index.php?page=[[$adminentrytype.listpage]]&amp;go=last">[[t]]last[[/t]] &raquo;</a></li>[[/if]]
        </ul>
    </td>
</tr></table>
[[else]]
<table class="entriesnav [[$extraclass]]" cellspacing='0'><tr><td>&nbsp;</td></tr></table>
[[/if]]


