<?php
require_once '../config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        // Validar dados
        $titulo = trim($_POST['titulo'] ?? '');
        $orgao = trim($_POST['orgao'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $estado = trim($_POST['estado'] ?? '');
        $data_prova = $_POST['data_prova'] ?? '';
        $horario_inicio = $_POST['horario_inicio'] ?? '';
        $horario_fim = $_POST['horario_fim'] ?? '';
        $valor_pagamento = (float)($_POST['valor_pagamento'] ?? 0);
        $vagas_disponiveis = (int)($_POST['vagas_disponiveis'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $termos_aceite = trim($_POST['termos_aceite'] ?? '');
        $status = $_POST['status'] ?? 'ativo';
        
        // Validações
        $errors = [];
        
        if (empty($titulo)) {
            $errors[] = 'Título é obrigatório.';
        }
        
        if (empty($orgao)) {
            $errors[] = 'Órgão é obrigatório.';
        }
        
        if (empty($cidade)) {
            $errors[] = 'Cidade é obrigatória.';
        }
        
        if (empty($estado)) {
            $errors[] = 'Estado é obrigatório.';
        }
        
        if (empty($data_prova)) {
            $errors[] = 'Data da prova é obrigatória.';
        } else {
            $data_prova_obj = new DateTime($data_prova);
            $hoje = new DateTime();
            if ($data_prova_obj < $hoje) {
                $errors[] = 'Data da prova não pode ser anterior a hoje.';
            }
        }
        
        if (empty($horario_inicio)) {
            $errors[] = 'Horário de início é obrigatório.';
        }
        
        if (empty($horario_fim)) {
            $errors[] = 'Horário de fim é obrigatório.';
        }
        
        if ($valor_pagamento <= 0) {
            $errors[] = 'Valor do pagamento deve ser maior que zero.';
        }
        
        if ($vagas_disponiveis <= 0) {
            $errors[] = 'Número de vagas deve ser maior que zero.';
        }
        
        // Processar upload de logo
        $logo_orgao = '';
        if (isset($_FILES['logo_orgao']) && $_FILES['logo_orgao']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../logos/';
            $file_extension = strtolower(pathinfo($_FILES['logo_orgao']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                $errors[] = 'Formato de arquivo não permitido. Use JPG, PNG ou GIF.';
            } else {
                $file_size = $_FILES['logo_orgao']['size'];
                if ($file_size > 5 * 1024 * 1024) { // 5MB
                    $errors[] = 'Arquivo muito grande. Máximo 5MB.';
                } else {
                    $new_filename = 'orgao_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['logo_orgao']['tmp_name'], $upload_path)) {
                        $logo_orgao = 'logos/' . $new_filename;
                    } else {
                        $errors[] = 'Erro ao fazer upload do arquivo.';
                    }
                }
            }
        }
        
        if (empty($errors)) {
            try {
                $db = getDB();
                
                $stmt = $db->prepare("
                    INSERT INTO concursos (
                        titulo, orgao, cidade, estado, data_prova, horario_inicio, 
                        horario_fim, valor_pagamento, vagas_disponiveis, status, 
                        descricao, termos_aceite, logo_orgao
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $titulo,
                    $orgao,
                    $cidade,
                    $estado,
                    $data_prova,
                    $horario_inicio,
                    $horario_fim,
                    $valor_pagamento,
                    $vagas_disponiveis,
                    $status,
                    $descricao,
                    $termos_aceite,
                    $logo_orgao
                ]);
                
                $concurso_id = $db->lastInsertId();
                
                logActivity("Novo concurso criado: $titulo (ID: $concurso_id)", 'INFO');
                
                setMessage('Concurso criado com sucesso!', 'success');
                redirect('concursos.php');
                
            } catch (Exception $e) {
                logActivity('Erro ao criar concurso: ' . $e->getMessage(), 'ERROR');
                $erro = 'Erro interno do sistema. Tente novamente.';
            }
        } else {
            $erro = 'Erro de validação: ' . implode(' ', $errors);
        }
    } else {
        $erro = 'Token de segurança inválido.';
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Concurso - Sistema IDH</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Novo Concurso</h1>
            <p>Sistema de Cadastro de Fiscais - IDH</p>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">
                        📝 Novo Concurso
                    </h2>
                    <a href="concursos.php" class="btn btn-secondary">
                        ← Voltar
                    </a>
                </div>
            </div>
        </div>

<?php if ($erro): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <?= htmlspecialchars($erro) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Informações do Concurso
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">
                                    <i class="fas fa-heading me-1"></i>Título do Concurso *
                                </label>
                                <input type="text" class="form-control" id="titulo" name="titulo" 
                                       value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>" required>
                                <div class="invalid-feedback">Título é obrigatório.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="status" class="form-label">
                                    <i class="fas fa-toggle-on me-1"></i>Status
                                </label>
                                <select class="form-select" id="status" name="status">
                                    <option value="ativo" <?= ($_POST['status'] ?? 'ativo') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                    <option value="inativo" <?= ($_POST['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="orgao" class="form-label">
                                    <i class="fas fa-building me-1"></i>Órgão *
                                </label>
                                <input type="text" class="form-control" id="orgao" name="orgao" 
                                       value="<?= htmlspecialchars($_POST['orgao'] ?? '') ?>" required>
                                <div class="invalid-feedback">Órgão é obrigatório.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="logo_orgao" class="form-label">
                                    <i class="fas fa-image me-1"></i>Logo do Órgão (opcional)
                                </label>
                                <input type="file" class="form-control" id="logo_orgao" name="logo_orgao" 
                                       accept="image/*">
                                <div class="form-text">Formatos: JPG, PNG, GIF. Máximo 5MB.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cidade" class="form-label">
                                    <i class="fas fa-map-marker-alt me-1"></i>Cidade *
                                </label>
                                <input type="text" class="form-control" id="cidade" name="cidade" 
                                       value="<?= htmlspecialchars($_POST['cidade'] ?? '') ?>" required>
                                <div class="invalid-feedback">Cidade é obrigatória.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="estado" class="form-label">
                                    <i class="fas fa-map me-1"></i>Estado *
                                </label>
                                <select class="form-select" id="estado" name="estado" required>
                                    <option value="">Selecione...</option>
                                    <option value="AC" <?= ($_POST['estado'] ?? '') === 'AC' ? 'selected' : '' ?>>Acre</option>
                                    <option value="AL" <?= ($_POST['estado'] ?? '') === 'AL' ? 'selected' : '' ?>>Alagoas</option>
                                    <option value="AP" <?= ($_POST['estado'] ?? '') === 'AP' ? 'selected' : '' ?>>Amapá</option>
                                    <option value="AM" <?= ($_POST['estado'] ?? '') === 'AM' ? 'selected' : '' ?>>Amazonas</option>
                                    <option value="BA" <?= ($_POST['estado'] ?? '') === 'BA' ? 'selected' : '' ?>>Bahia</option>
                                    <option value="CE" <?= ($_POST['estado'] ?? '') === 'CE' ? 'selected' : '' ?>>Ceará</option>
                                    <option value="DF" <?= ($_POST['estado'] ?? '') === 'DF' ? 'selected' : '' ?>>Distrito Federal</option>
                                    <option value="ES" <?= ($_POST['estado'] ?? '') === 'ES' ? 'selected' : '' ?>>Espírito Santo</option>
                                    <option value="GO" <?= ($_POST['estado'] ?? '') === 'GO' ? 'selected' : '' ?>>Goiás</option>
                                    <option value="MA" <?= ($_POST['estado'] ?? '') === 'MA' ? 'selected' : '' ?>>Maranhão</option>
                                    <option value="MT" <?= ($_POST['estado'] ?? '') === 'MT' ? 'selected' : '' ?>>Mato Grosso</option>
                                    <option value="MS" <?= ($_POST['estado'] ?? '') === 'MS' ? 'selected' : '' ?>>Mato Grosso do Sul</option>
                                    <option value="MG" <?= ($_POST['estado'] ?? '') === 'MG' ? 'selected' : '' ?>>Minas Gerais</option>
                                    <option value="PA" <?= ($_POST['estado'] ?? '') === 'PA' ? 'selected' : '' ?>>Pará</option>
                                    <option value="PB" <?= ($_POST['estado'] ?? '') === 'PB' ? 'selected' : '' ?>>Paraíba</option>
                                    <option value="PR" <?= ($_POST['estado'] ?? '') === 'PR' ? 'selected' : '' ?>>Paraná</option>
                                    <option value="PE" <?= ($_POST['estado'] ?? '') === 'PE' ? 'selected' : '' ?>>Pernambuco</option>
                                    <option value="PI" <?= ($_POST['estado'] ?? '') === 'PI' ? 'selected' : '' ?>>Piauí</option>
                                    <option value="RJ" <?= ($_POST['estado'] ?? '') === 'RJ' ? 'selected' : '' ?>>Rio de Janeiro</option>
                                    <option value="RN" <?= ($_POST['estado'] ?? '') === 'RN' ? 'selected' : '' ?>>Rio Grande do Norte</option>
                                    <option value="RS" <?= ($_POST['estado'] ?? '') === 'RS' ? 'selected' : '' ?>>Rio Grande do Sul</option>
                                    <option value="RO" <?= ($_POST['estado'] ?? '') === 'RO' ? 'selected' : '' ?>>Rondônia</option>
                                    <option value="RR" <?= ($_POST['estado'] ?? '') === 'RR' ? 'selected' : '' ?>>Roraima</option>
                                    <option value="SC" <?= ($_POST['estado'] ?? '') === 'SC' ? 'selected' : '' ?>>Santa Catarina</option>
                                    <option value="SP" <?= ($_POST['estado'] ?? '') === 'SP' ? 'selected' : '' ?>>São Paulo</option>
                                    <option value="SE" <?= ($_POST['estado'] ?? '') === 'SE' ? 'selected' : '' ?>>Sergipe</option>
                                    <option value="TO" <?= ($_POST['estado'] ?? '') === 'TO' ? 'selected' : '' ?>>Tocantins</option>
                                </select>
                                <div class="invalid-feedback">Estado é obrigatório.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="data_prova" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>Data da Prova *
                                </label>
                                <input type="date" class="form-control" id="data_prova" name="data_prova" 
                                       value="<?= htmlspecialchars($_POST['data_prova'] ?? '') ?>" required>
                                <div class="invalid-feedback">Data da prova é obrigatória.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="horario_inicio" class="form-label">
                                    <i class="fas fa-clock me-1"></i>Horário de Início *
                                </label>
                                <input type="time" class="form-control" id="horario_inicio" name="horario_inicio" 
                                       value="<?= htmlspecialchars($_POST['horario_inicio'] ?? '') ?>" required>
                                <div class="invalid-feedback">Horário de início é obrigatório.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="horario_fim" class="form-label">
                                    <i class="fas fa-clock me-1"></i>Horário de Fim *
                                </label>
                                <input type="time" class="form-control" id="horario_fim" name="horario_fim" 
                                       value="<?= htmlspecialchars($_POST['horario_fim'] ?? '') ?>" required>
                                <div class="invalid-feedback">Horário de fim é obrigatório.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="valor_pagamento" class="form-label">
                                    <i class="fas fa-dollar-sign me-1"></i>Valor do Pagamento (R$) *
                                </label>
                                <input type="number" class="form-control" id="valor_pagamento" name="valor_pagamento" 
                                       value="<?= htmlspecialchars($_POST['valor_pagamento'] ?? '') ?>" 
                                       step="0.01" min="0" required>
                                <div class="invalid-feedback">Valor do pagamento deve ser maior que zero.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="vagas_disponiveis" class="form-label">
                                    <i class="fas fa-users me-1"></i>Número de Vagas *
                                </label>
                                <input type="number" class="form-control" id="vagas_disponiveis" name="vagas_disponiveis" 
                                       value="<?= htmlspecialchars($_POST['vagas_disponiveis'] ?? '') ?>" 
                                       min="1" required>
                                <div class="invalid-feedback">Número de vagas deve ser maior que zero.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">
                            <i class="fas fa-align-left me-1"></i>Descrição (opcional)
                        </label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3" 
                                  placeholder="Descrição detalhada do concurso..."><?= htmlspecialchars($_POST['descricao'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="termos_aceite" class="form-label">
                            <i class="fas fa-file-contract me-1"></i>Termos de Aceite *
                        </label>
                        <textarea class="form-control" id="termos_aceite" name="termos_aceite" rows="8" required
                                  placeholder="Termos de aceite que os fiscais devem concordar..."><?= htmlspecialchars($_POST['termos_aceite'] ?? '') ?></textarea>
                        <div class="invalid-feedback">Termos de aceite são obrigatórios.</div>
                        <div class="form-text">Estes termos serão exibidos aos fiscais durante o cadastro.</div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="concursos.php" class="btn btn-secondary me-md-2">
                            <i class="fas fa-times me-1"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Criar Concurso
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Validação do formulário
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.needs-validation');
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
    
    // Validação da data da prova
    const dataProvaInput = document.getElementById('data_prova');
    dataProvaInput.addEventListener('change', function() {
        const dataProva = new Date(this.value);
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);
        
        if (dataProva < hoje) {
            this.setCustomValidity('Data da prova não pode ser anterior a hoje.');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Validação dos horários
    const horarioInicioInput = document.getElementById('horario_inicio');
    const horarioFimInput = document.getElementById('horario_fim');
    
    function validarHorarios() {
        if (horarioInicioInput.value && horarioFimInput.value) {
            if (horarioInicioInput.value >= horarioFimInput.value) {
                horarioFimInput.setCustomValidity('Horário de fim deve ser posterior ao horário de início.');
            } else {
                horarioFimInput.setCustomValidity('');
            }
        }
    }
    
    horarioInicioInput.addEventListener('change', validarHorarios);
    horarioFimInput.addEventListener('change', validarHorarios);
});
</script>
    </div>
</body>
</html> 