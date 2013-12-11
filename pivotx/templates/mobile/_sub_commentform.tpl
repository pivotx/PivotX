
<form method="post" action="[[ self ]]#message" id="commentform">
    <table border='0' cellspacing='0' cellpadding='0' width="98%" class="commentform">

        <tr>
            <td>
                <label for="piv_name">[[t]]Name[[/t]]:</label>&nbsp;&nbsp;
            </td>
            <td colspan="2">
                <input type="text" name="piv_name" id="piv_name" size="20" class="commentinput [[ registered ]]" value="[[ remember name=name ]]" />
            </td>
        </tr>

        <tr>
            <td>
                <label for="piv_email">[[t]]Email[[/t]]:</label>
            </td>
            <td colspan="2">
                <input name="piv_email" id="piv_email" type="text" size="30" class="commentinput [[ registered ]]" value="[[ remember name=email ]]" />
            </td>
        </tr>

        <tr>
            <td>
                <label for="piv_url">[[t]]URL[[/t]]:</label>
            </td>
            <td colspan="2">
                <input name="piv_url" id="piv_url" type="text" size="30" class="commentinput [[ registered ]]" value="[[ remember name=url ]]" />
            </td>
        </tr>

        <tr>
            <td>
                <label for="piv_comment">[[t]]Comment[[/t]]:</label>
            </td>
            <td colspan="2">
                [[ emotpopup ]]
                [[ textilepopup ]]
            </td>
        </tr>

        <tr>
            <td colspan='4'>
                <textarea name="piv_comment" id="piv_comment" cols="40" rows="7" style="width:98%; margin-bottom:5px;">[[ remember name="comment"]]</textarea>
            </td>
        </tr>

        <tr>
            <td colspan='4'>
                [[ spamquiz ]]
            </td>
        </tr>

        <tr>
            <td colspan='4'>
                [[ moderate_message ]]
                <input type="hidden" name="piv_code" value="[[ $entry.uid ]]" />
                <input type="hidden" name="piv_discreet" value="1" />
                <input type="hidden" name="piv_weblog" value="[[ weblogid ]]" />
                <input type="submit" name="post" value="[[t]]Post Comment[[/t]]" class="commentbutton" style="font-weight: bold;" />
                <input type="submit" name="preview" value="[[t]]Preview Comment[[/t]]" class="commentbutton" />
            </td>
        </tr>

        <tr>
            <td colspan="5">

                <table border='0'>

                    <tr>
                        <td valign="top" colspan="4">
                            [[t]]Remember personal info?[[/t]]
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" style="width:10px">
                            <input name="piv_rememberinfo" type="checkbox" id="piv_rememberinfo" value="1" [[ remember name="rememberinfo"]] />
                        </td>
                        <td valign="top">
                            <label for="piv_rememberinfo">[[t]]Yes, give me a cookie and remember me.[[/t]]</label>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>

    </table>
</form>