<?php
// engine.php - núcleo para leitura, edição segura e controle de auditoria

function contarLinhas($conteudo) {
    return substr_count($conteudo, "\n") + 1;
}

function salvarAuditoria($arquivo, $linhasAntes, $linhasDepois, $tipoAlteracao, $justificativa) {
    $logPath = __DIR__ . '/../logs/auditoria_edicoes.json';
    
    if (!file_exists($logPath)) {
        file_put_contents($logPath, json_encode(["auditorias" => []], JSON_PRETTY_PRINT));
    }

    $json = json_decode(file_get_contents($logPath), true);
    $json['auditorias'][] = [
        "arquivo" => $arquivo,
        "linhas_antes" => $linhasAntes,
        "linhas_depois" => $linhasDepois,
        "tipo_alteracao" => $tipoAlteracao,
        "justificativa" => $justificativa,
        "timestamp" => date("Y-m-d H:i:s")
    ];

    file_put_contents($logPath, json_encode($json, JSON_PRETTY_PRINT));
}

function aplicarEdicaoSegura($caminhoAbsoluto, $novoConteudo, $justificativa = "Atualização padrão") {
    $conteudoAtual = file_get_contents($caminhoAbsoluto);
    $linhasAntes = contarLinhas($conteudoAtual);
    $linhasDepois = contarLinhas($novoConteudo);

    $tipo = $linhasDepois > $linhasAntes ? "insercao" : ($linhasDepois < $linhasAntes ? "remocao" : "edicao");

    file_put_contents($caminhoAbsoluto, $novoConteudo);
    salvarAuditoria(basename($caminhoAbsoluto), $linhasAntes, $linhasDepois, $tipo, $justificativa);
}

function gerarEstruturaCompleta($dir, &$estrutura) {
    $itens = scandir($dir);
    foreach ($itens as $item) {
        if ($item === '.' || $item === '..') continue;
        $caminho = $dir . '/' . $item;
        if (is_dir($caminho)) {
            gerarEstruturaCompleta($caminho, $estrutura);
        } else {
            $estrutura[] = str_replace(__DIR__ . '/../', '', $caminho);
        }
    }
}

function salvarEstruturaGeral() {
    $estrutura = [];
    gerarEstruturaCompleta(__DIR__ . '/../', $estrutura);
    file_put_contents(__DIR__ . '/estrutura_geral.json', json_encode(["arquivos" => $estrutura], JSON_PRETTY_PRINT));
}

function carregarArquivoTemp($relativo) {
    $base = __DIR__ . '/../';
    $origem = $base . $relativo;
    
    if (!file_exists($origem)) return false;

    $conteudo = file_get_contents($origem);

    $destinoOriginal = __DIR__ . '/tmp/' . $relativo;
    $destinoModificado = str_replace('original_', 'modificado_', $destinoOriginal);

    if (!is_dir(dirname($destinoOriginal))) {
        mkdir(dirname($destinoOriginal), 0777, true);
    }

    file_put_contents(str_replace(basename($destinoOriginal), 'original_' . basename($relativo), $destinoOriginal), $conteudo);
    file_put_contents(str_replace(basename($destinoOriginal), 'modificado_' . basename($relativo), $destinoOriginal), $conteudo);

    return true;
}

?>