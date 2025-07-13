<?php
require_once '../config.php';

// Verificar se é admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$usuario_id = (int)($input['id'] ?? 0);

if ($usuario_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$db = getDB();
$response = ['success' => false, 'message' => ''];

try {
    // Verificar se usuário existe
    $stmt = $db->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        throw new Exception('Usuário não encontrado');
    }
    
    // Verificar se não é o próprio usuário logado
    if ($usuario_id == $_SESSION['user_id']) {
        throw new Exception('Não é possível excluir o próprio usuário');
    }
    
    // Verificar se é o último administrador
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM usuarios u 
        JOIN tipos_usuario t ON u.tipo_usuario_id = t.id 
        WHERE t.permissoes LIKE '%\"admin\": true%' AND u.status = 'ativo'
    ");
    $stmt->execute();
    $admin_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Verificar se o usuário a ser excluído é admin
    $stmt = $db->prepare("
        SELECT t.permissoes 
        FROM usuarios u 
        JOIN tipos_usuario t ON u.tipo_usuario_id = t.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$usuario_id]);
    $permissoes = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($permissoes && strpos($permissoes['permissoes'], '"admin": true') !== false && $admin_count <= 1) {
        throw new Exception('Não é possível excluir o último administrador');
    }
    
    // Excluir usuário
    $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    
    // Log da atividade
    logActivity("Usuário excluído: {$usuario['nome']} ({$usuario['email']})", 'INFO');
    
    $response['success'] = true;
    $response['message'] = 'Usuário excluído com sucesso!';
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    logActivity('Erro ao excluir usuário: ' . $e->getMessage(), 'ERROR');
}

header('Content-Type: application/json');
echo json_encode($response);
?> 