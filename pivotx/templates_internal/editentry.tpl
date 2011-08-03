[[include file="inc_header.tpl" ]]

<div id="container">

    <form id="form1" name="form1" method="post" action="index.php?page=entry&amp;uid=[[ $entry.code ]]" >

    <input type="hidden" name="f_image" id="f_image" value="" />
    <input type="hidden" name="f_hasthumb" id="f_hasthumb" value="" />
    <input type="hidden" name="pivotxsession" id="pivotxsession" value="[[ $pivotxsession ]]" />
    <input type="hidden" name="postedfrom" id="postedfrom" value="" />

        <div class="leftcolumn">

            <table border="0" cellspacing="0" class="formclass" width="650">
                <tr>
                    <td width="140"><label><strong>[[t]]Title[[/t]]:</strong></label></td>
                    <td width="510"><input id="title" name="title" type="text" value="[[ $entry.title|escape ]]" class="xl lesswide"
                    [[if $entry.uid==0]]onkeyup="setSafename('title','uri','permalink');" onchange="setSafename('title','uri','permalink');"[[/if]] />
                    
                    <p id="permalink-p">[[t]]Permalink[[/t]]: 
                        [[$paths.host]][[$entry.link]]<span id="permalink">[[$entry.uri]]</span>[[$entry.link_end]]
                        [[if $entry.uri]]
                        <span id="permalink-link">(<a href='#' onclick="$('#permalink-edit').fadeIn();$('#permalink-link').hide();">[[t]]edit[[/t]]</a>)</span>
                        [[/if]]
                    </p>                    
                    
                    </td>
                </tr>
                    
                <tr id="permalink-edit">
                    <td><label><strong>[[t]]Internal Name[[/t]]:</strong></label></td>
                    <td><input id="uri" name="uri" type="text" value="[[ $entry.uri ]]" class="lesswide"
                        onkeyup="setSafename('uri','uri','permalink');" onchange="setSafename('uri','uri','permalink');" />
                    </td>
                </tr>

                [[ if $config.hide_subtitle ]]
                <input name="subtitle" type="hidden" value="[[ $entry.subtitle ]]" />
                [[ else]]
                <tr>
                    <td><strong>[[t]]Subtitle[[/t]]:</strong></td>
                    <td><input name="subtitle" type="text" value="[[ $entry.subtitle|escape ]]" /></td>
                </tr>
                [[/if]]    

            </table>

            [[ hook name="entry-introduction-before" value=$entry ]]

            <p><strong>[[t]]Introduction[[/t]]:</strong></p>
            <textarea name="introduction" id="introduction" class="Editor" rows='50'
                cols='4'>[[ $entry.introduction|escape:html ]]</textarea>
    
            [[ hook name="entry-body-before" value=$entry ]]

            <p><strong>[[t]]Body[[/t]]:</strong></p>
            <textarea name="body" id="body" class="Editor" rows='50'
                cols='4'>[[ $entry.body|escape:html ]]</textarea>

            <br />

            [[ hook name="entry-keywords-before" value=$entry ]]

            <table border="0" cellspacing="0" class="formclass" width="650">
                <tr>
                    <td width="140"><strong>[[t]]Keywords[[/t]] / [[t]]Tags[[/t]]:</strong></td>
                    <td width="510">
                        <input name="keywords" id="keywords" type="text" value="[[ $entry.keywords|escape ]]" />
                        <p style='margin-top:0;'>[[t]]Separate Tags with spaces. E.g., movies jedi starwars (not 'star wars')[[/t]]</p>
                        <div id="suggestedtags">&nbsp;</div>
                    </td>
                </tr>
                [[ if $config.show_via_fields ]]
                <tr>
                    <td><strong>[[t]]Via Link[[/t]]:</strong></td>
                    <td><input name="vialink" type="text" value="[[ $entry.vialink ]]" /></td>
                </tr>
                <tr>
                    <td><strong>[[t]]Via Title[[/t]]:</strong></td>
                    <td><input name="viatitle" type="text" value="[[ $entry.viatitle|escape ]]" /></td>
                </tr>
                [[ else]]
                        <input name="vialink" type="hidden" value="[[ $entry.vialink ]]" />
                        <input name="viatitle" type="hidden" value="[[ $entry.viatitle|escape ]]" />
                [[/if]]
                <tr>
                    <td valign="top"><strong>[[t]]Trackback Ping[[/t]]: </strong></td>
                    <td><textarea name="tb_url" id="tb_url" class="resizable" style="width:500px; height: 40px;" cols='50' rows='4'>[[ $entry.tb_url ]]</textarea></td>
                </tr>
            </table>

            [[ hook name="entry-bottom" value=$entry ]]

            [[* Here we select which editor to use *]]
            [[ if $user.text_processing==5 ]]

                [[ include file="inc_init_tinymce.tpl" ]]

            [[ else ]]

                [[ if $user.text_processing==0 || $user.text_processing==1 ]]
                
                    <script language="javascript" type="text/javascript">
                    jQuery(function($) {
                        $("#introduction").markItUp(markituphtml);
                        $("#body").markItUp(markituphtml);
                    });
                    </script>

                [[ /if ]]

                [[ if $user.text_processing==2 ]]

                    <script language="javascript" type="text/javascript">
                    jQuery(function($) {
                        $("#introduction").markItUp(markituptextile);
                        $("#body").markItUp(markituptextile);
                    });
                    </script>

                [[ /if ]]

                [[ if $user.text_processing==3 || $user.text_processing==4 ]]

                    <script language="javascript" type="text/javascript">
                    jQuery(function($) {
                        $("#introduction").markItUp(markitupmarkdown);
                        $("#body").markItUp(markitupmarkdown);
                    });
                    </script>
                    
                [[ /if ]]

                [[ include file="inc_init_texteditor.tpl" ]]

            [[ /if ]]

        </div>

        <div class="rightcolumn">

            <table border="0" cellpadding="0" class="formclass">
                <tr>
                    <td colspan="2" valign="top">

                        <p><strong>[[t]]Post or Preview[[/t]]:</strong></p>

                        <p class="buttons" style="margin-left: -2px; margin-right: -4px; height: 70px !important;">
                        
                            <button type="submit" class="positive">
                                <img src="./pics/tick.png" alt=""/>
                                [[t]]Post Entry[[/t]]
                            </button>

                            <button type="button" onclick="openEntryPreview();">
                                <img src="./pics/zoom.png" alt=""/>
                                [[t]]Preview[[/t]]
                            </button>

                            <br />

                            <button type="button" class="positive" onclick="saveEntryAndContinue();" style="margin-top: 4px;">
                                <img src="./pics/arrow_rotate_clockwise.png" alt=""/>
                                [[t]]Post and Continue Editing[[/t]]
                            </button>

                        </p>
                        <hr size="1" noshade="noshade" />    

                        [[ if $entry.code ]]
                        
                        <p><strong>[[t]]View Comments and Trackbacks[[/t]]:</strong></p>
                        
                        <p class="buttons" style="margin-left: -2px; margin-right: -4px; height: 35px !important;">
                            
                            <button type="button" onclick="openEntryExtra('comments',[[ $entry.code ]]);">
                                <img src="./pics/comment_edit.png" alt=""/>
                                [[t]]Comments[[/t]]
                            </button>

                            <button type="button" onclick="openEntryExtra('trackbacks',[[ $entry.code ]]);">
                                <img src="./pics/comment_edit.png" alt=""/>
                                [[t]]Trackbacks[[/t]]
                            </button>

                        </p>
                        <hr size="1" noshade="noshade" />
                        [[ /if ]]
                    </td>
                </tr>
                    [[ hook name="entry-category-before" value=$entry ]]
                <tr>
                    <td valign="top"><strong>[[t]]Category[[/t]]:</strong></td>
                    <td>
                        <select name="categories[]" size="6" multiple="multiple" style="width: 140px;">
                        <option value="">[[t]](none)[[/t]]</option>
                            [[ foreach from=$categories key=key item=category ]]
                            <option value='[[ $category.name ]]' [[ if in_array($category.name, $entry.category) ]]selected="selected"[[/if]]>[[ $category.display ]]</option>
                            [[ /foreach ]]
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><strong>[[t]]Post Status[[/t]]:</strong></td>
                    <td><select name="status">
                        <option value="publish" [[ if $entry.status=="publish" ]]selected="selected"[[/if]] >[[t]]Publish[[/t]]</option>
                        <option value="timed" [[ if $entry.status=="timed" ]]selected="selected"[[/if]] >[[t]]Timed Publish[[/t]]</option>
                        <option value="hold" [[ if $entry.status=="hold" ]]selected="selected"[[/if]] >[[t]]Hold[[/t]]</option>
                    </select></td>
                </tr>
                <tr>
                    <td colspan="2"><p><strong>[[t]]Publish on[[/t]]:</strong></p>
                        <input name="publish_date1" type="text" class='date-picker input' id="publish_date1"
                        value="[[ date date=$entry.publish_date format='%day%-%month%-%year%' ]]" size="15" />
                        <input name="publish_date2" type="text" class='input' id="publish_date2"
                        value="[[ date date=$entry.publish_date format='%hour24%-%minute%' ]]" size="7" />
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <table border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="padding: 0 2px;"><strong>[[t]]Allow comments[[/t]]: &nbsp;

                                </strong></td>
                                <td style="padding: 0 2px;">
                                    <input name="allow_comments" type="radio" value="1" id="comm_yes" [[ if $entry.allow_comments==1 ]]checked="checked"[[/if]] />
                                </td>
                                <td style="padding: 0 2px;"><label for="comm_yes">[[t]]Yes[[/t]]</label> &nbsp;</td>
                                <td style="padding: 0 2px;">
                                    <input name="allow_comments" type="radio" value="0" id="comm_no" [[ if $entry.allow_comments==0 ]]checked="checked"[[/if]] /></td>
                                    <td style="padding: 0 2px;"><label for="comm_no">[[t]]No[[/t]]</label> &nbsp; </td>
                                </tr>
                            </table>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2"> <hr size="1" noshade="noshade" /></td>
                        </tr>
                        <tr>
                            <td colspan="2"><p><strong>[[t]]Created on[[/t]]:
                            </strong>
                        </p>
                        <input name="date1" id="date1" type="text" class='input date-picker'
                        value="[[ date date=$entry.date format='%day%-%month%-%year%' ]]" size="15" />
                        <input name="date2" id="date2" type="text" class='input'
                        value="[[ date date=$entry.date format='%hour24%-%minute%' ]]" size="7" />
                    </td>
                </tr>


                <tr>
                    <td colspan="2"><p><strong>[[t]]Last edited on[[/t]]:</strong></p>
                        <input name="edit_date1" type="text" class='input' readonly='readonly'
                        value="[[ date date=$entry.edit_date format='%day%-%month%-%year%' ]]" size="15" />
                        <input name="edit_date2" type="text" class='input' readonly='readonly'
                        value="[[ date date=$entry.edit_date format='%hour24%-%minute%' ]]" size="7" />
                    </td>
                </tr>
                <tr>
                    <td><strong>[[t]]Author[[/t]]:</strong></td>
                    <td>
                        [[ if $user.userlevel >= 4 ]]
                            <select name="author">
                            [[ foreach from=$users key=key item=u ]]
                                <option value="[[$u.username]]" [[ if $entry.user==$u.username ]]selected="selected"[[/if]] >
                                    [[ $u.nickname ]]
                                </option>
                            [[/foreach]]    
                            </select>
                        [[ else ]]
                            <input name="author" type="text" value="[[ $entryuser.nickname ]]" readonly="readonly" />
                        [[/if]]
                    </td>
                </tr>
                <tr>
                    <td><strong>[[t]]Code[[/t]]:</strong></td>
                    <td><input name="code" type="hidden" value="[[ $entry.code ]]" id="uid" />[[ $entry.code ]]</td>
                </tr>
                [[ hook name="entry-code-after" value=$entry ]]
            </table>

        </div>




        <div class="cleaner">&nbsp;</div>

        <p class="buttons">
            <button type="submit" class="positive">
                <img src="./pics/tick.png" alt=""/>
                [[t]]Post Entry[[/t]]
            </button>

            <button type="button" class="positive" onclick="saveEntryAndContinue();">
                <img src="./pics/arrow_rotate_clockwise.png" alt=""/>
                [[t]]Post and Continue Editing[[/t]]
            </button>
            
            <button type="button" onclick="openEntryPreview();">
                <img src="./pics/zoom.png" alt=""/>
                [[t]]Preview[[/t]]
            </button>            

            [[ if $entry.code ]]
            <button type="button" class="negative" style="margin-left: 30px;" onclick="deleteEntry('[[t escape=js]]Are you sure you wish to delete this entry?[[/t]]');">
                <img src="./pics/delete.png" alt=""/>
                [[t]]Delete entry[[/t]]
            </button>
            [[/if]]
            
        </p>

        <div class="cleaner">&nbsp;</div>

    </form>

</div>

<iframe id="posthere" name="posthere" style='width: 1px; height: 1px; display:none; visibility: hidden;'>This hidden frame is here to allow posting the entry or page and continue editing</iframe>
[[include file="inc_footer.tpl" ]]
