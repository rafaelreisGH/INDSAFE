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

try {
    // Total de Empresas
    $query_total = "SELECT COUNT(id) as total FROM empresas";
    $stmt_total = $db->prepare($query_total);
    $stmt_total->execute();
    $total_empresas = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];

    // Total de Empresas que possuem PSCIP
    $query_pscip = "SELECT COUNT(id) as total FROM empresas WHERE possui_pscip = 'Possui'";
    $stmt_pscip = $db->prepare($query_pscip);
    $stmt_pscip->execute();
    $total_com_pscip = $stmt_pscip->fetch(PDO::FETCH_ASSOC)['total'];

    // Total de Empresas que não possuem PSCIP
    $query_no_pscip = "SELECT COUNT(id) as total FROM empresas WHERE possui_pscip = 'Não possui'";
    $stmt_no_pscip = $db->prepare($query_no_pscip);
    $stmt_no_pscip->execute();
    $total_sem_pscip = $stmt_no_pscip->fetch(PDO::FETCH_ASSOC)['total'];

    // Total de Empresas que prestam apoio
    $query_apoio = "SELECT COUNT(id) as total FROM apoio_cbm WHERE presta_apoio_ocorrencias = 1";
    $stmt_apoio = $db->prepare($query_apoio);
    $stmt_apoio->execute();
    $total_com_apoio = $stmt_apoio->fetch(PDO::FETCH_ASSOC)['total'];

    $stats = [
        "total_geral" => (int) $total_empresas,
        "total_com_pscip" => (int) $total_com_pscip,
        "total_sem_pscip" => (int) $total_sem_pscip,
        "total_com_apoio" => (int) $total_com_apoio,
        "zerado" => 0 // Apenas para preencher o último card
    ];

    http_response_code(200);
    echo json_encode($stats);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["message" => "Erro ao buscar estatísticas.", "error" => $e->getMessage()]);
}
?>