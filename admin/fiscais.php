<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

// Habilitar depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log para verificar carregamento
error_log("fiscais.php carregado em " . date('Y-m-d H:i:s'));

$db = getDB();
$fiscais = [];
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : 0;
$mensagem = '';

if ($concurso_id > 0) {
    try {
        // Verificar se o concurso é ativo
        $stmt = $db->prepare("SELECT id FROM concursos WHERE id = ? AND status = 'ativo'");
        $stmt->execute([$concurso_id]);
        if (!$stmt->fetch()) {
            $mensagem = 'Concurso inválido ou inativo. Por favor, selecione um concurso ativo.';
            $concurso_id = 0; // Resetar concurso_id para evitar exibição de dados
            error_log("Concurso inválido ou inativo: concurso_id=$concurso_id");
        } else {
            // Buscar fiscais apenas do concurso ativo selecionado
            $stmt = $db->prepare("
                SELECT f.id, f.nome, f.cpf, f.status, f.concurso_id
                FROM fiscais f
                WHERE f.concurso_id = ?
                ORDER BY f.nome ASC
            ");
            $stmt->execute([$concurso_id]);
            $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Fiscais encontrados: " . count($fiscais) . " para concurso_id=$concurso_id");
        }
    } catch (Exception $e) {
        error_log('Erro ao buscar fiscais: ' . $e->getMessage());
        $mensagem = 'Erro ao consultar o banco de dados. Tente novamente mais tarde.';
    }
} else {
    $mensagem = 'Por favor, selecione um concurso ativo para visualizar os fiscais.';
    error_log("Nenhum concurso selecionado: concurso_id=$concurso_id");
}

$pageTitle = 'Gerenciar Fiscais';
include '../includes/header.php';
?>

<style>
/* Garantir que o campo Concurso seja maior */
.row.mb-4 .col-md-4 select#concursoFilter {
    width: 100%;
    padding: 0.5rem;
}
.row.mb-4 .col-md-2 select#statusFilter {
    width: 100%;
    padding: 0.5rem;
}
.row.mb-4 .col-md-3 input#searchFilter {
    width: 100%;
    padding: 0.5rem;
}
.row.mb-4 .col-md-3 button {
    width: 100%;
    padding: 0.5rem;
}
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-users me-2"></i>
                Gerenciar Fiscais
            </h1>
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
                    <div class="col-md-4">
                        <label for="concursoFilter" class="form-label">Concurso</label>
                        <select class="form-select" id="concursoFilter">
                            <option value="">Selecione o Concurso</option>
                            <?php
                            $concursos = $db->query("SELECT id, titulo, orgao, numero_concurso, ano_concurso, cidade, estado FROM concursos WHERE status = 'ativo' ORDER BY ano_concurso DESC, numero_concurso DESC")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($concursos as $concurso) {
                                $selected = ($concurso['id'] == $concurso_id) ? 'selected' : '';
                                echo "<option value='{$concurso['id']}' {$selected}> {$concurso['titulo']} {$concurso['numero_concurso']}/{$concurso['ano_concurso']} da {$concurso['orgao']} de {$concurso['cidade']}/{$concurso['estado']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
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
                        <label for="searchFilter" class="form-label">CPF</label>
                        <input type="text" class="form-control" id="searchFilter" placeholder="CPF">
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

<!-- Mensagem ou Tabela de Fiscais -->
<?php if ($mensagem): ?>
    <div class="alert alert-warning mt-3 alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($mensagem) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php elseif ($fiscais): ?>
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
                                    <th>Nome</th>
                                    <th>Status</th>
                                    <th>CPF</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fiscais as $fiscal): ?>
                                <tr>
                                    <td><?= htmlspecialchars($fiscal['nome']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= getStatusColor($fiscal['status']) ?>">
                                            <?= ucfirst($fiscal['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= formatCPF($fiscal['cpf']) ?></td>
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
<?php endif; ?>

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
    console.log('DOM carregado em fiscais.php');
    // Inicializar DataTable apenas se a tabela existir
    if (document.getElementById('fiscaisTable')) {
        console.log('Inicializando DataTable');
        table = $('#fiscaisTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
            },
            responsive: true,
            pageLength: 25,
            order: [[0, 'asc']],
            columnDefs: [
                { targets: [3], orderable: false, searchable: false },
                { targets: [0, 1, 2], searchable: true }
            ]
        });

        // Filtros
        $('#statusFilter').on('change', function() {
            console.log('Filtro Status:', $(this).val());
            table.column(1).search($(this).val()).draw();
        });

        $('#searchFilter').on('keyup', function() {
            console.log('Filtro CPF:', $(this).val());
            table.column(2).search($(this).val()).draw();
        });
    } else {
        console.log('Tabela #fiscaisTable não encontrada');
    }

    // Filtro de Concurso
    $('#concursoFilter').on('change', function() {
        const concursoId = $(this).val();
        console.log('Filtro Concurso:', concursoId);
        window.location.href = concursoId ? `fiscais.php?concurso_id=${concursoId}` : 'fiscais.php';
    });
});

