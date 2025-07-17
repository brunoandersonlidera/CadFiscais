<?php
require_once '../config.php';

// Verificar se tem permissão para pagamentos
if (!isLoggedIn() || !temPermissaoPagamentos()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sem permissão']);
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    // Obter dados do POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['fiscal_id']) || !isset($input['concurso_id']) || !isset($input['valor'])) {
        throw new Exception('Dados incompletos');
    }
    
    $fiscal_id = (int)$input['fiscal_id'];
    $concurso_id = (int)$input['concurso_id'];
    $valor = (float)$input['valor'];
    
    $db = getDB();
    if (!$db) {
        throw new Exception('Erro de conexão com banco de dados');
    }
    
    // Verificar se o fiscal existe e está aprovado
    $stmt = $db->prepare("SELECT id, nome FROM fiscais WHERE id = ? AND status = 'aprovado'");
    $stmt->execute([$fiscal_id]);
    $fiscal = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$fiscal) {
        throw new Exception('Fiscal não encontrado ou não aprovado');
    }
    
    // Verificar se o fiscal esteve presente na prova
    $stmt = $db->prepare("SELECT status FROM presenca WHERE fiscal_id = ? AND concurso_id = ? AND tipo_presenca = 'prova'");
    $stmt->execute([$fiscal_id, $concurso_id]);
    $presenca = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$presenca || $presenca['status'] !== 'presente') {
        throw new Exception('Fiscal deve estar presente na prova para receber pagamento');
    }
    
    // Verificar se já existe pagamento para este fiscal
    $stmt = $db->prepare("SELECT id FROM pagamentos WHERE fiscal_id = ? AND concurso_id = ?");
    $stmt->execute([$fiscal_id, $concurso_id]);
    $pagamento_existente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pagamento_existente) {
        throw new Exception('Pagamento já registrado para este fiscal');
    }
    
    // Registrar o pagamento
    $stmt = $db->prepare("
        INSERT INTO pagamentos (fiscal_id, concurso_id, usuario_id, valor, forma_pagamento, status, data_pagamento, observacoes, created_at)
        VALUES (?, ?, ?, ?, 'dinheiro', 'pago', NOW(), 'Pagamento registrado via sistema simplificado', NOW())
    ");
    
    $stmt->execute([
        $fiscal_id,
        $concurso_id,
        $_SESSION['user_id'],
        $valor
    ]);
    
    // Log da atividade
    logActivity("Pagamento registrado para fiscal {$fiscal['nome']} (ID: $fiscal_id) - Valor: R$ " . number_format($valor, 2, ',', '.'), 'INFO');
    
    echo json_encode([
        'success' => true,
        'message' => 'Pagamento registrado com sucesso',
        'fiscal_nome' => $fiscal['nome'],
        'valor' => $valor
    ]);
    
} catch (Exception $e) {
    logActivity('Erro ao registrar pagamento: ' . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 