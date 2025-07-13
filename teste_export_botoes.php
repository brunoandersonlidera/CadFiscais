<?php
require_once 'config.php';

echo "<h1>Teste dos Botões de Exportação</h1>";

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo "<p style='color: red;'>❌ Usuário não está logado</p>";
    echo "<p><a href='login.php'>Fazer Login</a></p>";
    exit;
}

echo "<h2>1. Status do Sistema</h2>";
echo "<p style='color: green;'>✅ Usuário logado</p>";

echo "<h2>2. Teste de Exportação Direta</h2>";
echo "<p><strong>Links diretos para exportação:</strong></p>";
echo "<ul>";
echo "<li><a href='admin/export_direto.php?format=csv' target='_blank'>📄 Exportar CSV (Direto)</a></li>";
echo "<li><a href='admin/export_direto.php?format=excel' target='_blank'>📊 Exportar Excel (Direto)</a></li>";
echo "</ul>";

echo "<h2>3. Teste JavaScript Simples</h2>";
echo "<p><strong>Botões com JavaScript simples:</strong></p>";
echo "<button onclick=\"window.open('admin/export_direto.php?format=csv', '_blank')\" class='btn btn-success'>Exportar CSV</button> ";
echo "<button onclick=\"window.open('admin/export_direto.php?format=excel', '_blank')\" class='btn btn-info'>Exportar Excel</button>";

echo "<h2>4. Teste de Função JavaScript</h2>";
echo "<script>";
echo "function testExport(format) {";
echo "    console.log('Testando exportação:', format);";
echo "    const url = 'admin/export_direto.php?format=' + format;";
echo "    console.log('URL:', url);";
echo "    window.open(url, '_blank');";
echo "}";
echo "</script>";
echo "<button onclick=\"testExport('csv')\" class='btn btn-warning'>Teste CSV</button> ";
echo "<button onclick=\"testExport('excel')\" class='btn btn-warning'>Teste Excel</button>";

echo "<h2>5. Página de Fiscais</h2>";
echo "<p><a href='admin/fiscais.php' target='_blank' class='btn btn-primary'>📋 Acessar Página de Fiscais</a></p>";

echo "<h2>6. Debug dos Botões</h2>";
echo "<p><strong>Para debugar os botões na página de fiscais:</strong></p>";
echo "<ol>";
echo "<li>Abra a página de fiscais</li>";
echo "<li>Pressione F12 para abrir o console</li>";
echo "<li>Clique nos botões de exportação</li>";
echo "<li>Verifique se aparece 'Exportando: admin/export_direto.php?format=csv'</li>";
echo "<li>Se não aparecer, há um erro no JavaScript</li>";
echo "</ol>";

echo "<h2>7. Possíveis Problemas</h2>";
echo "<ul>";
echo "<li><strong>Popup bloqueado:</strong> O navegador pode estar bloqueando popups</li>";
echo "<li><strong>JavaScript desabilitado:</strong> Verificar se o JavaScript está ativo</li>";
echo "<li><strong>Erro na função:</strong> Verificar se há erros no console</li>";
echo "<li><strong>Conflito de funções:</strong> Pode haver conflito com outras funções</li>";
echo "</ul>";

echo "<h2>8. Solução Alternativa</h2>";
echo "<p>Se os botões não funcionarem, use os links diretos:</p>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>Links de exportação direta:</strong></p>";
echo "<p><a href='admin/export_direto.php?format=csv' target='_blank' style='color: #28a745; text-decoration: none;'>📄 Exportar CSV</a></p>";
echo "<p><a href='admin/export_direto.php?format=excel' target='_blank' style='color: #17a2b8; text-decoration: none;'>📊 Exportar Excel</a></p>";
echo "</div>";

echo "<hr>";
echo "<p><strong>Teste concluído!</strong></p>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 