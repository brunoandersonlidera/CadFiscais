<?php
require_once 'config.php';

echo "<h2>Teste de Acesso - alocar_fiscal.php</h2>";

// Verificar se está logado
if (!isAdmin()) {
    echo "<p style='color: red;'><strong>❌ Não está logado como administrador!</strong></p>";
    echo "<p>Você precisa fazer login como administrador para acessar esta página.</p>";
    echo "<p><a href='login.php'>Fazer Login</a></p>";
    exit;
}

echo "<p style='color: green;'><strong>✓ Logado como administrador</strong></p>";

// Verificar se há fiscais cadastrados
try {
    $db = getDB();
    $stmt = $db->query("SELECT id, nome FROM fiscais LIMIT 5");
    $fiscais = $stmt->fetchAll();
    
    if (count($fiscais) > 0) {
        echo "<h3>Fiscais disponíveis para alocação:</h3>";
        echo "<ul>";
        foreach ($fiscais as $fiscal) {
            echo "<li><a href='admin/alocar_fiscal.php?id=" . $fiscal['id'] . "' target='_blank'>";
            echo htmlspecialchars($fiscal['nome']) . " (ID: " . $fiscal['id'] . ")";
            echo "</a></li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'><strong>❌ Nenhum fiscal cadastrado!</strong></p>";
        echo "<p>Você precisa cadastrar fiscais primeiro.</p>";
        echo "<p><a href='cadastro.php'>Cadastrar Fiscal</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Erro:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='admin/fiscais.php'>Ver Todos os Fiscais</a></p>";
echo "<p><a href='admin/dashboard.php'>Dashboard</a></p>";
?> 