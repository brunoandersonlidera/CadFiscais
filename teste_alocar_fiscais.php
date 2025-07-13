<?php
require_once 'config.php';

echo "<h1>Teste da Nova Página de Alocar Fiscais</h1>";

// Verificar se o arquivo existe
if (file_exists('admin/alocar_fiscais.php')) {
    echo "<p style='color: green;'>✅ Arquivo admin/alocar_fiscais.php existe</p>";
} else {
    echo "<p style='color: red;'>❌ Arquivo admin/alocar_fiscais.php não existe</p>";
}

// Verificar se há erros de sintaxe
$output = shell_exec('php -l admin/alocar_fiscais.php 2>&1');
if (strpos($output, 'No syntax errors') !== false) {
    echo "<p style='color: green;'>✅ Arquivo admin/alocar_fiscais.php não tem erros de sintaxe</p>";
} else {
    echo "<p style='color: red;'>❌ Erro de sintaxe no arquivo admin/alocar_fiscais.php:</p>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
}

// Testar conexão com banco de dados
echo "<h2>Teste de Conexão com Banco de Dados</h2>";

try {
    $db = getDB();
    if ($db) {
        echo "<p style='color: green;'>✅ Conexão com banco de dados OK</p>";
        
        // Testar consulta de fiscais aprovados
        $stmt = $db->query("
            SELECT COUNT(*) as total 
            FROM fiscais 
            WHERE status = 'aprovado'
        ");
        $total_fiscais = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>Total de fiscais aprovados: $total_fiscais</p>";
        
        // Testar consulta de alocações
        $stmt = $db->query("
            SELECT COUNT(*) as total 
            FROM alocacoes_fiscais 
            WHERE status = 'ativo'
        ");
        $total_alocacoes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>Total de alocações ativas: $total_alocacoes</p>";
        
        // Testar consulta completa
        $stmt = $db->query("
            SELECT 
                f.id,
                f.nome,
                f.status_alocacao,
                a.escola_id,
                e.nome as escola_nome
            FROM fiscais f
            LEFT JOIN alocacoes_fiscais a ON f.id = a.fiscal_id AND a.status = 'ativo'
            LEFT JOIN escolas e ON a.escola_id = e.id
            WHERE f.status = 'aprovado'
            LIMIT 5
        ");
        $fiscais_teste = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Fiscais para teste (primeiros 5): " . count($fiscais_teste) . "</p>";
        
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

echo "<h2>Comparação das Páginas</h2>";

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Página</th><th>URL</th><th>Função</th></tr>";
echo "<tr>";
echo "<td>Listar Fiscais</td>";
echo "<td><a href='http://localhost:8000/admin/fiscais.php' target='_blank'>admin/fiscais.php</a></td>";
echo "<td>Lista todos os fiscais com opções de edição, exclusão, etc.</td>";
echo "</tr>";
echo "<tr>";
echo "<td>Alocar Fiscais (Nova)</td>";
echo "<td><a href='http://localhost:8000/admin/alocar_fiscais.php' target='_blank'>admin/alocar_fiscais.php</a></td>";
echo "<td>Lista fiscais aprovados com foco em alocação, mostra escola/sala, status de alocação</td>";
echo "</tr>";
echo "<tr>";
echo "<td>Alocar Fiscal (Individual)</td>";
echo "<td><a href='http://localhost:8000/admin/alocar_fiscal.php?id=1' target='_blank'>admin/alocar_fiscal.php</a></td>";
echo "<td>Formulário para alocar um fiscal específico</td>";
echo "</tr>";
echo "</table>";

echo "<h2>Funcionalidades da Nova Página</h2>";

echo "<ul>";
echo "<li><strong>Filtros:</strong> Por concurso e status de alocação</li>";
echo "<li><strong>Estatísticas:</strong> Total de fiscais, alocados, não alocados, mulheres</li>";
echo "<li><strong>Informações de Alocação:</strong> Escola, sala, data, horário, tipo</li>";
echo "<li><strong>WhatsApp:</strong> Botão para contato direto</li>";
echo "<li><strong>Ações:</strong> Alocar, re-alocar, ver detalhes, editar</li>";
echo "<li><strong>Status Visual:</strong> Badges coloridos para status de alocação</li>";
echo "</ul>";

echo "<h2>Links de Teste</h2>";

echo "<p><strong>Links para testar:</strong></p>";
echo "<ul>";
echo "<li><a href='http://localhost:8000/admin/alocar_fiscais.php' target='_blank'>📋 Nova Página de Alocar Fiscais</a></li>";
echo "<li><a href='http://localhost:8000/admin/fiscais.php' target='_blank'>👥 Página Original de Fiscais</a></li>";
echo "<li><a href='http://localhost:8000/admin/alocar_fiscal.php?id=1' target='_blank'>🔧 Alocar Fiscal Individual</a></li>";
echo "</ul>";

echo "<h2>Resumo das Mudanças</h2>";

echo "<p><strong>Problema:</strong> Menu 'Alocar Fiscais' chamava a mesma página de 'Listar Fiscais'</p>";
echo "<p><strong>Solução:</strong> Criada nova página específica para alocação com:</p>";
echo "<ul>";
echo "<li>Foco em fiscais aprovados</li>";
echo "<li>Informações de alocação (escola, sala, data)</li>";
echo "<li>Status visual de alocação</li>";
echo "<li>Botão WhatsApp para contato</li>";
echo "<li>Filtros específicos para alocação</li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>Teste concluído!</strong></p>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 