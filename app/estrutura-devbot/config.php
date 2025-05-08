<?php
// config.php

function getEnvVar($key, $default = null) {
    $envPath = dirname(__DIR__) . '/.env';
    if (!file_exists($envPath)) return $default;
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        if ($k === $key) return $v;
    }
    return $default;
}

define('GITHUB_TOKEN', getEnvVar('GITHUB_TOKEN'));
define('GITHUB_REPO', getEnvVar('GITHUB_REPO'));
define('GITHUB_USER', getEnvVar('GITHUB_USER'));

define('TMP_DIR', __DIR__ . '/tmp');
define('LOG_DIR', __DIR__ . '/logs');
