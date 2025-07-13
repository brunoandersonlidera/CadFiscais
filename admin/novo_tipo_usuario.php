<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $permissoes = $_POST['permissoes'] ?? [];
        
        // Validações
        if (empty($nome)) {
            throw new Exception('Nome do tipo de usuário é obrigatório');
        }
        
        if (empty($descricao)) {
            throw new Exception('Descrição é obrigatória');
        }
        
        // Preparar permissões JSON
        $permissoes_json = [
            'admin' => in_array('admin', $permissoes),
            'relatorios' => in_array('relatorios', $permissoes),
            'cadastros' => in_array('cadastros', $permissoes),
            'usuarios' => in_array('usuarios', $permissoes),
            'tipos_usuario' => in_array('tipos_usuario', $permissoes),
            'alocacoes' => in_array('alocacoes', $permissoes),
            'presenca' => in_array('presenca', $permissoes),
            'pagamentos' => in_array('pagamentos', $permissoes)
        ];
        
        $db = getDB();
        
        // Verificar se já existe tipo com este nome
        $stmt = $db->prepare("SELECT id FROM tipos_usuario WHERE nome = ?");
        $stmt->execute([$nome]);
        if ($stmt->fetch()) {
            throw new Exception('Já existe um tipo de usuário com este nome');
        }
        
        // Inserir novo tipo
        $stmt = $db->prepare("
            INSERT INTO tipos_usuario (nome, descricao, permissoes) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$nome, $descricao, json_encode($permissoes_json)]);
        
        $tipo_id = $db->lastInsertId();
        logActivity("Novo tipo de usuário criado: $nome (ID: $tipo_id)", 'INFO');
        
        setMessage('Tipo de usuário criado com sucesso!', 'success');
        redirect('tipos_usuario.php');
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        logActivity('Erro ao criar tipo de usuário: ' . $e->getMessage(), 'ERROR');
    }
}

$pageTitle = 'Novo Tipo de Usuário';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-user-tag me-2"></i>
                Novo Tipo de Usuário
            </h1>
            <div>
                <a href="tipos_usuario.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Voltar
                </a>
            </div>
        </div>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-plus me-2"></i>
                    Informações do Tipo de Usuário
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" id="formTipoUsuario">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome do Tipo *</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
                                <div class="form-text">Ex: Coordenador, Supervisor, etc.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição *</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="3" required><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
                                <div class="form-text">Descreva as responsabilidades deste tipo de usuário</div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="fas fa-shield-alt me-2"></i>
                                Permissões
                            </h5>
                            <p class="text-muted">Selecione as permissões que este tipo de usuário terá:</p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Permissões Administrativas</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="admin" name="permissoes[]" value="admin">
                                        <label class="form-check-label" for="admin">
                                            <strong>Acesso Total (Administrador)</strong>
                                        </label>
                                        <div class="form-text">Acesso completo a todas as funcionalidades</div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="usuarios" name="permissoes[]" value="usuarios">
                                        <label class="form-check-label" for="usuarios">
                                            Gerenciar Usuários
                                        </label>
                                        <div class="form-text">Criar, editar e excluir usuários</div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="tipos_usuario" name="permissoes[]" value="tipos_usuario">
                                        <label class="form-check-label" for="tipos_usuario">
                                            Gerenciar Tipos de Usuário
                                        </label>
                                        <div class="form-text">Criar e editar tipos de usuário</div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="cadastros" name="permissoes[]" value="cadastros">
                                        <label class="form-check-label" for="cadastros">
                                            Gerenciar Cadastros
                                        </label>
                                        <div class="form-text">Criar e editar concursos, escolas, salas</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Permissões Operacionais</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="alocacoes" name="permissoes[]" value="alocacoes">
                                        <label class="form-check-label" for="alocacoes">
                                            Alocar Fiscais
                                        </label>
                                        <div class="form-text">Alocar fiscais em escolas e salas</div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="presenca" name="permissoes[]" value="presenca">
                                        <label class="form-check-label" for="presenca">
                                            Controle de Presença
                                        </label>
                                        <div class="form-text">Registrar presença dos fiscais</div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="pagamentos" name="permissoes[]" value="pagamentos">
                                        <label class="form-check-label" for="pagamentos">
                                            Gerenciar Pagamentos
                                        </label>
                                        <div class="form-text">Registrar e controlar pagamentos</div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="relatorios" name="permissoes[]" value="relatorios">
                                        <label class="form-check-label" for="relatorios">
                                            Acessar Relatórios
                                        </label>
                                        <div class="form-text">Visualizar relatórios do sistema</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="tipos_usuario.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>
                                    Cancelar
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i>
                                    Criar Tipo de Usuário
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
    // Quando "Acesso Total" é marcado, marcar todas as outras
    document.getElementById('admin').addEventListener('change', function() {
        const isChecked = this.checked;
        const checkboxes = document.querySelectorAll('input[name="permissoes[]"]');
        
        checkboxes.forEach(checkbox => {
            if (checkbox !== this) {
                checkbox.checked = isChecked;
            }
        });
    });
    
    // Quando outras permissões são desmarcadas, desmarcar "Acesso Total"
    const otherCheckboxes = document.querySelectorAll('input[name="permissoes[]"]:not(#admin)');
    otherCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (!this.checked) {
                document.getElementById('admin').checked = false;
            }
        });
    });
    
    // Validação do formulário
    document.getElementById('formTipoUsuario').addEventListener('submit', function(e) {
        const nome = document.getElementById('nome').value.trim();
        const descricao = document.getElementById('descricao').value.trim();
        const permissoes = document.querySelectorAll('input[name="permissoes[]"]:checked');
        
        if (!nome) {
            e.preventDefault();
            showMessage('Nome do tipo de usuário é obrigatório', 'error');
            return false;
        }
        
        if (!descricao) {
            e.preventDefault();
            showMessage('Descrição é obrigatória', 'error');
            return false;
        }
        
        if (permissoes.length === 0) {
            e.preventDefault();
            showMessage('Selecione pelo menos uma permissão', 'error');
            return false;
        }
        
        showLoading();
        return true;
    });
});
</script>

<?php include '../includes/footer.php'; ?> 