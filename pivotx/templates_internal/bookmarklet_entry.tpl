[[ include file="inc_bookmarklet_header.tpl" ]]


    <form id="form1" name="form1" method="post" action="index.php?page=bookmarklet" >

<input name="publish_date1" type="hidden" value="[[ date date=$entry.publish_date format='%month%-%day%-%year%' ]]" />
<input name="publish_date2" type="hidden" value="[[ date date=$entry.publish_date format='%hour24%-%minute%' ]]"  />
<input type="hidden" name="pivotxsession" id="pivotxsession" value="[[ $pivotxsession ]]" />

            <table border="0" cellspacing="0" class="formclass bookmarklet">
                <tr>
                    <td width="90"><label>[[t]]Title[[/t]]:</label></td>
                    <td width=""><input id="title" name="title" type="text" value="[[ $entry.title|escape ]]" class="bookmarklet" />
                    
                    </td>
                </tr>
                    


            </table>

            <textarea name="introduction" id="introduction" class="bookmarklet" rows='5'
                cols='50'>[[ if $user.text_processing==5 ]][[ $entry.introduction|escape:html ]][[ else ]][[ $entry.introduction ]][[/if]]</textarea>
    
    
            [[* Here we select which editor to use *]]
            [[ if $user.text_processing==5 ]]
                [[ include file="inc_init_bookmarklet_tinymce.tpl" ]]
            [[ /if ]]

            <table border="0" cellspacing="0" class="formclass bookmarklet">
                
                <tr>
                    <td width="90">[[t]]Tags[[/t]]:</td>
                    <td colspan="4">
                        <input name="keywords" type="text" value="[[ $entry.keywords|escape ]]" />
                       
                    </td>
                </tr>                
                
        <tr>
                        <td valign="top">[[t]]Category[[/t]]:</td>
                        <td>
                            <select name="categories[]" style="width: 135px;">
                            <option value="">[[t]](none)[[/t]]</option>
                            [[ foreach from=$categories key=key item=category ]]
                                <option value='[[ $category.name ]]' [[ if in_array($category.name, $entry.category) ]]selected="selected"[[/if]]>[[ $category.display ]]</option>
                            [[ /foreach ]]
                        </select>
                    </td>
                    <td> &nbsp; </td>
                    <td class="nowrap">[[t]]Post Status[[/t]]:</td>
                    <td><select name="status" style="width: 98px;">
                        <option value="publish" [[ if $entry.status=="publish" ]]selected="selected"[[/if]] >[[t]]Publish[[/t]]</option>
                        <option value="timed" [[ if $entry.status=="timed" ]]selected="selected"[[/if]] >[[t]]Timed Publish[[/t]]</option>
                        <option value="hold" [[ if $entry.status=="hold" ]]selected="selected"[[/if]] >[[t]]Hold[[/t]]</option>
                    </select></td>
                </tr>

          
                
          
                
                

        <tr><td colspan="2" class="buttons">
            <button type="submit" class="positive">
                <img src="./pics/tick.png" alt=""/>
                [[t]]Post Entry[[/t]]
            </button>
            </td>

        </tr>
        

      </table>

    </form>

</body>
</html>
