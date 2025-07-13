<?php
require_once 'config.php';

echo "<h1>Teste da Consulta SQL da Presença</h1>";

$db = getDB();
if (!$db) {
    echo "❌ Erro de conexão com banco de dados";
    exit;
}

$concurso_id = 6;

try {
    echo "<h2>Testando consulta SQL para concurso ID: $concurso_id</h2>";
    
    $where_conditions = ["f.concurso_id = ?", "f.status = 'aprovado'"];
    $params = [$concurso_id];
    $where_clause = implode(" AND ", $where_conditions);
    
    $sql = "
        SELECT f.*, 
               e.nome as escola_nome,
               s.nome as sala_nome,
               af.tipo_alocacao,
               af.observacoes as observacoes_alocacao,
               af.id as alocacao_id,
               CASE WHEN pf.presente IS NOT NULL THEN pf.presente ELSE NULL END as presenca_registrada,
               pf.observacoes as observacoes_presenca,
               pf.created_at as data_registro_presenca
        FROM fiscais f
        LEFT JOIN escolas e ON f.escola_id = e.id
        LEFT JOIN salas s ON f.sala_id = s.id
        LEFT JOIN alocacoes_fiscais af ON f.id = af.fiscal_id AND af.status = 'ativo'
        LEFT JOIN presenca_fiscais pf ON f.id = pf.fiscal_id AND pf.concurso_id = f.concurso_id
        WHERE $where_clause
        ORDER BY e.nome, s.nome, f.nome
        LIMIT 5
    ";
    
    echo "<h3>SQL Executado:</h3>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Resultados (Primeiros 5):</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>CPF</th><th>Escola</th><th>Sala</th><th>Tipo Alocação</th><th>Alocação ID</th></tr>";
    
    foreach ($fiscais as $fiscal) {
        echo "<tr>";
        echo "<td>" . $fiscal['id'] . "</td>";
        echo "<td>" . htmlspecialchars($fiscal['nome']) . "</td>";
        echo "<td>" . formatCPF($fiscal['cpf']) . "</td>";
        echo "<td>" . htmlspecialchars($fiscal['escola_nome'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($fiscal['sala_nome'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($fiscal['tipo_alocacao'] ?? 'NULL') . "</td>";
        echo "<td>" . ($fiscal['alocacao_id'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Total de fiscais encontrados: " . count($fiscais) . "</h3>";
    
    if (empty($fiscais)) {
        echo "<p style='color: orange;'>⚠️ Nenhum fiscal encontrado. Verificando se existem fiscais aprovados...</p>";
        
        // Verificar se existem fiscais aprovados
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM fiscais WHERE concurso_id = ? AND status = 'aprovado'");
        $stmt->execute([$concurso_id]);
        $result = $stmt->fetch();
        
        echo "<p>Total de fiscais aprovados no concurso $concurso_id: " . $result['total'] . "</p>";
        
        if ($result['total'] > 0) {
            echo "<p>Verificando se existem alocações ativas...</p>";
            
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM alocacoes_fiscais WHERE status = 'ativo'");
            $stmt->execute();
            $result = $stmt->fetch();
            
            echo "<p>Total de alocações ativas: " . $result['total'] . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 