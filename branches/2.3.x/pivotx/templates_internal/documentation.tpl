<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" id="documentation">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta name="robots" content="noindex, nofollow" />
<link rel="stylesheet" type="text/css" href="templates_internal/assets/pivotx_docs.css"/>
<title>[[t]]Documentation[[/t]] - [[$title]]</title>
</head>
<body>
[[if $title!=""]]
    <div id="header">
        <h1>[[$title]]</h1>
    </div>
[[/if]]
<div id="docs">

    [[ if $toc ]]
    <div id="toc">
        <strong>[[t]]Table of Contents[[/t]]</strong>
        [[ $toc ]]
    </div>
    [[/if]]

    [[$html]]

-- 
</div>
</body>
</html>
