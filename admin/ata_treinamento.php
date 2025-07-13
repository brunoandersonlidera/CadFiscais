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
$data_treinamento = isset($_GET['data_treinamento']) ? $_GET['data_treinamento'] : date('Y-m-d');
$horario_treinamento = isset($_GET['horario_treinamento']) ? $_GET['horario_treinamento'] : '';

try {
    $sql = "
        SELECT f.*, c.titulo as concurso_titulo, c.data_prova,
               TIMESTAMPDIFF(YEAR, f.data_nascimento, CURDATE()) as idade,
               a.escola_id, a.sala_id, a.data_alocacao, a.horario_alocacao,
               e.nome as escola_nome, s.nome as sala_nome
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        LEFT JOIN alocacoes_fiscais a ON f.id = a.fiscal_id AND a.status = 'ativo' AND a.tipo_alocacao = 'treinamento'
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
    
    if ($horario_treinamento) {
        $sql .= " AND a.horario_alocacao = ?";
        $params[] = $horario_treinamento;
    }
    
    $sql .= " ORDER BY e.nome, s.nome, f.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar fiscais para ata: ' . $e->getMessage(), 'ERROR');
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

$pageTitle = 'Ata de Treinamento';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-file-alt me-2"></i>
                Ata de Treinamento
            </h1>
            <div>
                <button onclick="imprimirAta()" class="btn btn-primary">
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
                    <div class="col-md-2">
                        <label for="data_treinamento" class="form-label">Data</label>
                        <input type="date" class="form-control" id="data_treinamento" name="data_treinamento" 
                               value="<?= $data_treinamento ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="horario_treinamento" class="form-label">Horário</label>
                        <select class="form-select" id="horario_treinamento" name="horario_treinamento">
                            <option value="">Todos</option>
                            <option value="08:00" <?= $horario_treinamento == '08:00' ? 'selected' : '' ?>>08:00</option>
                            <option value="09:00" <?= $horario_treinamento == '09:00' ? 'selected' : '' ?>>09:00</option>
                            <option value="10:00" <?= $horario_treinamento == '10:00' ? 'selected' : '' ?>>10:00</option>
                            <option value="14:00" <?= $horario_treinamento == '14:00' ? 'selected' : '' ?>>14:00</option>
                            <option value="15:00" <?= $horario_treinamento == '15:00' ? 'selected' : '' ?>>15:00</option>
                            <option value="16:00" <?= $horario_treinamento == '16:00' ? 'selected' : '' ?>>16:00</option>
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

<!-- Ata de Treinamento -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">
                    <i class="fas fa-file-alt me-2"></i>
                    Ata de Treinamento - <?= date('d/m/Y', strtotime($data_treinamento)) ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($fiscais)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum fiscal encontrado com os filtros selecionados.
                </div>
                <?php else: ?>
                
                <!-- Cabeçalho da Ata -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="text-center">
                            <h4 class="text-primary">ATA DE TREINAMENTO</h4>
                            <p class="mb-1"><strong>Data:</strong> <?= date('d/m/Y', strtotime($data_treinamento)) ?></p>
                            <p class="mb-1"><strong>Horário:</strong> <?= $horario_treinamento ?: 'A definir' ?></p>
                            <p class="mb-1"><strong>Concurso:</strong> <?= htmlspecialchars($fiscais[0]['concurso_titulo']) ?></p>
                            <p class="mb-0"><strong>Total de Participantes:</strong> <?= count($fiscais) ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Informações do Treinamento -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Objetivos do Treinamento</h6>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li>Capacitar fiscais para atuação no concurso</li>
                                    <li>Esclarecer procedimentos e normas</li>
                                    <li>Treinar uso de equipamentos</li>
                                    <li>Simular situações de prova</li>
                                    <li>Tirar dúvidas dos participantes</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Material Distribuído</h6>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li>Manual do Fiscal</li>
                                    <li>Lista de Procedimentos</li>
                                    <li>Credencial de Identificação</li>
                                    <li>Material de Escritório</li>
                                    <li>Certificado de Participação</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Participantes -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h6 class="text-primary">Lista de Participantes</h6>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead class="table-warning">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="25%">Nome</th>
                                        <th width="15%">CPF</th>
                                        <th width="15%">Celular</th>
                                        <th width="15%">Escola</th>
                                        <th width="15%">Sala</th>
                                        <th width="10%">Assinatura</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fiscais as $index => $fiscal): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($fiscal['nome']) ?></td>
                                        <td><?= formatCPF($fiscal['cpf']) ?></td>
                                        <td><?= formatPhone($fiscal['celular']) ?></td>
                                        <td><?= htmlspecialchars($fiscal['escola_nome'] ?? 'Não alocado') ?></td>
                                        <td><?= htmlspecialchars($fiscal['sala_nome'] ?? 'Não alocado') ?></td>
                                        <td>
                                            <div style="height: 30px; border-bottom: 1px solid #ccc;"></div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Conteúdo do Treinamento -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Conteúdo Abordado</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Procedimentos Gerais</h6>
                                        <ul>
                                            <li>Chegada e identificação</li>
                                            <li>Distribuição de materiais</li>
                                            <li>Controle de tempo</li>
                                            <li>Procedimentos de segurança</li>
                                            <li>Comunicação com candidatos</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Normas Específicas</h6>
                                        <ul>
                                            <li>Regulamento do concurso</li>
                                            <li>Procedimentos de fiscalização</li>
                                            <li>Como lidar com irregularidades</li>
                                            <li>Preenchimento de documentos</li>
                                            <li>Procedimentos de emergência</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Observações e Conclusões -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Observações</h6>
                            </div>
                            <div class="card-body">
                                <textarea class="form-control" rows="6" placeholder="Anote aqui observações importantes sobre o treinamento..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Conclusões</h6>
                            </div>
                            <div class="card-body">
                                <textarea class="form-control" rows="6" placeholder="Anote aqui as conclusões do treinamento..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Assinaturas -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="border-top pt-3">
                            <p class="text-center mb-0">
                                <strong>Responsável pelo Treinamento</strong>
                            </p>
                            <div style="height: 80px; border-bottom: 1px solid #ccc; margin-top: 10px;"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border-top pt-3">
                            <p class="text-center mb-0">
                                <strong>Coordenador</strong>
                            </p>
                            <div style="height: 80px; border-bottom: 1px solid #ccc; margin-top: 10px;"></div>
                        </div>
                    </div>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function imprimirAta() {
    window.print();
}

function exportarPDF() {
    window.open('exportar_pdf_ata_treinamento.php?' + new URLSearchParams(window.location.search), '_blank');
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
    
    body {
        font-size: 12px;
    }
    
    h4, h6 {
        font-size: 14px;
    }
    
    .table {
        font-size: 11px;
    }
    
    .table th, .table td {
        padding: 3px;
    }
}
</style>

<?php 
include '../includes/footer.php'; 
?> 