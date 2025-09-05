<?php

session_start();

require_once '../ConexaoDB/conexao.php';
require_once __DIR__ . '/../Logger/LoggerFactory.php';
$logger = LoggerFactory::createLogger();
$conn = Conexao::getConexao();

// Função de validação de senha
require_once '../Controllers/validar_senha.php';

// Validação de token CSRF para evitar ataques
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    // Redireciona em caso de token inválido
    $logger->warning('Tentativa de registro de usuário com token CSRF inválido.');
    header('Location:../Views/inscrevase.php');
    exit;
}

// Captura dos dados
$nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$senha_original = filter_input(INPUT_POST, 'senha', FILTER_UNSAFE_RAW);
$confirma_senha = filter_input(INPUT_POST, 'confirma_senha', FILTER_UNSAFE_RAW);
$cpf = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_NUMBER_INT);

// Inicialização de erros
$erros = [];

// Validação da senha
if ($senha_original !== $confirma_senha) {
    $erros[] = "As senhas não coincidem.";
}

$senha_valida = validarSenha($senha_original);
if ($senha_valida !== true) {
    $erros[] = $senha_valida;
}

// Validação do e-mail
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros[] = "O e-mail informado é inválido.";
}

// Verificações no banco de dados
try {
    // Verifica se o nome já existe
    $stmt_nome = $conn->prepare("SELECT 1 FROM usuarios WHERE nome = :nome LIMIT 1");
    $stmt_nome->bindParam(':nome', $nome);
    $stmt_nome->execute();
    if ($stmt_nome->fetch()) {
        $erros[] = "O nome de usuário já está em uso.";
    }

    // Verifica se o e-mail já existe
    $stmt_email = $conn->prepare("SELECT 1 FROM usuarios WHERE email = :email LIMIT 1");
    $stmt_email->bindParam(':email', $email);
    $stmt_email->execute();
    if ($stmt_email->fetch()) {
        $erros[] = "O e-mail informado já está cadastrado.";
    }

    // Validação do CPF
    function validarCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;
    }

    if (!validarCPF($cpf)) {
        $erros[] = "O CPF informado é inválido.";
    } else {
        // Verifica se o CPF já existe
        $stmt_cpf = $conn->prepare("SELECT 1 FROM usuarios WHERE cpf = :cpf LIMIT 1");
        $stmt_cpf->bindParam(':cpf', $cpf);
        $stmt_cpf->execute();
        if ($stmt_cpf->fetch()) {
            $erros[] = "O CPF informado já está cadastrado.";
        }
    }

} catch (PDOException $e) {
    // Em caso de erro no banco de dados
    $erros[] = "Ocorreu um erro interno. Por favor, tente novamente mais tarde.";
    
    $logger->error('Erro no banco de dados durante a validação de novo usuário.', [
        'nome' => $nome,
        'email' => $email,
        'erro' => $e->getMessage()
    ]);
}

// Redireciona com os erros, se houver
if (!empty($erros)) {
    $_SESSION['mensagens'] = $erros;
    header("Location:../Views/inscrevase.php");
    exit;
}

// Se chegou aqui, está tudo certo — cadastrar usuário
try {
    // Gera hash seguro da senha
    $senha_hash = password_hash($senha_original, PASSWORD_DEFAULT);

    // Insere no banco com status 0 (bloqueado)
    $stmt = $conn->prepare("
        INSERT INTO usuarios (nome, email, cpf, senha_hash, ativo) 
        VALUES (:nome, :email, :cpf, :senha_hash, :ativo)
    ");
    $stmt->bindValue(':nome', $nome);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':cpf', $cpf);
    $stmt->bindValue(':senha_hash', $senha_hash);
    $stmt->bindValue(':ativo', 0); // Bloqueado até aprovação do admin

    if ($stmt->execute()) {
        // Log de sucesso
        $logger->info('Novo usuário registrado com sucesso.', [
            'nome' => $nome,
            'email' => $email
        ]);
        header('Location:../Views/recem_cadastrado.php');
        exit;
    } else {
        $erros[] = "Ocorreu um erro inesperado ao salvar os dados. Tente novamente mais tarde.";
        $_SESSION['mensagens'] = $erros;
        $logger->error('Erro inesperado ao salvar os dados do novo usuário.', [
            'nome' => $nome,
            'email' => $email,
            'erro' => 'Erro na execução da query de inserção.'
        ]);
        header('Location:../Views/inscrevase.php');
        exit;
    }

} catch (PDOException $e) {
    // Em caso de erro no insert
    $erros[] = "Ocorreu um erro inesperado ao salvar os dados. Tente novamente mais tarde.";
    $_SESSION['mensagens'] = $erros;
    
    // Log de insucesso
    $logger->error('Erro no banco de dados ao tentar registrar novo usuário.', [
        'nome' => $nome,
        'email' => $email,
        'erro' => $e->getMessage()
    ]);

    header('Location:../Views/inscrevase.php');
    exit;
}
