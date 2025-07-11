<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$pagamentos = [];

// Filtros
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : null;
$status_pagamento = isset($_GET['status_pagamento']) ? $_GET['status_pagamento'] : '';
$forma_pagamento = isset($_GET['forma_pagamento']) ? $_GET['forma_pagamento'] : '';
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

try {
    $sql = "
        SELECT p.*, f.nome as fiscal_nome, f.cpf as fiscal_cpf, f.celular as fiscal_celular,
               c.titulo as concurso_titulo, u.nome as usuario_nome
        FROM pagamentos p
        LEFT JOIN fiscais f ON p.fiscal_id = f.id
        LEFT JOIN concursos c ON p.concurso_id = c.id
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        WHERE 1=1
    ";
    $params = [];
    
    if ($concurso_id) {
        $sql .= " AND p.concurso_id = ?";
        $params[] = $concurso_id;
    }
    
    if ($status_pagamento) {
        $sql .= " AND p.status = ?";
        $params[] = $status_pagamento;
    }
    
    if ($forma_pagamento) {
        $sql .= " AND p.forma_pagamento = ?";
        $params[] = $forma_pagamento;
    }
    
    if ($data_inicio) {
        $sql .= " AND DATE(p.data_pagamento) >= ?";
        $params[] = $data_inicio;
    }
    
    if ($data_fim) {
        $sql .= " AND DATE(p.data_pagamento) <= ?";
        $params[] = $data_fim;
    }
    
    $sql .= " ORDER BY p.data_pagamento DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $pagamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar pagamentos: ' . $e->getMessage(), 'ERROR');
}

