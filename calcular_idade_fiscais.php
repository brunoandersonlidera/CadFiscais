<?php
require_once 'config.php';

echo "<h1>Calculadora de Idade dos Fiscais</h1>";

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
            idade
        FROM fiscais 
        WHERE data_nascimento IS NOT NULL AND data_nascimento != ''
        ORDER BY id DESC
        LIMIT 10
    ");
    $fiscais_com_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Fiscais com data de nascimento: <strong>" . count($fiscais_com_data) . "</strong></p>";
    
    if (count($fiscais_com_data) > 0) {
        echo "<h3>Últimos fiscais com data de nascimento:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Data Nascimento</th><th>Idade Atual</th><th>Idade Calculada</th></tr>";
        
        foreach ($fiscais_com_data as $fiscal) {
            $data_nasc = new DateTime($fiscal['data_nascimento']);
            $hoje = new DateTime();
            $idade_calculada = $hoje->diff($data_nasc)->y;
            
            echo "<tr>";
            echo "<td>{$fiscal['id']}</td>";
            echo "<td>{$fiscal['nome']}</td>";
            echo "<td>{$fiscal['data_nascimento']}</td>";
            echo "<td>{$fiscal['idade']}</td>";
            echo "<td>{$idade_calculada}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Perguntar se quer calcular
        echo "<h3>Calcular Idade dos Fiscais?</h3>";
        echo "<p>Isso irá calcular a idade baseada na data de nascimento.</p>";
        echo "<button onclick=\"calcularIdade()\" class='btn btn-warning'>Calcular Idade</button>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar fiscais: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Verificar Fiscais sem Data de Nascimento</h2>";
try {
    $stmt = $db->query("
        SELECT COUNT(*) as total 
        FROM fiscais 
        WHERE data_nascimento IS NULL OR data_nascimento = ''
    ");
    $fiscais_sem_data = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>Fiscais sem data de nascimento: <strong>{$fiscais_sem_data}</strong></p>";
    
    if ($fiscais_sem_data > 0) {
        echo "<p style='color: orange;'>⚠️ Há fiscais sem data de nascimento. Eles não terão idade calculada.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar fiscais sem data: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Teste da Distribuição por Idade</h2>";
try {
    $stmt = $db->query("
        SELECT 
            CASE 
                WHEN idade < 25 THEN '18-24'
                WHEN idade < 35 THEN '25-34'
                WHEN idade < 45 THEN '35-44'
                WHEN idade < 55 THEN '45-54'
                ELSE '55+'
            END as faixa_etaria,
            COUNT(*) as quantidade
        FROM fiscais 
        WHERE status = 'ativo' AND idade IS NOT NULL AND idade > 0
        GROUP BY faixa_etaria
        ORDER BY faixa_etaria
    ");
    $faixas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Distribuição atual por idade:</h3>";
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
        echo "<p style='color: orange;'>⚠️ Nenhum fiscal com idade válida encontrado</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro na consulta de faixa etária: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Teste do Dashboard</h2>";
echo "<p><a href='admin/dashboard.php' target='_blank' class='btn btn-primary'>📊 Acessar Dashboard</a></p>";

echo "<script>";
echo "function calcularIdade() {";
echo "    if (confirm('Tem certeza que deseja calcular a idade dos fiscais?')) {";
echo "        fetch('calcular_idade_ajax.php', {";
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