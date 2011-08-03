<?php

// ---------------------------------------------------------------------------
//
// PIVOTX - LICENSE:
//
// This file is part of PivotX. PivotX and all its parts are licensed under
// the GPL version 2. see: http://docs.pivotx.net/doku.php?id=help_about_gpl
// for more information.
//
// $Id$
//
// ---------------------------------------------------------------------------

// some global initialisation stuff
if(realpath(__FILE__)=="") {
    $pivotx_path = dirname(dirname(realpath($_SERVER['SCRIPT_FILENAME'])))."/";
} else {
    $pivotx_path = dirname(dirname(realpath(__FILE__)))."/";
}
$pivotx_path = str_replace("\\", "/", $pivotx_path);

require_once($pivotx_path.'lib.php');

initializePivotX(false);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en-us" />
    <title><?php _e('PivotX Image Cropper'); ?></title>
    <link rel="stylesheet" type="text/css" href="../templates_internal/assets/jquery.Jcrop.css" />
    <script src="<?php echo $PIVOTX['paths']['jquery_url']; ?>" type="text/javascript"></script>
    <script src="../includes/js/jquery.Jcrop.min.js" type="text/javascript"></script>



    <script type="text/javascript" charset="utf-8">

        function showCoords(c) {
            jQuery('#cropdetails').html('<?php _e('Crop size'); ?>: ' + c.w + "&times;" + c.h
                + " (" + c.x + "," + c.y + " - " + c.x2 + "," + c.y2 + ")"  );
            
            jQuery('#crop_x').val(c.x);
            jQuery('#crop_y').val(c.y);
            jQuery('#crop_w').val(c.w);
            jQuery('#crop_h').val(c.h);
            
            jQuery('#factor').val( 1 / Math.min( setH/c.h, setW/c.w) );
            
        }

        /**
         * Make a fixed size thumbnail, as set in configuration.
         */
        function cropFixed() {

            jQuery('#cropbox').Jcrop({
                boxWidth: 580,
                boxHeight: 500,
                onSelect: showCoords,
                onChange: showCoords,
                aspectRatio: setW/setH, 
                setSelect: [ 20, 20, 300, 300 ]
            });

            jQuery('#type').val('fixed');
            
            jQuery('#resultWidth').html(setW);
            jQuery('#resultHeight').html(setH);

        }

        /**
         * Make a thumbnail with free proportions, but it's bound by the size set in configuration
         */
        function cropBounded() {

            jQuery('#cropbox').Jcrop({
                aspectRatio: "",
                setSelect: [ 10, 10, 10+minWidth, 10+minHeight ]
            });
        
            jQuery('#type').val('bounded');
            
        }

        /**
         * Make a thumbnail by cropping whatever the user selected.
         */
        function cropFree() {

            jQuery('#cropbox').Jcrop({
                aspectRatio: "",
                setSelect: [ 10, 10, 10+minWidth, 10+minHeight ]
            });
            
            jQuery('#type').val('free');
            
        }
        

    </script>
</head>
<body>

<?php




require_once($pivotx_path.'modules/module_imagefunctions.php');

// -- main --

if(!$img) {
    $img =  $_GET['image'];
}

// get original image attributes
$attr = get_image_attributes( $img );
$img = new Attributes($attr['name'],$attr['w'],$attr['h'],$attr['x'],$attr['y'], $attr['link']);


if(isset($_GET['crop'])) {
    // create the thumbnail!
    create_thumbnail();
} else {
    // show the JS crop editor!
    print_crop_editor();
}


// -- main --


// Nothing to change from here
// -------------------------------
function get_image_attributes(&$img) {
    global $PIVOTX;

    if (file_exists($img)) {
        $nfo = getImageSize($img);
    } else if(file_exists("../".$img)) {
        $nfo = getImageSize("../".$img);
    } else if(file_exists(stripslashes(urldecode("../".$img)))) {
        $nfo = getImageSize(stripslashes(urldecode("../".$img)));
    } else if(file_exists($PIVOTX['paths']['upload_base_path'].$img)) {
        
        $link = $PIVOTX['paths']['upload_base_url'].$img;
        $img = $PIVOTX['paths']['upload_base_path'].$img;
                
        $nfo = getImageSize($img);
    } else {
        echo "<br />'".htmlspecialchars($img)."' " . __("can not be opened") . ". <br />";
        echo __("Current path") . ": " . str_replace($PIVOTX['paths']['site_path'], '[site_root]/', getcwd()) . "<br />";
        die();
    }

    if (empty($link)) {
        $sitepath = stripTrailingSlash($PIVOTX['paths']['site_path']);
        $siteurl = stripTrailingSlash($PIVOTX['paths']['site_url']);
        $link = str_replace($sitepath, $siteurl, $img);   
    }

    $result = array('name'=>$img,'w'=>$nfo[0],'h'=>$nfo[1],'x'=>0,'y'=>0,'extra'=>$nfo, 'link'=>$link);

    return $result;

}



