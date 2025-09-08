<?php
// Views/inscrevase.php
// Inicia a sessão para usar variáveis de sessão para mensagens de erro e token CSRF
session_start();

include_once("../helpers/header_basico.php");

// Gera um token CSRF único e o armazena na sessão
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Inicializa as mensagens de erro
$mensagens = [];
if (isset($_SESSION['mensagens'])) {
    $mensagens = $_SESSION['mensagens'];
    // Limpa a sessão para que as mensagens não apareçam novamente após o refresh
    unset($_SESSION['mensagens']);
}
?>

<body>

    <div class="container">
        <br /><br />

        <div class="col-md-6 mx-auto">
            <h3>Registre-se</h3>

            <?php
            // Exibe as mensagens, se houver
            if (!empty($mensagens)) {
                echo '<div class="alert alert-danger" role="alert">';
                foreach ($mensagens as $mensagem) {
                    echo "<p class='mb-0'>$mensagem</p>";
                }
                echo '</div>';
            }
            ?>

            <form id="formCadastrarse">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="form-group my-3">
                    <input type="text" class="form-control" id="nome" name="nome" placeholder="Nome completo" required="required">
                </div>
                <div class="form-group my-3">
                    <input type="text" class="form-control" id="cpf" name="cpf" placeholder="CPF (somente números)" required="required" pattern="\d{11}" title="Informe o CPF com 11 dígitos, sem pontos ou traços.">
                </div>
                <div class="form-group my-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" required="required">
                </div>
                <div class="form-group my-3">
                    <input type="password" class="form-control" id="senha" name="senha" placeholder="Senha" required="required">
                </div>
                <div class="form-group my-3">
                    <input type="password" class="form-control" id="confirma_senha" name="confirma_senha" placeholder="Confirme a senha" required="required">
                </div>
                
                <!-- Campo de busca de empresas com datalist -->
                <div class="form-group my-3">
                    <input type="text" class="form-control" id="razao_social_search" name="razao_social_search" placeholder="Busque a razão social da sua empresa" list="empresas-list" required="required">
                    <datalist id="empresas-list"></datalist>
                </div>
                
                <!-- Campo oculto para armazenar o ID da empresa selecionada -->
                <input type="hidden" id="empresa_id" name="empresa_id">

                <div class="col-md-4 mx-auto">
                    <button type="submit" class="btn btn-danger form-control">Registrar-se</button>
                </div>
            </form>

            <p class="mt-3 text-center">
                Já tem uma conta? <a href="../login.php">Faça login</a>
            </p>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('formCadastrarse');
            const razaoSocialSearch = document.getElementById('razao_social_search');
            const empresaDatalist = document.getElementById('empresas-list');
            const empresaIdInput = document.getElementById('empresa_id');
            
            let searchTimeout;

            // Busca empresas dinamicamente enquanto o usuário digita
            razaoSocialSearch.addEventListener('input', function() {
                const searchTerm = this.value;

                // Limpa o timeout anterior para evitar múltiplas requisições
                clearTimeout(searchTimeout);

                if (searchTerm.length > 2) {
                    searchTimeout = setTimeout(() => {
                        fetch(`../api/get_empresas.php?term=${encodeURIComponent(searchTerm)}`)
                            .then(response => response.json())
                            .then(data => {
                                empresaDatalist.innerHTML = '';
                                
                                data.forEach(empresa => {
                                    const option = document.createElement('option');
                                    option.value = `${empresa.razao_social} - ${empresa.cnpj}`;
                                    option.dataset.id = empresa.id; 
                                    empresaDatalist.appendChild(option);
                                });
                            })
                            .catch(error => console.error('Erro na busca de empresas:', error));
                    }, 300);
                } else {
                    empresaDatalist.innerHTML = '';
                    empresaIdInput.value = '';
                }
            });

            // Captura o ID da empresa quando uma opção do datalist é selecionada
            razaoSocialSearch.addEventListener('change', function() {
                const selectedOption = empresaDatalist.querySelector(`option[value="${this.value}"]`);
                if (selectedOption) {
                    empresaIdInput.value = selectedOption.dataset.id;
                } else {
                    empresaIdInput.value = '';
                }
            });
            
            form.addEventListener('submit', function(event) {
                event.preventDefault(); // Impede o envio padrão do formulário
                
                const senha = document.getElementById('senha').value;
                const confirmaSenha = document.getElementById('confirma_senha').value;

                if (senha !== confirmaSenha) {
                    alert('As senhas não coincidem!');
                    return;
                }
                
                if (!empresaIdInput.value) {
                    alert('Por favor, selecione uma empresa da lista.');
                    return;
                }
                
                const formData = new FormData(form);
                
                fetch('../Controllers/registra_usuario.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.href = '../Views/recem_cadastrado.php';
                    } else {
                        alert('Erro no cadastro: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Ocorreu um erro no servidor. Tente novamente mais tarde.');
                });
            });
        });
    </script>
</body>

</html>
