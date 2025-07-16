<?php
require_once '../config.php';
require_once '../includes/pdf_base.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$fiscais = [];

// Filtros
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$genero = isset($_GET['genero']) ? $_GET['genero'] : '';

try {
    $sql = "
        SELECT f.*, c.titulo as concurso_titulo,
               TIMESTAMPDIFF(YEAR, f.data_nascimento, CURDATE()) as idade
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        WHERE 1=1
    ";
    $params = [];
    
    if ($concurso_id) {
        $sql .= " AND f.concurso_id = ?";
        $params[] = $concurso_id;
    }
    
    if ($status) {
        $sql .= " AND f.status = ?";
        $params[] = $status;
    }
    
    if ($genero) {
        $sql .= " AND f.genero = ?";
        $params[] = $genero;
    }
    
    $sql .= " ORDER BY f.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar fiscais: ' . $e->getMessage(), 'ERROR');
}

// Buscar concursos para filtro
$concursos = [];
try {
    $stmt = $db->query("SELECT id, titulo FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC");
    $concursos = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar concursos: ' . $e->getMessage(), 'ERROR');
}

// Buscar dados do concurso
$concurso = null;
if ($concurso_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM concursos WHERE id = ?");
        $stmt->execute([$concurso_id]);
        $concurso = $stmt->fetch();
    } catch (Exception $e) {
        logActivity('Erro ao buscar concurso: ' . $e->getMessage(), 'ERROR');
    }
} else {
    // Se não especificado, buscar o concurso ativo mais recente
    try {
        $stmt = $db->query("SELECT * FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC LIMIT 1");
        $concurso = $stmt->fetch();
    } catch (Exception $e) {
        logActivity('Erro ao buscar concurso ativo: ' . $e->getMessage(), 'ERROR');
    }
}

$pageTitle = 'Relatório de Fiscais';
include '../includes/header.php';

$instituto_nome = getConfig('instituto_nome', 'Instituto Dignidade Humana');
$instituto_logo = __DIR__ . '/../logos/instituto.png';
$instituto_info = getConfig('info_institucional', 'Instituto Dignidade Humana\nEndereço: ...\nContato: ...');
$pdf = new PDFInstituto('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setInstitutoData($instituto_nome, $instituto_logo, $instituto_info);
$pdf->AddPage();
$pdf->Ln(18); // Espaço extra após o cabeçalho

// Informações do concurso centralizadas
if ($concurso) {
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 8, $concurso['orgao'] . ' - ' . $concurso['cidade'] . ' - ' . $concurso['estado'], 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, $concurso['titulo'] . ' - ' . $concurso['numero_concurso'] . '/' . $concurso['ano_concurso'], 0, 1, 'C');
    $pdf->Ln(8);
}

// Título do relatório
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-users me-2"></i>
                Relatório de Fiscais
            </h1>
            <button class="btn btn-danger" onclick="exportarPDF()">
                <i class="fas fa-file-pdf me-2"></i> Gerar PDF
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
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Todos os status</option>
                            <option value="pendente" <?= $status == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="aprovado" <?= $status == 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                            <option value="reprovado" <?= $status == 'reprovado' ? 'selected' : '' ?>>Reprovado</option>
                            <option value="cancelado" <?= $status == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="genero" class="form-label">Gênero</label>
                        <select class="form-select" id="genero" name="genero">
                            <option value="">Todos</option>
                            <option value="M" <?= $genero == 'M' ? 'selected' : '' ?>>Masculino</option>
                            <option value="F" <?= $genero == 'F' ? 'selected' : '' ?>>Feminino</option>
                        </select>
                    </div>
                    <div class="col-md-3">
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
                            <?= count(array_filter($fiscais, function($f) { return $f['status'] == 'aprovado'; })) ?>
                        </h4>
                        <p class="mb-0">Aprovados</p>
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
                            <?= count(array_filter($fiscais, function($f) { return $f['status'] == 'pendente'; })) ?>
                        </h4>
                        <p class="mb-0">Pendentes</p>
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
                            <?= count(array_filter($fiscais, function($f) { return $f['genero'] == 'F'; })) ?>
                        </h4>
                        <p class="mb-0">Mulheres</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-venus fa-2x"></i>
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
                                <th>Gênero</th>
                                <th>Concurso</th>
                                <th>Status</th>
                                <th>Data Cadastro</th>
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
                                <td>
                                    <span class="badge bg-<?= $fiscal['genero'] == 'F' ? 'danger' : 'primary' ?>">
                                        <?= $fiscal['genero'] == 'F' ? 'F' : 'M' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($fiscal['concurso_titulo']) ?></td>
                                <td>
                                    <span class="badge bg-<?= getStatusColor($fiscal['status']) ?>">
                                        <?= ucfirst($fiscal['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($fiscal['created_at'])) ?></td>
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
    $('#fiscaisTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        responsive: true,
        pageLength: 50,
        order: [[1, 'asc']],
        dom: 'Bfrtip',
        buttons: [
            'pdf', 'print'
        ]
    });
});

function exportarPDF() {
    window.open('exportar_pdf_fiscais.php?' + new URLSearchParams(window.location.search), '_blank');
}

function exportarExcel() {
    window.open('exportar_excel_fiscais.php?' + new URLSearchParams(window.location.search), '_blank');
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