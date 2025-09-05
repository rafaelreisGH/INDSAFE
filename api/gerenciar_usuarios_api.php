<?php
// api/gerenciar_usuarios_api.php
session_start();
header('Content-Type: application/json; charset=UTF-8');

include_once '../ConexaoDB/conexao.php';
require_once __DIR__ . '/../Logger/LoggerFactory.php';

$logger = LoggerFactory::createLogger();
$db = null;

// Verifica se o usuário tem a permissão 'gerenciar_usuarios'
if (!isset($_SESSION['logado']) || !in_array('gerenciar_usuarios', $_SESSION['permissoes'])) {
    $logger->warning('Acesso negado à API de gerenciamento de usuários', [
        'id' => $_SESSION['id_usuario'] ?? 'N/A',
        'usuario' => $_SESSION['nome_usuario'] ?? 'N/A'
    ]);
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

$base_log_context = [
    'id_usuario_logado' => $_SESSION['id_usuario'] ?? 'N/A',
    'usuario_logado' => $_SESSION['nome_usuario'] ?? 'N/A'
];

try {
    $db = Conexao::getConexao();

    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    $log_context = array_merge($base_log_context, ['action' => $action]);

    if ($action === 'listar') {
        // Parâmetros de paginação e filtro
        $limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT) ?? 20;
        $offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT) ?? 0;
        $filtro = filter_input(INPUT_GET, 'filtro', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

        // Contagem total de usuários (sem paginação, mas com filtro)
        $queryCount = "SELECT COUNT(*) FROM usuarios WHERE nome LIKE :filtro OR cpf LIKE :filtro OR email LIKE :filtro";
        $stmtCount = $db->prepare($queryCount);
        $stmtCount->bindValue(':filtro', '%' . $filtro . '%');
        $stmtCount->execute();
        $totalUsuarios = $stmtCount->fetchColumn();

        // Consulta com paginação e filtro
        $query = "
            SELECT 
                u.id, 
                u.nome, 
                u.email, 
                u.cpf, 
                u.ativo,
                p.id AS perfil_id,
                p.nome AS perfil_nome
            FROM usuarios AS u
            LEFT JOIN usuario_perfis AS up ON u.id = up.usuario_id
            LEFT JOIN perfis AS p ON up.perfil_id = p.id
            WHERE u.nome LIKE :filtro OR u.cpf LIKE :filtro OR u.email LIKE :filtro
            ORDER BY u.id ASC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':filtro', '%' . $filtro . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'usuarios' => $usuarios,
            'total_usuarios' => $totalUsuarios
        ]);

        $logger->info('Listagem de usuários bem-sucedida', $log_context);
    } elseif ($action === 'listar_perfis') {
        $query = "SELECT id, nome FROM perfis ORDER BY nome ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $perfis = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'perfis' => $perfis]);

        $logger->info('Listagem de perfis para edição bem-sucedida', $log_context);
    } elseif ($action === 'toggle_status') {
        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        $status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_NUMBER_INT);
        $log_context['target_user_id'] = $id;
        $log_context['status_novo'] = $status;

        if (!$id || !is_numeric($status)) {
            $logger->warning('Falha ao alternar status do usuário - parâmetros inválidos', $log_context);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
            exit();
        }

        $query = "UPDATE usuarios SET ativo = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $message = $status == 1 ? 'Usuário ativado com sucesso.' : 'Usuário desativado com sucesso.';
        echo json_encode(['success' => true, 'message' => $message]);

        $logger->info('Status de usuário alterado com sucesso', $log_context);
    } elseif ($action === 'editar_perfil') {
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $perfil_id = filter_input(INPUT_POST, 'perfil_id', FILTER_SANITIZE_NUMBER_INT);
        $log_context['target_user_id'] = $id;
        $log_context['novo_perfil_id'] = $perfil_id;

        if (!$id || !$perfil_id) {
            $logger->warning('Falha ao editar perfil de usuário - parâmetros inválidos', $log_context);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
            exit();
        }

        // Deleta o perfil atual do usuário e insere o novo
        $db->beginTransaction();

        $query_delete = "DELETE FROM usuario_perfis WHERE usuario_id = :id";
        $stmt_delete = $db->prepare($query_delete);
        $stmt_delete->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_delete->execute();

        $query_insert = "INSERT INTO usuario_perfis (usuario_id, perfil_id) VALUES (:usuario_id, :perfil_id)";
        $stmt_insert = $db->prepare($query_insert);
        $stmt_insert->bindParam(':usuario_id', $id, PDO::PARAM_INT);
        $stmt_insert->bindParam(':perfil_id', $perfil_id, PDO::PARAM_INT);
        $stmt_insert->execute();

        $db->commit();

        echo json_encode(['success' => true, 'message' => 'Perfil do usuário atualizado com sucesso.']);

        $logger->info('Perfil de usuário editado com sucesso', $log_context);
    } else {
        $logger->warning('Ação não especificada na API de gerenciamento de usuários', $base_log_context);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação não especificada.']);
    }
} catch (PDOException $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);

    $log_context = array_merge($base_log_context, ['erro' => $e->getMessage()]);
    $logger->error('Erro PDO na API de gerenciamento de usuários', $log_context);
}
