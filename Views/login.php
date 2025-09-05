<?php
session_start();

include_once("../helpers/header_basico.php");

// Gera um token CSRF único e o armazena na sessão
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Inicializa as mensagens, se existirem na sessão
$mensagens = [];
$tipo_alerta = 'danger'; // Tipo padrão de alerta

if (isset($_SESSION['mensagens'])) {
    $mensagens = $_SESSION['mensagens'];
    // Verifica o tipo de mensagem para definir a classe do alerta
    if (isset($_SESSION['mensagem_tipo']) && $_SESSION['mensagem_tipo'] === 'success') {
        $tipo_alerta = 'success';
    }
    // Limpa as mensagens da sessão após serem lidas
    unset($_SESSION['mensagens']);
    unset($_SESSION['mensagem_tipo']);
}
?>

<body>

    <div class="container">
        <br /><br />

        <div class="col-md-6 mx-auto">
            <h3>Login</h3>

            <?php
            // Exibe as mensagens, se houver
            if (!empty($mensagens)) {
                echo '<div class="alert alert-' . $tipo_alerta . ' alert-dismissible fade show" role="alert">';
                foreach ($mensagens as $mensagem) {
                    echo "<p class='mb-0'>$mensagem</p>";
                }
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                echo '</div>';
            }
            ?>

            <form method="post" action="../Controllers/validar_acesso.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="form-group my-3">
                    <input type="text" class="form-control" id="cpf" name="cpf" placeholder="CPF (somente números)" required="required" pattern="\d{11}" title="Informe o CPF com 11 dígitos, sem pontos ou traços.">
                </div>
                <div class="input-group my-3">
                    <input type="password" class="form-control" id="senha" name="senha" placeholder="Senha" required="required">
                    <button class="btn btn-outline-secondary" type="button" id="toggleSenha" title="Mostrar/Ocultar senha">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <button type="submit" class="btn btn-primary form-control">Entrar</button>
            </form>

            <p class="mt-3 text-center">
                Ainda não tem uma conta? <a href="inscrevase.php">Registre-se</a>
            </p>
            <p class="mt-3 text-center">
                Esqueceu a senha? <a href="esqueci_senha.php">Recuperar senha</a>
            </p>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleSenha = document.getElementById('toggleSenha');
            const senhaInput = document.getElementById('senha');
            const icon = toggleSenha.querySelector('i');

            toggleSenha.addEventListener('click', function() {
                // Alterna o tipo do input entre 'password' e 'text'
                const type = senhaInput.getAttribute('type') === 'password' ? 'text' : 'password';
                senhaInput.setAttribute('type', type);

                // Alterna o ícone do botão
                if (type === 'text') {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });
    </script>
</body>

</html>