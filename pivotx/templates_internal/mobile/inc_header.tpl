<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" [[lang type='xml']] [[lang type='html']]>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="robots" content="noindex, nofollow" />
    <meta name="viewport" id="iphone-viewport" content="width=320, maximum-scale=1" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <link rel="shortcut icon" href="[[ $paths.pivotx_url ]]pics/favicon.ico" />
    <link rel="apple-touch-icon" href="[[ $paths.pivotx_url ]]pics/apple-touch-icon.png" />
		

    <title>PivotX &raquo; 
    [[ if $currentpage=="dashboard" || $currentpage=="login" ]] [[ $config.sitename|strip_tags ]] &raquo; [[/if]]
    [[ $title|strip_tags ]]</title>

    <!-- jquery and the likes -->
    <script src="[[$paths.jquery_url]]" type="text/javascript"></script>



    <!-- PivotX -->
    <script src="includes/js/m_pivotx.js" type="text/javascript"></script>
    <link rel="stylesheet" type="text/css" href="templates_internal/assets/tripoli.simple.css"/>
    <link rel="stylesheet" type="text/css" href="templates_internal/assets/m_pivotx.css"/>

</head>

<body>

<div id="wrap">
    
<div id="header" class="content">

    <div id="logo">
        <a href="[[ $paths.pivotx_url ]]index.php?page=m_dashboard"><img src="templates_internal/assets/m_pivotx.png" alt="PivotX" /></a>
    </div>




    <div id="usermenu">
        [[if $user.username=="" ]]
            [[t]]Welcome, unknown user.[[/t]]
        [[ else ]]
            [[t]]Welcome back[[/t]], [[ $user.nickname ]]
        [[/if]]
    </div>

    <div class="cleaner">&nbsp;</div>

    <!-- main menu -->
    
    [[ if $user.userlevel>0 ]]
    <div id="mainmenu">
    <ul>
        
            <li [[if $active=="entries"]]class="active"[[/if]]><a href='' rel='entries'>[[t]]Entries[[/t]]</a></li>
            <li [[if $active=="pages"]]class="active"[[/if]]><a href='' rel='pages'>[[t]]Pages[[/t]]</a></li>
            <li [[if $active=="comments"]]class="active"[[/if]]><a href='' rel='comments'>[[t]]Comments[[/t]]</a></li>
            <li [[if $active=="other"]]class="active"[[/if]]><a href='' rel='other'>[[t]]Other[[/t]]</a></li>               
    
    </ul>
    </div>
    
    <div id="submenu">
    
        <div id="menu-entries">
            <ul>
                <li><a href="[[ $paths.pivotx_url ]]index.php?page=m_entries">[[t]]Overview[[/t]]</a></li>
                <li><a href="[[ $paths.pivotx_url ]]index.php?page=m_editentry">[[t]]New Entry[[/t]]</a></li>
            </ul>
        </div>
    
        <div id="menu-pages">
            <ul>
                <li><a href="[[ $paths.pivotx_url ]]index.php?page=m_pages">[[t]]Overview[[/t]]</a></li>
                <li><a href="[[ $paths.pivotx_url ]]index.php?page=m_editpage">[[t]]New Page[[/t]]</a></li>
            </ul>
        </div>
    
        <div id="menu-comments">
            <ul>
                <li><a href="[[ $paths.pivotx_url ]]index.php?page=m_comments">[[t]]Overview[[/t]]</a></li>
            </ul>
        </div>        
    
        <div id="menu-other">
            <ul>
                <li><a href="[[ $paths.pivotx_url ]]index.php?page=m_logout">[[t]]Logout[[/t]]</a></li>
                <li><a href="../">[[t]]View site[[/t]]</a></li>
                <li><a href="[[ $paths.pivotx_url ]]index.php?page=m_about">[[t]]About PivotX[[/t]]</a></li>
            </ul>
        </div>    
    
    </div>    
    
    [[ else ]]
        &nbsp;
    [[ /if ]] 


    <!-- end of main menu -->


</div>


<div id="content" class="content">

    [[ if $skiptitle!=true ]]
        <h1>
            [[ $title ]]
            [[ if $entry.title != ""]]<span> &raquo; [[$entry.title|trimlen:28|hyphenize ]]</span>[[/if]]
            [[ if $page.title != ""]]<span> &raquo; [[$page.title|trimlen:28|hyphenize ]]</span>[[/if]]
        </h1>
        
    [[ /if ]]

    [[ if $heading!=$title && $heading!="" ]]
        <h2>[[ $heading ]]</h2>
    [[/if]]


    [[if $error!="" ]]
        <div class="errorbanner" id='errorbanner'>
            [[ $error ]]
        </div>
    [[/if]]


    [[ if is_array($messages) && count($messages)>0 ]]
    <div class="messages" id="messages">
    
        [[ foreach from=$messages key=key item=item ]]
        <p>[[ $item ]]</p>
        [[ /foreach ]]
    </div>
    [[ /if ]]
    
