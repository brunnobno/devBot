<?php
// app/deploy/debug_projects.php

// Define que a resposta será JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Caminho correto para o .env principal
$env_path = __DIR__ . '/../.env'; // Sobe 1 nível para 'app/.env'

if (!file_exists($env_path)) {
    echo json_encode(["error" => ".env não encontrado em $env_path."]);
    exit;
}

$env = parse_ini_file($env_path);
$deploy_path = $env['DEPLOY_PATH'] ?? null;

if (!$deploy_path) {
    echo json_encode(["error" => "Variável DEPLOY_PATH não configurada no .env."]);
    exit;
}

if (!is_dir($deploy_path)) {
    echo json_encode([
        "error" => "Diretório de DEPLOY_PATH não encontrado.",
        "path" => $deploy_path
    ]);
    exit;
}

// Função para listar os projetos (diretórios)
function listProjects($path)
{
    $projects = [];
    foreach (scandir($path) as $item) {
        if ($item === '.' || $item === '..') continue;
        if (is_dir($path . DIRECTORY_SEPARATOR . $item)) {
            $projects[] = $item;
        }
    }
    return $projects;
}

// Resultado final
$result = [
    "deploy_path" => $deploy_path,
    "projects" => listProjects($deploy_path)
];

echo json_encode($result, JSON_PRETTY_PRINT);
