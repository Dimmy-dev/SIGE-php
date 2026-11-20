const API_BASE = '../api';

function mostrarStatus(mensagem) {
    const el = document.getElementById('mensagem-status');
    if (el) {
        el.textContent = mensagem;
    }
}

async function requisitarApi(caminho, opcoes = {}) {
    const resposta = await fetch(`${API_BASE}/${caminho}`, {
        headers: { 'Content-Type': 'application/json' },
        ...opcoes,
    });

    const dados = await resposta.json().catch(() => ({}));
    if (!resposta.ok) {
        throw new Error(dados.erro || 'Não foi possível concluir a operação.');
    }

    return dados;
}

function limparTabela(tbody, colunas, mensagemVazia) {
    tbody.innerHTML = '';
    if (mensagemVazia) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = colunas;
        td.textContent = mensagemVazia;
        tr.appendChild(td);
        tbody.appendChild(tr);
    }
}

function criarCelulaTexto(texto) {
    const td = document.createElement('td');
    td.textContent = texto ?? '';
    return td;
}

function criarBotao(texto, aoClicar) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = texto;
    btn.addEventListener('click', aoClicar);
    return btn;
}

async function carregarTabelaOcupacao() {
    const tbody = document.getElementById('tabela-ocupacao');
    if (!tbody) return;

    const lista = await requisitarApi('relatorios.php?tipo=ocupacao');
    if (lista.length === 0) {
        limparTabela(tbody, 7, 'Nenhum evento cadastrado até o momento.');
        return;
    }

    tbody.innerHTML = '';
    for (const item of lista) {
        const tr = document.createElement('tr');
        tr.appendChild(criarCelulaTexto(item.nome_evento));
        tr.appendChild(criarCelulaTexto(item.data_evento));
        tr.appendChild(criarCelulaTexto(item.turno));
        tr.appendChild(criarCelulaTexto(String(item.vagas)));
        tr.appendChild(criarCelulaTexto(String(item.inscritos)));
        tr.appendChild(criarCelulaTexto(String(item.vagas_restantes)));

        const tdProgresso = document.createElement('td');
        const barra = document.createElement('progress');
        barra.max = 100;
        barra.value = Number(item.percentual_ocupacao) || 0;
        tdProgresso.appendChild(barra);
        tdProgresso.appendChild(document.createTextNode(` ${item.percentual_ocupacao ?? 0}%`));
        tr.appendChild(tdProgresso);

        tbody.appendChild(tr);
    }
}

async function iniciarDashboard() {
    try {
        const resumo = await requisitarApi('relatorios.php?tipo=resumo');
        document.getElementById('kpi-eventos').textContent = resumo.total_eventos ?? '0';
        document.getElementById('kpi-participantes').textContent = resumo.total_participantes ?? '0';
        document.getElementById('kpi-inscricoes').textContent = resumo.total_inscricoes ?? '0';
        document.getElementById('kpi-vagas').textContent = resumo.total_vagas ?? '0';
        await carregarTabelaOcupacao();
    } catch (erro) {
        console.error(erro);
        mostrarStatus(erro.message);
    }
}

