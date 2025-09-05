<?php
// Controllers/atualizar_usuario.php
session_start();

require_once '../ConexaoDB/conexao.php';
$conn = Conexao::getConexao();

// Array para mensagens de erro
$mensagens = [];

// 1. Valida o token CSRF
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $mensagens[] = "Ocorreu um erro de segurança. Por favor, tente novamente.";
    $_SESSION['mensagens'] = $mensagens;
    $_SESSION['mensagem_tipo'] = 'danger';
    header('Location:../Views/painel_do_usuario.php');
    exit;
}

// 2. Valida se o usuário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: ../Views/login.php');
    exit();
}

// 3. Captura e sanitiza os dados do formulário
$nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$id_usuario = $_SESSION['id_usuario'];

// 4. Validação dos campos
if (empty($nome) || empty($email)) {
    $mensagens[] = "Nome e e-mail não podem ficar vazios.";
    $_SESSION['mensagens'] = $mensagens;
    $_SESSION['mensagem_tipo'] = 'danger';
    header('Location:../Views/painel_do_usuario.php');
    exit;
}

// 5. Atualiza os dados no banco de dados
try {
    $stmt = $conn->prepare("UPDATE usuarios SET nome = :nome, email = :email WHERE id = :id");
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':id', $id_usuario);
    $stmt->execute();

    // 6. Atualiza as variáveis de sessão
    $_SESSION['nome_usuario'] = $nome;
    $_SESSION['email_usuario'] = $email;
    
    $mensagens[] = "Suas informações foram atualizadas com sucesso.";
    $_SESSION['mensagens'] = $mensagens;
    $_SESSION['mensagem_tipo'] = 'success';
    
    header('Location:../Views/painel_do_usuario.php');
    exit;

} catch (PDOException $e) {
    $mensagens[] = "Erro ao atualizar informações: " . $e->getMessage();
    $_SESSION['mensagens'] = $mensagens;
    $_SESSION['mensagem_tipo'] = 'danger';
    header('Location:../Views/painel_do_usuario.php');
    exit;
}
