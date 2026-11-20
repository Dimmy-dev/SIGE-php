# Documentação do Backend - SIGE

Documentação técnica do Sistema Web de Gerenciamento de Eventos Acadêmicos (SIGE). O backend foi separado do frontend para permitir que as telas projetadas no Figma consumam os dados via JSON sem misturar HTML com regra de negócio.


## Mapa de arquivos

| Arquivo | Função | Detalhes |
|---|---|---|
| `index.php` | Ponto de entrada HTML do sistema | Base onde o frontend será integrado |
| `configDB/conexaodb.php` | Conexão PDO com o MySQL `gerenciador_events` | [Banco e Configuração](banco-e-configuracao.md) |
| `sql/schema.sql` | Script DDL de criação das tabelas e chaves | [Banco e Configuração](banco-e-configuracao.md) |
| `funcoes/evento.php` | Classe `Evento` (CRUD e validações de evento) | [Classes de Domínio](classes-dominio.md) |
| `funcoes/participante.php` | Classe `Participante` (CRUD e checagem de e-mail/CPF) | [Classes de Domínio](classes-dominio.md) |
| `funcoes/inscricao.php` | Classe `Inscricao` (registro com controle de vagas, consulta, cancelamento) | [Classes de Domínio](classes-dominio.md) |
| `funcoes/relatorio.php` | Classe `Relatorio` (ocupação, lista de inscritos e resumo) | [Classes de Domínio](classes-dominio.md) |
| `api/http.php` | Helpers HTTP/JSON e tratamento centralizado de erros | [Endpoints da API](endpoints-api.md) |
| `api/eventos.php` | Endpoint REST de eventos | [Endpoints da API](endpoints-api.md) |
| `api/participantes.php` | Endpoint REST de participantes | [Endpoints da API](endpoints-api.md) |
| `api/inscricoes.php` | Endpoint REST de inscrições | [Endpoints da API](endpoints-api.md) |
| `api/relatorios.php` | Endpoint GET para relatórios gerenciais | [Endpoints da API](endpoints-api.md) |
| `frontend/index.html` | Painel geral em HTML puro | [Frontend HTML](frontend-html.md) |
| `frontend/eventos.html` | Tela de CRUD e busca de eventos | [Frontend HTML](frontend-html.md) |
| `frontend/participantes.html` | Tela de CRUD e busca de participantes | [Frontend HTML](frontend-html.md) |
| `frontend/inscricoes.html` | Tela de controle de inscrições | [Frontend HTML](frontend-html.md) |
| `frontend/relatorios.html` | Tela de relatórios e lista de inscritos | [Frontend HTML](frontend-html.md) |
| `frontend/app.js` | Integração `fetch()` com a pasta `api/` | [Frontend HTML](frontend-html.md) |
| `frontend/assets/icone-eventos.svg` | Ícone exportado do Figma | [Frontend HTML](frontend-html.md) |

## Fluxo de execução

```
Frontend (Figma) -> api/*.php -> funcoes/*.php -> configDB/conexaodb.php -> MySQL
```

1. O frontend envia uma requisição HTTP (`GET`, `POST`, `PUT` ou `DELETE`) para um arquivo em `api/`.
2. O endpoint lê o JSON recebido e instancia a classe correspondente em `funcoes/`, injetando o `$pdo`.
3. A classe valida os dados, executa a consulta SQL preparada e devolve o resultado.
4. O helper `api/http.php` converte o retorno ou exceção em resposta JSON com o status HTTP adequado.
