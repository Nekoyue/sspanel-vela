<?php

namespace App\Services;

use App\Models\User;

class Auth
{

    protected User $user;

    private static function getDriver(): Auth\Cookie|Redis|Auth\JwtToken
    {
        return Factory::createAuth();
    }

    public static function login($uid, $time): void
    {
        self::getDriver()->login($uid, $time);
    }

    /**
     * Get current user(cached)
     *
     * @return \App\Models\User
     */
    public static function getUser(): \App\Models\User
    {
        global $user;
        if ($user === null) {
            $user = self::getDriver()->getUser();
        }
        return $user;
    }

    public static function logout(): void
    {
        self::getDriver()->logout();
    }
}
