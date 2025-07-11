<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

$escola_id = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : 0;

if (!$escola_id) {
    echo json_encode([]);
    exit;
}

$db = getDB();

try {
    $stmt = $db->prepare("
        SELECT id, nome, capacidade, tipo
        FROM salas 
        WHERE escola_id = ? AND status = 'ativo'
        ORDER BY nome
    ");
    $stmt->execute([$escola_id]);
    $salas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($salas);
} catch (Exception $e) {
    logActivity('Erro ao buscar salas: ' . $e->getMessage(), 'ERROR');
    echo json_encode([]);
}
?> 