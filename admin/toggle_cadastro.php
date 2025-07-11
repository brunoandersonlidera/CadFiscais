<?php
require_once '../config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    $db = getDB();
    
    if (!$db) {
        throw new Exception("Erro de conexão com banco de dados");
    }
    
    // Buscar status atual
    $stmt = $db->prepare("SELECT valor FROM configuracoes WHERE chave = 'cadastro_aberto'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $status_atual = $result['valor'] ?? '1';
    $novo_status = $status_atual === '1' ? '0' : '1';
    
    // Atualizar configuração
    $stmt = $db->prepare("
        INSERT INTO configuracoes (chave, valor) 
        VALUES ('cadastro_aberto', ?) 
        ON DUPLICATE KEY UPDATE valor = ?
    ");
    $stmt->execute([$novo_status, $novo_status]);
    
    // Log da atividade
    $acao = $novo_status === '1' ? 'aberto' : 'fechado';
    logActivity("Cadastro de fiscais $acao pelo usuário " . ($_SESSION['user_name'] ?? 'admin'), 'INFO');
    
    echo json_encode([
        'success' => true,
        'message' => "Cadastro " . ($novo_status === '1' ? 'aberto' : 'fechado') . " com sucesso!",
        'status' => $novo_status
    ]);
    
} catch (Exception $e) {
    logActivity('Erro ao alterar status do cadastro: ' . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?> 