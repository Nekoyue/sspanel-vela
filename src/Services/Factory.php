<?php

namespace App\Services;

use App\Services\Auth\Cookie;
use App\Services\Auth\JwtToken;

class Factory
{
    public static function createAuth(): Cookie|Redis|JwtToken
    {
        $method = $_ENV['authDriver'];
        switch ($method) {
            case 'cookie':
                return new Cookie();
            case 'jwt':
                return new JwtToken();
        }
        return new Redis();
    }
}
