<?php
require_once '../config.php';
require_once '../includes/pdf_base.php';

// Verificar se tem permissão para relatórios
if (!isLoggedIn() || !temPermissaoPresenca()) {
    redirect('../login.php');
}

$db = getDB();
$relatorio = [];

// Filtros
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : null;
$escola_id = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : null;
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

try {
    $sql = "
        SELECT 
            f.id,
            f.nome,
            f.cpf,
            f.celular,
            c.titulo as concurso_titulo,
            e.nome as escola_nome,
            s.nome as sala_nome,
            a.data_alocacao,
            a.horario_alocacao,
            a.tipo_alocacao,
            pt.status as presente_treinamento,
            pt.observacoes as obs_treinamento,
            pp.status as presente_prova,
            pp.observacoes as obs_prova
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        LEFT JOIN alocacoes_fiscais a ON f.id = a.fiscal_id AND a.status = 'ativo'
        LEFT JOIN escolas e ON a.escola_id = e.id
        LEFT JOIN salas s ON a.sala_id = s.id
        LEFT JOIN presenca pt ON f.id = pt.fiscal_id AND pt.concurso_id = f.concurso_id AND pt.tipo_presenca = 'treinamento'
        LEFT JOIN presenca pp ON f.id = pp.fiscal_id AND pp.concurso_id = f.concurso_id AND pp.tipo_presenca = 'prova'
        WHERE f.status = 'aprovado'
    ";
    $params = [];
    
    if ($concurso_id) {
        $sql .= " AND f.concurso_id = ?";
        $params[] = $concurso_id;
    }
    
    if ($escola_id) {
        $sql .= " AND a.escola_id = ?";
        $params[] = $escola_id;
    }
    
    $sql .= " ORDER BY e.nome, s.nome, f.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $relatorio = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar dados para relatório: ' . $e->getMessage(), 'ERROR');
}

// Buscar concursos para filtro
$concursos = [];
try {
    $stmt = $db->query("SELECT id, titulo FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC");
    $concursos = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar concursos: ' . $e->getMessage(), 'ERROR');
}

// Buscar escolas para filtro
$escolas = [];
try {
    $stmt = $db->query("SELECT id, nome FROM escolas WHERE status = 'ativo' ORDER BY nome");
    $escolas = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar escolas: ' . $e->getMessage(), 'ERROR');
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

$pageTitle = 'Relatório de Comparecimento';
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
                <i class="fas fa-clipboard-check me-2"></i>
                Relatório de Comparecimento
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
                        <label for="escola_id" class="form-label">Escola</label>
                        <select class="form-select" id="escola_id" name="escola_id">
                            <option value="">Todas as escolas</option>
                            <?php foreach ($escolas as $escola): ?>
                            <option value="<?= $escola['id'] ?>" <?= $escola_id == $escola['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($escola['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="data_inicio" class="form-label">Data Início</label>
                        <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?= $data_inicio ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="data_fim" class="form-label">Data Fim</label>
                        <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?= $data_fim ?>">
                    </div>
                    <div class="col-md-2">
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
                        <h4 class="mb-0"><?= count($relatorio) ?></h4>
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
                            <?= count(array_filter($relatorio, function($r) { return $r['presente_treinamento'] == 'presente'; })) ?>
                        </h4>
                        <p class="mb-0">Presentes Treinamento</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-graduation-cap fa-2x"></i>
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
                            <?= count(array_filter($relatorio, function($r) { return $r['presente_prova'] == 'presente'; })) ?>
                        </h4>
                        <p class="mb-0">Presentes Prova</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-file-alt fa-2x"></i>
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
                            <?= count(array_filter($relatorio, function($r) { return $r['presente_treinamento'] == 'presente' && $r['presente_prova'] == 'presente'; })) ?>
                        </h4>
                        <p class="mb-0">Presentes Ambos</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-double fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Relatório -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>
                    Relatório de Comparecimento (<?= count($relatorio) ?> fiscais)
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($relatorio)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="relatorioTable">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Concurso</th>
                                <th>Escola</th>
                                <th>Sala</th>
                                <th>Data Alocação</th>
                                <th>Treinamento</th>
                                <th>Prova</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($relatorio as $fiscal): ?>
                            <tr>
                                <td><?= htmlspecialchars($fiscal['nome']) ?></td>
                                <td><?= formatCPF($fiscal['cpf']) ?></td>
                                <td><?= htmlspecialchars($fiscal['concurso_titulo']) ?></td>
                                <td><?= htmlspecialchars($fiscal['escola_nome'] ?? 'Não alocado') ?></td>
                                <td><?= htmlspecialchars($fiscal['sala_nome'] ?? 'Não alocado') ?></td>
                                <td><?= $fiscal['data_alocacao'] ? date('d/m/Y', strtotime($fiscal['data_alocacao'])) : 'N/A' ?></td>
                                <td>
                                    <?php if ($fiscal['presente_treinamento'] == 'presente'): ?>
                                    <span class="badge bg-success">Presente</span>
                                    <?php elseif ($fiscal['presente_treinamento'] == 'ausente'): ?>
                                    <span class="badge bg-danger">Ausente</span>
                                    <?php elseif ($fiscal['presente_treinamento'] == 'justificado'): ?>
                                    <span class="badge bg-warning">Justificado</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Não registrado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($fiscal['presente_prova'] == 'presente'): ?>
                                    <span class="badge bg-success">Presente</span>
                                    <?php elseif ($fiscal['presente_prova'] == 'ausente'): ?>
                                    <span class="badge bg-danger">Ausente</span>
                                    <?php elseif ($fiscal['presente_prova'] == 'justificado'): ?>
                                    <span class="badge bg-warning">Justificado</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Não registrado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $treinamento = $fiscal['presente_treinamento'] ?? null;
                                    $prova = $fiscal['presente_prova'] ?? null;
                                    
                                    if ($treinamento === 'presente' && $prova === 'presente') {
                                        echo '<span class="badge bg-success">Completo</span>';
                                    } elseif ($treinamento === 'presente' || $prova === 'presente') {
                                        echo '<span class="badge bg-warning">Parcial</span>';
                                    } elseif ($treinamento === 'ausente' || $prova === 'ausente') {
                                        echo '<span class="badge bg-danger">Ausente</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary">Não registrado</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">Nenhum dado encontrado</h5>
                    <p class="text-muted">Tente ajustar os filtros ou verificar se há dados disponíveis.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    $('#relatorioTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[0, 'asc']]
    });
});

function exportarPDF() {
    window.open('exportar_pdf_relatorio_comparecimento.php?' + new URLSearchParams(window.location.search), '_blank');
}

function exportarExcel() {
    window.open('exportar_excel_relatorio_comparecimento.php?' + new URLSearchParams(window.location.search), '_blank');
}
</script>

<?php include '../includes/footer.php'; ?> 