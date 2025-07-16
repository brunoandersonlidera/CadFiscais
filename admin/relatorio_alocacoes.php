<?php
require_once '../config.php';
require_once '../includes/pdf_base.php';

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
        SELECT a.id, f.nome as fiscal_nome, e.nome as escola_nome, e.endereco as escola_endereco, e.responsavel as escola_responsavel, s.nome as sala_nome,
               a.tipo_alocacao, a.observacoes, a.data_alocacao, a.horario_alocacao, a.status, a.created_at, a.updated_at,
               f.concurso_id
        FROM alocacoes_fiscais a
        LEFT JOIN fiscais f ON a.fiscal_id = f.id
        LEFT JOIN escolas e ON a.escola_id = e.id
        LEFT JOIN salas s ON a.sala_id = s.id
        WHERE 1=1
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
    $sql .= " ORDER BY a.data_alocacao DESC, e.nome, s.nome";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $alocacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar alocações: ' . $e->getMessage(), 'ERROR');
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
                <i class="fas fa-map-marker-alt me-2"></i>
                Relatório de Alocações
            </h1>
            <div>
                <button onclick="exportarPDF()" class="btn btn-danger">
                    <i class="fas fa-file-pdf me-2"></i>
                    Exportar PDF
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
                            <option value="">Todos</option>
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
                            <option value="">Todas</option>
                            <?php foreach ($escolas as $escola): ?>
                            <option value="<?= $escola['id'] ?>" <?= $escola_id == $escola['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($escola['nome']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 align-self-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>
                            Filtrar
                        </button>
                    </div>
                </form>
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
                                <th>Fiscal</th>
                                <th>Escola</th>
                                <th>Endereço da Escola</th>
                                <th>Coordenador</th>
                                <th>Sala</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alocacoes as $alocacao): ?>
                            <tr>
                                <td><?= htmlspecialchars($alocacao['fiscal_nome'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($alocacao['escola_nome'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($alocacao['escola_endereco'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($alocacao['escola_responsavel'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($alocacao['sala_nome'] ?? 'N/A') ?></td>
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

    // Atualizar escolas ao mudar concurso
    document.getElementById('concurso_id').addEventListener('change', function() {
        const concursoId = this.value;
        const escolaSelect = document.getElementById('escola_id');
        escolaSelect.innerHTML = '<option value="">Todas</option>';
        if (concursoId) {
            fetch('buscar_escola.php?concurso_id=' + encodeURIComponent(concursoId))
                .then(response => response.json())
                .then(data => {
                    data.forEach(escola => {
                        const opt = document.createElement('option');
                        opt.value = escola.id;
                        opt.textContent = escola.nome;
                        escolaSelect.appendChild(opt);
                    });
                });
        } else {
            // Se nenhum concurso, pode buscar todas as escolas ou deixar só "Todas"
        }
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
    // Pega os valores dos filtros diretamente dos selects
    const concursoId = document.getElementById('concurso_id').value;
    const escolaId = document.getElementById('escola_id').value;
    let params = [];
    if (concursoId) params.push('concurso_id=' + encodeURIComponent(concursoId));
    if (escolaId) params.push('escola_id=' + encodeURIComponent(escolaId));
    const url = 'exportar_pdf_alocacoes.php' + (params.length ? '?' + params.join('&') : '');
    window.open(url, '_blank');
}

function exportarExcel() {
    window.open('exportar_excel_alocacoes.php?' + new URLSearchParams(window.location.search), '_blank');
}
</script>

<?php 
// Funções auxiliares
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