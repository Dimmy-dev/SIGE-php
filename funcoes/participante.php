<?php

declare(strict_types=1);

class Participante
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function cadastrarParticipante(string $nome, string $email, string $cpf, string $telefone = ''): int
    {
        $cpfLimpo = $this->limparCpf($cpf);
        $emailLimpo = strtolower(trim($email));
        $this->validarDados($nome, $emailLimpo, $cpfLimpo);

        if ($this->existeDuplicidade($emailLimpo, $cpfLimpo)) {
            throw new RuntimeException('Já existe um participante cadastrado com este e-mail ou CPF.');
        }

        $sql = 'INSERT INTO participantes (nome, email, cpf, telefone)
                VALUES (:nome, :email, :cpf, :telefone)';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nome', trim($nome));
        $stmt->bindValue(':email', $emailLimpo);
        $stmt->bindValue(':cpf', $cpfLimpo);
        $stmt->bindValue(':telefone', trim($telefone));
        $stmt->execute();

        return (int) $this->conn->lastInsertId();
    }

    public function consultarParticipantes(string $busca = ''): array
    {
        $busca = trim($busca);

        if ($busca !== '') {
            $sql = 'SELECT * FROM participantes
                    WHERE nome LIKE :busca OR email LIKE :busca OR cpf LIKE :busca
                    ORDER BY nome ASC';
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':busca', "%{$busca}%");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $sql = 'SELECT * FROM participantes ORDER BY nome ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorIdParticipante(int $id_participante): ?array
    {
        $sql = 'SELECT * FROM participantes WHERE id_participante = :id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id_participante, PDO::PARAM_INT);
        $stmt->execute();

        $participante = $stmt->fetch(PDO::FETCH_ASSOC);
        return $participante !== false ? $participante : null;
    }

    public function atualizarParticipante(int $id_participante, string $nome, string $email, string $cpf, string $telefone = ''): bool
    {
        $cpfLimpo = $this->limparCpf($cpf);
        $emailLimpo = strtolower(trim($email));
        $this->validarDados($nome, $emailLimpo, $cpfLimpo);

        if ($this->existeDuplicidade($emailLimpo, $cpfLimpo, $id_participante)) {
            throw new RuntimeException('Outro participante já utiliza este e-mail ou CPF.');
        }

        $sql = 'UPDATE participantes
                SET nome = :nome,
                    email = :email,
                    cpf = :cpf,
                    telefone = :telefone
                WHERE id_participante = :id';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id_participante, PDO::PARAM_INT);
        $stmt->bindValue(':nome', trim($nome));
        $stmt->bindValue(':email', $emailLimpo);
        $stmt->bindValue(':cpf', $cpfLimpo);
        $stmt->bindValue(':telefone', trim($telefone));
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function excluirParticipante(int $id_participante): bool
    {
        $sql = 'DELETE FROM participantes WHERE id_participante = :id';
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id_participante, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    private function limparCpf(string $cpf): string
    {
        return preg_replace('/\D/', '', $cpf) ?? '';
    }

    private function validarDados(string $nome, string $email, string $cpf): void
    {
        if (trim($nome) === '') {
            throw new InvalidArgumentException('O nome do participante é obrigatório.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail inválido.');
        }

        if (strlen($cpf) !== 11) {
            throw new InvalidArgumentException('O CPF deve conter exatamente 11 dígitos numéricos.');
        }
    }

    private function existeDuplicidade(string $email, string $cpf, ?int $ignorarId = null): bool
    {
        $sql = 'SELECT 1 FROM participantes WHERE (email = :email OR cpf = :cpf)';
        if ($ignorarId !== null) {
            $sql .= ' AND id_participante <> :id';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':cpf', $cpf);
        if ($ignorarId !== null) {
            $stmt->bindValue(':id', $ignorarId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }
}
