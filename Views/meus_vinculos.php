<?php
// Views/meus_vinculos.php
session_start();

// Redireciona se o usuário não estiver logado
if (!isset($_SESSION['logado'])) {
    header('Location: ../Views/login.php');
    exit();
}

include_once '../helpers/header_basico.php';
?>

<main class="container-fluid my-4 d-flex flex-column align-items-center">
    <div class="col-md-10 p-4">
        <h3 class="mb-4">Meus Vínculos de Empresa</h3>
        <div id="alerts-container" class="mt-3"></div>
        <div class="card">
            <div class="card-header">
                Status dos Vínculos
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="tabela-meus-vinculos">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Empresa</th>
                                <th scope="col">CNPJ</th>
                                <th scope="col">Status</th>
                                <th scope="col">Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Conteúdo da tabela será carregado via JavaScript -->
                            <tr>
                                <td colspan="5" class="text-center">Carregando seus vínculos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        carregarMeusVinculos();
    });

    async function carregarMeusVinculos() {
        const alertsContainer = document.getElementById('alerts-container');
        const tbody = document.querySelector('#tabela-meus-vinculos tbody');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Carregando seus vínculos...</td></tr>';
        alertsContainer.innerHTML = '';

        try {
            // Usa o ID do usuário logado para buscar os vínculos
            const userId = <?php echo json_encode($_SESSION['id_usuario']); ?>;
            const response = await fetch(`../api/gerenciar_vinculos_api.php?action=listar_por_usuario&userId=${userId}`);
            const data = await response.json();

            if (data.success) {
                tbody.innerHTML = '';
                if (data.vinculos.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center">Nenhum vínculo encontrado.</td></tr>';
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
                            <td>${vinculo.razao_social_empresa}</td>
                            <td>${vinculo.cnpj_empresa}</td>
                            <td>${statusBadge}</td>
                            <td>${vinculo.motivo_rejeicao || '-'}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            } else {
                exibirAlerta(data.message, 'danger');
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">Não foi possível carregar seus vínculos.</td></tr>';
            }
        } catch (error) {
            exibirAlerta('Erro ao carregar a lista de vínculos. Tente novamente.', 'danger');
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">Erro de conexão.</td></tr>';
        }
    }

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