// Buscar concursos para filtro
$concursos = [];
try {
    $stmt = $db->query("SELECT id, titulo FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC");
    $concursos = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar concursos: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Planilha de Pagamentos';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-table me-2"></i>
                Planilha de Pagamentos
            </h1>
            <div>
                <button onclick="exportarCSV()" class="btn btn-success">
                    <i class="fas fa-file-csv me-2"></i>
                    Exportar CSV
                </button>
                <button onclick="exportarExcel()" class="btn btn-primary">
                    <i class="fas fa-file-excel me-2"></i>
                    Exportar Excel
                </button>
                <button onclick="exportarPDF()" class="btn btn-danger">
                    <i class="fas fa-file-pdf me-2"></i>
                    Exportar PDF
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>
                    Filtros
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-md-2">
                        <label for="concurso_id" class="form-label">Concurso</label>
                        <select class="form-select" id="concurso_id" name="concurso_id">
                            <option value="">Todos</option>
                            <?php foreach ($concursos as $concurso): ?>
                            <option value="<?= $concurso['id'] ?>" <?= $concurso_id == $concurso['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($concurso['titulo']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status_pagamento" class="form-label">Status</label>
                        <select class="form-select" id="status_pagamento" name="status_pagamento">
                            <option value="">Todos</option>
                            <option value="pendente" <?= $status_pagamento == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="pago" <?= $status_pagamento == 'pago' ? 'selected' : '' ?>>Pago</option>
                            <option value="cancelado" <?= $status_pagamento == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="forma_pagamento" class="form-label">Forma</label>
                        <select class="form-select" id="forma_pagamento" name="forma_pagamento">
                            <option value="">Todas</option>
                            <option value="dinheiro" <?= $forma_pagamento == 'dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
                            <option value="pix" <?= $forma_pagamento == 'pix' ? 'selected' : '' ?>>PIX</option>
                            <option value="transferencia" <?= $forma_pagamento == 'transferencia' ? 'selected' : '' ?>>Transferência</option>
                            <option value="cheque" <?= $forma_pagamento == 'cheque' ? 'selected' : '' ?>>Cheque</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="data_inicio" class="form-label">Data Início</label>
                        <input type="date" class="form-control" id="data_inicio" name="data_inicio" 
                               value="<?= $data_inicio ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="data_fim" class="form-label">Data Fim</label>
                        <input type="date" class="form-control" id="data_fim" name="data_fim" 
                               value="<?= $data_fim ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>
                                Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Estatísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?= count($pagamentos) ?></h4>
                        <p class="mb-0">Total de Pagamentos</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-money-bill-wave fa-2x"></i>
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
                        <h4 class="mb-0">
                            R$ <?= number_format(array_sum(array_column(array_filter($pagamentos, function($p) { return $p['status'] == 'pago'; }), 'valor')), 2, ',', '.') ?>
                        </h4>
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
                        <h4 class="mb-0">
                            R$ <?= number_format(array_sum(array_column(array_filter($pagamentos, function($p) { return $p['status'] == 'pendente'; }), 'valor')), 2, ',', '.') ?>
                        </h4>
                        <p class="mb-0">Total Pendente</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x"></i>
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
                        <h4 class="mb-0">
                            <?= count(array_unique(array_column($pagamentos, 'fiscal_id'))) ?>
                        </h4>
                        <p class="mb-0">Fiscais Únicos</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Planilha de Pagamentos -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>
                    Planilha de Pagamentos (<?= count($pagamentos) ?> registros)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="planilhaPagamentosTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fiscal</th>
                                <th>CPF</th>
                                <th>Celular</th>
                                <th>Concurso</th>
                                <th>Valor</th>
                                <th>Forma Pagamento</th>
                                <th>Status</th>
                                <th>Data Pagamento</th>
                                <th>Registrado por</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagamentos as $pagamento): ?>
                            <tr>
                                <td><?= $pagamento['id'] ?></td>
                                <td><?= htmlspecialchars($pagamento['fiscal_nome']) ?></td>
                                <td><?= formatCPF($pagamento['fiscal_cpf']) ?></td>
                                <td><?= formatPhone($pagamento['fiscal_celular']) ?></td>
                                <td><?= htmlspecialchars($pagamento['concurso_titulo']) ?></td>
                                <td>R$ <?= number_format($pagamento['valor'], 2, ',', '.') ?></td>
                                <td><?= ucfirst($pagamento['forma_pagamento']) ?></td>
                                <td>
                                    <span class="badge bg-<?= getStatusPagamentoColor($pagamento['status']) ?>">
                                        <?= ucfirst($pagamento['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($pagamento['data_pagamento'])) ?></td>
                                <td><?= htmlspecialchars($pagamento['usuario_nome']) ?></td>
                                <td><?= htmlspecialchars($pagamento['observacoes'] ?? '') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resumo por Forma de Pagamento -->
<?php if (!empty($pagamentos)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Resumo por Forma de Pagamento
                </h5>
            </div>
            <div class="card-body">
                <?php
                $formas_resumo = [];
                foreach ($pagamentos as $pagamento) {
                    $forma = $pagamento['forma_pagamento'];
                    if (!isset($formas_resumo[$forma])) {
                        $formas_resumo[$forma] = [
                            'total' => 0,
                            'valor_total' => 0,
                            'pagos' => 0,
                            'pendentes' => 0
                        ];
                    }
                    $formas_resumo[$forma]['total']++;
                    $formas_resumo[$forma]['valor_total'] += $pagamento['valor'];
                    
                    if ($pagamento['status'] == 'pago') {
                        $formas_resumo[$forma]['pagos']++;
                    } elseif ($pagamento['status'] == 'pendente') {
                        $formas_resumo[$forma]['pendentes']++;
                    }
                }
                ?>
                
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Forma de Pagamento</th>
                                <th>Total de Registros</th>
                                <th>Valor Total</th>
                                <th>Pagos</th>
                                <th>Pendentes</th>
                                <th>Taxa de Pagamento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($formas_resumo as $forma => $dados): ?>
                            <tr>
                                <td><?= ucfirst($forma) ?></td>
                                <td><span class="badge bg-primary"><?= $dados['total'] ?></span></td>
                                <td><strong>R$ <?= number_format($dados['valor_total'], 2, ',', '.') ?></strong></td>
                                <td><span class="badge bg-success"><?= $dados['pagos'] ?></span></td>
                                <td><span class="badge bg-warning"><?= $dados['pendentes'] ?></span></td>
                                <td>
                                    <?php 
                                    $taxa = $dados['total'] > 0 ? round(($dados['pagos'] / $dados['total']) * 100, 1) : 0;
                                    $cor_taxa = $taxa >= 80 ? 'success' : ($taxa >= 60 ? 'warning' : 'danger');
                                    ?>
                                    <span class="badge bg-<?= $cor_taxa ?>"><?= $taxa ?>%</span>
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
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    $('#planilhaPagamentosTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        responsive: true,
        pageLength: 100,
        order: [[8, 'desc']],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});

function exportarCSV() {
    const params = new URLSearchParams(window.location.search);
    window.open('exportar_csv_pagamentos.php?' + params.toString(), '_blank');
}

function exportarExcel() {
    const params = new URLSearchParams(window.location.search);
    window.open('exportar_excel_pagamentos.php?' + params.toString(), '_blank');
}

function exportarPDF() {
    const params = new URLSearchParams(window.location.search);
    window.open('exportar_pdf_pagamentos.php?' + params.toString(), '_blank');
}
</script>

<?php 
// Funções auxiliares
function formatCPF($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

function formatPhone($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) === 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
    }
    return $phone;
}

function getStatusPagamentoColor($status) {
    switch ($status) {
        case 'pago': return 'success';
        case 'pendente': return 'warning';
        case 'cancelado': return 'danger';
        default: return 'secondary';
    }
}

include '../includes/footer.php'; 
?> 