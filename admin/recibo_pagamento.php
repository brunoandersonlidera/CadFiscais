<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$pagamento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();

if (!$pagamento_id) {
    showMessage('ID do pagamento não fornecido', 'error');
    redirect('lista_pagamentos.php');
}

try {
    $stmt = $db->prepare("
        SELECT p.*, f.nome as fiscal_nome, f.cpf as fiscal_cpf, f.celular as fiscal_celular,
               c.titulo as concurso_titulo, u.nome as usuario_nome
        FROM pagamentos p
        LEFT JOIN fiscais f ON p.fiscal_id = f.id
        LEFT JOIN concursos c ON p.concurso_id = c.id
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$pagamento_id]);
    $pagamento = $stmt->fetch();
    
    if (!$pagamento) {
        showMessage('Pagamento não encontrado', 'error');
        redirect('lista_pagamentos.php');
    }
} catch (Exception $e) {
    logActivity('Erro ao buscar pagamento: ' . $e->getMessage(), 'ERROR');
    showMessage('Erro ao buscar pagamento', 'error');
    redirect('lista_pagamentos.php');
}

$pageTitle = 'Recibo de Pagamento';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-receipt me-2"></i>
                Recibo de Pagamento
            </h1>
            <div>
                <button onclick="imprimirRecibo()" class="btn btn-primary">
                    <i class="fas fa-print me-2"></i>
                    Imprimir
                </button>
                <a href="lista_pagamentos.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Voltar
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recibo -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="text-primary mb-4">
                            <i class="fas fa-receipt me-2"></i>
                            RECIBO DE PAGAMENTO
                        </h4>
                    </div>
                    <div class="col-md-4 text-end">
                        <p class="mb-1"><strong>Nº do Recibo:</strong> <?= str_pad($pagamento['id'], 6, '0', STR_PAD_LEFT) ?></p>
                        <p class="mb-1"><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pagamento['data_pagamento'])) ?></p>
                    </div>
                </div>
                
                <hr>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-primary">DADOS DO FISCAL</h6>
                        <p class="mb-1"><strong>Nome:</strong> <?= htmlspecialchars($pagamento['fiscal_nome']) ?></p>
                        <p class="mb-1"><strong>CPF:</strong> <?= formatCPF($pagamento['fiscal_cpf']) ?></p>
                        <p class="mb-1"><strong>Celular:</strong> <?= formatPhone($pagamento['fiscal_celular']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary">DADOS DO PAGAMENTO</h6>
                        <p class="mb-1"><strong>Concurso:</strong> <?= htmlspecialchars($pagamento['concurso_titulo']) ?></p>
                        <p class="mb-1"><strong>Valor:</strong> R$ <?= number_format($pagamento['valor'], 2, ',', '.') ?></p>
                        <p class="mb-1"><strong>Forma de Pagamento:</strong> <?= ucfirst($pagamento['forma_pagamento']) ?></p>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12">
                        <h6 class="text-primary">DETALHES DO PAGAMENTO</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <td width="30%"><strong>Status:</strong></td>
                                        <td>
                                            <span class="badge bg-<?= getStatusPagamentoColor($pagamento['status']) ?>">
                                                <?= ucfirst($pagamento['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Data do Pagamento:</strong></td>
                                        <td><?= date('d/m/Y H:i:s', strtotime($pagamento['data_pagamento'])) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Registrado por:</strong></td>
                                        <td><?= htmlspecialchars($pagamento['usuario_nome']) ?></td>
                                    </tr>
                                    <?php if (!empty($pagamento['observacoes'])): ?>
                                    <tr>
                                        <td><strong>Observações:</strong></td>
                                        <td><?= htmlspecialchars($pagamento['observacoes']) ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="border p-3 bg-light">
                            <h6 class="text-primary mb-3">DECLARAÇÃO</h6>
                            <p class="mb-2">
                                Declaramos que o fiscal <strong><?= htmlspecialchars($pagamento['fiscal_nome']) ?></strong>, 
                                CPF <?= formatCPF($pagamento['fiscal_cpf']) ?>, recebeu o pagamento de 
                                <strong>R$ <?= number_format($pagamento['valor'], 2, ',', '.') ?></strong> 
                                referente aos serviços prestados no concurso 
                                <strong><?= htmlspecialchars($pagamento['concurso_titulo']) ?></strong>.
                            </p>
                            <p class="mb-0">
                                Este recibo é válido como comprovante de pagamento e deve ser mantido em arquivo.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="border-top pt-3">
                            <p class="text-center mb-0">
                                <strong>Assinatura do Fiscal</strong>
                            </p>
                            <div style="height: 80px; border-bottom: 1px solid #ccc; margin-top: 10px;"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border-top pt-3">
                            <p class="text-center mb-0">
                                <strong>Assinatura do Responsável</strong>
                            </p>
                            <div style="height: 80px; border-bottom: 1px solid #ccc; margin-top: 10px;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Informações:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Este recibo deve ser apresentado em caso de dúvidas sobre o pagamento</li>
                                <li>Guarde este documento por pelo menos 5 anos</li>
                                <li>Para questões sobre o pagamento, entre em contato com a administração</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function imprimirRecibo() {
    window.print();
}
</script>

<style>
@media print {
    .btn, .navbar, .footer {
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
}
</style>

<?php 
// Funções auxiliares
function formatCPF($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

function formatPhone($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) === 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
    }
    return $phone;
}

function getStatusPagamentoColor($status) {
    switch ($status) {
        case 'pago': return 'success';
        case 'pendente': return 'warning';
        case 'cancelado': return 'danger';
        default: return 'secondary';
    }
}

include '../includes/footer.php'; 
?> 