<?php
///Views/busca_por_nome.php
include_once '../helpers/header.php';
include_once '../helpers/header_empresa.php';

// A página só pode ser acessada por quem tem a permissão 'editar_empresa'
if (!in_array('editar_empresa', $_SESSION['permissoes'])) {
    header('Location: ../Views/acesso_restrito.php');
    exit();
}
?>

<main class="container-fluid my-4 d-flex flex-column align-items-center">
    <div class="col-md-8 p-4">
        <h3 class="mb-4">Informações da Empresa</h3>
        <div id="alerts-container" class="mt-3"></div>
        <div class="card">
            <div class="card-header">
                Editar Dados da Empresa
            </div>
            <div class="card-body">
                <form id="form-editar-empresa">
                    <div class="mb-3">
                        <label for="nome-fantasia" class="form-label">Nome Fantasia</label>
                        <input type="text" class="form-control" id="nome-fantasia" name="nome_fantasia" required>
                    </div>
                    <div class="mb-3">
                        <label for="razao-social" class="form-label">Razão Social</label>
                        <input type="text" class="form-control" id="razao-social" name="razao_social" required>
                    </div>
                    <div class="mb-3">
                        <label for="cnpj" class="form-label">CNPJ</label>
                        <input type="text" class="form-control" id="cnpj" name="cnpj" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="endereco" class="form-label">Endereço</label>
                        <input type="text" class="form-control" id="endereco" name="endereco" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="tel" class="form-control" id="telefone" name="telefone" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        carregarEmpresa();
    });

    async function carregarEmpresa() {
        const alertsContainer = document.getElementById('alerts-container');
        alertsContainer.innerHTML = '';

        try {
            const response = await fetch('../api/gerenciar_empresas_api.php?action=listar_empresa');
            const data = await response.json();

            if (data.success && data.empresa) {
                document.getElementById('nome-fantasia').value = data.empresa.nome_fantasia || '';
                document.getElementById('razao-social').value = data.empresa.razao_social || '';
                document.getElementById('cnpj').value = data.empresa.cnpj || '';
                document.getElementById('endereco').value = data.empresa.endereco || '';
                document.getElementById('telefone').value = data.empresa.telefone || '';
                document.getElementById('email').value = data.empresa.email || '';
            } else {
                exibirAlerta(data.message || 'Não foi possível carregar os dados da empresa.', 'danger');
            }
        } catch (error) {
            console.error('Erro:', error);
            exibirAlerta('Erro ao carregar os dados da empresa.', 'danger');
        }
    }

    document.getElementById('form-editar-empresa').addEventListener('submit', async (e) => {
        e.preventDefault();

        const alertsContainer = document.getElementById('alerts-container');
        alertsContainer.innerHTML = '';

        const formData = new FormData(e.target);
        formData.append('action', 'salvar_empresa');

        // Adiciona o CNPJ para referência na API, mesmo estando desabilitado
        formData.append('cnpj', document.getElementById('cnpj').value);

        try {
            const response = await fetch('../api/gerenciar_empresas_api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                exibirAlerta(data.message, 'success');
                carregarEmpresa(); // Recarrega os dados para confirmar a alteração
            } else {
                exibirAlerta(data.message, 'danger');
            }
        } catch (error) {
            console.error('Erro:', error);
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