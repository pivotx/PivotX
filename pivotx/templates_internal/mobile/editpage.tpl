[[include file="mobile/inc_header.tpl" ]]


<form id="form1" class="formclass" name="form1" method="post" action="index.php?page=m_editpage&amp;uid=[[ $page.uid ]]" >

<input type="hidden" name="pivotxsession" id="pivotxsession" value="[[ $pivotxsession ]]" />
<input type="hidden" name="code" id="code" value="[[ $page.uid ]]" />
<input type="hidden" name="date1" id="date1" value="[[ date date=$page.date format='%day%-%month%-%year%' ]]" />
<input type="hidden" name="date2" id="date2" value="[[ date date=$page.date format='%hour24%-%minute%' ]]" />

<p>
    <label for="title">[[t]]Title[[/t]]:</label><br />
    <input id="title" name="title" type="text" value="[[ $page.title|escape|trim ]]" />
</p>
   
   
<p>
    <label for='template'>[[t]]Chapter[[/t]]:</label><br />
    <select name="chapter" style="width: 120px;">
    [[ foreach from=$chapters key=key item=chapter ]]
        [[ if $chapter.chaptername!="" ]]
            <option value='[[ $key ]]' [[ if $key==$page.chapter ]]selected="selected"[[/if]]>
                [[ $chapter.chaptername ]]
            </option>
        [[ /if ]]
    [[ /foreach ]]
    </select>    
</p>                    
         
                
<div id='edit-simpletabs'>
<span id='simpletab1' class='first'>[[t]]Introduction[[/t]]</span><span id='simpletab2'>[[t]]Body[[/t]]</span>
</div>

<div id='edit-simpletab1' class='edit-simpletab'>
    <textarea name="introduction" id="introduction" class="Editor" cols='50' rows='6'>[[ $page.introduction|trim ]]</textarea>

</div>

<div id='edit-simpletab2' class='edit-simpletab'>
                    
    <textarea name="body" id="body" class="Editor" cols='50' rows='7'>[[ $page.body|trim ]]</textarea>

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
    <input name="keywords" id="keywords" type="text" value="[[ $page.keywords|escape ]]" /><br />
    <small>[[t]]Separate Tags with spaces. E.g., movies jedi starwars (not 'star wars')[[/t]]</small>
</p>


<p>
    <label for='categories'>[[t]]Template[[/t]]:</label><br />
    <select name="template">
        [[ foreach from=$templates key=key item=template ]]
            <option value='[[ $key ]]' [[ if $template==$page.template ]]selected="selected"[[/if]]>
                [[ $template ]]
            </option>
        [[ /foreach ]]
    </select>
</p>

<p>
    <label for='status'>[[t]]Post Status[[/t]]:</label><br />
    <select name="status" style="min-width: 200px">
        <option value="publish" [[ if $page.status=="publish" ]]selected="selected"[[/if]] >[[t]]Publish[[/t]]</option>
        <option value="timed" [[ if $page.status=="timed" ]]selected="selected"[[/if]] >[[t]]Timed Publish[[/t]]</option>
        <option value="hold" [[ if $page.status=="hold" ]]selected="selected"[[/if]] >[[t]]Hold[[/t]]</option>
    </select>
</p>                    
                
<p>
    <label for='publish_date1'>[[t]]Publish on[[/t]]:</label><br />
    <input name="publish_date1" type="text" style="width: 100px;" id="publish_date1"
    value="[[ date date=$page.publish_date format='%day%-%month%-%year%' ]]" size="15" />
    <input name="publish_date2" type="text" style="width: 80px" id="publish_date2"
    value="[[ date date=$page.publish_date format='%hour24%-%minute%' ]]" size="7" />
</p>
            
                   
                    
                         <hr size="1" noshade="noshade" />
                    

    <p class="buttons">
        <button type="submit" class="large awesome">
            [[t]]Post Page[[/t]]
        </button>

[[*            <button type="button" onclick="openEntryPreview();">
            <img src="./pics/zoom.png" alt=""/>
            [[t]]Preview[[/t]]
        </button>
*]]

        
    </p>

</form>

[[include file="mobile/inc_footer.tpl" ]]
