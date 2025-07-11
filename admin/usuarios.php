<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$usuarios = [];

try {
    $stmt = $db->query("
        SELECT id, nome, email, tipo_usuario, status, created_at, last_login
        FROM usuarios 
        ORDER BY created_at DESC
    ");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar usuários: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Gerenciar Usuários';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-users-cog me-2"></i>
                Gerenciar Usuários
            </h1>
            <div>
                <a href="novo_usuario.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>
                    Novo Usuário
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
                <div class="row">
                    <div class="col-md-4">
                        <label for="tipoFilter" class="form-label">Tipo de Usuário</label>
                        <select class="form-select" id="tipoFilter">
                            <option value="">Todos</option>
                            <option value="admin">Administrador</option>
                            <option value="colaborador">Colaborador</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="statusFilter" class="form-label">Status</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">Todos</option>
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="searchFilter" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="searchFilter" placeholder="Nome ou email">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Usuários -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Lista de Usuários (<?= count($usuarios) ?>)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="usuariosTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Último Login</th>
                                <th>Data Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= $usuario['id'] ?></td>
                                <td><?= htmlspecialchars($usuario['nome']) ?></td>
                                <td><?= htmlspecialchars($usuario['email']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $usuario['tipo_usuario'] == 'admin' ? 'danger' : 'info' ?>">
                                        <?= ucfirst($usuario['tipo_usuario']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $usuario['status'] == 'ativo' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($usuario['status']) ?>
                                    </span>
                                </td>
                                <td><?= $usuario['last_login'] ? date('d/m/Y H:i', strtotime($usuario['last_login'])) : 'Nunca' ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($usuario['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="verDetalhes(<?= $usuario['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-warning" 
                                                onclick="editarUsuario(<?= $usuario['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteUsuario(<?= $usuario['id'] ?>)">
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
                    Detalhes do Usuário
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalhesContent">
                <!-- Conteúdo será carregado via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" onclick="editarUsuario()">Editar</button>
            </div>
        </div>
    </div>
</div>

<script>
let table;

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    table = $('#usuariosTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[0, 'desc']],
        columnDefs: [
            { targets: [7], orderable: false, searchable: false }
        ]
    });
    
    // Filtros
    $('#tipoFilter').on('change', function() {
        table.column(3).search($(this).val()).draw();
    });
    
    $('#statusFilter').on('change', function() {
        table.column(4).search($(this).val()).draw();
    });
    
    $('#searchFilter').on('keyup', function() {
        table.search($(this).val()).draw();
    });
});

function verDetalhes(id) {
    showLoading();
    
    fetch(`get_usuario.php?id=${id}`)
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

function editarUsuario(id) {
    if (id) {
        window.location.href = `editar_usuario.php?id=${id}`;
    } else {
        // Pegar ID do modal
        const modal = document.getElementById('detalhesModal');
        const id = modal.getAttribute('data-usuario-id');
        if (id) {
            window.location.href = `editar_usuario.php?id=${id}`;
        }
    }
}

function deleteUsuario(id) {
    confirmAction('Tem certeza que deseja excluir este usuário?', function() {
        showLoading();
        
        fetch('delete_usuario.php', {
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
                showMessage('Usuário excluído com sucesso!');
                setTimeout(() => location.reload(), 1500);
            } else {
                showMessage(data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showMessage('Erro ao excluir usuário', 'error');
        });
    });
}
</script>

<?php include '../includes/footer.php'; ?> 