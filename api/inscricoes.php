<?php

require_once __DIR__ . '/http.php';
require_once __DIR__ . '/../funcoes/inscricao.php';

try {
    $inscricao = new Inscricao(obterConexao());
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($metodo === 'GET') {
        $idEvento = ($_GET['id_evento'] ?? '') !== '' ? (int) $_GET['id_evento'] : null;
        $idParticipante = ($_GET['id_participante'] ?? '') !== '' ? (int) $_GET['id_participante'] : null;
        responderJson(200, $inscricao->consultarInscricoes($idEvento, $idParticipante));

    } elseif ($metodo === 'POST') {
        $dados = lerCorpoJson();
        $idEvento = (int) ($dados['id_evento'] ?? 0);
        $idParticipante = (int) ($dados['id_participante'] ?? 0);
        $turno = (string) ($dados['turno'] ?? '');

        if ($idEvento <= 0 || $idParticipante <= 0 || trim($turno) === '') {
            throw new InvalidArgumentException('id_evento, id_participante e turno são obrigatórios.');
        }

        $id = $inscricao->registrarInscricao($idEvento, $idParticipante, $turno);
        responderJson(201, ['id_inscricao' => $id, 'mensagem' => 'Inscrição registrada com sucesso.']);

    } elseif ($metodo === 'DELETE') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('ID da inscrição é obrigatório.');
        }

        if (!$inscricao->cancelarInscricao($id)) {
            responderJson(404, ['erro' => 'Inscrição não encontrada.']);
        }

        responderJson(200, ['mensagem' => 'Inscrição cancelada com sucesso.']);

    } else {
        responderJson(405, ['erro' => 'Método não permitido.']);
    }
} catch (Throwable $e) {
    tratarErroEndpoint($e);
}
