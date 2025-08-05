<?php
require_once '../config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

$db = getDB();
$concursos = [];
$mensagem = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $concurso_id = (int)($_POST['concurso_id'] ?? 0);
    
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        switch ($acao) {
            case 'ativar':
                $stmt = $db->prepare("UPDATE concursos SET status = 'ativo' WHERE id = ?");
                $stmt->execute([$concurso_id]);
                $mensagem = 'Concurso ativado com sucesso!';
                break;
                
            case 'inativar':
                $stmt = $db->prepare("UPDATE concursos SET status = 'inativo' WHERE id = ?");
                $stmt->execute([$concurso_id]);
                $mensagem = 'Concurso inativado com sucesso!';
                break;
                
            case 'finalizar':
                $stmt = $db->prepare("UPDATE concursos SET status = 'finalizado' WHERE id = ?");
                $stmt->execute([$concurso_id]);
                $mensagem = 'Concurso finalizado com sucesso!';
                break;
                
            case 'excluir':
                // Verificar se há fiscais cadastrados
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM fiscais WHERE concurso_id = ?");
                $stmt->execute([$concurso_id]);
                $total_fiscais = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                if ($total_fiscais > 0) {
                    $mensagem = 'Não é possível excluir um concurso que possui fiscais cadastrados.';
                } else {
                    $stmt = $db->prepare("DELETE FROM concursos WHERE id = ?");
                    $stmt->execute([$concurso_id]);
                    $mensagem = 'Concurso excluído com sucesso!';
                }
                break;
        }
        
        logActivity("Ação '$acao' executada no concurso ID: $concurso_id", 'INFO');
    }
}

