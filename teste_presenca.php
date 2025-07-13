<?php
require_once 'config.php';

echo "<h1>Teste das Funcionalidades de Presença</h1>";

// Verificar se as tabelas existem
$db = getDB();
if (!$db) {
    echo "<p style='color: red;'>Erro: Não foi possível conectar ao banco de dados</p>";
    exit;
}

echo "<h2>1. Verificando Tabelas de Presença</h2>";

// Verificar tabela presenca_treinamento
try {
    $stmt = $db->query("SHOW TABLES LIKE 'presenca_treinamento'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Tabela 'presenca_treinamento' existe</p>";
        
        // Contar registros
        $stmt = $db->query("SELECT COUNT(*) as total FROM presenca_treinamento");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>Total de registros de presença no treinamento: $total</p>";
    } else {
        echo "<p style='color: red;'>❌ Tabela 'presenca_treinamento' não existe</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao verificar tabela presenca_treinamento: " . $e->getMessage() . "</p>";
}

// Verificar tabela presenca_fiscais
try {
    $stmt = $db->query("SHOW TABLES LIKE 'presenca_fiscais'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Tabela 'presenca_fiscais' existe</p>";
        
        // Contar registros
        $stmt = $db->query("SELECT COUNT(*) as total FROM presenca_fiscais");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>Total de registros de presença na prova: $total</p>";
    } else {
        echo "<p style='color: red;'>❌ Tabela 'presenca_fiscais' não existe</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao verificar tabela presenca_fiscais: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Verificando Concursos Ativos</h2>";

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

echo "<h2>3. Verificando Fiscais Aprovados</h2>";

try {
    $stmt = $db->query("
        SELECT f.id, f.nome, f.cpf, c.titulo as concurso_titulo
        FROM fiscais f
        JOIN concursos c ON f.concurso_id = c.id
        WHERE f.status = 'aprovado' AND c.status = 'ativo'
        ORDER BY f.nome
        LIMIT 10
    ");
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($fiscais)) {
        echo "<p style='color: green;'>✅ Encontrados " . count($fiscais) . " fiscais aprovados (mostrando até 10):</p>";
        echo "<ul>";
        foreach ($fiscais as $fiscal) {
            echo "<li><strong>" . htmlspecialchars($fiscal['nome']) . "</strong> - " . htmlspecialchars($fiscal['concurso_titulo']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhum fiscal aprovado encontrado</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao buscar fiscais: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Links de Teste</h2>";

echo "<p><strong>Links para testar as funcionalidades:</strong></p>";
echo "<ul>";
echo "<li><a href='presenca_mobile.php' target='_blank'>📱 Página de Seleção de Presença</a></li>";
echo "<li><a href='presenca_treinamento.php' target='_blank'>📚 Presença no Treinamento</a></li>";
echo "<li><a href='presenca_prova.php' target='_blank'>📝 Presença na Prova</a></li>";
echo "<li><a href='index.php' target='_blank'>🏠 Dashboard Principal</a></li>";
echo "</ul>";

echo "<h2>5. Estatísticas do Sistema</h2>";

try {
    // Total de fiscais
    $stmt = $db->query("SELECT COUNT(*) as total FROM fiscais WHERE status = 'aprovado'");
    $total_fiscais = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de concursos
    $stmt = $db->query("SELECT COUNT(*) as total FROM concursos WHERE status = 'ativo'");
    $total_concursos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Fiscais aprovados
    $stmt = $db->query("SELECT COUNT(*) as total FROM fiscais WHERE status = 'aprovado'");
    $fiscais_aprovados = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Métrica</th><th>Valor</th></tr>";
    echo "<tr><td>Total de Fiscais Ativos</td><td>$total_fiscais</td></tr>";
    echo "<tr><td>Fiscais Aprovados</td><td>$fiscais_aprovados</td></tr>";
    echo "<tr><td>Concursos Ativos</td><td>$total_concursos</td></tr>";
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao buscar estatísticas: " . $e->getMessage() . "</p>";
}

echo "<h2>6. Verificação de Usuário Logado</h2>";

if (isLoggedIn()) {
    echo "<p style='color: green;'>✅ Usuário está logado</p>";
    echo "<p><strong>Nome:</strong> " . htmlspecialchars($_SESSION['user_name'] ?? 'N/A') . "</p>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($_SESSION['user_email'] ?? 'N/A') . "</p>";
    echo "<p><strong>Tipo:</strong> " . ($_SESSION['user_type'] == 1 ? 'Administrador' : 'Usuário') . "</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Usuário não está logado</p>";
    echo "<p><a href='login.php'>Fazer Login</a></p>";
}

echo "<hr>";
echo "<p><strong>Teste concluído!</strong></p>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 