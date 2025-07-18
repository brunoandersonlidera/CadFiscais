<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();

// Buscar concursos ativos
$concursos = [];
try {
    $stmt = $db->query("SELECT id, titulo, numero_concurso, ano_concurso, orgao, cidade, estado, nome FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC");
    $concursos = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar concursos: ' . $e->getMessage(), 'ERROR');
}

// Buscar escolas
$escolas = [];
try {
    $stmt = $db->query("SELECT id, nome FROM escolas WHERE status = 'ativo' ORDER BY nome");
    $escolas = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar escolas: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Relatórios';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-chart-bar me-2"></i>
                Relatórios
            </h1>
        </div>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">
                            <?php
                            $stmt = $db->query("SELECT COUNT(*) as total FROM fiscais");
                            $total = $stmt->fetch()['total'];
                            echo $total;
                            ?>
                        </h4>
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
                            <?php
                            $stmt = $db->query("SELECT COUNT(*) as total FROM fiscais WHERE status = 'aprovado'");
                            $aprovados = $stmt->fetch()['total'];
                            echo $aprovados;
                            ?>
                        </h4>
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
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">
                            <?php
                            $stmt = $db->query("SELECT COUNT(*) as total FROM escolas WHERE status = 'ativo'");
                            $escolas_count = $stmt->fetch()['total'];
                            echo $escolas_count;
                            ?>
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
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0">
                            <?php
                            $stmt = $db->query("SELECT COUNT(*) as total FROM alocacoes_fiscais WHERE status = 'ativo'");
                            $alocacoes = $stmt->fetch()['total'];
                            echo $alocacoes;
                            ?>
                        </h4>
                        <p class="mb-0">Alocações Ativas</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-map-marker-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Relatórios de Fiscais -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    Relatórios de Fiscais
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-list me-2"></i>
                                    Lista Completa de Fiscais
                                </h6>
                                <p class="card-text">Relatório com todos os fiscais cadastrados, incluindo dados pessoais e status.</p>
                                <a href="relatorio_fiscais.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-download me-2"></i>
                                    Gerar Relatório
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Fiscais Aprovados
                                </h6>
                                <p class="card-text">Lista de fiscais aprovados para alocação em escolas e salas.</p>
                                <a href="relatorio_fiscais_aprovados.php" class="btn btn-success btn-sm">
                                    <i class="fas fa-download me-2"></i>
                                    Gerar Relatório
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3 border-warning">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-bullhorn me-2 text-warning"></i>
                                    Fiscais Aprovados para Mural
                                </h6>
                                <p class="card-text">Relatório para afixação em mural, sem CPF e telefone.</p>
                                <a href="exportar_pdf_fiscais_aprovados_mural.php" class="btn btn-warning btn-sm">
                                    <i class="fas fa-download me-2"></i>
                                    Gerar PDF para Mural
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    Alocações de Fiscais
                                </h6>
                                <p class="card-text">Relatório detalhado de alocações por escola, sala e fiscal.</p>
                                <a href="relatorio_alocacoes.php" class="btn btn-info btn-sm">
                                    <i class="fas fa-download me-2"></i>
                                    Gerar Relatório
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-clock me-2"></i>
                                    Fiscais por Horário
                                </h6>
                                <p class="card-text">Agrupamento de fiscais por melhor horário para contato.</p>
                                <a href="relatorio_fiscais_horario.php" class="btn btn-warning btn-sm">
                                    <i class="fas fa-download me-2"></i>
                                    Gerar Relatório
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Relatórios de Presença -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-check me-2"></i>
                    Relatórios de Presença
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-calendar-check me-2"></i>
                                    Lista de Presença - Dia da Prova
                                </h6>
                                <p class="card-text">Lista de presença dos fiscais no dia da aplicação da prova.</p>
                                <a href="lista_presenca.php" class="btn btn-success btn-sm">
                                    <i class="fas fa-download me-2"></i>
                                    Gerar Lista
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-graduation-cap me-2"></i>
                                    Lista de Presença - Treinamento
                                </h6>
                                <p class="card-text">Lista de presença para o treinamento dos fiscais.</p>
                                <a href="lista_presenca_treinamento.php" class="btn btn-info btn-sm">
                                    <i class="fas fa-download me-2"></i>
                                    Gerar Lista
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-file-alt me-2"></i>
                                    Ata de Reunião - Treinamento
                                </h6>
                                <p class="card-text">Modelo de ata para reunião de treinamento dos fiscais.</p>
                                <a href="ata_treinamento.php" class="btn btn-warning btn-sm">
                                    <i class="fas fa-download me-2"></i>
                                    Gerar Ata
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Relatório de Comparecimento
                                </h6>
                                <p class="card-text">Estatísticas de comparecimento por escola e sala.</p>
                                <a href="relatorio_comparecimento.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-download me-2"></i>
                                    Gerar Relatório
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Relatórios de Pagamentos -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    Relatórios de Pagamentos
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-list me-2"></i>
                                    Lista de Pagamentos
                                </h6>
                                <p class="card-text">Lista completa de fiscais com valores e status de pagamento.</p>
                                <a href="lista_pagamentos.php" class="btn btn-warning btn-sm">
                                    <i class="fas fa-download me-2"></i>
                                    Gerar Lista
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-receipt me-2"></i>
                                    Recibos de Pagamento
                                </h6>
                                <p class="card-text">Modelo de recibo para pagamento dos fiscais.</p>
                                <a href="recibo_pagamento.php" class="btn btn-success btn-sm">
                                    <i class="fas fa-download me-2"></i>
                                    Gerar Recibo
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-chart-pie me-2"></i>
                                    Resumo Financeiro
                                </h6>
                                <p class="card-text">Resumo dos valores pagos e pendentes por concurso.</p>
                                <a href="resumo_financeiro.php" class="btn btn-info btn-sm">
                                    <i class="fas fa-download me-2"></i>
                                    Gerar Resumo
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-file-invoice me-2"></i>
                                    Planilha de Pagamentos
                                </h6>
                                <p class="card-text">Planilha Excel com dados para controle financeiro.</p>
                                <a href="planilha_pagamentos.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-download me-2"></i>
                                    Baixar Planilha
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Relatórios por Escola -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-school me-2"></i>
                    Relatórios por Escola (Em construção)
                </h5>
            </div>
            <div class="card-body">
                <form action="relatorio_escola.php" method="GET" target="_blank">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="escola_id" class="form-label">Escola</label>
                            <select class="form-select" id="escola_id" name="escola_id" required>
                                <option value="">Selecione uma escola</option>
                                <?php foreach ($escolas as $escola): ?>
                                <option value="<?= $escola['id'] ?>"><?= htmlspecialchars($escola['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="tipo_relatorio" class="form-label">Tipo de Relatório</label>
                            <select class="form-select" id="tipo_relatorio" name="tipo" required>
                                <option value="">Selecione o tipo</option>
                                <option value="fiscais">Fiscais Alocados</option>
                                <option value="presenca">Lista de Presença</option>
                                <option value="salas">Salas e Capacidades</option>
                                <option value="completo">Relatório Completo</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-download me-2"></i>
                                    Gerar Relatório
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Relatórios por Concurso -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Relatórios por Concurso (Em construção)
                </h5>
            </div>
            <div class="card-body">
                <form action="relatorio_concurso.php" method="GET" target="_blank">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="concurso_id" class="form-label">Concurso</label>
                            <select class="form-select" id="concurso_id" name="concurso_id" required>
                                <option value="">Selecione um concurso</option>
                                <?php foreach ($concursos as $concurso): ?>
                                <option value="<?= $concurso['id'] ?>"><?= htmlspecialchars($concurso['titulo']) ?> <?= htmlspecialchars($concurso['numero_concurso']) ?>/<?= htmlspecialchars($concurso['ano_concurso']) ?> da <?= htmlspecialchars($concurso['orgao']) ?> de <?= htmlspecialchars($concurso['cidade']) ?>/<?= htmlspecialchars($concurso['estado']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="tipo_relatorio_concurso" class="form-label">Tipo de Relatório</label>
                            <select class="form-select" id="tipo_relatorio_concurso" name="tipo" required>
                                <option value="">Selecione o tipo</option>
                                <option value="fiscais">Fiscais Cadastrados</option>
                                <option value="alocacoes">Alocações</option>
                                <option value="presenca">Presença</option>
                                <option value="pagamentos">Pagamentos</option>
                                <option value="completo">Relatório Completo</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-secondary">
                                    <i class="fas fa-download me-2"></i>
                                    Gerar Relatório
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validação dos formulários
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showMessage('Por favor, preencha todos os campos obrigatórios', 'error');
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?> 