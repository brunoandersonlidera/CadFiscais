<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$tipo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$tipo_id) {
    echo json_encode(['success' => false, 'message' => 'ID do tipo de usuário não fornecido']);
    exit;
}

try {
    $db = getDB();
    
    // Buscar dados do tipo de usuário
    $stmt = $db->prepare("SELECT * FROM tipos_usuario WHERE id = ?");
    $stmt->execute([$tipo_id]);
    $tipo = $stmt->fetch();
    
    if (!$tipo) {
        echo json_encode(['success' => false, 'message' => 'Tipo de usuário não encontrado']);
        exit;
    }
    
    // Decodificar permissões
    $permissoes = json_decode($tipo['permissoes'], true) ?? [];
    
    // Contar usuários deste tipo
    $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario_id = ?");
    $stmt->execute([$tipo_id]);
    $usuarios_count = $stmt->fetchColumn();
    
    // Gerar HTML dos detalhes
    $html = '
    <div class="row">
        <div class="col-md-6">
            <h6><i class="fas fa-info-circle me-2"></i>Informações Básicas</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>ID:</strong></td>
                    <td>' . $tipo['id'] . '</td>
                </tr>
                <tr>
                    <td><strong>Nome:</strong></td>
                    <td>' . htmlspecialchars($tipo['nome']) . '</td>
                </tr>
                <tr>
                    <td><strong>Descrição:</strong></td>
                    <td>' . htmlspecialchars($tipo['descricao']) . '</td>
                </tr>
                <tr>
                    <td><strong>Criado em:</strong></td>
                    <td>' . date('d/m/Y H:i', strtotime($tipo['created_at'])) . '</td>
                </tr>
                <tr>
                    <td><strong>Usuários:</strong></td>
                    <td>' . $usuarios_count . ' usuário(s)</td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6><i class="fas fa-shield-alt me-2"></i>Permissões</h6>
            <div class="row">';
    
    $permissoes_labels = [
        'admin' => 'Acesso Total (Administrador)',
        'usuarios' => 'Gerenciar Usuários',
        'tipos_usuario' => 'Gerenciar Tipos de Usuário',
        'cadastros' => 'Gerenciar Cadastros',
        'alocacoes' => 'Alocar Fiscais',
        'presenca' => 'Controle de Presença',
        'pagamentos' => 'Gerenciar Pagamentos',
        'relatorios' => 'Acessar Relatórios'
    ];
    
    foreach ($permissoes_labels as $chave => $label) {
        $tem_permissao = $permissoes[$chave] ?? false;
        $html .= '
                <div class="col-12 mb-2">
                    <span class="badge bg-' . ($tem_permissao ? 'success' : 'secondary') . '">
                        <i class="fas fa-' . ($tem_permissao ? 'check' : 'times') . ' me-1"></i>
                        ' . $label . '
                    </span>
                </div>';
    }
    
    $html .= '
            </div>
        </div>
    </div>';
    
    // Verificar se é um tipo padrão
    $tipos_protegidos = [1, 2, 3];
    $eh_protegido = in_array($tipo_id, $tipos_protegidos);
    
    if ($eh_protegido) {
        $html .= '
        <div class="alert alert-warning mt-3">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Atenção:</strong> Este é um tipo de usuário padrão do sistema e não pode ser excluído.
        </div>';
    }
    
    if ($usuarios_count > 0) {
        $html .= '
        <div class="alert alert-info mt-3">
            <i class="fas fa-users me-2"></i>
            <strong>Usuários associados:</strong> Este tipo de usuário possui ' . $usuarios_count . ' usuário(s) associado(s).
        </div>';
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'tipo' => $tipo,
        'permissoes' => $permissoes,
        'usuarios_count' => $usuarios_count,
        'eh_protegido' => $eh_protegido
    ]);
    
} catch (Exception $e) {
    logActivity('Erro ao buscar tipo de usuário: ' . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?> 