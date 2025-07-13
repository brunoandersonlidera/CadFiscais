<?php
require_once '../config.php';

// Verificar se tem permissão para pagamentos
if (!isLoggedIn() || !temPermissaoPagamentos()) {
    redirect('../login.php');
}

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('novo_pagamento.php');
}

$db = getDB();

// Capturar dados do formulário
$concurso_id = isset($_POST['concurso_id']) ? (int)$_POST['concurso_id'] : 0;
$fiscal_id = isset($_POST['fiscal_id']) ? (int)$_POST['fiscal_id'] : 0;
$valor = isset($_POST['valor']) ? (float)$_POST['valor'] : 0;
$forma_pagamento = isset($_POST['forma_pagamento']) ? $_POST['forma_pagamento'] : '';
$data_pagamento = isset($_POST['data_pagamento']) ? $_POST['data_pagamento'] : '';
$observacoes = isset($_POST['observacoes']) ? $_POST['observacoes'] : '';
$status_pagamento = isset($_POST['status_pagamento']) ? $_POST['status_pagamento'] : '';

// Validar dados obrigatórios
if (!$concurso_id || !$fiscal_id || !$valor || !$forma_pagamento || !$data_pagamento || !$status_pagamento) {
    setMessage('Todos os campos obrigatórios devem ser preenchidos', 'error');
    redirect('novo_pagamento.php');
}

// Validar valor
if ($valor <= 0) {
    setMessage('O valor deve ser maior que zero', 'error');
    redirect('novo_pagamento.php');
}

// Validar data
if (!strtotime($data_pagamento)) {
    setMessage('Data de pagamento inválida', 'error');
    redirect('novo_pagamento.php');
}

try {
    // Verificar se o concurso existe
    $stmt = $db->prepare("SELECT id FROM concursos WHERE id = ?");
    $stmt->execute([$concurso_id]);
    if (!$stmt->fetch()) {
        setMessage('Concurso não encontrado', 'error');
        redirect('novo_pagamento.php');
    }
    
    // Verificar se o fiscal existe e está aprovado
    $stmt = $db->prepare("SELECT id, nome FROM fiscais WHERE id = ? AND status = 'aprovado'");
    $stmt->execute([$fiscal_id]);
    $fiscal = $stmt->fetch();
    if (!$fiscal) {
        setMessage('Fiscal não encontrado ou não aprovado', 'error');
        redirect('novo_pagamento.php');
    }
    
    // Verificar se já existe pagamento para este fiscal neste concurso
    $stmt = $db->prepare("SELECT id FROM pagamentos WHERE fiscal_id = ? AND concurso_id = ?");
    $stmt->execute([$fiscal_id, $concurso_id]);
    if ($stmt->fetch()) {
        setMessage('Já existe um pagamento registrado para este fiscal neste concurso', 'error');
        redirect('novo_pagamento.php');
    }
    
    // Inserir pagamento
    $stmt = $db->prepare("
        INSERT INTO pagamentos (concurso_id, fiscal_id, valor, forma_pagamento, data_pagamento, observacoes, status_pagamento, data_criacao)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $concurso_id,
        $fiscal_id,
        $valor,
        $forma_pagamento,
        $data_pagamento,
        $observacoes,
        $status_pagamento
    ]);
    
    if ($result) {
        $pagamento_id = $db->lastInsertId();
        
        // Log da atividade
        logActivity("Novo pagamento registrado: Fiscal {$fiscal['nome']} - R$ " . number_format($valor, 2, ',', '.') . " - {$forma_pagamento}", 'INFO');
        
        setMessage('Pagamento registrado com sucesso!', 'success');
        redirect('lista_pagamentos.php');
    } else {
        setMessage('Erro ao registrar pagamento', 'error');
        redirect('novo_pagamento.php');
    }
    
} catch (Exception $e) {
    logActivity('Erro ao salvar pagamento: ' . $e->getMessage(), 'ERROR');
    setMessage('Erro interno do sistema. Tente novamente.', 'error');
    redirect('novo_pagamento.php');
}
?> 