<?php
// app/deploy/pre-run-check.php

set_time_limit(30);

header('Content-Type: application/json');

$repo_name = $_GET['repo'] ?? null;
$deploy_path = parse_ini_file(__DIR__ . '/../.env')['DEPLOY_PATH'] ?? null;

if (!$repo_name || !$deploy_path) {
    echo json_encode(["error" => "Dados insuficientes para validar."]);
    exit;
}

$project_folder = rtrim($deploy_path, '/') . '/' . $repo_name;

if (!is_dir($project_folder)) {
    echo json_encode(["error" => "Projeto não encontrado no servidor."]);
    exit;
}

// Procura por padrões perigosos
$dangerous = ['while(true)', 'eval(', 'shell_exec(', 'exec(', 'system('];
$danger_found = [];

$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($project_folder));

foreach ($rii as $file) {
    if (!$file->isDir()) {
        $contents = file_get_contents($file->getPathname());
        foreach ($dangerous as $pattern) {
            if (strpos($contents, $pattern) !== false) {
                $danger_found[] = [
                    "file" => $file->getPathname(),
                    "pattern" => $pattern
                ];
            }
        }
    }
}

if (empty($danger_found)) {
    echo json_encode(["message" => "Nenhum padrão perigoso encontrado. Seguro para executar."]);
} else {
    echo json_encode(["alert" => "Padrões perigosos detectados!", "details" => $danger_found]);
}
?>
