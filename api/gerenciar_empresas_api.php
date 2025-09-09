<?php
// api/gerenciar_empresas_api.php
session_start();
header('Content-Type: application/json; charset=UTF-8');

include_once '../ConexaoDB/conexao.php';
require_once __DIR__ . '/../Logger/LoggerFactory.php';

$logger = LoggerFactory::createLogger();
$db = null;

// Verifica se o usuário tem a permissão 'editar_empresa'
if (!isset($_SESSION['logado']) || !in_array('editar_empresa', $_SESSION['permissoes'])) {
    $logger->warning('Acesso negado à API de gerenciamento de empresas.', [
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

    if ($action === 'carregar_painel') {
        // Ação principal para carregar o painel da empresa
        $id_usuario = $_SESSION['id_usuario'];
        $empresa = null;
        $hasAuthorizedLink = false;

        // 1. Busca por vínculo ativo com JOIN para obter os dados da empresa
        $query_vinculo_ativo = "SELECT
                                    e.id,
                                    e.razao_social,
                                    e.cnpj,
                                    e.risco_edificacao,
                                    e.area_construida_m2,
                                    e.possui_pscip,
                                    e.email_contato
                                FROM empresas e
                                JOIN usuario_empresa_vinculo uev ON uev.empresa_id = e.id
                                WHERE uev.usuario_id = :id_usuario AND uev.status_vinculo = 1
                                LIMIT 1";

        $stmt_vinculo_ativo = $db->prepare($query_vinculo_ativo);
        $stmt_vinculo_ativo->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt_vinculo_ativo->execute();
        $empresa = $stmt_vinculo_ativo->fetch(PDO::FETCH_ASSOC);

        if ($empresa) {
            $hasAuthorizedLink = true;
            $logger->info('Vínculo ativo encontrado.', $log_context);
        } else {
            $logger->info('Nenhum vínculo ativo encontrado.', $log_context);
        }

        // 2. Busca por todas as vinculações (ativas, pendentes, rejeitadas)
        $query_vinculacoes = "SELECT
                                    uev.status_vinculo,
                                    uev.motivo_rejeicao,
                                    e.razao_social,
                                    e.cnpj
                                FROM usuario_empresa_vinculo uev
                                JOIN empresas e ON uev.empresa_id = e.id
                                WHERE uev.usuario_id = :id_usuario";

        $stmt_vinculacoes = $db->prepare($query_vinculacoes);
        $stmt_vinculacoes->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt_vinculacoes->execute();
        $vinculacoes = $stmt_vinculacoes->fetchAll(PDO::FETCH_ASSOC);
        
        // Retorna todos os dados em uma única resposta
        echo json_encode([
            'success' => true,
            'hasAuthorizedLink' => $hasAuthorizedLink,
            'empresa' => $empresa,
            'vinculacoes' => $vinculacoes
        ]);
        $logger->info('Dados do painel da empresa carregados com sucesso.', $log_context);
    
    } elseif ($action === 'salvar_empresa') {
        // Ação para salvar as alterações da empresa (código mantido)
        $cnpj = $_POST['cnpj'] ?? null;
        $razao_social = $_POST['razao_social'] ?? null;
        $risco_edificacao = $_POST['risco_edificacao'] ?? null;
        $area_construida_m2 = $_POST['area_construida_m2'] ?? null;
        $possui_pscip = $_POST['possui_pscip'] ?? null;
        $email_contato = $_POST['email_contato'] ?? null;

        // Validação básica dos dados
        if (!$cnpj || !$razao_social || !$risco_edificacao || !$area_construida_m2 || !isset($possui_pscip) || !$email_contato) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios.']);
            $logger->error('Campos obrigatórios ausentes na requisição.', $log_context);
            exit();
        }
        
        $query = "UPDATE empresas
                  SET razao_social = :razao_social,
                      risco_edificacao = :risco_edificacao,
                      area_construida_m2 = :area_construida_m2,
                      possui_pscip = :possui_pscip,
                      email_contato = :email_contato
                  WHERE cnpj = :cnpj";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':razao_social', $razao_social);
        $stmt->bindParam(':risco_edificacao', $risco_edificacao);
        $stmt->bindParam(':area_construida_m2', $area_construida_m2);
        $stmt->bindParam(':possui_pscip', $possui_pscip); // Envia o valor de texto diretamente
        $stmt->bindParam(':email_contato', $email_contato);
        $stmt->bindParam(':cnpj', $cnpj);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Dados da empresa atualizados com sucesso.']);
            $logger->info('Dados da empresa atualizados com sucesso.', $log_context);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar as alterações.']);
            $logger->error('Erro ao executar a atualização da empresa.', $log_context);
        }

    } else {
        $logger->warning('Ação não especificada na API de gerenciamento de empresas.', $base_log_context);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação não especificada.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados.']);
    $logger->critical('Erro fatal no banco de dados da API.', [
        'erro' => $e->getMessage()
    ]);
}
?>