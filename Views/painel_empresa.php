<?php
// Views/painel_empresa.php
session_start();

// Verifica a permissão para 'editar_empresa'
if (!in_array('editar_empresa', $_SESSION['permissoes'])) {
    header('Location: ../Views/acesso_restrito.php');
    exit();
}
include_once '../helpers/header.php';
include_once '../helpers/header_empresa.php';
?>

<main class="container-fluid my-4 d-flex flex-column align-items-center">
    <div class="col-md-10 p-4">
        <h3 class="mb-4">Painel da Empresa</h3>
        <div id="page-alert-container" class="mt-3"></div>

        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active bg-light" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">Informações</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">Vínculo(s)</button>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" aria-labelledby="home-tab" tabindex="0">
                <div class="card mb-4 mt-3 shadow-sm">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        Informações da Empresa
                        <button id="openEditModalBtn" class="btn btn-outline-light btn-sm" disabled>
                            <i class="bi bi-pencil"></i> Editar
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="no-link-alert" class="alert alert-warning d-none" role="alert">
                            Não há uma empresa vinculada e autorizada para este usuário.
                            <br>
                            Você pode solicitar um novo vínculo.
                        </div>
                        <div id="company-display-data">
                            <p><strong>Razão Social:</strong> <span id="displayRazaoSocial">Carregando...</span></p>
                            <p><strong>CNPJ:</strong> <span id="displayCnpj">Carregando...</span></p>
                            <p><strong>Risco da Edificação:</strong> <span id="displayRiscoEdificacao">Carregando...</span></p>
                            <p><strong>Área Construída:</strong> <span id="displayAreaConstruida">Carregando...</span> m²</p>
                            <p><strong>Possui PSCIP?</strong> <span id="displayPossuiPscip">Carregando...</span></p>
                            <p><strong>E-mail de Contato:</strong> <span id="displayEmailContato">Carregando...</span></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-danger text-white">Suas Solicitações de Vínculo</div>
                    <div class="card-body">
                        <div id="vinculacoesContainer" class="list-group">
                            <p class="text-center text-muted">Carregando solicitações...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal de Edição -->
<div class="modal fade" id="editCompanyModal" tabindex="-1" aria-labelledby="editCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCompanyModalLabel">Editar Informações da Empresa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modal-alert-container"></div>
                <form id="editCompanyForm">
                    <input type="hidden" id="formCnpj" name="cnpj">
                    <div class="mb-3">
                        <label for="formRazaoSocial" class="form-label">Razão Social</label>
                        <input type="text" class="form-control" id="formRazaoSocial" name="razao_social" required>
                    </div>
                    <div class="mb-3">
                        <label for="formRiscoEdificacao" class="form-label">Risco da Edificação</label>
                        <select class="form-select" id="formRiscoEdificacao" name="risco_edificacao" required>
                            <option value="Risco Baixo">Baixo</option>
                            <option value="Risco Médio">Médio</option>
                            <option value="Risco Alto">Alto</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="formAreaConstruida" class="form-label">Área Construída (m²)</label>
                        <input type="number" step="0.01" class="form-control" id="formAreaConstruida" name="area_construida_m2" required>
                    </div>
                    <div class="mb-3">
                        <label for="formPossuiPscip" class="form-label">Possui PSCIP?</label>
                        <select class="form-select" id="formPossuiPscip" name="possui_pscip" required>
                            <option value="Possui">Sim</option>
                            <option value="Não possui">Não</option>
                            <option value="Não informado">Não informado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="formEmailContato" class="form-label">E-mail de Contato</label>
                        <input type="email" class="form-control" id="formEmailContato" name="email_contato" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <!-- ** MUDANÇA 1: Adicionado um ID ao botão de salvar ** -->
                <button type="submit" form="editCompanyForm" class="btn btn-success" id="saveChangesBtn">Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>


