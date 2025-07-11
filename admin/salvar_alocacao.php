<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMessage('Método não permitido', 'error');
    redirect('fiscais.php');
}

// Obter dados do formulário
$fiscal_id = (int)($_POST['fiscal_id'] ?? 0);
$escola_id = (int)($_POST['escola_id'] ?? 0);
$sala_id = (int)($_POST['sala_id'] ?? 0);
$tipo_alocacao = trim($_POST['tipo_alocacao'] ?? '');
$observacoes = trim($_POST['observacoes_alocacao'] ?? '');
$data_alocacao = trim($_POST['data_alocacao'] ?? '');
$horario_alocacao = trim($_POST['horario_alocacao'] ?? '');

// Validar dados obrigatórios
if (!$fiscal_id || !$escola_id || !$sala_id || !$data_alocacao || !$horario_alocacao) {
    setMessage('Todos os campos obrigatórios devem ser preenchidos', 'error');
    redirect("alocar_fiscal.php?id=$fiscal_id");
}

$db = getDB();

try {
    // Verificar se o fiscal existe
    $stmt = $db->prepare("SELECT id, nome FROM fiscais WHERE id = ?");
    $stmt->execute([$fiscal_id]);
    $fiscal = $stmt->fetch();
    
    if (!$fiscal) {
        setMessage('Fiscal não encontrado', 'error');
        redirect('fiscais.php');
    }
    
    // Verificar se a escola existe
    $stmt = $db->prepare("SELECT id, nome FROM escolas WHERE id = ? AND status = 'ativo'");
    $stmt->execute([$escola_id]);
    $escola = $stmt->fetch();
    
    if (!$escola) {
        setMessage('Escola não encontrada', 'error');
        redirect("alocar_fiscal.php?id=$fiscal_id");
    }
    
    // Verificar se a sala existe e pertence à escola
    $stmt = $db->prepare("SELECT id, nome FROM salas WHERE id = ? AND escola_id = ? AND status = 'ativo'");
    $stmt->execute([$sala_id, $escola_id]);
    $sala = $stmt->fetch();
    
    if (!$sala) {
        setMessage('Sala não encontrada ou não pertence à escola selecionada', 'error');
        redirect("alocar_fiscal.php?id=$fiscal_id");
    }
    
    // Verificar se já existe alocação ativa para este fiscal na mesma data/horário
    $stmt = $db->prepare("
        SELECT id FROM alocacoes_fiscais 
        WHERE fiscal_id = ? AND data_alocacao = ? AND horario_alocacao = ? AND status = 'ativo'
    ");
    $stmt->execute([$fiscal_id, $data_alocacao, $horario_alocacao]);
    $alocacao_existente = $stmt->fetch();
    
    if ($alocacao_existente) {
        setMessage('Já existe uma alocação ativa para este fiscal na data/horário selecionado', 'error');
        redirect("alocar_fiscal.php?id=$fiscal_id");
    }
    
    // Inserir alocação
    $stmt = $db->prepare("
        INSERT INTO alocacoes_fiscais (
            fiscal_id, escola_id, sala_id, tipo_alocacao, observacoes, 
            data_alocacao, horario_alocacao, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'ativo', CURRENT_TIMESTAMP)
    ");
    
    $resultado = $stmt->execute([
        $fiscal_id, $escola_id, $sala_id, $tipo_alocacao, $observacoes,
        $data_alocacao, $horario_alocacao
    ]);
    
    if ($resultado) {
        $alocacao_id = $db->lastInsertId();
        
        // Atualizar status do fiscal para aprovado
        $stmt = $db->prepare("UPDATE fiscais SET status = 'aprovado' WHERE id = ?");
        $stmt->execute([$fiscal_id]);
        
        // Log da atividade
        logActivity("Fiscal {$fiscal['nome']} alocado na escola {$escola['nome']}, sala {$sala['nome']} - ID: $alocacao_id", 'INFO');
        
        // Redirecionar com mensagem de sucesso
        setMessage('Alocação salva com sucesso!', 'success');
        redirect("alocar_fiscal.php?id=$fiscal_id");
    } else {
        setMessage('Erro ao salvar alocação', 'error');
        redirect("alocar_fiscal.php?id=$fiscal_id");
    }
    
} catch (Exception $e) {
    logActivity('Erro ao salvar alocação: ' . $e->getMessage(), 'ERROR');
    setMessage('Erro interno do servidor', 'error');
    redirect("alocar_fiscal.php?id=$fiscal_id");
}
?> 