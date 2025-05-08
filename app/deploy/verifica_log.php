<?php
// verifica_log.php

$log_dir = __DIR__ . '/logs';
$log_file = $log_dir . '/deploy_' . date('Y-m-d') . '.log';

header('Content-Type: text/plain');

if (!file_exists($log_file)) {
    echo "Log de hoje não encontrado: $log_file";
    exit;
}

readfile($log_file);