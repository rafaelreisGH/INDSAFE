<?php
// api/gerenciar_empresas_contatos_api.php
session_start();
header('Content-Type: application/json; charset=UTF-8');

include_once '../ConexaoDB/conexao.php';
require_once __DIR__ . '/../Logger/LoggerFactory.php';

$logger = LoggerFactory::createLogger();
$db = null;

if (!isset($_SESSION['logado']) || !in_array('editar_empresa', $_SESSION['permissoes'])) {
    http_response_code(403);
    $logger->warning('Acesso negado à API de gerenciamento de contatos.', ['id' => $_SESSION['id_usuario'] ?? 'N/A']);
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

    if ($action === 'carregar_painel_contatos') {
        $id_usuario = $_SESSION['id_usuario'];
        $empresa = null;
        
        // 1. Busca a empresa vinculada ao usuário
        $stmt_vinculo = $db->prepare(
            "SELECT e.id, e.razao_social, e.cnpj FROM empresas e
             JOIN usuario_empresa_vinculo uev ON uev.empresa_id = e.id
             WHERE uev.usuario_id = :id_usuario AND uev.status_vinculo = 1 LIMIT 1"
        );
        $stmt_vinculo->execute([':id_usuario' => $id_usuario]);
        $empresa = $stmt_vinculo->fetch(PDO::FETCH_ASSOC);

        $contatos = [];
        $responsaveis = [];
        
        if ($empresa) {
            // 2. Busca contatos da empresa
            $stmt_contatos = $db->prepare("SELECT * FROM contatos_na_empresa WHERE empresas_id = :empresa_id ORDER BY Nome");
            $stmt_contatos->execute([':empresa_id' => $empresa['id']]);
            $contatos = $stmt_contatos->fetchAll(PDO::FETCH_ASSOC);

            // 3. Busca responsáveis técnicos da empresa
            $stmt_responsaveis = $db->prepare("SELECT * FROM responsaveis_tecnicos WHERE empresa_id = :empresa_id ORDER BY nome");
            $stmt_responsaveis->execute([':empresa_id' => $empresa['id']]);
            $responsaveis = $stmt_responsaveis->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode([
            'success' => true,
            'empresa' => $empresa,
            'contatos' => $contatos,
            'responsaveis' => $responsaveis
        ]);
        $logger->info('Dados do painel de contatos carregados com sucesso.', $log_context);

    } elseif ($action === 'salvar_contatos') {
        $empresa_id = $_POST['empresa_id'] ?? null;
        $dados_recebidos = json_decode($_POST['dados'] ?? '[]', true);

        if (!$empresa_id || !is_array($dados_recebidos)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            exit();
        }

        $db->beginTransaction();
        try {
            // IDs de contatos existentes no banco
            $ids_existentes = $db->prepare("SELECT id FROM contatos_na_empresa WHERE empresas_id = :empresa_id");
            $ids_existentes->execute([':empresa_id' => $empresa_id]);
            $ids_no_db = $ids_existentes->fetchAll(PDO::FETCH_COLUMN);
            $ids_recebidos = [];

            $stmt_update = $db->prepare("UPDATE contatos_na_empresa SET Nome = :nome, telefone = :tel, email = :email, setor_funcao = :setor WHERE id = :id AND empresas_id = :eid");
            $stmt_insert = $db->prepare("INSERT INTO contatos_na_empresa (empresas_id, Nome, telefone, email, setor_funcao) VALUES (:eid, :nome, :tel, :email, :setor)");

            foreach ($dados_recebidos as $dado) {
                $id = $dado['id'] ?? null;
                if ($id && in_array($id, $ids_no_db)) { // Atualiza
                    $ids_recebidos[] = $id;
                    $stmt_update->execute([
                        ':nome' => $dado['Nome'],
                        ':tel' => $dado['telefone'],
                        ':email' => $dado['email'],
                        ':setor' => $dado['setor_funcao'],
                        ':id' => $id,
                        ':eid' => $empresa_id
                    ]);
                } else { // Insere
                    $stmt_insert->execute([
                        ':eid' => $empresa_id,
                        ':nome' => $dado['Nome'],
                        ':tel' => $dado['telefone'],
                        ':email' => $dado['email'],
                        ':setor' => $dado['setor_funcao']
                    ]);
                }
            }
            
            // Deleta contatos que não foram enviados no formulário
            $ids_para_deletar = array_diff($ids_no_db, $ids_recebidos);
            if (!empty($ids_para_deletar)) {
                $placeholders = implode(',', array_fill(0, count($ids_para_deletar), '?'));
                $stmt_delete = $db->prepare("DELETE FROM contatos_na_empresa WHERE id IN ($placeholders) AND empresas_id = ?");
                $ids_para_deletar[] = $empresa_id;
                $stmt_delete->execute(array_values($ids_para_deletar));
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Contatos salvos com sucesso!']);
            $logger->info('Contatos da empresa salvos.', $log_context);
        } catch (PDOException $e) {
            $db->rollBack();
            throw $e;
        }

    } elseif ($action === 'salvar_responsaveis') {
        $empresa_id = $_POST['empresa_id'] ?? null;
        $dados_recebidos = json_decode($_POST['dados'] ?? '[]', true);
        
        if (!$empresa_id || !is_array($dados_recebidos)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            exit();
        }

        $db->beginTransaction();
        try {
            // IDs de responsáveis existentes no banco
            $ids_existentes = $db->prepare("SELECT id FROM responsaveis_tecnicos WHERE empresa_id = :empresa_id");
            $ids_existentes->execute([':empresa_id' => $empresa_id]);
            $ids_no_db = $ids_existentes->fetchAll(PDO::FETCH_COLUMN);
            $ids_recebidos = [];

            $stmt_update = $db->prepare("UPDATE responsaveis_tecnicos SET nome = :nome, email = :email, crea_cau = :crea_cau, telefone = :tel WHERE id = :id AND empresa_id = :eid");
            $stmt_insert = $db->prepare("INSERT INTO responsaveis_tecnicos (empresa_id, nome, email, crea_cau, telefone) VALUES (:eid, :nome, :email, :crea_cau, :tel)");

            foreach ($dados_recebidos as $dado) {
                $id = $dado['id'] ?? null;
                if ($id && in_array($id, $ids_no_db)) { // Atualiza
                    $ids_recebidos[] = $id;
                    $stmt_update->execute([
                        ':nome' => $dado['nome'],
                        ':email' => $dado['email'],
                        ':crea_cau' => $dado['crea_cau'],
                        ':tel' => $dado['telefone'],
                        ':id' => $id,
                        ':eid' => $empresa_id
                    ]);
                } else { // Insere
                    $stmt_insert->execute([
                        ':eid' => $empresa_id,
                        ':nome' => $dado['nome'],
                        ':email' => $dado['email'],
                        ':crea_cau' => $dado['crea_cau'],
                        ':tel' => $dado['telefone']
                    ]);
                }
            }
            
            // Deleta responsáveis que não foram enviados no formulário
            $ids_para_deletar = array_diff($ids_no_db, $ids_recebidos);
            if (!empty($ids_para_deletar)) {
                $placeholders = implode(',', array_fill(0, count($ids_para_deletar), '?'));
                $stmt_delete = $db->prepare("DELETE FROM responsaveis_tecnicos WHERE id IN ($placeholders) AND empresa_id = ?");
                $ids_para_deletar[] = $empresa_id;
                $stmt_delete->execute(array_values($ids_para_deletar));
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Responsáveis técnicos salvos com sucesso!']);
            $logger->info('Responsáveis técnicos da empresa salvos.', $log_context);
        } catch (PDOException $e) {
            $db->rollBack();
            throw $e;
        }

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação não especificada.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados ou processamento.']);
    $logger->critical('Erro fatal na API de contatos.', ['erro' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
