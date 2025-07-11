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
    $escola_id = isset($input['escola_id']) ? (int)$input['escola_id'] : 0;
    
    if (!$escola_id) {
        throw new Exception('ID da escola não informado');
    }
    
    // Buscar escola
    $sql = "SELECT nome FROM escolas WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$escola_id]);
    $escola = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$escola) {
        throw new Exception('Escola não encontrada');
    }
    
    // Buscar salas da escola
    $sql = "
        SELECT s.*, 
               (SELECT COUNT(*) FROM alocacoes_fiscais WHERE sala_id = s.id AND status = 'ativo') as total_alocacoes
        FROM salas s 
        WHERE s.escola_id = ? 
        ORDER BY s.nome
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$escola_id]);
    $salas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Gerar HTML
    $html = "
    <div class='mb-3'>
        <h6 class='text-primary'>Escola: " . htmlspecialchars($escola['nome']) . "</h6>
        <p class='text-muted'>Total de salas: " . count($salas) . "</p>
    </div>
    
    <div class='table-responsive'>
        <table class='table table-sm'>
            <thead class='table-light'>
                <tr>
                    <th>Nome da Sala</th>
                    <th>Tipo</th>
                    <th>Capacidade</th>
                    <th>Status</th>
                    <th>Alocações</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>";
    
    if (empty($salas)) {
        $html .= "
            <tr>
                <td colspan='6' class='text-center text-muted'>
                    <i class='fas fa-info-circle me-2'></i>
                    Nenhuma sala cadastrada para esta escola
                </td>
            </tr>";
    } else {
        foreach ($salas as $sala) {
            $html .= "
            <tr>
                <td>" . htmlspecialchars($sala['nome']) . "</td>
                <td>
                    <span class='badge bg-" . ($sala['tipo'] == 'sala_aula' ? 'primary' : 'info') . "'>
                        " . ucfirst(str_replace('_', ' ', $sala['tipo'])) . "
                    </span>
                </td>
                <td>" . $sala['capacidade'] . " pessoas</td>
                <td>
                    <span class='badge bg-" . ($sala['status'] == 'ativo' ? 'success' : 'danger') . "'>
                        " . ucfirst($sala['status']) . "
                    </span>
                </td>
                <td>
                    <span class='badge bg-info'>" . $sala['total_alocacoes'] . "</span>
                </td>
                <td>
                    <div class='btn-group btn-group-sm'>
                        <button onclick='editarSala(" . $sala['id'] . ")' class='btn btn-warning' title='Editar'>
                            <i class='fas fa-edit'></i>
                        </button>
                        <button onclick='toggleStatusSala(" . $sala['id'] . ")' 
                                class='btn btn-" . ($sala['status'] == 'ativo' ? 'danger' : 'success') . "' 
                                title='" . ($sala['status'] == 'ativo' ? 'Desativar' : 'Ativar') . "'>
                            <i class='fas fa-" . ($sala['status'] == 'ativo' ? 'times' : 'check') . "'></i>
                        </button>
                    </div>
                </td>
            </tr>";
        }
    }
    
    $html .= "
            </tbody>
        </table>
    </div>
    
    <div class='mt-3'>
        <button onclick='novaSala(" . $escola_id . ")' class='btn btn-primary btn-sm'>
            <i class='fas fa-plus me-2'></i>
            Nova Sala
        </button>
    </div>";
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    logActivity('Erro ao buscar salas da escola: ' . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?> 