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

// don't access directly..
if(!defined('INPIVOTX')){ exit('not in pivotx'); }

$self = $_SERVER['PHP_SELF']; 
// Using global variable $PIVOTX to store image settings
$PIVOTX['image'] = array();
$PIVOTX['image']['mw'] = getDefault($PIVOTX['config']->get('upload_thumb_width'), 200);
$PIVOTX['image']['mh'] = getDefault($PIVOTX['config']->get('upload_thumb_height'), 200);
$PIVOTX['image']['qual'] = getDefault($PIVOTX['config']->get('upload_thumb_quality'), 70);
$PIVOTX['image']['local'] = true; 

// Check if GD is installed and with the right version
if(!extension_loaded('gd')) {
    $PIVOTX['image']['local'] = false;
    die("Creating thumbs remotely since GD isn't installed/loaded.");
} else {
    $gd_version_info = gd_info();
    preg_match('/\d/', $gd_version_info['GD Version'], $match);
    $gd_version_number = $match[0];
    if($gd_version_number=="1") {
        $PIVOTX['image']['local'] = false;
        debug("Creating thumbs remotely since only GD version 1 is installed.");
    } elseif($gd_version_number=="2") {
        $PIVOTX['image']['local'] = true;
    }
}

/*
// a quick hack, to revert to remote cropping, if imagecreatefromjpeg does not exist..
if ( (!function_exists('imagecreatefromjpeg')) || (!function_exists('imagecreatetruecolor')) ) {
    $PIVOTX['image']['local'] = FALSE;
} */


function gd_crop($thumb) {
    global $img, $PIVOTX;
    
    $sx     = $thumb->x;
    $sy     = $thumb->y;
    $sw     = $thumb->w;
    $sh     = $thumb->h;

    $scalew = $sw / $PIVOTX['image']['mw'];
    $scaleh = $sh / $PIVOTX['image']['mh'];

    if ($thumb->type=="bounded") {
        $factor = $_GET['factor'];
    } else if ($thumb->type=="free") {
        $factor = 1;
    } else {
        $factor = max($scalew,$scaleh);
    }

    $dx     = 0;
    $dy     = 0;
    $dw     = $thumb->w/$factor;
    $dh     = $thumb->h/$factor;

    $ext = strtolower($img->ext);

    printf("<div id='editor'><h1 style=\"padding: 6px; margin: 0 0 10px;  border-bottom: 1px solid #AAA;\">%s:  <b>'%s'</b></h1>\n", 
        __("PivotX thumbnail creator"), basename($img->name));

    if( $ext == 'gif' ) {
        echo "<small style='color:red;'>(".
            __("When using GIF files, there is a significant chance that you can't use PivotX to make thumbnails. If you have problems with making thumbnails, we suggest using PNG or JPG files.").
            ")</small><br /><br />\n";
    }

    if( !in_array($ext, array('gif', 'jpg', 'png', 'jpeg'))) {
        echo "<strong>" . __("You can only make thumbnails of .gif, .jpg and .png images with PivotX.") . "</strong>\n";
            die();
    }
    $filename = $img->name;
    
    $sitepath = stripTrailingSlash($PIVOTX['paths']['site_path']);
    
    // Check if the base path is already in $_GET['crop']..
    if (strpos($_GET['crop'], $sitepath)===0){
        $thumbfilename = $_GET['crop'];
        $siteurl = stripTrailingSlash($PIVOTX['paths']['site_url']);
        $thumblink = str_replace($sitepath, $siteurl, $_GET['crop']);
    } else {
        $thumbfilename = $PIVOTX['paths']['upload_base_path'].$_GET['crop'];
        $thumblink = $PIVOTX['paths']['upload_base_url'].$_GET['crop'];
    }


    if($PIVOTX['image']['local']) {

        if($ext == "jpeg") { $ext="jpg"; }

        if($ext == "jpg") { $src = imagecreatefromjpeg($filename); }
        if($ext == "png") { $src = imagecreatefrompng($filename); }
        if($ext == "gif") { $src = imagecreatefromgif($filename); }


        if(function_exists('imagecreatetruecolor')) {
            $dst = imagecreatetruecolor($dw, $dh);
            $tmp_img = imagecreatetruecolor($sw, $sh);
        } else {
            $dst = imagecreate($dw, $dh);
        }

        if (function_exists('imagecopyresampled')) {
            // GD 2.0 has a bug that ignores the 'source_x' and 'source_y'..
            // to compensate, we use a temp image..
            imagecopy ($tmp_img, $src,0,0,$sx,$sy, $sw,$sh);
            imagecopyresampled($dst,$tmp_img,0,0,0,0,$dw,$dh,$sw,$sh);
        } else {
            imagecopyresized($dst,$src,0,0,$sx,$sy,$dw,$dh,$sw,$sh);
        }

        if($ext == "jpg") {
            imagejpeg($dst, $thumbfilename, $PIVOTX['image']['qual']);
        }

        if($ext == "png") {
            imagepng($dst, $thumbfilename, ceil( $PIVOTX['image']['qual'] / 10 ));
        }

        // Ensure the created thumb has the correct file permission.
        chmodFile($thumbfilename);

        ImageDestroy($dst);

    } else {

        $remotefile = str_replace($PIVOTX['paths']['upload_base_path'], $PIVOTX['paths']['upload_base_url'], $filename);
        $remotefile = sprintf("%s%s", $PIVOTX['paths']['host'], urlencode($remotefile));

        $remoteurl = getDefault($PIVOTX['config']->get('remote_crop_script'), 
            'http://www.mijnkopthee.nl/remote/crop.php');

        $remote = sprintf('%s?img=%s&dx=%s&dy=%s&sx=%s&sy=%s&dw=%s&dh=%s&sw=%s&sh=%s&ext=%s', 
            $remoteurl,$remotefile,$dx,$dy,$sx,$sy,$dw,$dh,$sw,$sh,$img->ext);

        if (@$fp = fopen($remote,"rb")) {
            $handle = fopen($thumb->name,"wb");
            while (!feof($fp)) {
              fwrite($handle,fread($fp, 8192) );
            }
            fclose($handle);
            fclose($fp);
        } else {
            echo "<p><strong>" . __("Couldn't make thumbnail remotely using") . " $remoteurl</strong></p>";
        }

    }


    srand ((double) microtime() * 1000000);
    $rand = rand(10000, 99999);
    echo '<div id="testWrap" style="float:left;">';
    
    printf("<img src='%s?%s' alt='%s'><br />\n", $thumblink, $rand, $thumblink);
    echo "</div>";
    print_module_footer();
}




