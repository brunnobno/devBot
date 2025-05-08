<?php
// github_api.php

header('Content-Type: application/json');

// Permitir acesso CORS automaticamente
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Carrega variáveis do .env
$env = parse_ini_file(__DIR__ . '/.env');

$github_token = $env['GITHUB_TOKEN'] ?? null;
$github_user = $env['GITHUB_USER'] ?? null;
$github_api = $env['GITHUB_API'] ?? 'https://api.github.com';
$openai_token = $env['OPENAI_API_KEY'] ?? null;
$openai_url = $env['OPENAI_API_URL'] ?? 'https://api.openai.com/v1/chat/completions';

if (!$github_token || !$github_user) {
    http_response_code(500);
    echo json_encode(["error" => "Configuração do GitHub no .env está incompleta."]);
    exit;
}

// Função de log
function logRequest($action, $requestData, $response, $statusCode) {
    $logFile = __DIR__ . '/request_log.txt';
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'request' => json_encode($requestData),
        'response' => json_encode($response),
        'status_code' => $statusCode,
    ];
    file_put_contents($logFile, json_encode($logData) . PHP_EOL, FILE_APPEND);
}

// Função para chamadas ao GitHub
function callGitHubAPI($method, $endpoint, $data = null)
{
    global $github_token, $github_api;

    $url = "$github_api$endpoint";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: token $github_token",
        "User-Agent: DevBot",
        "Accept: application/vnd.github.v3+json",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "[LOG] $method $url - Status $statusCode\n";

if (!$response || $statusCode >= 400) {
    logRequest($method, $data, $response, $statusCode);
    echo json_encode([
        "error" => "Erro na chamada GitHub API",
        "http_code" => $statusCode,
        "raw_response" => $response

        
    ]);
    curl_close($ch);
    exit;
}


    logRequest($method, $data, $response, $statusCode);

    if (curl_errno($ch)) {
        curl_close($ch);
        return ["error" => curl_error($ch)];
    }

    curl_close($ch);

    return [
        "status" => $statusCode,
        "response" => json_decode($response, true)
    ];
}

// Funções GitHub
function healthCheck() { return ["message" => "API conectada e funcionando."]; }

function readFileGitHub($repo, $path)
{
    global $github_user;
    $endpoint = "/repos/$github_user/$repo/contents/$path";
    return callGitHubAPI('GET', $endpoint);
}

function updateFile($repo, $path, $new_content, $commit_message)
{
    $file = readFileGitHub($repo, $path);

    if (isset($file['response']['sha'])) {
        global $github_user;
        $endpoint = "/repos/$github_user/$repo/contents/$path";
        return callGitHubAPI('PUT', $endpoint, [
            "message" => $commit_message,
            "content" => base64_encode($new_content),
            "sha" => $file['response']['sha']
        ]);
    } else {
        return ["error" => "Arquivo não encontrado para atualizar."];
    }
}

function deleteFile($repo, $path, $commit_message)
{
    $file = readFileGitHub($repo, $path);

    if (isset($file['response']['sha'])) {
        global $github_user;
        $endpoint = "/repos/$github_user/$repo/contents/$path";
        return callGitHubAPI('DELETE', $endpoint, [
            "message" => $commit_message,
            "sha" => $file['response']['sha']
        ]);
    } else {
        return ["error" => "Arquivo não encontrado para deletar."];
    }
}

function listRepositories()
{
    $endpoint = "/user/repos";
    return callGitHubAPI('GET', $endpoint);
}

function listAllRepositories()
{
    $endpoint = "/user/repos";
    return callGitHubAPI('GET', $endpoint);
}

// Função para verificar se a branch main existe
function checkBranchExists($repo, $branch = 'main')
{
    global $github_user;
    $endpoint = "/repos/$github_user/$repo/branches/$branch";
    $response = callGitHubAPI('GET', $endpoint);

    if ($response['status'] != 200) {
        // Se a branch não existir, inicializar o repositório
        initializeRepository($repo);
        return false;
    }

    return true;
}

// Função para listar commits do repositório
function listCommits($repo)
{
    global $github_user;
    $endpoint = "/repos/$github_user/$repo/commits";
    $response = callGitHubAPI('GET', $endpoint);

    if ($response['status'] !== 200) {
        return ["error" => "Erro ao listar commits: " . json_encode($response['response'])];
    }

    return $response['response'];
}

// Função para inicializar repositório vazio
function initializeRepository($repo_name)
{
    global $github_user;
    $repo_url = "/repos/$github_user/$repo_name/contents";
    $repo_check = callGitHubAPI('GET', $repo_url);

    if ($repo_check['status'] == 404 || empty($repo_check['response'])) {
        return createFile($repo_name, 'README.md', 'Este é um repositório inicializado automaticamente pelo DevBot.', 'Primeiro commit do repositório.');
    }

    return ["status" => 200, "response" => "Repositório já inicializado."];
}

