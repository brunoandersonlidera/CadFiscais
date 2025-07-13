<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$comparecimentos = [];

// Filtros
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : null;
$escola_id = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : null;
$data_evento = isset($_GET['data_evento']) ? $_GET['data_evento'] : date('Y-m-d');
$tipo_evento = isset($_GET['tipo_evento']) ? $_GET['tipo_evento'] : '';

try {
    $sql = "
        SELECT f.*, c.titulo as concurso_titulo,
               TIMESTAMPDIFF(YEAR, f.data_nascimento, CURDATE()) as idade,
               a.escola_id, a.sala_id, a.data_alocacao, a.horario_alocacao, a.tipo_alocacao,
               e.nome as escola_nome, s.nome as sala_nome,
               p.status as presenca_status, p.data_registro, p.observacoes
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        LEFT JOIN alocacoes_fiscais a ON f.id = a.fiscal_id AND a.status = 'ativo'
        LEFT JOIN escolas e ON a.escola_id = e.id
        LEFT JOIN salas s ON a.sala_id = s.id
        LEFT JOIN presenca p ON f.id = p.fiscal_id AND DATE(p.data_evento) = ?
        WHERE f.status = 'aprovado'
    ";
    $params = [$data_evento];
    
    if ($concurso_id) {
        $sql .= " AND f.concurso_id = ?";
        $params[] = $concurso_id;
    }
    
    if ($escola_id) {
        $sql .= " AND a.escola_id = ?";
        $params[] = $escola_id;
    }
    
    if ($tipo_evento) {
        $sql .= " AND a.tipo_alocacao = ?";
        $params[] = $tipo_evento;
    }
    
    $sql .= " ORDER BY e.nome, s.nome, f.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $comparecimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar comparecimentos: ' . $e->getMessage(), 'ERROR');
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

$pageTitle = 'Relatório de Comparecimento';
include '../includes/header.php';
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
                        <label for="data_evento" class="form-label">Data do Evento</label>
                        <input type="date" class="form-control" id="data_evento" name="data_evento" 
                               value="<?= $data_evento ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="tipo_evento" class="form-label">Tipo de Evento</label>
                        <select class="form-select" id="tipo_evento" name="tipo_evento">
                            <option value="">Todos</option>
                            <option value="prova" <?= $tipo_evento == 'prova' ? 'selected' : '' ?>>Prova</option>
                            <option value="treinamento" <?= $tipo_evento == 'treinamento' ? 'selected' : '' ?>>Treinamento</option>
                            <option value="reuniao" <?= $tipo_evento == 'reuniao' ? 'selected' : '' ?>>Reunião</option>
                        </select>
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
                        <h4 class="mb-0"><?= count($comparecimentos) ?></h4>
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
                            <?= count(array_filter($comparecimentos, function($c) { return $c['presenca_status'] == 'presente'; })) ?>
                        </h4>
                        <p class="mb-0">Presentes</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x"></i>
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
                            <?= count(array_filter($comparecimentos, function($c) { return $c['presenca_status'] == 'ausente'; })) ?>
                        </h4>
                        <p class="mb-0">Ausentes</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-times-circle fa-2x"></i>
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
                            <?= count(array_filter($comparecimentos, function($c) { return !$c['presenca_status']; })) ?>
                        </h4>
                        <p class="mb-0">Não Registrados</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-question-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Comparecimento -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Lista de Comparecimento - <?= date('d/m/Y', strtotime($data_evento)) ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="comparecimentoTable">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Celular</th>
                                <th>Escola</th>
                                <th>Sala</th>
                                <th>Tipo Evento</th>
                                <th>Status</th>
                                <th>Horário Chegada</th>
                                <th>Observações</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comparecimentos as $comparecimento): ?>
                            <tr>
                                <td><?= htmlspecialchars($comparecimento['nome']) ?></td>
                                <td><?= formatCPF($comparecimento['cpf']) ?></td>
                                <td><?= formatPhone($comparecimento['celular']) ?></td>
                                <td><?= htmlspecialchars($comparecimento['escola_nome'] ?? 'Não alocado') ?></td>
                                <td><?= htmlspecialchars($comparecimento['sala_nome'] ?? 'Não alocado') ?></td>
                                <td>
                                    <span class="badge bg-<?= getTipoEventoColor($comparecimento['tipo_alocacao']) ?>">
                                        <?= ucfirst($comparecimento['tipo_alocacao']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($comparecimento['presenca_status']): ?>
                                    <span class="badge bg-<?= getStatusPresencaColor($comparecimento['presenca_status']) ?>">
                                        <?= ucfirst($comparecimento['presenca_status']) ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Não registrado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $comparecimento['data_registro'] ? date('H:i', strtotime($comparecimento['data_registro'])) : '-' ?>
                                </td>
                                <td><?= htmlspecialchars($comparecimento['observacoes'] ?? '') ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <?php if (!$comparecimento['presenca_status']): ?>
                                        <button onclick="marcarPresenca(<?= $comparecimento['id'] ?>, 'presente')" 
                                                class="btn btn-sm btn-success" title="Marcar Presente">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="marcarPresenca(<?= $comparecimento['id'] ?>, 'ausente')" 
                                                class="btn btn-sm btn-danger" title="Marcar Ausente">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php else: ?>
                                        <button onclick="editarPresenca(<?= $comparecimento['id'] ?>)" 
                                                class="btn btn-sm btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
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

<!-- Resumo por Escola -->
<?php if (!empty($comparecimentos)): ?>
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
                foreach ($comparecimentos as $comparecimento) {
                    $escola = $comparecimento['escola_nome'] ?? 'Não Alocado';
                    if (!isset($escolas_resumo[$escola])) {
                        $escolas_resumo[$escola] = [
                            'total' => 0,
                            'presentes' => 0,
                            'ausentes' => 0,
                            'nao_registrados' => 0
                        ];
                    }
                    $escolas_resumo[$escola]['total']++;
                    
                    if ($comparecimento['presenca_status'] == 'presente') {
                        $escolas_resumo[$escola]['presentes']++;
                    } elseif ($comparecimento['presenca_status'] == 'ausente') {
                        $escolas_resumo[$escola]['ausentes']++;
                    } else {
                        $escolas_resumo[$escola]['nao_registrados']++;
                    }
                }
                ?>
                
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Escola</th>
                                <th>Total</th>
                                <th>Presentes</th>
                                <th>Ausentes</th>
                                <th>Não Registrados</th>
                                <th>Taxa de Comparecimento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($escolas_resumo as $escola => $dados): ?>
                            <tr>
                                <td><?= htmlspecialchars($escola) ?></td>
                                <td><span class="badge bg-primary"><?= $dados['total'] ?></span></td>
                                <td><span class="badge bg-success"><?= $dados['presentes'] ?></span></td>
                                <td><span class="badge bg-danger"><?= $dados['ausentes'] ?></span></td>
                                <td><span class="badge bg-warning"><?= $dados['nao_registrados'] ?></span></td>
                                <td>
                                    <?php 
                                    $taxa = $dados['total'] > 0 ? round(($dados['presentes'] / $dados['total']) * 100, 1) : 0;
                                    $cor_taxa = $taxa >= 80 ? 'success' : ($taxa >= 60 ? 'warning' : 'danger');
                                    ?>
                                    <span class="badge bg-<?= $cor_taxa ?>"><?= $taxa ?>%</span>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable
    $('#comparecimentoTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        responsive: true,
        pageLength: 50,
        order: [[0, 'asc']],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
});

