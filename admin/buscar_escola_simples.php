<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $escola_id = isset($input['escola_id']) ? (int)$input['escola_id'] : 0;
    
    if (!$escola_id) {
        throw new Exception('ID da escola não informado');
    }
    
    $db = getDB();
    $sql = "SELECT * FROM escolas WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$escola_id]);
    $escola = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$escola) {
        throw new Exception('Escola não encontrada');
    }
    
    echo json_encode([
        'success' => true,
        'escola' => $escola
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?> 