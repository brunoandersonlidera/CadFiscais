<?php
require_once '../config.php';

// Verificar se tem permissão para pagamentos
if (!isLoggedIn() || !temPermissaoPagamentos()) {
    redirect('../login.php');
}

$db = getDB();

// Buscar concursos ativos
$concursos = [];
try {
    $stmt = $db->query("SELECT id, titulo, numero_concurso, ano_concurso, orgao, cidade, estado FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC");
    $concursos = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar concursos: ' . $e->getMessage(), 'ERROR');
}

// Buscar fiscais aprovados
$fiscais = [];
try {
    $stmt = $db->query("SELECT id, nome, cpf FROM fiscais WHERE status = 'aprovado' ORDER BY nome");
    $fiscais = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar fiscais: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Novo Pagamento';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-money-bill-wave me-2"></i>
                Novo Pagamento
            </h1>
            <a href="lista_pagamentos.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Voltar
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-plus me-2"></i>
                    Cadastrar Novo Pagamento
                </h5>
            </div>
            <div class="card-body">
                <form action="salvar_pagamento.php" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="concurso_id" class="form-label">Concurso *</label>
                                <select class="form-select" id="concurso_id" name="concurso_id" required>
                                    <option value="">Selecione um concurso</option>
                                    <?php foreach ($concursos as $concurso): ?>
                                    <option value="<?= $concurso['id'] ?>"><?= htmlspecialchars($concurso['titulo']) ?> <?= htmlspecialchars($concurso['numero_concurso']) ?>/<?= htmlspecialchars($concurso['ano_concurso']) ?> da <?= htmlspecialchars($concurso['orgao']) ?> de <?= htmlspecialchars($concurso['cidade']) ?>/<?= htmlspecialchars($concurso['estado']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fiscal_id" class="form-label">Fiscal *</label>
                                <select class="form-select" id="fiscal_id" name="fiscal_id" required>
                                    <option value="">Selecione um fiscal</option>
                                    <?php foreach ($fiscais as $fiscal): ?>
                                    <option value="<?= $fiscal['id'] ?>"><?= htmlspecialchars($fiscal['nome']) ?> - <?= htmlspecialchars($fiscal['cpf']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="valor" class="form-label">Valor (R$) *</label>
                                <input type="number" class="form-control" id="valor" name="valor" step="0.01" min="0" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="forma_pagamento" class="form-label">Forma de Pagamento *</label>
                                <select class="form-select" id="forma_pagamento" name="forma_pagamento" required>
                                    <option value="">Selecione</option>
                                    <option value="dinheiro">Dinheiro</option>
                                    <option value="pix">PIX</option>
                                    <option value="transferencia">Transferência</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="data_pagamento" class="form-label">Data do Pagamento *</label>
                                <input type="date" class="form-control" id="data_pagamento" name="data_pagamento" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="observacoes" class="form-label">Observações</label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status_pagamento" class="form-label">Status do Pagamento *</label>
                                <select class="form-select" id="status_pagamento" name="status_pagamento" required>
                                    <option value="">Selecione</option>
                                    <option value="pago">Pago</option>
                                    <option value="pendente">Pendente</option>
                                    <option value="cancelado">Cancelado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>
                            Salvar Pagamento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Definir data atual como padrão
    document.getElementById('data_pagamento').value = new Date().toISOString().split('T')[0];
    
    // Validação do formulário
    const form = document.querySelector('form');
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
    
    // Filtro de fiscais por concurso
    const concursoSelect = document.getElementById('concurso_id');
    const fiscalSelect = document.getElementById('fiscal_id');
    
    concursoSelect.addEventListener('change', function() {
        const concursoId = this.value;
        if (concursoId) {
            // Aqui você pode implementar uma chamada AJAX para filtrar fiscais por concurso
            // Por enquanto, vamos apenas habilitar/desabilitar o select de fiscais
            fiscalSelect.disabled = false;
        } else {
            fiscalSelect.disabled = true;
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?> 