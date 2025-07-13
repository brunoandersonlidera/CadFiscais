<?php
require_once 'config.php';

echo "<h2>Teste do Relatório de Fiscais</h2>";

try {
    $db = getDB();
    
    // Teste 1: Verificar se há fiscais cadastrados
    echo "<h3>1. Verificação de Fiscais Cadastrados</h3>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM fiscais");
    $result = $stmt->fetch();
    echo "<p><strong>Total de fiscais:</strong> " . $result['total'] . "</p>";
    
    // Teste 2: Listar todos os fiscais
    echo "<h3>2. Lista de Fiscais</h3>";
    $stmt = $db->query("SELECT nome, cpf, status, created_at FROM fiscais ORDER BY nome LIMIT 10");
    $fiscais = $stmt->fetchAll();
    
    if (count($fiscais) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Nome</th><th>CPF</th><th>Status</th><th>Data Cadastro</th></tr>";
        
        foreach ($fiscais as $fiscal) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($fiscal['nome']) . "</td>";
            echo "<td>" . htmlspecialchars($fiscal['cpf']) . "</td>";
            echo "<td>" . htmlspecialchars($fiscal['status']) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($fiscal['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>Nenhum fiscal encontrado!</strong></p>";
    }
    
    // Teste 3: Verificar estrutura da tabela
    echo "<h3>3. Estrutura da Tabela Fiscais</h3>";
    $stmt = $db->query("DESCRIBE fiscais");
    $colunas = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>" . $coluna['Field'] . "</td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . $coluna['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Teste 4: Testar a consulta exata do relatório
    echo "<h3>4. Teste da Consulta do Relatório</h3>";
    $sql = "SELECT f.nome, f.cpf, f.status, f.created_at FROM fiscais f ORDER BY f.nome";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $resultado = $stmt->fetchAll();
    
    echo "<p><strong>Consulta executada:</strong> " . $sql . "</p>";
    echo "<p><strong>Resultados encontrados:</strong> " . count($resultado) . "</p>";
    
    if (count($resultado) > 0) {
        echo "<p style='color: green;'><strong>✓ Consulta funcionando corretamente!</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>✗ Nenhum resultado encontrado!</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Erro:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='admin/exportar_pdf_fiscais.php' target='_blank'>Testar Exportação PDF</a></p>";
echo "<p><a href='admin/relatorio_fiscais.php'>Voltar ao Relatório</a></p>";
?> 