/**
 * Creates a thumbnail using the GD library.
 *
 * Currently only JPEG and PNG is supported (in the GD library).
 *
 * @param string $imagename
 * @return boolean
 */
function auto_thumbnail($imagename, $folder='', $action='upload', $maxsize='0') {
    global $PIVOTX;

    // If we can't create thumbnails locally or we haven't enabled "Automatic Thumbnails", 
    // we don't automatically make a thumbnail..
    // Action not Upload --> other function trying to create a thumb so disregard upload_autothumb
    if (!$PIVOTX['image']['local'] || ($action == 'upload' && !$PIVOTX['config']->get('upload_autothumb'))) {
        return FALSE;
    }

    $ext = strtolower(getExtension($imagename));
    if($ext == "jpeg") { $ext="jpg"; }

    $thumbname = makeThumbname(basename($imagename));

    if ($folder == '') {
        $folder = $PIVOTX['paths']['upload_path'];
    }
    $filename = $folder . $imagename;
    $thumbfilename = $folder . $thumbname;

    // check whether the file exists (and stop continuing to avoid warnings)
    if (!file_exists($filename)) {
        debug("Can not auto create thumb for ".basename($filename)." - file does not exist.");
        return FALSE;
    }


    $width = $PIVOTX['image']['mw'];
    $height = $PIVOTX['image']['mh'];

    // We are current only handling JPEG and PNG.
    if ($ext == "jpg") {
        $src = ImageCreateFromJPEG($filename);
    } elseif ($ext == "png") {
        $src = ImageCreateFromPNG($filename);
    } else {
        debug("Can not auto create thumb for ".basename($filename)." - unsupported extension.");
        return FALSE;
    }

    list($curwidth, $curheight) = getimagesize($filename);

    // When Fancybox calls and maxsize is specified then maxthumb was specified in FB
    if ($action == 'Fancybox' && $maxsize != '0') {
        if ($curwidth > $curheight) {
            $height = round($curheight * ($maxsize / $curwidth));
            $width = $maxsize;
        } else {
            $width = round($curwidth * ($maxsize / $curheight));
            $height = $maxsize;   
        }
    }

    $scale = min ( ($curheight / $height), ($curwidth / $width) );

    if(function_exists('ImageCreateTrueColor')) {
        $dst = ImageCreateTrueColor($width,$height);
    } else {
        $dst = ImageCreate($width,$height);
    }

    $startx = ( ($width / 2) - ($curwidth / 2 / $scale) );
    $endx = ( ( $width / 2) + ($curwidth / 2 / $scale)  ) - $startx;
    $starty = ( ($height / 2) - (($curheight / 2) / $scale) );
    $endy = ( ($height / 2) + (($curheight / 2) / $scale)) - $starty;

    ImageCopyResampled($dst, $src, $startx, $starty, 0, 0, $endx, $endy, $curwidth, $curheight);

    if($ext == "jpg") {
        ImageJPEG($dst,$thumbfilename,$PIVOTX['image']['qual']);
    } elseif($ext == "png") {
        ImagePNG($dst,$thumbfilename,$PIVOTX['image']['qual']);
    }
    chmodFile($thumbfilename);

    ImageDestroy($src);
    ImageDestroy($dst);

    return TRUE;
}

?>
