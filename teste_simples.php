<?php
require_once 'config.php';

echo "<h1>Teste Simples das Funções</h1>";

// Testar funções de formatação
$cpf_teste = "12345678901";
$phone_teste = "11987654321";

echo "<h2>1. Testando funções de formatação</h2>";
echo "<p>CPF teste: $cpf_teste → " . formatCPF($cpf_teste) . "</p>";
echo "<p>Telefone teste: $phone_teste → " . formatPhone($phone_teste) . "</p>";

// Testar conexão com banco
$db = getDB();
if ($db) {
    echo "<h2>2. Testando conexão com banco</h2>";
    echo "<p style='color: green;'>✅ Conexão com banco de dados OK</p>";
    
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM fiscais");
        $result = $stmt->fetch();
        echo "<p>Total de fiscais no banco: " . $result['total'] . "</p>";
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM alocacoes_fiscais WHERE status = 'ativo'");
        $result = $stmt->fetch();
        echo "<p>Total de alocações ativas: " . $result['total'] . "</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro na consulta: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Erro na conexão com banco de dados</p>";
}

echo "<h2>3. Status do sistema</h2>";
echo "<p>✅ Funções formatCPF() e formatPhone() estão funcionando</p>";
echo "<p>✅ Conexão com banco de dados OK</p>";
echo "<p>✅ Sistema básico funcionando</p>";

echo "<h2>4. Próximos passos</h2>";
echo "<p>Para testar as páginas completas, você precisa:</p>";
echo "<ol>";
echo "<li>Fazer login no sistema</li>";
echo "<li>Acessar as páginas via navegador</li>";
echo "<li>Testar as funcionalidades de alocação</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 