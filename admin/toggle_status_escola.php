<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$db = getDB();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $escola_id = isset($input['escola_id']) ? (int)$input['escola_id'] : 0;
    
    if (!$escola_id) {
        throw new Exception('ID da escola não informado');
    }
    
    // Buscar escola atual
    $sql = "SELECT id, nome, status FROM escolas WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$escola_id]);
    $escola = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$escola) {
        throw new Exception('Escola não encontrada');
    }
    
    // Alternar status
    $novo_status = $escola['status'] == 'ativo' ? 'inativo' : 'ativo';
    
    $sql = "UPDATE escolas SET status = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$novo_status, $escola_id]);
    
    $acao = $novo_status == 'ativo' ? 'ativada' : 'desativada';
    logActivity("Escola {$acao}: {$escola['nome']}", 'INFO');
    
    echo json_encode([
        'success' => true,
        'message' => "Escola {$acao} com sucesso!",
        'novo_status' => $novo_status
    ]);
    
} catch (Exception $e) {
    logActivity('Erro ao alterar status da escola: ' . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?> 