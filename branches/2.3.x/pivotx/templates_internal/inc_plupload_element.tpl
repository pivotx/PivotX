[[*
Parameters:

    browse_button           id of button to choose the files (and start uploading)
    container               id of the container where everything happens
    progress_selector       jquery selector for the progress information
    input_selector          (optional) enter the uploaded file names to this jquery selector
    upload_type             type of upload: image, images, file, files
    filters                 file type filter: image, document, any
*]]

[[assign var='multiple' value=false]]
[[if ($upload_type=='files') || ($upload_type=='images')]]
    [[assign var='multiple' value=true]]
[[/if]]

<script type="text/javascript">

// Custom example logic for [[$upload_var]] - [[$input_selector]]
//
var [[$upload_var]];
var timer_[[$upload_var]] = false;
var [[$upload_var]]_upload_history = [];

function [[$upload_var]]_start()
{
    [[$upload_var]].start();

    [[if $upload_dialog!='']]
    $('[[$upload_dialog]]').dialog({
            bgiframe: true, 
            resizable: false,
            modal: true,
            draggable: false, 
            width: 540,
            height: 220,
            title: "[[t]]Uploading[[/t]]",
            overlay: { opacity: 0.75, background: "#789" },
            close: function() {
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
            }
    });
    [[/if]]
}

function [[$upload_var]]_reloadbutton()
{
    if ([[$upload_var]].total.queued == 0) {
        $('[[$upload_dialog]] .buttons').show();
    }
    else {
        setTimeout('[[$upload_var]]_reloadbutton();',250);
    }
}

function [[$upload_var]]_setprogress(name,percent)
{
    var width = $("[[$progress_selector]]").width();
    var height = $("[[$progress_selector]]").height();

    var pwidth = Math.round((percent * (width - 20)) / 100);
    if (pwidth > width) {
        pwidth = width;
    }
    var pheight = height;
    if (pheight < 2) {
        pheight = 2;
    }
    if (pheight > 2) {
        pheight = 2;
    }

    var html = '';
    html += '<div style="border: 1px solid #888; margin: 4px; padding: 4px; color: #444">';

[[if $upload_type=='images']]
    if ([[$upload_var]]_upload_history.length > 0) {
        for(var i=0; i < [[$upload_var]]_upload_history.length; i++) {
            html += '<strong>' + [[$upload_var]]_upload_history[i] + '</strong> [[t]]uploaded[[/t]].<br/>';
        }
        html += '<br/>';
    }
[[/if]]

    html += '<strong>' + name + '</strong><br/>';
    if (percent < 100) {
        html += '[[t]]Uploading[[/t]]<br/>';
    }
    else {
        html += '[[t]]Uploaded[[/t]]<br/>';
    }
    html += '<div style="display:block;margin:4px 0 0 0;padding:0;width:'+pwidth+'px;height:'+pheight+'px;background-color:#888">&#160;</div>';
    html += '</div>';

    $("[[$progress_selector]]").show();
    $("[[$progress_selector]]").html(html);
}

