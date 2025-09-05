<?php
// Controllers/processa_redefinicao.php
session_start();

require_once '../ConexaoDB/conexao.php';
$conn = Conexao::getConexao();
require_once '../Controllers/validar_senha.php';
require_once __DIR__ . '/../Logger/LoggerFactory.php';

$logger = LoggerFactory::createLogger();

// 1. Captura e saneamento dos dados do formulário
$token = filter_input(INPUT_POST, 'token', FILTER_UNSAFE_RAW);
$senhaNova = filter_input(INPUT_POST, 'senhaNova', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
$confirmaSenha = filter_input(INPUT_POST, 'confirmaSenha', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);

// 2. Verifica se as senhas coincidem
if ($senhaNova !== $confirmaSenha) {
    $_SESSION['mensagens'][] = "As senhas não coincidem. Por favor, tente novamente.";
    header('Location: ../Views/redefinir_senha.php?token=' . urlencode($token));
    exit;
}

// 3. Valida a senha (usando a mesma função de validação)
$erroValidacao = validarSenha($senhaNova);
if ($erroValidacao !== true) {
    $_SESSION['mensagens'][] = $erroValidacao;
    header('Location: ../Views/redefinir_senha.php?token=' . urlencode($token));
    exit;
}

try {
    // 4. Busca o usuário pelo token
    $stmt = $conn->prepare("SELECT id, token_expiracao FROM usuarios WHERE token_recuperacao = ?");
    $stmt->bindParam(1, $token, PDO::PARAM_STR);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // 5. Verifica a validade do token
    if ($usuario && $usuario['token_expiracao'] > date('Y-m-d H:i:s')) {
        // Token é válido e não expirou.
        $senhaHash = password_hash($senhaNova, PASSWORD_DEFAULT);

        // 6. Atualiza a senha e limpa os campos do token
        $stmt_update = $conn->prepare("UPDATE usuarios SET senha_hash = ?, token_recuperacao = NULL, token_expiracao = NULL WHERE id = ?");
        $stmt_update->bindParam(1, $senhaHash, PDO::PARAM_STR);
        $stmt_update->bindParam(2, $usuario['id'], PDO::PARAM_INT);
        $stmt_update->execute();

        $_SESSION['mensagens'][] = "Sua senha foi alterada com sucesso. Faça login com a nova senha.";
        $logger->info('Senha redefinida com sucesso.', ['id' => $usuario['id']]);
        header('Location: ../Views/login.php');
        exit;
    } else {
        // Token inválido ou expirado.
        $_SESSION['mensagens'][] = "O link de redefinição é inválido ou já expirou. Por favor, solicite um novo link.";
        $logger->warning('Tentativa de redefinicao de senha com token invalido.', ['token' => $token]);
        header('Location: ../Views/esqueci_senha.php');
        exit;
    }

} catch (PDOException $ex) {
    $_SESSION['mensagens'][] = "Ocorreu um erro inesperado. Tente novamente.";
    $logger->error('Erro PDO ao processar redefinicao de senha.', ['erro' => $ex->getMessage()]);
    header('Location: ../Views/redefinir_senha.php?token=' . urlencode($token));
    exit;
}
