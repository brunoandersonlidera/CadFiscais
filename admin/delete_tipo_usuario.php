<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter dados JSON
$input = json_decode(file_get_contents('php://input'), true);
$tipo_id = isset($input['id']) ? (int)$input['id'] : 0;

if (!$tipo_id) {
    echo json_encode(['success' => false, 'message' => 'ID do tipo de usuário não fornecido']);
    exit;
}

try {
    $db = getDB();
    
    // Verificar se o tipo existe
    $stmt = $db->prepare("SELECT nome FROM tipos_usuario WHERE id = ?");
    $stmt->execute([$tipo_id]);
    $tipo = $stmt->fetch();
    
    if (!$tipo) {
        echo json_encode(['success' => false, 'message' => 'Tipo de usuário não encontrado']);
        exit;
    }
    
    // Verificar se há usuários usando este tipo
    $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario_id = ?");
    $stmt->execute([$tipo_id]);
    $usuarios_count = $stmt->fetchColumn();
    
    if ($usuarios_count > 0) {
        echo json_encode([
            'success' => false, 
            'message' => "Não é possível excluir este tipo de usuário. Existem $usuarios_count usuário(s) associado(s) a ele."
        ]);
        exit;
    }
    
    // Verificar se é um tipo padrão (não permitir exclusão)
    $tipos_protegidos = [1, 2, 3]; // IDs dos tipos padrão (Administrador, Colaborador, Coordenador)
    if (in_array($tipo_id, $tipos_protegidos)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Não é possível excluir tipos de usuário padrão do sistema'
        ]);
        exit;
    }
    
    // Excluir tipo de usuário
    $stmt = $db->prepare("DELETE FROM tipos_usuario WHERE id = ?");
    $stmt->execute([$tipo_id]);
    
    logActivity("Tipo de usuário excluído: {$tipo['nome']} (ID: $tipo_id)", 'INFO');
    
    echo json_encode([
        'success' => true, 
        'message' => 'Tipo de usuário excluído com sucesso!'
    ]);
    
} catch (Exception $e) {
    logActivity('Erro ao excluir tipo de usuário: ' . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?> 