<?php
// Teste de conexão com GitHub API

$github_api = 'https://api.github.com/';

function testGitHubConnection($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout de 10 segundos

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        echo "Erro na conexão: " . curl_error($ch);
    } else {
        echo "Status HTTP: $statusCode\n";
    }

    curl_close($ch);
}

testGitHubConnection($github_api);
?>
