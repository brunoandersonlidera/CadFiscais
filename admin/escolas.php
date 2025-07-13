<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$escolas = [];
$concursos = [];

// Buscar concursos ativos
try {
    $stmt = $db->query("SELECT id, titulo, orgao FROM concursos WHERE status = 'ativo' ORDER BY titulo");
    $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar concursos: ' . $e->getMessage(), 'ERROR');
}

// Filtro por concurso
$concurso_filtro = isset($_GET['concurso']) ? (int)$_GET['concurso'] : '';

try {
    if ($concurso_filtro) {
        $stmt = $db->prepare("
            SELECT e.*, c.titulo as concurso_titulo 
            FROM escolas e 
            LEFT JOIN concursos c ON e.concurso_id = c.id 
            WHERE e.concurso_id = ? 
            ORDER BY e.nome
        ");
        $stmt->execute([$concurso_filtro]);
    } else {
        $stmt = $db->query("
            SELECT e.*, c.titulo as concurso_titulo 
            FROM escolas e 
            LEFT JOIN concursos c ON e.concurso_id = c.id 
            ORDER BY e.nome
        ");
    }
    $escolas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar escolas: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Gerenciar Escolas';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-school me-2"></i>
                Gerenciar Escolas
            </h1>
            <div>
                <a href="../migrar_escolas_concurso.php" class="btn btn-warning me-2" title="Migrar Escolas">
                    <i class="fas fa-sync-alt me-2"></i>
                    Migrar Escolas
                </a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalEscola">
                    <i class="fas fa-plus me-2"></i>
                    Nova Escola
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Filtro por Concurso -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-filter me-2"></i>
                    Filtros
                </h6>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label for="concurso" class="form-label">Filtrar por Concurso</label>
                        <select class="form-select" id="concurso" name="concurso" onchange="this.form.submit()">
                            <option value="">Todos os Concursos</option>
                            <?php foreach ($concursos as $concurso): ?>
                            <option value="<?= $concurso['id'] ?>" <?= $concurso_filtro == $concurso['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($concurso['titulo']) ?> (<?= htmlspecialchars($concurso['orgao']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <a href="escolas.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>
                            Limpar Filtros
                        </a>
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
                        <h4 class="mb-0"><?= count($escolas) ?></h4>
                        <p class="mb-0">Total de Escolas</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-school fa-2x"></i>
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
                            <?= count(array_filter($escolas, function($e) { return $e['status'] == 'ativo'; })) ?>
                        </h4>
                        <p class="mb-0">Escolas Ativas</p>
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
                            <?= count(array_filter($escolas, function($e) { return $e['status'] == 'inativo'; })) ?>
                        </h4>
                        <p class="mb-0">Escolas Inativas</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-pause-circle fa-2x"></i>
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
                            <?= count(array_filter($escolas, function($e) { return $e['tipo'] == 'publica'; })) ?>
                        </h4>
                        <p class="mb-0">Escolas Públicas</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-building fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Escolas -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Lista de Escolas (<?= count($escolas) ?>)
                    <?php if ($concurso_filtro): ?>
                        <small class="ms-2">- Filtrado por concurso</small>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="escolasTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Concurso</th>
                                <th>Endereço</th>
                                <th>Telefone</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Data Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($escolas as $escola): ?>
                            <tr>
                                <td><?= $escola['id'] ?></td>
                                <td><?= htmlspecialchars($escola['nome']) ?></td>
                                <td>
                                    <?php if ($escola['concurso_titulo']): ?>
                                        <span class="badge bg-info">
                                            <?= htmlspecialchars($escola['concurso_titulo']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Sem concurso</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($escola['endereco']) ?></td>
                                <td><?= formatPhone($escola['telefone']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $escola['tipo'] == 'publica' ? 'primary' : 'success' ?>">
                                        <?= ucfirst($escola['tipo']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $escola['status'] == 'ativo' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($escola['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($escola['data_cadastro'])) ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button onclick="editarEscola(<?= $escola['id'] ?>)" 
                                                class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="toggleStatusEscola(<?= $escola['id'] ?>)" 
                                                class="btn btn-sm btn-<?= $escola['status'] == 'ativo' ? 'danger' : 'success' ?>" 
                                                title="<?= $escola['status'] == 'ativo' ? 'Desativar' : 'Ativar' ?>">
                                            <i class="fas fa-<?= $escola['status'] == 'ativo' ? 'times' : 'check' ?>"></i>
                                        </button>
                                        <button onclick="verSalas(<?= $escola['id'] ?>)" 
                                                class="btn btn-sm btn-info" title="Ver Salas">
                                            <i class="fas fa-door-open"></i>
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

<!-- Modal Nova/Editar Escola -->
<div class="modal fade" id="modalEscola" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nova Escola</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEscola">
                <div class="modal-body">
                    <input type="hidden" id="escola_id" name="escola_id">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome da Escola *</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="concurso_id" class="form-label">Concurso *</label>
                                <select class="form-select" id="concurso_id" name="concurso_id" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($concursos as $concurso): ?>
                                    <option value="<?= $concurso['id'] ?>">
                                        <?= htmlspecialchars($concurso['titulo']) ?> (<?= htmlspecialchars($concurso['orgao']) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tipo" class="form-label">Tipo *</label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="">Selecione...</option>
                                    <option value="publica">Pública</option>
                                    <option value="privada">Privada</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="capacidade" class="form-label">Capacidade Total</label>
                                <input type="number" class="form-control" id="capacidade" name="capacidade" min="1">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="endereco" class="form-label">Endereço Completo *</label>
                                <textarea class="form-control" id="endereco" name="endereco" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="telefone" name="telefone">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="responsavel" class="form-label">Responsável</label>
                                <input type="text" class="form-control" id="responsavel" name="responsavel">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="coordenador_idh" class="form-label">Coordenador IDH</label>
                                <input type="text" class="form-control" id="coordenador_idh" name="coordenador_idh" 
                                       placeholder="Nome do coordenador do IDH">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="coordenador_comissao" class="form-label">Coordenador da Comissão</label>
                                <input type="text" class="form-control" id="coordenador_comissao" name="coordenador_comissao" 
                                       placeholder="Nome do coordenador da comissão">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="observacoes" class="form-label">Observações</label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Salas -->
<div class="modal fade" id="modalSalas" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Salas da Escola</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="salasContent">
                    <!-- Conteúdo será carregado via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    $('#escolasTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[1, 'asc']]
    });
    
    // Configurar formulário
    document.getElementById('formEscola').addEventListener('submit', function(e) {
        e.preventDefault();
        salvarEscola();
    });
});

function novaEscola() {
    document.getElementById('modalTitle').textContent = 'Nova Escola';
    document.getElementById('formEscola').reset();
    document.getElementById('escola_id').value = '';
    $('#modalEscola').modal('show');
}

function editarEscola(escolaId) {
    fetch('buscar_escola.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            escola_id: escolaId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const escola = data.escola;
            document.getElementById('modalTitle').textContent = 'Editar Escola';
            document.getElementById('escola_id').value = escola.id;
            document.getElementById('nome').value = escola.nome;
            document.getElementById('concurso_id').value = escola.concurso_id || '';
            document.getElementById('tipo').value = escola.tipo;
            document.getElementById('endereco').value = escola.endereco;
            document.getElementById('telefone').value = escola.telefone;
            document.getElementById('email').value = escola.email;
            document.getElementById('responsavel').value = escola.responsavel;
            document.getElementById('capacidade').value = escola.capacidade;
            document.getElementById('observacoes').value = escola.observacoes;
            $('#modalEscola').modal('show');
        } else {
            showMessage('Erro ao buscar escola: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showMessage('Erro ao processar requisição', 'error');
    });
}

function salvarEscola() {
    const formData = new FormData(document.getElementById('formEscola'));
    
    fetch('salvar_escola.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('Escola salva com sucesso!', 'success');
            $('#modalEscola').modal('hide');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showMessage('Erro ao salvar escola: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showMessage('Erro ao processar requisição', 'error');
    });
}

function toggleStatusEscola(escolaId) {
    if (confirm('Confirmar alteração do status da escola?')) {
        fetch('toggle_status_escola.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                escola_id: escolaId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Status da escola alterado com sucesso!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showMessage('Erro ao alterar status: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showMessage('Erro ao processar requisição', 'error');
        });
    }
}

function verSalas(escolaId) {
    fetch('buscar_salas_escola.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            escola_id: escolaId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('salasContent').innerHTML = data.html;
            $('#modalSalas').modal('show');
        } else {
            showMessage('Erro ao buscar salas: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showMessage('Erro ao processar requisição', 'error');
    });
}
</script>

<?php 
include '../includes/footer.php'; 
?> 