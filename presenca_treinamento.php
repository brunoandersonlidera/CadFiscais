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
        
        // Verificar se já existe registro de presença no treinamento
        $stmt = $db->prepare("SELECT id FROM presenca_treinamento WHERE fiscal_id = ? AND concurso_id = ?");
        $stmt->execute([$fiscal_id, $concurso_id]);
        
        if ($stmt->rowCount() > 0) {
            // Atualizar presença existente
            $stmt = $db->prepare("UPDATE presenca_treinamento SET presente = ?, observacoes = ?, updated_at = CURRENT_TIMESTAMP WHERE fiscal_id = ? AND concurso_id = ?");
            $stmt->execute([$presente, $observacoes, $fiscal_id, $concurso_id]);
        } else {
            // Inserir nova presença
            $stmt = $db->prepare("INSERT INTO presenca_treinamento (fiscal_id, concurso_id, presente, observacoes, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$fiscal_id, $concurso_id, $presente, $observacoes]);
        }
        
        $message = 'Presença no treinamento registrada com sucesso!';
        logActivity("Presença no treinamento registrada para fiscal ID: $fiscal_id, Concurso ID: $concurso_id, Presente: " . ($presente ? 'Sim' : 'Não'), 'INFO');
        
    } catch (Exception $e) {
        $error = 'Erro ao registrar presença: ' . $e->getMessage();
        logActivity("Erro ao registrar presença no treinamento: " . $e->getMessage(), 'ERROR');
    }
}

