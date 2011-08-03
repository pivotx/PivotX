<div id="pagesmenu">
    <form name="chapterjumpform" id="chapterjumpform" action="" method="post">
        <fieldset>
            <legend>[[t]]Jump to:[[/t]]</legend>
			<select id="pagesjumplist">
[[ foreach from=$pages key=key item=item ]]
	[[ if $item.chaptername!="" ]]
				<option value="#chapter-[[$key]]">[[ $item.chaptername ]]</option>
	[[/if]]
[[/foreach]]
				<option value="#chapter-orphans">[[t]]Orphaned Pages[[/t]]</option>
            </select>
        </fieldset>
    </form>

    <div class="buttons">
	    <a href="index.php?page=chapter&amp" title="[[t]]Add a Chapter[[/t]]" class="dialog chapter">
	        <img src="pics/book_add.png" alt="" /> [[t]]Add a Chapter[[/t]] </a>

	    [[ button link="index.php?page=page&chapter=$key" icon="page_white_add.png" ]] [[t]]Write a new Page[[/t]] [[/button]]
	</div>
</div>
