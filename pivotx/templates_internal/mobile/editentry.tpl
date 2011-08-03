[[include file="mobile/inc_header.tpl" ]]


<form id="form1" class="formclass" name="form1" method="post" action="index.php?page=m_editentry&amp;uid=[[ $entry.uid ]]" >

<input type="hidden" name="pivotxsession" id="pivotxsession" value="[[ $pivotxsession ]]" />
<input type="hidden" name="code" id="code" value="[[ $entry.uid ]]" />
<input type="hidden" name="date1" id="date1" value="[[ date date=$entry.date format='%day%-%month%-%year%' ]]" />
<input type="hidden" name="date2" id="date2" value="[[ date date=$entry.date format='%hour24%-%minute%' ]]" />


<p>
    <label for="title">[[t]]Title[[/t]]:</label><br />
    <input id="title" name="title" type="text" value="[[ $entry.title|escape|trim ]]" />
</p>
                
                
<div id='edit-simpletabs'>
<span id='simpletab1' class='first'>[[t]]Introduction[[/t]]</span><span id='simpletab2'>[[t]]Body[[/t]]</span>
</div>

<div id='edit-simpletab1' class='edit-simpletab'>
    <textarea name="introduction" id="introduction" class="Editor" cols='50' rows='6'>[[ $entry.introduction|trim ]]</textarea>

</div>

<div id='edit-simpletab2' class='edit-simpletab'>
                    
    <textarea name="body" id="body" class="Editor" cols='50' rows='7'>[[ $entry.body|trim ]]</textarea>

</div>
    <p class="buttons">
        <button type="button" class="medium gray awesome button strong"><strong>*B*</strong></button>
        <button type="button" class="medium gray awesome button em"><em>_EM_</em></button>
        <button type="button" class="medium gray awesome button link"><span style="color: #00F; text-decoration:underline;">link</a></button>
        <button type="button" class="medium gray awesome button h1">H1</button>
        <button type="button" class="medium gray awesome button h2">H2</button>
        <button type="button" class="medium gray awesome button h3">H3</button>
    </p>
                    


<p>
    <label for='keywords'>[[t]]Keywords[[/t]] / [[t]]Tags[[/t]]:</label><br />
    <input name="keywords" id="keywords" type="text" value="[[ $entry.keywords|escape ]]" /><br />
    <small>[[t]]Separate Tags with spaces. E.g., movies jedi starwars (not 'star wars')[[/t]]</small>
</p>


<p>
    <label for='categories'>[[t]]Category[[/t]]:</label><br />

    <select name="categories[]" style="min-width: 200px">
    <option value="">[[t]](none)[[/t]]</option>
        [[ foreach from=$categories key=key item=category ]]
        <option value='[[ $category.name ]]' [[ if in_array($category.name, $entry.category) ]]selected="selected"[[/if]]>[[ $category.display ]]</option>
        [[ /foreach ]]
    </select>
</p>

<p>
    <label for='status'>[[t]]Post Status[[/t]]:</label><br />
    <select name="status" style="min-width: 200px">
        <option value="publish" [[ if $entry.status=="publish" ]]selected="selected"[[/if]] >[[t]]Publish[[/t]]</option>
        <option value="timed" [[ if $entry.status=="timed" ]]selected="selected"[[/if]] >[[t]]Timed Publish[[/t]]</option>
        <option value="hold" [[ if $entry.status=="hold" ]]selected="selected"[[/if]] >[[t]]Hold[[/t]]</option>
    </select>
</p>                    
                
<p>
    <label for='publish_date1'>[[t]]Publish on[[/t]]:</label><br />
    <input name="publish_date1" type="text" style="width: 100px;" id="publish_date1"
    value="[[ date date=$entry.publish_date format='%day%-%month%-%year%' ]]" size="15" />
    <input name="publish_date2" type="text" style="width: 80px" id="publish_date2"
    value="[[ date date=$entry.publish_date format='%hour24%-%minute%' ]]" size="7" />
</p>
            
<p>
    <label>[[t]]Allow comments[[/t]]: </label> 
    <input name="allow_comments" type="radio" value="1" id="comm_yes" [[ if $entry.allow_comments==1 ]]checked="checked"[[/if]] />
    <label for="comm_yes">[[t]]Yes[[/t]]</label> &nbsp;
    <input name="allow_comments" type="radio" value="0" id="comm_no" [[ if $entry.allow_comments==0 ]]checked="checked"[[/if]] />
    <label for="comm_no">[[t]]No[[/t]]</label>
</p>
                            
                    
                         <hr size="1" noshade="noshade" />
                    

    <p class="buttons">
        <button type="submit" class="large awesome">
            [[t]]Post Entry[[/t]]
        </button>

[[*            <button type="button" onclick="openEntryPreview();">
            <img src="./pics/zoom.png" alt=""/>
            [[t]]Preview[[/t]]
        </button>
*]]

        
    </p>

</form>

[[include file="mobile/inc_footer.tpl" ]]