async function iniciarEventos() {
    const form = document.getElementById('form-evento');
    const tbody = document.getElementById('tabela-eventos');
    const btnSalvar = document.getElementById('btn-salvar-evento');
    const btnCancelar = document.getElementById('btn-cancelar-edicao');
    const tituloForm = document.getElementById('titulo-form-evento');
    const campoId = document.getElementById('id_evento');
    const campoBusca = document.getElementById('busca-evento');

    function obterTurnosMarcados() {
        return Array.from(form.querySelectorAll('input[name="turnos"]:checked')).map((el) => el.value);
    }

    function marcarTurnos(turnos) {
        const lista = Array.isArray(turnos) ? turnos : [];
        form.querySelectorAll('input[name="turnos"]').forEach((el) => {
            el.checked = lista.includes(el.value);
        });
    }

    function resetarFormulario() {
        form.reset();
        marcarTurnos([]);
        campoId.value = '';
        tituloForm.textContent = 'Cadastrar Evento';
        btnSalvar.textContent = 'Cadastrar evento';
        btnCancelar.hidden = true;
    }

    async function carregarEventos(busca = '') {
        const query = busca ? `?busca=${encodeURIComponent(busca)}` : '';
        const eventos = await requisitarApi(`eventos.php${query}`);

        if (eventos.length === 0) {
            limparTabela(tbody, 7, 'Nenhum evento encontrado.');
            return;
        }

        tbody.innerHTML = '';
        for (const ev of eventos) {
            const turnosTexto = Array.isArray(ev.turnos) && ev.turnos.length > 0 ? ev.turnos.join(', ') : '-';
            const tr = document.createElement('tr');
            tr.appendChild(criarCelulaTexto(String(ev.id_evento)));
            tr.appendChild(criarCelulaTexto(ev.nome_evento));
            tr.appendChild(criarCelulaTexto(ev.data_evento));
            tr.appendChild(criarCelulaTexto(turnosTexto));
            tr.appendChild(criarCelulaTexto(String(ev.vagas)));
            tr.appendChild(criarCelulaTexto(ev.descricao || '-'));

            const tdAcoes = document.createElement('td');
            tdAcoes.appendChild(criarBotao('Editar', () => {
                campoId.value = ev.id_evento;
                document.getElementById('nome_evento').value = ev.nome_evento;
                document.getElementById('data_evento').value = ev.data_evento;
                marcarTurnos(ev.turnos);
                document.getElementById('vagas').value = ev.vagas;
                document.getElementById('descricao').value = ev.descricao || '';
                tituloForm.textContent = `Editar Evento #${ev.id_evento}`;
                btnSalvar.textContent = 'Salvar alterações';
                btnCancelar.hidden = false;
                document.getElementById('nome_evento').focus();
            }));

            tdAcoes.appendChild(document.createTextNode(' '));
            tdAcoes.appendChild(criarBotao('Excluir', async () => {
                if (!window.confirm(`Excluir o evento "${ev.nome_evento}" e todas as suas inscrições?`)) {
                    return;
                }
                try {
                    const res = await requisitarApi(`eventos.php?id=${ev.id_evento}`, { method: 'DELETE' });
                    mostrarStatus(res.mensagem);
                    await carregarEventos(campoBusca.value.trim());
                } catch (erro) {
                    mostrarStatus(erro.message);
                }
            }));

            tdAcoes.appendChild(document.createTextNode(' '));
            const linkInscritos = document.createElement('a');
            linkInscritos.href = `relatorios.html?id_evento=${ev.id_evento}`;
            linkInscritos.textContent = 'Ver inscritos';
            tdAcoes.appendChild(linkInscritos);

            tr.appendChild(tdAcoes);
            tbody.appendChild(tr);
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        btnSalvar.disabled = true;
        try {
            const payload = {
                nome_evento: document.getElementById('nome_evento').value,
                data_evento: document.getElementById('data_evento').value,
                turnos: obterTurnosMarcados(),
                vagas: Number(document.getElementById('vagas').value),
                descricao: document.getElementById('descricao').value,
            };

            const id = campoId.value;
            const res = id
                ? await requisitarApi(`eventos.php?id=${id}`, { method: 'PUT', body: JSON.stringify(payload) })
                : await requisitarApi('eventos.php', { method: 'POST', body: JSON.stringify(payload) });

            mostrarStatus(res.mensagem);
            resetarFormulario();
            await carregarEventos(campoBusca.value.trim());
        } catch (erro) {
            mostrarStatus(erro.message);
        } finally {
            btnSalvar.disabled = false;
        }
    });

    btnCancelar.addEventListener('click', resetarFormulario);

    document.getElementById('form-busca-evento').addEventListener('submit', async (e) => {
        e.preventDefault();
        await carregarEventos(campoBusca.value.trim());
    });

    document.getElementById('btn-limpar-busca-evento').addEventListener('click', async () => {
        campoBusca.value = '';
        await carregarEventos();
    });

    await carregarEventos();
}

