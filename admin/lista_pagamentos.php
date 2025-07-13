<?php
require_once '../config.php';

// Verificar se tem permissão para pagamentos
if (!isLoggedIn() || !temPermissaoPagamentos()) {
    redirect('../login.php');
}

$db = getDB();
$pagamentos = [];

// Filtros
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : null;
$status_pagamento = isset($_GET['status_pagamento']) ? $_GET['status_pagamento'] : '';
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

$pageTitle = 'Lista de Pagamentos';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-money-bill-wave me-2"></i>
                Lista de Pagamentos
            </h1>
            <div>
                <a href="novo_pagamento.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>
                    Novo Pagamento
                </a>
                <button onclick="exportarPDF()" class="btn btn-danger">
                    <i class="fas fa-file-pdf me-2"></i>
                    Exportar PDF
                </button>
                <button onclick="exportarExcel()" class="btn btn-primary">
                    <i class="fas fa-file-excel me-2"></i>
                    Exportar Excel
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
                        <label for="status_pagamento" class="form-label">Status</label>
                        <select class="form-select" id="status_pagamento" name="status_pagamento">
                            <option value="">Todos os status</option>
                            <option value="pendente" <?= $status_pagamento == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="pago" <?= $status_pagamento == 'pago' ? 'selected' : '' ?>>Pago</option>
                            <option value="cancelado" <?= $status_pagamento == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
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
                            <?= count(array_filter($pagamentos, function($p) { return $p['status'] == 'pago'; })) ?>
                        </h4>
                        <p class="mb-0">Pagamentos Realizados</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-receipt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Pagamentos -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Lista de Pagamentos (<?= count($pagamentos) ?>)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="pagamentosTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fiscal</th>
                                <th>CPF</th>
                                <th>Concurso</th>
                                <th>Valor</th>
                                <th>Data Pagamento</th>
                                <th>Status</th>
                                <th>Forma Pagamento</th>
                                <th>Registrado por</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagamentos as $pagamento): ?>
                            <tr>
                                <td><?= $pagamento['id'] ?></td>
                                <td><?= htmlspecialchars($pagamento['fiscal_nome']) ?></td>
                                <td><?= formatCPF($pagamento['fiscal_cpf']) ?></td>
                                <td><?= htmlspecialchars($pagamento['concurso_titulo']) ?></td>
                                <td>R$ <?= number_format($pagamento['valor'], 2, ',', '.') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($pagamento['data_pagamento'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= getStatusPagamentoColor($pagamento['status']) ?>">
                                        <?= ucfirst($pagamento['status']) ?>
                                    </span>
                                </td>
                                <td><?= ucfirst($pagamento['forma_pagamento']) ?></td>
                                <td><?= htmlspecialchars($pagamento['usuario_nome']) ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="recibo_pagamento.php?id=<?= $pagamento['id'] ?>" 
                                           class="btn btn-sm btn-info" title="Ver Recibo">
                                            <i class="fas fa-receipt"></i>
                                        </a>
                                        <a href="editar_pagamento.php?id=<?= $pagamento['id'] ?>" 
                                           class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($pagamento['status'] == 'pendente'): ?>
                                        <button onclick="marcarComoPago(<?= $pagamento['id'] ?>)" 
                                                class="btn btn-sm btn-success" title="Marcar como Pago">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    $('#pagamentosTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        responsive: true,
        pageLength: 50,
        order: [[5, 'desc']],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});

function marcarComoPago(pagamentoId) {
    if (confirm('Confirmar que o pagamento foi realizado?')) {
        fetch('marcar_pagamento_pago.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                pagamento_id: pagamentoId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Pagamento marcado como pago com sucesso!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showMessage('Erro ao marcar pagamento: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showMessage('Erro ao processar requisição', 'error');
        });
    }
}

function exportarPDF() {
    window.open('exportar_pdf_pagamentos.php?' + new URLSearchParams(window.location.search), '_blank');
}

function exportarExcel() {
    window.open('exportar_excel_pagamentos.php?' + new URLSearchParams(window.location.search), '_blank');
}
</script>

<?php 
// Funções auxiliares
function formatCPF($cpf) {
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
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