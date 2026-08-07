<?php
/**
 * Minimal PSR-4 autoloader for the vendored phpseclib 3.x + ParagonIE\ConstantTime.
 *
 * phpseclib normally arrives via composer; WaSQL does not use composer for extras,
 * so the two packages are vendored here and mapped by hand:
 *
 *   phpseclib3\*               -> phpseclib3/phpseclib/*
 *   ParagonIE\ConstantTime\*   -> phpseclib3/ParagonIE/ConstantTime/*
 *
 * Safe to include more than once, and it will not fight a composer autoloader that
 * has already registered the same classes (it only claims classes it can resolve).
 *
 * Versions vendored: phpseclib 3.0.56, paragonie/constant_time_encoding 2.8.2
 * (constant_time v2 rather than v3 so this still runs on PHP 7.x sites.)
 */

if (!defined('PHPSECLIB3_AUTOLOAD_REGISTERED')) {
    define('PHPSECLIB3_AUTOLOAD_REGISTERED', 1);

    spl_autoload_register(function ($class) {
        static $prefixes = null;
        if ($prefixes === null) {
            $prefixes = array(
                'phpseclib3\\'             => __DIR__ . '/phpseclib/',
                'ParagonIE\\ConstantTime\\' => __DIR__ . '/ParagonIE/ConstantTime/',
            );
        }
        foreach ($prefixes as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($class, $prefix, $len) !== 0) { continue; }
            $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
            if (is_file($file)) { require $file; return; }
        }
    });

    require_once __DIR__ . '/phpseclib/bootstrap.php';
}