// Função para criar o repositório com o README.md automaticamente
function createRepository($repo_name, $private = true)
{
    $data = [
        "name" => $repo_name,
        "private" => $private,
        "auto_init" => true // Este parâmetro garante que o repositório será inicializado
    ];
    $response = callGitHubAPI('POST', "/user/repos", $data);

    if ($response['status'] == 201) {
        // Espera 2 segundos para o repositório ser propagado no GitHub
        sleep(2);

        // Verifica se a branch main foi criada
        if (!checkBranchExists($repo_name)) {
            // Cria um README.md se a branch main não existir
            createFile($repo_name, 'README.md', 'Este é um repositório inicializado automaticamente pelo DevBot.', 'Primeiro commit do repositório.');
        }
    }

    return $response;
}

// Função para criar o arquivo index.php após o commit inicial
function createFile($repo, $path, $content, $commit_message)
{
    if (!checkBranchExists($repo)) {
        return ["error" => "Branch main não disponível para criar arquivos."];
    }

    global $github_user;
    $endpoint = "/repos/$github_user/$repo/contents/$path";
    return callGitHubAPI('PUT', $endpoint, [
        "message" => $commit_message,
        "content" => base64_encode($content),
        "branch" => "main"
    ]);
}

function deleteRepository($repo_name)
{
    global $github_user;
    $endpoint = "/repos/$github_user/$repo_name";
    return callGitHubAPI('DELETE', $endpoint);
}

// Função para listar todas as branches de um repositório
function listBranches($repo)
{
    global $github_user;
    $endpoint = "/repos/$github_user/$repo/branches";
    $response = callGitHubAPI('GET', $endpoint);

    if ($response['status'] !== 200) {
        return ["error" => "Erro ao listar branches: " . json_encode($response['response'])];
    }

    return $response['response'];
}

// Função para listar arquivos do repositório
function listFiles($repo, $path = '')
{
    // Primeiro, tenta listar todas as branches
    $branches = listBranches($repo);

    if (isset($branches['error'])) {
        return ["error" => "Erro ao listar branches: " . json_encode($branches)];
    }

    // Define a branch padrão
    $branch = 'main'; // padrão esperado
    if (!in_array('main', array_column($branches, 'name'))) {
        // Se não tiver 'main', pega a primeira branch disponível
        $branch = $branches[0]['name'] ?? null;
    }

    if (!$branch) {
        return ["error" => "Nenhuma branch disponível no repositório."];
    }

    // Agora listar os arquivos baseado na branch correta
    global $github_user;
    $endpoint = "/repos/$github_user/$repo/contents/$path?ref=$branch";
    $response = callGitHubAPI('GET', $endpoint);

    if ($response['status'] !== 200) {
        return ["error" => "Erro ao listar arquivos: " . json_encode($response['response'])];
    }

    if (empty($response['response'])) {
        return ["message" => "O repositório está vazio ou não possui arquivos visíveis."];
    }

    return $response['response'];
}

// Processamento da requisição
$request = json_decode(file_get_contents('php://input'), true);

if (!$request || !isset($request['action'])) {
    echo json_encode(["error" => "Nenhuma ação definida."]);
    exit;
}

$action = $request['action'];
$repo = $request['repo'] ?? null;

// Roteador de ações
switch ($action) {
    case 'healthcheck':
        echo json_encode(healthCheck());
        break;
    case 'create_file':
        echo json_encode(createFile($repo, $request['path'], $request['content'], $request['commit_message']));
        break;
    case 'read_file':
        echo json_encode(readFileGitHub($repo, $request['path']));
        break;
    case 'update_file':
        echo json_encode(updateFile($repo, $request['path'], $request['new_content'], $request['commit_message']));
        break;
    case 'delete_file':
        echo json_encode(deleteFile($repo, $request['path'], $request['commit_message']));
        break;
    case 'list_files':
        echo json_encode(listFiles($repo, $request['path'] ?? ''));
        break;
    case 'create_branch':
        echo json_encode(createBranch($repo, $request['new_branch'], $request['source_branch'] ?? 'main'));
        break;
    case 'merge_branch':
        echo json_encode(mergeBranch($repo, $request['base_branch'], $request['head_branch'], $request['commit_message']));
        break;
    case 'list_repositories':
        echo json_encode(listRepositories());
        break;
    case 'list_all_repositories':
        echo json_encode(listAllRepositories());
        break;
    case 'create_repository':
        echo json_encode(createRepository($request['repo_name'], $request['private'] ?? true));
        break;
    case 'delete_repository':
        echo json_encode(deleteRepository($request['repo_name']));
        break;
    case 'list_branches':    # <-- 🔥 Adicione isso aqui!
        echo json_encode(listBranches($repo));
        break;
    case 'list_commits':     # (Se tiver a função depois, já deixa reservado também!)
        echo json_encode(listCommits($repo));
        break;
    case 'generate_code':
        echo json_encode(callOpenAIAPI($request['prompt']));
        break;
    default:
        echo json_encode(["error" => "Ação não reconhecida."]);
}

