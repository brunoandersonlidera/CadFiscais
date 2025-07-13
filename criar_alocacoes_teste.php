<?php
require_once 'config.php';

echo "<h2>Criar Alocações de Teste</h2>";

try {
    $db = getDB();
    
    // Verificar se há alocações
    $stmt = $db->query("SELECT COUNT(*) as total FROM alocacoes_fiscais WHERE status = 'ativo'");
    $result = $stmt->fetch();
    $total_alocacoes = $result['total'];
    
    echo "<p><strong>Alocações ativas encontradas:</strong> " . $total_alocacoes . "</p>";
    
    if ($total_alocacoes == 0) {
        echo "<h3>Criando alocações de teste...</h3>";
        
        // Buscar fiscais aprovados
        $stmt = $db->query("SELECT id, nome FROM fiscais WHERE status = 'aprovado' LIMIT 5");
        $fiscais = $stmt->fetchAll();
        
        // Buscar escolas
        $stmt = $db->query("SELECT id, nome FROM escolas WHERE status = 'ativo' LIMIT 3");
        $escolas = $stmt->fetchAll();
        
        // Buscar salas
        $stmt = $db->query("SELECT id, nome, escola_id FROM salas WHERE status = 'ativo' LIMIT 5");
        $salas = $stmt->fetchAll();
        
        // Buscar concursos
        $stmt = $db->query("SELECT id, titulo FROM concursos WHERE status = 'ativo' LIMIT 2");
        $concursos = $stmt->fetchAll();
        
        if (count($fiscais) > 0 && count($escolas) > 0 && count($salas) > 0 && count($concursos) > 0) {
            $alocacoes_criadas = 0;
            
            foreach ($fiscais as $fiscal) {
                // Selecionar escola e sala aleatoriamente
                $escola = $escolas[array_rand($escolas)];
                $salas_escola = array_filter($salas, function($s) use ($escola) {
                    return $s['escola_id'] == $escola['id'];
                });
                
                if (!empty($salas_escola)) {
                    $sala = array_values($salas_escola)[array_rand($salas_escola)];
                    $concurso = $concursos[array_rand($concursos)];
                    
                    // Criar alocação
                    $stmt = $db->prepare("
                        INSERT INTO alocacoes_fiscais 
                        (fiscal_id, concurso_id, escola_id, sala_id, tipo_alocacao, 
                         data_alocacao, horario_alocacao, observacoes, status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                    ");
                    
                    $result = $stmt->execute([
                        $fiscal['id'],
                        $concurso['id'],
                        $escola['id'],
                        $sala['id'],
                        'prova',
                        date('Y-m-d'),
                        '07:00',
                        'Alocação de teste criada automaticamente',
                        'ativo'
                    ]);
                    
                    if ($result) {
                        $alocacoes_criadas++;
                        echo "<p style='color: green;'>✓ Alocação criada para " . htmlspecialchars($fiscal['nome']) . 
                             " na escola " . htmlspecialchars($escola['nome']) . 
                             " sala " . htmlspecialchars($sala['nome']) . "</p>";
                    }
                }
            }
            
            echo "<p><strong>Total de alocações criadas:</strong> " . $alocacoes_criadas . "</p>";
            
        } else {
            echo "<p style='color: red;'><strong>Erro:</strong> Não há dados suficientes para criar alocações.</p>";
            echo "<p>Fiscais aprovados: " . count($fiscais) . "</p>";
            echo "<p>Escolas: " . count($escolas) . "</p>";
            echo "<p>Salas: " . count($salas) . "</p>";
            echo "<p>Concursos: " . count($concursos) . "</p>";
        }
        
    } else {
        echo "<p style='color: green;'><strong>✓ Já existem alocações cadastradas!</strong></p>";
    }
    
    // Mostrar alocações atuais
    echo "<h3>Alocações Atuais:</h3>";
    $stmt = $db->query("
        SELECT a.*, f.nome as fiscal_nome, c.titulo as concurso_titulo, 
               e.nome as escola_nome, s.nome as sala_nome
        FROM alocacoes_fiscais a
        LEFT JOIN fiscais f ON a.fiscal_id = f.id
        LEFT JOIN concursos c ON a.concurso_id = c.id
        LEFT JOIN escolas e ON a.escola_id = e.id
        LEFT JOIN salas s ON a.sala_id = s.id
        WHERE a.status = 'ativo'
        ORDER BY a.created_at DESC
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
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Erro:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='teste_alocacoes.php'>Testar Relatório de Alocações</a></p>";
echo "<p><a href='admin/relatorio_alocacoes.php'>Abrir Relatório de Alocações</a></p>";
?> 