<?php

namespace App\Utils;

class Cookie
{
    public static function set($arg, $time): void
    {
        foreach ($arg as $key => $value) {
            setcookie($key, $value, $time, '/');
        }
    }

    public static function setwithdomain($arg, $time, $domain): void
    {
        foreach ($arg as $key => $value) {
            setcookie($key, $value, $time, '/', $domain);
        }
    }

    public static function get($key)
    {
        return $_COOKIE[$key] ?? '';
    }
}