async function iniciarParticipantes() {
    const form = document.getElementById('form-participante');
    const tbody = document.getElementById('tabela-participantes');
    const btnSalvar = document.getElementById('btn-salvar-participante');
    const btnCancelar = document.getElementById('btn-cancelar-edicao-participante');
    const tituloForm = document.getElementById('titulo-form-participante');
    const campoId = document.getElementById('id_participante');
    const campoBusca = document.getElementById('busca-participante');

    // TODO: aplicar mascara automatica no input de cpf quando fizer o css/js final

    function resetarFormulario() {
        form.reset();
        campoId.value = '';
        tituloForm.textContent = 'Cadastrar Participante';
        btnSalvar.textContent = 'Cadastrar participante';
        btnCancelar.hidden = true;
    }

    async function carregarParticipantes(busca = '') {
        const query = busca ? `?busca=${encodeURIComponent(busca)}` : '';
        const participantes = await requisitarApi(`participantes.php${query}`);

        if (participantes.length === 0) {
            limparTabela(tbody, 6, 'Nenhum participante encontrado.');
            return;
        }

        tbody.innerHTML = '';
        for (const p of participantes) {
            const tr = document.createElement('tr');
            tr.appendChild(criarCelulaTexto(String(p.id_participante)));
            tr.appendChild(criarCelulaTexto(p.nome));
            tr.appendChild(criarCelulaTexto(p.email));
            tr.appendChild(criarCelulaTexto(p.cpf));
            tr.appendChild(criarCelulaTexto(p.telefone || '-'));

            const tdAcoes = document.createElement('td');
            tdAcoes.appendChild(criarBotao('Editar', () => {
                campoId.value = p.id_participante;
                document.getElementById('nome').value = p.nome;
                document.getElementById('email').value = p.email;
                document.getElementById('cpf').value = p.cpf;
                document.getElementById('telefone').value = p.telefone || '';
                tituloForm.textContent = `Editar Participante #${p.id_participante}`;
                btnSalvar.textContent = 'Salvar alterações';
                btnCancelar.hidden = false;
                document.getElementById('nome').focus();
            }));

            tdAcoes.appendChild(document.createTextNode(' '));
            tdAcoes.appendChild(criarBotao('Excluir', async () => {
                if (!window.confirm(`Excluir o participante "${p.nome}"?`)) {
                    return;
                }
                try {
                    const res = await requisitarApi(`participantes.php?id=${p.id_participante}`, { method: 'DELETE' });
                    mostrarStatus(res.mensagem);
                    await carregarParticipantes(campoBusca.value.trim());
                } catch (erro) {
                    mostrarStatus(erro.message);
                }
            }));

            tr.appendChild(tdAcoes);
            tbody.appendChild(tr);
        }
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        btnSalvar.disabled = true;
        try {
            const payload = {
                nome: document.getElementById('nome').value,
                email: document.getElementById('email').value,
                cpf: document.getElementById('cpf').value,
                telefone: document.getElementById('telefone').value,
            };

            const id = campoId.value;
            const res = id
                ? await requisitarApi(`participantes.php?id=${id}`, { method: 'PUT', body: JSON.stringify(payload) })
                : await requisitarApi('participantes.php', { method: 'POST', body: JSON.stringify(payload) });

            mostrarStatus(res.mensagem);
            resetarFormulario();
            await carregarParticipantes(campoBusca.value.trim());
        } catch (erro) {
            mostrarStatus(erro.message);
        } finally {
            btnSalvar.disabled = false;
        }
    });

    btnCancelar.addEventListener('click', resetarFormulario);

    document.getElementById('form-busca-participante').addEventListener('submit', async (e) => {
        e.preventDefault();
        await carregarParticipantes(campoBusca.value.trim());
    });

    document.getElementById('btn-limpar-busca-participante').addEventListener('click', async () => {
        campoBusca.value = '';
        await carregarParticipantes();
    });

    await carregarParticipantes();
}

