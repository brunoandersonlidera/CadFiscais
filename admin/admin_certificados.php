<?php
require_once '../config.php';

// Verificar se o usuário está logado e tem permissão
if (!isLoggedIn() || !hasPermission('admin')) {
    header('Location: login.php');
    exit;
}

$db = getDB();
$message = '';
$error = '';

// Processar ações
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'delete':
            $cert_id = $_POST['cert_id'] ?? 0;
            if ($cert_id) {
                $stmt = $db->prepare("UPDATE certificados SET status = 'cancelado' WHERE id = ?");
                if ($stmt->execute([$cert_id])) {
                    $message = 'Certificado cancelado com sucesso!';
                } else {
                    $error = 'Erro ao cancelar certificado.';
                }
            }
            break;
            
        case 'reactivate':
            $cert_id = $_POST['cert_id'] ?? 0;
            if ($cert_id) {
                $stmt = $db->prepare("UPDATE certificados SET status = 'ativo' WHERE id = ?");
                if ($stmt->execute([$cert_id])) {
                    $message = 'Certificado reativado com sucesso!';
                } else {
                    $error = 'Erro ao reativar certificado.';
                }
            }
            break;
            
        case 'update_date':
            $cert_id = $_POST['cert_id'] ?? 0;
            $new_date = $_POST['new_date'] ?? '';
            if ($cert_id && $new_date) {
                $stmt = $db->prepare("UPDATE certificados SET data_treinamento = ? WHERE id = ?");
                if ($stmt->execute([$new_date, $cert_id])) {
                    $message = 'Data do treinamento atualizada com sucesso!';
                } else {
                    $error = 'Erro ao atualizar data do treinamento.';
                }
            }
            break;
    }
}