function limparFiltros() {
    console.log('Limpando filtros');
    $('#statusFilter').val('');
    $('#concursoFilter').val('');
    $('#searchFilter').val('');
    if (table) {
        table.search('').columns().search('').draw();
    }
    window.location.href = 'fiscais.php';
}

function verDetalhes(id) {
    console.log('Ver detalhes do fiscal ID:', id);
    showLoading();
    
    fetch(`get_fiscal.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.error) {
                console.error('Erro no fetch:', data.error);
                showMessage(data.error, 'error');
                return;
            }
            
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h5>Dados Pessoais</h5>
                        <p><strong>Nome:</strong> ${data.fiscal.nome || 'Não informado'}</p>
                        <p><strong>CPF:</strong> ${formatCPF(data.fiscal.cpf)}</p>
                        <p><strong>Status:</strong> <span class="badge bg-${getStatusColor(data.fiscal.status)}">${data.fiscal.status}</span></p>
                    </div>
                    <div class="col-md-6">
                        <h5>Informações do Concurso</h5>
                        <p><strong>Concurso:</strong> ${data.fiscal.concurso_nome || 'Não informado'}</p>
                        <p><strong>Data de Cadastro:</strong> ${new Date(data.fiscal.created_at).toLocaleDateString('pt-BR')}</p>
                    </div>
                </div>
                
                ${data.alocacoes.length > 0 ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <h5>Alocações</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Escola</th>
                                        <th>Sala</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.alocacoes.map(alocacao => `
                                        <tr>
                                            <td>${alocacao.escola_nome || 'N/A'}</td>
                                            <td>${alocacao.sala_nome || 'N/A'}</td>
                                            <td>${new Date(alocacao.data_alocacao).toLocaleDateString('pt-BR')}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                ` : ''}
            `;
            
            document.getElementById('detalhesContent').innerHTML = html;
            document.getElementById('detalhesModal').setAttribute('data-fiscal-id', id);
            new bootstrap.Modal(document.getElementById('detalhesModal')).show();
        })
        .catch(error => {
            hideLoading();
            console.error('Erro ao carregar detalhes:', error);
            showMessage('Erro ao carregar detalhes', 'error');
        });
}

function editarFiscal(id) {
    id = id || document.getElementById('detalhesModal').getAttribute('data-fiscal-id');
    if (id) {
        console.log('Editar fiscal ID:', id);
        window.location.href = `editar_fiscal.php?id=${id}`;
    }
}

function alocarFiscal(id) {
    id = id || document.getElementById('detalhesModal').getAttribute('data-fiscal-id');
    if (id) {
        console.log('Alocar fiscal ID:', id);
        window.location.href = `alocar_fiscal.php?id=${id}`;
    }
}

function deleteFiscal(id) {
    console.log('Deletar fiscal ID:', id);
    confirmAction('Tem certeza que deseja excluir este fiscal?', function() {
        showLoading();
        
        fetch('delete_fiscal.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showMessage('Fiscal excluído com sucesso!');
                setTimeout(() => location.reload(), 1500);
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Erro ao excluir fiscal:', error);
            showMessage('Erro ao excluir fiscal', 'error');
        });
    });
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