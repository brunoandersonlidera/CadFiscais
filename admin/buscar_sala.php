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
    
    $sql = "SELECT * FROM salas WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$sala_id]);
    $sala = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sala) {
        throw new Exception('Sala não encontrada');
    }
    
    echo json_encode([
        'success' => true,
        'sala' => $sala
    ]);
    
} catch (Exception $e) {
    logActivity('Erro ao buscar sala: ' . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?> 