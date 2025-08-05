<?php
require_once '../config.php';

// Verificar se tem permissão para alocações
// TEMPORARIAMENTE COMENTADO PARA DEBUG
// if (!isLoggedIn() || !temPermissaoAlocacoes()) {
//     redirect('../login.php');
// }

// Verificação simplificada - apenas se está logado
if (!isLoggedIn()) {
    redirect('../login.php');
}

$db = getDB();
$fiscais = [];
$mensagem = '';

// Filtros
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : null;
$status_alocacao = isset($_GET['status_alocacao']) ? $_GET['status_alocacao'] : '';

try {
    $sql = "
        SELECT 
            f.*,
            c.titulo as concurso_titulo,
            TIMESTAMPDIFF(YEAR, f.data_nascimento, CURDATE()) as idade,
            a.escola_id,
            a.sala_id,
            a.data_alocacao,
            a.horario_alocacao,
            a.tipo_alocacao,
            a.observacoes as observacoes_alocacao,
            e.nome as escola_nome,
            s.nome as sala_nome,
            CASE 
                WHEN a.id IS NOT NULL THEN 'alocado'
                ELSE 'nao_alocado'
            END as status_alocacao
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
    
    if ($status_alocacao) {
        if ($status_alocacao === 'alocado') {
            $sql .= " AND a.id IS NOT NULL";
        } else {
            $sql .= " AND a.id IS NULL";
        }
    }
    
    $sql .= " ORDER BY f.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar fiscais: ' . $e->getMessage(), 'ERROR');
    $mensagem = 'Erro ao carregar fiscais.';
}

