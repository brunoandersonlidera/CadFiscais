<?php
require_once '../config.php';

// Log de debug
logActivity('Iniciando salvamento de edição de fiscal', 'DEBUG');

// Verificar se é admin
if (!isAdmin()) {
    logActivity('Tentativa de acesso sem autenticação', 'WARNING');
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logActivity('Método não permitido: ' . $_SERVER['REQUEST_METHOD'], 'WARNING');
    redirect('fiscais.php');
}

$fiscal_id = isset($_POST['fiscal_id']) ? (int)$_POST['fiscal_id'] : 0;
$db = getDB();

logActivity('Fiscal ID recebido: ' . $fiscal_id, 'DEBUG');

if (!$fiscal_id) {
    logActivity('ID do fiscal não fornecido', 'ERROR');
    setMessage('ID do fiscal não fornecido', 'error');
    redirect('fiscais.php');
}

try {
    // Validar dados obrigatórios
    $campos_obrigatorios = ['nome', 'email', 'ddi', 'celular', 'genero', 'cpf', 'data_nascimento', 'endereco', 'concurso_id', 'status'];
    $dados_faltantes = [];
    
    foreach ($campos_obrigatorios as $campo) {
        if (empty($_POST[$campo])) {
            $dados_faltantes[] = $campo;
        }
    }
    
    if (!empty($dados_faltantes)) {
        setMessage('Campos obrigatórios não preenchidos: ' . implode(', ', $dados_faltantes), 'error');
        redirect("editar_fiscal.php?id=$fiscal_id");
    }
    
    // Preparar dados
    $dados = [
        'nome' => trim($_POST['nome']),
        'email' => trim($_POST['email']),
        'ddi' => $_POST['ddi'],
        'celular' => preg_replace('/\D/', '', $_POST['celular']),
        'genero' => $_POST['genero'],
        'cpf' => preg_replace('/\D/', '', $_POST['cpf']),
        'data_nascimento' => $_POST['data_nascimento'],
        'endereco' => trim($_POST['endereco']),
        'melhor_horario' => $_POST['melhor_horario'] ?? null,
        'whatsapp' => !empty($_POST['whatsapp']) ? preg_replace('/\D/', '', $_POST['whatsapp']) : null,
        'observacoes' => trim($_POST['observacoes'] ?? ''),
        'concurso_id' => (int)$_POST['concurso_id'],
        'status' => $_POST['status'],
        'status_contato' => $_POST['status_contato'] ?? 'nao_contatado',
        'aceite_termos' => isset($_POST['aceite_termos']) ? 1 : 0,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    logActivity('Dados preparados: ' . json_encode($dados), 'DEBUG');
    
    // Se aceite dos termos foi marcado, registrar data
    if ($dados['aceite_termos'] && !isset($_POST['data_aceite_termos'])) {
        $dados['data_aceite_termos'] = date('Y-m-d H:i:s');
    }
    
    logActivity('Iniciando verificações de CPF e email', 'DEBUG');
    
    // Verificar se CPF já existe (exceto para o próprio fiscal)
    $stmt = $db->prepare("SELECT id FROM fiscais WHERE cpf = ? AND id != ?");
    $stmt->execute([$dados['cpf'], $fiscal_id]);
    if ($stmt->fetch()) {
        logActivity('CPF já cadastrado para outro fiscal', 'ERROR');
        setMessage('CPF já cadastrado para outro fiscal', 'error');
        redirect("editar_fiscal.php?id=$fiscal_id");
    }
    
    // Verificar se email já existe (exceto para o próprio fiscal)
    $stmt = $db->prepare("SELECT id FROM fiscais WHERE email = ? AND id != ?");
    $stmt->execute([$dados['email'], $fiscal_id]);
    if ($stmt->fetch()) {
        logActivity('Email já cadastrado para outro fiscal', 'ERROR');
        setMessage('E-mail já cadastrado para outro fiscal', 'error');
        redirect("editar_fiscal.php?id=$fiscal_id");
    }
    
    logActivity('Verificações de CPF e email concluídas', 'DEBUG');
    
    logActivity('Iniciando construção do SQL UPDATE', 'DEBUG');
    
    // Atualizar fiscal
    $sql = "
        UPDATE fiscais SET 
            nome = ?, email = ?, ddi = ?, celular = ?, genero = ?, 
            cpf = ?, data_nascimento = ?, endereco = ?, melhor_horario = ?, 
            whatsapp = ?, observacoes = ?, concurso_id = ?, status = ?, 
            status_contato = ?, aceite_termos = ?, updated_at = ?
        WHERE id = ?
    ";
    
    $params = [
        $dados['nome'], $dados['email'], $dados['ddi'], $dados['celular'], 
        $dados['genero'], $dados['cpf'], $dados['data_nascimento'], 
        $dados['endereco'], $dados['melhor_horario'], $dados['whatsapp'], 
        $dados['observacoes'], $dados['concurso_id'], $dados['status'], 
        $dados['status_contato'], $dados['aceite_termos'], $dados['updated_at'],
        $fiscal_id
    ];
    
    // Adicionar data_aceite_termos se necessário
    if (isset($dados['data_aceite_termos'])) {
        $sql = str_replace('updated_at = ?', 'updated_at = ?, data_aceite_termos = ?', $sql);
        array_splice($params, -1, 0, [$dados['data_aceite_termos']]);
    }
    
    $stmt = $db->prepare($sql);
    $resultado = $stmt->execute($params);
    
    logActivity('SQL executado: ' . $sql, 'DEBUG');
    logActivity('Parâmetros: ' . json_encode($params), 'DEBUG');
    logActivity('Resultado da execução: ' . ($resultado ? 'SUCESSO' : 'FALHA'), 'DEBUG');
    
    if ($resultado) {
        // Log da atividade
        $usuario = $_SESSION['usuario']['nome'] ?? 'Admin';
        logActivity("Fiscal ID $fiscal_id editado por $usuario", 'INFO');
        
        setMessage('Fiscal atualizado com sucesso!', 'success');
        redirect('fiscais.php');
    } else {
        logActivity('Erro ao executar UPDATE: ' . implode(', ', $stmt->errorInfo()), 'ERROR');
        setMessage('Erro ao atualizar fiscal', 'error');
        redirect("editar_fiscal.php?id=$fiscal_id");
    }
    
} catch (Exception $e) {
    logActivity('Erro ao editar fiscal: ' . $e->getMessage(), 'ERROR');
    setMessage('Erro interno do sistema', 'error');
    redirect("editar_fiscal.php?id=$fiscal_id");
}
?> 