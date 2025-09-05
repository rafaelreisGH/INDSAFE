<?php
// Controllers/muda_senha.php

session_start();
require_once '../ConexaoDB/conexao.php';
$conn = Conexao::getConexao();
require_once '../Controllers/validar_senha.php';
require_once __DIR__ . '/../Logger/LoggerFactory.php';

$senhaNova = filter_input(INPUT_POST, 'senhaNova', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
$confirmaSenha = filter_input(INPUT_POST, 'confirmaSenha', FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);

$logger = LoggerFactory::createLogger();
header('Content-Type: application/json; charset=UTF-8');

// 1. Verifica se as senhas coincidem
if ($senhaNova !== $confirmaSenha) {
    $logger->warning('Tentativa de mudança de senha falhou: senhas não coincidem', [
        'id' => $_SESSION['id_usuario'] ?? 'N/A',
        'usuario' => $_SESSION['nome_usuario'] ?? 'N/A'
    ]);

    echo json_encode([
        "status" => "erro",
        "mensagem" => "As senhas não coincidem. Por favor, tente novamente."
    ]);
    exit;
}

// 2. Valida a senha
$erroValidacao = validarSenha($senhaNova);
if ($erroValidacao !== true) {
    $logger->warning('Tentativa de mudança de senha falhou: senha inválida', [
        'id' => $_SESSION['id_usuario'] ?? 'N/A',
        'usuario' => $_SESSION['nome_usuario'] ?? 'N/A'
    ]);

    echo json_encode([
        "status" => "erro",
        "mensagem" => $erroValidacao
    ]);
    exit;
}

$senhaHash = password_hash($senhaNova, PASSWORD_DEFAULT);
$user_email = $_SESSION['email_usuario'];

try {
    // Atualiza apenas a senha_hash
    $stmt = $conn->prepare("UPDATE usuarios SET senha_hash = ? WHERE email = ?");
    $stmt->bindParam(1, $senhaHash, PDO::PARAM_STR);
    $stmt->bindParam(2, $user_email, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $logger->info('Senha alterada com sucesso', [
            'id' => $_SESSION['id_usuario'] ?? 'N/A',
            'usuario' => $_SESSION['nome_usuario'] ?? 'N/A'
        ]);

        // Destrói sessão para forçar novo login
        session_unset();
        session_destroy();

        echo json_encode([
            "status" => "sucesso",
            "mensagem" => "Senha alterada com sucesso. Faça login com a nova senha."
        ]);
    } else {
        $logger->error('Erro ao alterar senha no banco de dados', [
            'id' => $_SESSION['id_usuario'] ?? 'N/A',
            'usuario' => $_SESSION['nome_usuario'] ?? 'N/A',
            'email' => $user_email
        ]);

        echo json_encode([
            "status" => "erro",
            "mensagem" => "Erro ao alterar a senha. Tente novamente."
        ]);
    }
} catch (PDOException $ex) {
    $logger->error('Exceção PDO ao alterar a senha', [
        'id' => $_SESSION['id_usuario'] ?? 'N/A',
        'usuario' => $_SESSION['nome_usuario'] ?? 'N/A',
        'erro' => $ex->getMessage()
    ]);

    echo json_encode([
        "status" => "erro",
        "mensagem" => "Erro ao alterar a senha: " . $ex->getMessage()
    ]);
}
