<?php
// api/gerenciar_vinculos_api.php
session_start();
header('Content-Type: application/json');

// Inclui os arquivos necessários para a conexão e o logger.
require_once '../ConexaoDB/conexao.php';
require_once __DIR__ . '/../Logger/LoggerFactory.php';

$logger = LoggerFactory::createLogger();
$conn = Conexao::getConexao();

// Estrutura de resposta padrão.
$response = [
    'success' => false,
    'message' => ''
];

// Verifica se o usuário tem permissão de administrador para usar esta API.
if (!isset($_SESSION['logado']) || !in_array('gerenciar_vinculos', $_SESSION['permissoes'])) {
    $response['message'] = 'Acesso negado. Permissões insuficientes.';
    $logger->warning('Tentativa de acesso não autorizado à API de gerenciamento de vínculos.', ['session_id' => session_id()]);
    echo json_encode($response);
    exit();
}

try {
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'listar':
            // Endpoint para listar vínculos pendentes para o administrador.
            $limit = $_GET['limit'] ?? 20;
            $offset = $_GET['offset'] ?? 0;
            $filtro = $_GET['filtro'] ?? '';

            // Query para contar o total de registros.
            $queryCount = "SELECT COUNT(id) AS total FROM usuario_empresa_vinculo WHERE status_vinculo = 0";
            $stmtCount = $conn->prepare($queryCount);
            $stmtCount->execute();
            $totalResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $totalVinculos = $totalResult['total'];

            // Query para listar os vínculos com paginação e filtro.
            $query = "SELECT 
                        v.id, 
                        u.nome AS nome_usuario, 
                        u.email AS email_usuario, 
                        u.cpf AS cpf_usuario,
                        e.razao_social AS razao_social_empresa,
                        e.cnpj AS cnpj_empresa
                    FROM usuario_empresa_vinculo v
                    JOIN usuarios u ON v.usuario_id = u.id
                    JOIN empresas e ON v.empresa_id = e.id
                    WHERE v.status_vinculo = 0";
            
            // Adiciona a condição de filtro, se houver.
            if (!empty($filtro)) {
                $query .= " AND (u.nome LIKE :filtro OR u.email LIKE :filtro OR u.cpf LIKE :filtro OR e.razao_social LIKE :filtro OR e.cnpj LIKE :filtro)";
            }

            $query .= " LIMIT :limit OFFSET :offset";

            $stmt = $conn->prepare($query);
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

            if (!empty($filtro)) {
                $stmt->bindValue(':filtro', '%' . $filtro . '%', PDO::PARAM_STR);
            }

            $stmt->execute();
            $vinculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['vinculos'] = $vinculos;
            $response['total_vinculos'] = $totalVinculos;
            break;

        case 'aprovar_vinculo':
            // Endpoint para aprovar um vínculo.
            $id = $_GET['id'] ?? null;
            if (!$id) {
                $response['message'] = 'ID do vínculo não fornecido.';
                break;
            }
            $stmt = $conn->prepare("UPDATE vinculo_usuario_empresa SET status_vinculo = 1, motivo_rejeicao = NULL WHERE id = :id AND status_vinculo = 0");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $success = $stmt->execute();

            if ($success && $stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Vínculo aprovado com sucesso!';
                $logger->info('Vínculo aprovado.', ['vinculo_id' => $id, 'usuario_id' => $_SESSION['id_usuario']]);
            } else {
                $response['message'] = 'Vínculo não encontrado ou já processado.';
            }
            break;

        case 'rejeitar_vinculo':
            // Endpoint para rejeitar um vínculo com um motivo.
            $id = $_POST['id'] ?? null;
            $motivo = $_POST['motivo'] ?? null;

            if (!$id || !$motivo) {
                $response['message'] = 'ID do vínculo ou motivo não fornecido.';
                break;
            }

            $stmt = $conn->prepare("UPDATE vinculo_usuario_empresa SET status_vinculo = 2, motivo_rejeicao = :motivo WHERE id = :id AND status_vinculo = 0");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':motivo', $motivo, PDO::PARAM_STR);
            $success = $stmt->execute();

            if ($success && $stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Vínculo rejeitado com sucesso!';
                $logger->info('Vínculo rejeitado.', ['vinculo_id' => $id, 'motivo' => $motivo, 'usuario_id' => $_SESSION['id_usuario']]);
            } else {
                $response['message'] = 'Vínculo não encontrado ou já processado.';
            }
            break;

        case 'listar_por_usuario':
            // Endpoint para listar os vínculos de um usuário específico.
            $userId = $_GET['userId'] ?? null;
            if (!$userId) {
                $response['message'] = 'ID do usuário não fornecido.';
                break;
            }

            $query = "SELECT 
                        v.id, 
                        e.razao_social AS razao_social_empresa, 
                        e.cnpj AS cnpj_empresa,
                        v.status_vinculo,
                        v.motivo_rejeicao
                    FROM vinculo_usuario_empresa v
                    JOIN empresas e ON v.empresa_id = e.id
                    WHERE v.usuario_id = :userId";

            $stmt = $conn->prepare($query);
            $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $vinculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['vinculos'] = $vinculos;
            break;

        default:
            $response['message'] = 'Ação não reconhecida.';
            $logger->warning('Ação desconhecida na API de gerenciamento de vínculos.', ['action' => $action]);
            break;
    }
} catch (PDOException $e) {
    $response['message'] = 'Erro no banco de dados: ' . $e->getMessage();
    $logger->error('Erro no banco de dados na API de gerenciamento de vínculos.', ['erro' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}

echo json_encode($response);
