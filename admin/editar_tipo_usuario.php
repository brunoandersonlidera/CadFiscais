<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$tipo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$tipo_id) {
    setMessage('ID do tipo de usuário não fornecido', 'error');
    redirect('tipos_usuario.php');
}

$db = getDB();
$tipo = null;
$error = '';

// Buscar dados do tipo de usuário
try {
    $stmt = $db->prepare("SELECT * FROM tipos_usuario WHERE id = ?");
    $stmt->execute([$tipo_id]);
    $tipo = $stmt->fetch();
    
    if (!$tipo) {
        setMessage('Tipo de usuário não encontrado', 'error');
        redirect('tipos_usuario.php');
    }
} catch (Exception $e) {
    setMessage('Erro ao buscar tipo de usuário: ' . $e->getMessage(), 'error');
    redirect('tipos_usuario.php');
}

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
        
        // Verificar se já existe tipo com este nome (exceto o atual)
        $stmt = $db->prepare("SELECT id FROM tipos_usuario WHERE nome = ? AND id != ?");
        $stmt->execute([$nome, $tipo_id]);
        if ($stmt->fetch()) {
            throw new Exception('Já existe um tipo de usuário com este nome');
        }
        
        // Atualizar tipo
        $stmt = $db->prepare("
            UPDATE tipos_usuario 
            SET nome = ?, descricao = ?, permissoes = ? 
            WHERE id = ?
        ");
        $stmt->execute([$nome, $descricao, json_encode($permissoes_json), $tipo_id]);
        
        logActivity("Tipo de usuário atualizado: $nome (ID: $tipo_id)", 'INFO');
        
        setMessage('Tipo de usuário atualizado com sucesso!', 'success');
        redirect('tipos_usuario.php');
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        logActivity('Erro ao atualizar tipo de usuário: ' . $e->getMessage(), 'ERROR');
    }
}

// Decodificar permissões atuais
$permissoes_atuais = json_decode($tipo['permissoes'], true) ?? [];

$pageTitle = 'Editar Tipo de Usuário';
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-edit me-2"></i>
                Editar Tipo de Usuário
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
                    <i class="fas fa-edit me-2"></i>
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
                                       value="<?= htmlspecialchars($_POST['nome'] ?? $tipo['nome']) ?>" required>
                                <div class="form-text">Ex: Coordenador, Supervisor, etc.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição *</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="3" required><?= htmlspecialchars($_POST['descricao'] ?? $tipo['descricao']) ?></textarea>
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
                                        <input class="form-check-input" type="checkbox" id="admin" name="permissoes[]" value="admin"
                                               <?= ($permissoes_atuais['admin'] ?? false) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="admin">
                                            <strong>Acesso Total (Administrador)</strong>
                                        </label>
                                        <div class="form-text">Acesso completo a todas as funcionalidades</div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="usuarios" name="permissoes[]" value="usuarios"
                                               <?= ($permissoes_atuais['usuarios'] ?? false) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="usuarios">
                                            Gerenciar Usuários
                                        </label>
                                        <div class="form-text">Criar, editar e excluir usuários</div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="tipos_usuario" name="permissoes[]" value="tipos_usuario"
                                               <?= ($permissoes_atuais['tipos_usuario'] ?? false) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="tipos_usuario">
                                            Gerenciar Tipos de Usuário
                                        </label>
                                        <div class="form-text">Criar e editar tipos de usuário</div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="cadastros" name="permissoes[]" value="cadastros"
                                               <?= ($permissoes_atuais['cadastros'] ?? false) ? 'checked' : '' ?>>
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
                                        <input class="form-check-input" type="checkbox" id="alocacoes" name="permissoes[]" value="alocacoes"
                                               <?= ($permissoes_atuais['alocacoes'] ?? false) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="alocacoes">
                                            Alocar Fiscais
                                        </label>
                                        <div class="form-text">Alocar fiscais em escolas e salas</div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="presenca" name="permissoes[]" value="presenca"
                                               <?= ($permissoes_atuais['presenca'] ?? false) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="presenca">
                                            Controle de Presença
                                        </label>
                                        <div class="form-text">Registrar presença dos fiscais</div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="pagamentos" name="permissoes[]" value="pagamentos"
                                               <?= ($permissoes_atuais['pagamentos'] ?? false) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="pagamentos">
                                            Gerenciar Pagamentos
                                        </label>
                                        <div class="form-text">Registrar e controlar pagamentos</div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="relatorios" name="permissoes[]" value="relatorios"
                                               <?= ($permissoes_atuais['relatorios'] ?? false) ? 'checked' : '' ?>>
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
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    Atualizar Tipo de Usuário
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