jQuery(function($) {
    [[$upload_var]] = new plupload.Uploader({
        runtimes : '[[$runtimes]]',
        browse_button : '[[$browse_button]]',
        container : '[[$container]]',
        max_file_size : '[[$max_file_size]]',
        [[if $path=='']]
        url : '[[$url]]?type=[[$upload_type]]',
        [[else]]
        url : '[[$url]]?type=[[$upload_type]]&path=[[$path]]',
        [[/if]]
        flash_swf_url : '[[$jsdir]]/plupload.flash.swf',
        silverlight_xap_url : '[[$jsdir]]/plupload.silverlight.xap',
        [[if count($filters)>0]]
        filters : [
        [[assign var='first' value=true]]
        [[foreach from=$filters item='filter']]
            [[if !$first]],[[/if]][[assign var=first value=false]]

            {title : "[[$filter.title]]", extensions : "[[$filter.extensions]]"}
        [[/foreach]]
        ],
        [[/if]]
        [[if $multiple]]
        multi_selection: true,
        [[else]]
        multi_selection: false,
        [[/if]]
        unique_names: false,
        urlstream_upload: true
    });

    [[$upload_var]].bind('Init', function(up, params){
        var log = 'plupload runtime used is "' + params.runtime + '"';
        var url = '[[$paths.pivotx_url]]ajaxhelper.php?function=logDebug&log='+escape(log);
        $.ajax({ 'url': url });
    });

    [[$upload_var]].bind('QueueChanged', function(up){
        [[if !$multiple]]
        while (up.files.length > 1) {
            ret = up.removeFile(up.files[0]);
        }
        [[/if]]
    });

    [[$upload_var]].bind('FilesAdded', function(up, files){
        if (timer_[[$upload_var]]) {
            clearTimeout(timer_[[$upload_var]]);
            timer_[[$upload_var]] = false;
        }

        //console.log('filesadded');
        [[if !$multiple]]
        var first = true;
        $.each(files, function(i, file) {
            if (!first) {
                up.removeFile(file);
            }
            first = false;
        });
        [[/if]]
 
        up.refresh(); // Reposition Flash/Silverlight

        if (files.length > 0) {
            setTimeout('[[$upload_var]]_start();',500);
        }
    });

    [[$upload_var]].bind('UploadProgress', function(up, file) {
        if (file.percent < 100) {
            var name = new String(file.name);
            // this replacement *has* to be the same as the fileupload.php one
            name = name.replace(/[^a-zA-Z0-9_. -]+/,' ',name);
            [[$upload_var]]_setprogress(name,file.percent);
            //$('#' + file.id + " b").html(file.percent + "%");
        }
    });

    [[$upload_var]].bind('Error', function(up, err) {
        var log = 'File upload error. Code=' + err.code;
        var url = '[[$paths.pivotx_url]]ajaxhelper.php?function=logDebug&log='+escape(log);
        $.ajax({ 'url': url });

        var message = '';
        if (err.code === plupload.FILE_EXTENSION_ERROR) {
            message = '[[t]]File extension error[[/t]]';
        }
        else if (err.code === plupload.FILE_SIZE_ERROR) {
            message = '[[t]]Upload too big[[/t]]';
        }
        else if (err.code == plupload.INIT_ERROR) {
            message = '[[t]]Upload init error[[/t]]';
        }
        else {
            message = '[[t]]Upload error[[/t]]';
        }

        if (message != '') {
            if ($('[[$progress_selector]]').is(':visible') == false) {
                alert(message);
            }
            else {
                $('[[$progress_selector]]').append(message);
            }
        }
 
        up.refresh(); // Reposition Flash/Silverlight
    });

    [[$upload_var]].bind('FileUploaded', function(up, file) {
        var name = new String(file.name);
        // this replacement *has* to be the same as the fileupload.php one

        // determine the actual filename on the server, by calling the upload-script and asking for the 'last filename'
        var realname = name;
        $.ajax({
            async: false,
            url: '[[$paths.pivotx_url]]fileupload.php?path=[[$path]]&type=[[$upload_type]]&name='+escape(file.name)+'&act=filename',
            data: {},
            success: function(data){
                realname = data;
            },
            dataType: 'html'
        });
        name = realname;


    [[if $upload_type=='image'||$upload_type=='file'||$upload_type=='images']]
        name = "[[$upload_path]]" + name;
    [[/if]]

    [[if $input_selector!='']]
        $('[[$input_selector]]').val(name);
    [[/if]]
        [[$upload_var]]_setprogress(name,100);

        var cmd = "[[$upload_var]]_upload_history=[]; $('[[$progress_selector]]').hide('slow')";
        timer_[[$upload_var]] = setTimeout(cmd,3000);

    [[if $upload_type=='image']]
        updatePreview(name);
    [[/if]]

    [[if $upload_dialog!='']]
        var el = $('[[$upload_dialog]] .files');
        var html = el.html();
        if (el.hasClass('default')) {
            el.removeClass('default');
            html = '';
        }
        html += '<strong>' + file.name + '</strong> [[t]]uploaded[[/t]].<br/>';
        $('[[$upload_dialog]] .files').html(html);

        setTimeout('[[$upload_var]]_reloadbutton();',100);
    [[/if]]

    // getting the length of an array with a variable name is not easy to do....
    var temp_upl_hist = [[$upload_var]]_upload_history;
    temp_upl_hist[temp_upl_hist.length] = file.name;
    [[$upload_var]]_upload_history = temp_upl_hist;

    [[if $upload_type=='images']]
        if (imagearray) {
            imagearray[imagearray.length] = name;
        }
    [[/if]]
    });

    [[$upload_var]].init();
});
</script>
