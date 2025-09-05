<?php
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

            <form method="post" action="../Controllers/registra_usuario.php" id="formCadastrarse">
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
                <button type="submit" class="btn btn-danger form-control">Registrar-se</button>
            </form>
            
            <p class="mt-3 text-center">
                Já tem uma conta? <a href="../login.php">Faça login</a>
            </p>

        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Adiciona um evento de escuta para o envio do formulário
        document.getElementById('formCadastrarse').addEventListener('submit', function(event) {
            const senha = document.getElementById('senha').value;
            const confirmaSenha = document.getElementById('confirma_senha').value;
            
            if (senha !== confirmaSenha) {
                // Previne o envio do formulário
                event.preventDefault();
                alert('As senhas não coincidem!');
            }
        });
    </script>

</body>

</html>