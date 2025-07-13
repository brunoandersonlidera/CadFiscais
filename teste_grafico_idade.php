<?php
require_once 'config.php';

echo "<h1>Teste do Gráfico de Idade</h1>";

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo "<p style='color: red;'>❌ Usuário não está logado</p>";
    echo "<p><a href='login.php'>Fazer Login</a></p>";
    exit;
}

$db = getDB();

echo "<h2>1. Dados de Faixa Etária</h2>";
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
        WHERE status = 'ativo'
        GROUP BY faixa_etaria
        ORDER BY 
            CASE 
                WHEN faixa_etaria = 'Sem Idade' THEN 0
                ELSE 1
            END,
            faixa_etaria
    ");
    $faixas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Dados para o gráfico:</h3>";
    echo "<pre>" . json_encode($faixas, JSON_PRETTY_PRINT) . "</pre>";
    
    if (count($faixas) > 0) {
        echo "<h3>Tabela de dados:</h3>";
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
        echo "<p style='color: orange;'>⚠️ Nenhum dado encontrado</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro na consulta: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Teste do Gráfico</h2>";
echo "<div style='width: 400px; height: 300px;'>";
echo "<canvas id='testeChart'></canvas>";
echo "</div>";

echo "<h2>3. Script do Gráfico</h2>";
echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>";
echo "<script>";
echo "document.addEventListener('DOMContentLoaded', function() {";
echo "    const ctx = document.getElementById('testeChart').getContext('2d');";
echo "    const data = " . json_encode($faixas ?? []) . ";";
echo "    const labels = data.map(item => item.faixa_etaria);";
echo "    const values = data.map(item => parseInt(item.quantidade));";
echo "    ";
echo "    console.log('Labels:', labels);";
echo "    console.log('Values:', values);";
echo "    ";
echo "    new Chart(ctx, {";
echo "        type: 'doughnut',";
echo "        data: {";
echo "            labels: labels,";
echo "            datasets: [{";
echo "                data: values,";
echo "                backgroundColor: [";
echo "                    '#3498db',";
echo "                    '#2ecc71',";
echo "                    '#f39c12',";
echo "                    '#e74c3c',";
echo "                    '#9b59b6',";
echo "                    '#95a5a6'";
echo "                ],";
echo "                borderWidth: 2,";
echo "                borderColor: '#fff'";
echo "            }]";
echo "        },";
echo "        options: {";
echo "            responsive: true,";
echo "            maintainAspectRatio: false,";
echo "            plugins: {";
echo "                legend: {";
echo "                    position: 'bottom',";
echo "                    labels: {";
echo "                        padding: 20,";
echo "                        usePointStyle: true";
echo "                    }";
echo "                }";
echo "            }";
echo "        }";
echo "    });";
echo "});";
echo "</script>";

echo "<h2>4. Debug JavaScript</h2>";
echo "<p><strong>Para debugar:</strong></p>";
echo "<ol>";
echo "<li>Abra o console do navegador (F12)</li>";
echo "<li>Verifique se aparecem os logs: 'Labels:' e 'Values:'</li>";
echo "<li>Se não aparecer, há um erro no JavaScript</li>";
echo "<li>Se aparecer mas o gráfico não renderizar, pode ser problema com Chart.js</li>";
echo "</ol>";

echo "<h2>5. Teste do Dashboard</h2>";
echo "<p><a href='admin/dashboard.php' target='_blank' class='btn btn-primary'>📊 Acessar Dashboard</a></p>";

echo "<h2>6. Calcular Idade (se necessário)</h2>";
echo "<p><a href='calcular_idade_fiscais.php' class='btn btn-warning'>🧮 Calcular Idade dos Fiscais</a></p>";

echo "<hr>";
echo "<p><strong>Teste concluído!</strong></p>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 