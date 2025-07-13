<?php
require_once '../config.php';

// Verificar se é admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$usuario_id = (int)($_GET['id'] ?? 0);
if ($usuario_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$db = getDB();
$response = ['success' => false, 'message' => '', 'html' => ''];

try {
    // Buscar usuário
    $stmt = $db->prepare("
        SELECT u.*, t.nome as tipo_nome, t.descricao as tipo_descricao
        FROM usuarios u 
        JOIN tipos_usuario t ON u.tipo_usuario_id = t.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        throw new Exception('Usuário não encontrado');
    }
    
    // Gerar HTML dos detalhes
    $html = '
    <div class="row">
        <div class="col-md-6">
            <h6 class="text-muted">Informações Pessoais</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>Nome:</strong></td>
                    <td>' . htmlspecialchars($usuario['nome']) . '</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>' . htmlspecialchars($usuario['email']) . '</td>
                </tr>
                <tr>
                    <td><strong>CPF:</strong></td>
                    <td>' . formatCPF($usuario['cpf']) . '</td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>
                        <span class="badge bg-' . ($usuario['status'] == 'ativo' ? 'success' : 'secondary') . '">
                            ' . ucfirst($usuario['status']) . '
                        </span>
                    </td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6 class="text-muted">Informações do Sistema</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>Tipo de Usuário:</strong></td>
                    <td>' . htmlspecialchars($usuario['tipo_nome']) . '</td>
                </tr>
                <tr>
                    <td><strong>Descrição:</strong></td>
                    <td>' . htmlspecialchars($usuario['tipo_descricao']) . '</td>
                </tr>
                <tr>
                    <td><strong>Data de Cadastro:</strong></td>
                    <td>' . date('d/m/Y H:i', strtotime($usuario['created_at'])) . '</td>
                </tr>
                <tr>
                    <td><strong>Última Atualização:</strong></td>
                    <td>' . ($usuario['updated_at'] ? date('d/m/Y H:i', strtotime($usuario['updated_at'])) : 'Nunca') . '</td>
                </tr>
                <tr>
                    <td><strong>Último Login:</strong></td>
                    <td>' . ($usuario['ultimo_login'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) : 'Nunca') . '</td>
                </tr>
            </table>
        </div>
    </div>';
    
    $response['success'] = true;
    $response['html'] = $html;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    logActivity('Erro ao buscar usuário: ' . $e->getMessage(), 'ERROR');
}

header('Content-Type: application/json');
echo json_encode($response);
?> 