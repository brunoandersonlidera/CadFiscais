<?php
require_once 'config.php';

echo "<h1>Teste do Gráfico do Dashboard</h1>";

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo "<p style='color: red;'>❌ Usuário não está logado</p>";
    echo "<p><a href='login.php'>Fazer Login</a></p>";
    exit;
}

$db = getDB();

echo "<h2>1. Simular Dados do Dashboard</h2>";
try {
    // Simular a mesma consulta do dashboard
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
    
    echo "<h3>Dados encontrados:</h3>";
    echo "<pre>" . json_encode($faixas, JSON_PRETTY_PRINT) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro na consulta: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Teste do Gráfico (Cópia do Dashboard)</h2>";
echo "<div style='width: 400px; height: 300px; border: 1px solid #ccc; padding: 10px;'>";
echo "<canvas id='idadeChart' width='400' height='200'></canvas>";
echo "</div>";

echo "<h2>3. Script Exato do Dashboard</h2>";
echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>";
echo "<script>";
echo "document.addEventListener('DOMContentLoaded', function() {";
echo "    console.log('Script carregado');";
echo "    const ctx = document.getElementById('idadeChart');";
echo "    if (!ctx) {";
echo "        console.error('Canvas não encontrado');";
echo "        return;";
echo "    }";
echo "    console.log('Canvas encontrado:', ctx);";
echo "    ";
echo "    const data = " . json_encode($faixas ?? []) . ";";
echo "    console.log('Dados:', data);";
echo "    ";
echo "    const labels = data.map(item => item.faixa_etaria);";
echo "    const values = data.map(item => parseInt(item.quantidade));";
echo "    ";
echo "    console.log('Labels:', labels);";
echo "    console.log('Values:', values);";
echo "    ";
echo "    if (typeof Chart === 'undefined') {";
echo "        console.error('Chart.js não está carregado');";
echo "        return;";
echo "    }";
echo "    ";
echo "    try {";
echo "        new Chart(ctx, {";
echo "            type: 'doughnut',";
echo "            data: {";
echo "                labels: labels,";
echo "                datasets: [{";
echo "                    data: values,";
echo "                    backgroundColor: [";
echo "                        '#3498db',";
echo "                        '#2ecc71',";
echo "                        '#f39c12',";
echo "                        '#e74c3c',";
echo "                        '#9b59b6',";
echo "                        '#95a5a6'";
echo "                    ],";
echo "                    borderWidth: 2,";
echo "                    borderColor: '#fff'";
echo "                }]";
echo "            },";
echo "            options: {";
echo "                responsive: true,";
echo "                maintainAspectRatio: false,";
echo "                plugins: {";
echo "                    legend: {";
echo "                        position: 'bottom',";
echo "                        labels: {";
echo "                            padding: 20,";
echo "                            usePointStyle: true";
echo "                        }";
echo "                    }";
echo "                }";
echo "            }";
echo "        });";
echo "        console.log('Gráfico criado com sucesso');";
echo "    } catch (error) {";
echo "        console.error('Erro ao criar gráfico:', error);";
echo "    }";
echo "});";
echo "</script>";

echo "<h2>4. Debug</h2>";
echo "<p><strong>Para debugar:</strong></p>";
echo "<ol>";
echo "<li>Abra o console do navegador (F12)</li>";
echo "<li>Recarregue a página</li>";
echo "<li>Verifique se aparecem as mensagens de debug</li>";
echo "<li>Se houver erro, verifique a mensagem</li>";
echo "</ol>";

echo "<h2>5. Possíveis Problemas</h2>";
echo "<ul>";
echo "<li><strong>Chart.js não carregado:</strong> Verificar se a CDN está acessível</li>";
echo "<li><strong>Canvas não encontrado:</strong> Verificar se o ID está correto</li>";
echo "<li><strong>Dados vazios:</strong> Verificar se há fiscais cadastrados</li>";
echo "<li><strong>Erro JavaScript:</strong> Verificar console para erros</li>";
echo "</ul>";

echo "<h2>6. Teste do Dashboard Real</h2>";
echo "<p><a href='admin/dashboard.php' target='_blank' class='btn btn-primary'>📊 Acessar Dashboard</a></p>";

echo "<h2>7. Verificar Dados</h2>";
echo "<p><a href='verificar_idade_fiscais.php' class='btn btn-warning'>🔍 Verificar Dados</a></p>";

echo "<hr>";
echo "<p><strong>Teste concluído!</strong></p>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 