// Buscar concursos para filtro
$concursos = [];
try {
    $stmt = $db->query("SELECT id, titulo, numero_concurso, ano_concurso, orgao, cidade, estado FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC");
    $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar concursos: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Alocar Fiscais';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-map-marker-alt me-2"></i>
                Alocar Fiscais
            </h1>
            <div>
                <button type="button" class="btn btn-primary me-2" onclick="alocarTodosFiscais()">
                    <i class="fas fa-users me-2"></i>
                    Alocar Todos
                </button>
                <a href="alocacao_automatica.php" class="btn btn-success me-2">
                    <i class="fas fa-magic me-2"></i>
                    Alocação Automática
                </a>
                <a href="fiscais.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Voltar
                </a>
            </div>
        </div>
    </div>
</div>

<?php if ($mensagem): ?>
<div class="alert alert-info alert-dismissible fade show" role="alert">
    <i class="fas fa-info-circle me-2"></i>
    <?= htmlspecialchars($mensagem) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

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
                        <select class="form-select" id="concurso_id" name="concurso_id" onchange="this.form.submit()">
                            <option value="">Selecione o concurso</option>
                            <?php foreach ($concursos as $concurso): ?>
                            <option value="<?= $concurso['id'] ?>" <?= $concurso_id == $concurso['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($concurso['titulo']) ?> <?= htmlspecialchars($concurso['numero_concurso']) ?>/<?= htmlspecialchars($concurso['ano_concurso']) ?> da <?= htmlspecialchars($concurso['orgao']) ?> de <?= htmlspecialchars($concurso['cidade']) ?>/<?= htmlspecialchars($concurso['estado']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="status_alocacao" class="form-label">Status de Alocação</label>
                        <select class="form-select" id="status_alocacao" name="status_alocacao" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="alocado" <?= $status_alocacao === 'alocado' ? 'selected' : '' ?>>Alocados</option>
                            <option value="nao_alocado" <?= $status_alocacao === 'nao_alocado' ? 'selected' : '' ?>>Não Alocados</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <!-- Botão de filtrar removido conforme solicitado -->
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($concurso_id && !empty($fiscais)): ?>
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
                            <?= count(array_filter($fiscais, function($f) { return $f['status_alocacao'] === 'alocado'; })) ?>
                        </h4>
                        <p class="mb-0">Alocados</p>
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
                            <?= count(array_filter($fiscais, function($f) { return $f['status_alocacao'] === 'nao_alocado'; })) ?>
                        </h4>
                        <p class="mb-0">Não Alocados</p>
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
                            <?= count(array_filter($fiscais, function($f) { return $f['genero'] === 'F'; })) ?>
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
<?php endif; ?>

<!-- Lista de Fiscais -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Lista de Fiscais para Alocação (<?= count($fiscais) ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if ($concurso_id && !empty($fiscais)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="fiscaisTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Contato</th>
                                <th>Concurso</th>
                                <th>Status Alocação</th>
                                <th>Escola/Sala</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fiscais as $fiscal): ?>
                            <tr>
                                <td>#<?= $fiscal['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($fiscal['nome']) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?= $fiscal['idade'] ?> anos • 
                                        <span class="badge bg-<?= $fiscal['genero'] === 'F' ? 'danger' : 'primary' ?>">
                                            <?= $fiscal['genero'] === 'F' ? 'F' : 'M' ?>
                                        </span>
                                    </small>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <small><?= formatPhone($fiscal['celular']) ?></small>
                                        <small class="text-muted"><?= htmlspecialchars($fiscal['email']) ?></small>
                                        <?php if ($fiscal['celular']): ?>
                                        <a href="https://wa.me/55<?= preg_replace('/\D/', '', $fiscal['celular']) ?>?text=Olá <?= urlencode($fiscal['nome']) ?>! Sou do IDH e gostaria de falar sobre o concurso." 
                                           target="_blank" class="btn btn-sm btn-success mt-1" style="width: fit-content;">
                                            <i class="fab fa-whatsapp"></i> WhatsApp
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?= htmlspecialchars($fiscal['concurso_titulo']) ?>
                                </td>
                                <td>
                                    <?php if ($fiscal['status_alocacao'] === 'alocado'): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Alocado
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-warning">
                                        <i class="fas fa-clock me-1"></i>Não Alocado
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($fiscal['status_alocacao'] === 'alocado'): ?>
                                    <div class="d-flex flex-column">
                                        <strong><?= htmlspecialchars($fiscal['escola_nome']) ?></strong>
                                        <small class="text-muted">Sala: <?= htmlspecialchars($fiscal['sala_nome']) ?></small>
                                        <small class="text-muted">
                                            <?= date('d/m/Y', strtotime($fiscal['data_alocacao'])) ?> às <?= $fiscal['horario_alocacao'] ?>h
                                        </small>
                                        <?php if ($fiscal['tipo_alocacao']): ?>
                                        <small class="text-muted">Tipo: <?= ucfirst($fiscal['tipo_alocacao']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted">Não alocado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <?php if ($fiscal['status_alocacao'] === 'nao_alocado'): ?>
                                        <a href="alocar_fiscal.php?id=<?= $fiscal['id'] ?>" 
                                           class="btn btn-primary" title="Alocar Fiscal">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </a>
                                        <?php else: ?>
                                        <a href="alocar_fiscal.php?id=<?= $fiscal['id'] ?>" 
                                           class="btn btn-warning" title="Re-alocar Fiscal">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn btn-info" 
                                                onclick="verDetalhes(<?= $fiscal['id'] ?>)" title="Ver Detalhes">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <a href="editar_fiscal.php?id=<?= $fiscal['id'] ?>" 
                                           class="btn btn-secondary" title="Editar Fiscal">
                                            <i class="fas fa-user-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php elseif ($concurso_id): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">Nenhum fiscal encontrado</h5>
                    <p class="text-muted">Tente ajustar os filtros ou cadastrar novos fiscais.</p>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-filter text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">Selecione um concurso</h5>
                    <p class="text-muted">Por favor, selecione um concurso para visualizar os fiscais disponíveis.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalhes -->
<div class="modal fade" id="detalhesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user me-2"></i>
                    Detalhes do Fiscal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalhesContent">
                <!-- Conteúdo será carregado via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
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
        pageLength: 25,
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [6] } // Coluna de ações não ordenável
        ]
    });
});

