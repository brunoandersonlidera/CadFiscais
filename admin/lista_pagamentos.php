<?php
require_once '../config.php';

// Verificar se tem permissão para pagamentos
if (!isLoggedIn() || !temPermissaoPagamentos()) {
    redirect('../login.php');
}

$db = getDB();
$fiscais = [];

// Filtros
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : null;
$escola_id = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : null;

try {
    // Buscar fiscais aprovados com informações de presença e pagamento
    $sql = "
        SELECT f.id, f.nome, f.cpf, f.celular, f.email, f.status as fiscal_status,
               c.id as concurso_id, c.titulo as concurso_titulo, c.valor_pagamento,
               p.id as pagamento_id, p.status as status_pagamento, p.data_pagamento,
               pres.status as status_presenca,
               e.nome as escola_nome, s.nome as sala_nome
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        LEFT JOIN pagamentos p ON f.id = p.fiscal_id
        LEFT JOIN presenca pres ON f.id = pres.fiscal_id AND f.concurso_id = pres.concurso_id AND pres.tipo_presenca = 'prova'
        LEFT JOIN alocacoes_fiscais af ON f.id = af.fiscal_id AND af.status = 'ativo'
        LEFT JOIN escolas e ON af.escola_id = e.id
        LEFT JOIN salas s ON af.sala_id = s.id
        WHERE f.status = 'aprovado'
    ";
    $params = [];
    
    if ($concurso_id) {
        $sql .= " AND f.concurso_id = ?";
        $params[] = $concurso_id;
    }
    
    if ($escola_id) {
        $sql .= " AND af.escola_id = ?";
        $params[] = $escola_id;
    }
    
    $sql .= " ORDER BY f.nome ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar fiscais: ' . $e->getMessage(), 'ERROR');
}

// Buscar concursos para filtro
$concursos = [];
try {
    $stmt = $db->query("SELECT id, titulo, numero_concurso, ano_concurso, orgao, cidade, estado FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC");
    $concursos = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar concursos: ' . $e->getMessage(), 'ERROR');
}

// Buscar escolas para filtro
$escolas = [];
if ($concurso_id) {
    try {
        $stmt = $db->prepare("SELECT id, nome FROM escolas WHERE concurso_id = ? AND status = 'ativo' ORDER BY nome");
        $stmt->execute([$concurso_id]);
        $escolas = $stmt->fetchAll();
    } catch (Exception $e) {
        logActivity('Erro ao buscar escolas: ' . $e->getMessage(), 'ERROR');
    }
}

