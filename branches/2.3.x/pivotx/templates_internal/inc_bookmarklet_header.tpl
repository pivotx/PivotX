<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" id="bookmarklet">
<head>
    <meta http-equiv="Content-Type"content="text/html; charset=utf-8"/>

    <title>PivotX &raquo; [[ $title ]]</title>

    <!-- jquery and the likes -->
    <script src="[[$paths.jquery_url]]" type="text/javascript"></script>
    <script src="[[$paths.jquery_plugins_url]]" type="text/javascript"></script>



    <!-- Formclass library -->
    <link rel="stylesheet" type="text/css" href="[[$paths.pivotx_url]]templates_internal/assets/formclass.css" />


    <!-- PivotX -->
    <script src="[[$paths.pivotx_url]]includes/js/pivotx.js" type="text/javascript"></script>
        
    <script language="javascript" type="text/javascript" src="[[ $paths.pivotx_url ]]editor_wysi/tiny_mce_gzip.js"></script>
   
    
    <link rel="stylesheet" type="text/css" href="[[$paths.pivotx_url]]templates_internal/assets/pivotx.css"/>
    <!--[if lte IE 6]>
        <link rel="stylesheet" type="text/css" href="[[$paths.pivotx_url]]templates_internal/assets/pivotx_ie.css"/>
    <![endif]-->

</head>
<body>
    <div id="bookmarkletlogo">
        <span>[[if $user.username=="" ]]
            [[t]]Welcome, unknown user.[[/t]]
        [[ else ]]
            [[t]]Welcome back[[/t]], [[ $user.nickname ]]
        [[/if]]
        [<em>[[ sitename ]]</em>]</span>
        <a href="index.php"><img src="templates_internal/assets/bookmarklet_logo.gif" alt="PivotX" width="116" height="22" /></a>
    </div>