// Buscar concursos
try {
    $stmt = $db->query("
        SELECT 
            c.*,
            COUNT(f.id) as fiscais_cadastrados,
            (c.vagas_disponiveis - COUNT(f.id)) as vagas_restantes
        FROM concursos c
        LEFT JOIN fiscais f ON c.id = f.concurso_id AND f.status = 'aprovado'
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar concursos: ' . $e->getMessage(), 'ERROR');
    $mensagem = 'Erro ao carregar concursos.';
}

$pageTitle = 'Gerenciar Concursos';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-clipboard-list me-2"></i>
                Gerenciar Concursos
            </h1>
            <a href="novo_concurso.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>
                Novo Concurso
            </a>
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

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Lista de Concursos
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($concursos)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="concursosTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Órgão</th>
                                <th>Cidade/UF</th>
                                <th>Data</th>
                                <th>Vagas</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($concursos as $concurso): ?>
                            <tr>
                                <td>#<?= $concurso['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($concurso['titulo']) ?> <?= htmlspecialchars($concurso['numero_concurso']) ?>/<?= htmlspecialchars($concurso['ano_concurso']) ?> da <?= htmlspecialchars($concurso['orgao']) ?> de <?= htmlspecialchars($concurso['cidade']) ?>/<?= htmlspecialchars($concurso['estado']) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        R$ <?= number_format($concurso['valor_pagamento'], 2, ',', '.') ?>
                                    </small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($concurso['orgao']) ?>
                                    <?php if ($concurso['logo_orgao'] && file_exists($concurso['logo_orgao'])): ?>
                                    <br>
                                    <img src="../<?= htmlspecialchars($concurso['logo_orgao']) ?>" 
                                         alt="Logo" style="height: 20px;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($concurso['cidade']) ?>/<?= htmlspecialchars($concurso['estado']) ?>
                                </td>
                                <td>
                                    <?php
                                    // Substituir célula de vagas e progresso por informações de datas e links
                                    ?>
                                    <?php if (!empty($concurso['logo_orgao']) && file_exists('../' . $concurso['logo_orgao'])): ?>
                                        <img src="../<?= htmlspecialchars($concurso['logo_orgao']) ?>" alt="Logo" style="height: 40px;">
                                        <br>
                                    <?php endif; ?>
                                    <strong>Treinamento:</strong> <?= !empty($concurso['data_treinamento']) ? date('d/m/Y', strtotime($concurso['data_treinamento'])) : 'N/A' ?>
                                    <?php if (!empty($concurso['hora_treinamento'])): ?> às <?= htmlspecialchars($concurso['hora_treinamento']) ?><?php endif; ?><br>
                                    <strong>Prova:</strong> <?= !empty($concurso['data_prova']) ? date('d/m/Y', strtotime($concurso['data_prova'])) : 'N/A' ?><br>
                                    <?php if ($concurso['tipo_treinamento'] === 'online' && !empty($concurso['link_treinamento'])): ?>
                                        <strong>Link Treinamento:</strong> <a href="<?= htmlspecialchars($concurso['link_treinamento']) ?>" target="_blank">Acessar</a><br>
                                    <?php elseif ($concurso['tipo_treinamento'] === 'presencial' && !empty($concurso['local_treinamento'])): ?>
                                        <strong>Local Treinamento:</strong> <?= htmlspecialchars($concurso['local_treinamento']) ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($concurso['link_material_fiscal'])): ?>
                                        <strong>Material/Manual:</strong> <a href="<?= htmlspecialchars($concurso['link_material_fiscal']) ?>" target="_blank">Acessar</a><br>
                                    <?php endif; ?>
                                    <a href="consulta_local_fiscal.php?concurso_id=<?= $concurso['id'] ?>" class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="fas fa-search"></i> Verificar Local do Fiscal
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'ativo' => 'success',
                                        'inativo' => 'secondary',
                                        'finalizado' => 'info'
                                    ];
                                    $status_text = [
                                        'ativo' => 'Ativo',
                                        'inativo' => 'Inativo',
                                        'finalizado' => 'Finalizado'
                                    ];
                                    ?>
                                    <span class="badge bg-<?= $status_class[$concurso['status']] ?>">
                                        <?= $status_text[$concurso['status']] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary" 
                                                data-bs-toggle="modal" data-bs-target="#detalhesModal<?= $concurso['id'] ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="editar_concurso.php?id=<?= $concurso['id'] ?>" 
                                           class="btn btn-outline-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-info" 
                                                onclick="toggleStatus(<?= $concurso['id'] ?>, '<?= $concurso['status'] ?>')">
                                            <i class="fas fa-toggle-on"></i>
                                        </button>
                                        <?php if ($concurso['fiscais_cadastrados'] == 0): ?>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="excluirConcurso(<?= $concurso['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Modal de Detalhes -->
                            <div class="modal fade" id="detalhesModal<?= $concurso['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-clipboard-list me-2"></i>
                                                Detalhes do Concurso
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6><i class="fas fa-info-circle me-2"></i>Informações Gerais</h6>
                                                    <ul class="list-unstyled">
                                                        <li><strong>Título:</strong> <?= htmlspecialchars($concurso['titulo']) ?></li>
                                                        <li><strong>Órgão:</strong> <?= htmlspecialchars($concurso['orgao']) ?></li>
                                                        <li><strong>Cidade:</strong> <?= htmlspecialchars($concurso['cidade']) ?> - <?= htmlspecialchars($concurso['estado']) ?></li>
                                                        <li><strong>Data:</strong> <?= date('d/m/Y', strtotime($concurso['data_prova'])) ?></li>
                                                        <li><strong>Horário:</strong> <?= $concurso['horario_inicio'] ?>h às <?= $concurso['horario_fim'] ?>h</li>
                                                        <li><strong>Pagamento:</strong> R$ <?= number_format($concurso['valor_pagamento'], 2, ',', '.') ?></li>
                                                        <!-- Novas informações de treinamento -->
                                                        <?php if (!empty($concurso['data_treinamento'])): ?>
                                                        <li><strong>Data do Treinamento:</strong> <?= date('d/m/Y', strtotime($concurso['data_treinamento'])) ?></li>
                                                        <?php endif; ?>
                                                        <?php if (!empty($concurso['hora_treinamento'])): ?>
                                                        <li><strong>Horário do Treinamento:</strong> <?= htmlspecialchars($concurso['hora_treinamento']) ?></li>
                                                        <?php endif; ?>
                                                        <?php if (!empty($concurso['tipo_treinamento'])): ?>
                                                        <li><strong>Tipo de Treinamento:</strong> <?= ucfirst($concurso['tipo_treinamento']) ?></li>
                                                        <?php endif; ?>
                                                        <?php if ($concurso['tipo_treinamento'] === 'online' && !empty($concurso['link_treinamento'])): ?>
                                                        <li><strong>Link do Treinamento:</strong> <a href="<?= htmlspecialchars($concurso['link_treinamento']) ?>" target="_blank">Acessar</a></li>
                                                        <?php endif; ?>
                                                        <?php if ($concurso['tipo_treinamento'] === 'presencial' && !empty($concurso['local_treinamento'])): ?>
                                                        <li><strong>Local do Treinamento:</strong> <?= htmlspecialchars($concurso['local_treinamento']) ?></li>
                                                        <?php endif; ?>
                                                        <?php if (!empty($concurso['link_material_fiscal'])): ?>
                                                        <li><strong>Material/Manual do Fiscal:</strong> <a href="<?= htmlspecialchars($concurso['link_material_fiscal']) ?>" target="_blank">Acessar</a></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6><i class="fas fa-users me-2"></i>Estatísticas</h6>
                                                    <ul class="list-unstyled">
                                                        <li><strong>Fiscais Cadastrados:</strong> <?= $concurso['fiscais_cadastrados'] ?></li>
                                                        <li><strong>Status:</strong> 
                                                            <span class="badge bg-<?= $status_class[$concurso['status']] ?>">
                                                                <?= $status_text[$concurso['status']] ?>
                                                            </span>
                                                        </li>
                                                        <li><strong>Criado em:</strong> <?= date('d/m/Y H:i', strtotime($concurso['created_at'])) ?></li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <?php if ($concurso['descricao']): ?>
                                            <hr>
                                            <h6><i class="fas fa-align-left me-2"></i>Descrição</h6>
                                            <p class="text-muted"><?= nl2br(htmlspecialchars($concurso['descricao'])) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer">
                                            <a href="fiscais.php?concurso=<?= $concurso['id'] ?>" class="btn btn-primary">
                                                <i class="fas fa-users me-1"></i>
                                                Ver Fiscais
                                            </a>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">Nenhum concurso cadastrado</h5>
                    <p class="text-muted">Clique em "Novo Concurso" para começar.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Formulário para ações -->
