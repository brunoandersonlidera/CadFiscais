<?php
require_once '../config.php';

// Verificar se é admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('usuarios.php');
}

$db = getDB();
$response = ['success' => false, 'message' => ''];

try {
    // Validar dados
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $tipo_usuario_id = (int)($_POST['tipo_usuario_id'] ?? 0);
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $status = $_POST['status'] ?? 'ativo';
    
    // Validações
    if (empty($nome)) {
        throw new Exception('Nome é obrigatório');
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }
    
    if (strlen($cpf) !== 11) {
        throw new Exception('CPF inválido');
    }
    
    if ($tipo_usuario_id <= 0) {
        throw new Exception('Tipo de usuário é obrigatório');
    }
    
    if (strlen($senha) < 6) {
        throw new Exception('Senha deve ter pelo menos 6 caracteres');
    }
    
    if ($senha !== $confirmar_senha) {
        throw new Exception('Senhas não coincidem');
    }
    
    // Verificar se email já existe
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('Email já cadastrado');
    }
    
    // Verificar se CPF já existe
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE cpf = ?");
    $stmt->execute([$cpf]);
    if ($stmt->fetch()) {
        throw new Exception('CPF já cadastrado');
    }
    
    // Verificar se tipo de usuário existe
    $stmt = $db->prepare("SELECT id FROM tipos_usuario WHERE id = ?");
    $stmt->execute([$tipo_usuario_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Tipo de usuário inválido');
    }
    
    // Inserir usuário
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("
        INSERT INTO usuarios (nome, email, cpf, tipo_usuario_id, senha, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$nome, $email, $cpf, $tipo_usuario_id, $senha_hash, $status]);
    
    $usuario_id = $db->lastInsertId();
    
    // Log da atividade
    logActivity("Novo usuário criado: $nome ($email)", 'INFO');
    
    $response['success'] = true;
    $response['message'] = 'Usuário criado com sucesso!';
    $response['redirect'] = 'usuarios.php';
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    logActivity('Erro ao criar usuário: ' . $e->getMessage(), 'ERROR');
}

header('Content-Type: application/json');
echo json_encode($response);
?> 