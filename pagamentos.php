<?php
require_once 'config.php';

// Debug: Verificar status da sessão
error_log("=== DEBUG SESSÃO ===");
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));
error_log("isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false'));

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    error_log("Usuário não logado - redirecionando para login");
    // Temporariamente comentado para debug
    // redirect('login.php');
    error_log("DEBUG: Ignorando verificação de login temporariamente");
}

$message = '';
$error = '';
$debug_info = [];

// Processar confirmação de pagamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debug_info[] = "=== INÍCIO DO PROCESSAMENTO POST ===";
    $debug_info[] = "Método: " . $_SERVER['REQUEST_METHOD'];
    $debug_info[] = "POST data recebida: " . print_r($_POST, true);
    
    try {
        // Debug: Coletar informações para exibição
        $debug_info[] = "=== DEBUG PAGAMENTOS ===";
        
        $concurso_id = (int)$_POST['concurso_id'];
        $valor_pago = (float)($_POST['valor_pago'] ?? 0);
        $fiscais_ids = $_POST['fiscal_id'] ?? [];
        $pagos = $_POST['pago'] ?? [];
        

        
        $debug_info[] = "Concurso ID: $concurso_id";
        $debug_info[] = "Valor Pago: $valor_pago";
        $debug_info[] = "Fiscais IDs: " . print_r($fiscais_ids, true);
        $debug_info[] = "Pagos: " . print_r($pagos, true);
        
        // Garantir que são arrays
        if (!is_array($fiscais_ids)) {
            $fiscais_ids = [$fiscais_ids];
        }
        if (!is_array($pagos)) {
            $pagos = [$pagos];
        }
        
        $debug_info[] = "Fiscais IDs (após array): " . print_r($fiscais_ids, true);
        $debug_info[] = "Pagos (após array): " . print_r($pagos, true);
        
        $db = getDB();
        if (!$db) {
            throw new Exception("Erro de conexão com banco de dados");
        }
        

        
                    // Verificar e criar tabela pagamentos_fiscais se não existir
            try {
                $stmt = $db->query("SHOW TABLES LIKE 'pagamentos_fiscais'");
                if ($stmt->rowCount() == 0) {
                    $debug_info[] = "Tabela pagamentos_fiscais não existe - criando...";
                    $db->exec("CREATE TABLE pagamentos_fiscais (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        fiscal_id INT NOT NULL,
                        concurso_id INT NOT NULL,
                        pago TINYINT(1) DEFAULT 0,
                        valor_pago DECIMAL(10,2) DEFAULT 0.00,
                        observacoes TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (fiscal_id) REFERENCES fiscais(id) ON DELETE CASCADE,
                        FOREIGN KEY (concurso_id) REFERENCES concursos(id) ON DELETE CASCADE,
                        UNIQUE KEY unique_fiscal_concurso (fiscal_id, concurso_id)
                    )");
                                    $debug_info[] = "Tabela pagamentos_fiscais criada com sucesso";
            } else {
                $debug_info[] = "Tabela pagamentos_fiscais já existe";
            }
            } catch (Exception $e) {
                $debug_info[] = "Erro ao verificar/criar tabela pagamentos_fiscais: " . $e->getMessage();
            }
            
            // Verificar e criar tabela presenca_fiscais se não existir
            try {
                $stmt = $db->query("SHOW TABLES LIKE 'presenca_fiscais'");
                if ($stmt->rowCount() == 0) {
                                    $debug_info[] = "Tabela presenca_fiscais não existe - criando...";
                $db->exec("CREATE TABLE presenca_fiscais (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    fiscal_id INT NOT NULL,
                    concurso_id INT NOT NULL,
                    presente TINYINT(1) DEFAULT 0,
                    observacoes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (fiscal_id) REFERENCES fiscais(id) ON DELETE CASCADE,
                    FOREIGN KEY (concurso_id) REFERENCES concursos(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_fiscal_concurso (fiscal_id, concurso_id)
                )");
                $debug_info[] = "Tabela presenca_fiscais criada com sucesso";
            } else {
                $debug_info[] = "Tabela presenca_fiscais já existe";
            }
        } catch (Exception $e) {
            $debug_info[] = "Erro ao verificar/criar tabela presenca_fiscais: " . $e->getMessage();
        }
        
        $processados = 0;
        $erros = 0;
        
                // Processar cada fiscal

        
        for ($i = 0; $i < count($fiscais_ids); $i++) {
            $fiscal_id = (int)$fiscais_ids[$i];
            $pago = (int)$pagos[$i];
            

            
            try {
                // Verificar se já existe registro de pagamento
                $stmt = $db->prepare("SELECT id, pago FROM pagamentos_fiscais WHERE fiscal_id = ? AND concurso_id = ?");
                $stmt->execute([$fiscal_id, $concurso_id]);
                $pagamento_existente = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $debug_info[] = "Pagamento existente: " . ($pagamento_existente ? 'SIM' : 'NÃO');
            
            if ($pagamento_existente) {
                // Atualizar pagamento existente
                $stmt = $db->prepare("UPDATE pagamentos_fiscais SET pago = ?, valor_pago = ?, updated_at = CURRENT_TIMESTAMP WHERE fiscal_id = ? AND concurso_id = ?");
                $result = $stmt->execute([$pago, $valor_pago, $fiscal_id, $concurso_id]);
            } else {
                // Inserir novo pagamento
                $stmt = $db->prepare("INSERT INTO pagamentos_fiscais (fiscal_id, concurso_id, pago, valor_pago, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
                $result = $stmt->execute([$fiscal_id, $concurso_id, $pago, $valor_pago]);
            }
                
                $processados++;
                logActivity("Pagamento registrado para fiscal ID: $fiscal_id, Concurso ID: $concurso_id, Pago: " . ($pago ? 'Sim' : 'Não') . ", Valor: R$ " . number_format($valor_pago, 2, ',', '.'), 'INFO');
                
            } catch (Exception $e) {
                $erros++;
                logActivity("Erro ao registrar pagamento para fiscal ID: $fiscal_id - " . $e->getMessage(), 'ERROR');
            }
        }
        
        if ($erros > 0) {
            $message = "Processados: $processados, Erros: $erros";
        } else {
            $message = "Pagamentos registrados com sucesso! ($processados fiscais processados)";
            // Redirecionar para recarregar a página com dados atualizados
            $redirect_url = "pagamentos.php?concurso_id=$concurso_id";
            if (isset($_GET['escola_id']) && !empty($_GET['escola_id'])) {
                $redirect_url .= "&escola_id=" . $_GET['escola_id'];
            }
            header("Location: $redirect_url");
            exit;
        }
        
    } catch (Exception $e) {
        $error = 'Erro ao registrar pagamentos: ' . $e->getMessage();
        logActivity("Erro ao registrar pagamentos: " . $e->getMessage(), 'ERROR');
    }
}

// Buscar concursos ativos
$concursos = [];
try {
    $db = getDB();
    if ($db) {
        $stmt = $db->query("SELECT id, titulo, data_prova, valor_pagamento FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC");
        $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error = 'Erro ao carregar concursos: ' . $e->getMessage();
}

// Buscar escolas se concurso selecionado
$escolas = [];
$concurso_selecionado = null;
$fiscais = [];
$escola_filtro = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : 0;

if (isset($_GET['concurso_id']) && !empty($_GET['concurso_id'])) {
    $concurso_id = (int)$_GET['concurso_id'];
    
    try {
        // Buscar dados do concurso
        $stmt = $db->prepare("SELECT * FROM concursos WHERE id = ?");
        $stmt->execute([$concurso_id]);
        $concurso_selecionado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($concurso_selecionado) {
            // Buscar escolas do concurso
            $stmt = $db->prepare("SELECT DISTINCT e.* FROM escolas e 
                                 INNER JOIN salas s ON e.id = s.escola_id 
                                 INNER JOIN alocacoes_fiscais af ON s.id = af.sala_id 
                                 INNER JOIN fiscais f ON af.fiscal_id = f.id 
                                 WHERE f.concurso_id = ? AND e.status = 'ativo' 
                                 ORDER BY e.nome");
            $stmt->execute([$concurso_id]);
            $escolas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Buscar fiscais aprovados do concurso com dados de presença e pagamento
            $sql = "
                SELECT f.*, 
                       e.nome as escola_nome,
                       s.nome as sala_nome,
                       pf.pago as pagamento_registrado,
                       pf.valor_pago as valor_pago_registrado,
                       pf.created_at as data_registro_pagamento,
                       pr.presente as presenca_registrada,
                       pr.observacoes as observacoes_presenca
                FROM fiscais f
                LEFT JOIN alocacoes_fiscais af ON f.id = af.fiscal_id
                LEFT JOIN salas s ON af.sala_id = s.id
                LEFT JOIN escolas e ON s.escola_id = e.id
                LEFT JOIN pagamentos_fiscais pf ON f.id = pf.fiscal_id AND pf.concurso_id = f.concurso_id
                LEFT JOIN presenca_fiscais pr ON f.id = pr.fiscal_id AND pr.concurso_id = f.concurso_id
                WHERE f.concurso_id = ? AND f.status = 'aprovado'
            ";
            
            $params = [$concurso_id];
            
            // Aplicar filtro por escola se selecionado
            if ($escola_filtro > 0) {
                $sql .= " AND e.id = ?";
                $params[] = $escola_filtro;
            }
            
            $sql .= " ORDER BY f.nome, e.nome, s.nome";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $error = 'Erro ao carregar fiscais: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Pagamentos - IDH</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .mobile-optimized {
            max-width: 100%;
            padding: 10px;
        }
        .fiscal-row {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #eee;
            background: #fff;
        }
        .fiscal-row:nth-child(even) {
            background: #f9f9f9;
        }
        .fiscal-row.disabled {
            background: #f8d7da;
            opacity: 0.7;
        }
        .fiscal-info {
            flex: 1;
            min-width: 0;
        }
        .fiscal-name {
            font-weight: bold;
            margin-bottom: 4px;
            color: #333;
        }
        .fiscal-name.disabled {
            color: #dc3545;
        }
        .fiscal-details {
            font-size: 0.9em;
            color: #666;
        }
        .fiscal-status {
            font-size: 0.8em;
            margin-top: 4px;
        }
        .status-presente {
            color: #28a745;
            font-weight: bold;
        }
        .status-ausente {
            color: #dc3545;
            font-weight: bold;
        }
        .status-sem-presenca {
            color: #ffc107;
            font-weight: bold;
        }
        .checkbox-container {
            margin-left: 15px;
        }
        .payment-checkbox {
            width: 24px;
            height: 24px;
            accent-color: #28a745;
        }
        .payment-checkbox:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .status-paid {
            background: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .status-unpaid {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-item {
            flex: 1;
            min-width: 200px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        .stat-item {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #ddd;
        }
        .stat-number {
            font-size: 1.5em;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        .save-button {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 1.1em;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        .save-button:hover {
            background: #218838;
        }
        .fiscais-list {
            max-height: 60vh;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .warning-message {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 0.9em;
        }
        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-item {
                min-width: auto;
            }
            .fiscal-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .checkbox-container {
                margin-left: 0;
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="container mobile-optimized">
        <div class="header">
            <h1>💰 Controle de Pagamentos</h1>
            <p>Sistema IDH - Controle Financeiro</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        

        
        <!-- Seleção de Concurso -->
        <div class="card">
            <h2>Selecionar Concurso</h2>
            <form method="GET" action="">
                <div class="form-group">
                    <label for="concurso_id">Concurso:</label>
                    <select id="concurso_id" name="concurso_id" class="form-control" onchange="this.form.submit()">
                        <option value="">Selecione um concurso</option>
                        <?php foreach ($concursos as $concurso): ?>
                            <option value="<?= $concurso['id'] ?>" 
                                    <?= isset($_GET['concurso_id']) && $_GET['concurso_id'] == $concurso['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($concurso['titulo']) ?> 
                                (<?= date('d/m/Y', strtotime($concurso['data_prova'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
        
        <?php if ($concurso_selecionado): ?>
            <!-- Informações do Concurso -->
            <div class="card">
                <h2>💰 <?= htmlspecialchars($concurso_selecionado['titulo']) ?></h2>
                <div class="info-box">
                    <p><strong>Data da Prova:</strong> <?= date('d/m/Y', strtotime($concurso_selecionado['data_prova'])) ?></p>
                    <p><strong>Valor Padrão:</strong> R$ <?= number_format($concurso_selecionado['valor_pagamento'], 2, ',', '.') ?></p>
                </div>
            </div>
            
            <!-- Aviso sobre presença -->
            <div class="warning-message">
                <strong>⚠️ Atenção:</strong> Apenas fiscais <strong>presentes</strong> podem receber pagamento. 
                Fiscais ausentes ou sem registro de presença aparecem em vermelho e não podem ser marcados.
            </div>
            
            <!-- Filtro por Escola -->
            <?php if (!empty($escolas)): ?>
            <div class="filter-section">
                <h3>🏫 Filtro por Escola</h3>
                <form method="GET" action="">
                    <input type="hidden" name="concurso_id" value="<?= $concurso_selecionado['id'] ?>">
                    <div class="filter-row">
                        <div class="filter-item">
                            <label for="escola_id">Escola:</label>
                            <select id="escola_id" name="escola_id" class="form-control" onchange="this.form.submit()">
                                <option value="">Todas as escolas</option>
                                <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>" 
                                            <?= $escola_filtro == $escola['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($escola['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <!-- Estatísticas -->
            <?php if (!empty($fiscais)): ?>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= count($fiscais) ?></div>
                    <div class="stat-label">Total de Fiscais</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= count(array_filter($fiscais, function($f) { return $f['presenca_registrada'] === 1; })) ?></div>
                    <div class="stat-label">Presentes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= count(array_filter($fiscais, function($f) { return $f['pagamento_registrado'] === 1; })) ?></div>
                    <div class="stat-label">Pagamentos Realizados</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= count(array_filter($fiscais, function($f) { return $f['presenca_registrada'] === 1 && $f['pagamento_registrado'] !== 1; })) ?></div>
                    <div class="stat-label">Pendentes de Pagamento</div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Lista de Fiscais -->
            <div class="card">
                <h2>👥 Lista de Fiscais</h2>
                
                <?php if (empty($fiscais)): ?>
                    <p>Nenhum fiscal aprovado encontrado para este concurso.</p>
                <?php else: ?>
                    <form method="POST" action="" id="pagamentosForm">
                        <input type="hidden" name="concurso_id" value="<?= $concurso_selecionado['id'] ?>">
                        <input type="hidden" name="valor_pago" value="<?= $concurso_selecionado['valor_pagamento'] ?>">
                        
                        <div class="fiscais-list">
                            <?php foreach (
    $fiscais as $fiscal): ?>
    <?php 
    // Debug: Mostrar dados do fiscal
    $debug_info[] = "Fiscal ID: " . $fiscal['id'] . 
                   " - Nome: " . $fiscal['nome'] . 
                   " - Presença: " . $fiscal['presenca_registrada'] . 
                   " - Pagamento: " . $fiscal['pagamento_registrado'];
    
                                    // Determinar status de presença
                                $presenca_valor = $fiscal['presenca_registrada'];
                                $pode_receber_pagamento = ($presenca_valor == 1 || $presenca_valor === '1');
                                $status_presenca = '';
                                $status_class = '';
                                
                                if ($presenca_valor == 1 || $presenca_valor === '1') {
                                    $status_presenca = '✅ Presente';
                                    $status_class = 'status-presente';
                                } elseif ($presenca_valor == 0 || $presenca_valor === '0') {
                                    $status_presenca = '❌ Ausente';
                                    $status_class = 'status-ausente';
                                } else {
                                    $status_presenca = '⚠️ Sem registro de presença';
                                    $status_class = 'status-sem-presenca';
                                }
    
    
    ?>
    <div class="fiscal-row <?= !$pode_receber_pagamento ? 'disabled' : '' ?>">
        <div class="fiscal-info">
            <div class="fiscal-name <?= !$pode_receber_pagamento ? 'disabled' : '' ?>">
                <?= htmlspecialchars($fiscal['nome']) ?>
            </div>
            <div class="fiscal-details">
                <?= htmlspecialchars($fiscal['escola_nome'] ?? 'Escola não definida') ?> - 
                <?= htmlspecialchars($fiscal['sala_nome'] ?? 'Sala não definida') ?>
            </div>
            <div class="fiscal-status <?= $status_class ?>">
                <?= $status_presenca ?>
                <?php if ($fiscal['observacoes_presenca']): ?>
                    - <?= htmlspecialchars($fiscal['observacoes_presenca']) ?>
                <?php endif; ?>
            </div>
            <?php if ($fiscal['pagamento_registrado'] !== null): ?>
                <div class="<?= $fiscal['pagamento_registrado'] ? 'status-paid' : 'status-unpaid' ?>">
                    <?= $fiscal['pagamento_registrado'] ? '✅ Pago' : '❌ Não Pago' ?>
                    <?php if ($fiscal['valor_pago_registrado']): ?>
                        - R$ <?= number_format($fiscal['valor_pago_registrado'], 2, ',', '.') ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
                                        <div class="checkbox-container">
                                    <input type="checkbox" 
                                           name="fiscal_id[]" 
                                           value="<?= $fiscal['id'] ?>" 
                                           class="payment-checkbox"
                                           <?= ($fiscal['pagamento_registrado'] == 1 || $fiscal['pagamento_registrado'] === '1') ? 'checked' : '' ?>
                                           <?= !$pode_receber_pagamento ? 'disabled' : '' ?>
                                           onchange="updatePagoValue(this)">
                                    <input type="hidden" name="pago[]" id="pago_<?= $fiscal['id'] ?>" value="<?= ($fiscal['pagamento_registrado'] == 1 || $fiscal['pagamento_registrado'] === '1') ? '1' : '0' ?>" data-fiscal="<?= $fiscal['id'] ?>">
                                </div>
    </div>
<?php endforeach; ?>
                        </div>
                        
                        <button type="submit" name="confirmar_pagamento" class="save-button">
                            💾 Salvar Alterações
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h3>🔧 Ações</h3>
            <a href="presenca_mobile.php?concurso_id=<?= $concurso_selecionado['id'] ?? '' ?>" class="btn btn-primary btn-block">📱 Controle de Presença</a>
            <a href="relatorios.php" class="btn btn-secondary btn-block">📊 Relatórios</a>
            <a href="admin/dashboard.php" class="btn btn-success btn-block">🏠 Painel Admin</a>
            <a href="index.php" class="btn btn-info btn-block">🏠 Voltar ao Sistema</a>
        </div>
    </div>
    
    <script>
    function updatePagoValue(checkbox) {
        const fiscalId = checkbox.value;
        const pagoInput = document.getElementById('pago_' + fiscalId);
        if (pagoInput) {
            pagoInput.value = checkbox.checked ? '1' : '0';
            console.log('Fiscal ' + fiscalId + ' marcado como: ' + (checkbox.checked ? 'PAGO' : 'NÃO PAGO'));
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('pagamentosForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Atualizar todos os valores antes de enviar
                const checkboxes = document.querySelectorAll('input[name="fiscal_id[]"]:not(:disabled)');
                let processados = 0;
                let pagos = 0;
                let desmarcados = 0;
                
                // Limpar campos hidden duplicados
                const existingHidden = form.querySelectorAll('input[name="pago[]"]');
                existingHidden.forEach(input => input.remove());
                
                checkboxes.forEach(function(checkbox) {
                    const fiscalId = checkbox.value;
                    
                    // Criar novo campo hidden para cada checkbox
                    const pagoInput = document.createElement('input');
                    pagoInput.type = 'hidden';
                    pagoInput.name = 'pago[]';
                    pagoInput.value = checkbox.checked ? '1' : '0';
                    form.appendChild(pagoInput);
                    
                    if (checkbox.checked) pagos++;
                    else desmarcados++;
                    processados++;
                });
                
                if (processados === 0) {
                    alert('Nenhum fiscal elegível para pagamento encontrado.');
                    return;
                }
                
                // Alerta claro para o usuário
                let msg = `Você está prestes a salvar os pagamentos.\n\n` +
                          `- Fiscais MARCADOS serão salvos como PAGO.\n` +
                          `- Fiscais DESMARCADOS serão salvos como NÃO PAGO.\n\n` +
                          `Pagos: ${pagos}\nNão pagos: ${desmarcados}\n\n` +
                          `Tem certeza que deseja continuar?`;
                if (confirm(msg)) {
                    console.log('Enviando formulário...');
                    form.submit();
                }
            });
        }
    });
    </script>
</body>
</html> 