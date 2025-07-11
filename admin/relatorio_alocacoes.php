<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$alocacoes = [];

// Filtros
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : null;
$escola_id = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : null;
$tipo_alocacao = isset($_GET['tipo_alocacao']) ? $_GET['tipo_alocacao'] : '';
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

try {
    $sql = "
        SELECT a.*, f.nome as fiscal_nome, f.cpf as fiscal_cpf, f.celular as fiscal_celular,
               c.titulo as concurso_titulo, e.nome as escola_nome, s.nome as sala_nome,
               u.nome as usuario_nome
        FROM alocacoes_fiscais a
        LEFT JOIN fiscais f ON a.fiscal_id = f.id
        LEFT JOIN concursos c ON a.concurso_id = c.id
        LEFT JOIN escolas e ON a.escola_id = e.id
        LEFT JOIN salas s ON a.sala_id = s.id
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.status = 'ativo'
    ";
    $params = [];
    
    if ($concurso_id) {
        $sql .= " AND a.concurso_id = ?";
        $params[] = $concurso_id;
    }
    
    if ($escola_id) {
        $sql .= " AND a.escola_id = ?";
        $params[] = $escola_id;
    }
    
    if ($tipo_alocacao) {
        $sql .= " AND a.tipo_alocacao = ?";
        $params[] = $tipo_alocacao;
    }
    
    if ($data_inicio) {
        $sql .= " AND DATE(a.data_alocacao) >= ?";
        $params[] = $data_inicio;
    }
    
    if ($data_fim) {
        $sql .= " AND DATE(a.data_alocacao) <= ?";
        $params[] = $data_fim;
    }
    
    $sql .= " ORDER BY a.data_alocacao DESC, e.nome, s.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $alocacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar alocações: ' . $e->getMessage(), 'ERROR');
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

$pageTitle = 'Relatório de Alocações';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-map-marker-alt me-2"></i>
                Relatório de Alocações
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
                    <div class="col-md-2">
                        <label for="concurso_id" class="form-label">Concurso</label>
                        <select class="form-select" id="concurso_id" name="concurso_id">
                            <option value="">Todos</option>
                            <?php foreach ($concursos as $concurso): ?>
                            <option value="<?= $concurso['id'] ?>" <?= $concurso_id == $concurso['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($concurso['titulo']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="escola_id" class="form-label">Escola</label>
                        <select class="form-select" id="escola_id" name="escola_id">
                            <option value="">Todas</option>
                            <?php foreach ($escolas as $escola): ?>
                            <option value="<?= $escola['id'] ?>" <?= $escola_id == $escola['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($escola['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="tipo_alocacao" class="form-label">Tipo</label>
                        <select class="form-select" id="tipo_alocacao" name="tipo_alocacao">
                            <option value="">Todos</option>
                            <option value="prova" <?= $tipo_alocacao == 'prova' ? 'selected' : '' ?>>Prova</option>
                            <option value="treinamento" <?= $tipo_alocacao == 'treinamento' ? 'selected' : '' ?>>Treinamento</option>
                            <option value="reuniao" <?= $tipo_alocacao == 'reuniao' ? 'selected' : '' ?>>Reunião</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="data_inicio" class="form-label">Data Início</label>
                        <input type="date" class="form-control" id="data_inicio" name="data_inicio" 
                               value="<?= $data_inicio ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="data_fim" class="form-label">Data Fim</label>
                        <input type="date" class="form-control" id="data_fim" name="data_fim" 
                               value="<?= $data_fim ?>">
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
                        <h4 class="mb-0"><?= count($alocacoes) ?></h4>
                        <p class="mb-0">Total de Alocações</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-map-marker-alt fa-2x"></i>
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
                            <?= count(array_unique(array_column($alocacoes, 'escola_id'))) ?>
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
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">
                            <?= count(array_unique(array_column($alocacoes, 'sala_id'))) ?>
                        </h4>
                        <p class="mb-0">Salas</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-door-open fa-2x"></i>
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
                            <?= count(array_unique(array_column($alocacoes, 'fiscal_id'))) ?>
                        </h4>
                        <p class="mb-0">Fiscais Únicos</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Alocações -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Lista de Alocações (<?= count($alocacoes) ?>)
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="alocacoesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fiscal</th>
                                <th>CPF</th>
                                <th>Concurso</th>
                                <th>Escola</th>
                                <th>Sala</th>
                                <th>Tipo</th>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Alocado por</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alocacoes as $alocacao): ?>
                            <tr>
                                <td><?= $alocacao['id'] ?></td>
                                <td><?= htmlspecialchars($alocacao['fiscal_nome']) ?></td>
                                <td><?= formatCPF($alocacao['fiscal_cpf']) ?></td>
                                <td><?= htmlspecialchars($alocacao['concurso_titulo']) ?></td>
                                <td><?= htmlspecialchars($alocacao['escola_nome']) ?></td>
                                <td><?= htmlspecialchars($alocacao['sala_nome']) ?></td>
                                <td>
                                    <span class="badge bg-<?= getTipoAlocacaoColor($alocacao['tipo_alocacao']) ?>">
                                        <?= ucfirst($alocacao['tipo_alocacao']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($alocacao['data_alocacao'])) ?></td>
                                <td><?= $alocacao['horario_alocacao'] ?? 'N/A' ?></td>
                                <td><?= htmlspecialchars($alocacao['usuario_nome']) ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="editar_alocacao.php?id=<?= $alocacao['id'] ?>" 
                                           class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="cancelarAlocacao(<?= $alocacao['id'] ?>)" 
                                                class="btn btn-sm btn-danger" title="Cancelar">
                                            <i class="fas fa-times"></i>
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

<!-- Resumo por Escola -->
<?php if (!empty($alocacoes)): ?>
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
                foreach ($alocacoes as $alocacao) {
                    $escola = $alocacao['escola_nome'];
                    if (!isset($escolas_resumo[$escola])) {
                        $escolas_resumo[$escola] = [
                            'total' => 0,
                            'prova' => 0,
                            'treinamento' => 0,
                            'reuniao' => 0
                        ];
                    }
                    $escolas_resumo[$escola]['total']++;
                    $escolas_resumo[$escola][$alocacao['tipo_alocacao']]++;
                }
                ?>
                
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Escola</th>
                                <th>Total</th>
                                <th>Provas</th>
                                <th>Treinamentos</th>
                                <th>Reuniões</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($escolas_resumo as $escola => $dados): ?>
                            <tr>
                                <td><?= htmlspecialchars($escola) ?></td>
                                <td><span class="badge bg-primary"><?= $dados['total'] ?></span></td>
                                <td><span class="badge bg-success"><?= $dados['prova'] ?></span></td>
                                <td><span class="badge bg-warning"><?= $dados['treinamento'] ?></span></td>
                                <td><span class="badge bg-info"><?= $dados['reuniao'] ?></span></td>
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
    $('#alocacoesTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        responsive: true,
        pageLength: 50,
        order: [[7, 'desc']],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});

function cancelarAlocacao(alocacaoId) {
    if (confirm('Confirmar cancelamento desta alocação?')) {
        fetch('cancelar_alocacao.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                alocacao_id: alocacaoId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Alocação cancelada com sucesso!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showMessage('Erro ao cancelar alocação: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showMessage('Erro ao processar requisição', 'error');
        });
    }
}

function exportarPDF() {
    window.open('exportar_pdf_alocacoes.php?' + new URLSearchParams(window.location.search), '_blank');
}

function exportarExcel() {
    window.open('exportar_excel_alocacoes.php?' + new URLSearchParams(window.location.search), '_blank');
}
</script>

<?php 
// Funções auxiliares
function formatCPF($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

function getTipoAlocacaoColor($tipo) {
    switch ($tipo) {
        case 'prova': return 'success';
        case 'treinamento': return 'warning';
        case 'reuniao': return 'info';
        default: return 'secondary';
    }
}

include '../includes/footer.php'; 
?> 