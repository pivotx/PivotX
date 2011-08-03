<?php

/**
 * Two Kings Form Class, to construct web based forms, do validation and
 * handle the output.
 *
 * For more information, read: http://twokings.eu/tools/
 *
 * Two Kings Form Class and all its parts are licensed under the GPL version 2.
 * see: http://www.twokings.eu/tools/license for more information.
 *
 * @version 1.1
 * @author Bob den Otter, bob@twokings.nl
 * @copyright GPL, version 2
 * @link http://twokings.eu/tools/
 *
 * $Rev:: 702                                            $: SVN revision,
 * $Author:: pivotlog                                    $: author and
 * $Date:: 2007-09-22 20:25:00 +0200 (za, 22 sep 2007)   $: date of last commit
 *
 */



/**
 * This file contains the default HTML definition for each of the form elements
 * If you want to change these, you can either override some of the elements
 * in your PHP code, modify this file, or instantiate the form class using
 * another HTML definitions file.
 */


/**
 * Header and footer of the form
 */
$this->html['start'] = <<< EOM
<form  enctype="multipart/form-data"  name="%name%" id="%name%" action="%action%" method="post">
<fieldset style="display: none">
%hidden_fields%
</fieldset>
<table border="0" cellspacing="0" cellpadding="4" class="formclass">
EOM;


$this->html['finish'] = <<< EOM
</table>
</form>
EOM;

/**
 * The submit button
 */
$this->html['submit'] = <<< EOM
<tr>

    <td class="buttons" colspan="3">
        <button type="submit" tabindex="%tabindex%" class="positive">
            <img src="./pics/tick.png" alt="" />
            <span class="text">%submit%</span>
        </button>
    </td>

</tr>
EOM;

/**
 * The form header
 */
$this->html['header'] = <<< EOM
<tr>
    <th colspan="3">%text%</th>
</tr>
EOM;

/**
 * For adding a 'row' to display information
 */
$this->html['info'] = <<< EOM
<tr>
    <td colspan='3'><p>%text%</p></td>
</tr>
EOM;

/**
 * Add whatever to the form.
 */
$this->html['custom'] = <<< EOM
%text%
EOM;

$this->html['hr'] = <<< EOM
<tr><td colspan="3" style="margin: 0; font-size: 1px; line-height: 1px;"><hr size="1" noshade="noshade" /></td></tr>
EOM;


/**
 * Basic text input
 */
$this->html['text'] = <<< EOM
<tr>
    <td valign="top">
        <label for="%name%">%label% %isrequired%</label>
    </td>
    <td valign="top">
        <input name="%name%" id="%name%" class="%class% %haserror%" type="text" value="%value%" size="%size%" style="%style%" tabindex="%tabindex%" %extra% />
       %error%
    </td>
    <td>
       %text%
    </td>
</tr>
EOM;


/**
 * Insert a text input that's readonly
 */
$this->html['text_readonly'] = <<< EOM
<tr>
    <td valign="top">
        <label for="%name%">%label%&nbsp;%isrequired%</label>
    </td>
    <td valign="top">
        <input name="%name%" id="%name%" class="%haserror%" type="text" value="%value%" size="%size%" style="%style%" readonly="readonly" tabindex="%tabindex%" />
       %error%
    </td>
    <td>
       %text%
    </td>
</tr>
EOM;


/**
 * Text input, with an option to select/upload an image.
 */
$this->html['image_select'] = <<< EOM
<tr>
    <td valign="top">
        <label for="%name%">%label% %isrequired%</label>
    </td>
    <td valign="top">
        <table border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td valign="top" style="padding: 0;">
            <input name="%name%" id="%name%" class="%class% %haserror%" type="text" value="%value%" size="%size%" style="%style%" tabindex="%tabindex%" %extra% />
           %error%
                </td>
                <td valign="top" class="buttons_small" style="padding: 0 0 0 5px;">
                    <a href="javascript:;" onclick="openUploadWindow('Select or upload an image', $('#%name%'), 'gif,jpg,png');">
                        <img src='pics/page_lightning.png' /> Select
                    </a>
                </td>
            </td>
        </table>
    </td>
    <td>
       %text%
    </td>
</tr>
EOM;


/**
 * Insert a date select box.
 *
 * TODO: replace with jquery select box.
 */
