<?php
require_once '../config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

$db = getDB();
$stats = [];

try {
    // Estatísticas gerais
    $stmt = $db->query("SELECT COUNT(*) as total FROM fiscais WHERE status = 'ativo'");
    $stats['total_fiscais'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM concursos WHERE status = 'ativo'");
    $stats['concursos_ativos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM concursos");
    $stats['total_concursos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Fiscais por concurso (consulta corrigida)
    $stmt = $db->query("
        SELECT 
            c.id,
            c.titulo,
            c.orgao,
            c.data_prova,
            c.vagas_disponiveis,
            COALESCE(f.fiscais_cadastrados, 0) as fiscais_cadastrados,
            (c.vagas_disponiveis - COALESCE(f.fiscais_cadastrados, 0)) as vagas_restantes
        FROM concursos c
        LEFT JOIN (
            SELECT 
                concurso_id,
                COUNT(*) as fiscais_cadastrados
            FROM fiscais 
            WHERE status = 'ativo'
            GROUP BY concurso_id
        ) f ON c.id = f.concurso_id
        WHERE c.status = 'ativo'
        ORDER BY c.data_prova ASC
    ");
    $stats['concursos_detalhados'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fiscais por faixa etária
    $stmt = $db->query("
        SELECT 
            CASE 
                WHEN idade < 25 THEN '18-24'
                WHEN idade < 35 THEN '25-34'
                WHEN idade < 45 THEN '35-44'
                WHEN idade < 55 THEN '45-54'
                ELSE '55+'
            END as faixa_etaria,
            COUNT(*) as quantidade
        FROM fiscais 
        WHERE status = 'ativo'
        GROUP BY faixa_etaria
        ORDER BY faixa_etaria
    ");
    $stats['faixa_etaria'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Últimos cadastros
    $stmt = $db->query("
        SELECT 
            f.*,
            c.titulo as concurso_titulo,
            c.orgao as concurso_orgao
        FROM fiscais f
        JOIN concursos c ON f.concurso_id = c.id
        WHERE f.status = 'ativo'
        ORDER BY f.created_at DESC
        LIMIT 10
    ");
    $stats['ultimos_cadastros'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Status do cadastro
    $cadastro_aberto = getConfig('cadastro_aberto', '1') == '1';
    
} catch (Exception $e) {
    logActivity('Erro ao buscar estatísticas: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Dashboard';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-tachometer-alt me-2"></i>
                Dashboard Administrativo
            </h1>
            <div class="text-end">
                <img src="../logos/instituto.png" alt="IDH" style="height: 50px;">
                <p class="mb-0 text-muted small">Instituto Dignidade Humana</p>
            </div>
        </div>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><?= $stats['total_fiscais'] ?? 0 ?></h3>
                    <p class="mb-0">Fiscais Ativos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(45deg, #27ae60, #2ecc71);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><?= $stats['concursos_ativos'] ?? 0 ?></h3>
                    <p class="mb-0">Concursos Ativos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(45deg, #f39c12, #e67e22);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><?= $stats['total_concursos'] ?? 0 ?></h3>
                    <p class="mb-0">Total de Concursos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(45deg, #9b59b6, #8e44ad);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><?= $cadastro_aberto ? 'Aberto' : 'Fechado' ?></h3>
                    <p class="mb-0">Status Cadastro</p>
                </div>
                <div class="icon">
                    <i class="fas fa-toggle-<?= $cadastro_aberto ? 'on' : 'off' ?>"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Controle de Cadastro -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-cog me-2"></i>
                    Controle de Cadastro
                </h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6>Status do Cadastro de Fiscais</h6>
                        <p class="text-muted mb-0">
                            <?= $cadastro_aberto ? 'O cadastro está aberto e os fiscais podem se inscrever.' : 'O cadastro está fechado. Os fiscais não podem se inscrever.' ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" class="btn btn-<?= $cadastro_aberto ? 'warning' : 'success' ?>" 
                                onclick="toggleCadastro()">
                            <i class="fas fa-toggle-<?= $cadastro_aberto ? 'on' : 'off' ?> me-1"></i>
                            <?= $cadastro_aberto ? 'Fechar Cadastro' : 'Abrir Cadastro' ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Concursos Ativos -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Concursos Ativos
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['concursos_detalhados'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Concurso</th>
                                <th>Órgão</th>
                                <th>Data</th>
                                <th>Fiscais</th>
                                <th>Progresso</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['concursos_detalhados'] as $concurso): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($concurso['titulo']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($concurso['orgao']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($concurso['orgao']) ?></td>
                                <td><?= date('d/m/Y', strtotime($concurso['data_prova'])) ?></td>
                                <td>
                                    <?= $concurso['fiscais_cadastrados'] ?>/<?= $concurso['vagas_disponiveis'] ?>
                                    <br>
                                    <small class="text-muted"><?= $concurso['vagas_restantes'] ?> restantes</small>
                                </td>
                                <td>
                                    <div class="progress" style="height: 8px;">
                                        <?php 
                                        $percentual = $concurso['vagas_disponiveis'] > 0 
                                            ? (($concurso['fiscais_cadastrados'] / $concurso['vagas_disponiveis']) * 100) 
                                            : 0;
                                        ?>
                                        <div class="progress-bar <?= $percentual >= 90 ? 'bg-danger' : ($percentual >= 70 ? 'bg-warning' : 'bg-success') ?>" 
                                             style="width: <?= min($percentual, 100) ?>%"></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="fiscais.php?concurso=<?= $concurso['id'] ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-users"></i>
                                        </a>
                                        <a href="concursos.php" class="btn btn-outline-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-clipboard-list text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">Nenhum concurso ativo</h5>
                    <p class="text-muted">Crie um novo concurso para começar.</p>
                    <a href="novo_concurso.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        Novo Concurso
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos e Estatísticas -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Distribuição por Idade
                </h5>
            </div>
            <div class="card-body">
                <canvas id="idadeChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Últimos Cadastros
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['ultimos_cadastros'])): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($stats['ultimos_cadastros'] as $fiscal): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($fiscal['nome']) ?></h6>
                            <small class="text-muted">
                                <?= htmlspecialchars($fiscal['concurso_orgao']) ?> - 
                                <?= date('d/m/Y H:i', strtotime($fiscal['created_at'])) ?>
                            </small>
                        </div>
                        <span class="badge bg-primary rounded-pill">
                            <?= $fiscal['idade'] ?> anos
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-3">
                    <i class="fas fa-users text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2">Nenhum fiscal cadastrado ainda.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Ações Rápidas
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="novo_concurso.php" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>
                            Novo Concurso
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="fiscais.php" class="btn btn-success w-100">
                            <i class="fas fa-users me-2"></i>
                            Ver Fiscais
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="export.php" class="btn btn-info w-100">
                            <i class="fas fa-download me-2"></i>
                            Exportar Dados
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="relatorios.php" class="btn btn-warning w-100">
                            <i class="fas fa-chart-bar me-2"></i>
                            Relatórios
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Gráfico de distribuição por idade
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('idadeChart').getContext('2d');
    
    const data = <?= json_encode($stats['faixa_etaria'] ?? []) ?>;
    const labels = data.map(item => item.faixa_etaria);
    const values = data.map(item => parseInt(item.quantidade));
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: [
                    '#3498db',
                    '#2ecc71',
                    '#f39c12',
                    '#e74c3c',
                    '#9b59b6'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        }
    });
});

// Função para alternar status do cadastro
function toggleCadastro() {
    const isOpen = <?= $cadastro_aberto ? 'true' : 'false' ?>;
    const newStatus = isOpen ? 'fechar' : 'abrir';
    
    if (confirm(`Tem certeza que deseja ${newStatus} o cadastro de fiscais?`)) {
        fetch('toggle_cadastro.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro ao alterar status do cadastro');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao alterar status do cadastro');
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?> 