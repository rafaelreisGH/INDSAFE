<?php
// Views/gerenciar_permissoes.php
include_once '../helpers/header_admin.php';

// Assumindo que a permissão para gerenciar perfis e permissões é 'gerenciar_perfis'
if (!in_array('gerenciar_perfis', $_SESSION['permissoes'])) {
    header('Location: ../Views/acesso_restrito.php');
    exit();
}
?>

<main class="container-fluid my-4 d-flex flex-column align-items-center">
    <div class="col-md-10 p-4">
        <h3 class="mb-4">Gerenciar Perfis e Permissões</h3>
        <div id="alerts-container" class="mt-3"></div>
        <div class="card">
            <div class="card-header">
                Lista de Perfis
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="tabela-perfis">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nome do Perfil</th>
                                <th scope="col">Permissões</th>
                                <th scope="col">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Linhas da tabela serão preenchidas via JavaScript -->
                            <tr>
                                <td colspan="4" class="text-center">Carregando perfis...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal para Editar Permissões do Perfil -->
<div class="modal fade" id="editarPermissoesModal" tabindex="-1" aria-labelledby="editarPermissoesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarPermissoesModalLabel">Editar Permissões para o Perfil: <span id="perfil-nome-modal"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-editar-permissoes">
                    <input type="hidden" id="perfil-id-modal">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div id="modal-alerts" class="mb-2"></div> <!-- <= AQUI -->
                    <p>Selecione as permissões que este perfil deve ter:</p>
                    <div id="permissoes-lista" class="row"></div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btn-salvar-permissoes">Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    carregarPerfis();

    // Clique no botão "Salvar Alterações" dentro do modal
    const btnSalvar = document.getElementById('btn-salvar-permissoes');
    btnSalvar.addEventListener('click', onSalvarPermissoes);
});

// ---- Helpers de alerta ----
function exibirAlerta(mensagem, tipo = 'info') {
    const alertsContainer = document.getElementById('alerts-container');
    if (!alertsContainer) return;
    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
        <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
            ${mensagem}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
    alertsContainer.append(wrapper);
    setTimeout(() => wrapper.remove(), 5000);
}

function exibirAlertaModal(mensagem, tipo = 'info') {
    const modalAlerts = document.getElementById('modal-alerts');
    if (!modalAlerts) return;
    modalAlerts.innerHTML = `
        <div class="alert alert-${tipo} alert-dismissible fade show mb-2" role="alert">
            ${mensagem}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
}

// Limpa alertas do modal
function limparAlertaModal() {
    const modalAlerts = document.getElementById('modal-alerts');
    if (modalAlerts) modalAlerts.innerHTML = '';
}

// ---- Carrega perfis na tabela ----
async function carregarPerfis() {
    const tbody = document.querySelector('#tabela-perfis tbody');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center">Carregando perfis...</td></tr>';

    try {
        const resp = await fetch('../api/gerenciar_permissoes_api.php?action=listar_perfis');
        const data = await resp.json();

        if (data.success) {
            tbody.innerHTML = '';
            if (data.perfis.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">Nenhum perfil encontrado.</td></tr>';
                return;
            }
            data.perfis.forEach(perfil => {
                const permissoesTexto = perfil.permissoes ? perfil.permissoes : 'Nenhuma';
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${perfil.id}</td>
                    <td>${perfil.nome}</td>
                    <td>${permissoesTexto}</td>
                    <td>
                        <button class="btn btn-sm btn-primary editar-permissoes-btn"
                                data-id="${perfil.id}" data-nome="${perfil.nome}">
                            Editar Permissões
                        </button>
                    </td>`;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">Não foi possível carregar os perfis.</td></tr>';
            exibirAlerta(data.message || 'Não foi possível carregar os perfis.', 'danger');
        }
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Erro de conexão.</td></tr>';
        exibirAlerta('Erro de conexão ao carregar os perfis. Verifique sua rede.', 'danger');
    }
}

