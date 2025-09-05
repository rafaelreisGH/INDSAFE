<?php
// api/gerenciar_permissoes_api.php
session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

require_once '../ConexaoDB/conexao.php';
require_once __DIR__ . '/../Logger/LoggerFactory.php';

$logger = LoggerFactory::createLogger();
$conn = null;

try {
    $conn = Conexao::getConexao();
} catch (PDOException $e) {
    // Log de erro de conexão com o banco de dados
    $logger->error('Erro de conexão com o banco de dados', [
        'id' => $_SESSION['id_usuario'] ?? 'N/A',
        'usuario' => $_SESSION['nome_usuario'] ?? 'N/A',
        'erro' => $e->getMessage()
    ]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao conectar ao banco de dados.']);
    exit;
}

// Verificação de permissão
if (!isset($_SESSION['permissoes']) || !in_array('gerenciar_perfis', $_SESSION['permissoes'])) {
    // Log de aviso para acesso negado
    $logger->warning('Tentativa de acesso negado à API de permissões', [
        'id' => $_SESSION['id_usuario'] ?? 'N/A',
        'usuario' => $_SESSION['nome_usuario'] ?? 'N/A'
    ]);
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

$response = ['success' => false, 'message' => 'Ação inválida.'];
$action = $_REQUEST['action'] ?? '';

$base_log_context = [
    'action' => $action,
    'id_usuario_logado' => $_SESSION['id_usuario'] ?? 'N/A',
    'usuario_logado' => $_SESSION['nome_usuario'] ?? 'N/A'
];

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

            // Log de sucesso para listagem (não é uma alteração, mas uma ação bem-sucedida)
            $logger->info('Listagem de perfis bem-sucedida', $base_log_context);
            break;

        case 'listar_permissoes_perfil':
            $id_perfil = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            $log_context = array_merge($base_log_context, ['id_perfil' => $id_perfil]);

            if (!$id_perfil) {
                http_response_code(400);
                $response['message'] = 'ID do perfil não fornecido.';
                $logger->warning('Busca de permissoes por perfil falhou - ID não fornecido', $log_context);
                break;
            }

            $stmt_todas = $conn->prepare("SELECT id, nome FROM permissoes ORDER BY nome");
            $stmt_todas->execute();
            $todas_permissoes = $stmt_todas->fetchAll(PDO::FETCH_ASSOC);

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

            // Log de sucesso para listagem de permissões
            $logger->info('Listagem de permissões por perfil bem-sucedida', $log_context);
            break;

        case 'salvar_permissoes':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                $response['message'] = 'Método de requisição inválido.';
                $logger->warning('Tentativa de salvar permissoes com método inválido', $base_log_context);
                break;
            }

            $csrf = $_POST['csrf_token'] ?? '';
            if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
                http_response_code(403);
                $response['message'] = 'Token CSRF inválido.';
                $logger->warning('Tentativa de salvar permissoes com token CSRF inválido', $base_log_context);
                break;
            }

            $id_perfil = filter_input(INPUT_POST, 'id_perfil', FILTER_SANITIZE_NUMBER_INT);
            $permissoes_json = $_POST['permissoes'] ?? '[]';
            $permissoes_selecionadas = json_decode($permissoes_json, true);
            $log_context = array_merge($base_log_context, ['id_perfil' => $id_perfil, 'permissoes' => $permissoes_selecionadas]);

            if (!$id_perfil || !is_array($permissoes_selecionadas)) {
                http_response_code(400);
                $response['message'] = 'Dados inválidos.';
                $logger->warning('Falha ao salvar permissoes - dados inválidos', $log_context);
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

            // Log de sucesso para a alteração
            $logger->info('Permissões de perfil salvas com sucesso', $log_context);
            break;

        default:
            http_response_code(400);
            $response['message'] = 'Ação não reconhecida.';
            $logger->warning('Ação de API não reconhecida', $base_log_context);
            break;
    }
} catch (PDOException $e) {
    if ($conn && $conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    $response = ['success' => false, 'message' => 'Erro ao processar: ' . $e->getMessage()];
    
    // Log de erro em caso de exceção no sistema
    $log_context = array_merge($base_log_context, ['erro' => $e->getMessage()]);
    $logger->error('Erro PDO ao processar API de permissões', $log_context);
}

echo json_encode($response);
exit;
