<?php
require_once 'config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

$message = '';
$error = '';

// Processar confirmação de pagamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_pagamento'])) {
    try {
        $fiscal_id = (int)$_POST['fiscal_id'];
        $concurso_id = (int)$_POST['concurso_id'];
        $pago = (int)$_POST['pago'];
        $valor_pago = (float)($_POST['valor_pago'] ?? 0);
        $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
        
        $db = getDB();
        if (!$db) {
            throw new Exception("Erro de conexão com banco de dados");
        }
        
        // Verificar se já existe registro de pagamento
        $stmt = $db->prepare("SELECT id FROM pagamentos_fiscais WHERE fiscal_id = ? AND concurso_id = ?");
        $stmt->execute([$fiscal_id, $concurso_id]);
        
        if ($stmt->rowCount() > 0) {
            // Atualizar pagamento existente
            $stmt = $db->prepare("UPDATE pagamentos_fiscais SET pago = ?, valor_pago = ?, observacoes = ?, updated_at = CURRENT_TIMESTAMP WHERE fiscal_id = ? AND concurso_id = ?");
            $stmt->execute([$pago, $valor_pago, $observacoes, $fiscal_id, $concurso_id]);
        } else {
            // Inserir novo pagamento
            $stmt = $db->prepare("INSERT INTO pagamentos_fiscais (fiscal_id, concurso_id, pago, valor_pago, observacoes, created_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$fiscal_id, $concurso_id, $pago, $valor_pago, $observacoes]);
        }
        
        $message = 'Pagamento registrado com sucesso!';
        logActivity("Pagamento registrado para fiscal ID: $fiscal_id, Concurso ID: $concurso_id, Pago: " . ($pago ? 'Sim' : 'Não') . ", Valor: R$ " . number_format($valor_pago, 2, ',', '.'), 'INFO');
        
    } catch (Exception $e) {
        $error = 'Erro ao registrar pagamento: ' . $e->getMessage();
        logActivity("Erro ao registrar pagamento: " . $e->getMessage(), 'ERROR');
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

// Buscar fiscais se concurso selecionado
$fiscais = [];
$concurso_selecionado = null;
if (isset($_GET['concurso_id']) && !empty($_GET['concurso_id'])) {
    $concurso_id = (int)$_GET['concurso_id'];
    
    try {
        // Buscar dados do concurso
        $stmt = $db->prepare("SELECT * FROM concursos WHERE id = ?");
        $stmt->execute([$concurso_id]);
        $concurso_selecionado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($concurso_selecionado) {
            // Buscar fiscais aprovados do concurso
            $stmt = $db->prepare("
                SELECT f.*, 
                       CASE WHEN pf.pago IS NOT NULL THEN pf.pago ELSE NULL END as pagamento_registrado,
                       pf.valor_pago as valor_pago_registrado,
                       pf.observacoes as observacoes_pagamento,
                       pf.created_at as data_registro_pagamento
                FROM fiscais f
                LEFT JOIN pagamentos_fiscais pf ON f.id = pf.fiscal_id AND pf.concurso_id = f.concurso_id
                WHERE f.concurso_id = ? AND f.status = 'aprovado'
                ORDER BY f.nome
            ");
            $stmt->execute([$concurso_id]);
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
        .fiscal-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #fff;
        }
        .fiscal-info {
            margin-bottom: 10px;
        }
        .pagamento-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .btn-pago {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-nao-pago {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .status-pagamento {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 5px;
        }
        .status-pago {
            background: #d4edda;
            color: #155724;
        }
        .status-nao-pago {
            background: #f8d7da;
            color: #721c24;
        }
        .status-pendente {
            background: #fff3cd;
            color: #856404;
        }
        .valor-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 5px;
        }
        .observacoes-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 5px;
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
                    <p><strong>Total de Fiscais:</strong> <?= count($fiscais) ?></p>
                    <p><strong>Pagamentos Realizados:</strong> <?= count(array_filter($fiscais, function($f) { return $f['pagamento_registrado'] === 1; })) ?></p>
                    <p><strong>Pagamentos Pendentes:</strong> <?= count(array_filter($fiscais, function($f) { return $f['pagamento_registrado'] === 0; })) ?></p>
                </div>
            </div>
            
            <!-- Lista de Fiscais -->
            <div class="card">
                <h2>👥 Lista de Fiscais</h2>
                
                <?php if (empty($fiscais)): ?>
                    <p>Nenhum fiscal aprovado encontrado para este concurso.</p>
                <?php else: ?>
                    <?php foreach ($fiscais as $fiscal): ?>
                        <div class="fiscal-card">
                            <div class="fiscal-info">
                                <h4><?= htmlspecialchars($fiscal['nome']) ?></h4>
                                <p><strong>CPF:</strong> <?= htmlspecialchars($fiscal['cpf']) ?></p>
                                <p><strong>Celular:</strong> <?= htmlspecialchars($fiscal['celular']) ?></p>
                                <p><strong>Valor Padrão:</strong> R$ <?= number_format($concurso_selecionado['valor_pagamento'], 2, ',', '.') ?></p>
                            </div>
                            
                            <?php if ($fiscal['pagamento_registrado'] !== null): ?>
                                <!-- Pagamento já registrado -->
                                <div class="status-pagamento <?= $fiscal['pagamento_registrado'] ? 'status-pago' : 'status-nao-pago' ?>">
                                    <?= $fiscal['pagamento_registrado'] ? '✅ Pago' : '❌ Não Pago' ?>
                                    <?php if ($fiscal['valor_pago_registrado']): ?>
                                        <br><strong>Valor: R$ <?= number_format($fiscal['valor_pago_registrado'], 2, ',', '.') ?></strong>
                                    <?php endif; ?>
                                    <br>
                                    <small>Registrado em: <?= date('d/m/Y H:i', strtotime($fiscal['data_registro_pagamento'])) ?></small>
                                </div>
                                
                                <?php if ($fiscal['observacoes_pagamento']): ?>
                                    <p><strong>Observações:</strong> <?= htmlspecialchars($fiscal['observacoes_pagamento']) ?></p>
                                <?php endif; ?>
                                
                                <!-- Botão para alterar -->
                                <button type="button" class="btn btn-secondary" onclick="alterarPagamento(<?= $fiscal['id'] ?>)">
                                    Alterar Registro
                                </button>
                                
                            <?php else: ?>
                                <!-- Registrar pagamento -->
                                <form method="POST" action="" class="pagamento-form">
                                    <input type="hidden" name="fiscal_id" value="<?= $fiscal['id'] ?>">
                                    <input type="hidden" name="concurso_id" value="<?= $concurso_selecionado['id'] ?>">
                                    
                                    <div class="pagamento-buttons">
                                        <button type="submit" name="confirmar_pagamento" value="1" class="btn-pago">
                                            ✅ Pago
                                        </button>
                                        <button type="submit" name="confirmar_pagamento" value="0" class="btn-nao-pago">
                                            ❌ Não Pago
                                        </button>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="valor_pago_<?= $fiscal['id'] ?>">Valor Pago (R$):</label>
                                        <input type="number" name="valor_pago" id="valor_pago_<?= $fiscal['id'] ?>" 
                                               class="valor-input" step="0.01" min="0" 
                                               value="<?= $concurso_selecionado['valor_pagamento'] ?>"
                                               placeholder="0,00">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="observacoes_<?= $fiscal['id'] ?>">Observações:</label>
                                        <textarea name="observacoes" id="observacoes_<?= $fiscal['id'] ?>" 
                                                  class="observacoes-input" rows="2" 
                                                  placeholder="Observações sobre o pagamento..."></textarea>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h3>🔧 Ações</h3>
            <a href="relatorios.php" class="btn btn-secondary btn-block">📊 Relatórios</a>
            <a href="presenca_mobile.php" class="btn btn-primary btn-block">📱 Controle de Presença</a>
            <a href="admin/dashboard.php" class="btn btn-success btn-block">🏠 Painel Admin</a>
            <a href="index.php" class="btn btn-info btn-block">🏠 Voltar ao Sistema</a>
        </div>
    </div>
    
    <script>
    function alterarPagamento(fiscalId) {
        if (confirm('Deseja alterar o registro de pagamento deste fiscal?')) {
            // Remover registro atual e permitir novo registro
            location.reload();
        }
    }
    
    // Auto-submit quando botão de pagamento for clicado
    document.addEventListener('DOMContentLoaded', function() {
        const pagamentoButtons = document.querySelectorAll('.btn-pago, .btn-nao-pago');
        pagamentoButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                const form = this.closest('form');
                const valorPago = form.querySelector('input[name="valor_pago"]');
                const observacoes = form.querySelector('textarea[name="observacoes"]');
                
                // Adicionar valor do botão ao form
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'pago';
                input.value = this.classList.contains('btn-pago') ? '1' : '0';
                form.appendChild(input);
                
                // Submit do form
                form.submit();
            });
        });
    });
    </script>
</body>
</html> 