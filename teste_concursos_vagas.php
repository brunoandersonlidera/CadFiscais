<?php
require_once 'config.php';

$db = getDB();

echo "<h1>Teste de Contagem de Fiscais Aprovados</h1>";

// Teste 1: Contar fiscais aprovados por concurso
echo "<h2>1. Fiscais Aprovados por Concurso</h2>";
try {
    $stmt = $db->query("
        SELECT 
            c.id,
            c.titulo,
            c.vagas_disponiveis,
            COUNT(f.id) as fiscais_aprovados,
            (c.vagas_disponiveis - COUNT(f.id)) as vagas_restantes
        FROM concursos c
        LEFT JOIN fiscais f ON c.id = f.concurso_id AND f.status = 'aprovado'
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Título</th><th>Vagas Disponíveis</th><th>Fiscais Aprovados</th><th>Vagas Restantes</th></tr>";
    
    foreach ($concursos as $concurso) {
        echo "<tr>";
        echo "<td>{$concurso['id']}</td>";
        echo "<td>" . htmlspecialchars($concurso['titulo']) . "</td>";
        echo "<td>{$concurso['vagas_disponiveis']}</td>";
        echo "<td>{$concurso['fiscais_aprovados']}</td>";
        echo "<td>{$concurso['vagas_restantes']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}

// Teste 2: Verificar todos os status de fiscais
echo "<h2>2. Status dos Fiscais</h2>";
try {
    $stmt = $db->query("
        SELECT 
            status,
            COUNT(*) as total
        FROM fiscais 
        GROUP BY status
        ORDER BY total DESC
    ");
    $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Status</th><th>Total</th></tr>";
    
    foreach ($status_counts as $status) {
        echo "<tr>";
        echo "<td>{$status['status']}</td>";
        echo "<td>{$status['total']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}

// Teste 3: Verificar fiscais por concurso com todos os status
echo "<h2>3. Fiscais por Concurso (Todos os Status)</h2>";
try {
    $stmt = $db->query("
        SELECT 
            c.id,
            c.titulo,
            f.status,
            COUNT(f.id) as total
        FROM concursos c
        LEFT JOIN fiscais f ON c.id = f.concurso_id
        GROUP BY c.id, f.status
        ORDER BY c.id, f.status
    ");
    $fiscais_por_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Concurso ID</th><th>Título</th><th>Status</th><th>Total</th></tr>";
    
    foreach ($fiscais_por_status as $fiscal) {
        echo "<tr>";
        echo "<td>{$fiscal['id']}</td>";
        echo "<td>" . htmlspecialchars($fiscal['titulo']) . "</td>";
        echo "<td>{$fiscal['status']}</td>";
        echo "<td>{$fiscal['total']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Resumo da Correção</h2>";
echo "<p><strong>Problema identificado:</strong> A consulta SQL estava usando <code>f.status = 'ativo'</code> em vez de <code>f.status = 'aprovado'</code>.</p>";
echo "<p><strong>Solução aplicada:</strong> Alterado o filtro na consulta SQL para contar apenas fiscais com status 'aprovado'.</p>";
echo "<p><strong>Resultado esperado:</strong> A coluna 'Vagas' agora deve mostrar o número correto de fiscais aprovados para cada concurso.</p>";

echo "<h2>5. Link para Teste</h2>";
echo "<p><a href='http://localhost:8000/admin/concursos.php' target='_blank'>Acessar página de concursos</a></p>";
?> 