<form id="acaoForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="acao" id="acao">
    <input type="hidden" name="concurso_id" id="concurso_id">
</form>

<script>
function toggleStatus(concursoId, statusAtual) {
    let novaAcao = '';
    let mensagem = '';
    
    switch (statusAtual) {
        case 'ativo':
            novaAcao = 'inativar';
            mensagem = 'Deseja inativar este concurso?';
            break;
        case 'inativo':
            novaAcao = 'ativar';
            mensagem = 'Deseja ativar este concurso?';
            break;
        case 'finalizado':
            novaAcao = 'ativar';
            mensagem = 'Deseja reativar este concurso?';
            break;
    }
    
    if (confirm(mensagem)) {
        document.getElementById('acao').value = novaAcao;
        document.getElementById('concurso_id').value = concursoId;
        document.getElementById('acaoForm').submit();
    }
}

function excluirConcurso(concursoId) {
    if (confirm('Tem certeza que deseja excluir este concurso? Esta ação não pode ser desfeita.')) {
        document.getElementById('acao').value = 'excluir';
        document.getElementById('concurso_id').value = concursoId;
        document.getElementById('acaoForm').submit();
    }
}

// Inicializar DataTable
$(document).ready(function() {
    $('#concursosTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
        },
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true
    });
});
</script>

<?php include '../includes/footer.php'; ?>