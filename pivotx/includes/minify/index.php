<?php
/**
 * Front controller for default Minify implementation
 * 
 * DO NOT EDIT! Configure this utility via config.php and groupsConfig.php
 * 
 * @package Minify
 */

define('MINIFY_MIN_DIR', dirname(__FILE__));

// load config
require MINIFY_MIN_DIR . '/config.php';

// setup include path
set_include_path($min_libPath . PATH_SEPARATOR . get_include_path());

require 'Minify.php';

Minify::$uploaderHoursBehind = $min_uploaderHoursBehind;
Minify::setCache(
    isset($min_cachePath) ? $min_cachePath : ''
    ,$min_cacheFileLocking
);

if ($min_documentRoot) {
    $_SERVER['DOCUMENT_ROOT'] = $min_documentRoot;
} elseif (0 === stripos(PHP_OS, 'win')) {
    Minify::setDocRoot(); // IIS may need help
}

if (isset($min_symlinks)) {
    $min_serveOptions['minifierOptions'][Minify::TYPE_CSS]['symlinks'] = $min_symlinks;
}

// Disable minifying of Javascript since it generates invalid code
// with already minified jQuery 1.8.2 and jQuery UI 1.9.1.
// (This is OK since Minify will combine the files.)
$min_serveOptions['minifiers'][Minify::TYPE_JS] = '';

if ($min_allowDebugFlag && isset($_GET['debug'])) {
    $min_serveOptions['debug'] = true;
}

if ($min_errorLogger) {
    require_once 'Minify/Logger.php';
    if (true === $min_errorLogger) {
        require_once 'FirePHP.php';
        Minify_Logger::setLogger(FirePHP::getInstance(true));
    } else {
        Minify_Logger::setLogger($min_errorLogger);
    }
}

// check for URI versioning
if (preg_match('/&\\d/', $_SERVER['QUERY_STRING'])) {
    $min_serveOptions['maxAge'] = 31536000;
}
if (isset($_GET['g'])) {
    // well need groups config
    $min_serveOptions['minApp']['groups'] = (require MINIFY_MIN_DIR . '/groupsConfig.php');
}
if (isset($_GET['f']) || isset($_GET['g'])) {
    // serve!   
    Minify::serve('MinApp', $min_serveOptions);
        
} elseif ($min_enableBuilder) {
    header('Location: builder/');
    exit();
} else {
    header("Location: /");
    exit();
}
