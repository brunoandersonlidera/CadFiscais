<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();

// Parâmetros da URL
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : 0;
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

// Validar parâmetros
if (!$concurso_id || !$tipo) {
    showMessage('Parâmetros inválidos', 'error');
    redirect('relatorios.php');
}

// Buscar dados do concurso
try {
    $stmt = $db->prepare("SELECT * FROM concursos WHERE id = ?");
    $stmt->execute([$concurso_id]);
    $concurso = $stmt->fetch();
    
    if (!$concurso) {
        showMessage('Concurso não encontrado', 'error');
        redirect('relatorios.php');
    }
} catch (Exception $e) {
    logActivity('Erro ao buscar concurso: ' . $e->getMessage(), 'ERROR');
    showMessage('Erro ao buscar dados do concurso', 'error');
    redirect('relatorios.php');
}

// Buscar escolas do concurso
$escolas = [];
try {
    $stmt = $db->prepare("SELECT DISTINCT e.* FROM escolas e 
                         INNER JOIN salas s ON e.id = s.escola_id 
                         INNER JOIN alocacoes_fiscais af ON s.id = af.sala_id 
                         WHERE af.concurso_id = ? AND e.status = 'ativo' 
                         ORDER BY e.nome");
    $stmt->execute([$concurso_id]);
    $escolas = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar escolas: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Relatório por Concurso - ' . $concurso['titulo'];
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-clipboard-list me-2"></i>
                Relatório por Concurso
            </h1>
            <a href="relatorios.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Voltar
            </a>
        </div>
    </div>
</div>

<!-- Informações do Concurso -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Informações do Concurso
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Título:</strong> <?= htmlspecialchars($concurso['titulo']) ?></p>
                        <p><strong>Data da Prova:</strong> <?= date('d/m/Y', strtotime($concurso['data_prova'])) ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?= $concurso['status'] == 'ativo' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($concurso['status']) ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Tipo de Relatório:</strong> 
                            <span class="badge bg-info"><?= ucfirst($tipo) ?></span>
                        </p>
                        <p><strong>Escolas Envolvidas:</strong> <?= count($escolas) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Gerar relatório baseado no tipo
switch ($tipo) {
    case 'fiscais':
        gerarRelatorioFiscais($db, $concurso_id, $concurso);
        break;
    case 'alocacoes':
        gerarRelatorioAlocacoes($db, $concurso_id, $concurso);
        break;
    case 'presenca':
        gerarRelatorioPresenca($db, $concurso_id, $concurso);
        break;
    case 'pagamentos':
        gerarRelatorioPagamentos($db, $concurso_id, $concurso);
        break;
    default:
        echo '<div class="alert alert-warning">Tipo de relatório não reconhecido</div>';
}
?>

<?php
function gerarRelatorioFiscais($db, $concurso_id, $concurso) {
    try {
        $stmt = $db->prepare("
            SELECT f.*, 
                   CASE WHEN af.id IS NOT NULL THEN 'Alocado' ELSE 'Não Alocado' END as status_alocacao,
                   e.nome as escola_nome,
                   s.nome as sala_nome
            FROM fiscais f
            LEFT JOIN alocacoes_fiscais af ON f.id = af.fiscal_id AND af.concurso_id = ?
            LEFT JOIN salas s ON af.sala_id = s.id
            LEFT JOIN escolas e ON s.escola_id = e.id
            WHERE f.concurso_id = ?
            ORDER BY f.nome
        ");
        $stmt->execute([$concurso_id, $concurso_id]);
        $fiscais = $stmt->fetchAll();
        ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>
                            Fiscais Cadastrados - <?= htmlspecialchars($concurso['titulo']) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Nome</th>
                                        <th>CPF</th>
                                        <th>Telefone</th>
                                        <th>Status</th>
                                        <th>Alocação</th>
                                        <th>Escola</th>
                                        <th>Sala</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fiscais as $fiscal): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($fiscal['nome']) ?></td>
                                        <td><?= htmlspecialchars($fiscal['cpf']) ?></td>
                                        <td><?= htmlspecialchars($fiscal['telefone']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $fiscal['status'] == 'aprovado' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($fiscal['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $fiscal['status_alocacao'] == 'Alocado' ? 'success' : 'secondary' ?>">
                                                <?= $fiscal['status_alocacao'] ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($fiscal['escola_nome'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($fiscal['sala_nome'] ?? '-') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <a href="exportar_excel_fiscais.php?concurso_id=<?= $concurso_id ?>" class="btn btn-success">
                                <i class="fas fa-file-excel me-2"></i>
                                Exportar Excel
                            </a>
                            <a href="exportar_pdf_fiscais.php?concurso_id=<?= $concurso_id ?>" class="btn btn-danger">
                                <i class="fas fa-file-pdf me-2"></i>
                                Exportar PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } catch (Exception $e) {
        logActivity('Erro ao gerar relatório de fiscais: ' . $e->getMessage(), 'ERROR');
        echo '<div class="alert alert-danger">Erro ao gerar relatório de fiscais</div>';
    }
}

function gerarRelatorioAlocacoes($db, $concurso_id, $concurso) {
    try {
        $stmt = $db->prepare("
            SELECT af.*, f.nome as fiscal_nome, f.cpf as fiscal_cpf, f.telefone as fiscal_telefone,
                   e.nome as escola_nome, s.nome as sala_nome, s.capacidade
            FROM alocacoes_fiscais af
            INNER JOIN fiscais f ON af.fiscal_id = f.id
            INNER JOIN salas s ON af.sala_id = s.id
            INNER JOIN escolas e ON s.escola_id = e.id
            WHERE af.concurso_id = ?
            ORDER BY e.nome, s.nome, f.nome
        ");
        $stmt->execute([$concurso_id]);
        $alocacoes = $stmt->fetchAll();
        ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Alocações - <?= htmlspecialchars($concurso['titulo']) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Escola</th>
                                        <th>Sala</th>
                                        <th>Capacidade</th>
                                        <th>Fiscal</th>
                                        <th>CPF</th>
                                        <th>Telefone</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alocacoes as $alocacao): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($alocacao['escola_nome']) ?></td>
                                        <td><?= htmlspecialchars($alocacao['sala_nome']) ?></td>
                                        <td><?= $alocacao['capacidade'] ?></td>
                                        <td><?= htmlspecialchars($alocacao['fiscal_nome']) ?></td>
                                        <td><?= htmlspecialchars($alocacao['fiscal_cpf']) ?></td>
                                        <td><?= htmlspecialchars($alocacao['fiscal_telefone']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $alocacao['status'] == 'ativo' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($alocacao['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } catch (Exception $e) {
        logActivity('Erro ao gerar relatório de alocações: ' . $e->getMessage(), 'ERROR');
        echo '<div class="alert alert-danger">Erro ao gerar relatório de alocações</div>';
    }
}

function gerarRelatorioPresenca($db, $concurso_id, $concurso) {
    try {
        $stmt = $db->prepare("
            SELECT af.*, f.nome as fiscal_nome, f.cpf as fiscal_cpf,
                   e.nome as escola_nome, s.nome as sala_nome
            FROM alocacoes_fiscais af
            INNER JOIN fiscais f ON af.fiscal_id = f.id
            INNER JOIN salas s ON af.sala_id = s.id
            INNER JOIN escolas e ON s.escola_id = e.id
            WHERE af.concurso_id = ? AND af.status = 'ativo'
            ORDER BY e.nome, s.nome, f.nome
        ");
        $stmt->execute([$concurso_id]);
        $fiscais = $stmt->fetchAll();
        ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>
                            Lista de Presença - <?= htmlspecialchars($concurso['titulo']) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Escola</th>
                                        <th>Sala</th>
                                        <th>Fiscal</th>
                                        <th>CPF</th>
                                        <th>Assinatura</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fiscais as $fiscal): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($fiscal['escola_nome']) ?></td>
                                        <td><?= htmlspecialchars($fiscal['sala_nome']) ?></td>
                                        <td><?= htmlspecialchars($fiscal['fiscal_nome']) ?></td>
                                        <td><?= htmlspecialchars($fiscal['fiscal_cpf']) ?></td>
                                        <td style="height: 50px; border: 1px solid #ccc;"></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <a href="lista_presenca.php?concurso_id=<?= $concurso_id ?>" class="btn btn-warning">
                                <i class="fas fa-print me-2"></i>
                                Imprimir Lista
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } catch (Exception $e) {
        logActivity('Erro ao gerar relatório de presença: ' . $e->getMessage(), 'ERROR');
        echo '<div class="alert alert-danger">Erro ao gerar relatório de presença</div>';
    }
}

function gerarRelatorioPagamentos($db, $concurso_id, $concurso) {
    try {
        $stmt = $db->prepare("
            SELECT f.*, af.id as alocacao_id,
                   CASE WHEN af.id IS NOT NULL THEN 'Alocado' ELSE 'Não Alocado' END as status_alocacao
            FROM fiscais f
            LEFT JOIN alocacoes_fiscais af ON f.id = af.fiscal_id AND af.concurso_id = ?
            WHERE f.concurso_id = ?
            ORDER BY f.nome
        ");
        $stmt->execute([$concurso_id, $concurso_id]);
        $fiscais = $stmt->fetchAll();
        ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-money-bill-wave me-2"></i>
                            Controle de Pagamentos - <?= htmlspecialchars($concurso['titulo']) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Fiscal</th>
                                        <th>CPF</th>
                                        <th>Telefone</th>
                                        <th>Status</th>
                                        <th>Alocação</th>
                                        <th>Valor</th>
                                        <th>Status Pagamento</th>
                                        <th>Data Pagamento</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fiscais as $fiscal): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($fiscal['nome']) ?></td>
                                        <td><?= htmlspecialchars($fiscal['cpf']) ?></td>
                                        <td><?= htmlspecialchars($fiscal['telefone']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $fiscal['status'] == 'aprovado' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($fiscal['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $fiscal['status_alocacao'] == 'Alocado' ? 'success' : 'secondary' ?>">
                                                <?= $fiscal['status_alocacao'] ?>
                                            </span>
                                        </td>
                                        <td>R$ 0,00</td>
                                        <td>
                                            <span class="badge bg-secondary">Pendente</span>
                                        </td>
                                        <td>-</td>
                                        <td>
                                            <a href="marcar_pagamento_pago.php?fiscal_id=<?= $fiscal['id'] ?>" 
                                               class="btn btn-success btn-sm">
                                                <i class="fas fa-check me-1"></i>
                                                Marcar Pago
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <a href="planilha_pagamentos.php?concurso_id=<?= $concurso_id ?>" class="btn btn-success">
                                <i class="fas fa-file-excel me-2"></i>
                                Planilha de Pagamentos
                            </a>
                            <a href="lista_pagamentos.php?concurso_id=<?= $concurso_id ?>" class="btn btn-info">
                                <i class="fas fa-list me-2"></i>
                                Lista de Pagamentos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } catch (Exception $e) {
        logActivity('Erro ao gerar relatório de pagamentos: ' . $e->getMessage(), 'ERROR');
        echo '<div class="alert alert-danger">Erro ao gerar relatório de pagamentos</div>';
    }
}
?>

<?php include '../includes/footer.php'; ?> 