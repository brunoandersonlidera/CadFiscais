<?php
require_once 'config.php';

echo "<h1>Calculadora Automática de Idade</h1>";

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo "<p style='color: red;'>❌ Usuário não está logado</p>";
    echo "<p><a href='login.php'>Fazer Login</a></p>";
    exit;
}

$db = getDB();

echo "<h2>1. Verificar Fiscais com Data de Nascimento</h2>";
try {
    $stmt = $db->query("
        SELECT 
            id,
            nome,
            data_nascimento,
            idade,
            status
        FROM fiscais 
        WHERE data_nascimento IS NOT NULL AND data_nascimento != ''
        ORDER BY id DESC
        LIMIT 10
    ");
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Fiscais com data de nascimento: <strong>" . count($fiscais) . "</strong></p>";
    
    if (count($fiscais) > 0) {
        echo "<h3>Últimos fiscais com data de nascimento:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Data Nascimento</th><th>Idade Atual</th><th>Idade Calculada</th><th>Status</th></tr>";
        
        $hoje = new DateTime();
        foreach ($fiscais as $fiscal) {
            $data_nasc = new DateTime($fiscal['data_nascimento']);
            $idade_calculada = $hoje->diff($data_nasc)->y;
            
            echo "<tr>";
            echo "<td>{$fiscal['id']}</td>";
            echo "<td>{$fiscal['nome']}</td>";
            echo "<td>{$fiscal['data_nascimento']}</td>";
            echo "<td>{$fiscal['idade']}</td>";
            echo "<td>{$idade_calculada}</td>";
            echo "<td>{$fiscal['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Perguntar se quer calcular
        echo "<h3>Calcular Idade dos Fiscais?</h3>";
        echo "<p>Isso irá:</p>";
        echo "<ul>";
        echo "<li>Calcular a idade baseada na data de nascimento</li>";
        echo "<li>Manter todos os status inalterados</li>";
        echo "<li>Considerar fiscais 'aprovado' como ativos para o gráfico</li>";
        echo "</ul>";
        echo "<button onclick=\"calcularIdade()\" class='btn btn-warning'>Calcular Idade dos Fiscais</button>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar fiscais: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Verificar Status dos Fiscais</h2>";
try {
    $stmt = $db->query("
        SELECT 
            status,
            COUNT(*) as quantidade
        FROM fiscais 
        GROUP BY status
    ");
    $status_fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Distribuição por status:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Status</th><th>Quantidade</th></tr>";
    foreach ($status_fiscais as $status) {
        echo "<tr>";
        echo "<td>{$status['status']}</td>";
        echo "<td>{$status['quantidade']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar status: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Teste da Consulta de Faixa Etária (Fiscais Aprovados)</h2>";
try {
    $stmt = $db->query("
        SELECT 
            CASE 
                WHEN idade IS NULL OR idade = 0 OR idade = '' THEN 'Sem Idade'
                WHEN idade < 25 THEN '18-24'
                WHEN idade < 35 THEN '25-34'
                WHEN idade < 45 THEN '35-44'
                WHEN idade < 55 THEN '45-54'
                ELSE '55+'
            END as faixa_etaria,
            COUNT(*) as quantidade
        FROM fiscais 
        WHERE status = 'aprovado'
        GROUP BY faixa_etaria
        ORDER BY 
            CASE 
                WHEN faixa_etaria = 'Sem Idade' THEN 0
                ELSE 1
            END,
            faixa_etaria
    ");
    $faixas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Distribuição por idade (fiscais aprovados):</h3>";
    if (count($faixas) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Faixa Etária</th><th>Quantidade</th></tr>";
        foreach ($faixas as $faixa) {
            echo "<tr>";
            echo "<td>{$faixa['faixa_etaria']}</td>";
            echo "<td>{$faixa['quantidade']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhum fiscal aprovado encontrado</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro na consulta de faixa etária: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Teste do Dashboard</h2>";
echo "<p><a href='admin/dashboard.php' target='_blank' class='btn btn-primary'>📊 Acessar Dashboard</a></p>";

echo "<script>";
echo "function calcularIdade() {";
echo "    if (confirm('Tem certeza que deseja calcular a idade dos fiscais?')) {";
echo "        fetch('calcular_idade_automatico_ajax.php', {";
echo "            method: 'POST'";
echo "        })";
echo "        .then(response => response.json())";
echo "        .then(data => {";
echo "            if (data.success) {";
echo "                alert('Idade calculada com sucesso! ' + data.message);";
echo "                location.reload();";
echo "            } else {";
echo "                alert('Erro: ' + data.message);";
echo "            }";
echo "        })";
echo "        .catch(error => {";
echo "            alert('Erro ao calcular idade');";
echo "        });";
echo "    }";
echo "}";
echo "</script>";

echo "<hr>";
echo "<p><strong>Verificação concluída!</strong></p>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 