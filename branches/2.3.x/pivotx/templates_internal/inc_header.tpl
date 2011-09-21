<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" [[lang type='xml']] [[lang type='html']]>
<head>

    [[ hook name="head-begin" ]]

    <link rel="shortcut icon" href="[[ $paths.pivotx_url ]]pics/favicon.ico" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="robots" content="noindex, nofollow" />

    <title>PivotX &raquo;
    [[ if $currentpage=="dashboard" || $currentpage=="login" ]] [[ $config.sitename|strip_tags ]] &raquo; [[/if]]
    [[ $title|strip_tags ]]</title>

    <!-- jquery and the likes -->
    <script src="[[$paths.jquery_url]]" type="text/javascript"></script>
    <script src="includes/js/jquery-ui-1.8.16.custom.min.js" type="text/javascript"></script>
    <script src="includes/js/jquery-plugins.js" type="text/javascript"></script>
    <script type="text/javascript">
    jQuery(document).ready(function(){
	humanMsg.setup('body','[[t escape=js]]Message Log[[/t]]');
    });
    </script>

    <link rel="stylesheet" href="templates_internal/ui-theme/jquery-ui-1.8.16.custom.css" type="text/css" />


    <!-- Markitup -->
    <link rel="stylesheet" type="text/css" href="includes/markitup/markitup.css" />
    <script src="includes/markitup/jquery.markitup.js" type="text/javascript"></script>
    <script src="includes/markitup/set.js" type="text/javascript"></script>

    <!-- Thickbox -->
    <script src="includes/js/thickbox.js" type="text/javascript"></script>
    <link rel="stylesheet" href="templates_internal/assets/thickbox.css" type="text/css" />

    <!-- Formclass library -->
    <link rel="stylesheet" type="text/css" href="templates_internal/assets/formclass.css" />

    <!-- PivotX -->
    <script src="includes/js/pivotx.js" type="text/javascript"></script>
    <link rel="stylesheet" type="text/css" href="templates_internal/assets/pivotx.css"/>

    [[ hook name="head-end" ]]

</head>

[[if !$online]]
<body class="website-offline">
[[else]]
<body>
[[/if]]

    [[ hook name="body-begin" ]]


<div id="header">

    [[ hook name="logo-before" ]]
    <div id="logo">
        <a href="[[ $paths.pivotx_url ]]index.php"><img src="templates_internal/assets/pivotx.png" alt="PivotX" /></a>
    </div>
    [[ hook name="logo-after" ]]


    [[ hook name="sitename-before" ]]
    <div id="sitenamediv">
        <a href="[[ $paths.site_url ]]" [[if !$config.front_end_links_same_window]]target="_blank"[[/if]] class="front_end">[[ $config.sitename ]]</a>
    </div>
    [[ hook name="sitename-after" ]]


    [[ hook name="usermenu-before" ]]
    <div id="usermenu">
        <span class="website-offline"><strong>[[t]]Website is OFFLINE[[/t]]</strong> | </span>
        [[if $user.username=="" ]]
            [[t]]Welcome, unknown user.[[/t]]
        [[ else ]]
            [[t]]Welcome back[[/t]], [[ $user.nickname ]]
            - <a href="index.php?page=myinfo">[[t]]My Info[[/t]]</a>
            - <a href="index.php?page=logout">[[t]]Logout[[/t]]</a>
        [[/if]]
    </div>
    [[ hook name="usermenu-after" ]]


    [[ hook name="mainmenu-before" ]]
    <!-- main menu -->


    <ul id="mainmenu" class="sf-menu sf-navbar">

        <!-- Current page: [[$currentpage]] -->
[[      foreach from=$menu item=item ]]
        <li[[if in_array($currentpage,$item.all_pages)]] class="current parent"[[/if]]>
            <a href="[[$item.href]]" title="[[$item.description]]"><span>[[$item.name]]</span></a>
[[          if $item.have_menu ]]
            <ul>
[[              foreach from=$item.menu item=subitem name=submenu ]]
[[                  if $subitem.is_divider ]]
                <li class="divider">&#160;</li>
[[                  else ]]
                <li class="[[if in_array($currentpage,$subitem.all_pages)]]current[[/if]][[if $smarty.foreach.submenu.last]]last[[/if]]">
[[                      if $subitem.have_menu ]]
                    <a class="sf-with-ul" href="#" title="[[$subitem.description]]">[[$subitem.name]]</a>
                    <ul>
[[                          foreach from=$subitem.menu item=subsubitem ]]
                        <li><a href="[[$subsubitem.href]]" title="[[$subsubitem.description]]">[[$subsubitem.name]]</a></li>
[[                          /foreach ]]
                    </ul>
[[                      else ]]
                    <a href="[[$subitem.href]]" title="[[$subitem.description]]" [[if !empty($subitem.target_blank)]]target="_blank"[[/if]]>[[$subitem.name]]</a>
[[                      /if ]]
                </li>
[[                  /if ]]
[[              /foreach ]]
            </ul>
[[          /if ]]
        </li>
[[      /foreach ]]

    </ul>

    <!-- end of main menu -->
    [[ hook name="mainmenu-after" ]]


</div>

[[ hook name="content-before" ]]
<div id="content">
    [[ hook name="content-begin" ]]


    [[ hook name="title-before" ]]

    [[ if $skiptitle!=true ]]
        <h1>
            [[ $title ]]
            [[ if $entry.title != ""]]<span> &raquo; [[$entry.title]]</span>[[/if]]
            [[ if $page.title != ""]]<span> &raquo; [[$page.title]]</span>[[/if]]
        </h1>

    [[ /if ]]

    [[ if $heading!=$title && $heading!=""]]
        <h2>[[ $heading ]]</h2>
    [[/if]]

    [[ hook name="title-after" ]]

    [[ hook name="error-before" ]]

    [[if $error!="" ]]
        <div class="errorbanner" id='errorbanner'>
            [[ $error ]]
        </div>
    [[/if]]

    [[ hook name="error-after" ]]

    [[ if is_array($messages) && count($messages)>0 ]]
    <script type="text/javascript">
    //<![CDATA[

    jQuery(function($) {
        [[ foreach from=$messages key=key item=item ]]
        humanMsg.displayMsg("[[ $item|escape ]]");
        [[ /foreach ]]
    });
    //]]>
    </script>
    [[ /if ]]
