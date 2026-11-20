# Classes de Domínio (`funcoes/`)

Todas as classes recebem uma instância `PDO` via `__construct(PDO $db)` e utilizam *Prepared Statements* com `bindValue`.

## `funcoes/evento.php` (`Evento`)

Gerencia o cadastro e a manutenção de eventos acadêmicos:

- `cadastrarEvento(string $nome_evento, string $descricao, int $vagas, string $data_evento, array $turnos): int`  
  Valida nome, quantidade de vagas (`> 0`), data (`AAAA-MM-DD`) e a lista de turnos (`matutino`, `vespertino`, `noturno`), gravando em `eventos` e `evento_turnos` em transação. Retorna o ID gerado.
- `consultarEvento(string $busca = ''): array`  
  Lista eventos ordenados por data com o array `turnos` de cada evento. Se `$busca` for informada, filtra por `nome_evento`, `descricao` ou turno.
- `buscarporIdEvento(int $id_evento): ?array`  
  Retorna os dados de um evento (incluindo `turnos`) ou `null` caso não exista.
- `atualizarEvento(int $id_evento, string $nome_evento, string $descricao, int $vagas, string $data_evento, array $turnos): bool`  
  Valida os dados, impede remover turnos com inscrições existentes e impede reduzir `vagas` abaixo do total de inscritos em qualquer turno.
- `excluirEvento(int $id_evento): bool`  
  Remove o evento pelo ID. Inscrições vinculadas são removidas em cascata pelo banco.

## `funcoes/participante.php` (`Participante`)

Gerencia o cadastro de alunos e participantes externos:

- `cadastrarParticipante(string $nome, string $email, string $cpf, string $telefone = ''): int`  
  Limpa pontuação do CPF, valida formato de e-mail, exige 11 dígitos no CPF e bloqueia duplicidade de e-mail ou CPF.
- `consultarParticipantes(string $busca = ''): array`  
  Lista participantes em ordem alfabética, com filtro opcional por nome, e-mail ou CPF.
- `buscarPorIdParticipante(int $id_participante): ?array`  
  Retorna um participante pelo ID ou `null`.
- `atualizarParticipante(int $id_participante, string $nome, string $email, string $cpf, string $telefone = ''): bool`  
  Reaplica as mesmas validações e verifica se o e-mail ou CPF não pertence a outro registro.
- `excluirParticipante(int $id_participante): bool`  
  Exclui o participante pelo ID.

## `funcoes/inscricao.php` (`Inscricao`)

Controla o vínculo entre participantes e eventos:

- `registrarInscricao(int $id_evento, int $id_participante, string $turno): int`  
  Executa dentro de uma transação (`beginTransaction`) com `SELECT ... FOR UPDATE` na linha do evento. Garante que evento, turno ofertado (`evento_turnos`) e participante existem, bloqueia inscrição duplicada no mesmo turno e data, e impede ultrapassar o limite de `vagas` do turno.
- `consultarInscricoes(?int $id_evento = null, ?int $id_participante = null): array`  
  Retorna inscrições com dados combinados de `eventos` e `participantes`, filtrando opcionalmente por evento ou participante.
- `cancelarInscricao(int $id_inscricao): bool`  
  Remove a inscrição pelo `id_inscricao`, liberando a vaga imediatamente.

## `funcoes/relatorio.php` (`Relatorio`)

Fornece consultas consolidadas para a coordenação acadêmica:

- `ocupacaoEventos(): array`  
  Retorna cada evento com `vagas`, `inscritos`, `vagas_restantes` e `percentual_ocupacao` usando `LEFT JOIN` em uma única query.
- `inscritosPorEvento(int $id_evento): array`  
  Lista os participantes inscritos em um evento específico para conferência de presença e emissão de certificados.
- `resumoGeral(): array`  
  Retorna totais acumulados de eventos, participantes, inscrições e vagas ofertadas.
