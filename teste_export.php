<?php
require_once 'config.php';

echo "<h1>Teste de Exportação</h1>";

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo "<p style='color: red;'>❌ Usuário não está logado</p>";
    echo "<p><a href='login.php'>Fazer Login</a></p>";
    exit;
}

// Verificar se é admin
if (!isAdmin()) {
    echo "<p style='color: red;'>❌ Usuário não é admin</p>";
    exit;
}

echo "<h2>1. Testando exportação CSV</h2>";

try {
    $db = getDB();
    
    // Buscar fiscais
    $stmt = $db->query("
        SELECT 
            id,
            nome,
            email,
            celular,
            cpf,
            data_nascimento,
            idade,
            status,
            endereco,
            observacoes,
            created_at,
            updated_at
        FROM fiscais 
        ORDER BY created_at DESC
        LIMIT 5
    ");
    
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green;'>✅ Busca de fiscais realizada com sucesso</p>";
    echo "<p>Fiscais encontrados: " . count($fiscais) . "</p>";
    
    // Simular exportação CSV
    $output = fopen('php://temp', 'w+');
    
    // Cabeçalho
    fputcsv($output, [
        'ID',
        'Nome',
        'Email',
        'Celular',
        'CPF',
        'Data Nascimento',
        'Idade',
        'Status',
        'Endereço',
        'Observações',
        'Data Cadastro',
        'Última Atualização'
    ]);
    
    // Dados
    foreach ($fiscais as $fiscal) {
        fputcsv($output, [
            $fiscal['id'],
            $fiscal['nome'],
            $fiscal['email'],
            formatPhone($fiscal['celular']),
            formatCPF($fiscal['cpf']),
            date('d/m/Y', strtotime($fiscal['data_nascimento'])),
            $fiscal['idade'],
            ucfirst($fiscal['status']),
            $fiscal['endereco'],
            $fiscal['observacoes'] ?? '',
            date('d/m/Y H:i', strtotime($fiscal['created_at'])),
            date('d/m/Y H:i', strtotime($fiscal['updated_at']))
        ]);
    }
    
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    
    echo "<h3>CSV Gerado (primeiros 500 caracteres):</h3>";
    echo "<pre>" . htmlspecialchars(substr($csv, 0, 500)) . "...</pre>";
    
    echo "<h3>Download do CSV:</h3>";
    echo "<a href='data:text/csv;charset=utf-8," . urlencode($csv) . "' download='fiscais_teste.csv' class='btn btn-success'>Download CSV</a>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Testando requisição POST</h2>";

// Simular requisição POST para export.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['format'] = 'csv';

echo "<p>Método: " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p>Format: " . ($_POST['format'] ?? 'não definido') . "</p>";

echo "<h2>3. Links de teste</h2>";

echo "<p><strong>Links para testar:</strong></p>";
echo "<ul>";
echo "<li><a href='admin/fiscais.php' target='_blank'>📋 Página de Fiscais</a></li>";
echo "<li><a href='admin/export.php' target='_blank'>📤 Página de Exportação (deve dar erro de método)</a></li>";
echo "</ul>";

echo "<h2>4. Solução</h2>";

echo "<p>O problema é que o arquivo <code>admin/export.php</code> só aceita requisições POST, mas quando você acessa diretamente via navegador, é uma requisição GET.</p>";
echo "<p>Os botões na página de fiscais fazem requisições POST corretamente via JavaScript.</p>";

echo "<hr>";
echo "<p><strong>Teste concluído!</strong></p>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 