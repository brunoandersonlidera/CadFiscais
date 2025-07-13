<?php
require_once 'config.php';

echo "<h1>Teste de Alocações na Presença</h1>";

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo "<p style='color: red;'>❌ Usuário não está logado</p>";
    echo "<p><a href='login.php'>Fazer Login</a></p>";
    exit;
}

$db = getDB();
if (!$db) {
    echo "<p style='color: red;'>❌ Erro de conexão com banco de dados</p>";
    exit;
}

echo "<h2>1. Verificando Alocações Ativas</h2>";

try {
    $stmt = $db->prepare("
        SELECT 
            f.nome as fiscal_nome,
            f.cpf,
            e.nome as escola_nome,
            s.nome as sala_nome,
            af.tipo_alocacao,
            af.observacoes as observacoes_alocacao,
            af.data_alocacao,
            af.horario_alocacao
        FROM alocacoes_fiscais af
        JOIN fiscais f ON af.fiscal_id = f.id
        LEFT JOIN escolas e ON af.escola_id = e.id
        LEFT JOIN salas s ON af.sala_id = s.id
        WHERE af.status = 'ativo'
        ORDER BY e.nome, s.nome, f.nome
        LIMIT 10
    ");
    $stmt->execute();
    $alocacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($alocacoes)) {
        echo "<p style='color: green;'>✅ Encontradas " . count($alocacoes) . " alocações ativas:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Fiscal</th><th>CPF</th><th>Escola</th><th>Sala</th><th>Tipo</th><th>Observações</th></tr>";
        
        foreach ($alocacoes as $alocacao) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($alocacao['fiscal_nome']) . "</td>";
            echo "<td>" . formatCPF($alocacao['cpf']) . "</td>";
            echo "<td>" . htmlspecialchars($alocacao['escola_nome']) . "</td>";
            echo "<td>" . htmlspecialchars($alocacao['sala_nome'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($alocacao['tipo_alocacao']) . "</td>";
            echo "<td>" . htmlspecialchars($alocacao['observacoes_alocacao'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhuma alocação ativa encontrada</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao buscar alocações: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Verificando Fiscais com Alocação</h2>";

try {
    $stmt = $db->prepare("
        SELECT 
            f.id,
            f.nome,
            f.cpf,
            f.concurso_id,
            e.nome as escola_nome,
            s.nome as sala_nome,
            af.tipo_alocacao,
            af.observacoes as observacoes_alocacao
        FROM fiscais f
        LEFT JOIN escolas e ON f.escola_id = e.id
        LEFT JOIN salas s ON f.sala_id = s.id
        LEFT JOIN alocacoes_fiscais af ON f.id = af.fiscal_id AND af.status = 'ativo'
        WHERE f.concurso_id = 6 AND f.status = 'aprovado'
        ORDER BY e.nome, s.nome, f.nome
        LIMIT 10
    ");
    $stmt->execute();
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($fiscais)) {
        echo "<p style='color: green;'>✅ Encontrados " . count($fiscais) . " fiscais com alocação:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Fiscal</th><th>CPF</th><th>Escola</th><th>Sala</th><th>Tipo Alocação</th><th>Observações</th></tr>";
        
        foreach ($fiscais as $fiscal) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($fiscal['nome']) . "</td>";
            echo "<td>" . formatCPF($fiscal['cpf']) . "</td>";
            echo "<td>" . htmlspecialchars($fiscal['escola_nome'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($fiscal['sala_nome'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($fiscal['tipo_alocacao'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($fiscal['observacoes_alocacao'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhum fiscal com alocação encontrado</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao buscar fiscais: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Tipos de Alocação Disponíveis</h2>";

$tipos_alocacao = [
    'sala' => '🚪 Sala',
    'corredor' => '🚶 Corredor',
    'entrada' => '🚪 Portaria/Entrada',
    'banheiro' => '🚻 Banheiro',
    'outro' => '📍 Outro local'
];

echo "<p><strong>Tipos de alocação suportados:</strong></p>";
echo "<ul>";
foreach ($tipos_alocacao as $tipo => $descricao) {
    echo "<li><strong>$tipo:</strong> $descricao</li>";
}
echo "</ul>";

echo "<h2>4. Links de Teste</h2>";

echo "<p><strong>Links para testar as funcionalidades:</strong></p>";
echo "<ul>";
echo "<li><a href='presenca_prova.php?concurso_id=6' target='_blank'>📝 Presença na Prova - Concurso 6</a></li>";
echo "<li><a href='presenca_treinamento.php?concurso_id=6' target='_blank'>📚 Presença no Treinamento - Concurso 6</a></li>";
echo "<li><a href='admin/relatorio_alocacoes.php' target='_blank'>📊 Relatório de Alocações</a></li>";
echo "<li><a href='admin/alocar_fiscal.php' target='_blank'>🔧 Alocar Fiscais</a></li>";
echo "</ul>";

echo "<h2>5. Criar Alocações de Teste</h2>";

if (empty($alocacoes)) {
    echo "<p><strong>Nenhuma alocação encontrada. Você pode:</strong></p>";
    echo "<ul>";
    echo "<li><a href='criar_alocacoes_teste.php'>Criar Alocações de Teste</a></li>";
    echo "<li><a href='admin/alocar_fiscal.php'>Alocar Fiscais Manualmente</a></li>";
    echo "</ul>";
} else {
    echo "<p style='color: green;'>✅ Alocações encontradas. O sistema está funcionando corretamente.</p>";
}

echo "<hr>";
echo "<p><strong>Teste concluído!</strong></p>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
?> 