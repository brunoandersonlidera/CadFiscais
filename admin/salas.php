<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$salas = [];
$escolas = [];
$concursos = [];

// Filtros
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : null;
$escola_id = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : null;

try {
    // Buscar concursos ativos
    $stmt = $db->query("SELECT id, titulo, orgao, numero_concurso, ano_concurso, cidade, estado FROM concursos WHERE status = 'ativo' ORDER BY titulo");
    $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar escolas baseado no concurso selecionado
    if ($concurso_id) {
        $stmt = $db->prepare("SELECT id, nome FROM escolas WHERE status = 'ativo' AND concurso_id = ? ORDER BY nome");
        $stmt->execute([$concurso_id]);
        $escolas = $stmt->fetchAll();
    } else {
        $escolas = [];
    }
    
    // Buscar salas baseado na escola selecionada
    if ($escola_id && $concurso_id) {
        $sql = "
            SELECT s.*, e.nome as escola_nome, c.titulo as concurso_titulo,
                   (SELECT COUNT(*) FROM alocacoes_fiscais WHERE sala_id = s.id AND status = 'ativo') as total_alocacoes
            FROM salas s
            LEFT JOIN escolas e ON s.escola_id = e.id
            LEFT JOIN concursos c ON e.concurso_id = c.id
            WHERE s.escola_id = ? AND e.concurso_id = ?
            ORDER BY s.nome
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$escola_id, $concurso_id]);
        $salas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $salas = [];
    }
} catch (Exception $e) {
    logActivity('Erro ao buscar dados: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Gerenciar Salas';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-door-open me-2"></i>
                Gerenciar Salas
            </h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSala">
                <i class="fas fa-plus me-2"></i>
                Nova Sala
            </button>
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
                <form method="GET" class="row" id="filtroForm">
                    <div class="col-md-4">
                        <label for="concurso_id" class="form-label">Concurso</label>
                        <select class="form-select" id="concurso_id" name="concurso_id" onchange="atualizarFiltros()">
                            <option value="">Selecione o Concurso</option>
                            <?php foreach ($concursos as $concurso): ?>
                            <option value="<?= $concurso['id'] ?>" <?= $concurso_id == $concurso['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($concurso['titulo']) ?> - <?= htmlspecialchars($concurso['orgao']) ?> - <?= htmlspecialchars($concurso['cidade']) ?>/<?= htmlspecialchars($concurso['estado']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="escola_id" class="form-label">Escola</label>
                        <select class="form-select" id="escola_id" name="escola_id" onchange="atualizarFiltros()" <?= !$concurso_id ? 'disabled' : '' ?>>
                            <option value="">Selecione a Escola</option>
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
                            <button type="button" class="btn btn-secondary" onclick="limparFiltros()">
                                <i class="fas fa-times me-2"></i>
                                Limpar Filtros
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
                        <h4 class="mb-0"><?= count($salas) ?></h4>
                        <p class="mb-0">Total de Salas</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-door-open fa-2x"></i>
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
                            <?= count(array_filter($salas, function($s) { return $s['status'] == 'ativo'; })) ?>
                        </h4>
                        <p class="mb-0">Salas Ativas</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x"></i>
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
                            <?= count(array_unique(array_column($salas, 'escola_id'))) ?>
                        </h4>
                        <p class="mb-0">Escolas</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-school fa-2x"></i>
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
                            <?= array_sum(array_column($salas, 'total_alocacoes')) ?>
                        </h4>
                        <p class="mb-0">Total Alocações</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Salas -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Lista de Salas (<?= count($salas) ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (!$concurso_id): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Selecione um concurso para visualizar as escolas e salas</h5>
                        <p class="text-muted">Use os filtros acima para escolher o concurso desejado.</p>
                    </div>
                <?php elseif (!$escola_id): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-school fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Selecione uma escola para visualizar suas salas</h5>
                        <p class="text-muted">Escolha uma escola do concurso selecionado para ver suas salas.</p>
                    </div>
                <?php elseif (empty($salas)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-door-open fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhuma sala encontrada</h5>
                        <p class="text-muted">Não há salas cadastradas para a escola selecionada.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped" id="salasTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Escola</th>
                                    <th>Tipo</th>
                                    <th>Capacidade</th>
                                    <th>Status</th>
                                    <th>Alocações</th>
                                    <th>Data Cadastro</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($salas as $sala): ?>
                            <tr>
                                <td><?= $sala['id'] ?></td>
                                <td><?= htmlspecialchars($sala['nome']) ?></td>
                                <td><?= htmlspecialchars($sala['escola_nome']) ?></td>
                                <td>
                                    <span class="badge bg-<?= getTipoSalaColor($sala['tipo']) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $sala['tipo'])) ?>
                                    </span>
                                </td>
                                <td><?= $sala['capacidade'] ?> pessoas</td>
                                <td>
                                    <span class="badge bg-<?= $sala['status'] == 'ativo' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($sala['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= $sala['total_alocacoes'] ?></span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($sala['data_cadastro'])) ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button onclick="editarSala(<?= $sala['id'] ?>)" 
                                                class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="toggleStatusSala(<?= $sala['id'] ?>)" 
                                                class="btn btn-sm btn-<?= $sala['status'] == 'ativo' ? 'danger' : 'success' ?>" 
                                                title="<?= $sala['status'] == 'ativo' ? 'Desativar' : 'Ativar' ?>">
                                            <i class="fas fa-<?= $sala['status'] == 'ativo' ? 'times' : 'check' ?>"></i>
                                        </button>
                                        <button onclick="verAlocacoes(<?= $sala['id'] ?>)" 
                                                class="btn btn-sm btn-info" title="Ver Alocações">
                                            <i class="fas fa-users"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nova/Editar Sala -->
<div class="modal fade" id="modalSala" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nova Sala</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSala">
                <div class="modal-body">
                    <input type="hidden" id="sala_id" name="sala_id">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome da Sala *</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="tipo" class="form-label">Tipo *</label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="">Selecione...</option>
                                    <option value="sala_aula">Sala de Aula</option>
                                    <option value="auditorio">Auditório</option>
                                    <option value="laboratorio">Laboratório</option>
                                    <option value="biblioteca">Biblioteca</option>
                                    <option value="sala_reuniao">Sala de Reunião</option>
                                    <option value="outro">Outro</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="escola_id_modal" class="form-label">Escola *</label>
                                <select class="form-select" id="escola_id_modal" name="escola_id" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($escolas as $escola): ?>
                                    <option value="<?= $escola['id'] ?>">
                                        <?= htmlspecialchars($escola['nome']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="capacidade" class="form-label">Capacidade *</label>
                                <input type="number" class="form-control" id="capacidade" name="capacidade" min="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
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

<!-- Modal Alocações -->
<div class="modal fade" id="modalAlocacoes" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alocações da Sala</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="alocacoesContent">
                    <!-- Conteúdo será carregado via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    $('#salasTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[1, 'asc']]
    });
    
    // Configurar formulário
    document.getElementById('formSala').addEventListener('submit', function(e) {
        e.preventDefault();
        salvarSala();
    });
});

function novaSala() {
    document.getElementById('modalTitle').textContent = 'Nova Sala';
    document.getElementById('formSala').reset();
    document.getElementById('sala_id').value = '';
    $('#modalSala').modal('show');
}

function editarSala(salaId) {
    fetch('buscar_sala.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            sala_id: salaId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const sala = data.sala;
            document.getElementById('modalTitle').textContent = 'Editar Sala';
            document.getElementById('sala_id').value = sala.id;
            document.getElementById('nome').value = sala.nome;
            document.getElementById('tipo').value = sala.tipo;
            document.getElementById('escola_id_modal').value = sala.escola_id;
            document.getElementById('capacidade').value = sala.capacidade;
            document.getElementById('descricao').value = sala.descricao;
            $('#modalSala').modal('show');
        } else {
            showMessage('Erro ao buscar sala: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showMessage('Erro ao processar requisição', 'error');
    });
}

function salvarSala() {
    const formData = new FormData(document.getElementById('formSala'));
    
    fetch('salvar_sala.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('Sala salva com sucesso!', 'success');
            $('#modalSala').modal('hide');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showMessage('Erro ao salvar sala: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showMessage('Erro ao processar requisição', 'error');
    });
}

function toggleStatusSala(salaId) {
    if (confirm('Confirmar alteração do status da sala?')) {
        fetch('toggle_status_sala.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                sala_id: salaId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Status da sala alterado com sucesso!', 'success');
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

function verAlocacoes(salaId) {
    fetch('buscar_alocacoes_sala.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            sala_id: salaId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('alocacoesContent').innerHTML = data.html;
            $('#modalAlocacoes').modal('show');
        } else {
            showMessage('Erro ao buscar alocações: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showMessage('Erro ao processar requisição', 'error');
    });
}

// Função para atualizar filtros em cascata
function atualizarFiltros() {
    const concursoId = document.getElementById('concurso_id').value;
    const escolaId = document.getElementById('escola_id').value;
    
    // Se mudou o concurso, limpar escola
    if (event.target.id === 'concurso_id') {
        document.getElementById('escola_id').value = '';
    }
    
    // Atualizar página com os filtros
    const params = new URLSearchParams();
    if (concursoId) params.append('concurso_id', concursoId);
    if (escolaId && concursoId) params.append('escola_id', escolaId);
    
    window.location.href = 'salas.php' + (params.toString() ? '?' + params.toString() : '');
}

// Função para limpar filtros
function limparFiltros() {
    window.location.href = 'salas.php';
}

// Habilitar/desabilitar select de escola baseado no concurso
document.addEventListener('DOMContentLoaded', function() {
    const concursoSelect = document.getElementById('concurso_id');
    const escolaSelect = document.getElementById('escola_id');
    
    function toggleEscolaSelect() {
        if (concursoSelect.value) {
            escolaSelect.disabled = false;
        } else {
            escolaSelect.disabled = true;
            escolaSelect.value = '';
        }
    }
    
    toggleEscolaSelect();
    concursoSelect.addEventListener('change', toggleEscolaSelect);
});
</script>

<?php 
// Funções auxiliares
function getTipoSalaColor($tipo) {
    switch ($tipo) {
        case 'sala_aula': return 'primary';
        case 'auditorio': return 'success';
        case 'laboratorio': return 'info';
        case 'biblioteca': return 'warning';
        case 'sala_reuniao': return 'secondary';
        default: return 'dark';
    }
}

include '../includes/footer.php'; 
?>