<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$fiscais = [];

try {
    $stmt = $db->query("
        SELECT f.id, f.nome, f.email, f.celular, f.cpf, f.data_nascimento, 
               f.status, f.created_at, f.observacoes, f.concurso_id,
               c.titulo as concurso_titulo,
               TIMESTAMPDIFF(YEAR, f.data_nascimento, CURDATE()) as idade
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        ORDER BY f.created_at DESC
    ");
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar fiscais: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Gerenciar Fiscais';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-users me-2"></i>
                Gerenciar Fiscais
            </h1>
            <div>
                <button onclick="exportData('csv')" class="btn btn-success">
                    <i class="fas fa-download me-2"></i>
                    Exportar CSV
                </button>
                <button onclick="exportData('excel')" class="btn btn-info">
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
                <div class="row">
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label">Status</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">Todos</option>
                            <option value="pendente">Pendente</option>
                            <option value="aprovado">Aprovado</option>
                            <option value="reprovado">Reprovado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="concursoFilter" class="form-label">Concurso</label>
                        <select class="form-select" id="concursoFilter">
                            <option value="">Todos</option>
                            <?php
                            $concursos = $db->query("SELECT id, titulo FROM concursos WHERE status = 'ativo'")->fetchAll();
                            foreach ($concursos as $concurso) {
                                echo "<option value='{$concurso['id']}'>{$concurso['titulo']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="searchFilter" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="searchFilter" placeholder="Nome, email ou CPF">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button onclick="limparFiltros()" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>
                                Limpar Filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Fiscais -->
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
                                <th>Email</th>
                                <th>Celular</th>
                                <th>CPF</th>
                                <th>Idade</th>
                                <th>Concurso</th>
                                <th>Status</th>
                                <th>Data Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fiscais as $fiscal): ?>
                            <tr>
                                <td><?= $fiscal['id'] ?></td>
                                <td><?= htmlspecialchars($fiscal['nome']) ?></td>
                                <td><?= htmlspecialchars($fiscal['email']) ?></td>
                                <td><?= formatPhone($fiscal['celular']) ?></td>
                                <td><?= formatCPF($fiscal['cpf']) ?></td>
                                <td><?= $fiscal['idade'] ?> anos</td>
                                <td><?= htmlspecialchars($fiscal['concurso_titulo'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge bg-<?= getStatusColor($fiscal['status']) ?>">
                                        <?= ucfirst($fiscal['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($fiscal['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="verDetalhes(<?= $fiscal['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-warning" 
                                                onclick="editarFiscal(<?= $fiscal['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-success" 
                                                onclick="alocarFiscal(<?= $fiscal['id'] ?>)">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteFiscal(<?= $fiscal['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
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

<!-- Modal de Detalhes -->
<div class="modal fade" id="detalhesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user me-2"></i>
                    Detalhes do Fiscal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalhesContent">
                <!-- Conteúdo será carregado via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" onclick="editarFiscal()">Editar</button>
                <button type="button" class="btn btn-success" onclick="alocarFiscal()">Alocar</button>
            </div>
        </div>
    </div>
</div>

<script>
let table;

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    table = $('#fiscaisTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[0, 'desc']],
        columnDefs: [
            { targets: [9], orderable: false, searchable: false }
        ]
    });
    
    // Filtros
    $('#statusFilter').on('change', function() {
        table.column(7).search($(this).val()).draw();
    });
    
    $('#concursoFilter').on('change', function() {
        table.column(6).search($(this).val()).draw();
    });
    
    $('#searchFilter').on('keyup', function() {
        table.search($(this).val()).draw();
    });
});

function limparFiltros() {
    $('#statusFilter').val('');
    $('#concursoFilter').val('');
    $('#searchFilter').val('');
    table.search('').columns().search('').draw();
}

function verDetalhes(id) {
    showLoading();
    
    fetch(`get_fiscal.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                document.getElementById('detalhesContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('detalhesModal')).show();
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showMessage('Erro ao carregar detalhes', 'error');
        });
}

function editarFiscal(id) {
    if (id) {
        window.location.href = `editar_fiscal.php?id=${id}`;
    } else {
        // Pegar ID do modal
        const modal = document.getElementById('detalhesModal');
        const id = modal.getAttribute('data-fiscal-id');
        if (id) {
            window.location.href = `editar_fiscal.php?id=${id}`;
        }
    }
}

function alocarFiscal(id) {
    if (id) {
        window.location.href = `alocar_fiscal.php?id=${id}`;
    } else {
        // Pegar ID do modal
        const modal = document.getElementById('detalhesModal');
        const id = modal.getAttribute('data-fiscal-id');
        if (id) {
            window.location.href = `alocar_fiscal.php?id=${id}`;
        }
    }
}

function changeStatus(id, status) {
    confirmAction(`Tem certeza que deseja alterar o status para "${status}"?`, function() {
        showLoading();
        
        fetch('change_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id, status: status })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showMessage('Status alterado com sucesso!');
                setTimeout(() => location.reload(), 1500);
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showMessage('Erro ao alterar status', 'error');
        });
    });
}

function exportData(format) {
    showLoading();
    
    fetch('export.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ format: format })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            // Criar link para download
            const link = document.createElement('a');
            link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(data.data);
            link.download = `fiscais_${new Date().toISOString().split('T')[0]}.${format}`;
            link.click();
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showMessage('Erro ao exportar dados', 'error');
    });
}

// Funções auxiliares
function formatPhone(phone) {
    phone = phone.replace(/\D/g, '');
    if (phone.length === 11) {
        return phone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    }
    return phone;
}

function formatCPF(cpf) {
    cpf = cpf.replace(/\D/g, '');
    return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

function getStatusColor(status) {
    switch (status) {
        case 'aprovado': return 'success';
        case 'pendente': return 'warning';
        case 'reprovado': return 'danger';
        case 'cancelado': return 'secondary';
        default: return 'secondary';
    }
}
</script>

<?php 
// Funções auxiliares
function formatPhone($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) === 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
    }
    return $phone;
}

function formatCPF($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

function getStatusColor($status) {
    switch ($status) {
        case 'aprovado': return 'success';
        case 'pendente': return 'warning';
        case 'reprovado': return 'danger';
        case 'cancelado': return 'secondary';
        default: return 'secondary';
    }
}

include '../includes/footer.php'; 
?> 