<?php
require_once __DIR__ . '/github_api.php';

if (!file_exists(__DIR__ . '/.env')) {
    die('Arquivo .env não encontrado.');
}

// Carregar variáveis de ambiente
env = parse_ini_file(__DIR__ . '/.env');

// Configurações básicas
$planPath = __DIR__ . '/' . $env['PLANIFICADOR_PATH'];
$logPath = __DIR__ . '/' . $env['LOGS_PATH'];

// Verificar se existe planificador
if (!file_exists($planPath)) {
    die('Planificador não encontrado.');
}

// Carregar plano de ações
$plan = json_decode(file_get_contents($planPath), true);

// Em breve: validar e executar plano
echo "Plano carregado com sucesso. Aguardando execução.";
