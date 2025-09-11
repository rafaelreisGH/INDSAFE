<?php
// /api/gerenciar_recursos_api.php
session_start();
header('Content-Type: application/json; charset=UTF-8');

include_once '../ConexaoDB/conexao.php';
require_once __DIR__ . '/../Logger/LoggerFactory.php';

$logger = LoggerFactory::createLogger();
$db = null;

if (!isset($_SESSION['logado']) || !in_array('editar_empresa', $_SESSION['permissoes'])) {
    http_response_code(403);
    $logger->warning('Acesso negado à API de gerenciamento de recursos.', ['id' => $_SESSION['id_usuario'] ?? 'N/A']);
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

    if ($action === 'carregar_painel_recursos') {
        $id_usuario = $_SESSION['id_usuario'];
        $empresa = null;
        
        // 1. Busca a empresa vinculada
        $stmt_vinculo = $db->prepare(
            "SELECT e.id, e.razao_social, e.cnpj FROM empresas e
             JOIN usuario_empresa_vinculo uev ON uev.empresa_id = e.id
             WHERE uev.usuario_id = :id_usuario AND uev.status_vinculo = 1 LIMIT 1"
        );
        $stmt_vinculo->execute([':id_usuario' => $id_usuario]);
        $empresa = $stmt_vinculo->fetch(PDO::FETCH_ASSOC);

        $recursos_essenciais = [];
        $maquinarios = [];
        
        if ($empresa) {
            // 2. Busca todos os recursos da empresa de uma vez
            $stmt_recursos = $db->prepare(
                "SELECT er.id, er.recurso_id, er.quantidade, er.unidade_medida_id, er.detalhes_adicionais, cr.descricao AS recurso_descricao, cum.descricao AS unidade_descricao
                 FROM empresa_recursos er
                 JOIN catalogo_recursos cr ON er.recurso_id = cr.id
                 LEFT JOIN catalogo_unidades_medida cum ON er.unidade_medida_id = cum.id
                 WHERE er.empresa_id = :empresa_id ORDER BY cr.id"
            );
            $stmt_recursos->execute([':empresa_id' => $empresa['id']]);
            $todos_recursos = $stmt_recursos->fetchAll(PDO::FETCH_ASSOC);

            // 3. Separa os resultados nas categorias desejadas
            foreach ($todos_recursos as $recurso) {
                if ($recurso['recurso_id'] == 1 || $recurso['recurso_id'] == 2) {
                    $recursos_essenciais[] = $recurso;
                } else {
                    $maquinarios[] = $recurso;
                }
            }
        }
        
        // 4. Busca os catálogos para preencher os modais de edição
        $catalogo_recursos = $db->query("SELECT id, descricao FROM catalogo_recursos ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        $catalogo_unidades = $db->query("SELECT id, descricao FROM catalogo_unidades_medida ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'empresa' => $empresa,
            'recursos_essenciais' => $recursos_essenciais,
            'maquinarios' => $maquinarios,
            'catalogos' => [
                'recursos' => $catalogo_recursos,
                'unidades' => $catalogo_unidades
            ]
        ]);
        $logger->info('Dados do painel de recursos carregados com sucesso.', $log_context);

    } elseif ($action === 'salvar_recursos') {
        $empresa_id = $_POST['empresa_id'] ?? null;
        $recursos_data = json_decode($_POST['recursos'] ?? '[]', true);

        if (!$empresa_id || !is_array($recursos_data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            exit();
        }

        $db->beginTransaction();
        try {
            $ids_existentes = $db->prepare("SELECT id FROM empresa_recursos WHERE empresa_id = :empresa_id");
            $ids_existentes->execute([':empresa_id' => $empresa_id]);
            $ids_no_db = $ids_existentes->fetchAll(PDO::FETCH_COLUMN);
            $ids_recebidos = [];

            $stmt_update = $db->prepare("UPDATE empresa_recursos SET quantidade = :qtd, unidade_medida_id = :uid, detalhes_adicionais = :det WHERE id = :id AND empresa_id = :eid");
            $stmt_insert = $db->prepare("INSERT INTO empresa_recursos (empresa_id, recurso_id, quantidade, unidade_medida_id, detalhes_adicionais) VALUES (:eid, :rid, :qtd, :uid, :det)");

            foreach ($recursos_data as $recurso) {
                $id = $recurso['id'] ?? null;
                $unidade_id = empty($recurso['unidade_medida_id']) ? null : $recurso['unidade_medida_id'];

                if ($id && in_array($id, $ids_no_db)) { // Atualiza
                    $ids_recebidos[] = $id;
                    $stmt_update->execute([':qtd' => $recurso['quantidade'], ':uid' => $unidade_id, ':det' => $recurso['detalhes_adicionais'], ':id' => $id, ':eid' => $empresa_id]);
                } else { // Insere
                    $stmt_insert->execute([':eid' => $empresa_id, ':rid' => $recurso['recurso_id'], ':qtd' => $recurso['quantidade'], ':uid' => $unidade_id, ':det' => $recurso['detalhes_adicionais']]);
                }
            }
            
            $ids_para_deletar = array_diff($ids_no_db, $ids_recebidos);
            if (!empty($ids_para_deletar)) {
                $placeholders = implode(',', array_fill(0, count($ids_para_deletar), '?'));
                $stmt_delete = $db->prepare("DELETE FROM empresa_recursos WHERE id IN ($placeholders) AND empresa_id = ?");
                $ids_para_deletar[] = $empresa_id;
                $stmt_delete->execute(array_values($ids_para_deletar));
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Dados salvos com sucesso!']);
            $logger->info('Recursos da empresa salvos.', $log_context);
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
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados.']);
    $logger->critical('Erro fatal na API de recursos.', ['erro' => $e->getMessage()]);
}
?>