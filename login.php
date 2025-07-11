<?php
require_once 'config.php';

// Se já estiver logado, redirecionar
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        if (autenticarUsuario($email, $senha)) {
            logActivity("Login realizado com sucesso", 'INFO');
            redirect('index.php');
        } else {
            $error = 'Email ou senha incorretos.';
            logActivity("Tentativa de login falhou para email: $email", 'WARNING');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema IDH</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Login - Sistema IDH</h1>
            <p>Sistema de Cadastro de Fiscais - Instituto Dignidade Humana</p>
        </div>
        
        <div class="card">
            <h2>Entrar no Sistema</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Entrar</button>
                </div>
            </form>
            
            <div style="margin-top: 20px; text-align: center;">
                <p><strong>Credenciais padrão:</strong></p>
                <p><strong>Email:</strong> admin@idh.com</p>
                <p><strong>Senha:</strong> admin123</p>
            </div>
        </div>
        
        <div class="card">
            <h3>ℹ️ Informações</h3>
            <p><strong>Administrador:</strong> Acesso total ao sistema</p>
            <p><strong>Colaborador:</strong> Acesso para gerar relatórios</p>
            <p><strong>Segurança:</strong> Todas as atividades são registradas</p>
        </div>
    </div>
</body>
</html> 