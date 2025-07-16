<?php
require_once '../config.php';

// Verificar se tem permissão para alocações
if (!isLoggedIn() || !temPermissaoAlocacoes()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$db = getDB();

try {
    // Obter dados do POST
    $input = json_decode(file_get_contents('php://input'), true);
    $concurso_id = isset($input['concurso_id']) ? (int)$input['concurso_id'] : null;
    
    // Buscar fiscais aprovados não alocados
    $sql_fiscais = "
        SELECT f.* 
        FROM fiscais f
        LEFT JOIN alocacoes_fiscais a ON f.id = a.fiscal_id AND a.status = 'ativo'
        WHERE f.status = 'aprovado' 
        AND a.id IS NULL
    ";
    
    if ($concurso_id) {
        $sql_fiscais .= " AND f.concurso_id = ?";
        $stmt = $db->prepare($sql_fiscais);
        $stmt->execute([$concurso_id]);
    } else {
        $stmt = $db->prepare($sql_fiscais);
        $stmt->execute();
    }
    
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($fiscais)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Nenhum fiscal aprovado não alocado encontrado'
        ]);
        exit;
    }
    
    // Buscar escolas e salas disponíveis
    $sql_escolas = "
        SELECT e.*, s.id as sala_id, s.nome as sala_nome, s.capacidade
        FROM escolas e
        INNER JOIN salas s ON e.id = s.escola_id
        WHERE e.status = 'ativo' AND s.status = 'ativo'
    ";
    
    if ($concurso_id) {
        $sql_escolas .= " AND e.concurso_id = ?";
        $stmt = $db->prepare($sql_escolas);
        $stmt->execute([$concurso_id]);
    } else {
        $stmt = $db->prepare($sql_escolas);
        $stmt->execute();
    }
    
    $escolas_salas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($escolas_salas)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Nenhuma escola ou sala disponível encontrada'
        ]);
        exit;
    }
    
    // Contar alocações existentes por sala
    $alocacoes_por_sala = [];
    foreach ($escolas_salas as $escola_sala) {
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM alocacoes_fiscais 
            WHERE sala_id = ? AND status = 'ativo'
        ");
        $stmt->execute([$escola_sala['sala_id']]);
        $alocacoes_por_sala[$escola_sala['sala_id']] = $stmt->fetchColumn();
    }
    
    // Distribuir fiscais entre as salas
    $fiscais_alocados = 0;
    $fiscal_index = 0;
    
    foreach ($escolas_salas as $escola_sala) {
        $sala_id = $escola_sala['sala_id'];
        $capacidade = (int)$escola_sala['capacidade'];
        $alocacoes_atuais = $alocacoes_por_sala[$sala_id];
        
        // Calcular quantos fiscais podem ser alocados nesta sala
        $fiscais_por_sala = min(2, $capacidade - $alocacoes_atuais); // Máximo 2 fiscais por sala
        
        for ($i = 0; $i < $fiscais_por_sala && $fiscal_index < count($fiscais); $i++) {
            $fiscal = $fiscais[$fiscal_index];
            
            // Gerar data e horário aleatórios para o concurso
            $data_alocacao = date('Y-m-d', strtotime('+' . rand(1, 30) . ' days'));
            $horario_alocacao = rand(8, 18); // Entre 8h e 18h
            
            // Inserir alocação
            $stmt = $db->prepare("
                INSERT INTO alocacoes_fiscais (
                    fiscal_id, escola_id, sala_id, data_alocacao, horario_alocacao,
                    tipo_alocacao, observacoes, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'ativo', CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                $fiscal['id'],
                $escola_sala['id'],
                $sala_id,
                $data_alocacao,
                $horario_alocacao,
                'prova', // Tipo padrão
                'Alocação automática - ' . $escola_sala['nome'] . ' - ' . $escola_sala['sala_nome']
            ]);
            
            $fiscais_alocados++;
            $fiscal_index++;
        }
    }
    
    // Log da atividade
    logActivity(
        "Alocação automática realizada: {$fiscais_alocados} fiscais alocados", 
        'INFO'
    );
    
    echo json_encode([
        'success' => true,
        'message' => "Alocação automática realizada com sucesso! {$fiscais_alocados} fiscais foram alocados.",
        'fiscais_alocados' => $fiscais_alocados
    ]);
    
} catch (Exception $e) {
    logActivity('Erro na alocação automática: ' . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao realizar alocação automática: ' . $e->getMessage()
    ]);
} 