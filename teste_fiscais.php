<?php
require_once 'config.php';

echo "<h1>Teste da Página de Fiscais</h1>";

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

$db = getDB();
if (!$db) {
    echo "<p style='color: red;'>❌ Erro de conexão com banco de dados</p>";
    exit;
}

echo "<h2>1. Testando busca de fiscais</h2>";

try {
    $stmt = $db->query("
        SELECT f.id, f.nome, f.email, f.celular, f.whatsapp, f.cpf, f.data_nascimento, 
               f.status, f.created_at, f.observacoes, f.concurso_id,
               c.titulo as concurso_titulo,
               TIMESTAMPDIFF(YEAR, f.data_nascimento, CURDATE()) as idade
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        ORDER BY f.created_at DESC
        LIMIT 5
    ");
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green;'>✅ Busca de fiscais realizada com sucesso</p>";
    echo "<p>Fiscais encontrados: " . count($fiscais) . "</p>";
    
    if (!empty($fiscais)) {
        echo "<h3>Primeiros fiscais:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>CPF</th><th>Status</th></tr>";
        
        foreach ($fiscais as $fiscal) {
            echo "<tr>";
            echo "<td>" . $fiscal['id'] . "</td>";
            echo "<td>" . htmlspecialchars($fiscal['nome']) . "</td>";
            echo "<td>" . htmlspecialchars($fiscal['email']) . "</td>";
            echo "<td>" . formatCPF($fiscal['cpf']) . "</td>";
            echo "<td>" . ucfirst($fiscal['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao buscar fiscais: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Testando funções de formatação</h2>";

$cpf_teste = "12345678901";
$phone_teste = "11987654321";

echo "<p>CPF teste: $cpf_teste → " . formatCPF($cpf_teste) . "</p>";
echo "<p>Telefone teste: $phone_teste → " . formatPhone($phone_teste) . "</p>";

echo "<h2>3. Links de teste</h2>";

echo "<p><strong>Links para testar:</strong></p>";
echo "<ul>";
echo "<li><a href='admin/fiscais.php' target='_blank'>📋 Página de Fiscais</a></li>";
echo "<li><a href='admin/alocar_fiscal.php?id=2' target='_blank'>🔧 Alocar Fiscal</a></li>";
echo "<li><a href='presenca_prova.php?concurso_id=6' target='_blank'>📝 Presença na Prova</a></li>";
echo "<li><a href='presenca_treinamento.php?concurso_id=6' target='_blank'>📚 Presença no Treinamento</a></li>";
echo "</ul>";

echo "<h2>4. Criar alocações de teste</h2>";

echo "<p><a href='verificar_fiscais_sem_alocacao.php?criar_alocacoes=1' class='btn btn-primary'>Criar Alocações de Teste</a></p>";

echo "<hr>";
echo "<p><strong>Teste concluído!</strong></p>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 