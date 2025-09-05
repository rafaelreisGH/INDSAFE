<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../ConexaoDB/conexao.php';

$database = new Conexao();
$db = $database->getConexao();

if (!$db) {
    http_response_code(500);
    echo json_encode(["message" => "Não foi possível conectar ao banco de dados."]);
    exit();
}

$empresa_id = isset($_GET['id']) && !empty($_GET['id']) ? (int)$_GET['id'] : null;

// Se um ID foi fornecido, busca os detalhes da empresa
if ($empresa_id) {
    try {
        // 1. Buscar dados principais da empresa
        $query = "SELECT * FROM empresas WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $empresa_id);
        $stmt->execute();
        $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$empresa) {
            http_response_code(404);
            echo json_encode(["message" => "Empresa não encontrada."]);
            exit();
        }

        // 2. Buscar dados relacionados em outras tabelas
        $empresa['responsaveis_tecnicos'] = fetchRelatedData($db, "SELECT * FROM responsaveis_tecnicos WHERE empresa_id = :id", $empresa_id);
        $empresa['contatos'] = fetchRelatedData($db, "SELECT * FROM contatos_na_empresa WHERE empresas_id = :id", $empresa_id);
        $empresa['recursos'] = fetchRelatedData($db, 
            "SELECT cr.descricao as recurso, er.quantidade, cum.descricao as unidade 
             FROM empresa_recursos er
             JOIN catalogo_recursos cr ON er.recurso_id = cr.id
             LEFT JOIN catalogo_unidades_medida cum ON er.unidade_medida_id = cum.id
             WHERE er.empresa_id = :id", 
            $empresa_id
        );
        // ... adicione aqui buscas para outras tabelas relacionadas se necessário (riscos, medidas, etc.)

        http_response_code(200);
        echo json_encode($empresa);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Erro ao buscar detalhes da empresa.", "error" => $e->getMessage()]);
    }
} else {
    // Se nenhum ID foi fornecido, lista todas as empresas
    try {
        $query = "SELECT id, razao_social, cnpj, risco_edificacao, possui_pscip FROM empresas ORDER BY razao_social ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $empresas_arr = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $empresas_arr[] = $row;
        }

        http_response_code(200);
        echo json_encode($empresas_arr);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Erro ao listar empresas.", "error" => $e->getMessage()]);
    }
}

// Função auxiliar para buscar dados relacionados
function fetchRelatedData($db, $query, $id) {
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>