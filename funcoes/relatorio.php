<?php

declare(strict_types=1);

class Relatorio
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function ocupacaoEventos(): array
    {
        $sql = "SELECT e.id_evento,
                       e.nome_evento,
                       e.data_evento,
                       et.turno,
                       e.vagas,
                       COUNT(i.id_inscricao) AS inscritos,
                       (e.vagas - COUNT(i.id_inscricao)) AS vagas_restantes,
                       ROUND((COUNT(i.id_inscricao) / e.vagas) * 100, 1) AS percentual_ocupacao
                FROM eventos e
                INNER JOIN evento_turnos et ON et.id_evento = e.id_evento
                LEFT JOIN inscricoes i ON i.id_evento = e.id_evento AND i.turno = et.turno
                GROUP BY e.id_evento, e.nome_evento, e.data_evento, et.turno, e.vagas
                ORDER BY e.data_evento ASC, FIELD(et.turno, 'matutino', 'vespertino', 'noturno')";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function inscritosPorEvento(int $id_evento): array
    {
        $sql = "SELECT p.id_participante,
                       p.nome,
                       p.email,
                       p.cpf,
                       p.telefone,
                       i.id_inscricao,
                       i.turno,
                       i.data_inscricao
                FROM inscricoes i
                INNER JOIN participantes p ON p.id_participante = i.id_participante
                WHERE i.id_evento = :id_evento
                ORDER BY FIELD(i.turno, 'matutino', 'vespertino', 'noturno'), p.nome ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_evento', $id_evento, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function resumoGeral(): array
    {
        $sql = 'SELECT
                    (SELECT COUNT(*) FROM eventos) AS total_eventos,
                    (SELECT COUNT(*) FROM participantes) AS total_participantes,
                    (SELECT COUNT(*) FROM inscricoes) AS total_inscricoes,
                    (SELECT COALESCE(SUM(e.vagas), 0)
                     FROM eventos e
                     INNER JOIN evento_turnos et ON et.id_evento = e.id_evento) AS total_vagas';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
