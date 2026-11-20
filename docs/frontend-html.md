# Frontend HTML (`frontend/`)

Estrutura em HTML5 puro (sem CSS), desenhada a partir do layout base do Figma (`node-id=1:1045`) para ser estilizada posteriormente sem alterar a lógica de consumo da API.

## Arquivos da pasta

- `frontend/assets/icone-eventos.svg`: ícone de calendário exportado do Figma e usado no item "Total de Eventos" da navegação superior.
- `frontend/index.html`: painel geral (`data-pagina="dashboard"`) com os indicadores de resumo (`<dl>`) e a tabela de ocupação de vagas (`<progress>`).
- `frontend/eventos.html`: cadastro, edição, busca e exclusão de eventos (`data-pagina="eventos"`), além de atalho para ver inscritos no relatório.
- `frontend/participantes.html`: cadastro, edição, busca e exclusão de participantes (`data-pagina="participantes"`).
- `frontend/inscricoes.html`: registro de inscrição entre evento e participante, filtros por evento/participante e cancelamento (`data-pagina="inscricoes"`).
- `frontend/relatorios.html`: consulta de lista de inscritos por evento, botão de impressão (`window.print()`) e tabela de ocupação geral (`data-pagina="relatorios"`).
- `frontend/app.js`: script compartilhado que conecta todas as páginas aos endpoints em `../api/` via `fetch()`, monta tabelas com `textContent` para evitar XSS, desabilita botões durante o envio e exibe retornos em `#mensagem-status` (`aria-live="polite"`).
