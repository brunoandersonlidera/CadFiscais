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
    $sala_id = isset($input['sala_id']) ? (int)$input['sala_id'] : 0;
    
    if (!$sala_id) {
        throw new Exception('ID da sala não informado');
    }
    
    // Buscar dados da sala
    $stmt = $db->prepare("
        SELECT s.*, e.nome as escola_nome 
        FROM salas s 
        LEFT JOIN escolas e ON s.escola_id = e.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$sala_id]);
    $sala = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sala) {
        throw new Exception('Sala não encontrada');
    }
    
    // Buscar alocações da sala
    $stmt = $db->prepare("
        SELECT a.*, f.nome as fiscal_nome, f.cpf as fiscal_cpf,
               c.titulo as concurso_titulo
        FROM alocacoes a
        LEFT JOIN fiscais f ON a.fiscal_id = f.id
        LEFT JOIN concursos c ON a.concurso_id = c.id
        WHERE a.sala_id = ? AND a.status = 'agendada'
        ORDER BY a.data_alocacao, a.horario_inicio
    ");
    $stmt->execute([$sala_id]);
    $alocacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Gerar HTML
    $html = "
    <div class='row'>
        <div class='col-12'>
            <h6 class='text-primary'>Dados da Sala</h6>
            <table class='table table-sm'>
                <tr>
                    <td><strong>Nome:</strong></td>
                    <td>" . htmlspecialchars($sala['nome']) . "</td>
                </tr>
                <tr>
                    <td><strong>Escola:</strong></td>
                    <td>" . htmlspecialchars($sala['escola_nome']) . "</td>
                </tr>
                <tr>
                    <td><strong>Tipo:</strong></td>
                    <td>" . ucfirst(str_replace('_', ' ', $sala['tipo'])) . "</td>
                </tr>
                <tr>
                    <td><strong>Capacidade:</strong></td>
                    <td>" . $sala['capacidade'] . " pessoas</td>
                </tr>
            </table>
        </div>
    </div>
    
    <hr>
    
    <div class='row'>
        <div class='col-12'>
            <h6 class='text-primary'>Alocações de Fiscais (" . count($alocacoes) . ")</h6>";
    
    if (empty($alocacoes)) {
        $html .= "<p class='text-muted'>Nenhuma alocação encontrada para esta sala.</p>";
    } else {
        $html .= "
        <div class='table-responsive'>
            <table class='table table-sm table-striped'>
                <thead>
                    <tr>
                        <th>Fiscal</th>
                        <th>CPF</th>
                        <th>Concurso</th>
                        <th>Data</th>
                        <th>Horário</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>";
        
        foreach ($alocacoes as $alocacao) {
            $html .= "
            <tr>
                <td>" . htmlspecialchars($alocacao['fiscal_nome']) . "</td>
                <td>" . htmlspecialchars($alocacao['fiscal_cpf']) . "</td>
                <td>" . htmlspecialchars($alocacao['concurso_titulo']) . "</td>
                <td>" . date('d/m/Y', strtotime($alocacao['data_alocacao'])) . "</td>
                <td>" . $alocacao['horario_inicio'] . " - " . $alocacao['horario_fim'] . "</td>
                <td>
                    <span class='badge bg-" . ($alocacao['status'] == 'agendada' ? 'warning' : 'success') . "'>
                        " . ucfirst($alocacao['status']) . "
                    </span>
                </td>
            </tr>";
        }
        
        $html .= "
                </tbody>
            </table>
        </div>";
    }
    
    $html .= "
        </div>
    </div>";
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    logActivity('Erro ao buscar alocações da sala: ' . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?> 