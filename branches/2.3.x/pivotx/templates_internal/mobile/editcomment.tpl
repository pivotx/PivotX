[[include file="mobile/inc_header.tpl" ]]

<form id="form1" class="formclass" name="form1" method="post" action="index.php?page=m_editcomment&amp;uid=[[ $comment.entry_uid ]]&amp;key=[[ $comment.uid ]]" >

<input type="hidden" name="pivotxsession" id="pivotxsession" value="[[ $pivotxsession ]]" />
<input type="hidden" name="entry_uid" id="entry_uid" value="[[ $comment.entry_uid ]]" />
<input type="hidden" name="uid" id="uid" value="[[ $comment.uid ]]" />

<p>
    <label for="name">[[t]]Name[[/t]]:</label><br />
    <input id="name" name="name" type="text" value="[[ $comment.name|escape|trim ]]" />
</p>

<p>
    <label for="email">[[t]]Email[[/t]]:</label><br />
    <input id="email" name="email" type="text" value="[[ $comment.email|escape|trim ]]" />
</p>
<p>
    <label for="url">[[t]]URL[[/t]]:</label><br />
    <input id="url" name="url" type="text" value="[[ $comment.url|escape|trim ]]" />
</p>
                
                
<p>
    <label for="comment">[[t]]Comment[[/t]]:</label><br />
    <textarea name="comment" id="comment" class="Editor" cols='50' rows='6'>[[ $comment.comment|trim ]]</textarea>

</p>

       

<p>
    <label for="ip">[[t]]IP address[[/t]]:</label><br />
    <input id="ip" name="ip" type="text" value="[[ $comment.ip|escape|trim ]]" />
</p>       

<p>
    <label for="useragent">[[t]]User agent[[/t]]:</label><br />
    <input id="useragent" name="useragent" type="text" value="[[ $comment.useragent|escape|trim ]]" />
</p>       


<table style="width: 300px;">
    <tr>
        <td><label for="moderate">[[t]]In moderation[[/t]]:</label></td>
        <td><input type="checkbox" id="moderate" name="moderate" value="1" [[ if $comment.moderate==1 ]]checked="checked"[[/if]] /></td>
    </tr>

    
</table>
            
             
                    
                         <hr size="1" noshade="noshade" />
                    

    <p >
        <button type="submit" class="large awesome">
            [[t]]Post Comment[[/t]]
        </button>


        
    </p>

</form>

[[include file="mobile/inc_footer.tpl" ]]
