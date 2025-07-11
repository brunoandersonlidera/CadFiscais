<?php
require_once '../config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    redirect('../index.php');
}

// Log da atividade
logActivity('Usuário fez logout: ' . ($_SESSION['username'] ?? 'Admin'), 'INFO');

// Destruir a sessão
session_destroy();

// Redirecionar para a página inicial
setMessage('Logout realizado com sucesso!', 'success');
redirect('../index.php');
?> 