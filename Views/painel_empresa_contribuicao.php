<?php
// Views/painel_empresa_contribuicao.php
session_start();
include_once '../helpers/header.php';
include_once '../helpers/header_empresa.php';

// Verifica se o usuário tem a permissão para contribuir com a empresa
if (!in_array('contribuir_empresa', $_SESSION['permissoes']) || !isset($_SESSION['empresa_id'])) {
    header('Location: ../Views/acesso_restrito.php');
    exit();
}
?>

<main class="container-fluid my-4 d-flex flex-column align-items-center">
    <div class="col-md-10 p-4">
        <h3 class="mb-4">Gerenciar Contribuições da Empresa</h3>
        <div id="alerts-container" class="mt-3"></div>

        <div class="card mb-4">
            <div class="card-header">
                Sua Sugestão de Contribuição
            </div>
            <div class="card-body">
                <form id="contribuicaoForm">
                    <input type="hidden" id="contribuicao-id">
                    <div class="mb-3">
                        <label for="sugestao_contribuicao" class="form-label">Descreva como sua empresa pode contribuir com o CBMMT:</label>
                        <textarea class="form-control" id="sugestao_contribuicao" name="sugestao_contribuicao" rows="5" required></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary" id="btn-salvar">Salvar Contribuição</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Contribuições Existentes
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="tabela-contribuicoes">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Sugestão</th>
                                <th scope="col">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- As contribuições serão carregadas aqui via JavaScript -->
                            <tr><td colspan="3" class="text-center">Nenhuma contribuição encontrada.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once '../helpers/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const alertsContainer = document.getElementById('alerts-container');
        const contribuicaoForm = document.getElementById('contribuicaoForm');
        const contribuicaoIdInput = document.getElementById('contribuicao-id');
        const sugestaoContribuicaoTextarea = document.getElementById('sugestao_contribuicao');
        const tabelaContribuicoesBody = document.querySelector('#tabela-contribuicoes tbody');

        function exibirAlerta(mensagem, tipo) {
            alertsContainer.innerHTML = `
                <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
                    ${mensagem}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
        }

        async function carregarContribuicoes() {
            try {
                const response = await fetch('../api/gerenciar_empresas_contribuicao_api.php?action=get_contribuicoes');
                const data = await response.json();

                tabelaContribuicoesBody.innerHTML = '';
                if (data.success && data.contribuicoes.length > 0) {
                    data.contribuicoes.forEach(contribuicao => {
                        const row = `
                            <tr>
                                <td>${contribuicao.id}</td>
                                <td>${contribuicao.sugestao_contribuicao}</td>
                                <td>
                                    <button class="btn btn-warning btn-sm editar-btn" data-id="${contribuicao.id}" data-sugestao="${contribuicao.sugestao_contribuicao}">
                                        <i class="bi bi-pencil-square"></i> Editar
                                    </button>
                                    <button class="btn btn-danger btn-sm excluir-btn" data-id="${contribuicao.id}">
                                        <i class="bi bi-trash"></i> Excluir
                                    </button>
                                </td>
                            </tr>
                        `;
                        tabelaContribuicoesBody.innerHTML += row;
                    });
                } else {
                    tabelaContribuicoesBody.innerHTML = '<tr><td colspan="3" class="text-center">Nenhuma contribuição encontrada.</td></tr>';
                }

                // Adiciona os event listeners após carregar a tabela
                document.querySelectorAll('.editar-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        const sugestao = this.getAttribute('data-sugestao');
                        contribuicaoIdInput.value = id;
                        sugestaoContribuicaoTextarea.value = sugestao;
                        document.getElementById('btn-salvar').textContent = 'Atualizar Contribuição';
                    });
                });

                document.querySelectorAll('.excluir-btn').forEach(btn => {
                    btn.addEventListener('click', async function() {
                        const id = this.getAttribute('data-id');
                        if (confirm('Tem certeza que deseja excluir esta contribuição?')) {
                            const formData = new FormData();
                            formData.append('action', 'delete_contribuicao');
                            formData.append('id', id);

                            try {
                                const response = await fetch('../api/gerenciar_empresas_contribuicao_api.php', {
                                    method: 'POST',
                                    body: formData
                                });
                                const data = await response.json();
                                if (data.success) {
                                    exibirAlerta(data.message, 'success');
                                    carregarContribuicoes();
                                } else {
                                    exibirAlerta(data.message, 'danger');
                                }
                            } catch (error) {
                                exibirAlerta('Erro ao excluir a contribuição. Tente novamente.', 'danger');
                                console.error('Erro:', error);
                            }
                        }
                    });
                });

            } catch (error) {
                exibirAlerta('Erro ao carregar as contribuições. Tente novamente mais tarde.', 'danger');
                console.error('Erro:', error);
            }
        }

        contribuicaoForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'salvar_contribuicao');
            
            try {
                const response = await fetch('../api/gerenciar_empresas_contribuicao_api.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    exibirAlerta(data.message, 'success');
                    contribuicaoForm.reset();
                    contribuicaoIdInput.value = '';
                    document.getElementById('btn-salvar').textContent = 'Salvar Contribuição';
                    carregarContribuicoes();
                } else {
                    exibirAlerta(data.message, 'danger');
                }
            } catch (error) {
                exibirAlerta('Erro ao salvar as alterações. Tente novamente.', 'danger');
                console.error('Erro:', error);
            }
        });
        
        // Carrega as contribuições ao iniciar a página
        carregarContribuicoes();
    });
</script>
