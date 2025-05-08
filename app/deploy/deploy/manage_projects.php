<?php
// app/deploy/manage_projects.php

// Define que a resposta será JSON
header('Content-Type: application/json');

// Permitir acesso de qualquer origem (CORS liberado para API)
header('Access-Control-Allow-Origin: *');

// Carrega variáveis do .env (caminho correto agora)
$envPath = __DIR__ . '/../.env';

if (!file_exists($envPath)) {
    http_response_code(500);
    echo json_encode(["error" => ".env não encontrado no deploy."]);
    exit;
}

$env = parse_ini_file($envPath);

// Define o caminho onde estão os projetos
$deploy_path = $env['DEPLOY_PATH'] ?? null;

if (!$deploy_path || !is_dir($deploy_path)) {
    http_response_code(500);
    echo json_encode(["error" => "Diretório de projetos ($deploy_path) não configurado corretamente."]);
    exit;
}

// Ação recebida
$action = $_GET['action'] ?? null;

if (!$action) {
    http_response_code(400);
    echo json_encode(["error" => "Nenhuma ação definida."]);
    exit;
}

// Funções auxiliares
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

function deleteProject($path, $project)
{
    $fullPath = $path . DIRECTORY_SEPARATOR . $project;
    if (!is_dir($fullPath)) {
        return ["error" => "Projeto não encontrado."];
    }

    // Deletar recursivamente
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
    }
    rmdir($fullPath);

    return ["success" => "Projeto deletado com sucesso."];
}

// Executa a ação
switch ($action) {
    case 'list_projects':
        echo json_encode(["projects" => listProjects($deploy_path)]);
        break;

    case 'delete_project':
        $project = $_GET['project'] ?? null;
        if (!$project) {
            http_response_code(400);
            echo json_encode(["error" => "Nome do projeto não informado."]);
            exit;
        }
        echo json_encode(deleteProject($deploy_path, $project));
        break;

    default:
        http_response_code(400);
        echo json_encode(["error" => "Ação inválida."]);
}
?>
