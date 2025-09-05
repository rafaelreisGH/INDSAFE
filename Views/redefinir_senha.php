<?php
// Views/redefinir_senha.php
session_start();
include_once("../helpers/header_basico.php");

// Gera um token CSRF único e o armazena na sessão
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Inicializa as mensagens
$mensagens = [];
if (isset($_SESSION['mensagens'])) {
    $mensagens = $_SESSION['mensagens'];
    unset($_SESSION['mensagens']);
}

// Verifica se o token está presente na URL
$token = $_GET['token'] ?? null;
$token_valido = false;

if ($token) {
    // Aqui você faria a validação real do token no banco de dados.
    // Para fins de demonstração, vamos apenas assumir que um token presente é o suficiente para mostrar o formulário.
    // A validação completa será feita no próximo controlador.
    $token_valido = true;
}
?>

<body>

    <div class="container">
        <br /><br />
        <div class="col-md-6 mx-auto">
            <h3>Redefinir Senha</h3>
            <p class="text-muted">Por favor, digite e confirme sua nova senha.</p>

            <?php
            if (!empty($mensagens)) {
                echo '<div class="alert alert-danger" role="alert">';
                foreach ($mensagens as $mensagem) {
                    echo "<p class='mb-0'>$mensagem</p>";
                }
                echo '</div>';
            }
            ?>

            <?php if ($token_valido) : ?>
                <form method="post" action="../Controllers/processa_redefinicao.php" id="formRedefinirSenha">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="form-group my-3">
                        <input type="password" class="form-control" id="senhaNova" name="senhaNova" 
                            placeholder="Nova senha" required="required">
                    </div>

                    <div class="form-group my-3">
                        <input type="password" class="form-control" id="confirmaSenha" name="confirmaSenha" 
                            placeholder="Confirme a nova senha" required="required">
                    </div>
                    
                    <button type="submit" class="btn btn-primary form-control">Alterar Senha</button>
                </form>
            <?php else : ?>
                <div class="alert alert-danger" role="alert">
                    <p class='mb-0'>O link de redefinição de senha é inválido ou já expirou.</p>
                </div>
            <?php endif; ?>

            <p class="mt-3 text-center">
                <a href="../Views/login.php">Voltar ao login</a>
            </p>

        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
