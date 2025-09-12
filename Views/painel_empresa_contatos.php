<?php
session_start();
if (!in_array('editar_empresa', $_SESSION['permissoes'])) {
    header('Location: ../Views/acesso_restrito.php');
    exit();
}
include_once '../helpers/header.php';
include_once '../helpers/header_empresa.php';
?>

<main class="container-fluid my-4 d-flex flex-column align-items-center">
    <div class="col-md-10 p-4">
        <h3 class="mb-4">Gerenciar Contatos e Responsáveis Técnicos</h3>
        <div id="page-alert-container" class="mt-3"></div>

        <!-- SEÇÃO 1: DADOS BÁSICOS DA EMPRESA -->
        <div class="card mb-4 mt-3 shadow-sm">
            <div class="card-header bg-danger text-white">
                Informações da Empresa
            </div>
            <div class="card-body" id="info-basica-body">
                <p><strong>Razão Social:</strong> <span id="displayRazaoSocial">Carregando...</span></p>
                <p><strong>CNPJ:</strong> <span id="displayCnpj">Carregando...</span></p>
            </div>
        </div>

        <!-- SEÇÃO 2: CONTATOS DA EMPRESA -->
        <div class="card mb-4 mt-3 shadow-sm">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                Contatos da Empresa
                <button id="openContatosModalBtn" class="btn btn-outline-light btn-sm" disabled>
                    <i class="bi bi-pencil"></i> Editar
                </button>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush" id="contatos-list">
                    <li class="list-group-item">Carregando...</li>
                </ul>
            </div>
        </div>

        <!-- SEÇÃO 3: RESPONSÁVEIS TÉCNICOS -->
        <div class="card mb-4 mt-3 shadow-sm">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                Responsáveis Técnicos
                <button id="openResponsaveisModalBtn" class="btn btn-outline-light btn-sm" disabled>
                    <i class="bi bi-pencil"></i> Editar
                </button>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush" id="responsaveis-list">
                    <li class="list-group-item">Carregando...</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<!-- MODAL DE EDIÇÃO DE CONTATOS -->
<div class="modal fade" id="contatosModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Contatos da Empresa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modal-contatos-alert-container"></div>
                <form id="editContatosForm">
                    <!-- Conteúdo gerado dinamicamente pelo JavaScript -->
                </form>
                <button type="button" id="addContatoBtn" class="btn btn-primary btn-sm mt-3">Adicionar Contato</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="submit" form="editContatosForm" class="btn btn-success" id="saveContatosBtn">Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE EDIÇÃO DE RESPONSÁVEIS TÉCNICOS -->
