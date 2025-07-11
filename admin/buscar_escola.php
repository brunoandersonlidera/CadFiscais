<?php
require_once '../config.php';

// Verificar se é admin - comentado temporariamente para teste
/*
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}
*/

$db = getDB();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $escola_id = isset($input['escola_id']) ? (int)$input['escola_id'] : 0;
    
    // Log para debug
    error_log("Buscar escola - ID recebido: " . $escola_id);
    
    if (!$escola_id) {
        throw new Exception('ID da escola não informado');
    }
    
    $sql = "SELECT * FROM escolas WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$escola_id]);
    $escola = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Log para debug
    error_log("Escola encontrada: " . ($escola ? 'sim' : 'não'));
    
    if (!$escola) {
        throw new Exception('Escola não encontrada');
    }
    
    echo json_encode([
        'success' => true,
        'escola' => $escola
    ]);
    
} catch (Exception $e) {
    error_log("Erro ao buscar escola: " . $e->getMessage());
    logActivity('Erro ao buscar escola: ' . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?> 