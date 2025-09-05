<?php
// api/gerenciar_permissoes_api.php
session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

require_once '../ConexaoDB/conexao.php';
$conn = Conexao::getConexao();

// Verificação de permissão
if (!isset($_SESSION['permissoes']) || !in_array('gerenciar_perfis', $_SESSION['permissoes'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

$response = ['success' => false, 'message' => 'Ação inválida.'];
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'listar_perfis':
            $stmt = $conn->prepare("
                SELECT 
                    p.id, 
                    p.nome,
                    GROUP_CONCAT(pe.nome ORDER BY pe.nome ASC SEPARATOR ', ') AS permissoes
                FROM perfis p
                LEFT JOIN perfil_permissoes pp ON p.id = pp.perfil_id
                LEFT JOIN permissoes pe ON pp.permissao_id = pe.id
                GROUP BY p.id, p.nome
                ORDER BY p.nome
            ");
            $stmt->execute();
            $perfis = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = ['success' => true, 'perfis' => $perfis];
            http_response_code(200);
            break;

        case 'listar_permissoes_perfil':
            $id_perfil = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            if (!$id_perfil) {
                http_response_code(400);
                $response['message'] = 'ID do perfil não fornecido.';
                break;
            }

            // Todas
            $stmt_todas = $conn->prepare("SELECT id, nome FROM permissoes ORDER BY nome");
            $stmt_todas->execute();
            $todas_permissoes = $stmt_todas->fetchAll(PDO::FETCH_ASSOC);

            // Do perfil
            $stmt_perfil = $conn->prepare("
                SELECT p.id, p.nome 
                FROM permissoes p
                JOIN perfil_permissoes pp ON p.id = pp.permissao_id
                WHERE pp.perfil_id = :id_perfil
                ORDER BY p.nome
            ");
            $stmt_perfil->bindParam(':id_perfil', $id_perfil, PDO::PARAM_INT);
            $stmt_perfil->execute();
            $permissoes_do_perfil = $stmt_perfil->fetchAll(PDO::FETCH_ASSOC);

            $response = [
                'success' => true,
                'todas_permissoes' => $todas_permissoes,
                'permissoes_do_perfil' => $permissoes_do_perfil
            ];
            http_response_code(200);
            break;

        case 'salvar_permissoes':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                $response['message'] = 'Método de requisição inválido.';
                break;
            }

            // Valida CSRF (mesmo input usado no form do modal)
            $csrf = $_POST['csrf_token'] ?? '';
            if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
                http_response_code(403);
                $response['message'] = 'Token CSRF inválido.';
                break;
            }

            $id_perfil = filter_input(INPUT_POST, 'id_perfil', FILTER_SANITIZE_NUMBER_INT);
            $permissoes_json = $_POST['permissoes'] ?? '[]';
            $permissoes_selecionadas = json_decode($permissoes_json, true);

            if (!$id_perfil || !is_array($permissoes_selecionadas)) {
                http_response_code(400);
                $response['message'] = 'Dados inválidos.';
                break;
            }

            $conn->beginTransaction();

            $stmt_delete = $conn->prepare("DELETE FROM perfil_permissoes WHERE perfil_id = :id_perfil");
            $stmt_delete->bindParam(':id_perfil', $id_perfil, PDO::PARAM_INT);
            $stmt_delete->execute();

            if (!empty($permissoes_selecionadas)) {
                $stmt_insert = $conn->prepare("INSERT INTO perfil_permissoes (perfil_id, permissao_id) VALUES (:id_perfil, :id_permissao)");
                foreach ($permissoes_selecionadas as $permissao_id) {
                    $pid = (int)$permissao_id;
                    $stmt_insert->bindParam(':id_perfil', $id_perfil, PDO::PARAM_INT);
                    $stmt_insert->bindParam(':id_permissao', $pid, PDO::PARAM_INT);
                    $stmt_insert->execute();
                }
            }

            $conn->commit();
            http_response_code(200);
            $response = ['success' => true, 'message' => 'Permissões atualizadas com sucesso!'];
            break;

        default:
            http_response_code(400);
            $response['message'] = 'Ação não reconhecida.';
            break;
    }
} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    $response = ['success' => false, 'message' => 'Erro ao processar: ' . $e->getMessage()];
}

echo json_encode($response);
exit;
