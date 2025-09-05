<?php
// api/gerenciar_usuarios_api.php
session_start();
header('Content-Type: application/json; charset=UTF-8');

include_once '../ConexaoDB/conexao.php';

// Verifica se o usuário tem a permissão 'gerenciar_usuarios'
if (!isset($_SESSION['logado']) || !in_array('gerenciar_usuarios', $_SESSION['permissoes'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

try {
    $db = Conexao::getConexao();

    $action = $_GET['action'] ?? $_POST['action'] ?? null;

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
        
    } elseif ($action === 'listar_perfis') {
        $query = "SELECT id, nome FROM perfis ORDER BY nome ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $perfis = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'perfis' => $perfis]);
    } elseif ($action === 'toggle_status') {
        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        $status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_NUMBER_INT);

        if (!$id || !is_numeric($status)) {
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
    } elseif ($action === 'editar_perfil') {
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $perfil_id = filter_input(INPUT_POST, 'perfil_id', FILTER_SANITIZE_NUMBER_INT);

        if (!$id || !$perfil_id) {
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
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação não especificada.']);
    }
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
