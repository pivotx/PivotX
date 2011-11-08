[[include file="inc_header.tpl" ]]
[[include file="inc_init_texteditor.tpl" ]]

<script type="text/javascript">

jQuery(function($){
    $('#reload-button').bind('click',function(e){
        e.preventDefault();
        var loc = new String(document.location);
        var pos = loc.indexOf('#');
        if (pos > 0) {
            loc = loc.substring(0,pos);
        }
        loc = loc.replace(/([?&]del=[^&]*)/,'');
        loc = loc.replace(/([?&]file=[^&]*)/,'');
        loc = loc.replace(/([?&]pivotxsession=[^&]*)/,'');
        loc = loc.replace(/([?&]answer=[^&]*)/,'');
        document.location = loc;
    });
});
</script>

[[ hook name="media-before" ]]

[[ if count($labels)>1 ]]
    <div class="buttons_small">
    [[ foreach from=$labels key=key item=item ]]
        <a href='index.php?page=[[$currentpage]]&additionalpath=[[$additionalpath]]&offset=[[$key]]' style="margin: 0 5px 5px 0;">
                <img src='pics/page.png' alt='' /><strong>[[t]]Pg.[[/t]] [[ $key+1 ]]</strong> ([[$item]])
        </a>
    [[ /foreach ]]
    </div>
    <div class="cleaner">&nbsp;</div>
[[ /if ]]

<table border='0' cellspacing='0' class='formclass tabular' >

<tr>
    <th>&nbsp;</th>
    <th>[[t]]Filename[[/t]]</th>
    <th>[[t]]Description[[/t]]</th>
    <th align="right">[[t]]Filesize[[/t]]</th>
    <th>&nbsp;</th>
    <th>&nbsp;</th>
    <th>&nbsp;</th>
    <th>&nbsp;</th>
    <th>&nbsp;</th>  
</tr>

[[ foreach from=$dirs key=name item=path ]]

<tr class='[[ cycle values="even, odd"]]' style='height: 26px;'>
    <td>
        <img src='pics/folder.png' width='16' height='16' alt='folder' />
    </td>
    <td colspan='7'>
        <strong><a href='index.php?page=[[$currentpage]]&amp;additionalpath=[[ $path|escape:"url" ]]'>[[ $name ]]</a></strong>

    </td>

    <td class="buttons_small">
        [[ hook name="media-line-folder" value=$path ]]
    </td>

</tr>


[[ /foreach ]]


[[ foreach from=$files key=name item=item ]]

