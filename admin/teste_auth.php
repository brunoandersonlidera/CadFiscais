<?php
require_once '../config.php';

echo "<h2>Teste de Autenticação</h2>";

// Verificar se a sessão está ativa
echo "<p>Sessão ativa: " . (session_status() === PHP_SESSION_ACTIVE ? 'Sim' : 'Não') . "</p>";

// Verificar se o usuário está logado
echo "<p>Usuário logado: " . (isLoggedIn() ? 'Sim' : 'Não') . "</p>";

// Verificar se é admin
echo "<p>É admin: " . (isAdmin() ? 'Sim' : 'Não') . "</p>";

// Verificar dados da sessão
echo "<h3>Dados da Sessão:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Testar busca de escola sem verificação de admin
echo "<h3>Teste de Busca de Escola (sem verificação de admin):</h3>";

try {
    $db = getDB();
    $sql = "SELECT * FROM escolas WHERE id = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $escola = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($escola) {
        echo "<p style='color: green;'>✅ Escola encontrada: " . htmlspecialchars($escola['nome']) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Escola não encontrada</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?> 