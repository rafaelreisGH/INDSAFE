<?php
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
    unset($_SESSION['mensagens']);
}
?>

<body>

    <div class="container">
        <br /><br />

        <div class="col-md-6 mx-auto">
            <h3>Recuperar Senha</h3>
            <p class="text-muted">Informe seu CPF e e-mail cadastrados para receber as instruções de redefinição.</p>

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

            <form method="post" action="../Controllers/enviar_link.php" id="formEsqueciSenha">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="form-group my-3">
                    <input type="text" class="form-control" id="cpf" name="cpf" 
                           placeholder="CPF (somente números)" required="required"
                           pattern="\d{11}" title="Informe o CPF com 11 dígitos, sem pontos ou traços.">
                </div>

                <div class="form-group my-3">
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="E-mail cadastrado" required="required">
                </div>
                
                <button type="submit" class="btn btn-primary form-control">Recuperar Senha</button>
            </form>
            
            <p class="mt-3 text-center">
                Lembrou sua senha? <a href="../Views/login.php">Voltar ao login</a>
            </p>

        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
