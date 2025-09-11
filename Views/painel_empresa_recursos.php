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
        <h3 class="mb-4">Painel de Recursos e Maquinários</h3>
        <div id="page-alert-container" class="mt-3"></div>

        <!-- SEÇÃO 1: DADOS BÁSICOS -->
        <div class="card mb-4 mt-3 shadow-sm">
            <div class="card-header bg-danger text-white">
                Informações da Empresa
            </div>
            <div class="card-body" id="info-basica-body">
                <p><strong>Razão Social:</strong> <span id="displayRazaoSocial">Carregando...</span></p>
                <p><strong>CNPJ:</strong> <span id="displayCnpj">Carregando...</span></p>
            </div>
        </div>

        <!-- SEÇÃO 2: RECURSOS ESSENCIAIS (ÁGUA E LGE) -->
        <div class="card mb-4 mt-3 shadow-sm">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                Recursos Essenciais (Água e LGE)
                <button id="openRecursosModalBtn" class="btn btn-outline-light btn-sm" disabled>
                    <i class="bi bi-pencil"></i> Editar
                </button>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush" id="recursos-essenciais-list">
                    <li class="list-group-item">Carregando...</li>
                </ul>
            </div>
        </div>

        <!-- SEÇÃO 3: MAQUINÁRIOS -->
        <div class="card mb-4 mt-3 shadow-sm">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                Maquinários e Equipamentos
                <button id="openMaquinariosModalBtn" class="btn btn-outline-light btn-sm" disabled>
                    <i class="bi bi-pencil"></i> Editar
                </button>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush" id="maquinarios-list">
                    <li class="list-group-item">Carregando...</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<!-- MODAL DE EDIÇÃO DE RECURSOS ESSENCIAIS -->
