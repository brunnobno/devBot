<?php
// deploy_ftp.php (com validação real do diretório remoto via FTP)

header('Content-Type: application/json');

$env = parse_ini_file(dirname(__DIR__) . '/.env');
$ftp_host = $env['FTP_HOST'] ?? null;
$ftp_user = $env['FTP_USER'] ?? null;
$ftp_pass = $env['FTP_PASS'] ?? null;
$ftp_base_path = $env['FTP_BASE_PATH'] ?? null;
$github_user = $env['GITHUB_USER'] ?? null;

function logDeploy($msg) {
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($dir . '/deploy_' . date('Y-m-d') . '.log', '[' . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

function ensureFtpDirExists($ftp, $path) {
    $parts = explode('/', trim($path, '/'));
    $current = '';
    foreach ($parts as $part) {
        $current .= "/$part";
        @ftp_mkdir($ftp, $current);
    }
}

$repo = basename($_GET['repo'] ?? '');
if (!$repo) {
    logDeploy("ERRO: Parâmetro 'repo' ausente");
    http_response_code(400);
    echo json_encode(["erro" => "Parâmetro 'repo' obrigatório"]);
    exit;
}

$temp_dir = __DIR__ . "/temp_repo";
$working_dir = "$temp_dir/files";
if (!is_dir($temp_dir)) mkdir($temp_dir, 0755, true);
foreach (glob("$temp_dir/*") as $f) is_dir($f) ? deleteDir($f) : unlink($f);

$zip_url = "https://github.com/$github_user/$repo/archive/refs/heads/main.zip";
$zip_file = "$temp_dir/$repo.zip";
if (!file_put_contents($zip_file, @file_get_contents($zip_url))) {
    logDeploy("ERRO: download ZIP");
    http_response_code(500);
    echo json_encode(["erro" => "Erro ao baixar ZIP"]);
    exit;
}

$zip = new ZipArchive;
if ($zip->open($zip_file) === TRUE) {
    $zip->extractTo($temp_dir);
    $zip->close();
    unlink($zip_file);
    logDeploy("ZIP extraído");
} else {
    logDeploy("ERRO: extração ZIP");
    http_response_code(500);
    echo json_encode(["erro" => "Erro ao extrair ZIP"]);
    exit;
}

$main_folder = glob("$temp_dir/*-main")[0] ?? null;
if (!$main_folder || !is_dir($main_folder)) {
    logDeploy("ERRO: pasta -main não encontrada");
    http_response_code(500);
    echo json_encode(["erro" => "Pasta do repositório não encontrada"]);
    exit;
}

mkdir($working_dir);
moveDirContent($main_folder, $working_dir);
logDeploy("Iniciando upload a partir de: $working_dir");

$conn = ftp_connect($ftp_host, 2121);
if (!$conn || !ftp_login($conn, $ftp_user, $ftp_pass)) {
    logDeploy("ERRO: conexão/login FTP");
    http_response_code(500);
    echo json_encode(["erro" => "Erro FTP"]);
    exit;
}
ftp_pasv($conn, true);

$ftp_repo_path = rtrim($ftp_base_path, '/') . "/$repo";
logDeploy("[VALIDAÇÃO] Verificando existência de: $ftp_repo_path");
$existing_dirs = ftp_nlist($conn, dirname($ftp_repo_path));
if (!in_array($ftp_repo_path, $existing_dirs)) {
    logDeploy("[AÇÃO] Criando diretório remoto: $ftp_repo_path");
    ftp_mkdir($conn, $ftp_repo_path);
}

ensureFtpDirExists($conn, $ftp_repo_path);

function uploadDir($ftp, $local, $remote) {
    foreach (scandir($local) as $i) {
        if ($i === '.' || $i === '..') continue;
        $lp = "$local/$i";
        $rp = "$remote/$i";
        logDeploy("[uploadDir] Verificando: $lp");
        if (is_dir($lp)) {
            @ftp_mkdir($ftp, $rp);
            uploadDir($ftp, $lp, $rp);
        } else {
            $realLocal = realpath($lp);
            if (ftp_put($ftp, $rp, $realLocal, FTP_BINARY)) {
                logDeploy("UPLOAD OK: $rp");
            } else {
                $err = error_get_last();
                logDeploy("UPLOAD FALHOU: $rp – Motivo: " . ($err['message'] ?? 'erro desconhecido'));
            }
        }
    }
}

function moveDirContent($src, $dst) {
    foreach (scandir($src) as $item) {
        if ($item === '.' || $item === '..') continue;
        $from = "$src/$item";
        $to = "$dst/$item";
        logDeploy("[moveDirContent] Copiando: $from -> $to");
        if (is_dir($from)) {
            mkdir($to);
            moveDirContent($from, $to);
        } else {
            copy($from, $to);
        }
    }
}

uploadDir($conn, $working_dir, $ftp_repo_path);
ftp_close($conn);
deleteDir($temp_dir);

logDeploy("Deploy de $repo concluído em $ftp_repo_path");
http_response_code(200);
echo json_encode(["message" => "Deploy concluído", "ftp_path" => $ftp_repo_path]);

function deleteDir($p) {
    foreach (scandir($p) as $f) {
        if ($f === '.' || $f === '..') continue;
        $fp = "$p/$f";
        is_dir($fp) ? deleteDir($fp) : unlink($fp);
    }
    rmdir($p);
}