<?php
// Views/gerenciar_usuarios.php
include_once '../helpers/header_admin.php';

// Assumindo que a permissão para gerenciar usuários é 'gerenciar_usuarios'
if (!in_array('gerenciar_usuarios', $_SESSION['permissoes'])) {
    header('Location: ../Views/acesso_restrito.php');
    exit();
}
?>

<main class="container-fluid my-4 d-flex flex-column align-items-center">
    <div class="col-md-10 p-4">
        <h3 class="mb-4">Gerenciar Usuários</h3>
        <div id="alerts-container" class="mt-3"></div>
        <div class="card">
            <div class="card-header">
                Lista de Usuários
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="filtro-usuarios" placeholder="Filtrar por nome, CPF ou e-mail...">
                </div>
                <div class="table-responsive">
                    <table class="table table-striped" id="tabela-usuarios">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Nome</th>
                                <th scope="col">E-mail</th>
                                <th scope="col">CPF</th>
                                <th scope="col">Perfil</th>
                                <th scope="col">Status</th>
                                <th scope="col">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                <div id="paginacao" class="d-flex justify-content-center mt-3"></div>
            </div>
        </div>
    </div>
    </div>
</main>

<div class="modal fade" id="editarUsuarioModal" tabindex="-1" aria-labelledby="editarUsuarioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarUsuarioModalLabel">Editar Perfil do Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-editar-usuario">
                    <input type="hidden" id="usuario-id-modal">
                    <div class="mb-3">
                        <label for="nome-usuario-modal" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="nome-usuario-modal" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="perfil-usuario-modal" class="form-label">Perfil</label>
                        <select class="form-select" id="perfil-usuario-modal">
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btn-salvar-edicao">Salvar mudanças</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        carregarUsuarios();
    });

    // ...
    async function carregarUsuarios(pagina = 1, filtro = '') {
        const alertsContainer = document.getElementById('alerts-container');
        const tbody = document.querySelector('#tabela-usuarios tbody');
        const paginacaoDiv = document.getElementById('paginacao');
        const limit = 20;
        const offset = (pagina - 1) * limit;

        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Carregando usuários...</td></tr>';
        alertsContainer.innerHTML = '';
        paginacaoDiv.innerHTML = '';

        try {
            const response = await fetch(`../api/gerenciar_usuarios_api.php?action=listar&limit=${limit}&offset=${offset}&filtro=${filtro}`);
            const data = await response.json();

            if (data.success) {
                tbody.innerHTML = '';
                if (data.usuarios.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center">Nenhum usuário encontrado.</td></tr>';
                } else {
                    data.usuarios.forEach(usuario => {
                        // ... o seu código para preencher a tabela permanece aqui
                        const tr = document.createElement('tr');
                        let statusBadge = usuario.ativo == 1 ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-danger">Inativo</span>';
                        tr.innerHTML = `
                        <td>${usuario.id}</td>
                        <td>${usuario.nome}</td>
                        <td>${usuario.email}</td>
                        <td>${usuario.cpf}</td>
                        <td>${usuario.perfil_nome || 'N/A'}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <button class="btn btn-sm btn-primary editar-btn" data-id="${usuario.id}" data-nome="${usuario.nome}" data-perfil-id="${usuario.perfil_id}">
                                Editar
                            </button>
                            <button class="btn btn-sm ${usuario.ativo == 1 ? 'btn-danger' : 'btn-success'} toggle-status-btn" data-id="${usuario.id}" data-ativo="${usuario.ativo}">
                                ${usuario.ativo == 1 ? 'Desativar' : 'Ativar'}
                            </button>
                        </td>
                    `;
                        tbody.appendChild(tr);
                    });
                }

                // Lógica para criar os botões de paginação
                const totalPaginas = Math.ceil(data.total_usuarios / limit);
                if (totalPaginas > 1) {
                    const nav = document.createElement('nav');
                    const ul = document.createElement('ul');
                    ul.classList.add('pagination');

                    // Botão "Anterior"
                    const liPrev = document.createElement('li');
                    liPrev.classList.add('page-item', pagina === 1 ? 'disabled' : '');
                    liPrev.innerHTML = `<a class="page-link" href="#" data-page="${pagina - 1}">Anterior</a>`;
                    ul.appendChild(liPrev);

                    // Números das páginas
                    for (let i = 1; i <= totalPaginas; i++) {
                        const li = document.createElement('li');
                        li.classList.add('page-item', i === pagina ? 'active' : '');
                        li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
                        ul.appendChild(li);
                    }

                    // Botão "Próximo"
                    const liNext = document.createElement('li');
                    liNext.classList.add('page-item', pagina === totalPaginas ? 'disabled' : '');
                    liNext.innerHTML = `<a class="page-link" href="#" data-page="${pagina + 1}">Próximo</a>`;
                    ul.appendChild(liNext);

                    nav.appendChild(ul);
                    paginacaoDiv.appendChild(nav);
                }
            } else {
                exibirAlerta(data.message, 'danger');
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">Não foi possível carregar os usuários.</td></tr>';
            }
        } catch (error) {
            exibirAlerta('Erro ao carregar a lista de usuários.', 'danger');
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">Erro de conexão.</td></tr>';
        }
    }

    document.querySelector('#tabela-usuarios tbody').addEventListener('click', async (e) => {
        const alertsContainer = document.getElementById('alerts-container');
        alertsContainer.innerHTML = '';

        if (e.target.classList.contains('editar-btn')) {
            const userId = e.target.getAttribute('data-id');
            const userName = e.target.getAttribute('data-nome');
            const userPerfilId = e.target.getAttribute('data-perfil-id');

            // Preenche o modal
            document.getElementById('usuario-id-modal').value = userId;
            document.getElementById('nome-usuario-modal').value = userName;

            // Carrega e preenche os perfis no select
            await carregarPerfis(userPerfilId);

            const modal = new bootstrap.Modal(document.getElementById('editarUsuarioModal'));
            modal.show();

        } else if (e.target.classList.contains('toggle-status-btn')) {
            const userId = e.target.getAttribute('data-id');
            const currentStatus = e.target.getAttribute('data-ativo');
            const newStatus = currentStatus == 1 ? 0 : 1;

            if (confirm(`Tem certeza que deseja ${newStatus == 1 ? 'ativar' : 'desativar'} este usuário?`)) {
                const response = await fetch(`../api/gerenciar_usuarios_api.php?action=toggle_status&id=${userId}&status=${newStatus}`);
                const data = await response.json();
                if (data.success) {
                    exibirAlerta(data.message, 'success');
                    carregarUsuarios(); // Recarrega a tabela
                } else {
                    exibirAlerta(data.message, 'danger');
                }
            }
        }
    });

    async function carregarPerfis(perfilAtualId) {
        const selectPerfis = document.getElementById('perfil-usuario-modal');
        selectPerfis.innerHTML = '<option value="">Carregando...</option>';

        try {
            const response = await fetch('../api/gerenciar_usuarios_api.php?action=listar_perfis');
            const data = await response.json();

            if (data.success) {
                selectPerfis.innerHTML = '';
                data.perfis.forEach(perfil => {
                    const option = document.createElement('option');
                    option.value = perfil.id;
                    option.textContent = perfil.nome;
                    if (perfil.id == perfilAtualId) {
                        option.selected = true;
                    }
                    selectPerfis.appendChild(option);
                });
            } else {
                selectPerfis.innerHTML = '<option value="">Erro ao carregar</option>';
                exibirAlerta(data.message, 'danger');
            }
        } catch (error) {
            selectPerfis.innerHTML = '<option value="">Erro de conexão</option>';
            exibirAlerta('Erro ao carregar a lista de perfis.', 'danger');
        }
    }

    // Adiciona o event listener para o campo de filtro
    document.getElementById('filtro-usuarios').addEventListener('input', (e) => {
        const filtro = e.target.value;
        carregarUsuarios(1, filtro); // Sempre volta para a primeira página ao filtrar
    });

    // Adiciona o event listener para os botões de paginação
    document.getElementById('paginacao').addEventListener('click', (e) => {
        if (e.target.classList.contains('page-link')) {
            e.preventDefault();
            const pagina = parseInt(e.target.getAttribute('data-page'));
            const filtro = document.getElementById('filtro-usuarios').value;
            carregarUsuarios(pagina, filtro);
        }
    });

    document.getElementById('btn-salvar-edicao').addEventListener('click', async () => {
        const userId = document.getElementById('usuario-id-modal').value;
        const novoPerfilId = document.getElementById('perfil-usuario-modal').value;

        const formData = new FormData();
        formData.append('action', 'editar_perfil');
        formData.append('id', userId);
        formData.append('perfil_id', novoPerfilId);

        const alertsContainer = document.getElementById('alerts-container');
        alertsContainer.innerHTML = '';

        try {
            const response = await fetch('../api/gerenciar_usuarios_api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            const modal = bootstrap.Modal.getInstance(document.getElementById('editarUsuarioModal'));
            modal.hide();

            if (data.success) {
                exibirAlerta(data.message, 'success');
                carregarUsuarios();
            } else {
                exibirAlerta(data.message, 'danger');
            }
        } catch (error) {
            exibirAlerta('Erro ao salvar as alterações.', 'danger');
        }
    });

    function exibirAlerta(mensagem, tipo) {
        const alertsContainer = document.getElementById('alerts-container');
        alertsContainer.innerHTML = `
        <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
            ${mensagem}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    }
</script>
<?php
include_once '../helpers/footer.php';
?>