<?php
require_once 'config.php';

echo "<h1>Teste de Presença com Filtro por Escola</h1>";

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo "<p style='color: red;'>❌ Usuário não está logado</p>";
    echo "<p><a href='login.php'>Fazer Login</a></p>";
    exit;
}

$db = getDB();
if (!$db) {
    echo "<p style='color: red;'>❌ Erro de conexão com banco de dados</p>";
    exit;
}

echo "<h2>1. Verificando Concursos Ativos</h2>";

try {
    $stmt = $db->query("SELECT id, titulo, data_prova FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC");
    $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($concursos)) {
        echo "<p style='color: green;'>✅ Encontrados " . count($concursos) . " concursos ativos:</p>";
        echo "<ul>";
        foreach ($concursos as $concurso) {
            echo "<li><strong>" . htmlspecialchars($concurso['titulo']) . "</strong> - " . date('d/m/Y', strtotime($concurso['data_prova'])) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhum concurso ativo encontrado</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao buscar concursos: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Verificando Escolas do Concurso 6</h2>";

try {
    $stmt = $db->prepare("SELECT id, nome FROM escolas WHERE concurso_id = 6 AND status = 'ativo' ORDER BY nome");
    $stmt->execute();
    $escolas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($escolas)) {
        echo "<p style='color: green;'>✅ Encontradas " . count($escolas) . " escolas para o concurso 6:</p>";
        echo "<ul>";
        foreach ($escolas as $escola) {
            echo "<li><strong>" . htmlspecialchars($escola['nome']) . "</strong> (ID: " . $escola['id'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhuma escola encontrada para o concurso 6</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao buscar escolas: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Verificando Fiscais Aprovados com Escola e Sala</h2>";

try {
    $stmt = $db->prepare("
        SELECT f.id, f.nome, f.cpf, f.celular, 
               e.nome as escola_nome, s.nome as sala_nome
        FROM fiscais f
        LEFT JOIN escolas e ON f.escola_id = e.id
        LEFT JOIN salas s ON f.sala_id = s.id
        WHERE f.concurso_id = 6 AND f.status = 'aprovado'
        ORDER BY e.nome, s.nome, f.nome
        LIMIT 10
    ");
    $stmt->execute();
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($fiscais)) {
        echo "<p style='color: green;'>✅ Encontrados " . count($fiscais) . " fiscais aprovados (mostrando até 10):</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Nome</th><th>CPF</th><th>Escola</th><th>Sala</th></tr>";
        foreach ($fiscais as $fiscal) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($fiscal['nome']) . "</td>";
            echo "<td>" . formatCPF($fiscal['cpf']) . "</td>";
            echo "<td>" . htmlspecialchars($fiscal['escola_nome'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($fiscal['sala_nome'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhum fiscal aprovado encontrado para o concurso 6</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao buscar fiscais: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Links de Teste</h2>";

echo "<p><strong>Links para testar as funcionalidades:</strong></p>";
echo "<ul>";
echo "<li><a href='presenca_mobile.php' target='_blank'>📱 Página de Seleção de Presença</a></li>";
echo "<li><a href='presenca_treinamento.php?concurso_id=6' target='_blank'>📚 Presença no Treinamento - Concurso 6</a></li>";
echo "<li><a href='presenca_prova.php?concurso_id=6' target='_blank'>📝 Presença na Prova - Concurso 6</a></li>";
if (!empty($escolas)) {
    $primeira_escola = $escolas[0];
    echo "<li><a href='presenca_treinamento.php?concurso_id=6&escola_id=" . $primeira_escola['id'] . "' target='_blank'>📚 Presença no Treinamento - Escola: " . htmlspecialchars($primeira_escola['nome']) . "</a></li>";
    echo "<li><a href='presenca_prova.php?concurso_id=6&escola_id=" . $primeira_escola['id'] . "' target='_blank'>📝 Presença na Prova - Escola: " . htmlspecialchars($primeira_escola['nome']) . "</a></li>";
}
echo "</ul>";

echo "<h2>5. Estatísticas</h2>";

try {
    // Total de fiscais por escola
    $stmt = $db->prepare("
        SELECT e.nome as escola_nome, COUNT(f.id) as total_fiscais
        FROM escolas e
        LEFT JOIN fiscais f ON e.id = f.escola_id AND f.concurso_id = 6 AND f.status = 'aprovado'
        WHERE e.concurso_id = 6 AND e.status = 'ativo'
        GROUP BY e.id, e.nome
        ORDER BY e.nome
    ");
    $stmt->execute();
    $estatisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($estatisticas)) {
        echo "<p><strong>Fiscais por Escola:</strong></p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Escola</th><th>Total de Fiscais</th></tr>";
        foreach ($estatisticas as $estat) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($estat['escola_nome']) . "</td>";
            echo "<td>" . $estat['total_fiscais'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao buscar estatísticas: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Teste concluído!</strong></p>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 