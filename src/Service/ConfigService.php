<?php

namespace Fusio\Adapter\Http\Service;

class ConfigService
{
    public static function enval(string $key, mixed $defaultValue): mixed
    {
        return $_ENV[$key] ?? $defaultValue;
    }
}