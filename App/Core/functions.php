<?php
declare(strict_types=1);

use App\Core\Config;

function config(string $key = null, mixed $default = null): mixed
{
    static $config;

    if ($config === null) {
        $config = new Config(require dirname(__DIR__, 2) . '/config/app.php');
    }

    if ($key === null) {
        return $config->all();
    }

    return $config->get($key, $default);
}