function preencherSelect(select, itens, rotuloVazio, obterValor, obterTexto) {
    select.innerHTML = '';
    const optVazia = document.createElement('option');
    optVazia.value = '';
    optVazia.textContent = rotuloVazio;
    select.appendChild(optVazia);

    for (const item of itens) {
        const opt = document.createElement('option');
        opt.value = String(obterValor(item));
        opt.textContent = obterTexto(item);
        select.appendChild(opt);
    }
}

async function iniciarInscricoes() {
    const selectEvento = document.getElementById('inscricao-evento');
    const selectTurno = document.getElementById('inscricao-turno');
    const selectParticipante = document.getElementById('inscricao-participante');
    const filtroEvento = document.getElementById('filtro-evento');
    const filtroParticipante = document.getElementById('filtro-participante');
    const tbody = document.getElementById('tabela-inscricoes');
    const btnRegistrar = document.getElementById('btn-registrar-inscricao');
    let eventosCache = [];

    function atualizarTurnosDoEvento() {
        const eventoSelecionado = eventosCache.find((e) => String(e.id_evento) === selectEvento.value);
        const turnos = eventoSelecionado && Array.isArray(eventoSelecionado.turnos) ? eventoSelecionado.turnos : [];

        if (turnos.length === 0) {
            preencherSelect(selectTurno, [], 'Selecione um evento primeiro', (t) => t, (t) => t);
            return;
        }

        preencherSelect(selectTurno, turnos, 'Selecione o turno', (t) => t, (t) => t.charAt(0).toUpperCase() + t.slice(1));
    }

    async function carregarOpcoes() {
        const [eventos, participantes] = await Promise.all([
            requisitarApi('eventos.php'),
            requisitarApi('participantes.php'),
        ]);
        eventosCache = eventos;

        preencherSelect(
            selectEvento,
            eventos,
            'Selecione um evento',
            (e) => e.id_evento,
            (e) => `${e.nome_evento} (${e.data_evento} - ${(e.turnos || []).join(', ')})`
        );
        preencherSelect(filtroEvento, eventos, 'Todos os eventos', (e) => e.id_evento, (e) => e.nome_evento);
        preencherSelect(selectParticipante, participantes, 'Selecione um participante', (p) => p.id_participante, (p) => `${p.nome} (${p.cpf})`);
        preencherSelect(filtroParticipante, participantes, 'Todos os participantes', (p) => p.id_participante, (p) => p.nome);
        atualizarTurnosDoEvento();
    }

    selectEvento.addEventListener('change', atualizarTurnosDoEvento);

    async function carregarInscricoes() {
        const params = new URLSearchParams();
        if (filtroEvento.value) params.set('id_evento', filtroEvento.value);
        if (filtroParticipante.value) params.set('id_participante', filtroParticipante.value);

        const query = params.toString() ? `?${params.toString()}` : '';
        const inscricoes = await requisitarApi(`inscricoes.php${query}`);

        if (inscricoes.length === 0) {
            limparTabela(tbody, 9, 'Nenhuma inscrição encontrada.');
            return;
        }

        tbody.innerHTML = '';
        for (const ins of inscricoes) {
            const tr = document.createElement('tr');
            tr.appendChild(criarCelulaTexto(String(ins.id_inscricao)));
            tr.appendChild(criarCelulaTexto(ins.nome_evento));
            tr.appendChild(criarCelulaTexto(ins.data_evento));
            tr.appendChild(criarCelulaTexto(ins.turno));
            tr.appendChild(criarCelulaTexto(ins.nome_participante));
            tr.appendChild(criarCelulaTexto(ins.email));
            tr.appendChild(criarCelulaTexto(ins.cpf));
            tr.appendChild(criarCelulaTexto(ins.data_inscricao));

            const tdAcoes = document.createElement('td');
            tdAcoes.appendChild(criarBotao('Cancelar inscrição', async () => {
                if (!window.confirm(`Cancelar inscrição #${ins.id_inscricao} de ${ins.nome_participante}?`)) {
                    return;
                }
                try {
                    const res = await requisitarApi(`inscricoes.php?id=${ins.id_inscricao}`, { method: 'DELETE' });
                    mostrarStatus(res.mensagem);
                    await carregarInscricoes();
                } catch (erro) {
                    mostrarStatus(erro.message);
                }
            }));
            tr.appendChild(tdAcoes);
            tbody.appendChild(tr);
        }
    }

    document.getElementById('form-inscricao').addEventListener('submit', async (e) => {
        e.preventDefault();
        btnRegistrar.disabled = true;
        try {
            const res = await requisitarApi('inscricoes.php', {
                method: 'POST',
                body: JSON.stringify({
                    id_evento: Number(selectEvento.value),
                    id_participante: Number(selectParticipante.value),
                    turno: selectTurno.value,
                }),
            });
            mostrarStatus(res.mensagem);
            await carregarInscricoes();
        } catch (erro) {
            mostrarStatus(erro.message);
        } finally {
            btnRegistrar.disabled = false;
        }
    });

    document.getElementById('form-filtro-inscricoes').addEventListener('submit', async (e) => {
        e.preventDefault();
        await carregarInscricoes();
    });

    document.getElementById('btn-limpar-filtro-inscricoes').addEventListener('click', async () => {
        filtroEvento.value = '';
        filtroParticipante.value = '';
        await carregarInscricoes();
    });

    await carregarOpcoes();
    await carregarInscricoes();
}

