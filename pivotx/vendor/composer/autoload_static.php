<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitf180f69d7a522e1b43e75e7397bbbd53
{
    public static $prefixLengthsPsr4 = array (
        'N' => 
        array (
            'Netcarver\\Textile\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Netcarver\\Textile\\' => 
        array (
            0 => __DIR__ . '/..' . '/netcarver/textile/src/Netcarver/Textile',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitf180f69d7a522e1b43e75e7397bbbd53::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitf180f69d7a522e1b43e75e7397bbbd53::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