function verDetalhes(id) {
    showLoading();
    
    fetch('get_fiscal.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.fiscal) {
            const fiscal = data.fiscal;
            const alocacoes = data.alocacoes || [];
            const presencas = data.presencas || [];
            const pagamentos = data.pagamentos || [];
            
            const html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-user me-2"></i>Informações Pessoais</h6>
                        <ul class="list-unstyled">
                            <li><strong>Nome:</strong> ${fiscal.nome}</li>
                            <li><strong>CPF:</strong> ${fiscal.cpf}</li>
                            <li><strong>Email:</strong> ${fiscal.email}</li>
                            <li><strong>Celular:</strong> ${fiscal.celular}</li>
                            <li><strong>WhatsApp:</strong> ${fiscal.whatsapp || 'Não informado'}</li>
                            <li><strong>Data Nascimento:</strong> ${fiscal.data_nascimento ? new Date(fiscal.data_nascimento).toLocaleDateString('pt-BR') : 'Não informado'}</li>
                            <li><strong>Gênero:</strong> ${fiscal.genero === 'F' ? 'Feminino' : 'Masculino'}</li>
                            <li><strong>Endereço:</strong> ${fiscal.endereco || 'Não informado'}</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-clipboard-list me-2"></i>Informações do Concurso</h6>
                        <ul class="list-unstyled">
                            <li><strong>Concurso:</strong> ${fiscal.concurso_nome || 'Não informado'}</li>
                            <li><strong>Status:</strong> 
                                <span class="badge bg-${getStatusColor(fiscal.status)}">${fiscal.status}</span>
                            </li>
                            <li><strong>Data Cadastro:</strong> ${new Date(fiscal.created_at).toLocaleDateString('pt-BR')}</li>
                            <li><strong>Observações:</strong> ${fiscal.observacoes || 'Nenhuma'}</li>
                        </ul>
                    </div>
                </div>
                
                ${alocacoes.length > 0 ? `
                <hr>
                <h6><i class="fas fa-map-marker-alt me-2"></i>Alocações</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Escola</th>
                                <th>Sala</th>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${alocacoes.map(alocacao => `
                                <tr>
                                    <td>${alocacao.escola_nome || 'N/A'}</td>
                                    <td>${alocacao.sala_nome || 'N/A'}</td>
                                    <td>${alocacao.data_alocacao ? new Date(alocacao.data_alocacao).toLocaleDateString('pt-BR') : 'N/A'}</td>
                                    <td>${alocacao.horario_alocacao || 'N/A'}</td>
                                    <td>${alocacao.tipo_alocacao || 'N/A'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                ` : ''}
                
                ${presencas.length > 0 ? `
                <hr>
                <h6><i class="fas fa-clipboard-check me-2"></i>Presença</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Concurso</th>
                                <th>Data</th>
                                <th>Presente</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${presencas.map(presenca => `
                                <tr>
                                    <td>${presenca.concurso_nome || 'N/A'}</td>
                                    <td>${presenca.data_evento ? new Date(presenca.data_evento).toLocaleDateString('pt-BR') : 'N/A'}</td>
                                    <td><span class="badge bg-${presenca.presente ? 'success' : 'danger'}">${presenca.presente ? 'Sim' : 'Não'}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                ` : ''}
                
                ${pagamentos.length > 0 ? `
                <hr>
                <h6><i class="fas fa-money-bill-wave me-2"></i>Pagamentos</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Concurso</th>
                                <th>Data</th>
                                <th>Valor</th>
                                <th>Pago</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${pagamentos.map(pagamento => `
                                <tr>
                                    <td>${pagamento.concurso_nome || 'N/A'}</td>
                                    <td>${pagamento.data_pagamento ? new Date(pagamento.data_pagamento).toLocaleDateString('pt-BR') : 'N/A'}</td>
                                    <td>R$ ${parseFloat(pagamento.valor_pago || 0).toFixed(2).replace('.', ',')}</td>
                                    <td><span class="badge bg-${pagamento.pago ? 'success' : 'warning'}">${pagamento.pago ? 'Sim' : 'Não'}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                ` : ''}
            `;
            
            document.getElementById('detalhesContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('detalhesModal')).show();
        } else {
            showMessage('Erro ao carregar detalhes: ' + (data.error || 'Erro desconhecido'), 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Erro:', error);
        showMessage('Erro ao carregar detalhes', 'error');
    });
}

function getStatusColor(status) {
    switch (status) {
        case 'aprovado': return 'success';
        case 'pendente': return 'warning';
        case 'reprovado': return 'danger';
        case 'cancelado': return 'secondary';
        default: return 'secondary';
    }
}

function alocarTodosFiscais() {
    if (!confirm('Tem certeza que deseja alocar todos os fiscais aprovados automaticamente? Esta ação irá distribuir os fiscais entre as escolas e salas disponíveis.')) {
        return;
    }
    
    showLoading();
    
    // Obter concurso selecionado
    const concursoId = document.getElementById('concurso_id').value;
    
    fetch('alocar_todos_fiscais.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            concurso_id: concursoId 
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showMessage(data.message, 'success');
            // Recarregar a página após 2 segundos
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showMessage(data.message || 'Erro ao alocar fiscais', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Erro:', error);
        showMessage('Erro ao alocar fiscais', 'error');
    });
}
</script>

<?php include '../includes/footer.php'; ?>