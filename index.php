<?php
// Habilitar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Buscar concursos ativos
$db = getDB();
$concursos = [];
$total_fiscais = 0;
$total_concursos = 0;

if ($db) {
    try {
        // Consulta mais simples e segura
        $stmt = $db->query("
            SELECT 
                c.*,
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
        $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar estatísticas gerais
        $stmt = $db->query("SELECT COUNT(*) as total FROM fiscais WHERE status = 'aprovado'");
        $total_fiscais = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM concursos WHERE status = 'ativo'");
        $total_concursos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
    } catch (Exception $e) {
        logActivity('Erro ao buscar concursos: ' . $e->getMessage(), 'ERROR');
        // Fallback para CSV
        $concursos = getConcursosAtivosFromCSV();
        $total_concursos = count($concursos);
    }
} else {
    // Usar CSV como fallback
    $concursos = getConcursosAtivosFromCSV();
    $total_concursos = count($concursos);
}

$pageTitle = 'Início';
include 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-home me-2"></i>
                Bem-vindo ao Sistema de Fiscais
            </h1>
            <div class="text-end">
                <img src="logos/instituto.png" alt="IDH" style="height: 60px; margin-right: 15px;">
                <h5 class="mb-0 text-muted">Instituto Dignidade Humana</h5>
            </div>
        </div>
    </div>
</div>

<?php if (isLoggedIn()): ?>
<!-- Cards de Estatísticas - Apenas para usuários logados -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><?= $total_concursos ?? 0 ?></h3>
                    <p class="mb-0">Concursos Ativos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(45deg, #27ae60, #2ecc71);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0"><?= $total_fiscais ?? 0 ?></h3>
                    <p class="mb-0">Fiscais Cadastrados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(45deg, #007bff, #0056b3);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0">📚</h3>
                    <p class="mb-0">Presença Treinamento</p>
                </div>
                <div class="icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stats-card" style="background: linear-gradient(45deg, #dc3545, #c82333);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-0">📝</h3>
                    <p class="mb-0">Presença Prova</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-alt"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Menu de Acesso Rápido - Apenas para usuários logados -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Acesso Rápido
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="presenca_treinamento.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 100px;">
                            <i class="fas fa-graduation-cap fa-2x mb-2"></i>
                            <strong>Presença no Treinamento</strong>
                            <small class="text-muted">Controle de presença</small>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="presenca_prova.php" class="btn btn-outline-danger w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 100px;">
                            <i class="fas fa-file-alt fa-2x mb-2"></i>
                            <strong>Presença na Prova</strong>
                            <small class="text-muted">Controle de presença</small>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="admin/lista_pagamentos.php" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 100px;">
                            <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                            <strong>Controle de Pagamentos</strong>
                            <small class="text-muted">Gestão financeira</small>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="admin/dashboard.php" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 100px;">
                            <i class="fas fa-cogs fa-2x mb-2"></i>
                            <strong>Painel Administrativo</strong>
                            <small class="text-muted">Configurações</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Menu Público - Certificados -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-certificate me-2"></i>
                    Certificados - Acesso Público
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <a href="certificado_treinamento.php" class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 120px;">
                            <i class="fas fa-download fa-3x mb-3"></i>
                            <strong>Gerar Certificado</strong>
                            <small class="text-muted">Para fiscais que participaram do treinamento</small>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="validar_certificado.php" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 120px;">
                            <i class="fas fa-check-circle fa-3x mb-3"></i>
                            <strong>Validar Certificado</strong>
                            <small class="text-muted">Verificar autenticidade de certificados</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Concursos Ativos -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-check me-2"></i>
                    Concursos Ativos - Cadastro de Fiscais
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($concursos)): ?>
                <div class="row">
                    <?php foreach ($concursos as $concurso): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <div class="d-flex align-items-center">
                                    <?php if ($concurso['logo_orgao'] && file_exists($concurso['logo_orgao'])): ?>
                                    <img src="<?= htmlspecialchars($concurso['logo_orgao']) ?>" 
                                         alt="Logo" class="me-2" style="height: 30px;">
                                    <?php endif; ?>
                                    <h6 class="mb-0"><?= htmlspecialchars($concurso['orgao']) ?> de <?= htmlspecialchars($concurso['cidade']) ?> - <?= htmlspecialchars($concurso['estado']) ?> </h6>
                                </div>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title text-primary">
                                    <?= htmlspecialchars($concurso['titulo']) ?> <?= htmlspecialchars($concurso['numero_concurso']) ?>/<?= htmlspecialchars($concurso['ano_concurso']) ?> da <?= htmlspecialchars($concurso['orgao']) ?> de <?= htmlspecialchars($concurso['cidade']) ?> - <?= htmlspecialchars($concurso['estado']) ?>
                                </h6>
                                <p class="card-text text-muted small">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?= htmlspecialchars($concurso['cidade']) ?> - <?= htmlspecialchars($concurso['estado']) ?>
                                </p>
                                <div class="mb-2">
                                    <strong>Treinamento:</strong> <?= !empty($concurso['data_treinamento']) ? date('d/m/Y', strtotime($concurso['data_treinamento'])) : 'N/A' ?>
                                    <?php if (!empty($concurso['hora_treinamento'])): ?> às <?= htmlspecialchars($concurso['hora_treinamento']) ?><?php endif; ?><br>
                                    <strong>Prova:</strong> <?= !empty($concurso['data_prova']) ? date('d/m/Y', strtotime($concurso['data_prova'])) : 'N/A' ?>
                                    <?php if (!empty($concurso['horario_inicio'])): ?> às <?= htmlspecialchars($concurso['horario_inicio']) ?><?php endif; ?><br>
                                    <?php if ($concurso['tipo_treinamento'] === 'online' && !empty($concurso['link_treinamento'])): ?>
                                        <strong>Link Treinamento:</strong> <a href="<?= htmlspecialchars($concurso['link_treinamento']) ?>" target="_blank">Acessar</a><br>
                                    <?php elseif ($concurso['tipo_treinamento'] === 'presencial' && !empty($concurso['local_treinamento'])): ?>
                                        <strong>Local Treinamento:</strong> <?= htmlspecialchars($concurso['local_treinamento']) ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($concurso['link_material_fiscal'])): ?>
                                        <strong>Material/Manual:</strong> <a href="<?= htmlspecialchars($concurso['link_material_fiscal']) ?>" target="_blank">Acessar</a><br>
                                    <?php endif; ?>
                                </div>
                                <div class="d-grid mb-2">
                                    <a href="admin/consulta_local_fiscal.php?concurso_id=<?= $concurso['id'] ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-search me-1"></i> Verificar Local do Fiscal
                                    </a>
                                </div>
                                <?php if ($concurso['vagas_restantes'] > 0): ?>
                                <div class="d-grid">
                                    <a href="cadastro.php?concurso=<?= $concurso['id'] ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-user-plus me-1"></i>
                                        Inscrever-se
                                    </a>
                                </div>
                                <?php else: ?>
                                <div class="d-grid">
                                    <button class="btn btn-secondary btn-sm" disabled>
                                        <i class="fas fa-times me-1"></i>
                                        Vagas Esgotadas
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">Nenhum concurso ativo no momento</h5>
                    <p class="text-muted">Novos concursos serão disponibilizados em breve.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Informações sobre a IDH -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Sobre o Instituto Dignidade Humana (IDH)
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h6><i class="fas fa-building me-2"></i>Nossa Empresa</h6>
                        <p class="text-muted">
                            O Instituto Dignidade Humana (IDH) é especializado em realizar concursos públicos 
                            e seletivos simplificados em todo o Brasil. Nossa missão é garantir processos 
                            transparentes e eficientes para a seleção de fiscais de prova.
                        </p>
                        
                        <h6><i class="fas fa-map-marker-alt me-2"></i>Atuação</h6>
                        <p class="text-muted">
                            Atuamos em múltiplas cidades e estados, oferecendo oportunidades para fiscais 
                            em diversos concursos públicos e seletivos simplificados.
                        </p>
                    </div>
                    <div class="col-md-4 text-center">
                        <img src="logos/instituto.png" alt="IDH" class="img-fluid" style="max-height: 100px;">
                        <p class="mt-2 text-muted small">Instituto Dignidade Humana</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ferramentas Administrativas -->
<?php if (isLoggedIn()): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-tools me-2"></i>
                    Ferramentas Administrativas
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="d-grid">
                            <a href="admin/relatorios.php" class="btn btn-outline-primary">
                                <i class="fas fa-chart-bar me-2"></i>
                                📊 Relatórios
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-grid">
                            <a href="presenca_mobile.php" class="btn btn-outline-success">
                                <i class="fas fa-mobile-alt me-2"></i>
                                📱 Controle de Presença
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-grid">
                            <a href="admin/lista_pagamentos.php" class="btn btn-outline-warning">
                                <i class="fas fa-money-bill-wave me-2"></i>
                                💰 Controle de Pagamentos
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="d-grid">
                            <a href="admin/dashboard.php" class="btn btn-outline-info">
                                <i class="fas fa-cog me-2"></i>
                                ⚙️ Painel Admin
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
