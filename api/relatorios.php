<?php

require_once __DIR__ . '/http.php';
require_once __DIR__ . '/../funcoes/relatorio.php';

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        responderJson(405, ['erro' => 'Método não permitido.']);
    }

    $relatorio = new Relatorio(obterConexao());
    $tipo = (string) ($_GET['tipo'] ?? 'ocupacao');

    if ($tipo === 'ocupacao') {
        responderJson(200, $relatorio->ocupacaoEventos());
    } elseif ($tipo === 'resumo') {
        responderJson(200, $relatorio->resumoGeral());
    } elseif ($tipo === 'inscritos') {
        $idEvento = (int) ($_GET['id_evento'] ?? 0);
        if ($idEvento <= 0) {
            throw new InvalidArgumentException('id_evento é obrigatório para listar inscritos.');
        }
        responderJson(200, $relatorio->inscritosPorEvento($idEvento));
    } else {
        throw new InvalidArgumentException('Tipo de relatório inválido. Use: ocupacao, resumo ou inscritos.');
    }
} catch (Throwable $e) {
    tratarErroEndpoint($e);
}