// Criar tabela presenca_treinamento se não existir
try {
    $db = getDB();
    $stmt = $db->query("SHOW TABLES LIKE 'presenca_treinamento'");
    if ($stmt->rowCount() == 0) {
        $db->exec("CREATE TABLE presenca_treinamento (
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
    }
} catch (Exception $e) {
    $error = 'Erro ao criar tabela de presença no treinamento: ' . $e->getMessage();
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

// Buscar escolas do concurso
$escolas = [];
if (isset($_GET['concurso_id']) && !empty($_GET['concurso_id'])) {
    $concurso_id = (int)$_GET['concurso_id'];
    
    try {
        $stmt = $db->prepare("SELECT id, nome FROM escolas WHERE concurso_id = ? AND status = 'ativo' ORDER BY nome");
        $stmt->execute([$concurso_id]);
        $escolas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = 'Erro ao carregar escolas: ' . $e->getMessage();
    }
}

// Buscar fiscais se concurso e escola selecionados
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
            // Se escola selecionada, filtrar por escola
            $where_conditions = ["f.concurso_id = ?", "f.status = 'aprovado'"];
            $params = [$concurso_id];
            
            if (isset($_GET['escola_id']) && !empty($_GET['escola_id'])) {
                $escola_id = (int)$_GET['escola_id'];
                $where_conditions[] = "f.escola_id = ?";
                $params[] = $escola_id;
            }
            
            $where_clause = implode(" AND ", $where_conditions);
            
            // Buscar fiscais aprovados do concurso com dados de presença no treinamento e informações de alocação
            $stmt = $db->prepare("
                SELECT f.*, 
                       e.nome as escola_nome,
                       s.nome as sala_nome,
                       af.tipo_alocacao,
                       af.observacoes as observacoes_alocacao,
                       CASE WHEN pt.presente IS NOT NULL THEN pt.presente ELSE NULL END as presenca_registrada,
                       pt.observacoes as observacoes_presenca,
                       pt.created_at as data_registro_presenca
                FROM fiscais f
                LEFT JOIN escolas e ON f.escola_id = e.id
                LEFT JOIN salas s ON f.sala_id = s.id
                LEFT JOIN alocacoes_fiscais af ON f.id = af.fiscal_id AND af.status = 'ativo'
                LEFT JOIN presenca_treinamento pt ON f.id = pt.fiscal_id AND pt.concurso_id = f.concurso_id
                WHERE $where_clause
                ORDER BY e.nome, s.nome, f.nome
            ");
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
    <title>Presença no Treinamento - IDH</title>
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
        .header {
            background: #007bff;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .concurso-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mobile-optimized">
        <div class="header">
            <h1>📚 Presença no Treinamento</h1>
            <p>Sistema IDH - Controle de Presença</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <!-- Seleção de Concurso -->
        <div class="form-group">
            <label for="concurso_id"><strong>Selecione o Concurso:</strong></label>
            <select id="concurso_id" class="concurso-select" onchange="window.location.href='?concurso_id=' + this.value">
                <option value="">Escolha um concurso...</option>
                <?php foreach ($concursos as $concurso): ?>
                <option value="<?= $concurso['id'] ?>" <?= (isset($_GET['concurso_id']) && $_GET['concurso_id'] == $concurso['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($concurso['titulo']) ?> - <?= date('d/m/Y', strtotime($concurso['data_prova'])) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Seleção de Escola -->
        <?php if (!empty($escolas)): ?>
        <div class="form-group">
            <label for="escola_id"><strong>Selecione a Escola:</strong></label>
            <select id="escola_id" class="concurso-select" onchange="window.location.href='?concurso_id=<?= $_GET['concurso_id'] ?? '' ?>&escola_id=' + this.value">
                <option value="">Todas as escolas...</option>
                <?php foreach ($escolas as $escola): ?>
                <option value="<?= $escola['id'] ?>" <?= (isset($_GET['escola_id']) && $_GET['escola_id'] == $escola['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($escola['nome']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <?php if ($concurso_selecionado): ?>
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-graduation-cap me-2"></i>
                    Treinamento - <?= htmlspecialchars($concurso_selecionado['titulo']) ?>
                </h5>
            </div>
            <div class="card-body">
                <p><strong>Data da Prova:</strong> <?= date('d/m/Y', strtotime($concurso_selecionado['data_prova'])) ?></p>
                
                <?php if (!empty($fiscais)): ?>
                <form method="POST">
                    <input type="hidden" name="concurso_id" value="<?= $concurso_selecionado['id'] ?>">
                    
                    <?php foreach ($fiscais as $fiscal): ?>
                    <div class="fiscal-card">
                        <div class="fiscal-info">
                            <h6><?= htmlspecialchars($fiscal['nome']) ?></h6>
                            <small class="text-muted">
                                CPF: <?= formatCPF($fiscal['cpf']) ?> | 
                                Celular: <?= formatPhone($fiscal['celular']) ?>
                                <?php if ($fiscal['escola_nome']): ?>
                                <br>🏫 Escola: <?= htmlspecialchars($fiscal['escola_nome']) ?>
                                <?php endif; ?>
                                <?php if ($fiscal['tipo_alocacao']): ?>
                                <br>📍 Localização: 
                                <?php
                                $localizacao = '';
                                switch ($fiscal['tipo_alocacao']) {
                                    case 'sala':
                                        $localizacao = '🚪 Sala: ' . ($fiscal['sala_nome'] ?? 'N/A');
                                        break;
                                    case 'corredor':
                                        $localizacao = '🚶 Corredor';
                                        break;
                                    case 'entrada':
                                        $localizacao = '🚪 Portaria/Entrada';
                                        break;
                                    case 'banheiro':
                                        $localizacao = '🚻 Banheiro';
                                        break;
                                    case 'outro':
                                        $localizacao = '📍 Outro local';
                                        break;
                                    default:
                                        $localizacao = '📍 ' . ucfirst($fiscal['tipo_alocacao']);
                                }
                                echo $localizacao;
                                if ($fiscal['observacoes_alocacao']) {
                                    echo ' - ' . htmlspecialchars($fiscal['observacoes_alocacao']);
                                }
                                ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <div class="presenca-buttons">
                            <button type="button" class="btn-presente <?= $fiscal['presenca_registrada'] === 1 ? 'active' : '' ?>" 
                                    onclick="marcarPresenca(<?= $fiscal['id'] ?>, 1)">
                                ✅ Presente
                            </button>
                            <button type="button" class="btn-ausente <?= $fiscal['presenca_registrada'] === 0 ? 'active' : '' ?>" 
                                    onclick="marcarPresenca(<?= $fiscal['id'] ?>, 0)">
                                ❌ Ausente
                            </button>
                        </div>
                        
                        <input type="hidden" name="fiscal_id[]" value="<?= $fiscal['id'] ?>">
                        <input type="hidden" name="presente[]" id="presente_<?= $fiscal['id'] ?>" 
                               value="<?= $fiscal['presenca_registrada'] ?? '' ?>">
                        
                        <input type="text" name="observacoes[]" class="observacoes-input" 
                               placeholder="Observações (opcional)" 
                               value="<?= htmlspecialchars($fiscal['observacoes_presenca'] ?? '') ?>">
                        
                        <?php if ($fiscal['presenca_registrada'] !== null): ?>
                        <div class="status-presenca status-<?= $fiscal['presenca_registrada'] ? 'presente' : 'ausente' ?>">
                            <?= $fiscal['presenca_registrada'] ? '✅ Presente' : '❌ Ausente' ?>
                            <?php if ($fiscal['data_registro_presenca']): ?>
                            <br><small>Registrado em: <?= date('d/m/Y H:i', strtotime($fiscal['data_registro_presenca'])) ?></small>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="status-presenca status-pendente">
                            ⏳ Pendente
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <button type="submit" name="confirmar_presenca" class="btn btn-primary btn-lg w-100">
                        💾 Salvar Presenças
                    </button>
                </form>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum fiscal aprovado encontrado para este concurso.
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Voltar ao Início
            </a>
        </div>
    </div>

    <script>
    function marcarPresenca(fiscalId, presente) {
        // Encontrar o card do fiscal
        const fiscalCard = document.querySelector(`input[name="fiscal_id[]"][value="${fiscalId}"]`).closest('.fiscal-card');
        
        if (fiscalCard) {
            const btnPresente = fiscalCard.querySelector('.btn-presente');
            const btnAusente = fiscalCard.querySelector('.btn-ausente');
            const inputPresente = fiscalCard.querySelector(`input[name="presente[]"]`);
            
            // Remover classes active
            btnPresente.classList.remove('active');
            btnAusente.classList.remove('active');
            
            // Adicionar classe active ao botão clicado
            if (presente) {
                btnPresente.classList.add('active');
            } else {
                btnAusente.classList.add('active');
            }
            
            // Atualizar input hidden
            inputPresente.value = presente;
        }
    }
    </script>
</body>
</html> 