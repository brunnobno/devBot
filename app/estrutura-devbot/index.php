<?php
// index.php

require_once __DIR__ . '/GitHub.php';
require_once __DIR__ . '/FileManager.php';
require_once __DIR__ . '/Editor.php';

$_GET['repo'] = $_GET['repo'] ?? 'devBot';

$action = $_GET['acao'] ?? '';
$path   = $_GET['arquivo'] ?? '';

if (!$action || !$path) {
    http_response_code(400);
    echo json_encode(["erro" => "Parâmetros obrigatórios: acao, arquivo"]);
    exit;
}

$github = new GitHub();
$repoPath = trim($path, '/');

switch ($action) {
    case 'carregar':
        $res = $github->getFile($repoPath);
        if ($res['status'] === 200 && isset($res['response']['content'])) {
            $content = base64_decode($res['response']['content']);
            $sha = $res['response']['sha'];
            FileManager::save('original_' . basename($repoPath), $content, dirname($repoPath));
            echo json_encode(["status" => "ok", "sha" => $sha]);
        } else {
            http_response_code($res['status']);
            echo json_encode(["erro" => "Falha ao carregar arquivo", "resposta" => $res]);
        }
        break;

    case 'salvar':
        $inputRaw = file_get_contents('php://input');
        $input = json_decode($inputRaw, true);

        if (!is_array($input)) {
            $input = $_POST;
        }

        $new = $input['novo'] ?? '';
        $sha = $input['sha'] ?? '';

        if (!$new || !$sha) {
            echo json_encode(["erro" => "Parâmetros obrigatórios: novo, sha"]);
            exit;
        }

        $res = $github->updateFile($repoPath, $new, $sha, 'Atualização via estrutura-devbot');
        echo json_encode($res);
        break;

    case 'salvar-interno':
        $sha = $_GET['sha'] ?? '';
        $mod = FileManager::read('modificado_' . basename($repoPath), dirname($repoPath));
        if (!$sha || !$mod) {
            echo json_encode(["erro" => "SHA ou conteúdo modificado ausente"]);
            exit;
        }
        $res = $github->updateFile($repoPath, $mod, $sha, 'Atualização via estrutura-devbot [auto]');
        echo json_encode($res);
        break;

    case 'diff':
        $orig = FileManager::read('original_' . basename($repoPath), dirname($repoPath));
        $mod  = $_POST['novo'] ?? '';
        echo FileManager::diff($orig, $mod);
        break;

    case 'diff-auto':
        $orig = FileManager::read('original_' . basename($repoPath), dirname($repoPath));
        if (!$orig) {
            echo json_encode(["erro" => "Arquivo original não encontrado para diff-auto"]);
            exit;
        }
        $modificado = Editor::substituir($orig, 'DevBot', 'DevBotAI');
        FileManager::save('modificado_' . basename($repoPath), $modificado, dirname($repoPath));
        echo FileManager::diff($orig, $modificado);
        break;

    default:
        echo json_encode(["erro" => "Ação inválida"]);
}