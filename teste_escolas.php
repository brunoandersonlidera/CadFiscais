<?php
require_once 'config.php';

echo "<h1>Teste da Página de Escolas</h1>";

// Verificar se o arquivo escolas.php existe
if (file_exists('admin/escolas.php')) {
    echo "<p style='color: green;'>✅ Arquivo admin/escolas.php existe</p>";
} else {
    echo "<p style='color: red;'>❌ Arquivo admin/escolas.php não existe</p>";
}

// Verificar se há erros de sintaxe
$output = shell_exec('php -l admin/escolas.php 2>&1');
if (strpos($output, 'No syntax errors') !== false) {
    echo "<p style='color: green;'>✅ Arquivo admin/escolas.php não tem erros de sintaxe</p>";
} else {
    echo "<p style='color: red;'>❌ Erro de sintaxe no arquivo admin/escolas.php:</p>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
}

// Verificar se a função formatPhone está definida apenas uma vez
echo "<h2>Verificação de Funções Duplicadas</h2>";

$config_content = file_get_contents('config.php');
$escolas_content = file_get_contents('admin/escolas.php');

$config_formatPhone_count = substr_count($config_content, 'function formatPhone');
$escolas_formatPhone_count = substr_count($escolas_content, 'function formatPhone');

echo "<p>Função formatPhone em config.php: $config_formatPhone_count</p>";
echo "<p>Função formatPhone em admin/escolas.php: $escolas_formatPhone_count</p>";

if ($config_formatPhone_count == 1 && $escolas_formatPhone_count == 0) {
    echo "<p style='color: green;'>✅ Função formatPhone está definida apenas uma vez (em config.php)</p>";
} else {
    echo "<p style='color: red;'>❌ Problema com função formatPhone duplicada</p>";
}

// Testar conexão com banco de dados
echo "<h2>Teste de Conexão com Banco de Dados</h2>";

try {
    $db = getDB();
    if ($db) {
        echo "<p style='color: green;'>✅ Conexão com banco de dados OK</p>";
        
        // Testar consulta de escolas
        $stmt = $db->query("SELECT COUNT(*) as total FROM escolas");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>Total de escolas no banco: $total</p>";
    } else {
        echo "<p style='color: red;'>❌ Erro na conexão com banco de dados</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao conectar com banco de dados: " . $e->getMessage() . "</p>";
}

// Verificar se o usuário está logado
echo "<h2>Verificação de Login</h2>";

if (isLoggedIn()) {
    echo "<p style='color: green;'>✅ Usuário está logado</p>";
    echo "<p><strong>Nome:</strong> " . htmlspecialchars($_SESSION['user_name'] ?? 'N/A') . "</p>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($_SESSION['user_email'] ?? 'N/A') . "</p>";
    echo "<p><strong>Tipo:</strong> " . ($_SESSION['user_type'] == 1 ? 'Administrador' : 'Usuário') . "</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Usuário não está logado</p>";
    echo "<p><a href='login.php'>Fazer Login</a></p>";
}

echo "<h2>Links de Teste</h2>";

echo "<p><strong>Links para testar:</strong></p>";
echo "<ul>";
echo "<li><a href='http://localhost:8000/admin/escolas.php' target='_blank'>📚 Página de Escolas</a></li>";
echo "<li><a href='http://localhost:8000/admin/concursos.php' target='_blank'>📋 Página de Concursos</a></li>";
echo "<li><a href='http://localhost:8000/admin/dashboard.php' target='_blank'>🏠 Dashboard</a></li>";
echo "</ul>";

echo "<h2>Resumo da Correção</h2>";

echo "<p><strong>Problema:</strong> Função formatPhone() duplicada entre admin/escolas.php e config.php</p>";
echo "<p><strong>Solução:</strong> Removida a função duplicada do arquivo admin/escolas.php</p>";
echo "<p><strong>Resultado:</strong> A página de escolas agora deve carregar sem erros</p>";

echo "<hr>";
echo "<p><strong>Teste concluído!</strong></p>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 