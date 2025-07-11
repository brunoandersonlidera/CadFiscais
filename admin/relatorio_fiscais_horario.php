<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$fiscais = [];

// Filtros
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : null;
$melhor_horario = isset($_GET['melhor_horario']) ? $_GET['melhor_horario'] : '';
$genero = isset($_GET['genero']) ? $_GET['genero'] : '';

try {
    $sql = "
        SELECT f.*, c.titulo as concurso_titulo,
               TIMESTAMPDIFF(YEAR, f.data_nascimento, CURDATE()) as idade
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        WHERE f.status = 'aprovado'
    ";
    $params = [];
    
    if ($concurso_id) {
        $sql .= " AND f.concurso_id = ?";
        $params[] = $concurso_id;
    }
    
    if ($melhor_horario) {
        $sql .= " AND f.melhor_horario = ?";
        $params[] = $melhor_horario;
    }
    
    if ($genero) {
        $sql .= " AND f.genero = ?";
        $params[] = $genero;
    }
    
    $sql .= " ORDER BY f.melhor_horario, f.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar fiscais por horário: ' . $e->getMessage(), 'ERROR');
}

// Buscar concursos para filtro
$concursos = [];
try {
    $stmt = $db->query("SELECT id, titulo FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC");
    $concursos = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar concursos: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Relatório de Fiscais por Horário';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-clock me-2"></i>
                Relatório de Fiscais por Horário
            </h1>
            <div>
                <button onclick="exportarPDF()" class="btn btn-danger">
                    <i class="fas fa-file-pdf me-2"></i>
                    Exportar PDF
                </button>
                <button onclick="exportarExcel()" class="btn btn-success">
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
                    <div class="col-md-4">
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
                    <div class="col-md-4">
                        <label for="melhor_horario" class="form-label">Melhor Horário</label>
                        <select class="form-select" id="melhor_horario" name="melhor_horario">
                            <option value="">Todos os horários</option>
                            <option value="manha" <?= $melhor_horario == 'manha' ? 'selected' : '' ?>>Manhã (8h às 12h)</option>
                            <option value="tarde" <?= $melhor_horario == 'tarde' ? 'selected' : '' ?>>Tarde (12h às 18h)</option>
                            <option value="noite" <?= $melhor_horario == 'noite' ? 'selected' : '' ?>>Noite (18h às 22h)</option>
                            <option value="qualquer" <?= $melhor_horario == 'qualquer' ? 'selected' : '' ?>>Qualquer horário</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="genero" class="form-label">Gênero</label>
                        <select class="form-select" id="genero" name="genero">
                            <option value="">Todos</option>
                            <option value="M" <?= $genero == 'M' ? 'selected' : '' ?>>Masculino</option>
                            <option value="F" <?= $genero == 'F' ? 'selected' : '' ?>>Feminino</option>
                        </select>
                    </div>
                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>
                            Filtrar
                        </button>
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
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">
                            <?= count(array_filter($fiscais, function($f) { return $f['melhor_horario'] == 'manha'; })) ?>
                        </h4>
                        <p class="mb-0">Manhã</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-sun fa-2x"></i>
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
                            <?= count(array_filter($fiscais, function($f) { return $f['melhor_horario'] == 'tarde'; })) ?>
                        </h4>
                        <p class="mb-0">Tarde</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-cloud-sun fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-dark text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">
                            <?= count(array_filter($fiscais, function($f) { return $f['melhor_horario'] == 'noite'; })) ?>
                        </h4>
                        <p class="mb-0">Noite</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-moon fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Agrupamento por Horário -->
<?php
$horarios_agrupados = [
    'manha' => [],
    'tarde' => [],
    'noite' => [],
    'qualquer' => []
];

foreach ($fiscais as $fiscal) {
    $horario = $fiscal['melhor_horario'] ?: 'qualquer';
    $horarios_agrupados[$horario][] = $fiscal;
}
?>

<?php foreach ($horarios_agrupados as $horario => $fiscais_horario): ?>
<?php if (!empty($fiscais_horario)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-<?= getHorarioColor($horario) ?> text-white">
                <h5 class="mb-0">
                    <i class="fas fa-<?= getHorarioIcon($horario) ?> me-2"></i>
                    <?= getHorarioLabel($horario) ?> (<?= count($fiscais_horario) ?> fiscais)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Celular</th>
                                <th>Email</th>
                                <th>Idade</th>
                                <th>Gênero</th>
                                <th>Concurso</th>
                                <th>Status Contato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fiscais_horario as $fiscal): ?>
                            <tr>
                                <td><?= htmlspecialchars($fiscal['nome']) ?></td>
                                <td><?= formatCPF($fiscal['cpf']) ?></td>
                                <td><?= formatPhone($fiscal['celular']) ?></td>
                                <td><?= htmlspecialchars($fiscal['email']) ?></td>
                                <td><?= $fiscal['idade'] ?> anos</td>
                                <td>
                                    <span class="badge bg-<?= $fiscal['genero'] == 'F' ? 'danger' : 'primary' ?>">
                                        <?= $fiscal['genero'] == 'F' ? 'F' : 'M' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($fiscal['concurso_titulo']) ?></td>
                                <td>
                                    <span class="badge bg-<?= getStatusContatoColor($fiscal['status_contato']) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $fiscal['status_contato'])) ?>
                                    </span>
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
<?php endforeach; ?>

<!-- Resumo Estatístico -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Resumo Estatístico
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Distribuição por Horário</h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Manhã (8h às 12h)
                                <span class="badge bg-warning rounded-pill">
                                    <?= count($horarios_agrupados['manha']) ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Tarde (12h às 18h)
                                <span class="badge bg-info rounded-pill">
                                    <?= count($horarios_agrupados['tarde']) ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Noite (18h às 22h)
                                <span class="badge bg-dark rounded-pill">
                                    <?= count($horarios_agrupados['noite']) ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Qualquer horário
                                <span class="badge bg-secondary rounded-pill">
                                    <?= count($horarios_agrupados['qualquer']) ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Distribuição por Gênero</h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Masculino
                                <span class="badge bg-primary rounded-pill">
                                    <?= count(array_filter($fiscais, function($f) { return $f['genero'] == 'M'; })) ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Feminino
                                <span class="badge bg-danger rounded-pill">
                                    <?= count(array_filter($fiscais, function($f) { return $f['genero'] == 'F'; })) ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportarPDF() {
    window.open('exportar_pdf_fiscais_horario.php?' + new URLSearchParams(window.location.search), '_blank');
}

function exportarExcel() {
    window.open('exportar_excel_fiscais_horario.php?' + new URLSearchParams(window.location.search), '_blank');
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

function getHorarioColor($horario) {
    switch ($horario) {
        case 'manha': return 'warning';
        case 'tarde': return 'info';
        case 'noite': return 'dark';
        case 'qualquer': return 'secondary';
        default: return 'secondary';
    }
}

function getHorarioIcon($horario) {
    switch ($horario) {
        case 'manha': return 'sun';
        case 'tarde': return 'cloud-sun';
        case 'noite': return 'moon';
        case 'qualquer': return 'clock';
        default: return 'clock';
    }
}

function getHorarioLabel($horario) {
    switch ($horario) {
        case 'manha': return 'Manhã (8h às 12h)';
        case 'tarde': return 'Tarde (12h às 18h)';
        case 'noite': return 'Noite (18h às 22h)';
        case 'qualquer': return 'Qualquer horário';
        default: return 'Não informado';
    }
}

function getStatusContatoColor($status) {
    switch ($status) {
        case 'confirmado': return 'success';
        case 'contatado': return 'info';
        case 'nao_contatado': return 'warning';
        case 'desistiu': return 'danger';
        default: return 'secondary';
    }
}

include '../includes/footer.php'; 
?> 