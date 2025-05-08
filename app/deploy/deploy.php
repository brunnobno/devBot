<?php
// deploy.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Carrega variáveis de ambiente do arquivo principal da aplicação
$env = parse_ini_file(__DIR__ . '/../.env');
$github_token = $env['GITHUB_TOKEN'] ?? null;
$github_user = $env['GITHUB_USER'] ?? null;
$deploy_path = $env['DEPLOY_PATH'] ?? null;

// Validação de configuração
if (!$github_token || !$github_user || !$deploy_path) {
    http_response_code(500);
    echo json_encode(["error" => "Configuração incompleta. Verifique o .env principal em app/.env."]);
    exit;
}

// Valida parâmetro de repositório
if (!isset($_GET['repo']) || empty($_GET['repo'])) {
    http_response_code(400);
    echo json_encode(["error" => "Parâmetro 'repo' obrigatório."]);
    exit;
}

$repo = basename($_GET['repo']);
$repo_url = "https://$github_token@github.com/$github_user/$repo.git";
$target_dir = rtrim($deploy_path, '/') . "/$repo";

// Função para executar comandos e capturar resposta
function executeCommand($cmd)
{
    exec($cmd, $output, $return_var);
    return [
        'output' => implode("\n", $output),
        'status' => $return_var
    ];
}

// Verifica se o diretório de destino já existe
if (is_dir($target_dir)) {
    // Atualizar projeto existente
    $cmd = "cd $target_dir && git pull origin main 2>&1";
    $action = "Atualizado";
} else {
    // Clonar novo projeto
    $cmd = "git clone --depth 1 $repo_url $target_dir 2>&1";
    $action = "Clonado";
}

$result = executeCommand($cmd);

// Trata resultado do comando
if ($result['status'] !== 0) {
    http_response_code(500);
    echo json_encode([
        "error" => "Erro no processo de deploy.",
        "detalhes" => $result['output']
    ]);
    exit;
}

// Retorno de sucesso
http_response_code(200);
$response = [
    "message" => "$action com sucesso.",
    "repositorio" => $repo,
    "diretorio" => $target_dir,
    "log" => $result['output']
];
echo json_encode($response, JSON_PRETTY_PRINT);