<div class="modal fade" id="recursosModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Recursos Essenciais</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modal-recursos-alert-container"></div>
                <form id="editRecursosForm">
                    <!-- Conteúdo gerado dinamicamente pelo JavaScript -->
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="submit" form="editRecursosForm" class="btn btn-success" id="saveRecursosBtn">Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE EDIÇÃO DE MAQUINÁRIOS -->
<div class="modal fade" id="maquinariosModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Maquinários e Equipamentos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modal-maquinarios-alert-container"></div>
                <form id="editMaquinariosForm">
                     <!-- Conteúdo gerado dinamicamente pelo JavaScript -->
                </form>
                 <button type="button" id="addMaquinarioBtn" class="btn btn-primary btn-sm mt-3">Adicionar Maquinário</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="submit" form="editMaquinariosForm" class="btn btn-success" id="saveMaquinariosBtn">Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Variáveis globais para armazenar os dados vindos da API
    let empresaData = null;
    let recursosEssenciaisData = [];
    let maquinariosData = [];
    let catalogosData = {};
    
    const recursosModal = new bootstrap.Modal(document.getElementById('recursosModal'));
    const maquinariosModal = new bootstrap.Modal(document.getElementById('maquinariosModal'));

    function exibirAlerta(containerId, mensagem, tipo, duracao = 5000) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = `<div class="alert alert-${tipo} alert-dismissible fade show" role="alert">${mensagem}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
        setTimeout(() => { if (container.innerHTML) { container.innerHTML = ''; } }, duracao);
    }

    // Função principal para carregar e renderizar todos os dados
    async function carregarPainel() {
        try {
            const response = await fetch('../api/gerenciar_empresas_recursos_api.php?action=carregar_painel_recursos');
            const data = await response.json();

            if (!data.success) {
                exibirAlerta('page-alert-container', data.message, 'danger');
                return;
            }
            
            // Armazena os dados globalmente
            empresaData = data.empresa;
            recursosEssenciaisData = data.recursos_essenciais;
            maquinariosData = data.maquinarios;
            catalogosData = data.catalogos;

            renderizarPainel();

        } catch (error) {
            exibirAlerta('page-alert-container', 'Erro de rede ao carregar os dados.', 'danger');
        }
    }

    function renderizarPainel() {
        if (!empresaData) {
            document.getElementById('info-basica-body').innerHTML = '<div class="alert alert-warning">Não há uma empresa vinculada e autorizada para este usuário.</div>';
            return;
        }

        // Habilita botões de edição
        document.getElementById('openRecursosModalBtn').disabled = false;
        document.getElementById('openMaquinariosModalBtn').disabled = false;

        // Renderiza Seção 1: Info Básica
        document.getElementById('displayRazaoSocial').textContent = empresaData.razao_social;
        document.getElementById('displayCnpj').textContent = empresaData.cnpj;

        // Renderiza Seção 2: Recursos Essenciais
        const recursosList = document.getElementById('recursos-essenciais-list');
        recursosList.innerHTML = '';
        if (recursosEssenciaisData.length > 0) {
            recursosEssenciaisData.forEach(r => {
                recursosList.innerHTML += `<li class="list-group-item"><strong>${r.recurso_descricao}:</strong> ${r.quantidade || 'N/A'} ${r.unidade_descricao || ''} <small class="text-muted d-block">${r.detalhes_adicionais || ''}</small></li>`;
            });
        } else {
            recursosList.innerHTML = '<li class="list-group-item text-muted">Nenhum recurso essencial cadastrado.</li>';
        }

        // Renderiza Seção 3: Maquinários
        const maquinariosList = document.getElementById('maquinarios-list');
        maquinariosList.innerHTML = '';
        if (maquinariosData.length > 0) {
            maquinariosData.forEach(m => {
                maquinariosList.innerHTML += `<li class="list-group-item"><strong>${m.recurso_descricao}:</strong> ${m.quantidade || 'N/A'} ${m.unidade_descricao || ''} <small class="text-muted d-block">${m.detalhes_adicionais || ''}</small></li>`;
            });
        } else {
            maquinariosList.innerHTML = '<li class="list-group-item text-muted">Nenhum maquinário cadastrado.</li>';
        }
    }
    
    // Função para criar um campo de select (dropdown)
    function createSelect(options, selectedValue, name, isRequired = false) {
        let selectHTML = `<select class="form-select" name="${name}" ${isRequired ? 'required' : ''}>`;
        selectHTML += '<option value="">Selecione...</option>';
        options.forEach(opt => {
            selectHTML += `<option value="${opt.id}" ${opt.id == selectedValue ? 'selected' : ''}>${opt.descricao}</option>`;
        });
        selectHTML += '</select>';
        return selectHTML;
    }
    
    // Gera uma linha de formulário para um recurso/maquinário
    function createFormRow(item, index, isMaquinario = false) {
        const id = item.id || `new_${index}`;
        return `
            <div class="row g-3 align-items-center mb-3 border p-2 rounded" data-id="${id}">
                <input type="hidden" name="recursos[${id}][id]" value="${item.id || ''}">
                <div class="col-md-3">
                    <label class="form-label">${isMaquinario ? 'Maquinário' : 'Recurso'}</label>
                    ${isMaquinario ? 
                        createSelect(catalogosData.recursos.filter(r => r.id > 2), item.recurso_id, `recursos[${id}][recurso_id]`, true) :
                        `<input type="text" class="form-control" value="${item.recurso_descricao}" readonly><input type="hidden" name="recursos[${id}][recurso_id]" value="${item.recurso_id}">`
                    }
                </div>
                <div class="col-md-2">
                    <label class="form-label">Quantidade</label>
                    <input type="number" step="1" class="form-control" name="recursos[${id}][quantidade]" value="${item.quantidade || ''}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Unidade</label>
                    ${createSelect(catalogosData.unidades, item.unidade_medida_id, `recursos[${id}][unidade_medida_id]`)}
                </div>
                <div class="col-md-4">
                    <label class="form-label">Detalhes</label>
                    <input type="text" class="form-control" name="recursos[${id}][detalhes_adicionais]" value="${item.detalhes_adicionais || ''}">
                </div>
                ${isMaquinario ? `<div class="col-md-1"><button type="button" class="btn btn-danger btn-sm mt-4 remove-row-btn">&times;</button></div>` : ''}
            </div>
        `;
    }
    
    // ABRIR MODAIS
    document.getElementById('openRecursosModalBtn').addEventListener('click', () => {
        const form = document.getElementById('editRecursosForm');
        form.innerHTML = '';
        recursosEssenciaisData.forEach((item, index) => {
            form.innerHTML += createFormRow(item, index, false);
        });
        recursosModal.show();
    });

    document.getElementById('openMaquinariosModalBtn').addEventListener('click', () => {
        const form = document.getElementById('editMaquinariosForm');
        form.innerHTML = '';
        maquinariosData.forEach((item, index) => {
            form.innerHTML += createFormRow(item, index, true);
        });
        maquinariosModal.show();
    });
    
    // ADICIONAR NOVO MAQUINÁRIO
    document.getElementById('addMaquinarioBtn').addEventListener('click', () => {
        const form = document.getElementById('editMaquinariosForm');
        const newIndex = form.children.length;
        const newItem = { id: null, recurso_id: '', quantidade: '', unidade_medida_id: '', detalhes_adicionais: '', recurso_descricao: '' };
        form.insertAdjacentHTML('beforeend', createFormRow(newItem, newIndex, true));
    });

    // REMOVER LINHA DE MAQUINÁRIO
    document.getElementById('editMaquinariosForm').addEventListener('click', (e) => {
        if (e.target && e.target.classList.contains('remove-row-btn')) {
            e.target.closest('.row').remove();
        }
    });

    // SALVAR DADOS (FUNÇÃO GENÉRICA)
    async function handleSave(formId, alertContainerId, modalInstance, saveBtnId) {
        const form = document.getElementById(formId);
        const saveBtn = document.getElementById(saveBtnId);
        
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';

        // Coleta os dados do formulário em um formato de array de objetos
        const dataToSave = [];
        form.querySelectorAll('.row[data-id]').forEach(row => {
            const rowData = {};
            row.querySelectorAll('input, select').forEach(input => {
                // Extrai o nome e a chave do campo (ex: 'recursos[id_123][quantidade]' -> 'quantidade')
                const match = input.name.match(/\[([^\]]+)\]$/);
                if (match) {
                    rowData[match[1]] = input.value;
                }
            });
            dataToSave.push(rowData);
        });
        
        const formData = new FormData();
        formData.append('action', 'salvar_recursos');
        formData.append('empresa_id', empresaData.id);
        formData.append('recursos', JSON.stringify(dataToSave));

        try {
            const response = await fetch('../api/gerenciar_empresas_recursos_api.php', {
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
    document.getElementById('editRecursosForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const allData = [...recursosEssenciaisData, ...maquinariosData]; // Junta os dados para não perder os maquinários
        const dataToSave = [];
        e.target.querySelectorAll('.row[data-id]').forEach(row => {
             const rowData = {};
             row.querySelectorAll('input, select').forEach(input => {
                const match = input.name.match(/\[([^\]]+)\]$/);
                if (match) rowData[match[1]] = input.value;
             });
             dataToSave.push(rowData);
        });

        // Combina os dados editados com os não editados
        const finalData = [...dataToSave, ...maquinariosData];
        handleSaveCombined(finalData, 'modal-recursos-alert-container', recursosModal, 'saveRecursosBtn');
    });

    document.getElementById('editMaquinariosForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const dataToSave = [];
        e.target.querySelectorAll('.row[data-id]').forEach(row => {
             const rowData = {};
             row.querySelectorAll('input, select').forEach(input => {
                const match = input.name.match(/\[([^\]]+)\]$/);
                if (match) rowData[match[1]] = input.value;
             });
             dataToSave.push(rowData);
        });
        // Combina os dados editados com os não editados
        const finalData = [...recursosEssenciaisData, ...dataToSave];
        handleSaveCombined(finalData, 'modal-maquinarios-alert-container', maquinariosModal, 'saveMaquinariosBtn');
    });

    // Função de salvamento que envia o conjunto completo de dados
    async function handleSaveCombined(fullDataSet, alertContainerId, modalInstance, saveBtnId) {
        const saveBtn = document.getElementById(saveBtnId);
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';
        
        const formData = new FormData();
        formData.append('action', 'salvar_recursos');
        formData.append('empresa_id', empresaData.id);
        formData.append('recursos', JSON.stringify(fullDataSet));

         try {
            const response = await fetch('../api/gerenciar_empresas_recursos_api.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.success) {
                exibirAlerta(alertContainerId, result.message, 'success');
                setTimeout(() => {
                    modalInstance.hide();
                    carregarPainel();
                }, 1500);
            } else {
                exibirAlerta(alertContainerId, result.message, 'danger');
            }
        } catch (error) {
            exibirAlerta(alertContainerId, 'Erro de rede.', 'danger');
        } finally {
             saveBtn.disabled = false;
             saveBtn.innerHTML = 'Salvar Alterações';
        }
    }

    // Inicia o carregamento dos dados
    carregarPainel();
});
</script>


<?php
include_once '../helpers/footer.php';
?>