<?php
require_once 'config.php';

echo "<h2>Teste do Relatório de Alocações</h2>";

try {
    $db = getDB();
    
    // Teste 1: Verificar se há alocações cadastradas
    echo "<h3>1. Verificação de Alocações Cadastradas</h3>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM alocacoes_fiscais WHERE status = 'ativo'");
    $result = $stmt->fetch();
    echo "<p><strong>Total de alocações ativas:</strong> " . $result['total'] . "</p>";
    
    // Teste 2: Listar todas as alocações
    echo "<h3>2. Lista de Alocações</h3>";
    $stmt = $db->query("
        SELECT a.*, f.nome as fiscal_nome, c.titulo as concurso_titulo, 
               e.nome as escola_nome, s.nome as sala_nome
        FROM alocacoes_fiscais a
        LEFT JOIN fiscais f ON a.fiscal_id = f.id
        LEFT JOIN concursos c ON a.concurso_id = c.id
        LEFT JOIN escolas e ON a.escola_id = e.id
        LEFT JOIN salas s ON a.sala_id = s.id
        WHERE a.status = 'ativo'
        ORDER BY a.data_alocacao DESC
        LIMIT 10
    ");
    $alocacoes = $stmt->fetchAll();
    
    if (count($alocacoes) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Fiscal</th><th>Concurso</th><th>Escola</th><th>Sala</th><th>Data</th><th>Status</th></tr>";
        
        foreach ($alocacoes as $alocacao) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($alocacao['fiscal_nome']) . "</td>";
            echo "<td>" . htmlspecialchars($alocacao['concurso_titulo']) . "</td>";
            echo "<td>" . htmlspecialchars($alocacao['escola_nome']) . "</td>";
            echo "<td>" . htmlspecialchars($alocacao['sala_nome']) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($alocacao['data_alocacao'])) . "</td>";
            echo "<td>" . htmlspecialchars($alocacao['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>Nenhuma alocação encontrada!</strong></p>";
    }
    
    // Teste 3: Verificar estrutura da tabela alocacoes_fiscais
    echo "<h3>3. Estrutura da Tabela Alocações</h3>";
    $stmt = $db->query("DESCRIBE alocacoes_fiscais");
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
    
    // Teste 4: Verificar fiscais aprovados
    echo "<h3>4. Fiscais Aprovados</h3>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM fiscais WHERE status = 'aprovado'");
    $result = $stmt->fetch();
    echo "<p><strong>Total de fiscais aprovados:</strong> " . $result['total'] . "</p>";
    
    // Teste 5: Verificar se há fiscais aprovados sem alocação
    $stmt = $db->query("
        SELECT COUNT(*) as total 
        FROM fiscais f 
        LEFT JOIN alocacoes_fiscais a ON f.id = a.fiscal_id AND a.status = 'ativo'
        WHERE f.status = 'aprovado' AND a.id IS NULL
    ");
    $result = $stmt->fetch();
    echo "<p><strong>Fiscais aprovados sem alocação:</strong> " . $result['total'] . "</p>";
    
    // Teste 6: Testar a consulta exata do relatório
    echo "<h3>5. Teste da Consulta do Relatório de Alocações</h3>";
    $sql = "
        SELECT a.*, f.nome as fiscal_nome, f.cpf as fiscal_cpf, f.celular as fiscal_celular,
               c.titulo as concurso_titulo, e.nome as escola_nome, s.nome as sala_nome,
               u.nome as usuario_nome
        FROM alocacoes_fiscais a
        LEFT JOIN fiscais f ON a.fiscal_id = f.id
        LEFT JOIN concursos c ON a.concurso_id = c.id
        LEFT JOIN escolas e ON a.escola_id = e.id
        LEFT JOIN salas s ON a.sala_id = s.id
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.status = 'ativo'
        ORDER BY a.data_alocacao DESC, e.nome, s.nome
    ";
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
echo "<p><a href='admin/relatorio_alocacoes.php' target='_blank'>Abrir Relatório de Alocações</a></p>";
echo "<p><a href='admin/exportar_pdf_alocacoes.php' target='_blank'>Testar Exportação PDF</a></p>";
?> 