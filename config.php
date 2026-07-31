<?php
declare(strict_types=1);

session_name('ACOSESSID');
session_start();

$DB_HOST = 'localhost';
$DB_NAME = 'andebol2_site_andebol';
$DB_USER = 'andebol2_andebol_user';
$DB_PASS = '!rMulYa1Vh~D+PJp';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Erro ao ligar à base de dados.');
}

function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function input_json(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}

function user_logado(): ?array {
    return $_SESSION['user'] ?? null;
}

function exigir_login(): array {
    $user = user_logado();

    if (!$user) {
        json_response(['erro' => 'Não autenticado.'], 401);
    }

    return $user;
}

function exigir_admin_ou_treinador(): array {
    $user = exigir_login();

    if ($user['tipo'] !== 'admin' && $user['tipo'] !== 'treinador') {
        json_response(['erro' => 'Sem permissão.'], 403);
    }

    return $user;
}