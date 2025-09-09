<?php
// Views/gerenciar_usuarios.php
include_once '../helpers/header_admin.php';

// Assumindo que a permissão para gerenciar usuários é 'gerenciar_usuarios'
if (!in_array('gerenciar_vinculos', $_SESSION['permissoes'])) {
    header('Location: ../Views/acesso_restrito.php');
    exit();
}
?>

<main class="container-fluid my-4 d-flex flex-column align-items-center">
    <div class="col-md-10 p-4">
        <h3 class="mb-4">Gerenciar Vínculos de Usuários a Empresas</h3>
        <div id="alerts-container" class="mt-3"></div>
        <div class="card">
            <div class="card-header">
                Vínculos Pendentes
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="filtro-vinculos" placeholder="Filtrar por nome de usuário, CPF, e-mail, razão social ou CNPJ...">
                </div>
                <div class="table-responsive">
                    <table class="table table-striped" id="tabela-vinculos">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Usuário</th>
                                <th scope="col">CPF</th>
                                <th scope="col">Email</th>
                                <th scope="col">Empresa</th>
                                <th scope="col">CNPJ</th>
                                <th scope="col">Status</th>
                                <th scope="col">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Conteúdo da tabela será carregado via JavaScript -->
                            <tr><td colspan="8" class="text-center">Carregando vínculos...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="paginacao" class="d-flex justify-content-center mt-3"></div>
            </div>
        </div>
    </div>
</main>

