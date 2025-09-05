<?php
// /api/busca_empresa.php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../ConexaoDB/conexao.php';
require_once __DIR__ . '/../Logger/LoggerFactory.php'; // Adicione a inclusão da fábrica de logger

try {
    $database = new Conexao();
    $db = $database->getConexao();
    $logger = LoggerFactory::createLogger();

    if (!$db) {
        // Log de erro de conexão com o banco de dados
        $logger->error('Erro na conexão com o banco de dados', [
            'id' => $_SESSION['id_usuario'] ?? 'N/A',
            'usuario' => $_SESSION['nome_usuario'] ?? 'N/A'
        ]);
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Não foi possível conectar ao banco de dados."]);
        exit();
    }

    $nome_empresa = isset($_GET['nome']) ? trim($_GET['nome']) : null;

    $log_context = [
        'tipo_busca' => 'Busca por Empresa',
        'termo_busca' => $nome_empresa,
        'id' => $_SESSION['id_usuario'] ?? 'N/A',
        'usuario' => $_SESSION['nome_usuario'] ?? 'N/A',
    ];

    if (empty($nome_empresa)) {
        $logger->warning('Busca por empresa falhou - termo vazio', $log_context);
        http_response_code(400); // Bad Request
        echo json_encode(["success" => false, "message" => "O nome da empresa não pode ser vazio."]);
        exit();
    }

    $nome_empresa_sanitizado = '%' . strtolower($nome_empresa) . '%';

    // 1. Buscar dados principais da empresa
    $query_empresa = "SELECT * FROM empresas WHERE LOWER(razao_social) LIKE :nome LIMIT 1";
    $stmt_empresa = $db->prepare($query_empresa);
    $stmt_empresa->bindParam(':nome', $nome_empresa_sanitizado);
    $stmt_empresa->execute();
    $empresa = $stmt_empresa->fetch(PDO::FETCH_ASSOC);

    if (!$empresa) {
        // Conforme a solicitação, não vamos mais logar quando a busca não encontrar resultados.
        // Apenas enviamos a resposta para o frontend.
        http_response_code(404); // Not Found
        echo json_encode(["success" => false, "message" => "Nenhuma empresa encontrada com este nome."]);
        exit();
    }

    // 2. Se a busca foi bem-sucedida, registre o sucesso
    $log_context['resultado_busca'] = 'Sucesso';
    $log_context['empresa_encontrada'] = $empresa['razao_social'];
    $logger->info('Busca por empresa bem-sucedida', $log_context);

    // 2. Buscar dados relacionados
    $empresa_id = $empresa['id'];

    // Responsáveis Técnicos - Buscando somente nome e email
    $query_responsaveis = "SELECT nome, email FROM responsaveis_tecnicos WHERE empresa_id = :id";
    $stmt_responsaveis = $db->prepare($query_responsaveis);
    $stmt_responsaveis->bindParam(':id', $empresa_id, PDO::PARAM_INT);
    $stmt_responsaveis->execute();
    $responsaveis = $stmt_responsaveis->fetchAll(PDO::FETCH_ASSOC);
    $empresa['responsaveis_tecnicos'] = $responsaveis;

    // Contatos
    $query_contatos = "SELECT * FROM contatos_na_empresa WHERE empresas_id = :id";
    $stmt_contatos = $db->prepare($query_contatos);
    $stmt_contatos->bindParam(':id', $empresa_id, PDO::PARAM_INT);
    $stmt_contatos->execute();
    $contatos = $stmt_contatos->fetchAll(PDO::FETCH_ASSOC);
    $empresa['contatos'] = $contatos;

    // Contribuições - Nova busca
    $query_contribuicao = "SELECT sugestao_contribuicao FROM contribuicoes WHERE empresa_id = :id";
    $stmt_contribuicao = $db->prepare($query_contribuicao);
    $stmt_contribuicao->bindParam(':id', $empresa_id, PDO::PARAM_INT);
    $stmt_contribuicao->execute();
    $contribuicoes = $stmt_contribuicao->fetchAll(PDO::FETCH_ASSOC);
    $empresa['contribuicoes'] = $contribuicoes;

    http_response_code(200); // OK
    echo json_encode(["success" => true, "data" => $empresa]);

} catch (PDOException $e) {
    // Em caso de erro, registre a falha
    $logger->error('Erro na busca por empresa', [
        'termo_busca' => $nome_empresa,
        'id' => $_SESSION['id_usuario'] ?? 'N/A',
        'usuario' => $_SESSION['nome_usuario'] ?? 'N/A',
        'erro' => $e->getMessage()
    ]);
    http_response_code(500); // Internal Server Error
    echo json_encode(["success" => false, "message" => "Erro no banco de dados: " . $e->getMessage()]);
    exit();
}