function create_thumbnail() {
    global $img;
    
    //echo "<pre>\n"; print_r($_GET); echo "</pre>";

    $thumb = new Image($_GET['crop'],$_GET['crop_w'],$_GET['crop_h'],$_GET['crop_x'],$_GET['crop_y'], $_GET['type']);

    $ext = strtolower($img->ext);

    if( ($ext == 'gif') || ($ext == 'jpg') || ($ext == 'jpeg') || ($ext == 'png') ) {
        gd_crop($thumb);
    } else {
        echo __("This file extension is not supported, please try JPG, GIF or PNG");
        print_module_footer();
    }
}



class Image {
    var $name, $w, $h, $x, $y, $type;

    function Image($n,$w,$h,$x,$y,$type,$link)
    {
        $this->name = $n;
        $this->w    = $w;
        $this->h    = $h;
        $this->x    = $x;
        $this->y    = $y;
        $this->type = $type;
        $this->link = $link;
    }
}



class Attributes extends Image {

    var $ext, $new_name, $org_name;

    function Attributes($n,$w,$h,$x,$y, $link)
    {
        $this->Image($n,$w,$h,$x,$y, $type, $link);

        $this->ext = getExtension($this->name);
        if ($this->ext != '') {
            $this->new_name = makeThumbname($_GET['image']);
            $this->org_name = $_GET['image'];
        } else {
            printf(__("Error creating thumbnail for %s - no file extension found."), $this->name);
            die();
        }
    }
}




function print_crop_editor() {
    global $host, $img, $PIVOTX;

    $w = $img->w;
    $h = $img->h;
    $mw = $PIVOTX['image']['mw'];
    $mh = $PIVOTX['image']['mh'];
    $filename = $img->name;
    $filelink = $img->link;

?>

    <script type="text/javascript" charset="utf-8">

    var minWidth = 100;
    var maxWidth = <?php echo $w ?>;
    var minHeight = 100;
    var maxHeight = <?php echo $h ?>;
    var setW = <?php echo $PIVOTX['image']['mw'] ?>;
    var setH = <?php echo $PIVOTX['image']['mh'] ?>;

    jQuery(function(){
        cropFixed();
    });


    </script>

    <h1 style="padding: 6px; margin: 0 0 10px;  border-bottom: 1px solid #AAA;">
        <?php _e("PivotX thumbnail creator"); ?>: 
        <b>'<?php echo basename($filename);  ?>'</b>
    </h1>

    <div id="testWrap" style="float:left;">
        <img src="<?php echo $filelink; ?>" alt="Cropping image" id="cropbox" />
    </div>

    <div style="float:left; padding-left: 10px;">

    <div id="cropdetails">&nbsp;</div>

    <p>
        <strong><?php _e("Crop type"); ?>:</strong>
    </p>
    
    <p>
        <input type="radio" onclick="cropFixed();" name="cropType" id="cropFixed" value="1" checked="checked" />
        <label for="cropFixed"><?php _e("Fixed Proportions"); ?></label><br />
        <input type="radio" onclick="cropBounded();" name="cropType" id="cropBounded" value="2" />
        <label for="cropBounded"><?php _e("Bounded Size"); ?></label><br />
        <input type="radio" onclick="cropFree();" name="cropType" id="cropFree" value="3" />
        <label for="cropFree"><?php _e("Free Crop"); ?></label><br />
    </p>

    <p>
        <?php _e("Target thumbnail will be"); ?>: <span id='resultWidth'><?php echo $PIVOTX['image']['mw'] ?></span> &times; 
        <span id='resultHeight'><?php echo $PIVOTX['image']['mh'] ?></span> <?php _e("pixels"); ?>.
    </p>

    <p>
        <form action="module_image.php">

            <input type="hidden" name="image" value="<?php echo $img->org_name; ?>" />
            <input type="hidden" name="crop" value="<?php echo $img->new_name; ?>" />
            <input type="hidden" name="ext" value="<?php echo $img->ext; ?>" />
            <input type="hidden" name="redir" value="1" />
            
            <input type="hidden" name="crop_x" id="crop_x" />
            <input type="hidden" name="crop_y" id="crop_y" />
            <input type="hidden" name="crop_w" id="crop_w" />
            <input type="hidden" name="crop_h" id="crop_h" />
            <input type="hidden" name="type" id="type" />
            <input type="hidden" name="factor" id="factor" value="<?php echo $factor; ?>" />
            <input type="submit" value="<?php _e("Create Thumbnail"); ?>" />
        </form>
    </p>

</div>

<?php
}


function print_module_footer () {

    global $img;

    printf("    <div style='float:left; padding-left: 20px;'>&rarr; %s <a href=\"javascript:history.go(-1);\">%s</a>, %s.<br />\n", 
        __("Go"), __("Back"), __("if the thumbnail is not satisfactory"));
    //print("&rarr; <a href=\"upload.php\">Upload</a> something else<br />\n");
    printf("&rarr; <a href='javascript:self.close();'>%s</a> %s</div>\n", __("Close"), __("this window"));
    print("<script type='text/javascript'>if(window.opener){
        var pos = 'x' + window.opener.location;
        if ((pos.indexOf('insert_popup'))<1) { window.opener.location.reload();} }</script>");
    echo "</div>";
}

?>
</body>
</html>
