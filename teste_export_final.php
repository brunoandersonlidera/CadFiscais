<?php
require_once 'config.php';

echo "<h1>Teste Final de Exportação</h1>";

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

echo "<h2>1. Status do Sistema</h2>";
echo "<p style='color: green;'>✅ Usuário logado e é admin</p>";

$db = getDB();
if ($db) {
    echo "<p style='color: green;'>✅ Conexão com banco de dados OK</p>";
    
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM fiscais");
        $result = $stmt->fetch();
        echo "<p>Total de fiscais: " . $result['total'] . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro ao contar fiscais: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Erro na conexão com banco de dados</p>";
}

echo "<h2>2. Links de Exportação</h2>";

echo "<p><strong>Links para testar exportação:</strong></p>";
echo "<ul>";
echo "<li><a href='admin/export_direto.php?format=csv' target='_blank'>📄 Exportar CSV Direto</a></li>";
echo "<li><a href='admin/export_direto.php?format=excel' target='_blank'>📊 Exportar Excel Direto</a></li>";
echo "</ul>";

echo "<h2>3. Página de Fiscais</h2>";

echo "<p><a href='admin/fiscais.php' target='_blank' class='btn btn-primary'>📋 Acessar Página de Fiscais</a></p>";
echo "<p><strong>Instruções:</strong></p>";
echo "<ol>";
echo "<li>Acesse a página de fiscais</li>";
echo "<li>Clique nos botões 'Exportar CSV' ou 'Exportar Excel'</li>";
echo "<li>Se os botões não funcionarem, use os links diretos acima</li>";
echo "</ol>";

echo "<h2>4. Solução Implementada</h2>";

echo "<p><strong>Problema identificado:</strong></p>";
echo "<ul>";
echo "<li>O arquivo <code>admin/export.php</code> só aceita requisições POST</li>";
echo "<li>Quando acessado diretamente via navegador, é uma requisição GET</li>";
echo "<li>Os botões JavaScript fazem POST corretamente, mas podem falhar</li>";
echo "</ul>";

echo "<p><strong>Solução implementada:</strong></p>";
echo "<ul>";
echo "<li>Criado <code>admin/export_direto.php</code> que aceita GET e POST</li>";
echo "<li>Modificado JavaScript para usar fallback se POST falhar</li>";
echo "<li>Adicionado download direto como alternativa</li>";
echo "</ul>";

echo "<h2>5. Teste dos Botões</h2>";

echo "<p><strong>Para testar os botões na página de fiscais:</strong></p>";
echo "<ol>";
echo "<li>Abra o console do navegador (F12)</li>";
echo "<li>Acesse a página de fiscais</li>";
echo "<li>Clique nos botões de exportação</li>";
echo "<li>Verifique se há erros no console</li>";
echo "<li>Se houver erros, os links diretos funcionarão</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>Teste concluído!</strong></p>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 