$pageTitle = 'Controle de Pagamentos';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-money-bill-wave me-2"></i>
                Controle de Pagamentos
            </h1>
            <div>
                <button onclick="exportarPDF()" class="btn btn-danger">
                    <i class="fas fa-file-pdf me-2"></i>
                    Exportar PDF
                </button>
                <a href="recibos_pdf.php" class="btn btn-success ms-2">
                    <i class="fas fa-receipt me-2"></i>
                    Recibos
                </a>
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
                    <div class="col-md-4">
                        <label for="concurso_id" class="form-label">Concurso</label>
                        <select class="form-select" id="concurso_id" name="concurso_id" onchange="this.form.submit()">
                            <option value="">Todos os concursos</option>
                            <?php foreach ($concursos as $concurso): ?>
                            <option value="<?= $concurso['id'] ?>" <?= $concurso_id == $concurso['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($concurso['titulo']) ?> <?= htmlspecialchars($concurso['numero_concurso']) ?>/<?= htmlspecialchars($concurso['ano_concurso']) ?> da <?= htmlspecialchars($concurso['orgao']) ?> de <?= htmlspecialchars($concurso['cidade']) ?>/<?= htmlspecialchars($concurso['estado']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="escola_id" class="form-label">Escola</label>
                        <select class="form-select" id="escola_id" name="escola_id" onchange="this.form.submit()">
                            <option value="">Todas as escolas</option>
                            <?php foreach ($escolas as $escola): ?>
                            <option value="<?= $escola['id'] ?>" <?= $escola_id == $escola['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($escola['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <a href="lista_pagamentos.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>
                                Limpar Filtros
                            </a>
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
                        <h4 class="mb-0"><?= count($fiscais) ?></h4>
                        <p class="mb-0">Total de Fiscais</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
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
                            <?= count(array_filter($fiscais, function($f) { return $f['status_pagamento'] == 'pago'; })) ?>
                        </h4>
                        <p class="mb-0">Pagamentos Realizados</p>
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
                            <?= count(array_filter($fiscais, function($f) { 
                                return $f['pagamento_id'] === null && $f['status_presenca'] === 'presente'; 
                            })) ?>
                        </h4>
                        <p class="mb-0">Aguardando Pagamento</p>
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
                            <?= count(array_filter($fiscais, function($f) { 
                                return $f['status_presenca'] === 'presente'; 
                            })) ?>
                        </h4>
                        <p class="mb-0">Presentes na Prova</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Versão Desktop -->
<div class="d-none d-md-block">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Lista de Fiscais (<?= count($fiscais) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="fiscaisTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>CPF</th>
                                    <th>Celular</th>
                                    <th>Concurso</th>
                                    <th>Escola</th>
                                    <th>Valor</th>
                                    <th>Presença</th>
                                    <th>Pagamento</th>
                                    <th>Data Pagamento</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fiscais as $fiscal): ?>
                                <tr>
                                    <td><?= $fiscal['id'] ?></td>
                                    <td><?= htmlspecialchars($fiscal['nome']) ?></td>
                                    <td><?= formatCPF($fiscal['cpf']) ?></td>
                                    <td><?= formatPhone($fiscal['celular']) ?></td>
                                    <td><?= htmlspecialchars($fiscal['concurso_titulo']) ?></td>
                                    <td><?= htmlspecialchars($fiscal['escola_nome'] ?? 'Não alocado') ?></td>
                                    <td>R$ <?= number_format($fiscal['valor_pagamento'], 2, ',', '.') ?></td>
                                    <td>
                                        <?php if ($fiscal['status_presenca'] === 'presente'): ?>
                                            <span class="badge bg-success">✅ Presente</span>
                                        <?php elseif ($fiscal['status_presenca'] === 'ausente'): ?>
                                            <span class="badge bg-danger">❌ Ausente</span>
                                        <?php elseif ($fiscal['status_presenca'] === 'justificado'): ?>
                                            <span class="badge bg-warning">👋 Justificado</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">⏳ Pendente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($fiscal['status_pagamento'] === 'pago'): ?>
                                            <span class="badge bg-success">✅ Pago</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">⏳ Pendente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($fiscal['data_pagamento']): ?>
                                            <?= date('d/m/Y H:i', strtotime($fiscal['data_pagamento'])) ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($fiscal['status_presenca'] === 'presente'): ?>
                                            <?php if ($fiscal['status_pagamento'] === 'pago'): ?>
                                                <button class="btn btn-sm btn-success" disabled>
                                                    <i class="fas fa-check"></i> Pago
                                                </button>
                                            <?php else: ?>
                                                <button onclick="togglePagamento(<?= $fiscal['id'] ?>, <?= $fiscal['concurso_id'] ?>, <?= $fiscal['valor_pagamento'] ?>)" 
                                                        class="btn btn-sm btn-outline-success" title="Marcar como Pago">
                                                    <i class="fas fa-toggle-on"></i> Pagar
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled title="Apenas fiscais presentes podem receber pagamento">
                                                <i class="fas fa-ban"></i> Não Presente
                                            </button>
                                        <?php endif; ?>
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
</div>

<!-- Versão Mobile -->
<div class="d-md-none">
    <?php foreach ($fiscais as $fiscal): ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <h6 class="mb-1"><?= htmlspecialchars($fiscal['nome']) ?></h6>
                    <small class="text-muted">
                        CPF: <?= formatCPF($fiscal['cpf']) ?><br>
                        Celular: <?= formatPhone($fiscal['celular']) ?><br>
                        Escola: <?= htmlspecialchars($fiscal['escola_nome'] ?? 'Não alocado') ?>
                    </small>
                </div>
                <div class="text-end">
                    <div class="mb-1">
                        <strong>R$ <?= number_format($fiscal['valor_pagamento'], 2, ',', '.') ?></strong>
                    </div>
                    <div class="mb-1">
                        <?php if ($fiscal['status_presenca'] === 'presente'): ?>
                            <span class="badge bg-success">✅ Presente</span>
                        <?php elseif ($fiscal['status_presenca'] === 'ausente'): ?>
                            <span class="badge bg-danger">❌ Ausente</span>
                        <?php elseif ($fiscal['status_presenca'] === 'justificado'): ?>
                            <span class="badge bg-warning">👋 Justificado</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">⏳ Pendente</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($fiscal['status_pagamento'] === 'pago'): ?>
                            <span class="badge bg-success">✅ Pago</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">⏳ Pendente</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($fiscal['data_pagamento']): ?>
            <div class="mb-2">
                <small class="text-muted">
                    <i class="fas fa-calendar me-1"></i>
                    Pago em: <?= date('d/m/Y H:i', strtotime($fiscal['data_pagamento'])) ?>
                </small>
            </div>
            <?php endif; ?>
            
            <div class="d-grid">
                <?php if ($fiscal['status_presenca'] === 'presente'): ?>
                    <?php if ($fiscal['status_pagamento'] === 'pago'): ?>
                        <button class="btn btn-success" disabled>
                            <i class="fas fa-check me-2"></i> Pago
                        </button>
                    <?php else: ?>
                        <button onclick="togglePagamento(<?= $fiscal['id'] ?>, <?= $fiscal['concurso_id'] ?>, <?= $fiscal['valor_pagamento'] ?>)" 
                                class="btn btn-outline-success">
                            <i class="fas fa-toggle-on me-2"></i> Pagar R$ <?= number_format($fiscal['valor_pagamento'], 2, ',', '.') ?>
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="btn btn-secondary" disabled>
                        <i class="fas fa-ban me-2"></i> Apenas fiscais presentes podem receber pagamento
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<style>
.btn-outline-success:hover {
    background-color: #198754;
    border-color: #198754;
    color: white;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable apenas na versão desktop
    if (window.innerWidth >= 768) {
        $('#fiscaisTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
            },
            responsive: true,
            pageLength: 50,
            order: [[1, 'asc']],
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'pdf', 'print'
            ]
        });
    }
});

function togglePagamento(fiscalId, concursoId, valor) {
    if (confirm(`Confirmar pagamento de R$ ${valor.toFixed(2).replace('.', ',')} para este fiscal?`)) {
        fetch('marcar_pagamento_simples.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                fiscal_id: fiscalId,
                concurso_id: concursoId,
                valor: valor
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Pagamento registrado com sucesso!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showMessage('Erro ao registrar pagamento: ' + data.message, 'error');
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
</script>

<?php 
include '../includes/footer.php'; 
?> 