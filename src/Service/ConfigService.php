<?php

namespace Fusio\Adapter\Http\Service;

use Symfony\Component\Dotenv\Dotenv;

class ConfigService
{
    private static ?Dotenv $dotenv;
    public static function enval(string $key, mixed $defaultValue): mixed
    {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        if (self::$dotenv === null) {
            self::$dotenv = new Dotenv();
            self::$dotenv->load(__DIR__.'/.env');
        }

        return $_ENV[$key] ?? $defaultValue;
    }
}