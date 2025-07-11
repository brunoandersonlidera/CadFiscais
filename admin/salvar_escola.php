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
    $escola_id = isset($_POST['escola_id']) ? (int)$_POST['escola_id'] : null;
    $concurso_id = isset($_POST['concurso_id']) ? (int)$_POST['concurso_id'] : null;
    $nome = trim($_POST['nome']);
    $tipo = $_POST['tipo'];
    $endereco = trim($_POST['endereco']);
    $telefone = trim($_POST['telefone']);
    $email = trim($_POST['email']);
    $responsavel = trim($_POST['responsavel']);
    $capacidade = isset($_POST['capacidade']) ? (int)$_POST['capacidade'] : null;
    $observacoes = trim($_POST['observacoes']);
    
    // Validações
    if (empty($nome)) {
        throw new Exception('Nome da escola é obrigatório');
    }
    
    if (empty($concurso_id)) {
        throw new Exception('Concurso é obrigatório');
    }
    
    if (empty($tipo) || !in_array($tipo, ['publica', 'privada'])) {
        throw new Exception('Tipo de escola inválido');
    }
    
    if (empty($endereco)) {
        throw new Exception('Endereço é obrigatório');
    }
    
    // Verificar se o concurso existe e está ativo
    $stmt_concurso = $db->prepare("SELECT id FROM concursos WHERE id = ? AND status = 'ativo'");
    $stmt_concurso->execute([$concurso_id]);
    if (!$stmt_concurso->fetch()) {
        throw new Exception('Concurso selecionado não existe ou está inativo');
    }
    
    // Verificar se já existe escola com mesmo nome no mesmo concurso
    $sql_check = "SELECT id FROM escolas WHERE nome = ? AND concurso_id = ? AND id != ?";
    $stmt_check = $db->prepare($sql_check);
    $stmt_check->execute([$nome, $concurso_id, $escola_id ?: 0]);
    
    if ($stmt_check->fetch()) {
        throw new Exception('Já existe uma escola com este nome neste concurso');
    }
    
    if ($escola_id) {
        // Atualizar escola existente
        $sql = "
            UPDATE escolas 
            SET nome = ?, concurso_id = ?, tipo = ?, endereco = ?, telefone = ?, email = ?, 
                responsavel = ?, capacidade = ?, observacoes = ?, data_atualizacao = NOW()
            WHERE id = ?
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $nome, $concurso_id, $tipo, $endereco, $telefone, $email, 
            $responsavel, $capacidade, $observacoes, $escola_id
        ]);
        
        logActivity("Escola atualizada: {$nome} (Concurso ID: {$concurso_id})", 'INFO');
        $message = 'Escola atualizada com sucesso!';
    } else {
        // Inserir nova escola
        $sql = "
            INSERT INTO escolas (nome, concurso_id, tipo, endereco, telefone, email, responsavel, 
                               capacidade, observacoes, status, data_cadastro)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativo', NOW())
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $nome, $concurso_id, $tipo, $endereco, $telefone, $email, 
            $responsavel, $capacidade, $observacoes
        ]);
        
        $escola_id = $db->lastInsertId();
        logActivity("Nova escola cadastrada: {$nome} (Concurso ID: {$concurso_id})", 'INFO');
        $message = 'Escola cadastrada com sucesso!';
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'escola_id' => $escola_id
    ]);
    
} catch (Exception $e) {
    logActivity('Erro ao salvar escola: ' . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?> 