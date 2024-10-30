<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit7c9d2d134e646e5d76ef9a1a5028508e
{
    public static $prefixLengthsPsr4 = array (
        'B' => 
        array (
            'BitPress\\BIT_WC_ZOHO_CRM\\' => 25,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'BitPress\\BIT_WC_ZOHO_CRM\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit7c9d2d134e646e5d76ef9a1a5028508e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit7c9d2d134e646e5d76ef9a1a5028508e::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit7c9d2d134e646e5d76ef9a1a5028508e::$classMap;

        }, null, ClassLoader::class);
    }
}