<div class="modal fade" id="responsaveisModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Responsáveis Técnicos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modal-responsaveis-alert-container"></div>
                <form id="editResponsaveisForm">
                    <!-- Conteúdo gerado dinamicamente pelo JavaScript -->
                </form>
                <button type="button" id="addResponsavelBtn" class="btn btn-primary btn-sm mt-3">Adicionar Responsável</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="submit" form="editResponsaveisForm" class="btn btn-success" id="saveResponsaveisBtn">Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Variáveis globais para armazenar os dados vindos da API
    let empresaData = null;
    let contatosData = [];
    let responsaveisData = [];

    const contatosModal = new bootstrap.Modal(document.getElementById('contatosModal'));
    const responsaveisModal = new bootstrap.Modal(document.getElementById('responsaveisModal'));

    /**
     * Exibe um alerta na tela.
     * @param {string} containerId - O ID do container onde o alerta será exibido.
     * @param {string} mensagem - A mensagem a ser exibida.
     * @param {string} tipo - O tipo de alerta (ex: 'success', 'danger', 'warning').
     * @param {number} duracao - Duração em ms para o alerta desaparecer (opcional).
     */
    function exibirAlerta(containerId, mensagem, tipo, duracao = 5000) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = `<div class="alert alert-${tipo} alert-dismissible fade show" role="alert">${mensagem}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
        setTimeout(() => { if (container.innerHTML) { container.innerHTML = ''; } }, duracao);
    }

    /**
     * Carrega e renderiza todos os dados do painel.
     */
    async function carregarPainel() {
        try {
            const response = await fetch('../api/gerenciar_empresas_contatos_api.php?action=carregar_painel_contatos');
            const data = await response.json();

            if (!data.success) {
                exibirAlerta('page-alert-container', data.message, 'danger');
                return;
            }
            
            // Armazena os dados globalmente
            empresaData = data.empresa;
            contatosData = data.contatos;
            responsaveisData = data.responsaveis;

            renderizarPainel();

        } catch (error) {
            exibirAlerta('page-alert-container', 'Erro de rede ao carregar os dados.', 'danger');
        }
    }

    /**
     * Renderiza os dados nas seções da página.
     */
    function renderizarPainel() {
        if (!empresaData) {
            document.getElementById('info-basica-body').innerHTML = '<div class="alert alert-warning">Não há uma empresa vinculada e autorizada para este usuário.</div>';
            return;
        }

        // Habilita botões de edição
        document.getElementById('openContatosModalBtn').disabled = false;
        document.getElementById('openResponsaveisModalBtn').disabled = false;

        // Renderiza Seção 1: Info Básica
        document.getElementById('displayRazaoSocial').textContent = empresaData.razao_social;
        document.getElementById('displayCnpj').textContent = empresaData.cnpj;

        // Renderiza Seção 2: Contatos
        const contatosList = document.getElementById('contatos-list');
        contatosList.innerHTML = '';
        if (contatosData.length > 0) {
            contatosData.forEach(c => {
                contatosList.innerHTML += `<li class="list-group-item"><strong>${c.Nome}:</strong> ${c.setor_funcao || 'N/D'}<br><small class="text-muted">${c.email || 'E-mail não informado'} | ${c.telefone || 'Telefone não informado'}</small></li>`;
            });
        } else {
            contatosList.innerHTML = '<li class="list-group-item text-muted">Nenhum contato cadastrado.</li>';
        }

        // Renderiza Seção 3: Responsáveis Técnicos
        const responsaveisList = document.getElementById('responsaveis-list');
        responsaveisList.innerHTML = '';
        if (responsaveisData.length > 0) {
            responsaveisData.forEach(r => {
                responsaveisList.innerHTML += `<li class="list-group-item"><strong>${r.nome}:</strong> ${r.crea_cau || 'N/D'}<br><small class="text-muted">${r.email || 'E-mail não informado'} | ${r.telefone || 'Telefone não informado'}</small></li>`;
            });
        } else {
            responsaveisList.innerHTML = '<li class="list-group-item text-muted">Nenhum responsável técnico cadastrado.</li>';
        }
    }
    
    /**
     * Gera uma linha de formulário para um contato ou responsável.
     * @param {object} item - O objeto de dados do item.
     * @param {number} index - O índice do item.
     * @param {boolean} isResponsavel - Se a linha é para um responsável técnico.
     */
    function createFormRow(item, index, isResponsavel = false) {
        const id = item.id || `new_${index}`;
        
        let fieldsHtml = '';
        if (isResponsavel) {
            fieldsHtml = `
                <div class="col-md-3">
                    <label class="form-label">Nome</label>
                    <input type="text" class="form-control" name="responsaveis[${id}][nome]" value="${item.nome || ''}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">E-mail</label>
                    <input type="email" class="form-control" name="responsaveis[${id}][email]" value="${item.email || ''}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">CREA/CAU</label>
                    <input type="text" class="form-control" name="responsaveis[${id}][crea_cau]" value="${item.crea_cau || ''}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Telefone</label>
                    <input type="text" class="form-control" name="responsaveis[${id}][telefone]" value="${item.telefone || ''}">
                </div>
            `;
        } else { // Contato
            fieldsHtml = `
                <div class="col-md-3">
                    <label class="form-label">Nome</label>
                    <input type="text" class="form-control" name="contatos[${id}][Nome]" value="${item.Nome || ''}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Telefone</label>
                    <input type="text" class="form-control" name="contatos[${id}][telefone]" value="${item.telefone || ''}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">E-mail</label>
                    <input type="email" class="form-control" name="contatos[${id}][email]" value="${item.email || ''}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Setor/Função</label>
                    <input type="text" class="form-control" name="contatos[${id}][setor_funcao]" value="${item.setor_funcao || ''}">
                </div>
            `;
        }
        
        return `
            <div class="row g-3 align-items-center mb-3 border p-2 rounded" data-id="${id}">
                <input type="hidden" name="${isResponsavel ? 'responsaveis' : 'contatos'}[${id}][id]" value="${item.id || ''}">
                ${fieldsHtml}
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm mt-4 remove-row-btn">&times;</button>
                </div>
            </div>
        `;
    }
    
    // ABRIR MODAL DE CONTATOS
    document.getElementById('openContatosModalBtn').addEventListener('click', () => {
        const form = document.getElementById('editContatosForm');
        form.innerHTML = '';
        contatosData.forEach((item, index) => {
            form.innerHTML += createFormRow(item, index, false);
        });
        contatosModal.show();
    });

    // ABRIR MODAL DE RESPONSÁVEIS
    document.getElementById('openResponsaveisModalBtn').addEventListener('click', () => {
        const form = document.getElementById('editResponsaveisForm');
        form.innerHTML = '';
        responsaveisData.forEach((item, index) => {
            form.innerHTML += createFormRow(item, index, true);
        });
        responsaveisModal.show();
    });
    
    // ADICIONAR NOVO CONTATO
    document.getElementById('addContatoBtn').addEventListener('click', () => {
        const form = document.getElementById('editContatosForm');
        const newIndex = form.children.length;
        const newItem = { id: null, Nome: '', telefone: '', email: '', setor_funcao: '' };
        form.insertAdjacentHTML('beforeend', createFormRow(newItem, newIndex, false));
    });

    // ADICIONAR NOVO RESPONSÁVEL TÉCNICO
    document.getElementById('addResponsavelBtn').addEventListener('click', () => {
        const form = document.getElementById('editResponsaveisForm');
        const newIndex = form.children.length;
        const newItem = { id: null, nome: '', email: '', crea_cau: '', telefone: '' };
        form.insertAdjacentHTML('beforeend', createFormRow(newItem, newIndex, true));
    });

    // REMOVER LINHA
    document.getElementById('editContatosForm').addEventListener('click', (e) => {
        if (e.target && e.target.classList.contains('remove-row-btn')) {
            e.target.closest('.row').remove();
        }
    });
    document.getElementById('editResponsaveisForm').addEventListener('click', (e) => {
        if (e.target && e.target.classList.contains('remove-row-btn')) {
            e.target.closest('.row').remove();
        }
    });

    /**
     * Coleta os dados do formulário e envia para a API.
     * @param {string} formId - O ID do formulário a ser salvo.
     * @param {string} action - A ação da API ('salvar_contatos' ou 'salvar_responsaveis').
     * @param {string} alertContainerId - O ID do container de alerta.
     * @param {object} modalInstance - A instância do modal.
     * @param {string} saveBtnId - O ID do botão de salvar.
     */
    async function handleSave(formId, action, alertContainerId, modalInstance, saveBtnId) {
        const form = document.getElementById(formId);
        const saveBtn = document.getElementById(saveBtnId);
        
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';

        const dataToSave = [];
        form.querySelectorAll('.row[data-id]').forEach(row => {
            const rowData = {};
            row.querySelectorAll('input').forEach(input => {
                const match = input.name.match(/\[([^\]]+)\]$/);
                if (match) {
                    rowData[match[1]] = input.value;
                }
            });
            dataToSave.push(rowData);
        });
        
        const formData = new FormData();
        formData.append('action', action);
        formData.append('empresa_id', empresaData.id);
        formData.append('dados', JSON.stringify(dataToSave));

        try {
            const response = await fetch('../api/gerenciar_empresas_contatos_api.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                exibirAlerta(alertContainerId, result.message, 'success');
                setTimeout(() => {
                    modalInstance.hide();
                    carregarPainel(); // Recarrega tudo
                }, 1500);
            } else {
                exibirAlerta(alertContainerId, result.message, 'danger');
            }
        } catch (error) {
            exibirAlerta(alertContainerId, 'Erro de rede ao salvar.', 'danger');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = 'Salvar Alterações';
        }
    }
    
    // Event Listeners para salvar
    document.getElementById('editContatosForm').addEventListener('submit', (e) => {
        e.preventDefault();
        handleSave('editContatosForm', 'salvar_contatos', 'modal-contatos-alert-container', contatosModal, 'saveContatosBtn');
    });

    document.getElementById('editResponsaveisForm').addEventListener('submit', (e) => {
        e.preventDefault();
        handleSave('editResponsaveisForm', 'salvar_responsaveis', 'modal-responsaveis-alert-container', responsaveisModal, 'saveResponsaveisBtn');
    });

    // Inicia o carregamento dos dados
    carregarPainel();
});
</script>

<?php
include_once '../helpers/footer.php';
?>
