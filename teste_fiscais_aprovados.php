<?php
require_once 'config.php';

echo "<h2>Teste do Relatório de Fiscais Aprovados</h2>";

try {
    $db = getDB();
    
    // Teste 1: Verificar se há fiscais aprovados
    echo "<h3>1. Verificação de Fiscais Aprovados</h3>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM fiscais WHERE status = 'aprovado'");
    $result = $stmt->fetch();
    echo "<p><strong>Total de fiscais aprovados:</strong> " . $result['total'] . "</p>";
    
    // Teste 2: Listar fiscais aprovados
    echo "<h3>2. Lista de Fiscais Aprovados</h3>";
    $stmt = $db->query("
        SELECT f.*, c.titulo as concurso_titulo, c.data_prova,
               TIMESTAMPDIFF(YEAR, f.data_nascimento, CURDATE()) as idade,
               a.escola_id, a.sala_id, a.data_alocacao, a.horario_alocacao,
               e.nome as escola_nome, s.nome as sala_nome
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        LEFT JOIN alocacoes_fiscais a ON f.id = a.fiscal_id AND a.status = 'ativo'
        LEFT JOIN escolas e ON a.escola_id = e.id
        LEFT JOIN salas s ON a.sala_id = s.id
        WHERE f.status = 'aprovado'
        ORDER BY f.nome
        LIMIT 10
    ");
    $fiscais = $stmt->fetchAll();
    
    if (count($fiscais) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Nome</th><th>CPF</th><th>Concurso</th><th>Escola</th><th>Sala</th><th>Status Contato</th></tr>";
        
        foreach ($fiscais as $fiscal) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($fiscal['nome']) . "</td>";
            echo "<td>" . htmlspecialchars($fiscal['cpf']) . "</td>";
            echo "<td>" . htmlspecialchars($fiscal['concurso_titulo']) . "</td>";
            echo "<td>" . htmlspecialchars($fiscal['escola_nome'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($fiscal['sala_nome'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($fiscal['status_contato'] ?? 'nao_contatado') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>Nenhum fiscal aprovado encontrado!</strong></p>";
    }
    
    // Teste 3: Verificar estrutura da tabela fiscais
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
    
    // Teste 4: Verificar valores do campo status_contato
    echo "<h3>4. Valores do Campo Status Contato</h3>";
    $stmt = $db->query("SELECT status_contato, COUNT(*) as total FROM fiscais GROUP BY status_contato");
    $status_contatos = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Status Contato</th><th>Quantidade</th></tr>";
    
    foreach ($status_contatos as $status) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($status['status_contato'] ?? 'NULL') . "</td>";
        echo "<td>" . $status['total'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Teste 5: Testar a consulta exata do relatório
    echo "<h3>5. Teste da Consulta do Relatório de Fiscais Aprovados</h3>";
    $sql = "
        SELECT f.*, c.titulo as concurso_titulo, c.data_prova,
               TIMESTAMPDIFF(YEAR, f.data_nascimento, CURDATE()) as idade,
               a.escola_id, a.sala_id, a.data_alocacao, a.horario_alocacao,
               e.nome as escola_nome, s.nome as sala_nome
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        LEFT JOIN alocacoes_fiscais a ON f.id = a.fiscal_id AND a.status = 'ativo'
        LEFT JOIN escolas e ON a.escola_id = e.id
        LEFT JOIN salas s ON a.sala_id = s.id
        WHERE f.status = 'aprovado'
        ORDER BY f.nome
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
echo "<p><a href='admin/relatorio_fiscais_aprovados.php' target='_blank'>Abrir Relatório de Fiscais Aprovados</a></p>";
echo "<p><a href='admin/exportar_pdf_fiscais_aprovados.php' target='_blank'>Testar Exportação PDF</a></p>";
?> 