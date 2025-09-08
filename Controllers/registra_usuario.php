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
    $logger->warning('Tentativa de registro de usuário com token CSRF inválido.');
    // Responde com JSON
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido.']);
    exit;
}

// Captura dos dados
$nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$senha_original = filter_input(INPUT_POST, 'senha', FILTER_UNSAFE_RAW);
$confirma_senha = filter_input(INPUT_POST, 'confirma_senha', FILTER_UNSAFE_RAW);
$cpf = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_NUMBER_INT);
$empresa_id = filter_input(INPUT_POST, 'empresa_id', FILTER_SANITIZE_NUMBER_INT);

// Inicialização de erros
$erros = [];

// Validação de campos
if (empty($empresa_id)) {
    $erros[] = "Por favor, selecione uma empresa da lista.";
}
if ($senha_original !== $confirma_senha) {
    $erros[] = "As senhas não coincidem.";
}
$senha_valida = validarSenha($senha_original);
if ($senha_valida !== true) {
    $erros[] = $senha_valida;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erros[] = "O e-mail informado é inválido.";
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
}

// Verificações no banco de dados
try {
    $stmt_nome = $conn->prepare("SELECT 1 FROM usuarios WHERE nome = :nome LIMIT 1");
    $stmt_nome->bindParam(':nome', $nome);
    $stmt_nome->execute();
    if ($stmt_nome->fetch()) {
        $erros[] = "O nome de usuário já está em uso.";
    }

    $stmt_email = $conn->prepare("SELECT 1 FROM usuarios WHERE email = :email LIMIT 1");
    $stmt_email->bindParam(':email', $email);
    $stmt_email->execute();
    if ($stmt_email->fetch()) {
        $erros[] = "O e-mail informado já está cadastrado.";
    }

    $stmt_cpf = $conn->prepare("SELECT 1 FROM usuarios WHERE cpf = :cpf LIMIT 1");
    $stmt_cpf->bindParam(':cpf', $cpf);
    $stmt_cpf->execute();
    if ($stmt_cpf->fetch()) {
        $erros[] = "O CPF informado já está cadastrado.";
    }

} catch (PDOException $e) {
    $erros[] = "Ocorreu um erro interno. Por favor, tente novamente mais tarde.";
    $logger->error('Erro no banco de dados durante a validação de novo usuário.', [
        'nome' => $nome,
        'email' => $email,
        'erro' => $e->getMessage()
    ]);
}

// Retorna os erros em JSON
if (!empty($erros)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $erros)]);
    exit;
}

// Se chegou aqui, está tudo certo — cadastrar usuário
try {
    $senha_hash = password_hash($senha_original, PASSWORD_DEFAULT);

    // Inicia uma transação para garantir que ambas as operações sejam bem-sucedidas ou falhem juntas.
    $conn->beginTransaction();

    // Insere o novo usuário
    $stmt = $conn->prepare("
        INSERT INTO usuarios (nome, email, cpf, senha_hash, ativo) 
        VALUES (:nome, :email, :cpf, :senha_hash, :ativo)
    ");
    $stmt->bindValue(':nome', $nome);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':cpf', $cpf);
    $stmt->bindValue(':senha_hash', $senha_hash);
    $stmt->bindValue(':ativo', 0); // Bloqueado até aprovação do admin

    if (!$stmt->execute()) {
        throw new Exception("Erro ao inserir usuário.");
    }
    
    $usuario_id = $conn->lastInsertId();

    // Insere o vínculo com a empresa como pendente (0)
    $stmt_vinculo = $conn->prepare("
        INSERT INTO usuario_empresa_vinculo (usuario_id, empresa_id, status_vinculo)
        VALUES (:usuario_id, :empresa_id, 0)
    ");
    $stmt_vinculo->bindValue(':usuario_id', $usuario_id);
    $stmt_vinculo->bindValue(':empresa_id', $empresa_id);

    if (!$stmt_vinculo->execute()) {
        throw new Exception("Erro ao inserir vínculo com a empresa.");
    }

    $conn->commit();

    $logger->info('Novo usuário e vínculo com empresa registrados com sucesso.', [
        'nome' => $nome,
        'email' => $email,
        'usuario_id' => $usuario_id,
        'empresa_id' => $empresa_id
    ]);
    
    // Unset do token apenas após o sucesso
    unset($_SESSION['csrf_token']);
    
    echo json_encode(['success' => true, 'message' => 'Cadastro realizado com sucesso! Aguarde a aprovação do administrador.']);

} catch (Exception $e) {
    $conn->rollBack();
    $erros[] = "Ocorreu um erro inesperado ao salvar os dados. Tente novamente mais tarde.";
    
    $logger->error('Erro no banco de dados ao tentar registrar novo usuário ou vínculo.', [
        'nome' => $nome,
        'email' => $email,
        'erro' => $e->getMessage()
    ]);

    echo json_encode(['success' => false, 'message' => implode(' ', $erros)]);
}
