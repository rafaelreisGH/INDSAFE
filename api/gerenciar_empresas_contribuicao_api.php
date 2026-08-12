<?php
// api/gerenciar_empresas_contribuicao_api.php
session_start();
header('Content-Type: application/json; charset=UTF-8');

include_once '../ConexaoDB/conexao.php';
require_once __DIR__ . '/../Logger/LoggerFactory.php';

$logger = LoggerFactory::createLogger();
$db = null;

// Verifica se o usuário tem a permissão 'contribuir_empresa' e está vinculado a uma empresa
if (!isset($_SESSION['logado']) || !in_array('contribuir_empresa', $_SESSION['permissoes']) || !isset($_SESSION['empresa_id'])) {
    $logger->warning('Acesso negado à API de contribuicao de empresas', [
        'id' => $_SESSION['id_usuario'] ?? 'N/A',
        'usuario' => $_SESSION['nome_usuario'] ?? 'N/A',
        'empresa_id' => $_SESSION['empresa_id'] ?? 'N/A'
    ]);
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

$base_log_context = [
    'id_usuario_logado' => $_SESSION['id_usuario'] ?? 'N/A',
    'usuario_logado' => $_SESSION['nome_usuario'] ?? 'N/A',
    'empresa_id' => $_SESSION['empresa_id'] ?? 'N/A'
];

try {
    $db = Conexao::getConexao();
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    $log_context = array_merge($base_log_context, ['action' => $action]);

    if ($action === 'get_contribuicoes') {
        $empresa_id = $_SESSION['empresa_id'];
        $query = "SELECT id, sugestao_contribuicao FROM contribuicoes WHERE empresa_id = :empresa_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);
        $stmt->execute();
        $contribuicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'contribuicoes' => $contribuicoes]);
        $logger->info('Contribuições da empresa carregadas com sucesso.', $log_context);

    } elseif ($action === 'salvar_contribuicao') {
        $empresa_id = $_SESSION['empresa_id'];
        $sugestao_contribuicao = $_POST['sugestao_contribuicao'] ?? '';
        $id = $_POST['id'] ?? null;

        if (empty($sugestao_contribuicao)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'A sugestão de contribuição não pode ser vazia.']);
            $logger->warning('Tentativa de salvar contribuição vazia.', $log_context);
            exit();
        }

        if ($id) {
            // Edita a contribuição existente
            $query = "UPDATE contribuicoes SET sugestao_contribuicao = :sugestao_contribuicao WHERE id = :id AND empresa_id = :empresa_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':sugestao_contribuicao', $sugestao_contribuicao, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Contribuição atualizada com sucesso.']);
                $logger->info('Contribuição de empresa atualizada com sucesso.', $log_context);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar a contribuição.']);
                $logger->error('Erro ao executar a atualização da contribuição.', $log_context);
            }
        } else {
            // Insere uma nova contribuição
            $query = "INSERT INTO contribuicoes (empresa_id, sugestao_contribuicao) VALUES (:empresa_id, :sugestao_contribuicao)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);
            $stmt->bindParam(':sugestao_contribuicao', $sugestao_contribuicao, PDO::PARAM_STR);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Contribuição salva com sucesso.']);
                $logger->info('Nova contribuição de empresa salva com sucesso.', $log_context);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao salvar a contribuição.']);
                $logger->error('Erro ao executar a inserção da contribuição.', $log_context);
            }
        }

    } elseif ($action === 'delete_contribuicao') {
        $id = $_POST['id'] ?? null;
        $empresa_id = $_SESSION['empresa_id'];

        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID da contribuição não especificado.']);
            $logger->warning('Tentativa de excluir contribuição sem ID.', $log_context);
            exit();
        }

        $query = "DELETE FROM contribuicoes WHERE id = :id AND empresa_id = :empresa_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Contribuição excluída com sucesso.']);
                $logger->info('Contribuição de empresa excluída com sucesso.', $log_context);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Contribuição não encontrada ou não pertence a esta empresa.']);
                $logger->warning('Tentativa de excluir contribuição não pertencente à empresa.', $log_context);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir a contribuição.']);
            $logger->error('Erro ao executar a exclusão da contribuição.', $log_context);
        }

    } else {
        $logger->warning('Ação não especificada na API de contribuição de empresas.', $base_log_context);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação não especificada.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    $logger->error('Erro de banco de dados na API de contribuição de empresas: ' . $e->getMessage(), $base_log_context);
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados.']);
} catch (Exception $e) {
    http_response_code(500);
    $logger->critical('Erro inesperado na API de contribuição de empresas: ' . $e->getMessage(), $base_log_context);
    echo json_encode(['success' => false, 'message' => 'Erro inesperado.']);
}
