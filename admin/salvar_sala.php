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
    $sala_id = isset($_POST['sala_id']) ? (int)$_POST['sala_id'] : null;
    $escola_id = isset($_POST['escola_id']) ? (int)$_POST['escola_id'] : null;
    $nome = trim($_POST['nome']);
    $tipo = $_POST['tipo'];
    $capacidade = isset($_POST['capacidade']) ? (int)$_POST['capacidade'] : null;
    $descricao = trim($_POST['descricao']);
    
    // Validações
    if (empty($nome)) {
        throw new Exception('Nome da sala é obrigatório');
    }
    
    if (empty($escola_id)) {
        throw new Exception('Escola é obrigatória');
    }
    
    if (empty($tipo) || !in_array($tipo, ['sala_aula', 'auditorio', 'laboratorio', 'biblioteca', 'sala_reuniao'])) {
        throw new Exception('Tipo de sala inválido');
    }
    
    if (empty($capacidade) || $capacidade < 1) {
        throw new Exception('Capacidade deve ser maior que zero');
    }
    
    // Verificar se a escola existe e está ativa
    $stmt_escola = $db->prepare("SELECT id FROM escolas WHERE id = ? AND status = 'ativo'");
    $stmt_escola->execute([$escola_id]);
    if (!$stmt_escola->fetch()) {
        throw new Exception('Escola selecionada não existe ou está inativa');
    }
    
    // Verificar se já existe sala com mesmo nome na mesma escola
    $sql_check = "SELECT id FROM salas WHERE nome = ? AND escola_id = ? AND id != ?";
    $stmt_check = $db->prepare($sql_check);
    $stmt_check->execute([$nome, $escola_id, $sala_id ?: 0]);
    
    if ($stmt_check->fetch()) {
        throw new Exception('Já existe uma sala com este nome nesta escola');
    }
    
    if ($sala_id) {
        // Atualizar sala existente
        $sql = "
            UPDATE salas 
            SET nome = ?, escola_id = ?, tipo = ?, capacidade = ?, descricao = ?
            WHERE id = ?
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $nome, $escola_id, $tipo, $capacidade, $descricao, $sala_id
        ]);
        
        logActivity("Sala atualizada: {$nome} (Escola ID: {$escola_id})", 'INFO');
        $message = 'Sala atualizada com sucesso!';
    } else {
        // Inserir nova sala
        $sql = "
            INSERT INTO salas (nome, escola_id, tipo, capacidade, descricao, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'ativo', NOW())
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $nome, $escola_id, $tipo, $capacidade, $descricao
        ]);
        
        $sala_id = $db->lastInsertId();
        logActivity("Nova sala cadastrada: {$nome} (Escola ID: {$escola_id})", 'INFO');
        $message = 'Sala cadastrada com sucesso!';
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'sala_id' => $sala_id
    ]);
    
} catch (Exception $e) {
    logActivity('Erro ao salvar sala: ' . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?> 