<script>
    // Função de Alerta (sem alterações)
    function exibirAlerta(containerId, mensagem, tipo, duracao = 5000) {
        // ... (código da função inalterado)
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = `<div class="alert alert-${tipo} alert-dismissible fade show" role="alert">${mensagem}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
        setTimeout(() => { if (container.innerHTML) { container.innerHTML = ''; } }, duracao);
    }

    // Função para carregar o painel (sem alterações)
    async function carregarPainel() {
        // ... (código da função inalterado)
        try {
            const response = await fetch('../api/gerenciar_empresas_api.php?action=carregar_painel');
            const data = await response.json();
            const openEditModalBtn = document.getElementById('openEditModalBtn');
            const noLinkAlert = document.getElementById('no-link-alert');
            if (data.success && data.hasAuthorizedLink) {
                noLinkAlert.classList.add('d-none');
                document.getElementById('company-display-data').classList.remove('d-none');
                const empresa = data.empresa;
                document.getElementById('displayRazaoSocial').textContent = empresa.razao_social;
                document.getElementById('displayCnpj').textContent = empresa.cnpj;
                document.getElementById('displayRiscoEdificacao').textContent = empresa.risco_edificacao;
                document.getElementById('displayAreaConstruida').textContent = empresa.area_construida_m2;
                document.getElementById('displayPossuiPscip').textContent = empresa.possui_pscip;
                document.getElementById('displayEmailContato').textContent = empresa.email_contato;
                openEditModalBtn.disabled = false;
            } else {
                noLinkAlert.classList.remove('d-none');
                document.getElementById('company-display-data').classList.add('d-none');
                openEditModalBtn.disabled = true;
            }
            const vinculacoesContainer = document.getElementById('vinculacoesContainer');
            vinculacoesContainer.innerHTML = '';
            if (data.vinculacoes && data.vinculacoes.length > 0) {
                 data.vinculacoes.forEach(vinculo => {
                    let statusText = ''; let statusClass = '';
                    switch (vinculo.status_vinculo) {
                        case 0: statusText = 'Pendente'; statusClass = 'text-warning'; break;
                        case 1: statusText = 'Autorizado'; statusClass = 'text-success'; break;
                        case 2: statusText = 'Rejeitado'; statusClass = 'text-danger'; break;
                        default: statusText = 'Desconhecido'; statusClass = 'text-muted';
                    }
                    const vinculoItem = document.createElement('div');
                    vinculoItem.className = 'list-group-item list-group-item-action flex-column align-items-start';
                    vinculoItem.innerHTML = `<div class="d-flex w-100 justify-content-between"><h5 class="mb-1">${vinculo.razao_social}</h5><small class="${statusClass}"><strong>${statusText}</strong></small></div><p class="mb-1">CNPJ: ${vinculo.cnpj}</p>${vinculo.status_vinculo == 2 && vinculo.motivo_rejeicao ? `<small class="text-danger">Motivo da Rejeição: ${vinculo.motivo_rejeicao}</small>` : ''}`;
                    vinculacoesContainer.appendChild(vinculoItem);
                });
            } else {
                vinculacoesContainer.innerHTML = '<p class="text-center text-muted">Nenhuma solicitação de vínculo encontrada.</p>';
            }
        } catch (error) {
            exibirAlerta('page-alert-container', 'Erro de rede ao carregar o painel.', 'danger');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const editCompanyModalEl = document.getElementById('editCompanyModal');
        const editModal = new bootstrap.Modal(editCompanyModalEl);
        // ** MUDANÇA 2: Obter referência ao botão de salvar **
        const saveBtn = document.getElementById('saveChangesBtn');

        // Evento para abrir o modal
        document.getElementById('openEditModalBtn').addEventListener('click', () => {
            document.getElementById('modal-alert-container').innerHTML = '';
            
            // ** MUDANÇA 3: Garantir que o botão esteja habilitado ao abrir o modal **
            saveBtn.disabled = false;
            saveBtn.innerHTML = 'Salvar Alterações'; // Reseta o texto do botão

            document.getElementById('formRazaoSocial').value = document.getElementById('displayRazaoSocial').textContent;
            document.getElementById('formCnpj').value = document.getElementById('displayCnpj').textContent;
            document.getElementById('formRiscoEdificacao').value = document.getElementById('displayRiscoEdificacao').textContent;
            document.getElementById('formAreaConstruida').value = document.getElementById('displayAreaConstruida').textContent;
            document.getElementById('formPossuiPscip').value = document.getElementById('displayPossuiPscip').textContent;
            document.getElementById('formEmailContato').value = document.getElementById('displayEmailContato').textContent;
            
            editModal.show();
        });

        // Evento para salvar os dados do formulário do modal
        document.getElementById('editCompanyForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // ** MUDANÇA 4: Desabilitar botão e mostrar feedback de carregamento **
            saveBtn.disabled = true;
            saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...`;

            const formData = new FormData(this);
            formData.append('action', 'salvar_empresa');

            try {
                const response = await fetch('../api/gerenciar_empresas_api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    exibirAlerta('modal-alert-container', data.message, 'success');
                    document.getElementById('displayRazaoSocial').textContent = formData.get('razao_social');
                    document.getElementById('displayRiscoEdificacao').textContent = formData.get('risco_edificacao');
                    document.getElementById('displayAreaConstruida').textContent = formData.get('area_construida_m2');
                    document.getElementById('displayPossuiPscip').textContent = formData.get('possui_pscip');
                    document.getElementById('displayEmailContato').textContent = formData.get('email_contato');
                    
                    // O botão permanece desabilitado, forçando o fechamento do modal
                    setTimeout(() => editModal.hide(), 2000);

                } else {
                    exibirAlerta('modal-alert-container', data.message, 'danger');
                    // ** MUDANÇA 5: Reabilitar o botão em caso de erro para permitir nova tentativa **
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = 'Salvar Alterações';
                }
            } catch (error) {
                exibirAlerta('modal-alert-container', 'Erro de rede ao salvar. Tente novamente.', 'danger');
                // ** MUDANÇA 6: Reabilitar também em caso de erro de rede **
                saveBtn.disabled = false;
                saveBtn.innerHTML = 'Salvar Alterações';
            }
        });

        carregarPainel();
    });
</script>
<?php
include_once '../helpers/footer.php';
?>