<tr class='[[ cycle values="even, odd"]]' style='height: 26px;'>
    [[ if $item.ext=="gif" || $item.ext=="jpg" || $item.ext=="jpeg" || $item.ext=="png" ]]
    
        <td>
            <img src='pics/image.png' width='16' height='16' alt='image' />
        </td>
        <td>
            <a href="[[$imageurl]][[$name]]" title="[[ $name ]]" class="thickbox">[[ $name ]]</a>
        </td>
        
        <td class='nowrap small'>
            [[t]]Image[[/t]], [[ $item.dimension ]] px.
        </td>
        <td align='right' class='nowrap'>
            [[ $item.size ]]
        </td>
        <td>
            &nbsp;
        </td>
    
        [[* Insert a table cell if we are hiding the cell with the thumbnail editor *]]
        [[ if $hide.medialineimage ]]
        <td class="buttons_small"></td>
        [[ /if ]]

        <td class="buttons_small">
            [[ if $user.userlevel>=2 ]]
                [[ hook name="media-line-image" value=$item ]]
            [[ /if ]]            
        </td>
    
        [[ if $hide.medialineimage ]]<!--[[/if x="-->"]]
        <td class="buttons_small">
            <a href="#" onclick="imageEdit('[[$imagepath]][[$name]]');">
                <img src='pics/image_edit.png' alt='' /> [[t]]Thumbnail[[/t]]
            </a>
        </td>
        [[ if $hide.medialineimage ]]-->[[/if]]
        
        
    

    [[ else ]]

        <td>
            <img src='pics/page.png' width='16' height='16' alt='file' />
        </td>
        <td>
            [[ $name ]]
        </td>
    
        
        <td class='nowrap small'>
            [[ filedescription filename=$name ]]&nbsp;
        </td>
        <td align='right' class='nowrap'>
            [[ $item.size ]]
        </td>
        <td>
            &nbsp;
        </td>
    
        <td class="buttons_small">
            [[ hook name="media-line-file" value=$item ]]           
        </td>
        <td class="buttons_small">        
            [[ if $item.writable && $item.bytesize<65536 && $user.userlevel>=3 ]]
            
                <a href="ajaxhelper.php?function=view&amp;basedir=[[ $basedir|escape:"base64" ]]&amp;file=[[ $item.path|escape:"url" ]]"
                    title="[[t]]Edit[[/t]] &raquo; <strong>[[ $item.path ]]</strong>" class="dialog editor">
                    <img src='pics/pencil.png' alt='' /> [[t]]Edit[[/t]]
                </a>

            [[ /if ]]
        </td>

    [[/if]]
    

        
    <td class="buttons_small">
        [[ if $item.writable && $user.userlevel>=2  ]]
        <a href="#"  onclick="return confirmme('index.php?page=[[$currentpage]]&amp;del=[[ $item.path|escape:"url" ]]', '[[t escape=js ]]Are you sure you wish to delete this file?[[/t]]');" class="negative">
            <img src='pics/delete.png' alt='' />
            [[t]]Delete[[/t]]
        </a>
        [[ /if ]]
    </td>
        
    <td class="buttons_small">
        [[ if $uploadallowed && $user.userlevel>=2  ]]
        <a href="#"  onclick="return askme('index.php?page=[[$currentpage]]&amp;file=[[ $item.path|escape:"url" ]]', '[[t esacpe=js]]Copy to file name?[[/t]]');">
        <img src='pics/add.png' alt='' />
        [[t]]Duplicate[[/t]]
        </a>
        [[ /if ]]
    </td>



</tr>


[[ /foreach ]]

</table>



[[ if $uploadallowed ]]

    <div id="upload-container">
    <p class="buttons">
        [[ if $user.userlevel>=2 ]]
        <a href="#" onclick="return askme('index.php?page=[[$currentpage]]&amp;path=[[ $additionalpath|escape:"url" ]]', '[[t escape=js]]New file name?[[/t]]');">
            <img src="pics/page_add.png" alt="" />
           [[t]]Create a new file[[/t]]
        </a>
    
        <a href="#" onclick="return askme('index.php?page=[[$currentpage]]&amp;addfolder=[[ $additionalpath|escape:"url" ]]', '[[t escape=js]]New folder name?[[/t]]');">
            <img src="pics/folder_add.png" alt="" />
            [[t]]Create a new folder[[/t]]
        </a>
        [[ /if ]]
    
        <a href="#" id="upload-button" >
            <img src="pics/page_lightning.png" alt="" />
           [[t]]Upload a file[[/t]]
        </a>
    </p>
    </div>


[[ else ]]

    <p>[[t]]You're not allowed to upload/duplicate files in this folder. Use your FTP program to do this.[[/t]]</p>

[[ /if ]]

[[assign var='prepath' value=$paths.upload_base_path]]
[[if $smarty.get.page=='fileexplore']]
    [[assign var='prepath' value=$paths.db_path]]
[[elseif $smarty.get.page=='homeexplore']]
    [[assign var='prepath' value=$paths.home_path]]
[[elseif $smarty.get.page=='templates']]
    [[assign var='prepath' value=$paths.templates_path]]
[[/if]]
[[upload_create_button browse_button='upload-button' container='upload-container' 
    progress_selector='#divFileProgressContainer' filters='any' 
    upload_type='files' path=$prepath|cat:$additionalpath upload_dialog='#uploader']]

<div id="uploader">
    <div class="files default">
        [[t]]List of uploaded files[[/t]]
    </div>

    <div id="divFileProgressContainer" style="width:330px; clear: both"></div>

    <div style="clear: both">
        <p class="buttons" style="display: none">
            <a href="#" id="reload-button">[[t]]Done[[/t]]</a>
        </p>
    </div>
</div>

[[ hook name="media-after" ]]


[[include file="inc_footer.tpl" ]]
