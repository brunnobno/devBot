<?php
require_once __DIR__ . '/engine.php';

$acao = $_GET['acao'] ?? '';

if ($acao === 'salvar') {
    salvarEstruturaGeral();
    echo json_encode(['status' => 'ok', 'mensagem' => 'estrutura_geral.json atualizado com sucesso.']);
} elseif ($acao === 'carregar_arquivo' && isset($_GET['arquivo'])) {
    $ok = carregarArquivoTemp($_GET['arquivo']);
    echo json_encode(['status' => $ok ? 'ok' : 'erro', 'mensagem' => $ok ? 'Arquivo carregado para tmp com sucesso.' : 'Falha ao carregar arquivo.']);
} else {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida.']);
}
?>