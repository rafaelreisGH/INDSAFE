<?php
// Inclui o arquivo de conexão com o banco de dados
require_once '../ConexaoDB/conexao.php';
require_once __DIR__ . '/../Logger/LoggerFactory.php';
$logger = LoggerFactory::createLogger();
$conn = Conexao::getConexao();

header('Content-Type: application/json');

// Captura e sanitiza o termo de busca
$searchTerm = filter_input(INPUT_GET, 'term', FILTER_SANITIZE_SPECIAL_CHARS);

// Se o termo de busca estiver vazio, retorna uma lista vazia
if (empty($searchTerm)) {
    echo json_encode([]);
    exit;
}

try {
    // A consulta usa LIKE para buscar a razão social que contenha o termo
    // O uso de LIMIT evita que o banco de dados seja sobrecarregado com muitos resultados
    $stmt = $conn->prepare("
        SELECT id, razao_social, cnpj 
        FROM empresas 
        WHERE razao_social LIKE :searchTerm
        ORDER BY razao_social
        LIMIT 20
    ");
    // O '%' é usado para a busca parcial
    $searchWildcard = "%" . $searchTerm . "%";
    $stmt->bindParam(':searchTerm', $searchWildcard);
    $stmt->execute();

    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($empresas);
} catch (PDOException $e) {
    $logger->error('Erro no banco de dados durante a busca de empresas.', [
        'termo_busca' => $searchTerm,
        'erro' => $e->getMessage()
    ]);
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Ocorreu um erro ao buscar as empresas.']);
}
