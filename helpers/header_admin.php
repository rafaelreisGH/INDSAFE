<?php
// helpers/header_admin.php
include_once '../Controllers/validar_sessao.php';

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

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale-1.0">
    <title>Portal INDSAFE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <div class="container">
        <header class="bg-light p-3 border-bottom">
            <div class="container-fluid d-flex align-items-center">
                <img src="../img/Vetor CBMMT.png" alt="Logo" style="height: 40px;" class="me-3">
                <h1 class="h4 mb-0">
                    <a class="py-2 link-body-emphasis text-decoration-none" href="../index.php">Portal INDSAFE</a>
                </h1>
                <nav class="d-inline-flex mt-2 mt-md-0 ms-md-auto">
                    <a class="me-3 py-2 link-body-emphasis text-decoration-none" href="#" data-bs-toggle="modal" data-bs-target="#userProfileModal">Painel do usuário</a>
                    <a class="me-3 py-2 link-body-emphasis text-decoration-none" href="#" data-bs-toggle="modal" data-bs-target="#mudaSenhaModal">Alterar senha</a>
                    <a class="py-2 link-body-emphasis text-decoration-none" href="../sair.php">Sair</a>
                </nav>
            </div>
        </header>

        <div class="modal fade" id="mudaSenhaModal" tabindex="-1" aria-labelledby="mudaSenhaModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="mudaSenhaModalLabel">Alterar Senha</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" action="../Controllers/muda_senha.php" id="form-muda-senha">
                        <div class="modal-body">
                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="senhaNova" name="senhaNova" placeholder="Nova senha" required>
                                <label for="senhaNova">Nova senha</label>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="confirmaSenha" name="confirmaSenha" placeholder="Confirme a nova senha" required>
                                <label for="confirmaSenha">Confirme a nova senha</label>
                            </div>
                            <div id="resultadoSenha" class="mt-2"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            <button type="submit" class="btn btn-primary">Alterar senha</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="userProfileModal" tabindex="-1" aria-labelledby="userProfileModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="userProfileModalLabel">Painel do Usuário</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="alerts-container">
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
                                    <div class="col-md-12 mb-3">
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
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
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
                </div>
            </div>
        </div>

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
        
        <div class="p-3 border-bottom">
            <div class="btn-group" role="group" aria-label="Basic outlined example">
                <a class="btn btn-outline-danger" href="../Views/gerenciar_usuarios.php" role="button">Usuários</a>
                <a class="btn btn-outline-danger" href="../Views/gerenciar_permissoes.php" role="button">Perfis</a>
            </div>
        </div>
        