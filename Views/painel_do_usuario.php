<?php
// Views/painel_do_usuario.php
session_start();
include_once '../helpers/header.php';

// Redireciona para o login se o usuário não estiver logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: ../Views/login.php');
    exit();
}

// Gerar token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Inicializa as mensagens de erro
$mensagens = [];
$tipo_alerta = 'danger';
if (isset($_SESSION['mensagens'])) {
    $mensagens = $_SESSION['mensagens'];
    if (isset($_SESSION['mensagem_tipo'])) {
        $tipo_alerta = $_SESSION['mensagem_tipo'];
    }
    unset($_SESSION['mensagens']);
    unset($_SESSION['mensagem_tipo']);
}
?>

<main class="container my-4 d-flex flex-column align-items-center">
    <div class="col-md-8 p-4">
        <h3 class="mb-4">Painel do Usuário</h3>
        <div id="alerts-container" class="mt-3">
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
        </div>
        <div class="card">
            <div class="card-header">
                Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['nome_usuario']); ?>!
            </div>
            <div class="card-body">
                <p>Este é o seu painel pessoal. Aqui você pode visualizar suas informações e gerenciar suas configurações.</p>
                <hr>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title">Informações da Conta</h5>
                                <p class="card-text">
                                    <strong>E-mail:</strong> <?php echo htmlspecialchars($_SESSION['email_usuario']); ?><br>
                                    <strong>CPF:</strong> <?php echo htmlspecialchars($_SESSION['cpf_usuario']); ?><br>
                                </p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                    Editar Perfil
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title">Seu Perfil</h5>
                                <p class="card-text">
                                    Você tem as seguintes permissões:<br>
                                    <?php
                                    if (!empty($_SESSION['permissoes'])) {
                                        echo '<ul>';
                                        foreach ($_SESSION['permissoes'] as $permissao) {
                                            echo '<li>' . htmlspecialchars($permissao) . '</li>';
                                        }
                                        echo '</ul>';
                                    } else {
                                        echo 'Nenhuma permissão específica atribuída.';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal de Edição de Perfil -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">Editar Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="../Controllers/atualizar_usuario.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="mb-3">
                        <label for="editNome" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="editNome" name="nome" value="<?php echo htmlspecialchars($_SESSION['nome_usuario']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmail" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="editEmail" name="email" value="<?php echo htmlspecialchars($_SESSION['email_usuario']); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
include_once '../helpers/footer.php';
?>
