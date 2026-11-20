# Banco de Dados e Configuração

## `index.php`

Arquivo inicial da raiz do projeto. Contém apenas a estrutura base em HTML5 (`lang="pt-BR"`, título `G.E`) pronta para receber o layout exportado do Figma e os scripts de consumo da pasta `api/`.

`configDB/conexaodb.php`

Responsável por abrir a conexão `PDO` com o banco `gerenciador_events` no servidor `localhost` usando charset `utf8` e `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`.

Ao ser incluído diretamente no navegador, imprime a mensagem de sucesso ou erro. Nos endpoints da pasta `api/`, a inclusão ocorre dentro de `ob_start()` / `ob_end_clean()` em `api/http.php` para que o `echo` da conexão não quebre o cabeçalho JSON.

## `sql/schema.sql`

Script SQL para criação do banco `gerenciador_events` e das tabelas em `InnoDB`:

- **`eventos`**: armazena `id_evento` (PK), `nome_evento`, `descricao`, `vagas`, `data_evento` e `criado_em`.
- **`evento_turnos`**: armazena os turnos ofertados por cada evento (`id_evento`, `turno` entre `matutino`, `vespertino`, `noturno`), permitindo que um evento tenha um ou mais turnos.
- **`participantes`**: armazena `id_participante` (PK), `nome`, `email`, `cpf` (`CHAR(11)`), `telefone` e `criado_em`. Possui índices únicos (`UNIQUE`) para `email` e `cpf`.
- **`inscricoes`**: relaciona `id_evento`, `id_participante` e `turno` com chaves estrangeiras (`ON DELETE CASCADE`) e chave única composta `(id_evento, id_participante, turno)`.
