<?php

require_once("../lib.php");

if (!empty($_GET['src'])) {
    $title = $_GET['src'];
} else {
    $title = "";
}

if (isBase64Encoded($title)) { $title = base64_decode($title); }

$width = 480;
if (!empty($_GET['h'])) { $width = intval($_GET['h']); }
if (!empty($_GET['w'])) { $width = intval($_GET['w']); }

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=<?php echo $width; ?>, initial-scale=1, user-scalable=yes" />
        <meta name="apple-mobile-web-app-capable" content="yes">
        <title><?php echo entifyQuotes(strip_tags($title)); ?> - PivotX</title>
        <style type="text/css">
            body, html, img { margin: 0; padding: 0; }
        </style>
    </head>
    <body>
    <img src="timthumb.php?<?php echo entifyQuotes($_SERVER['QUERY_STRING']); ?>" title="<?php echo entifyQuotes(strip_tags($title)); ?>" />
    </body>
</html>
