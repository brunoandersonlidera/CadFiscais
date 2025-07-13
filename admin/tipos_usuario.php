<?php
require_once '../config.php';

// Verificar se é admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$tipos_usuario = [];

try {
    $stmt = $db->query("
        SELECT t.*, COUNT(u.id) as total_usuarios
        FROM tipos_usuario t 
        LEFT JOIN usuarios u ON t.id = u.tipo_usuario_id AND u.status = 'ativo'
        GROUP BY t.id
        ORDER BY t.nome
    ");
    $tipos_usuario = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar tipos de usuário: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Tipos de Usuário';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-user-tag me-2"></i>
                Tipos de Usuário
            </h1>
            <div>
                <a href="novo_tipo_usuario.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>
                    Novo Tipo
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Lista de Tipos de Usuário (<?= count($tipos_usuario) ?>)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="tiposTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Permissões</th>
                                <th>Usuários Ativos</th>
                                <th>Data Criação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tipos_usuario as $tipo): ?>
                            <tr>
                                <td><?= $tipo['id'] ?></td>
                                <td><?= htmlspecialchars($tipo['nome']) ?></td>
                                <td><?= htmlspecialchars($tipo['descricao']) ?></td>
                                <td>
                                    <?php 
                                    $permissoes = json_decode($tipo['permissoes'], true);
                                    if ($permissoes): ?>
                                        <?php foreach ($permissoes as $permissao => $valor): ?>
                                            <?php if ($valor): ?>
                                                <span class="badge bg-info me-1"><?= ucfirst($permissao) ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Nenhuma</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= $tipo['total_usuarios'] ?></span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($tipo['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="verDetalhes(<?= $tipo['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-warning" 
                                                onclick="editarTipo(<?= $tipo['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($tipo['total_usuarios'] == 0): ?>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteTipo(<?= $tipo['id'] ?>)">
                                            <i class="fas fa-trash"></i>
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

<!-- Modal de Detalhes -->
<div class="modal fade" id="detalhesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-tag me-2"></i>
                    Detalhes do Tipo de Usuário
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalhesContent">
                <!-- Conteúdo será carregado via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" onclick="editarTipo()">Editar</button>
            </div>
        </div>
    </div>
</div>

<script>
let table;

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    table = $('#tiposTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[0, 'asc']],
        columnDefs: [
            { targets: [6], orderable: false, searchable: false }
        ]
    });
});

function verDetalhes(id) {
    showLoading();
    
    fetch(`get_tipo_usuario.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                document.getElementById('detalhesContent').innerHTML = data.html;
                document.getElementById('detalhesModal').setAttribute('data-tipo-id', id);
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

function editarTipo(id) {
    if (id) {
        window.location.href = `editar_tipo_usuario.php?id=${id}`;
    } else {
        // Pegar ID do modal
        const modal = document.getElementById('detalhesModal');
        const id = modal.getAttribute('data-tipo-id');
        if (id) {
            window.location.href = `editar_tipo_usuario.php?id=${id}`;
        }
    }
}

function deleteTipo(id) {
    confirmAction('Tem certeza que deseja excluir este tipo de usuário?', function() {
        showLoading();
        
        fetch('delete_tipo_usuario.php', {
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
                showMessage(data.message, 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showMessage('Erro ao excluir tipo de usuário', 'error');
        });
    });
}
</script>

<?php include '../includes/footer.php'; ?> 