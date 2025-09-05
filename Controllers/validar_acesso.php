<?php
// Controllers/validar_acesso.php
session_start();

require_once '../ConexaoDB/conexao.php';
$conn = Conexao::getConexao();

// Array de mensagens para armazenar erros
$mensagens = [];

// 1. Validação de token CSRF para evitar ataques
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $mensagens[] = "Ocorreu um erro de segurança. Tente fazer login novamente.";
    $_SESSION['mensagens'] = $mensagens;
    $_SESSION['mensagem_tipo'] = 'danger'; // Define o tipo de mensagem para o login.php
    header('Location:../Views/login.php');
    exit;
}

// 2. Captura e saneamento dos dados de login
$cpf = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_NUMBER_INT);
$senha_original = filter_input(INPUT_POST, 'senha', FILTER_UNSAFE_RAW);

if (empty($cpf) || empty($senha_original)) {
    $mensagens[] = "Por favor, preencha todos os campos.";
    $_SESSION['mensagens'] = $mensagens;
    $_SESSION['mensagem_tipo'] = 'danger'; // Define o tipo de mensagem
    header('Location:../Views/login.php');
    exit;
}

// 3. Busca o usuário no banco de dados pelo CPF
$stmt = $conn->prepare("SELECT id, nome, email, cpf, senha_hash, ativo FROM usuarios WHERE cpf = :cpf LIMIT 1");
$stmt->bindParam(':cpf', $cpf);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario && password_verify($senha_original, $usuario['senha_hash'])) {

    // 4. Verifica se o usuário está ativo
    if ($usuario['ativo'] == 0) {
        $mensagens[] = "Sua conta ainda não foi ativada. Por favor, aguarde a aprovação do administrador.";
        $_SESSION['mensagens'] = $mensagens;
        $_SESSION['mensagem_tipo'] = 'danger'; // Define o tipo de mensagem
        header('Location:../Views/login.php');
        exit;
    }

    // Login validado. Agora, vamos buscar as permissões.

    // 5. Busca as permissões do usuário no banco de dados
    $stmt_permissoes = $conn->prepare("
        SELECT p.nome
        FROM permissoes AS p
        JOIN perfil_permissoes AS pp ON p.id = pp.permissao_id
        JOIN usuario_perfis AS up ON pp.perfil_id = up.perfil_id
        WHERE up.usuario_id = :id_usuario
    ");
    $stmt_permissoes->bindParam(':id_usuario', $usuario['id']);
    $stmt_permissoes->execute();
    $permissoes = $stmt_permissoes->fetchAll(PDO::FETCH_COLUMN, 0);

    // 6. Armazena os dados do usuário e as permissões na sessão
    $_SESSION['id_usuario'] = $usuario['id'];
    $_SESSION['nome_usuario'] = $usuario['nome'];
    $_SESSION['email_usuario'] = $usuario['email'];
    $_SESSION['cpf_usuario'] = $usuario['cpf'];
    $_SESSION['ativo_usuario'] = $usuario['ativo'];
    $_SESSION['permissoes'] = $permissoes;
    $_SESSION['logado'] = true;
    $_SESSION['start'] = time();

    // Limpa o token CSRF da sessão após o uso
    unset($_SESSION['csrf_token']);

    // 7. LÓGICA DE REDIRECIONAMENTO POR PERMISSÃO:
    // Se o usuário possui a permissão de gerenciar usuários, redireciona para a página de admin.
    if (in_array('gerenciar_usuarios', $_SESSION['permissoes'])) {
        header('Location:../Views/gerenciar_usuarios.php');
    } else {
        // Caso contrário, redireciona para o painel do usuário.
        header('Location:../Views/home_usuario.php');
    }
    exit;
    
} else {
    // 8. Caso o CPF ou a senha estejam incorretos
    $mensagens[] = "CPF ou senha inválidos. Tente novamente.";
    $_SESSION['mensagens'] = $mensagens;
    $_SESSION['mensagem_tipo'] = 'danger'; // Define o tipo de mensagem
    header('Location:../Views/login.php');
    exit;
}
