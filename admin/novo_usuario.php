<?php
require_once '../config.php';

// Verificar se é admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$db = getDB();
$tipos_usuario = [];

try {
    $stmt = $db->query("SELECT * FROM tipos_usuario ORDER BY nome");
    $tipos_usuario = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar tipos de usuário: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Novo Usuário';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-user-plus me-2"></i>
                Novo Usuário
            </h1>
            <div>
                <a href="usuarios.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Voltar
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user me-2"></i>
                    Dados do Usuário
                </h5>
            </div>
            <div class="card-body">
                <form id="formUsuario" method="POST" action="salvar_usuario.php">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cpf" class="form-label">CPF *</label>
                                <input type="text" class="form-control" id="cpf" name="cpf" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tipo_usuario_id" class="form-label">Tipo de Usuário *</label>
                                <select class="form-select" id="tipo_usuario_id" name="tipo_usuario_id" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($tipos_usuario as $tipo): ?>
                                    <option value="<?= $tipo['id'] ?>"><?= htmlspecialchars($tipo['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha *</label>
                                <input type="password" class="form-control" id="senha" name="senha" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="confirmar_senha" class="form-label">Confirmar Senha *</label>
                                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="ativo">Ativo</option>
                                    <option value="inativo">Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>
                            Salvar Usuário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Máscara para CPF
    const cpfInput = document.getElementById('cpf');
    cpfInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length <= 11) {
            value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            e.target.value = value;
        }
    });
    
    // Validação de senha
    const form = document.getElementById('formUsuario');
    form.addEventListener('submit', function(e) {
        const senha = document.getElementById('senha').value;
        const confirmarSenha = document.getElementById('confirmar_senha').value;
        
        if (senha !== confirmarSenha) {
            e.preventDefault();
            showMessage('As senhas não coincidem', 'error');
            return false;
        }
        
        if (senha.length < 6) {
            e.preventDefault();
            showMessage('A senha deve ter pelo menos 6 caracteres', 'error');
            return false;
        }
    });
    
    // Validação de CPF
    cpfInput.addEventListener('blur', function() {
        const cpf = this.value.replace(/\D/g, '');
        if (cpf.length === 11 && !validarCPF(cpf)) {
            showMessage('CPF inválido', 'error');
            this.focus();
        }
    });
});

function validarCPF(cpf) {
    if (cpf.length !== 11) return false;
    
    // Verificar se todos os dígitos são iguais
    if (/^(\d)\1{10}$/.test(cpf)) return false;
    
    // Calcular primeiro dígito verificador
    let soma = 0;
    for (let i = 0; i < 9; i++) {
        soma += parseInt(cpf.charAt(i)) * (10 - i);
    }
    let resto = 11 - (soma % 11);
    let dv1 = resto < 2 ? 0 : resto;
    
    // Calcular segundo dígito verificador
    soma = 0;
    for (let i = 0; i < 10; i++) {
        soma += parseInt(cpf.charAt(i)) * (11 - i);
    }
    resto = 11 - (soma % 11);
    let dv2 = resto < 2 ? 0 : resto;
    
    return parseInt(cpf.charAt(9)) === dv1 && parseInt(cpf.charAt(10)) === dv2;
}
</script>

<?php include '../includes/footer.php'; ?> 