$this->html['date_select'] = <<< EOM
<tr>
    <td valign="top">
        %label% %isrequired%
    </td>
    <td valign="top"><input name="%name%" id="%name%" class="%haserror%" type="text" value="%value%" size="%size%"  tabindex="%tabindex%" />
        <button type="reset" id="trigger[%tabindex%]">...</button>
        <script type="text/javascript">
            Calendar.setup({
                inputField: "%name%", ifFormat: "%Y-%m-%d", showsTime: false,
                button: "trigger[%tabindex%]",  singleClick: true,  step: 1, align: "BR"
            });
        </script>\n\t%error% %text%\n\t</td>
</tr>
EOM;


/**
 * Insert a date/time select box.
 *
 * TODO: replace with jquery select box.
 */
$this->html['datetime_select'] = <<< EOM
<tr>
    <td valign="top">
        <label for="%name%">%label% %isrequired%</label>
    </td>
    <td valign="top"><input name="%name%" id="%name%" class="%haserror%"  type="text" value="%value%" size="%size%" tabindex="%tabindex%" />
        <button type="reset" id="trigger[%tabindex%]">...</button>
        <script type="text/javascript">
            Calendar.setup({
                inputField: "%name%", ifFormat: "%Y-%m-%d %H:%I:00", showsTime: true,
                button: "trigger[%tabindex%]",  singleClick: true,  step: 1, align: "BR"
            });
        </script>\n\t%error% %text%\n\t</td>
</tr>
EOM;

/**
 * Insert a basic textarea field
 */
$this->html['textarea'] = <<< EOM
<tr>
    <td valign="top">
        <label for="%name%">%label% %isrequired%</label>
    </td>
    <td valign="top" colspan="2">
        <textarea name="%name%" id="%name%" cols="%cols%" class="resizable %haserror%"  rows="%rows%" style="%style%" tabindex="%tabindex%" >%value%</textarea>
       %error%
        %text%
    </td>
</tr>
EOM;

/**
 * Insert a password field
 */
$this->html['password'] = <<< EOM
<tr>
    <td valign="top">
        <label for="%name%">%label% %isrequired%</label>
    </td>
    <td valign="top">
        <input name="%name%" id="%name%" type="password" class="%haserror%" value="%value%" size="%size%" style="%style%" tabindex="%tabindex%" %extra% />
       %error%
    </td>
    <td>
       %text%
    </td>
</tr>
EOM;

/**
 * Insert a hidden field. (will not be displayed on the form, but can be seen
 * using 'view source', so don't pass security related info this way.
 */
$this->html['hidden'] = <<< EOM
    <input name="%name%" id="%name%" type="hidden" value="%value%" />
EOM;



/**
 * Insert a hidden CSRF check field. We fill the form in the browser with the
 * value of the 'cookie' cookie. On the serverside, this value will be compared
 * to the 'sessionvalue'. If they do not match, an error will be raised.
 *
 * For info on why this is necessary, see:
 * http://en.wikipedia.org/wiki/Cross-site_request_forgery.
 *
 */
$this->html['csrf'] = <<< EOM
    <input name="csrfcheck" id="csrfcheck" type="hidden" value="" />

    <script type="text/javascript">
    $(function() {
        setTimeout('$("#csrfcheck").val( $.cookie("%cookie%"))', 500 );
    });
    </script>

EOM;


/**
 * Radio and radio_element are used together to create groups of radio buttons
 */
$this->html['radio'] = <<< EOM
<tr>
    <td valign="top">
        %label% %isrequired%
    </td>
    <td valign="top">
       <table border="0" cellspacing="0" cellpadding="0">

           %elements%

       </table>

       %error%
    </td>
    <td valign="top">
       %text%
    </td>
</tr>
EOM;


$this->html['radio_element'] = <<< EOM
    <tr>
        <td>
            <input type="radio" name="%name%" value="%value%" id="%formname%_%name%_%value%" %checked% class="noborder" tabindex="%tabindex%" />
        </td>
        <td>
            <label for="%formname%_%name%_%value%">%label%</label>
        </td>
    </tr>
EOM;



/**
 * Radiogrid and is used to create groups of radio buttons
 */
$this->html['radiogrid'] = <<< EOM
<tr>
    <td valign="top" colspan="2">
        %label% %isrequired%
        %elements%
        %error% %text%
    </td>
</tr>
EOM;



/**
 * Select and select_element are used together to create select drop down menu"s
 */
