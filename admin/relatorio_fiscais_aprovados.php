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
$escola_id = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : null;

try {
    $sql = "
        SELECT f.*, c.titulo as concurso_titulo, c.data_prova,
               TIMESTAMPDIFF(YEAR, f.data_nascimento, CURDATE()) as idade,
               a.escola_id, a.sala_id, a.data_alocacao, a.horario_alocacao,
               e.nome as escola_nome, s.nome as sala_nome
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        LEFT JOIN alocacoes_fiscais a ON f.id = a.fiscal_id AND a.status = 'ativo'
        LEFT JOIN escolas e ON a.escola_id = e.id
        LEFT JOIN salas s ON a.sala_id = s.id
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
    
    $sql .= " ORDER BY f.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar fiscais aprovados: ' . $e->getMessage(), 'ERROR');
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

$pageTitle = 'Relatório de Fiscais Aprovados';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-check-circle me-2"></i>
                Relatório de Fiscais Aprovados
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
                    <div class="col-md-4">
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
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?= count($fiscais) ?></h4>
                        <p class="mb-0">Fiscais Aprovados</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">
                            <?= count(array_filter($fiscais, function($f) { return $f['genero'] == 'M'; })) ?>
                        </h4>
                        <p class="mb-0">Homens</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-mars fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-danger text-white">
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
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">
                            <?= count(array_filter($fiscais, function($f) { return !empty($f['escola_id']); })) ?>
                        </h4>
                        <p class="mb-0">Alocados</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-map-marker-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Fiscais Aprovados -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Lista de Fiscais Aprovados (<?= count($fiscais) ?>)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="fiscaisAprovadosTable">
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
                                <th>Escola</th>
                                <th>Sala</th>
                                <th>Data Aprovação</th>
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
                                <td><?= htmlspecialchars($fiscal['escola_nome'] ?? 'Não alocado') ?></td>
                                <td><?= htmlspecialchars($fiscal['sala_nome'] ?? 'Não alocado') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($fiscal['updated_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resumo por Escola -->
<?php if (!empty($fiscais)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Resumo por Escola
                </h5>
            </div>
            <div class="card-body">
                <?php
                $escolas_resumo = [];
                foreach ($fiscais as $fiscal) {
                    $escola = $fiscal['escola_nome'] ?? 'Não Alocado';
                    if (!isset($escolas_resumo[$escola])) {
                        $escolas_resumo[$escola] = [
                            'total' => 0,
                            'homens' => 0,
                            'mulheres' => 0
                        ];
                    }
                    $escolas_resumo[$escola]['total']++;
                    if ($fiscal['genero'] == 'M') {
                        $escolas_resumo[$escola]['homens']++;
                    } else {
                        $escolas_resumo[$escola]['mulheres']++;
                    }
                }
                ?>
                
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Escola</th>
                                <th>Total</th>
                                <th>Homens</th>
                                <th>Mulheres</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($escolas_resumo as $escola => $dados): ?>
                            <tr>
                                <td><?= htmlspecialchars($escola) ?></td>
                                <td><span class="badge bg-primary"><?= $dados['total'] ?></span></td>
                                <td><span class="badge bg-primary"><?= $dados['homens'] ?></span></td>
                                <td><span class="badge bg-danger"><?= $dados['mulheres'] ?></span></td>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    $('#fiscaisAprovadosTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        responsive: true,
        pageLength: 50,
        order: [[1, 'asc']],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});

function exportarPDF() {
    window.open('exportar_pdf_fiscais_aprovados.php?' + new URLSearchParams(window.location.search), '_blank');
}

function exportarExcel() {
    window.open('exportar_excel_fiscais_aprovados.php?' + new URLSearchParams(window.location.search), '_blank');
}
</script>

<?php 
// Funções auxiliares
)(\d{5})(\d{4})/', '($1) $2-$3', $phone);
    }
    return $phone;
}

)(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

function getStatusContatoColor($status) {
    switch ($status) {
        case 'confirmado': return 'success';
        case 'nao_respondeu': return 'info';
        case 'nao_contatado': return 'warning';
        case 'desistiu': return 'danger';
        default: return 'secondary';
    }
}

include '../includes/footer.php'; 
?> 