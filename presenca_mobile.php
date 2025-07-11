<?php
require_once 'config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

$message = '';
$error = '';

// Processar confirmação de presença
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_presenca'])) {
    try {
        $fiscal_id = (int)$_POST['fiscal_id'];
        $concurso_id = (int)$_POST['concurso_id'];
        $presente = (int)$_POST['presente'];
        $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
        
        $db = getDB();
        if (!$db) {
            throw new Exception("Erro de conexão com banco de dados");
        }
        
        // Verificar se já existe registro de presença
        $stmt = $db->prepare("SELECT id FROM presenca_fiscais WHERE fiscal_id = ? AND concurso_id = ?");
        $stmt->execute([$fiscal_id, $concurso_id]);
        
        if ($stmt->rowCount() > 0) {
            // Atualizar presença existente
            $stmt = $db->prepare("UPDATE presenca_fiscais SET presente = ?, observacoes = ?, updated_at = CURRENT_TIMESTAMP WHERE fiscal_id = ? AND concurso_id = ?");
            $stmt->execute([$presente, $observacoes, $fiscal_id, $concurso_id]);
        } else {
            // Inserir nova presença
            $stmt = $db->prepare("INSERT INTO presenca_fiscais (fiscal_id, concurso_id, presente, observacoes, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$fiscal_id, $concurso_id, $presente, $observacoes]);
        }
        
        $message = 'Presença registrada com sucesso!';
        logActivity("Presença registrada para fiscal ID: $fiscal_id, Concurso ID: $concurso_id, Presente: " . ($presente ? 'Sim' : 'Não'), 'INFO');
        
    } catch (Exception $e) {
        $error = 'Erro ao registrar presença: ' . $e->getMessage();
        logActivity("Erro ao registrar presença: " . $e->getMessage(), 'ERROR');
    }
}

// Buscar concursos ativos
$concursos = [];
try {
    $db = getDB();
    if ($db) {
        $stmt = $db->query("SELECT id, titulo, data_prova FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC");
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
                       CASE WHEN pf.presente IS NOT NULL THEN pf.presente ELSE NULL END as presenca_registrada,
                       pf.observacoes as observacoes_presenca,
                       pf.created_at as data_registro_presenca
                FROM fiscais f
                LEFT JOIN presenca_fiscais pf ON f.id = pf.fiscal_id AND pf.concurso_id = f.concurso_id
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
    <title>Controle de Presença - IDH</title>
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
        .presenca-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .btn-presente {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-ausente {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-presente.active {
            background: #218838;
        }
        .btn-ausente.active {
            background: #c82333;
        }
        .status-presenca {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 5px;
        }
        .status-presente {
            background: #d4edda;
            color: #155724;
        }
        .status-ausente {
            background: #f8d7da;
            color: #721c24;
        }
        .status-pendente {
            background: #fff3cd;
            color: #856404;
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
            <h1>📱 Controle de Presença</h1>
            <p>Sistema IDH - Versão Mobile</p>
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
                <h2>📋 <?= htmlspecialchars($concurso_selecionado['titulo']) ?></h2>
                <div class="info-box">
                    <p><strong>Data da Prova:</strong> <?= date('d/m/Y', strtotime($concurso_selecionado['data_prova'])) ?></p>
                    <p><strong>Total de Fiscais:</strong> <?= count($fiscais) ?></p>
                    <p><strong>Presentes:</strong> <?= count(array_filter($fiscais, function($f) { return $f['presenca_registrada'] === 1; })) ?></p>
                    <p><strong>Ausentes:</strong> <?= count(array_filter($fiscais, function($f) { return $f['presenca_registrada'] === 0; })) ?></p>
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
                            </div>
                            
                            <?php if ($fiscal['presenca_registrada'] !== null): ?>
                                <!-- Presença já registrada -->
                                <div class="status-presenca <?= $fiscal['presenca_registrada'] ? 'status-presente' : 'status-ausente' ?>">
                                    <?= $fiscal['presenca_registrada'] ? '✅ Presente' : '❌ Ausente' ?>
                                    <br>
                                    <small>Registrado em: <?= date('d/m/Y H:i', strtotime($fiscal['data_registro_presenca'])) ?></small>
                                </div>
                                
                                <?php if ($fiscal['observacoes_presenca']): ?>
                                    <p><strong>Observações:</strong> <?= htmlspecialchars($fiscal['observacoes_presenca']) ?></p>
                                <?php endif; ?>
                                
                                <!-- Botão para alterar -->
                                <button type="button" class="btn btn-secondary" onclick="alterarPresenca(<?= $fiscal['id'] ?>)">
                                    Alterar Registro
                                </button>
                                
                            <?php else: ?>
                                <!-- Registrar presença -->
                                <form method="POST" action="" class="presenca-form">
                                    <input type="hidden" name="fiscal_id" value="<?= $fiscal['id'] ?>">
                                    <input type="hidden" name="concurso_id" value="<?= $concurso_selecionado['id'] ?>">
                                    
                                    <div class="presenca-buttons">
                                        <button type="submit" name="confirmar_presenca" value="1" class="btn-presente">
                                            ✅ Presente
                                        </button>
                                        <button type="submit" name="confirmar_presenca" value="0" class="btn-ausente">
                                            ❌ Ausente
                                        </button>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="observacoes_<?= $fiscal['id'] ?>">Observações:</label>
                                        <textarea name="observacoes" id="observacoes_<?= $fiscal['id'] ?>" 
                                                  class="observacoes-input" rows="2" 
                                                  placeholder="Observações sobre a presença..."></textarea>
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
            <a href="admin/dashboard.php" class="btn btn-primary btn-block">🏠 Painel Admin</a>
            <a href="index.php" class="btn btn-success btn-block">🏠 Voltar ao Sistema</a>
        </div>
    </div>
    
    <script>
    function alterarPresenca(fiscalId) {
        if (confirm('Deseja alterar o registro de presença deste fiscal?')) {
            // Remover registro atual e permitir novo registro
            // Isso pode ser implementado com AJAX ou redirecionamento
            location.reload();
        }
    }
    
    // Auto-submit quando botão de presença for clicado
    document.addEventListener('DOMContentLoaded', function() {
        const presencaButtons = document.querySelectorAll('.btn-presente, .btn-ausente');
        presencaButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                const form = this.closest('form');
                const observacoes = form.querySelector('textarea[name="observacoes"]');
                
                // Adicionar valor do botão ao form
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'presente';
                input.value = this.classList.contains('btn-presente') ? '1' : '0';
                form.appendChild(input);
                
                // Submit do form
                form.submit();
            });
        });
    });
    </script>
</body>
</html> 