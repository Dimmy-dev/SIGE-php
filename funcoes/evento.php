<?php

declare(strict_types=1);

class Evento
{
    private const TURNOS_VALIDOS = ['matutino', 'vespertino', 'noturno'];

    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function cadastrarEvento(string $nome_evento, string $descricao, int $vagas, string $data_evento, array $turnos): int
    {
        $turnosNormalizados = $this->normalizarTurnos($turnos);
        $this->validarDados($nome_evento, $vagas, $data_evento, $turnosNormalizados);

        $this->conn->beginTransaction();

        try {
            $sql = 'INSERT INTO eventos (nome_evento, descricao, vagas, data_evento)
                    VALUES (:nome_evento, :descricao, :vagas, :data_evento)';

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':nome_evento', trim($nome_evento));
            $stmt->bindValue(':descricao', trim($descricao));
            $stmt->bindValue(':vagas', $vagas, PDO::PARAM_INT);
            $stmt->bindValue(':data_evento', $data_evento);
            $stmt->execute();

            $idEvento = (int) $this->conn->lastInsertId();
            $this->inserirTurnos($idEvento, $turnosNormalizados);

            $this->conn->commit();
            return $idEvento;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function consultarEvento(string $busca = ''): array
    {
        $busca = trim($busca);

        $baseSql = "SELECT e.*,
                           GROUP_CONCAT(et.turno ORDER BY FIELD(et.turno, 'matutino', 'vespertino', 'noturno')) AS turnos_csv
                    FROM eventos e
                    LEFT JOIN evento_turnos et ON et.id_evento = e.id_evento";

        if ($busca !== '') {
            $sql = "{$baseSql}
                    WHERE e.nome_evento LIKE :busca
                       OR e.descricao LIKE :busca
                       OR EXISTS (
                           SELECT 1 FROM evento_turnos et2
                           WHERE et2.id_evento = e.id_evento AND et2.turno LIKE :busca
                       )
                    GROUP BY e.id_evento
                    ORDER BY e.data_evento ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':busca', "%{$busca}%");
        } else {
            $sql = "{$baseSql}
                    GROUP BY e.id_evento
                    ORDER BY e.data_evento ASC";
            $stmt = $this->conn->prepare($sql);
        }

        $stmt->execute();
        $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $linha): array => $this->hidratarTurnos($linha), $linhas);
    }

    public function buscarporIdEvento(int $id_evento): ?array
    {
        $sql = "SELECT e.*,
                       GROUP_CONCAT(et.turno ORDER BY FIELD(et.turno, 'matutino', 'vespertino', 'noturno')) AS turnos_csv
                FROM eventos e
                LEFT JOIN evento_turnos et ON et.id_evento = e.id_evento
                WHERE e.id_evento = :id
                GROUP BY e.id_evento";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id_evento, PDO::PARAM_INT);
        $stmt->execute();

        $evento = $stmt->fetch(PDO::FETCH_ASSOC);
        return $evento !== false ? $this->hidratarTurnos($evento) : null;
    }

    public function atualizarEvento(int $id_evento, string $nome_evento, string $descricao, int $vagas, string $data_evento, array $turnos): bool
    {
        $turnosNormalizados = $this->normalizarTurnos($turnos);
        $this->validarDados($nome_evento, $vagas, $data_evento, $turnosNormalizados);

        $stmtInscritos = $this->conn->prepare(
            'SELECT turno, COUNT(*) AS total FROM inscricoes WHERE id_evento = :id GROUP BY turno'
        );
        $stmtInscritos->bindValue(':id', $id_evento, PDO::PARAM_INT);
        $stmtInscritos->execute();
        $inscritosPorTurno = $stmtInscritos->fetchAll(PDO::FETCH_ASSOC);

        foreach ($inscritosPorTurno as $grupo) {
            if (!in_array($grupo['turno'], $turnosNormalizados, true)) {
                throw new InvalidArgumentException("Não é possível remover o turno {$grupo['turno']}, pois já existem inscrições nele.");
            }
            $totalTurno = (int) $grupo['total'];
            if ($vagas < $totalTurno) {
                throw new InvalidArgumentException("O número de vagas ({$vagas}) não pode ser menor que o total de inscritos no turno {$grupo['turno']} ({$totalTurno}).");
            }
        }

        $this->conn->beginTransaction();

        try {
            $sql = 'UPDATE eventos
                    SET nome_evento = :nome_evento,
                        descricao = :descricao,
                        vagas = :vagas,
                        data_evento = :data_evento
                    WHERE id_evento = :id';

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id_evento, PDO::PARAM_INT);
            $stmt->bindValue(':nome_evento', trim($nome_evento));
            $stmt->bindValue(':descricao', trim($descricao));
            $stmt->bindValue(':vagas', $vagas, PDO::PARAM_INT);
            $stmt->bindValue(':data_evento', $data_evento);
            $stmt->execute();

            $stmtCheck = $this->conn->prepare('SELECT 1 FROM eventos WHERE id_evento = :id');
            $stmtCheck->bindValue(':id', $id_evento, PDO::PARAM_INT);
            $stmtCheck->execute();
            if ($stmtCheck->fetchColumn() === false) {
                $this->conn->rollBack();
                return false;
            }

            $stmtDelTurnos = $this->conn->prepare('DELETE FROM evento_turnos WHERE id_evento = :id');
            $stmtDelTurnos->bindValue(':id', $id_evento, PDO::PARAM_INT);
            $stmtDelTurnos->execute();

            $this->inserirTurnos($id_evento, $turnosNormalizados);

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    public function excluirEvento(int $id_evento): bool
    {
        $sql = 'DELETE FROM eventos WHERE id_evento = :id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id_evento, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    private function inserirTurnos(int $id_evento, array $turnos): void
    {
        $stmt = $this->conn->prepare('INSERT INTO evento_turnos (id_evento, turno) VALUES (:id_evento, :turno)');
        foreach ($turnos as $turno) {
            $stmt->bindValue(':id_evento', $id_evento, PDO::PARAM_INT);
            $stmt->bindValue(':turno', $turno);
            $stmt->execute();
        }
    }

    private function hidratarTurnos(array $linha): array
    {
        $csv = $linha['turnos_csv'] ?? '';
        unset($linha['turnos_csv']);
        $linha['turnos'] = $csv !== null && $csv !== '' ? explode(',', $csv) : [];
        return $linha;
    }

    private function normalizarTurnos(array $turnos): array
    {
        $limpos = array_map(fn($t): string => strtolower(trim((string) $t)), $turnos);
        $unicos = array_values(array_unique(array_filter($limpos, fn(string $t): bool => $t !== '')));

        return $unicos;
    }

    private function validarDados(string $nome_evento, int $vagas, string $data_evento, array $turnos): void
    {
        if (trim($nome_evento) === '') {
            throw new InvalidArgumentException('O nome do evento é obrigatório.');
        }

        if ($vagas <= 0) {
            throw new InvalidArgumentException('A quantidade de vagas deve ser maior que zero.');
        }

        $data = DateTimeImmutable::createFromFormat('Y-m-d', $data_evento);
        if ($data === false || $data->format('Y-m-d') !== $data_evento) {
            throw new InvalidArgumentException('Data do evento inválida. Use o formato AAAA-MM-DD.');
        }

        if ($turnos === []) {
            throw new InvalidArgumentException('Selecione pelo menos um turno para o evento.');
        }

        foreach ($turnos as $turno) {
            if (!in_array($turno, self::TURNOS_VALIDOS, true)) {
                throw new InvalidArgumentException("Turno inválido ({$turno}). Use: matutino, vespertino ou noturno.");
            }
        }
    }
}
