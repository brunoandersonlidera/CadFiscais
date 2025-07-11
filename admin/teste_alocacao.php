<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$fiscal_id = isset($_GET['id']) ? (int)$_GET['id'] : 6; // Default para ID 6
$db = getDB();

echo "<h1>🧪 Teste de Alocação</h1>";
echo "<p>Fiscal ID: $fiscal_id</p>";

// 1. Verificar se o fiscal existe
try {
    $stmt = $db->prepare("SELECT * FROM fiscais WHERE id = ?");
    $stmt->execute([$fiscal_id]);
    $fiscal = $stmt->fetch();
    
    if ($fiscal) {
        echo "✅ Fiscal encontrado: " . htmlspecialchars($fiscal['nome']) . "<br>";
    } else {
        echo "❌ Fiscal não encontrado<br>";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Erro ao buscar fiscal: " . $e->getMessage() . "<br>";
    exit;
}

// 2. Verificar escolas disponíveis
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM escolas WHERE status = 'ativo'");
    $result = $stmt->fetch();
    echo "Escolas ativas: " . $result['total'] . "<br>";
    
    if ($result['total'] == 0) {
        echo "❌ Não há escolas cadastradas<br>";
        echo "<a href='escolas.php' class='btn btn-warning'>Cadastrar Escolas</a><br>";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar escolas: " . $e->getMessage() . "<br>";
}

// 3. Verificar salas disponíveis
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM salas WHERE status = 'ativo'");
    $result = $stmt->fetch();
    echo "Salas ativas: " . $result['total'] . "<br>";
    
    if ($result['total'] == 0) {
        echo "❌ Não há salas cadastradas<br>";
        echo "<a href='salas.php' class='btn btn-warning'>Cadastrar Salas</a><br>";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar salas: " . $e->getMessage() . "<br>";
}

// 4. Teste de inserção direta
echo "<h2>Teste de Inserção Direta</h2>";

try {
    // Buscar primeira escola e sala
    $stmt = $db->query("SELECT id FROM escolas WHERE status = 'ativo' LIMIT 1");
    $escola = $stmt->fetch();
    
    $stmt = $db->query("SELECT id FROM salas WHERE status = 'ativo' LIMIT 1");
    $sala = $stmt->fetch();
    
    if ($escola && $sala) {
        echo "Testando inserção com Escola ID: {$escola['id']}, Sala ID: {$sala['id']}<br>";
        
        $stmt = $db->prepare("
            INSERT INTO alocacoes_fiscais (
                fiscal_id, escola_id, sala_id, tipo_alocacao, observacoes, 
                data_alocacao, horario_alocacao, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'ativo', CURRENT_TIMESTAMP)
        ");
        
        $resultado = $stmt->execute([
            $fiscal_id, 
            $escola['id'], 
            $sala['id'], 
            'sala', 
            'Teste de alocação', 
            date('Y-m-d'), 
            '08:00:00'
        ]);
        
        if ($resultado) {
            $alocacao_id = $db->lastInsertId();
            echo "✅ Inserção bem-sucedida! ID: $alocacao_id<br>";
            
            // Remover o teste
            $stmt = $db->prepare("DELETE FROM alocacoes_fiscais WHERE id = ?");
            $stmt->execute([$alocacao_id]);
            echo "✅ Registro de teste removido<br>";
        } else {
            echo "❌ Erro na inserção<br>";
        }
    } else {
        echo "❌ Não há escolas ou salas disponíveis para teste<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro no teste de inserção: " . $e->getMessage() . "<br>";
}

// 5. Teste via POST
echo "<h2>Teste via POST</h2>";
echo "<form method='POST' action='salvar_alocacao.php'>";
echo "<input type='hidden' name='fiscal_id' value='$fiscal_id'>";
echo "<input type='hidden' name='escola_id' value='1'>";
echo "<input type='hidden' name='sala_id' value='1'>";
echo "<input type='hidden' name='tipo_alocacao' value='sala'>";
echo "<input type='hidden' name='observacoes_alocacao' value='Teste via POST'>";
echo "<input type='hidden' name='data_alocacao' value='" . date('Y-m-d') . "'>";
echo "<input type='hidden' name='horario_alocacao' value='08:00'>";
echo "<button type='submit' class='btn btn-primary'>Testar POST</button>";
echo "</form>";

echo "<h2>Links de Teste</h2>";
echo "<a href='alocar_fiscal.php?id=$fiscal_id' class='btn btn-success'>Ir para Alocação</a> ";
echo "<a href='debug_alocacao.php?id=$fiscal_id' class='btn btn-info'>Debug Completo</a> ";
echo "<a href='fiscais.php' class='btn btn-secondary'>Voltar para Fiscais</a>";

echo "<h2>Status do Sistema</h2>";
echo "<ul>";
echo "<li>✅ Tabela alocacoes_fiscais: OK</li>";
echo "<li>✅ Fiscal ID $fiscal_id: " . ($fiscal ? "Encontrado" : "Não encontrado") . "</li>";
echo "<li>✅ Escolas: " . ($result['total'] ?? 0) . " ativas</li>";
echo "<li>✅ Salas: " . ($result['total'] ?? 0) . " ativas</li>";
echo "<li>✅ JavaScript: Verificar console</li>";
echo "</ul>";

echo "<script>";
echo "console.log('Teste de JavaScript - Alocação');";
echo "console.log('Fiscal ID:', $fiscal_id);";
echo "if (typeof showMessage === 'function') {";
echo "    console.log('✅ showMessage disponível');";
echo "} else {";
echo "    console.log('❌ showMessage não disponível');";
echo "}";
echo "</script>";
?> 