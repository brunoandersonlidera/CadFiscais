<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();

// Parâmetros de filtro
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : 0;
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

// Buscar concursos
$concursos = [];
try {
    $stmt = $db->query("SELECT id, titulo FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC");
    $concursos = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar concursos: ' . $e->getMessage(), 'ERROR');
}

// Buscar dados financeiros
$dados_financeiros = [];
$estatisticas = [
    'total_pagamentos' => 0,
    'total_valor' => 0,
    'total_pago' => 0,
    'total_pendente' => 0,
    'por_forma_pagamento' => []
];

try {
    $sql = "
        SELECT p.*, f.nome as fiscal_nome, f.cpf as fiscal_cpf,
               c.titulo as concurso_titulo
        FROM pagamentos p
        INNER JOIN fiscais f ON p.fiscal_id = f.id
        INNER JOIN concursos c ON p.concurso_id = c.id
        WHERE 1=1
    ";
    $params = [];
    
    if ($concurso_id) {
        $sql .= " AND p.concurso_id = ?";
        $params[] = $concurso_id;
    }
    
    if ($data_inicio) {
        $sql .= " AND p.data_pagamento >= ?";
        $params[] = $data_inicio;
    }
    
    if ($data_fim) {
        $sql .= " AND p.data_pagamento <= ?";
        $params[] = $data_fim;
    }
    
    $sql .= " ORDER BY p.data_pagamento DESC, f.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $dados_financeiros = $stmt->fetchAll();
    
    // Calcular estatísticas
    foreach ($dados_financeiros as $pagamento) {
        $estatisticas['total_pagamentos']++;
        $estatisticas['total_valor'] += $pagamento['valor'];
        
        if ($pagamento['status_pagamento'] == 'pago') {
            $estatisticas['total_pago'] += $pagamento['valor'];
        } else {
            $estatisticas['total_pendente'] += $pagamento['valor'];
        }
        
        $forma = $pagamento['forma_pagamento'];
        if (!isset($estatisticas['por_forma_pagamento'][$forma])) {
            $estatisticas['por_forma_pagamento'][$forma] = 0;
        }
        $estatisticas['por_forma_pagamento'][$forma] += $pagamento['valor'];
    }
    
} catch (Exception $e) {
    logActivity('Erro ao buscar dados financeiros: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Resumo Financeiro';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-chart-pie me-2"></i>
                Resumo Financeiro
            </h1>
            <a href="relatorios.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Voltar
            </a>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>
                    Filtros
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="concurso_id" class="form-label">Concurso</label>
                            <select class="form-select" id="concurso_id" name="concurso_id">
                                <option value="">Todos os concursos</option>
                                <?php foreach ($concursos as $concurso): ?>
                                <option value="<?= $concurso['id'] ?>" <?= $concurso_id == $concurso['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($concurso['titulo']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="data_inicio" class="form-label">Data Início</label>
                            <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?= $data_inicio ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="data_fim" class="form-label">Data Fim</label>
                            <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?= $data_fim ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>
                                    Filtrar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?= $estatisticas['total_pagamentos'] ?></h4>
                        <p class="mb-0">Total de Pagamentos</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-receipt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">R$ <?= number_format($estatisticas['total_valor'], 2, ',', '.') ?></h4>
                        <p class="mb-0">Valor Total</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-money-bill-wave fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">R$ <?= number_format($estatisticas['total_pago'], 2, ',', '.') ?></h4>
                        <p class="mb-0">Total Pago</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">R$ <?= number_format($estatisticas['total_pendente'], 2, ',', '.') ?></h4>
                        <p class="mb-0">Total Pendente</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gráfico por Forma de Pagamento -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Por Forma de Pagamento
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Forma de Pagamento</th>
                                <th>Valor</th>
                                <th>Percentual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estatisticas['por_forma_pagamento'] as $forma => $valor): ?>
                            <tr>
                                <td><?= ucfirst($forma) ?></td>
                                <td>R$ <?= number_format($valor, 2, ',', '.') ?></td>
                                <td><?= $estatisticas['total_valor'] > 0 ? number_format(($valor / $estatisticas['total_valor']) * 100, 1) : 0 ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Resumo por Status
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <h3 class="text-success">R$ <?= number_format($estatisticas['total_pago'], 2, ',', '.') ?></h3>
                            <p class="text-muted">Pagos</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h3 class="text-warning">R$ <?= number_format($estatisticas['total_pendente'], 2, ',', '.') ?></h3>
                            <p class="text-muted">Pendentes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Pagamentos -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Lista de Pagamentos
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Fiscal</th>
                                <th>CPF</th>
                                <th>Concurso</th>
                                <th>Valor</th>
                                <th>Forma</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dados_financeiros as $pagamento): ?>
                            <tr>
                                <td><?= htmlspecialchars($pagamento['fiscal_nome']) ?></td>
                                <td><?= htmlspecialchars($pagamento['fiscal_cpf']) ?></td>
                                <td><?= htmlspecialchars($pagamento['concurso_titulo']) ?></td>
                                <td>R$ <?= number_format($pagamento['valor'], 2, ',', '.') ?></td>
                                <td><?= ucfirst($pagamento['forma_pagamento']) ?></td>
                                <td><?= date('d/m/Y', strtotime($pagamento['data_pagamento'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= $pagamento['status_pagamento'] == 'pago' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($pagamento['status_pagamento']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="recibo_pagamento.php?id=<?= $pagamento['id'] ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-receipt me-1"></i>
                                        Recibo
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($dados_financeiros)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum pagamento encontrado com os filtros aplicados.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 