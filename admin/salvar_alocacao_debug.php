<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    echo "<h1>❌ Acesso Negado</h1>";
    echo "<p>Você não tem permissão para acessar esta página.</p>";
    exit;
}

echo "<h1>🔧 Debug - Salvar Alocação</h1>";

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<h2>❌ Método não permitido</h2>";
    echo "<p>Método atual: " . $_SERVER['REQUEST_METHOD'] . "</p>";
    echo "<p>Esperado: POST</p>";
    exit;
}

echo "<h2>✅ Método POST detectado</h2>";

// Mostrar dados recebidos
echo "<h3>Dados Recebidos:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Obter dados do formulário
$fiscal_id = (int)($_POST['fiscal_id'] ?? 0);
$escola_id = (int)($_POST['escola_id'] ?? 0);
$sala_id = (int)($_POST['sala_id'] ?? 0);
$tipo_alocacao = trim($_POST['tipo_alocacao'] ?? '');
$observacoes = trim($_POST['observacoes_alocacao'] ?? '');
$data_alocacao = trim($_POST['data_alocacao'] ?? '');
$horario_alocacao = trim($_POST['horario_alocacao'] ?? '');

echo "<h3>Dados Processados:</h3>";
echo "<ul>";
echo "<li>Fiscal ID: $fiscal_id</li>";
echo "<li>Escola ID: $escola_id</li>";
echo "<li>Sala ID: $sala_id</li>";
echo "<li>Tipo Alocação: $tipo_alocacao</li>";
echo "<li>Observações: $observacoes</li>";
echo "<li>Data Alocação: $data_alocacao</li>";
echo "<li>Horário Alocação: $horario_alocacao</li>";
echo "</ul>";

// Validar dados obrigatórios
if (!$fiscal_id || !$escola_id || !$sala_id || !$data_alocacao || !$horario_alocacao) {
    echo "<h2>❌ Dados obrigatórios faltando</h2>";
    echo "<ul>";
    if (!$fiscal_id) echo "<li>Fiscal ID está vazio</li>";
    if (!$escola_id) echo "<li>Escola ID está vazio</li>";
    if (!$sala_id) echo "<li>Sala ID está vazio</li>";
    if (!$data_alocacao) echo "<li>Data Alocação está vazio</li>";
    if (!$horario_alocacao) echo "<li>Horário Alocação está vazio</li>";
    echo "</ul>";
    exit;
}

echo "<h2>✅ Dados obrigatórios preenchidos</h2>";

$db = getDB();

if (!$db) {
    echo "<h2>❌ Erro na conexão com banco</h2>";
    exit;
}

echo "<h2>✅ Conexão com banco estabelecida</h2>";

try {
    // Verificar se o fiscal existe
    $stmt = $db->prepare("SELECT id, nome FROM fiscais WHERE id = ?");
    $stmt->execute([$fiscal_id]);
    $fiscal = $stmt->fetch();
    
    if (!$fiscal) {
        echo "<h2>❌ Fiscal não encontrado</h2>";
        echo "<p>Fiscal ID: $fiscal_id</p>";
        exit;
    }
    
    echo "<h3>✅ Fiscal encontrado: " . htmlspecialchars($fiscal['nome']) . "</h3>";
    
    // Verificar se a escola existe
    $stmt = $db->prepare("SELECT id, nome FROM escolas WHERE id = ? AND status = 'ativo'");
    $stmt->execute([$escola_id]);
    $escola = $stmt->fetch();
    
    if (!$escola) {
        echo "<h2>❌ Escola não encontrada</h2>";
        echo "<p>Escola ID: $escola_id</p>";
        exit;
    }
    
    echo "<h3>✅ Escola encontrada: " . htmlspecialchars($escola['nome']) . "</h3>";
    
    // Verificar se a sala existe e pertence à escola
    $stmt = $db->prepare("SELECT id, nome FROM salas WHERE id = ? AND escola_id = ? AND status = 'ativo'");
    $stmt->execute([$sala_id, $escola_id]);
    $sala = $stmt->fetch();
    
    if (!$sala) {
        echo "<h2>❌ Sala não encontrada ou não pertence à escola</h2>";
        echo "<p>Sala ID: $sala_id</p>";
        echo "<p>Escola ID: $escola_id</p>";
        exit;
    }
    
    echo "<h3>✅ Sala encontrada: " . htmlspecialchars($sala['nome']) . "</h3>";
    
    // Verificar se já existe alocação ativa para este fiscal na mesma data/horário
    $stmt = $db->prepare("
        SELECT id FROM alocacoes_fiscais 
        WHERE fiscal_id = ? AND data_alocacao = ? AND horario_alocacao = ? AND status = 'ativo'
    ");
    $stmt->execute([$fiscal_id, $data_alocacao, $horario_alocacao]);
    $alocacao_existente = $stmt->fetch();
    
    if ($alocacao_existente) {
        echo "<h2>❌ Já existe uma alocação ativa para este fiscal na data/horário selecionado</h2>";
        exit;
    }
    
    echo "<h3>✅ Nenhuma alocação conflitante encontrada</h3>";
    
    // Inserir alocação
    echo "<h3>🔄 Inserindo alocação...</h3>";
    
    $stmt = $db->prepare("
        INSERT INTO alocacoes_fiscais (
            fiscal_id, escola_id, sala_id, tipo_alocacao, observacoes, 
            data_alocacao, horario_alocacao, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'ativo', CURRENT_TIMESTAMP)
    ");
    
    $resultado = $stmt->execute([
        $fiscal_id, $escola_id, $sala_id, $tipo_alocacao, $observacoes,
        $data_alocacao, $horario_alocacao
    ]);
    
    if ($resultado) {
        $alocacao_id = $db->lastInsertId();
        
        // Atualizar status do fiscal para aprovado
        $stmt = $db->prepare("UPDATE fiscais SET status = 'aprovado' WHERE id = ?");
        $stmt->execute([$fiscal_id]);
        
        // Log da atividade
        logActivity("Fiscal {$fiscal['nome']} alocado na escola {$escola['nome']}, sala {$sala['nome']} - ID: $alocacao_id", 'INFO');
        
        echo "<h2>✅ Alocação salva com sucesso!</h2>";
        echo "<ul>";
        echo "<li>Alocação ID: $alocacao_id</li>";
        echo "<li>Fiscal: " . htmlspecialchars($fiscal['nome']) . "</li>";
        echo "<li>Escola: " . htmlspecialchars($escola['nome']) . "</li>";
        echo "<li>Sala: " . htmlspecialchars($sala['nome']) . "</li>";
        echo "<li>Data: $data_alocacao</li>";
        echo "<li>Horário: $horario_alocacao</li>";
        echo "</ul>";
        
        echo "<h3>🔗 Links</h3>";
        echo "<a href='alocar_fiscal.php?id=$fiscal_id' class='btn btn-primary'>Voltar para Alocação</a> ";
        echo "<a href='fiscais.php' class='btn btn-secondary'>Lista de Fiscais</a>";
        
    } else {
        echo "<h2>❌ Erro ao salvar alocação</h2>";
        echo "<p>Verifique os logs do sistema para mais detalhes.</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Erro interno do servidor</h2>";
    echo "<p>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    
    logActivity('Erro ao salvar alocação: ' . $e->getMessage(), 'ERROR');
}
?> 