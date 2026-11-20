# Endpoints da API (`api/`)

Todos os endpoints respondem em JSON (`UTF-8`) e seguem o mesmo mapeamento de status HTTP definido em `api/http.php`:

- `200`: consulta, atualização ou exclusão concluída.
- `201`: registro criado.
- `400`: dados inválidos ou parâmetro obrigatório ausente (`InvalidArgumentException`).
- `404`: registro não encontrado.
- `405`: método HTTP não suportado no endpoint.
- `409`: conflito de regra de negócio, como e-mail/CPF duplicado ou evento lotado (`RuntimeException`).
- `500`: falha interna inesperada.

## `api/http.php`

Centraliza quatro funções auxiliares usadas pelos endpoints:

- `obterConexao(): PDO` — inclui `configDB/conexaodb.php` com buffer de saída ativo e devolve `$pdo`.
- `lerCorpoJson(): array` — lê JSON de `php://input` (ou `$_POST` como fallback).
- `responderJson(int $status, mixed $dados): never` — define o status HTTP, o cabeçalho `Content-Type` e encerra imprimindo o JSON.
- `executarEndpoint(callable $acao): never` — envolve a execução do endpoint e traduz exceções para os códigos HTTP acima.

## `api/eventos.php`

- `GET /api/eventos.php` — lista todos os eventos. Aceita `?busca=texto`.
- `GET /api/eventos.php?id=1` — busca um evento específico.
- `POST /api/eventos.php` — cadastra evento (`nome_evento`, `descricao`, `vagas`, `data_evento`, `turnos`: array).
- `PUT /api/eventos.php?id=1` — atualiza um evento existente.
- `DELETE /api/eventos.php?id=1` — exclui um evento.

## `api/participantes.php`

- `GET /api/participantes.php` — lista participantes. Aceita `?busca=texto`.
- `GET /api/participantes.php?id=1` — busca um participante específico.
- `POST /api/participantes.php` — cadastra participante (`nome`, `email`, `cpf`, `telefone`).
- `PUT /api/participantes.php?id=1` — atualiza dados do participante.
- `DELETE /api/participantes.php?id=1` — exclui um participante.

## `api/inscricoes.php`

- `GET /api/inscricoes.php` — lista inscrições. Aceita `?id_evento=1` e/ou `?id_participante=2`.
- `POST /api/inscricoes.php` — registra inscrição (`id_evento`, `id_participante`, `turno`).
- `DELETE /api/inscricoes.php?id=1` — cancela uma inscrição.

## `api/relatorios.php`

Aceita apenas `GET`, controlado pelo parâmetro `tipo`:

- `GET /api/relatorios.php?tipo=ocupacao` (padrão) — ocupação de vagas por evento.
- `GET /api/relatorios.php?tipo=resumo` — totais gerais do sistema.
- `GET /api/relatorios.php?tipo=inscritos&id_evento=1` — lista de inscritos de um evento.
