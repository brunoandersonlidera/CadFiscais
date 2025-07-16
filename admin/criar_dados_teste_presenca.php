<?php
require_once '../config.php';

echo "<h1>🔧 Criar Dados de Teste - Presença</h1>";

$db = getDB();
if (!$db) {
    echo "❌ Erro na conexão com banco<br>";
    exit;
}

try {
    // Verificar se existem fiscais
    $stmt = $db->query("SELECT COUNT(*) as total FROM fiscais WHERE status = 'aprovado'");
    $result = $stmt->fetch();
    $total_fiscais = $result['total'];
    
    echo "<p>Total de fiscais aprovados: <strong>{$total_fiscais}</strong></p>";
    
    if ($total_fiscais == 0) {
        echo "<p style='color: orange;'>⚠️ Não há fiscais aprovados para criar dados de teste.</p>";
        echo "<p>Crie alguns fiscais primeiro e depois execute este script novamente.</p>";
        exit;
    }
    
    // Buscar fiscais aprovados
    $stmt = $db->query("SELECT id, concurso_id FROM fiscais WHERE status = 'aprovado' LIMIT 10");
    $fiscais = $stmt->fetchAll();
    
    echo "<p>Inserindo dados de presença para " . count($fiscais) . " fiscais...</p>";
    
    // Limpar dados de presença existentes para evitar duplicatas
    $db->exec("DELETE FROM presenca WHERE fiscal_id IN (" . implode(',', array_column($fiscais, 'id')) . ")");
    echo "<p>✅ Dados de presença anteriores removidos</p>";
    
    // Inserir dados de teste
    $stmt = $db->prepare("
        INSERT INTO presenca (fiscal_id, concurso_id, data_presenca, tipo_presenca, status, observacoes, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $status_options = ['presente', 'ausente', 'justificado'];
    $observacoes = ['', 'Chegou no horário', 'Atrasou 10 minutos', 'Justificativa médica', 'Problema de transporte'];
    
    foreach ($fiscais as $index => $fiscal) {
        // Presença no treinamento
        $status_treinamento = $status_options[array_rand($status_options)];
        $obs_treinamento = $observacoes[array_rand($observacoes)];
        
        $stmt->execute([
            $fiscal['id'],
            $fiscal['concurso_id'],
            date('Y-m-d', strtotime('-2 days')), // 2 dias atrás
            'treinamento',
            $status_treinamento,
            $obs_treinamento
        ]);
        
        // Presença na prova
        $status_prova = $status_options[array_rand($status_options)];
        $obs_prova = $observacoes[array_rand($observacoes)];
        
        $stmt->execute([
            $fiscal['id'],
            $fiscal['concurso_id'],
            date('Y-m-d'), // Hoje
            'prova',
            $status_prova,
            $obs_prova
        ]);
        
        echo "<p>✅ Fiscal ID {$fiscal['id']}: Treinamento ({$status_treinamento}), Prova ({$status_prova})</p>";
    }
    
    echo "<h2>✅ Dados de teste criados com sucesso!</h2>";
    echo "<p>Agora você pode testar o relatório de comparecimento em: <a href='relatorio_comparecimento.php'>Relatório de Comparecimento</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?> 