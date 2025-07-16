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
        $db = getDB();
        if (!$db) {
            throw new Exception("Erro de conexão com banco de dados");
        }
        
        if (!isset($_POST['fiscal_id']) || !isset($_POST['presente']) || !isset($_POST['concurso_id'])) {
            throw new Exception("Dados do formulário incompletos");
        }
        
        $concurso_id = (int)$_POST['concurso_id'];
        $fiscal_ids = $_POST['fiscal_id'];
        $presentes = $_POST['presente'];
        $observacoes = $_POST['observacoes'] ?? [];
        $data_prova = $_POST['data_prova'] ?? date('Y-m-d');
        
        $sucessos = 0;
        $erros = 0;
        
        foreach ($fiscal_ids as $index => $fiscal_id) {
            $fiscal_id = (int)$fiscal_id;
            $presente = isset($presentes[$index]) ? $presentes[$index] : 'ausente';
            $observacao = sanitizeInput($observacoes[$index] ?? '');

            if ($presente === 'presente') {
                $status = 'presente';
            } elseif ($presente === 'justificado') {
                $status = 'justificado';
            } else {
                $status = 'ausente';
            }

            if ($fiscal_id <= 0) {
                $erros++;
                continue;
            }

            // Buscar dados de alocação para escola e sala
            $stmtAloc = $db->prepare("SELECT escola_id, sala_id FROM alocacoes_fiscais WHERE fiscal_id = ? AND status = 'ativo' LIMIT 1");
            $stmtAloc->execute([$fiscal_id]);
            $aloc = $stmtAloc->fetch(PDO::FETCH_ASSOC);
            $escola_id = $aloc['escola_id'] ?? null;
            $sala_id = $aloc['sala_id'] ?? null;

            // Gravar presença (evento único, mas editável)
            $stmt = $db->prepare(
                "INSERT INTO presenca (fiscal_id, concurso_id, escola_id, sala_id, data_presenca, tipo_presenca, status, observacoes, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 'prova', ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE status=VALUES(status), observacoes=VALUES(observacoes), data_presenca=VALUES(data_presenca), updated_at=NOW()"
            );
            $stmt->execute([$fiscal_id, $concurso_id, $escola_id, $sala_id, $data_prova, $status, $observacao]);
            $sucessos++;
        }
        
        if ($sucessos > 0) {
            $message = "Presenças registradas com sucesso! $sucessos registro(s) atualizado(s).";
            if ($erros > 0) {
                $message .= " $erros registro(s) com erro.";
            }
        } else {
            $error = "Nenhuma presença foi registrada. Verifique os dados.";
        }
    } catch (Exception $e) {
        $error = 'Erro ao registrar presença: ' . $e->getMessage();
        logActivity("Erro ao registrar presença na prova: " . $e->getMessage(), 'ERROR');
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
        $stmt = $db->prepare("SELECT * FROM concursos WHERE id = ?");
        $stmt->execute([$concurso_id]);
        $concurso_selecionado = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($concurso_selecionado) {
            $sql = "
                SELECT f.*, 
                       e.nome as escola_nome,
                       s.nome as sala_nome,
                       af.tipo_alocacao,
                       af.observacoes as observacoes_alocacao
                FROM fiscais f
                LEFT JOIN alocacoes_fiscais af ON f.id = af.fiscal_id AND af.status = 'ativo'
                LEFT JOIN escolas e ON af.escola_id = e.id
                LEFT JOIN salas s ON af.sala_id = s.id
                WHERE f.concurso_id = ? AND f.status = 'aprovado'";
            $params = [$concurso_id];
            if (isset($_GET['escola_id']) && !empty($_GET['escola_id'])) {
                $escola_id = (int)$_GET['escola_id'];
                $sql .= " AND af.escola_id = ?";
                $params[] = $escola_id;
            }
            $sql .= " ORDER BY f.nome ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Buscar status de presença para cada fiscal
            foreach ($fiscais as &$fiscal) {
                $stmtPresenca = $db->prepare("SELECT status, observacoes, data_presenca FROM presenca WHERE fiscal_id = ? AND concurso_id = ? AND tipo_presenca = 'prova' LIMIT 1");
                $stmtPresenca->execute([$fiscal['id'], $concurso_id]);
                $presenca = $stmtPresenca->fetch(PDO::FETCH_ASSOC);
                if ($presenca) {
                    $fiscal['status'] = $presenca['status'];
                    $fiscal['observacoes'] = $presenca['observacoes'];
                    $fiscal['data_presenca'] = $presenca['data_presenca'];
                } else {
                    $fiscal['status'] = null;
                    $fiscal['observacoes'] = '';
                    $fiscal['data_presenca'] = null;
                }
            }
            unset($fiscal);
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
    <title>Presença na Prova - IDH</title>
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
        .btn-presente, .btn-ausente, .btn-justificado {
            background: #222;
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
        }
        .btn-presente.active {
            background: #218838;
            color: #fff;
        }
        .btn-ausente.active {
            background: #c82333;
            color: #fff;
        }
        .btn-justificado.active {
            background: #ff9800;
            color: #222;
        }
        .status-presenca {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 5px;
            font-size: 1rem;
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
            font-size: 1rem;
        }
        .header {
            background: #007bff;
            color: white;
            padding: 28px 20px 24px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .header h1 {
            font-size: 2.2rem;
            font-weight: bold;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.25);
            margin-bottom: 8px;
        }
        .header p {
            font-size: 1.1rem;
            font-weight: 500;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.15);
        }
        .card-header.bg-primary.text-white {
            background: #0056b3 !important;
            color: #fff !important;
            font-weight: bold;
            font-size: 1.2rem;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.18);
        }
        .card-header h5 {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 0;
        }
        .fiscal-info h6 {
            font-size: 1.1rem;
            font-weight: bold;
            color: #222;
        }
        .fiscal-info small {
            color: #444;
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
            <h1>📝 Presença na Prova</h1>
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
                    <i class="fas fa-file-alt me-2"></i>
                    Prova - <?= htmlspecialchars($concurso_selecionado['titulo']) ?>
                </h5>
            </div>
            <div class="card-body">
                <p><strong>Data da Prova:</strong> <?= date('d/m/Y', strtotime($concurso_selecionado['data_prova'])) ?></p>
                
                <?php if (!empty($fiscais)): ?>
                <form method="POST">
                    <input type="hidden" name="concurso_id" value="<?= $concurso_selecionado['id'] ?>">
                    <input type="hidden" name="data_prova" value="<?= $concurso_selecionado['data_prova'] ?>">
                    
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
                            <button type="button" class="btn-presente <?= $fiscal['status'] === 'presente' ? 'active' : '' ?>" 
                                    onclick="marcarPresenca(<?= $fiscal['id'] ?>, 'presente')">
                                ✅ Presente
                            </button>
                            <button type="button" class="btn-ausente <?= $fiscal['status'] === 'ausente' ? 'active' : '' ?>" 
                                    onclick="marcarPresenca(<?= $fiscal['id'] ?>, 'ausente')">
                                ❌ Ausente
                            </button>
                            <button type="button" class="btn-justificado <?= $fiscal['status'] === 'justificado' ? 'active' : '' ?>" 
                                    onclick="marcarPresenca(<?= $fiscal['id'] ?>, 'justificado')">
                                👋 Justificado
                            </button>
                        </div>
                        
                        <input type="hidden" name="fiscal_id[]" value="<?= $fiscal['id'] ?>">
                        <input type="hidden" name="presente[]" id="presente_<?= $fiscal['id'] ?>" 
                               value="<?= $fiscal['status'] !== null ? $fiscal['status'] : 'ausente' ?>">
                        
                        <input type="text" name="observacoes[]" class="observacoes-input" 
                               placeholder="Observações (opcional)" 
                               value="<?= htmlspecialchars($fiscal['observacoes'] ?? '') ?>">
                        
                        <?php if ($fiscal['status'] !== null): ?>
                        <div class="status-presenca status-<?= $fiscal['status'] ?>">
                            <?= ucfirst($fiscal['status']) ?>
                            <?php if (!empty($fiscal['data_presenca'])): ?>
                            <br><small>Registrado em: <?= date('d/m/Y H:i', strtotime($fiscal['data_presenca'])) ?></small>
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
    function marcarPresenca(fiscalId, status) {
        // Encontrar o card do fiscal
        const fiscalCard = document.querySelector(`input[name=\"fiscal_id[]\"][value=\"${fiscalId}\"]`).closest('.fiscal-card');
        const btnPresente = fiscalCard.querySelector('.btn-presente');
        const btnAusente = fiscalCard.querySelector('.btn-ausente');
        const btnJustificado = fiscalCard.querySelector('.btn-justificado');
        const inputPresente = fiscalCard.querySelector(`input[name=\"presente[]\"]`);
        
        // Remover classes active
        btnPresente.classList.remove('active');
        btnAusente.classList.remove('active');
        if (btnJustificado) {
            btnJustificado.classList.remove('active');
        }
        
        // Adicionar classe active ao botão clicado
        if (status === 'presente') {
            btnPresente.classList.add('active');
        } else if (status === 'ausente') {
            btnAusente.classList.add('active');
        } else if (status === 'justificado') {
            btnJustificado.classList.add('active');
        }
        
        // Atualizar input hidden
        inputPresente.value = status;
    }
    </script>
</body>
</html> 