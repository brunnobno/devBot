<?php
// app/deploy/debug_exec.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Testa se a função exec() está habilitada
$exec_enabled = function_exists('exec') ? 'Sim' : 'Não';

// Tenta rodar "git --version" para ver se o Git está instalado
$git_version = null;
$git_output = null;
if (function_exists('exec')) {
    exec('git --version 2>&1', $git_output, $return_var);
    $git_version = implode("\n", $git_output);
}

// Retorno
$response = [
    "exec_habilitado" => $exec_enabled,
    "git_disponivel" => ($return_var === 0) ? "Sim" : "Não",
    "git_versao" => $git_version ?? "Não foi possível obter versão do Git",
];

echo json_encode($response, JSON_PRETTY_PRINT);