async function iniciarRelatorios() {
    const selectEvento = document.getElementById('relatorio-evento');
    const tbodyInscritos = document.getElementById('tabela-relatorio-inscritos');

    async function carregarListaInscritos(idEvento) {
        if (!idEvento) {
            limparTabela(tbodyInscritos, 7, 'Selecione um evento para visualizar os inscritos.');
            return;
        }

        const inscritos = await requisitarApi(`relatorios.php?tipo=inscritos&id_evento=${encodeURIComponent(idEvento)}`);
        if (inscritos.length === 0) {
            limparTabela(tbodyInscritos, 7, 'Nenhum participante inscrito neste evento.');
            return;
        }

        tbodyInscritos.innerHTML = '';
        for (const item of inscritos) {
            const tr = document.createElement('tr');
            tr.appendChild(criarCelulaTexto(String(item.id_inscricao)));
            tr.appendChild(criarCelulaTexto(item.turno));
            tr.appendChild(criarCelulaTexto(item.nome));
            tr.appendChild(criarCelulaTexto(item.email));
            tr.appendChild(criarCelulaTexto(item.cpf));
            tr.appendChild(criarCelulaTexto(item.telefone || '-'));
            tr.appendChild(criarCelulaTexto(item.data_inscricao));
            tbodyInscritos.appendChild(tr);
        }
    }

    const eventos = await requisitarApi('eventos.php');
    preencherSelect(selectEvento, eventos, 'Selecione um evento', (e) => e.id_evento, (e) => `${e.nome_evento} (${e.data_evento})`);

    document.getElementById('form-relatorio-inscritos').addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            await carregarListaInscritos(selectEvento.value);
        } catch (erro) {
            mostrarStatus(erro.message);
        }
    });

    document.getElementById('btn-imprimir-relatorio').addEventListener('click', () => {
        window.print();
    });

    const params = new URLSearchParams(window.location.search);
    const idInicial = params.get('id_evento');
    if (idInicial) {
        selectEvento.value = idInicial;
        await carregarListaInscritos(idInicial);
    }

    await carregarTabelaOcupacao();
}

document.addEventListener('DOMContentLoaded', () => {
    const pagina = document.body.dataset.pagina;

    if (pagina === 'dashboard') {
        iniciarDashboard();
    } else if (pagina === 'eventos') {
        iniciarEventos().catch((erro) => mostrarStatus(erro.message));
    } else if (pagina === 'participantes') {
        iniciarParticipantes().catch((erro) => mostrarStatus(erro.message));
    } else if (pagina === 'inscricoes') {
        iniciarInscricoes().catch((erro) => mostrarStatus(erro.message));
    } else if (pagina === 'relatorios') {
        iniciarRelatorios().catch((erro) => mostrarStatus(erro.message));
    }
});
