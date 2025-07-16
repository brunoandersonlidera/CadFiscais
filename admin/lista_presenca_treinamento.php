<?php
require_once '../config.php';

// Verificar se tem permissão para presença
if (!isLoggedIn() || !temPermissaoPresenca()) {
    redirect('../login.php');
}

$db = getDB();
$fiscais = [];

// Filtros
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : null;
$escola_id = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : null;
$data_treinamento = isset($_GET['data_treinamento']) ? $_GET['data_treinamento'] : date('Y-m-d');

try {
    $sql = "
        SELECT 
            f.*, 
            c.titulo as concurso_titulo, 
            c.data_prova,
            TIMESTAMPDIFF(YEAR, f.data_nascimento, CURDATE()) as idade,
            a.escola_id, 
            a.sala_id, 
            a.data_alocacao, 
            a.horario_alocacao,
            a.tipo_alocacao,
            e.nome as escola_nome, 
            s.nome as sala_nome
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
    
    $sql .= " ORDER BY e.nome, s.nome, f.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: mostrar quantos fiscais foram encontrados
    echo "<!-- Debug: " . count($fiscais) . " fiscais encontrados -->";
    
} catch (Exception $e) {
    logActivity('Erro ao buscar fiscais para treinamento: ' . $e->getMessage(), 'ERROR');
    echo "<!-- Debug: Erro na consulta: " . $e->getMessage() . " -->";
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

$pageTitle = 'Lista de Presença - Treinamento';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-graduation-cap me-2"></i>
                Lista de Presença - Treinamento
            </h1>
            <div>
                <button onclick="imprimirLista()" class="btn btn-primary">
                    <i class="fas fa-print me-2"></i>
                    Imprimir
                </button>
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
                    <div class="col-md-3">
                        <label for="data_treinamento" class="form-label">Data do Treinamento</label>
                        <input type="date" class="form-control" id="data_treinamento" name="data_treinamento" 
                               value="<?= $data_treinamento ?>">
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
                            <?= count(array_unique(array_column($fiscais, 'escola_id'))) ?>
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
                            <?= count(array_unique(array_column($fiscais, 'sala_id'))) ?>
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

<!-- Lista de Presença -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Lista de Presença - Treinamento - <?= date('d/m/Y', strtotime($data_treinamento)) ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($fiscais)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum fiscal encontrado com os filtros selecionados.
                </div>
                <?php else: ?>
                
                <!-- Agrupamento por Escola -->
                <?php
                $escolas_agrupadas = [];
                foreach ($fiscais as $fiscal) {
                    $escola_nome = $fiscal['escola_nome'] ?? 'Não Alocado';
                    $sala_nome = $fiscal['sala_nome'] ?? 'Não Alocado';
                    $chave = $escola_nome . ' - ' . $sala_nome;
                    $escolas_agrupadas[$chave][] = $fiscal;
                }
                ?>
                
                <?php foreach ($escolas_agrupadas as $chave => $fiscais_escola): ?>
                <div class="mb-4">
                    <h6 class="text-primary">
                        <i class="fas fa-school me-2"></i>
                        <?= htmlspecialchars($chave) ?>
                    </h6>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead class="table-warning">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="25%">Nome</th>
                                    <th width="15%">CPF</th>
                                    <th width="15%">Celular</th>
                                    <th width="10%">Idade</th>
                                    <th width="10%">Gênero</th>
                                    <th width="20%">Assinatura</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fiscais_escola as $index => $fiscal): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($fiscal['nome']) ?></td>
                                    <td><?= formatCPF($fiscal['cpf']) ?></td>
                                    <td><?= formatPhone($fiscal['celular']) ?></td>
                                    <td><?= $fiscal['idade'] ?? 'N/A' ?> anos</td>
                                    <td><?= $fiscal['genero'] === 'F' ? 'F' : 'M' ?></td>
                                    <td>
                                        <div style="height: 30px; border-bottom: 1px solid #ccc;"></div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Informações Adicionais -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Informações do Treinamento</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($fiscais)): ?>
                                <p><strong>Concurso:</strong> <?= htmlspecialchars($fiscais[0]['concurso_titulo']) ?></p>
                                <p><strong>Data do Treinamento:</strong> <?= date('d/m/Y', strtotime($data_treinamento)) ?></p>
                                <p><strong>Total de Fiscais:</strong> <?= count($fiscais) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Instruções do Treinamento</h6>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li>Marque a presença de cada fiscal</li>
                                    <li>Confirme se está na sala correta</li>
                                    <li>Verifique se chegou no horário</li>
                                    <li>Anote observações se necessário</li>
                                    <li>Distribua material de treinamento</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function imprimirLista() {
    window.print();
}

function exportarPDF() {
    const params = new URLSearchParams(window.location.search);
    params.set('tipo', 'treinamento');
    window.open('exportar_pdf_presenca.php?' + params.toString(), '_blank');
}
</script>

<style>
@media print {
    .btn, .card-header, .navbar, .footer {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .table {
        font-size: 12px;
    }
    
    .table th, .table td {
        padding: 4px;
    }
}
</style>

<?php 
include '../includes/footer.php'; 
?> 