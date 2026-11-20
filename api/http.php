<?php

function obterConexao()
{
    // captura a saida do conexaodb.php para nao quebrar o json
    ob_start();
    require __DIR__ . '/../configDB/conexaodb.php';
    ob_end_clean();

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('Falha ao inicializar conexão com o banco de dados.');
    }

    return $pdo;
}

function lerCorpoJson()
{
    $bruto = file_get_contents('php://input');
    if ($bruto === false || trim($bruto) === '') {
        return $_POST;
    }

    $dados = json_decode($bruto, true);
    if (!is_array($dados)) {
        throw new InvalidArgumentException('Corpo da requisição JSON inválido.');
    }

    return $dados;
}

function responderJson($status, $dados)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

function tratarErroEndpoint($e)
{
    if ($e instanceof InvalidArgumentException) {
        responderJson(400, ['erro' => $e->getMessage()]);
    } elseif ($e instanceof RuntimeException) {
        responderJson(409, ['erro' => $e->getMessage()]);
    } else {
        responderJson(500, ['erro' => 'Erro interno ao processar a solicitação.']);
    }
}
