<?php

require_once __DIR__ . '/http.php';
require_once __DIR__ . '/../funcoes/evento.php';

try {
    $evento = new Evento(obterConexao());
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($metodo === 'GET') {
        if (isset($_GET['id'])) {
            $registro = $evento->buscarporIdEvento((int) $_GET['id']);
            if ($registro === null) {
                responderJson(404, ['erro' => 'Evento não encontrado.']);
            }
            responderJson(200, $registro);
        }

        $busca = (string) ($_GET['busca'] ?? '');
        responderJson(200, $evento->consultarEvento($busca));

    } elseif ($metodo === 'POST') {
        $dados = lerCorpoJson();
        $turnos = is_array($dados['turnos'] ?? null) ? $dados['turnos'] : [];
        $id = $evento->cadastrarEvento(
            (string) ($dados['nome_evento'] ?? ''),
            (string) ($dados['descricao'] ?? ''),
            (int) ($dados['vagas'] ?? 0),
            (string) ($dados['data_evento'] ?? ''),
            $turnos
        );
        responderJson(201, ['id_evento' => $id, 'mensagem' => 'Evento cadastrado com sucesso.']);

    } elseif ($metodo === 'PUT') {
        $dados = lerCorpoJson();
        $id = (int) ($_GET['id'] ?? $dados['id_evento'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('ID do evento é obrigatório.');
        }

        $turnos = is_array($dados['turnos'] ?? null) ? $dados['turnos'] : [];
        $atualizado = $evento->atualizarEvento(
            $id,
            (string) ($dados['nome_evento'] ?? ''),
            (string) ($dados['descricao'] ?? ''),
            (int) ($dados['vagas'] ?? 0),
            (string) ($dados['data_evento'] ?? ''),
            $turnos
        );

        if (!$atualizado && $evento->buscarporIdEvento($id) === null) {
            responderJson(404, ['erro' => 'Evento não encontrado.']);
        }

        responderJson(200, ['mensagem' => 'Evento atualizado com sucesso.']);

    } elseif ($metodo === 'DELETE') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('ID do evento é obrigatório.');
        }

        if (!$evento->excluirEvento($id)) {
            responderJson(404, ['erro' => 'Evento não encontrado.']);
        }

        responderJson(200, ['mensagem' => 'Evento excluído com sucesso.']);

    } else {
        responderJson(405, ['erro' => 'Método não permitido.']);
    }
} catch (Throwable $e) {
    tratarErroEndpoint($e);
}
