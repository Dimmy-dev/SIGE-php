<?php

require_once __DIR__ . '/http.php';
require_once __DIR__ . '/../funcoes/participante.php';

try {
    $participante = new Participante(obterConexao());
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($metodo === 'GET') {
        if (isset($_GET['id'])) {
            $registro = $participante->buscarPorIdParticipante((int) $_GET['id']);
            if ($registro === null) {
                responderJson(404, ['erro' => 'Participante não encontrado.']);
            }
            responderJson(200, $registro);
        }

        $busca = (string) ($_GET['busca'] ?? '');
        responderJson(200, $participante->consultarParticipantes($busca));

    } elseif ($metodo === 'POST') {
        $dados = lerCorpoJson();
        $id = $participante->cadastrarParticipante(
            (string) ($dados['nome'] ?? ''),
            (string) ($dados['email'] ?? ''),
            (string) ($dados['cpf'] ?? ''),
            (string) ($dados['telefone'] ?? '')
        );
        responderJson(201, ['id_participante' => $id, 'mensagem' => 'Participante cadastrado com sucesso.']);

    } elseif ($metodo === 'PUT') {
        $dados = lerCorpoJson();
        $id = (int) ($_GET['id'] ?? $dados['id_participante'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('ID do participante é obrigatório.');
        }

        $atualizado = $participante->atualizarParticipante(
            $id,
            (string) ($dados['nome'] ?? ''),
            (string) ($dados['email'] ?? ''),
            (string) ($dados['cpf'] ?? ''),
            (string) ($dados['telefone'] ?? '')
        );

        if (!$atualizado && $participante->buscarPorIdParticipante($id) === null) {
            responderJson(404, ['erro' => 'Participante não encontrado.']);
        }

        responderJson(200, ['mensagem' => 'Participante atualizado com sucesso.']);

    } elseif ($metodo === 'DELETE') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('ID do participante é obrigatório.');
        }

        if (!$participante->excluirParticipante($id)) {
            responderJson(404, ['erro' => 'Participante não encontrado.']);
        }

        responderJson(200, ['mensagem' => 'Participante excluído com sucesso.']);

    } else {
        responderJson(405, ['erro' => 'Método não permitido.']);
    }
} catch (Throwable $e) {
    tratarErroEndpoint($e);
}