// ---- Abre modal ao clicar em "Editar Permissões" ----
document.querySelector('#tabela-perfis tbody').addEventListener('click', async (e) => {
    if (!e.target.classList.contains('editar-permissoes-btn')) return;

    const perfilId = e.target.getAttribute('data-id');
    const perfilNome = e.target.getAttribute('data-nome');

    document.getElementById('perfil-id-modal').value = perfilId;
    document.getElementById('perfil-nome-modal').textContent = perfilNome;

    limparAlertaModal();
    await carregarPermissoesDoPerfil(perfilId);

    const modal = new bootstrap.Modal(document.getElementById('editarPermissoesModal'));
    modal.show();
});

// ---- Carrega permissões no modal ----
async function carregarPermissoesDoPerfil(perfilId) {
    const permissoesDiv = document.getElementById('permissoes-lista');
    permissoesDiv.innerHTML = '<div class="col-12 text-center">Carregando permissões...</div>';

    try {
        const resp = await fetch(`../api/gerenciar_permissoes_api.php?action=listar_permissoes_perfil&id=${perfilId}`);
        const data = await resp.json();

        if (data.success) {
            permissoesDiv.innerHTML = '';
            if (data.todas_permissoes.length === 0) {
                permissoesDiv.innerHTML = '<div class="col-12 text-center">Nenhuma permissão encontrada.</div>';
                return;
            }
            const selecionadas = new Set((data.permissoes_do_perfil || []).map(p => String(p.id)));
            data.todas_permissoes.forEach(permissao => {
                const isChecked = selecionadas.has(String(permissao.id));
                const col = document.createElement('div');
                col.className = 'col-md-4 mb-2';
                col.innerHTML = `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="${permissao.id}"
                               id="permissao-${permissao.id}" ${isChecked ? 'checked' : ''}>
                        <label class="form-check-label" for="permissao-${permissao.id}">
                            ${permissao.nome}
                        </label>
                    </div>`;
                permissoesDiv.appendChild(col);
            });
        } else {
            permissoesDiv.innerHTML = '<div class="col-12 text-center">Erro ao carregar permissões.</div>';
            exibirAlertaModal(data.message || 'Erro ao carregar permissões.', 'danger');
        }
    } catch (err) {
        permissoesDiv.innerHTML = '<div class="col-12 text-center">Erro de conexão.</div>';
        exibirAlertaModal('Erro de conexão ao carregar permissões.', 'danger');
    }
}

// ---- Salvar permissões (dentro do modal) ----
async function onSalvarPermissoes() {
    const btn = document.getElementById('btn-salvar-permissoes');
    const perfilId = document.getElementById('perfil-id-modal').value;
    const csrfToken = document.querySelector('#form-editar-permissoes input[name="csrf_token"]').value;

    // coleta checkboxes
    const checkboxes = document.querySelectorAll('#permissoes-lista input[type="checkbox"]:checked');
    const permissoesSelecionadas = Array.from(checkboxes).map(cb => cb.value);

    // UI: desabilita botão + spinner
    limparAlertaModal();
    const oldHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Salvando...';

    try {
        const formData = new FormData();
        formData.append('action', 'salvar_permissoes');
        formData.append('id_perfil', perfilId);
        formData.append('permissoes', JSON.stringify(permissoesSelecionadas));
        formData.append('csrf_token', csrfToken);

        const resp = await fetch('../api/gerenciar_permissoes_api.php', {
            method: 'POST',
            body: formData
        });

        const data = await resp.json().catch(() => ({ success: false, message: 'Resposta inválida do servidor.' }));

        if (data.success) {
            exibirAlertaModal(data.message, 'success');

            // fecha modal após um breve delay, atualiza tabela e alerta global opcional
            setTimeout(() => {
                const modalEl = document.getElementById('editarPermissoesModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                exibirAlerta(data.message, 'success'); // fora do modal (opcional)
                carregarPerfis();
            }, 1000);
        } else {
            exibirAlertaModal(data.message || 'Não foi possível salvar as permissões.', 'danger');
        }
    } catch (err) {
        exibirAlertaModal('Erro de conexão ao salvar as alterações.', 'danger');
    } finally {
        // restaura botão
        btn.disabled = false;
        btn.innerHTML = oldHtml;
    }
}
</script>


<?php
include_once '../helpers/footer.php';
?>