<?php
///Views/busca_por_nome.php
include_once '../helpers/header.php';
include_once '../helpers/header_user.php';
?>

<main class="container-fluid my-4 d-flex flex-column align-items-center">

    <div class="col-md-6 p-3">
        <div class="input-group mb-3">
            <span class="input-group-text" id="basic-addon1">Nome da empresa</span>
            <input type="text" class="form-control" placeholder="Digite aqui" aria-label="Nome da empresa" aria-describedby="search-button" id="empresa-input">
            <button class="btn btn-outline-secondary" type="button" id="search-button">Buscar</button>
        </div>
    </div>

    <div id="alerts-container" class="mt-3 col-md-6"></div>

    <div id="resultados-container" class="mt-4 col-md-10">
    </div>

</main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('search-button').addEventListener('click', () => {
        buscarEmpresa();
    });

    document.getElementById('empresa-input').addEventListener('keypress', (event) => {
        if (event.key === 'Enter') {
            buscarEmpresa();
        }
    });

    async function buscarEmpresa() {
        const inputElement = document.getElementById('empresa-input');
        const nomeEmpresa = inputElement.value.trim();
        const alertsContainer = document.getElementById('alerts-container');
        const resultadosContainer = document.getElementById('resultados-container');

        // Limpa resultados e alertas anteriores
        alertsContainer.innerHTML = '';
        resultadosContainer.innerHTML = '';

        if (nomeEmpresa === '') {
            showAlert('danger', 'O campo de pesquisa não pode estar vazio.');
            return;
        }

        try {
            // Ajuste no caminho da API
            const response = await fetch(`../api/busca_empresa.php?nome=${encodeURIComponent(nomeEmpresa)}`);
            const result = await response.json();

            if (result.success) {
                const empresa = result.data;
                displayEmpresaDetalhes(empresa);
            } else {
                showAlert('warning', result.message);
            }

        } catch (error) {
            console.error('Erro na requisição:', error);
            showAlert('danger', 'Ocorreu um erro ao buscar a empresa. Tente novamente.');
        }
    }

    function showAlert(type, message) {
        const alertsContainer = document.getElementById('alerts-container');
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        alertsContainer.innerHTML = alertHtml;
    }

    function displayEmpresaDetalhes(empresa) {
        const resultadosContainer = document.getElementById('resultados-container');
        let statusBadge = '';
        if (empresa.possui_pscip === 'Possui') {
            statusBadge = `<span class="badge text-bg-success">Possui</span>`;
        } else if (empresa.possui_pscip === 'Não possui') {
            statusBadge = `<span class="badge text-bg-danger">Não Possui</span>`;
        } else {
            statusBadge = `<span class="badge text-bg-secondary">Não Informado</span>`;
        }

        let responsaveisList = '';
        if (empresa.responsaveis_tecnicos && empresa.responsaveis_tecnicos.length > 0) {
            empresa.responsaveis_tecnicos.forEach(resp => {
                const nome = resp.nome || '-';
                const email = resp.email ? ` - ${resp.email}` : '';
                responsaveisList += `<li class="list-group-item">${nome}${email}</li>`;
            });
        } else {
            responsaveisList = `<li class="list-group-item">Nenhum responsável técnico cadastrado.</li>`;
        }

        let contatosList = '';
        if (empresa.contatos && empresa.contatos.length > 0) {
            empresa.contatos.forEach(contato => {
                const nome = contato.nome || '-';
                const setor = contato.setor_funcao || '-';
                const telefone = contato.telefone ? `(${contato.telefone})` : '';

                const contatoString = `${nome} - ${setor} ${telefone}`.trim();
                contatosList += `<li class="list-group-item">${contatoString}</li>`;
            });
        } else {
            contatosList = `<li class="list-group-item">Nenhum contato cadastrado.</li>`;
        }

        // Nova seção para Contribuições
        let contribuicoesHtml = '';
        if (empresa.contribuicoes && empresa.contribuicoes.length > 0) {
            let contribuicoesList = '';
            empresa.contribuicoes.forEach(contribuicao => {
                contribuicoesList += `<li class="list-group-item">${contribuicao.sugestao_contribuicao}</li>`;
            });
            contribuicoesHtml = `
                <dt class="col-sm-4">Contribuições:</dt>
                <dd class="col-sm-8">
                    <ul class="list-unstyled mb-0">
                        ${contribuicoesList}
                    </ul>
                </dd>
            `;
        } else {
            contribuicoesHtml = `
                <dt class="col-sm-4">Contribuições:</dt>
                <dd class="col-sm-8">Nenhuma contribuição encontrada.</dd>
            `;
        }

        const htmlContent = `
            <div class="row g-4">
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">${empresa.razao_social}</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-4">CNPJ:</dt>
                                <dd class="col-sm-8">${empresa.cnpj || 'Não informado'}</dd>

                                <dt class="col-sm-4">Status PSCIP:</dt>
                                <dd class="col-sm-8">${statusBadge}</dd>

                                <dt class="col-sm-4">Risco da Edificação:</dt>
                                <dd class="col-sm-8">${empresa.risco_edificacao || 'Não informado'}</dd>

                                <dt class="col-sm-4">Área Construída:</dt>
                                <dd class="col-sm-8">${empresa.area_construida_m2 ? `${empresa.area_construida_m2} m²` : 'Não informada'}</dd>

                                <dt class="col-sm-4">Email Principal:</dt>
                                <dd class="col-sm-8">${empresa.email_contato || 'Não informado'}</dd>
                                
                                ${contribuicoesHtml}
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">Responsáveis Técnicos</div>
                        <ul class="list-group list-group-flush">
                            ${responsaveisList}
                        </ul>
                    </div>
                    <div class="card">
                        <div class="card-header">Contatos na Empresa</div>
                        <ul class="list-group list-group-flush">
                            ${contatosList}
                        </ul>
                    </div>
                </div>
            </div>
        `;
        resultadosContainer.innerHTML = htmlContent;
    }
</script>
<?php
include_once '../helpers/footer.php';
?>