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
    $sala_id = isset($input['sala_id']) ? (int)$input['sala_id'] : 0;
    
    if (!$sala_id) {
        throw new Exception('ID da sala não informado');
    }
    
    // Buscar sala atual
    $stmt = $db->prepare("SELECT id, nome, status FROM salas WHERE id = ?");
    $stmt->execute([$sala_id]);
    $sala = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sala) {
        throw new Exception('Sala não encontrada');
    }
    
    // Alternar status
    $novo_status = $sala['status'] == 'ativo' ? 'inativo' : 'ativo';
    
    $stmt = $db->prepare("UPDATE salas SET status = ? WHERE id = ?");
    $stmt->execute([$novo_status, $sala_id]);
    
    logActivity("Status da sala alterado: {$sala['nome']} - {$novo_status}", 'INFO');
    
    echo json_encode([
        'success' => true,
        'message' => 'Status da sala alterado com sucesso!',
        'novo_status' => $novo_status
    ]);
    
} catch (Exception $e) {
    logActivity('Erro ao alterar status da sala: ' . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?> 