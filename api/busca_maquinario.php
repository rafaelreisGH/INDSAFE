<?php
// api/busca_maquinario.php
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

    // O valor de GET['maquinario'] agora é o ID do recurso
    $recurso_id = isset($_GET['maquinario']) ? trim($_GET['maquinario']) : null;

    $log_context = [
        'tipo_busca' => 'Busca por Maquinário',
        'recurso_id' => $recurso_id,
        'id' => $_SESSION['id_usuario'] ?? 'N/A',
        'usuario' => $_SESSION['nome_usuario'] ?? 'N/A',
    ];

    if (empty($recurso_id)) {
        $logger->warning('Busca por maquinário falhou - ID de recurso vazio', $log_context);
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "O ID do maquinário não pode ser vazio."]);
        exit();
    }
    
    // Agora a busca é única e direta, sem a necessidade de lógicas condicionais
    $query_ids = "SELECT empresa_id FROM empresa_recursos WHERE recurso_id = :recurso_id";
    $stmt_ids = $db->prepare($query_ids);
    $stmt_ids->bindParam(':recurso_id', $recurso_id, PDO::PARAM_INT); // Bind com PDO::PARAM_INT, pois é um ID
    $stmt_ids->execute();
    
    $empresa_ids = $stmt_ids->fetchAll(PDO::FETCH_COLUMN);

    if (empty($empresa_ids)) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Nenhuma empresa encontrada com este maquinário.", "data" => []]);
        exit();
    }

    $log_context['resultado_busca'] = 'Sucesso';
    $logger->info('Busca por maquinário bem-sucedida', $log_context);

    $empresas_encontradas = [];
    foreach ($empresa_ids as $empresa_id) {
        // 1. Buscar dados principais da empresa
        $query_empresa = "SELECT * FROM empresas WHERE id = :id";
        $stmt_empresa = $db->prepare($query_empresa);
        $stmt_empresa->bindParam(':id', $empresa_id, PDO::PARAM_INT);
        $stmt_empresa->execute();
        $empresa = $stmt_empresa->fetch(PDO::FETCH_ASSOC);

        if ($empresa) {
            // 2. Buscar dados relacionados
            $query_responsaveis = "SELECT nome, email FROM responsaveis_tecnicos WHERE empresa_id = :id";
            $stmt_responsaveis = $db->prepare($query_responsaveis);
            $stmt_responsaveis->bindParam(':id', $empresa_id, PDO::PARAM_INT);
            $stmt_responsaveis->execute();
            $empresa['responsaveis_tecnicos'] = $stmt_responsaveis->fetchAll(PDO::FETCH_ASSOC);

            $query_contatos = "SELECT * FROM contatos_na_empresa WHERE empresas_id = :id";
            $stmt_contatos = $db->prepare($query_contatos);
            $stmt_contatos->bindParam(':id', $empresa_id, PDO::PARAM_INT);
            $stmt_contatos->execute();
            $empresa['contatos'] = $stmt_contatos->fetchAll(PDO::FETCH_ASSOC);

            $empresas_encontradas[] = $empresa;
        }
    }

    http_response_code(200);
    echo json_encode(["success" => true, "data" => $empresas_encontradas]);

} catch (PDOException $e) {
    $logger->error('Erro na busca por maquinário', [
        'termo_busca' => $recurso_id,
        'id' => $_SESSION['id_usuario'] ?? 'N/A',
        'usuario' => $_SESSION['nome_usuario'] ?? 'N/A',
        'erro' => $e->getMessage()
    ]);
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro no banco de dados: " . $e->getMessage()]);
    exit();
}