$this->html['select'] = <<< EOM
<tr>
    <td valign="top">
        <label for="%name%">%label% %isrequired%</label>
    </td>
    <td valign="top">
        <select name="%name%" id="%name%" size="%size%" class="%class% %haserror%"  %multiple% %extra%  tabindex="%tabindex%" >
            %elements%
        </select>%multiple_selectors%
       %error%
    </td>
    <td valign="top">
       %text%
    </td>
</tr>
EOM;


$this->html['select_element'] = <<< EOM
<option value="%value%" %selected% %disabled% >%label%</option>
EOM;



$this->html['add_select'] = <<< EOM
<tr>
    <td valign="top">
        <label for="%name%">%label% %isrequired%</label>
    </td>
    <td valign="top">

        <table border="0" cellpadding="1">
            <tr>
            <td>
                <b>Selected</b><br />
                <select name="%name%[]" id="%name%" multiple size="12" style="width: 150px"
                        onDblClick="moveOver("%name%","not%name%")" tabindex="%tabindex%" >
                    %elements%
                </select>
            </td>
            <td align="center">
                <input type="button" value="&raquo; Remove" onclick="moveOver("%name%","not%name%")"
                        style="width: 120px; margin: 3px;" /><br />
                <input type="button" value="&laquo; Add to selection"
                        onclick="moveOver("not%name%","%name%")" style="width: 120px; margin: 3px;" /><br />

                <br />

                <input type="button" value="Move Up" onclick="moveUp("%name%")"
                        style="width: 120px; margin: 3px;" /><br />
                <input type="button" value="Move Down" onclick="moveDown("%name%")"
                        style="width: 120px; margin: 3px;" /><br />
            </td>
            <td>
                <b>Available</b>
                <br />
                <select name="not%name%[]" id="not%name%" multiple size="12"
                        style="width: 150px" onDblClick="moveOver("not%name%","%name%")">
                    %unselected-elements%
                </select>
            </td>
            </tr>
        </table>

    </td>
</tr>
<script type="text/javascript">
    document.%formname%.onsubmit = function() { selectAll("%name%"); }
</script>

EOM;


/**
 * Some of the more obscure elements:
 */

$this->html['color_select'] = <<< EOM
<tr>
    <td valign="top">
        <label for="%name%">%label% %isrequired%</label>
    </td>
    <td valign="top">
        <select name="%name%" id="%name%" size="%size%" class="%haserror%"
              %multiple% onchange="this.style.background=this.value;"
              style="background-color:%value%" tabindex="%tabindex%">
            %elements%
        </select>
        %error%
        %text%
    </td>
</tr>
EOM;


$this->html['color_select_element'] = <<< EOM
<option value="%value%" %selected% style="background-color:%value%">%value% - %label%</option>
EOM;



$this->html['checkbox'] = <<< EOM
<tr>
    <td valign="top">
        <label for="%formname%_%name%">%label% %isrequired%</label>
    </td>
    <td valign="top">
        <input type="checkbox" name="%name%" value="1" %checked% id="%formname%_%name%"
              class="noborder" tabindex="%tabindex%" />
       %error%
    </td>
    <td>
       %text%
    </td>
</tr>
EOM;


/**
 * checkboxgrid is used to create groups of radio buttons
 */
$this->html['checkboxgrid'] = <<< EOM
<tr>
    <td valign="top" colspan="2">
        %label% %isrequired%
        %elements%
        %error% %text%
    </td>
</tr>
EOM;

$this->html['checkboxgrid_element'] = <<< EOM
<input type="checkbox" name="%name%" value="%value%" %checked% id="%formname%_%name%"
        class="noborder" tabindex="%tabindex%" />
EOM;

$this->html['file'] = <<< EOM
<tr>
    <td valign="top">
        <label for="%formname%_%name%">%label% %isrequired%</label>
    </td>
    <td valign="top">
        <input name="%name%" type="file" value="%value%" id="%formname%_%name%"
              class="%haserror%" size="%size%" style="%style%" tabindex="%tabindex%" />
        %error%
        %text%
    </td>
</tr>
EOM;


/**
 * How errors in your forms are displayed.
 */
$this->error = <<< EOM
<label for="%name%" generated="true" class="error">%error%</label>
EOM;

/**
 * The class to be added to a field that has an error..
 */
$this->haserror = "error";

/**
 * How 'required' elements are shown in the form.
 */
$this->isrequired = <<< EOM
<span class="required">*</span>
EOM;




?>
