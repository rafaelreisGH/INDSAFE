<?php
include_once '../helpers/header.php';
include_once '../helpers/header_gestor.php';
?>

<main class="container-fluid my-4 d-flex flex-column align-items-center">

    <div class="col-md-6 p-3">
        <div class="input-group mb-3">
            <span class="input-group-text" id="basic-addon1">Maquinário</span>
            <select class="form-select" id="maquinario-select">
                <option selected disabled>Selecione o maquinário</option>
                <option value="3">Empilhadeira</option>
                <option value="5">BobCat</option>
                <option value="6">Caminhão Munck</option>
                <option value="7">Caminhão Pipa</option>
                <option value="8">Escavadeira hidráulica</option>
                <option value="9">Pá-carregadeira</option>
                <option value="10">Retroescavadeira</option>
            </select>
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
        buscarEmpresas();
    });

    async function buscarEmpresas() {
        const selectElement = document.getElementById('maquinario-select');
        const maquinarioId = selectElement.value;
        const maquinarioNome = selectElement.options[selectElement.selectedIndex].text;
        const alertsContainer = document.getElementById('alerts-container');
        const resultadosContainer = document.getElementById('resultados-container');

        alertsContainer.innerHTML = '';
        resultadosContainer.innerHTML = '';

        if (maquinarioId === 'Selecione o maquinário') {
            showAlert('danger', 'Por favor, selecione um maquinário para a busca.');
            return;
        }

        try {
            // AQUI ESTÁ A MUDANÇA: enviando o ID para o backend
            const response = await fetch(`../api/busca_maquinario.php?maquinario=${encodeURIComponent(maquinarioId)}`);
            const result = await response.json();

            if (result.success) {
                if (result.data.length > 0) {
                    // AQUI ESTÁ A MUDANÇA: passando o NOME para a função de exibição
                    displayEmpresasDetalhes(result.data, maquinarioNome);
                } else {
                    showAlert('warning', `Nenhuma empresa encontrada com o maquinário "${maquinarioNome}".`);
                }
            } else {
                showAlert('warning', result.message);
            }

        } catch (error) {
            console.error('Erro na requisição:', error);
            showAlert('danger', 'Ocorreu um erro ao buscar as empresas. Tente novamente.');
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

    function displayEmpresasDetalhes(empresas, maquinario) {
        const resultadosContainer = document.getElementById('resultados-container');
        let allHtmlContent = '';

        empresas.forEach(empresa => {
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

            const htmlContent = `
                <div class="row g-4 mb-4">
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">${empresa.razao_social}</h5>
                            </div>
                            <div class="card-body">
                                <dl class="row">
                                    <dt class="col-sm-4">Maquinário:</dt>
                                    <dd class="col-sm-8"><span class="badge text-bg-info">${maquinario}</span></dd>
                                    
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
            allHtmlContent += htmlContent;
        });

        resultadosContainer.innerHTML = allHtmlContent;
    }
</script>
<?php
include_once '../helpers/footer.php';
?>