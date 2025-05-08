<?php
require_once __DIR__ . '/engine.php';

$acao = $_GET['acao'] ?? '';

if ($acao === 'salvar') {
    salvarEstruturaGeral();
    echo json_encode(['status' => 'ok', 'mensagem' => 'estrutura_geral.json atualizado com sucesso.']);
} else {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Ação inválida.']);
}
?>