<!-- Modal de Rejeição -->
<div class="modal fade" id="rejeitarModal" tabindex="-1" aria-labelledby="rejeitarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejeitarModalLabel">Motivo da Rejeição</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formRejeitar">
                    <div class="mb-3">
                        <label for="motivoRejeicao" class="form-label">Descreva o motivo da rejeição:</label>
                        <textarea class="form-control" id="motivoRejeicao" rows="3" required></textarea>
                    </div>
                    <input type="hidden" id="vinculoIdRejeitar">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmarRejeicaoBtn">Rejeitar Vínculo</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        carregarVinculos();
    });

    async function carregarVinculos(pagina = 1, filtro = '') {
        const alertsContainer = document.getElementById('alerts-container');
        const tbody = document.querySelector('#tabela-vinculos tbody');
        const paginacaoDiv = document.getElementById('paginacao');
        const limit = 20;
        const offset = (pagina - 1) * limit;

        tbody.innerHTML = '<tr><td colspan="8" class="text-center">Carregando vínculos...</td></tr>';
        alertsContainer.innerHTML = '';
        paginacaoDiv.innerHTML = '';

        try {
            const response = await fetch(`../api/gerenciar_vinculos_api.php?action=listar&limit=${limit}&offset=${offset}&filtro=${encodeURIComponent(filtro)}`);
            const data = await response.json();

            if (data.success) {
                tbody.innerHTML = '';
                if (data.vinculos.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center">Nenhum vínculo pendente encontrado.</td></tr>';
                } else {
                    data.vinculos.forEach(vinculo => {
                        let statusBadge = '';
                        switch (parseInt(vinculo.status_vinculo)) {
                            case 0:
                                statusBadge = '<span class="badge bg-warning text-dark">Pendente</span>';
                                break;
                            case 1:
                                statusBadge = '<span class="badge bg-success">Aprovado</span>';
                                break;
                            case 2:
                                statusBadge = '<span class="badge bg-danger">Rejeitado</span>';
                                break;
                        }

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${vinculo.id}</td>
                            <td>${vinculo.nome_usuario}</td>
                            <td>${vinculo.cpf_usuario}</td>
                            <td>${vinculo.email_usuario}</td>
                            <td>${vinculo.razao_social_empresa}</td>
                            <td>${vinculo.cnpj_empresa}</td>
                            <td>${statusBadge}</td>
                            <td>
                                <button class="btn btn-sm btn-success aprovar-btn" data-id="${vinculo.id}">Aprovar</button>
                                <button class="btn btn-sm btn-danger rejeitar-btn" data-id="${vinculo.id}" data-bs-toggle="modal" data-bs-target="#rejeitarModal">Rejeitar</button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }

                // Lógica de paginação
                const totalPaginas = Math.ceil(data.total_vinculos / limit);
                if (totalPaginas > 1) {
                    const nav = document.createElement('nav');
                    const ul = document.createElement('ul');
                    ul.classList.add('pagination');

                    const liPrev = document.createElement('li');
                    liPrev.classList.add('page-item', pagina === 1 ? 'disabled' : '');
                    liPrev.innerHTML = `<a class="page-link" href="#" data-page="${pagina - 1}">Anterior</a>`;
                    ul.appendChild(liPrev);

                    for (let i = 1; i <= totalPaginas; i++) {
                        const li = document.createElement('li');
                        li.classList.add('page-item', i === pagina ? 'active' : '');
                        li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
                        ul.appendChild(li);
                    }

                    const liNext = document.createElement('li');
                    liNext.classList.add('page-item', pagina === totalPaginas ? 'disabled' : '');
                    liNext.innerHTML = `<a class="page-link" href="#" data-page="${pagina + 1}">Próximo</a>`;
                    ul.appendChild(liNext);

                    nav.appendChild(ul);
                    paginacaoDiv.appendChild(nav);
                }

            } else {
                exibirAlerta(data.message, 'danger');
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">Não foi possível carregar os vínculos.</td></tr>';
            }
        } catch (error) {
            exibirAlerta('Erro ao carregar a lista de vínculos.', 'danger');
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">Erro de conexão.</td></tr>';
        }
    }

    document.querySelector('#tabela-vinculos tbody').addEventListener('click', async (e) => {
        const alertsContainer = document.getElementById('alerts-container');
        alertsContainer.innerHTML = '';
        const vinculoId = e.target.getAttribute('data-id');

        if (!vinculoId) {
            return;
        }

        let action = '';
        if (e.target.classList.contains('aprovar-btn')) {
            action = 'aprovar_vinculo';
        } else if (e.target.classList.contains('rejeitar-btn')) {
            // Se for o botão de rejeitar, a ação será tratada pelo modal
            document.getElementById('vinculoIdRejeitar').value = vinculoId;
            return;
        }

        if (action) {
            if (confirm(`Tem certeza que deseja ${action === 'aprovar_vinculo' ? 'aprovar' : 'rejeitar'} este vínculo?`)) {
                try {
                    const response = await fetch(`../api/gerenciar_vinculos_api.php?action=${action}&id=${vinculoId}`);
                    const data = await response.json();
                    if (data.success) {
                        exibirAlerta(data.message, 'success');
                        carregarVinculos();
                    } else {
                        exibirAlerta(data.message, 'danger');
                    }
                } catch (error) {
                    exibirAlerta('Erro ao realizar a ação. Tente novamente.', 'danger');
                }
            }
        }
    });

    document.getElementById('confirmarRejeicaoBtn').addEventListener('click', async () => {
        const vinculoId = document.getElementById('vinculoIdRejeitar').value;
        const motivo = document.getElementById('motivoRejeicao').value;
        const alertsContainer = document.getElementById('alerts-container');

        if (!motivo) {
            exibirAlerta('O motivo da rejeição é obrigatório.', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'rejeitar_vinculo');
        formData.append('id', vinculoId);
        formData.append('motivo', motivo);

        try {
            const response = await fetch('../api/gerenciar_vinculos_api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            const modal = bootstrap.Modal.getInstance(document.getElementById('rejeitarModal'));
            modal.hide();

            if (data.success) {
                exibirAlerta(data.message, 'success');
                carregarVinculos();
            } else {
                exibirAlerta(data.message, 'danger');
            }
        } catch (error) {
            exibirAlerta('Erro ao rejeitar o vínculo. Tente novamente.', 'danger');
        }
    });

    document.getElementById('filtro-vinculos').addEventListener('input', (e) => {
        const filtro = e.target.value;
        carregarVinculos(1, filtro); // Sempre volta para a primeira página ao filtrar
    });

    document.getElementById('paginacao').addEventListener('click', (e) => {
        if (e.target.classList.contains('page-link')) {
            e.preventDefault();
            const pagina = parseInt(e.target.getAttribute('data-page'));
            const filtro = document.getElementById('filtro-vinculos').value;
            carregarVinculos(pagina, filtro);
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

<?php include_once '../helpers/footer.php'; ?>
