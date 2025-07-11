<?php
require_once 'config.php';

// Log da atividade antes de destruir a sessão
if (isLoggedIn()) {
    logActivity("Logout realizado", 'INFO');
}

// Destruir a sessão
session_destroy();

// Redirecionar para a página de login
redirect('login.php');
?> 