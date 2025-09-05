<?php
// api/busca_info_tec.php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../ConexaoDB/conexao.php';
require_once __DIR__ . '/../Logger/LoggerFactory.php';

try {
    $database = new Conexao();
    $db = $database->getConexao();
    $logger = LoggerFactory::createLogger();

    if (!$db) {
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
        'tipo_busca' => 'Busca por Informação Técnica',
        'termo_busca' => $nome_empresa,
        'id' => $_SESSION['id_usuario'] ?? 'N/A',
        'usuario' => $_SESSION['nome_usuario'] ?? 'N/A',
    ];

    if (empty($nome_empresa)) {
        $logger->warning('Busca por info. técnica falhou - termo vazio', $log_context);
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
        $logger->info('Busca por info. técnica não encontrou resultados', $log_context);
        http_response_code(404); // Not Found
        echo json_encode(["success" => false, "message" => "Nenhuma empresa encontrada com este nome."]);
        exit();
    }

    // 2. Se a busca foi bem-sucedida, registre no log
    $log_context['resultado_busca'] = 'Sucesso';
    $log_context['empresa_encontrada'] = $empresa['razao_social'];
    $logger->info('Busca por info. técnica bem-sucedida', $log_context);

    // 3. Buscar dados relacionados
    $empresa_id = $empresa['id'];

    // Responsáveis Técnicos
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

    // Medidas de Segurança
    $query_medidas = "SELECT T1.medida_id, T2.descricao AS medida_nome FROM empresa_medidas_seguranca AS T1 JOIN catalogo_medidas_seguranca AS T2 ON T1.medida_id = T2.id WHERE T1.empresa_id = :id";
    $stmt_medidas = $db->prepare($query_medidas);
    $stmt_medidas->bindParam(':id', $empresa_id, PDO::PARAM_INT);
    $stmt_medidas->execute();
    $medidas_seguranca = $stmt_medidas->fetchAll(PDO::FETCH_ASSOC);
    $empresa['medidas_seguranca'] = $medidas_seguranca;

    // Riscos Potenciais
    $query_riscos = "SELECT T1.risco_id, T2.descricao AS risco_nome FROM empresa_riscos_potenciais AS T1 JOIN catalogo_riscos_potenciais AS T2 ON T1.risco_id = T2.id WHERE T1.empresa_id = :id";
    $stmt_riscos = $db->prepare($query_riscos);
    $stmt_riscos->bindParam(':id', $empresa_id, PDO::PARAM_INT);
    $stmt_riscos->execute();
    $riscos_potenciais = $stmt_riscos->fetchAll(PDO::FETCH_ASSOC);
    $empresa['riscos_potenciais'] = $riscos_potenciais;

    http_response_code(200); // OK
    echo json_encode(["success" => true, "data" => $empresa]);

} catch (PDOException $e) {
    // Log de erro
    $logger->error('Erro na busca por info. técnica', [
        'termo_busca' => $nome_empresa,
        'id' => $_SESSION['id_usuario'] ?? 'N/A',
        'usuario' => $_SESSION['nome_usuario'] ?? 'N/A',
        'erro' => $e->getMessage()
    ]);
    http_response_code(500); // Internal Server Error
    echo json_encode(["success" => false, "message" => "Erro no banco de dados: " . $e->getMessage()]);
    exit();
}