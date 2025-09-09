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
        <div id="alerts-container" class="mt-3"></div>

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
                <!-- Formulário de informações da empresa -->
                <div class="card mb-4 mt-3 shadow-sm">
                    <div class="card-header bg-danger text-white">
                        Informações da Empresa
                    </div>
                    <div class="card-body">
                        <!-- Alerta para quando não há empresa vinculada -->
                        <div id="no-link-alert" class="alert alert-warning d-none" role="alert">
                            Não há uma empresa vinculada e autorizada para este usuário.
                            <br>
                            Você pode solicitar um novo vínculo.
                        </div>

                        <form id="empresaForm">
                            <!-- Campos do formulário -->
                            <div class="mb-3">
                                <label for="razaoSocial" class="form-label">Razão Social</label>
                                <input type="text" class="form-control" id="razaoSocial" name="razao_social" required disabled>
                            </div>
                            <div class="mb-3">
                                <label for="cnpj" class="form-label">CNPJ</label>
                                <input type="text" class="form-control" id="cnpj" name="cnpj" required disabled>
                            </div>
                            <div class="mb-3">
                                <label for="riscoEdificacao" class="form-label">Risco da Edificação</label>
                                <select class="form-select" id="riscoEdificacao" name="risco_edificacao" required disabled>
                                    <option value="Risco Baixo">Baixo</option>
                                    <option value="Risco Médio">Médio</option>
                                    <option value="Risco Alto">Alto</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="areaConstruida" class="form-label">Área Construída (m²)</label>
                                <input type="number" step="0.01" class="form-control" id="areaConstruida" name="area_construida_m2" required disabled>
                            </div>
                            <div class="mb-3">
                                <label for="possuiPscip" class="form-label">Possui PSCIP?</label>
                                <select class="form-select" id="possuiPscip" name="possui_pscip" required disabled>
                                    <option value="Possui">Sim</option>
                                    <option value="Não possui">Não</option>
                                    <option value="Não informado">Não informado</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="emailContato" class="form-label">E-mail de Contato</label>
                                <input type="email" class="form-control" id="emailContato" name="email_contato" required disabled>
                            </div>
                            <button type="submit" class="btn btn-success" id="salvarBtn" disabled>Salvar Alterações</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
                <!-- Conteúdo da aba de vínculos -->
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-danger text-white">
                        Suas Solicitações de Vínculo
                    </div>
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

<script>
    // --- FUNÇÃO DE ALERTA APRIMORADA ---
    // Variável para controlar o timer do alerta e evitar que múltiplos timers rodem ao mesmo tempo.
    let alertTimeout;

    function exibirAlerta(mensagem, tipo, duracao = 5000) {
        const alertsContainer = document.getElementById('alerts-container');
        
        // Limpa qualquer timer anterior para garantir que o novo alerta não seja fechado prematuramente.
        clearTimeout(alertTimeout);

        // Cria o HTML do alerta.
        alertsContainer.innerHTML = `
            <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
                ${mensagem}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        // Define um timer para remover o alerta após a 'duracao' especificada (padrão de 5 segundos).
        alertTimeout = setTimeout(() => {
            const alertNode = alertsContainer.querySelector('.alert');
            if (alertNode) {
                // Usa a API do Bootstrap para fechar o alerta com a animação de fade out.
                bootstrap.Alert.getOrCreateInstance(alertNode).close();
            }
        }, duracao);
    }

    // Função principal para carregar os dados do painel
    async function carregarPainel() {
        try {
            const response = await fetch('../api/gerenciar_empresas_api.php?action=carregar_painel');
            const data = await response.json();

            const empresaForm = document.getElementById('empresaForm');
            const noLinkAlert = document.getElementById('no-link-alert');
            const vinculacoesContainer = document.getElementById('vinculacoesContainer');

            if (data.success) {
                if (data.hasAuthorizedLink && data.empresa) {
                    noLinkAlert.classList.add('d-none');
                    const empresa = data.empresa;

                    empresaForm.querySelector('#razaoSocial').value = empresa.razao_social;
                    empresaForm.querySelector('#cnpj').value = empresa.cnpj;
                    empresaForm.querySelector('#riscoEdificacao').value = empresa.risco_edificacao;
                    empresaForm.querySelector('#areaConstruida').value = empresa.area_construida_m2;
                    empresaForm.querySelector('#possuiPscip').value = empresa.possui_pscip;
                    empresaForm.querySelector('#emailContato').value = empresa.email_contato;

                    empresaForm.querySelectorAll('input, select, button').forEach(element => {
                        element.disabled = false;
                    });
                } else {
                    noLinkAlert.classList.remove('d-none');
                    empresaForm.reset();
                    empresaForm.querySelectorAll('input, select, button').forEach(element => {
                        element.disabled = true;
                    });
                }

                vinculacoesContainer.innerHTML = '';
                if (data.vinculacoes && data.vinculacoes.length > 0) {
                    data.vinculacoes.forEach(vinculo => {
                        let statusText = '';
                        let statusClass = '';
                        switch (vinculo.status_vinculo) {
                            case 0: statusText = 'Pendente'; statusClass = 'text-warning'; break;
                            case 1: statusText = 'Autorizado'; statusClass = 'text-success'; break;
                            case 2: statusText = 'Rejeitado'; statusClass = 'text-danger'; break;
                            default: statusText = 'Desconhecido'; statusClass = 'text-muted';
                        }

                        const vinculoItem = document.createElement('div');
                        vinculoItem.className = 'list-group-item list-group-item-action flex-column align-items-start';
                        vinculoItem.innerHTML = `
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">${vinculo.razao_social}</h5>
                                <small class="${statusClass}"><strong>${statusText}</strong></small>
                            </div>
                            <p class="mb-1">CNPJ: ${vinculo.cnpj}</p>
                            ${vinculo.status_vinculo == 2 && vinculo.motivo_rejeicao ? `<small class="text-danger">Motivo da Rejeição: ${vinculo.motivo_rejeicao}</small>` : ''}
                        `;
                        vinculacoesContainer.appendChild(vinculoItem);
                    });
                } else {
                    vinculacoesContainer.innerHTML = '<p class="text-center text-muted">Nenhuma solicitação de vínculo encontrada.</p>';
                }
            } else {
                // A própria função carregarPainel também pode emitir alertas se falhar
                exibirAlerta(data.message, 'danger');
            }
        } catch (error) {
            exibirAlerta('Erro de rede ao carregar o painel. Tente novamente mais tarde.', 'danger');
            console.error('Erro em carregarPainel:', error);
        }
    }

    // Adiciona o evento de submissão do formulário para salvar as alterações
    document.getElementById('empresaForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('action', 'salvar_empresa');

        try {
            const response = await fetch('../api/gerenciar_empresas_api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            // Exibe a mensagem retornada pela API, seja de sucesso ou de erro.
            if (data.success) {
                exibirAlerta(data.message, 'success');
                // Opcional: recarregar os dados após exibir a mensagem para garantir consistência.
                // A mensagem de sucesso ficará visível por 5 segundos.
                setTimeout(carregarPainel, 1000); // Adiciona um pequeno delay antes de recarregar
            } else {
                exibirAlerta(data.message, 'danger');
            }
        } catch (error) {
            exibirAlerta('Erro de rede ao salvar as alterações. Tente novamente.', 'danger');
            console.error('Erro no submit:', error);
        }
    });

    // Carrega o painel ao iniciar a página
    document.addEventListener('DOMContentLoaded', carregarPainel);
</script>
<?php
include_once '../helpers/footer.php';
?>