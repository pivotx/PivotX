<!--
 TODO: disable the search form when the filter form is active, and vice versa
-->

<div id="entriessearchfilter">
    <form name="entriesFilterform" id="entriesFilterform" action="" method="post">
        <input type="hidden" name="page" value="[[$adminentrytype.listpage]]" />
        <input type="hidden" name="pivotxsession" value="[[$adminentrycsrf]]"/>
        <fieldset>
            <legend>[[t]]Filter on:[[/t]]</legend>
            <select class="input" name="filterCategory" id="filterCategory">
                <option value="*">[[t]]Category[[/t]]</option>       
                <option disabled="disabled" value="*">----------</option>            
                <option value="*">[[t]]all categories[[/t]]</option>
                <option disabled="disabled" value="*">----------</option>        
                [[foreach from=$adminentryfilter.filtercategory.categories item="cat"]]
                <option value="[[$cat.name]]"
                    [[if $adminentryfilter.filtercategory.selected==$cat.name]]selected="selected"[[/if]]
                >[[$cat.display|trimtext:14]]</option>
                [[/foreach]]
            </select>
            [[if $adminentryfilter.filterauthor]]
            <select class="input" name="filterAuthor" id="filterAuthor">
                <option value="*">[[t]]Author[[/t]]</option> 
                <option disabled="disabled" value="*">----------</option>               
                <option value="*">[[t]]all authors[[/t]]</option>
                <option disabled="disabled" value="*">----------</option>      
                [[foreach from=$adminentryfilter.filterauthor.users item="author"]]
                <option value="[[$author.username]]"
                    [[if $adminentryfilter.filterauthor.selected==$author.username]]selected="selected"[[/if]]
                >[[$author.nickname|trimtext:14]]</option>
                [[/foreach]]
            </select>
            [[/if]]
            <select class="input" name="filterStatus" id="filterStatus">
                <option value="*">[[t]]Status[[/t]]</option>       
                <option disabled="disabled" value="*">----------</option>        
                <option value="*">[[t]]all statuses[[/t]]</option>
                <option disabled="disabled" value="*">----------</option>      
                [[foreach from=$adminentryfilter.filterstatus.statuses item="status"]]
                <option value="[[$status.status]]"
                    [[if $adminentryfilter.filterstatus.selected==$status.status]]selected="selected"[[/if]]
                >[[t]][[$status.displaystatus]][[/t]]</option>
                [[/foreach]]
            </select>
            <button type="submit" name="filtergo" class="positive" value="Go!">[[t]]Go![[/t]]</button>      
        </fieldset>
        <hr />
        <fieldset>
            <legend>[[t]]Search for:[[/t]]</legend>
            <input type="text" onfocus="this.select();" class="input" value="[[$adminentryfilter.filtersearch.search]]" name="search" id="search" />
            <button type="submit" name="searchgo" class="positive" value="Go!">[[t]]Go![[/t]]</button> 
        </fieldset>
        <hr />
        <fieldset>
            <button type="submit" name="clear" value="clear" class="button" title="[[t]]Reset search[[/t]]">[[t]]Show all[[/t]]</button>
        </fieldset>
    </form>
</div>