// Filtros
$filtro_status = $_GET['status'] ?? 'todos';
$filtro_concurso = $_GET['concurso'] ?? '';
$filtro_nome = $_GET['nome'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Construir query com filtros
$where_conditions = [];
$params = [];

if ($filtro_status !== 'todos') {
    $where_conditions[] = "cert.status = ?";
    $params[] = $filtro_status;
}

if (!empty($filtro_concurso)) {
    $where_conditions[] = "c.id = ?";
    $params[] = $filtro_concurso;
}

if (!empty($filtro_nome)) {
    $where_conditions[] = "f.nome LIKE ?";
    $params[] = '%' . $filtro_nome . '%';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Query principal
$sql = "
    SELECT cert.*, f.nome as fiscal_nome, f.cpf, c.titulo as concurso_titulo, c.numero_concurso,
           u.nome as usuario_gerador
    FROM certificados cert
    INNER JOIN fiscais f ON cert.fiscal_id = f.id
    INNER JOIN concursos c ON cert.concurso_id = c.id
    LEFT JOIN usuarios u ON cert.usuario_gerador_id = u.id
    $where_clause
    ORDER BY cert.data_geracao DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar total para paginação
$count_sql = "
    SELECT COUNT(*) as total
    FROM certificados cert
    INNER JOIN fiscais f ON cert.fiscal_id = f.id
    INNER JOIN concursos c ON cert.concurso_id = c.id
    $where_clause
";

$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $per_page);

// Buscar concursos para filtro
$concursos_stmt = $db->query("SELECT id, titulo, numero_concurso, ano_concurso, orgao, cidade, estado FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC");
$concursos = $concursos_stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$stats_sql = "
    SELECT 
        COUNT(*) as total_certificados,
        COUNT(CASE WHEN status = 'ativo' THEN 1 END) as ativos,
        COUNT(CASE WHEN status = 'cancelado' THEN 1 END) as cancelados,
        COUNT(CASE WHEN DATE(data_geracao) = CURDATE() THEN 1 END) as hoje
    FROM certificados
";
$stats = $db->query($stats_sql)->fetch(PDO::FETCH_ASSOC);
?>

<?php
$pageTitle = 'Administração de Certificados';
include '../includes/header.php';
?>

<style>
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .cert-actions {
        white-space: nowrap;
    }
    .status-badge {
        font-size: 0.8em;
    }
    .table-responsive {
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-certificate me-2"></i>
                Administração de Certificados
            </h1>
        </div>
    </div>
</div>

<div class="container-fluid">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3><?= number_format($stats['total_certificados']) ?></h3>
                    <p class="mb-0">Total de Certificados</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3><?= number_format($stats['ativos']) ?></h3>
                    <p class="mb-0">Certificados Ativos</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3><?= number_format($stats['cancelados']) ?></h3>
                    <p class="mb-0">Certificados Cancelados</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3><?= number_format($stats['hoje']) ?></h3>
                    <p class="mb-0">Gerados Hoje</p>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>
                    Filtros
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="todos" <?= $filtro_status === 'todos' ? 'selected' : '' ?>>Todos</option>
                            <option value="ativo" <?= $filtro_status === 'ativo' ? 'selected' : '' ?>>Ativos</option>
                            <option value="cancelado" <?= $filtro_status === 'cancelado' ? 'selected' : '' ?>>Cancelados</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Concurso</label>
                        <select name="concurso" class="form-select">
                            <option value="">Todos os concursos</option>
                            <?php foreach ($concursos as $concurso): ?>
                                <option value="<?= $concurso['id'] ?>" <?= $filtro_concurso == $concurso['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($concurso['titulo']) ?> <?= htmlspecialchars($concurso['numero_concurso']) ?>/<?= htmlspecialchars($concurso['ano_concurso']) ?> da <?= htmlspecialchars($concurso['orgao']) ?> de <?= htmlspecialchars($concurso['cidade']) ?>/<?= htmlspecialchars($concurso['estado']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nome do Fiscal</label>
                        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($filtro_nome) ?>" placeholder="Digite o nome...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block w-100">
                            <i class="fas fa-search me-1"></i>
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Certificados -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Certificados Gerados (<?= number_format($total_records) ?> registros)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Nº Certificado</th>
                                <th>Fiscal</th>
                                <th>CPF</th>
                                <th>Concurso</th>
                                <th>Data Treinamento</th>
                                <th>Data Geração</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($certificados)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Nenhum certificado encontrado com os filtros aplicados.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($certificados as $cert): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($cert['numero_certificado']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($cert['fiscal_nome']) ?></td>
                                        <td><?= formatCPF($cert['cpf']) ?></td>
                                        <td>
                                            <small class="text-muted"><?= htmlspecialchars($cert['numero_concurso']) ?></small><br>
                                            <?= htmlspecialchars($cert['concurso_titulo']) ?>
                                        </td>
                                        <td>
                                            <?php if ($cert['data_treinamento'] && $cert['data_treinamento'] !== '0000-00-00'): ?>
                                                <?= date('d/m/Y', strtotime($cert['data_treinamento'])) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Data não informada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($cert['data_geracao'])) ?></td>
                                        <td>
                                            <?php if ($cert['status'] === 'ativo'): ?>
                                                <span class="badge bg-success status-badge">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger status-badge">Cancelado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="cert-actions">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <!-- Visualizar Certificado -->
                                                <a href="gerar_certificado_pdf.php?cpf=<?= urlencode($cert['cpf']) ?>" 
                                                   class="btn btn-outline-primary" 
                                                   title="Visualizar Certificado" 
                                                   target="_blank">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <!-- Editar Data -->
                                                <button type="button" 
                                                        class="btn btn-outline-warning" 
                                                        title="Editar Data" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editDateModal" 
                                                        data-cert-id="<?= $cert['id'] ?>" 
                                                        data-current-date="<?= $cert['data_treinamento'] ?>">
                                                    <i class="fas fa-calendar-edit"></i>
                                                </button>
                                                
                                                <?php if ($cert['status'] === 'ativo'): ?>
                                                    <!-- Cancelar -->
                                                    <button type="button" 
                                                            class="btn btn-outline-danger" 
                                                            title="Cancelar Certificado" 
                                                            onclick="confirmAction('delete', <?= $cert['id'] ?>, 'Tem certeza que deseja cancelar este certificado?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <!-- Reativar -->
                                                    <button type="button" 
                                                            class="btn btn-outline-success" 
                                                            title="Reativar Certificado" 
                                                            onclick="confirmAction('reactivate', <?= $cert['id'] ?>, 'Tem certeza que deseja reativar este certificado?')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Paginação -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Modal para Editar Data -->
    <div class="modal fade" id="editDateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Data do Treinamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_date">
                        <input type="hidden" name="cert_id" id="edit_cert_id">
                        <div class="mb-3">
                            <label for="new_date" class="form-label">Nova Data do Treinamento</label>
                            <input type="date" class="form-control" name="new_date" id="new_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Form oculto para ações -->
    <form id="actionForm" method="POST" style="display: none;">
        <input type="hidden" name="action" id="action_type">
        <input type="hidden" name="cert_id" id="action_cert_id">
    </form>

</div> <!-- Fecha container-fluid -->

<script>
    function confirmAction(action, certId, message) {
        if (confirm(message)) {
            document.getElementById('action_type').value = action;
            document.getElementById('action_cert_id').value = certId;
            document.getElementById('actionForm').submit();
        }
    }

    // Modal para editar data
    document.getElementById('editDateModal').addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var certId = button.getAttribute('data-cert-id');
        var currentDate = button.getAttribute('data-current-date');
        
        document.getElementById('edit_cert_id').value = certId;
        document.getElementById('new_date').value = currentDate !== '0000-00-00' ? currentDate : '';
    });
</script>

<?php include '../includes/footer.php'; ?>