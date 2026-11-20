<?php

declare(strict_types=1);

class Inscricao
{
    private const TURNOS_VALIDOS = ['matutino', 'vespertino', 'noturno'];

    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function registrarInscricao(int $id_evento, int $id_participante, string $turno): int
    {
        $turnoNormalizado = strtolower(trim($turno));
        if (!in_array($turnoNormalizado, self::TURNOS_VALIDOS, true)) {
            throw new InvalidArgumentException('Turno inválido. Use: matutino, vespertino ou noturno.');
        }

        $this->conn->beginTransaction();

        try {
            // Bloqueia a linha do evento até o fim da transação para evitar overbooking simultâneo
            $stmtEvento = $this->conn->prepare('SELECT vagas, data_evento FROM eventos WHERE id_evento = :id_evento FOR UPDATE');
            $stmtEvento->bindValue(':id_evento', $id_evento, PDO::PARAM_INT);
            $stmtEvento->execute();
            $evento = $stmtEvento->fetch(PDO::FETCH_ASSOC);

            if ($evento === false) {
                throw new InvalidArgumentException('Evento não encontrado.');
            }

            $stmtEventoTurno = $this->conn->prepare(
                'SELECT 1 FROM evento_turnos WHERE id_evento = :id_evento AND turno = :turno LIMIT 1'
            );
            $stmtEventoTurno->bindValue(':id_evento', $id_evento, PDO::PARAM_INT);
            $stmtEventoTurno->bindValue(':turno', $turnoNormalizado);
            $stmtEventoTurno->execute();
            if ($stmtEventoTurno->fetchColumn() === false) {
                throw new InvalidArgumentException("Este evento não possui o turno {$turnoNormalizado} disponível.");
            }

            $stmtParticipante = $this->conn->prepare('SELECT 1 FROM participantes WHERE id_participante = :id_participante');
            $stmtParticipante->bindValue(':id_participante', $id_participante, PDO::PARAM_INT);
            $stmtParticipante->execute();
            if ($stmtParticipante->fetchColumn() === false) {
                throw new InvalidArgumentException('Participante não encontrado.');
            }
            $stmtTurno = $this->conn->prepare(
                'SELECT 1
                 FROM inscricoes i
                 INNER JOIN eventos e ON e.id_evento = i.id_evento
                 WHERE i.id_participante = :id_participante
                   AND e.data_evento = :data_evento
                   AND i.turno = :turno
                 LIMIT 1'
            );
            $stmtTurno->bindValue(':id_participante', $id_participante, PDO::PARAM_INT);
            $stmtTurno->bindValue(':data_evento', $evento['data_evento']);
            $stmtTurno->bindValue(':turno', $turnoNormalizado);
            $stmtTurno->execute();
            if ($stmtTurno->fetchColumn() !== false) {
                throw new RuntimeException("Participante já inscrito no turno {$turnoNormalizado} para a data {$evento['data_evento']}.");
            }

            $stmtOcupadas = $this->conn->prepare(
                'SELECT COUNT(*) FROM inscricoes WHERE id_evento = :id_evento AND turno = :turno'
            );
            $stmtOcupadas->bindValue(':id_evento', $id_evento, PDO::PARAM_INT);
            $stmtOcupadas->bindValue(':turno', $turnoNormalizado);
            $stmtOcupadas->execute();
            $ocupadas = (int) $stmtOcupadas->fetchColumn();

            if ($ocupadas >= (int) $evento['vagas']) {
                throw new RuntimeException("Não há mais vagas disponíveis para o turno {$turnoNormalizado} deste evento.");
            }

            $sql = 'INSERT INTO inscricoes (id_evento, id_participante, turno)
                    VALUES (:id_evento, :id_participante, :turno)';
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id_evento', $id_evento, PDO::PARAM_INT);
            $stmt->bindValue(':id_participante', $id_participante, PDO::PARAM_INT);
            $stmt->bindValue(':turno', $turnoNormalizado);
            $stmt->execute();

            $idInscricao = (int) $this->conn->lastInsertId();
            $this->conn->commit();

            return $idInscricao;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function consultarInscricoes(?int $id_evento = null, ?int $id_participante = null): array
    {
        $filtros = [];
        $params = [];

        if ($id_evento !== null) {
            $filtros[] = 'i.id_evento = :id_evento';
            $params[':id_evento'] = $id_evento;
        }

        if ($id_participante !== null) {
            $filtros[] = 'i.id_participante = :id_participante';
            $params[':id_participante'] = $id_participante;
        }

        $where = $filtros !== [] ? 'WHERE ' . implode(' AND ', $filtros) : '';

        $sql = "SELECT i.id_inscricao,
                       i.data_inscricao,
                       i.turno,
                       e.id_evento,
                       e.nome_evento,
                       e.data_evento,
                       p.id_participante,
                       p.nome AS nome_participante,
                       p.email,
                       p.cpf
                FROM inscricoes i
                INNER JOIN eventos e ON e.id_evento = i.id_evento
                INNER JOIN participantes p ON p.id_participante = i.id_participante
                {$where}
                ORDER BY i.data_inscricao DESC";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $chave => $valor) {
            $stmt->bindValue($chave, $valor, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cancelarInscricao(int $id_inscricao): bool
    {
        $sql = 'DELETE FROM inscricoes WHERE id_inscricao = :id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id_inscricao, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
