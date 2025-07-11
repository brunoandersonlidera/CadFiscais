<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Receber dados JSON
$input = json_decode(file_get_contents('php://input'), true);
$pagamento_id = isset($input['pagamento_id']) ? (int)$input['pagamento_id'] : 0;

if (!$pagamento_id) {
    echo json_encode(['success' => false, 'message' => 'ID do pagamento não fornecido']);
    exit;
}

$db = getDB();

try {
    // Verificar se o pagamento existe e está pendente
    $stmt = $db->prepare("SELECT * FROM pagamentos WHERE id = ? AND status = 'pendente'");
    $stmt->execute([$pagamento_id]);
    $pagamento = $stmt->fetch();
    
    if (!$pagamento) {
        echo json_encode(['success' => false, 'message' => 'Pagamento não encontrado ou já foi processado']);
        exit;
    }
    
    // Atualizar status do pagamento
    $stmt = $db->prepare("
        UPDATE pagamentos 
        SET status = 'pago', 
            data_pagamento = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $resultado = $stmt->execute([$pagamento_id]);
    
    if ($resultado) {
        // Log da atividade
        $usuario = $_SESSION['usuario']['nome'] ?? 'Admin';
        logActivity("Pagamento ID $pagamento_id marcado como pago por $usuario", 'INFO');
        
        echo json_encode(['success' => true, 'message' => 'Pagamento marcado como pago com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar pagamento']);
    }
    
} catch (Exception $e) {
    logActivity('Erro ao marcar pagamento como pago: ' . $e->getMessage(), 'ERROR');
    echo json_encode(['success' => false, 'message' => 'Erro interno do sistema']);
}
?> 