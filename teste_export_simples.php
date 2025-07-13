<?php
require_once 'config.php';

echo "<h1>Teste Simples de Exportação</h1>";

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

echo "<h2>2. Teste de Exportação Direta</h2>";

echo "<p><strong>Links para testar exportação:</strong></p>";
echo "<ul>";
echo "<li><a href='admin/export_direto.php?format=csv' target='_blank'>📄 Exportar CSV</a></li>";
echo "<li><a href='admin/export_direto.php?format=excel' target='_blank'>📊 Exportar Excel</a></li>";
echo "</ul>";

echo "<h2>3. Teste JavaScript</h2>";

echo "<p><strong>Botões de teste:</strong></p>";
echo "<button onclick=\"window.open('admin/export_direto.php?format=csv', '_blank')\" class='btn btn-success'>Exportar CSV</button> ";
echo "<button onclick=\"window.open('admin/export_direto.php?format=excel', '_blank')\" class='btn btn-info'>Exportar Excel</button>";

echo "<h2>4. Página de Fiscais</h2>";

echo "<p><a href='admin/fiscais.php' target='_blank' class='btn btn-primary'>📋 Acessar Página de Fiscais</a></p>";

echo "<h2>5. Debug JavaScript</h2>";

echo "<p><strong>Para debugar os botões:</strong></p>";
echo "<ol>";
echo "<li>Abra a página de fiscais</li>";
echo "<li>Pressione F12 para abrir o console</li>";
echo "<li>Clique nos botões de exportação</li>";
echo "<li>Verifique se aparece 'Iniciando exportação: csv' ou 'Iniciando exportação: excel'</li>";
echo "<li>Se não aparecer, há um erro no JavaScript</li>";
echo "</ol>";

echo "<script>";
echo "console.log('Script de teste carregado');";
echo "function testExport(format) {";
echo "    console.log('Teste de exportação:', format);";
echo "    window.open('admin/export_direto.php?format=' + format, '_blank');";
echo "}";
echo "</script>";

echo "<h2>6. Teste de Função</h2>";
echo "<button onclick=\"testExport('csv')\" class='btn btn-warning'>Teste CSV</button> ";
echo "<button onclick=\"testExport('excel')\" class='btn btn-warning'>Teste Excel</button>";

echo "<hr>";
echo "<p><strong>Teste concluído!</strong></p>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 