function marcarPresenca(fiscalId, status) {
    const dataEvento = document.getElementById('data_evento').value;
    if (!dataEvento) {
        showMessage('Selecione uma data de evento', 'error');
        return;
    }
    
    if (confirm(`Confirmar marcação como ${status}?`)) {
        fetch('marcar_presenca.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                fiscal_id: fiscalId,
                status: status,
                data_evento: dataEvento
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(`Presença marcada como ${status} com sucesso!`, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showMessage('Erro ao marcar presença: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showMessage('Erro ao processar requisição', 'error');
        });
    }
}

function editarPresenca(fiscalId) {
    // Implementar modal de edição de presença
    showMessage('Funcionalidade de edição será implementada em breve', 'info');
}

function exportarPDF() {
    window.open('exportar_pdf_comparecimento.php?' + new URLSearchParams(window.location.search), '_blank');
}

function exportarExcel() {
    window.open('exportar_excel_comparecimento.php?' + new URLSearchParams(window.location.search), '_blank');
}
</script>

<?php 
// Funções auxiliares
)(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

)(\d{5})(\d{4})/', '($1) $2-$3', $phone);
    }
    return $phone;
}

function getTipoEventoColor($tipo) {
    switch ($tipo) {
        case 'prova': return 'success';
        case 'treinamento': return 'warning';
        case 'reuniao': return 'info';
        default: return 'secondary';
    }
}

function getStatusPresencaColor($status) {
    switch ($status) {
        case 'presente': return 'success';
        case 'ausente': return 'danger';
        case 'atrasado': return 'warning';
        default: return 'secondary';
    }
}

include '../includes/footer.php'; 
?> 