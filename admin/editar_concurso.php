<?php
require_once '../config.php';

// Verificar se está logado
if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

$concurso_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$concurso_id) {
    setMessage('ID do concurso inválido.', 'error');
    redirect('concursos.php');
}

$db = getDB();
$concurso = null;

// Buscar dados do concurso
if ($db) {
    // Usar SQLite
    try {
        $stmt = $db->prepare("SELECT * FROM concursos WHERE id = ?");
        $stmt->execute([$concurso_id]);
        $concurso = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        setMessage('Erro ao buscar concurso: ' . $e->getMessage(), 'error');
        redirect('concursos.php');
    }
} else {
    // Usar CSV
    $concursos = getConcursosFromCSV();
    foreach ($concursos as $c) {
        if ($c['id'] == $concurso_id) {
            $concurso = $c;
            break;
        }
    }
}

if (!$concurso) {
    setMessage('Concurso não encontrado.', 'error');
    redirect('concursos.php');
}

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setMessage('Token de segurança inválido.', 'error');
        redirect('editar_concurso.php?id=' . $concurso_id);
    }
    
    // Validar dados
    $titulo = trim($_POST['titulo'] ?? '');
    $orgao = trim($_POST['orgao'] ?? '');
    $numero_concurso = trim($_POST['numero_concurso'] ?? '');
    $ano_concurso = intval($_POST['ano_concurso'] ?? date('Y'));
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $data_prova = $_POST['data_prova'] ?? '';
    $horario_inicio = $_POST['horario_inicio'] ?? '';
    $horario_fim = $_POST['horario_fim'] ?? '';
    $valor_pagamento = floatval($_POST['valor_pagamento'] ?? 0);
    $vagas_disponiveis = intval($_POST['vagas_disponiveis'] ?? 0);
    $status = $_POST['status'] ?? 'ativo';
    $descricao = trim($_POST['descricao'] ?? '');
    $termos_aceite = trim($_POST['termos_aceite'] ?? '');
    // Novos campos de treinamento/manual
    $data_treinamento = $_POST['data_treinamento'] ?? '';
    $hora_treinamento = $_POST['hora_treinamento'] ?? '';
    $tipo_treinamento = $_POST['tipo_treinamento'] ?? 'presencial';
    $link_treinamento = trim($_POST['link_treinamento'] ?? '');
    $local_treinamento = trim($_POST['local_treinamento'] ?? '');
    $link_material_fiscal = trim($_POST['link_material_fiscal'] ?? '');
    
    $errors = [];
    
    if (empty($titulo)) $errors[] = 'Título é obrigatório.';
    if (empty($orgao)) $errors[] = 'Órgão é obrigatório.';
    if (empty($cidade)) $errors[] = 'Cidade é obrigatória.';
    if (empty($estado)) $errors[] = 'Estado é obrigatório.';
    if (empty($data_prova)) $errors[] = 'Data da prova é obrigatória.';
    if (empty($horario_inicio)) $errors[] = 'Horário de início é obrigatório.';
    if (empty($horario_fim)) $errors[] = 'Horário de fim é obrigatório.';
    if ($valor_pagamento <= 0) $errors[] = 'Valor do pagamento deve ser maior que zero.';
    if ($vagas_disponiveis <= 0) $errors[] = 'Vagas disponíveis deve ser maior que zero.';
    
    if (empty($errors)) {
        try {
            if ($db) {
                // Processar upload da logo
                $logo_orgao = $concurso['logo_orgao'] ?? '';
                if (isset($_FILES['logo_orgao']) && $_FILES['logo_orgao']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['logo_orgao']['name'], PATHINFO_EXTENSION));
                    $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (in_array($ext, $permitidas)) {
                        $nome_arquivo = 'logos/orgao_' . $concurso_id . '_' . time() . '.' . $ext;
                        if (move_uploaded_file($_FILES['logo_orgao']['tmp_name'], '../' . $nome_arquivo)) {
                            $logo_orgao = $nome_arquivo;
                        }
                    }
                }
                // Atualizar no SQLite
                $stmt = $db->prepare("
                    UPDATE concursos SET 
                        titulo = ?, orgao = ?, numero_concurso = ?, ano_concurso = ?, cidade = ?, estado = ?, 
                        data_prova = ?, horario_inicio = ?, horario_fim = ?, 
                        valor_pagamento = ?, vagas_disponiveis = ?, status = ?, 
                        descricao = ?, termos_aceite = ?, 
                        data_treinamento = ?, hora_treinamento = ?, tipo_treinamento = ?, link_treinamento = ?, local_treinamento = ?, link_material_fiscal = ?,
                        logo_orgao = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([
                    $titulo, $orgao, $numero_concurso, $ano_concurso, $cidade, $estado, $data_prova,
                    $horario_inicio, $horario_fim, $valor_pagamento,
                    $vagas_disponiveis, $status, $descricao, $termos_aceite,
                    $data_treinamento, $hora_treinamento, $tipo_treinamento, $link_treinamento, $local_treinamento, $link_material_fiscal,
                    $logo_orgao,
                    $concurso_id
                ]);
                
                if ($result) {
                    logActivity("Concurso atualizado: $titulo (ID: $concurso_id)", 'INFO');
                    setMessage('Concurso atualizado com sucesso!', 'success');
                    redirect('concursos.php');
                } else {
                    throw new Exception('Erro ao atualizar concurso');
                }
            } else {
                // Atualizar no CSV (implementação simplificada)
                setMessage('Edição de concursos no modo CSV não está disponível. Use o SQLite para edição completa.', 'warning');
                redirect('concursos.php');
            }
        } catch (Exception $e) {
            setMessage('Erro ao atualizar concurso: ' . $e->getMessage(), 'error');
            logActivity('Erro ao atualizar concurso: ' . $e->getMessage(), 'ERROR');
        }
    } else {
        setMessage('Erros de validação: ' . implode(' ', $errors), 'error');
    }
}

$pageTitle = 'Editar Concurso';
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-edit me-2"></i>
                    Editar Concurso
                </h1>
                <a href="concursos.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Voltar
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-list me-2"></i>
                        Dados do Concurso
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="titulo" class="form-label">Título do Concurso *</label>
                                    <input type="text" class="form-control" id="titulo" name="titulo" value="<?= htmlspecialchars($concurso['titulo']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="orgao" class="form-label">Órgão *</label>
                                    <input type="text" class="form-control" id="orgao" name="orgao" value="<?= htmlspecialchars($concurso['orgao']) ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="numero_concurso" class="form-label">Número do Concurso *</label>
                                    <input type="text" class="form-control" id="numero_concurso" name="numero_concurso" value="<?= htmlspecialchars($concurso['numero_concurso'] ?? '') ?>" placeholder="Ex: 001/2024" required>
                                    <div class="form-text">Número oficial do concurso</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ano_concurso" class="form-label">Ano do Concurso *</label>
                                    <input type="number" class="form-control" id="ano_concurso" name="ano_concurso" value="<?= htmlspecialchars($concurso['ano_concurso'] ?? date('Y')) ?>" min="2000" max="2030" required>
                                    <div class="form-text">Ano do concurso</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="logo_orgao" class="form-label">Logo do Concurso</label>
                            <input type="file" class="form-control" id="logo_orgao" name="logo_orgao" accept="image/*">
                            <?php if (!empty($concurso['logo_orgao']) && file_exists('../' . $concurso['logo_orgao'])): ?>
                                <div class="mt-2">
                                    <img src="../<?= htmlspecialchars($concurso['logo_orgao']) ?>" alt="Logo atual" style="max-height: 60px;">
                                    <div><small>Logo atual</small></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cidade" class="form-label">Cidade *</label>
                                    <input type="text" class="form-control" id="cidade" name="cidade" 
                                           value="<?= htmlspecialchars($concurso['cidade']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="estado" class="form-label">Estado *</label>
                                    <input type="text" class="form-control" id="estado" name="estado" 
                                           value="<?= htmlspecialchars($concurso['estado']) ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="data_prova" class="form-label">Data da Prova *</label>
                                    <input type="date" class="form-control" id="data_prova" name="data_prova" 
                                           value="<?= htmlspecialchars($concurso['data_prova']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="horario_inicio" class="form-label">Horário de Início *</label>
                                    <input type="time" class="form-control" id="horario_inicio" name="horario_inicio" 
                                           value="<?= htmlspecialchars($concurso['horario_inicio']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="horario_fim" class="form-label">Horário de Fim *</label>
                                    <input type="time" class="form-control" id="horario_fim" name="horario_fim" 
                                           value="<?= htmlspecialchars($concurso['horario_fim']) ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="valor_pagamento" class="form-label">Valor do Pagamento (R$) *</label>
                                    <input type="number" class="form-control" id="valor_pagamento" name="valor_pagamento" 
                                           value="<?= htmlspecialchars($concurso['valor_pagamento']) ?>" 
                                           step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="vagas_disponiveis" class="form-label">Vagas Disponíveis *</label>
                                    <input type="number" class="form-control" id="vagas_disponiveis" name="vagas_disponiveis" 
                                           value="<?= htmlspecialchars($concurso['vagas_disponiveis']) ?>" 
                                           min="1" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="ativo" <?= $concurso['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                <option value="inativo" <?= $concurso['status'] === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                                <option value="finalizado" <?= $concurso['status'] === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= htmlspecialchars($concurso['descricao'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="termos_aceite" class="form-label">Termos de Aceite</label>
                            <textarea class="form-control" id="termos_aceite" name="termos_aceite" rows="8"><?= htmlspecialchars($concurso['termos_aceite'] ?? '') ?></textarea>
                            <div class="form-text">Termos que serão exibidos para os fiscais durante o cadastro.</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="data_treinamento" class="form-label">Data do Treinamento</label>
                                    <input type="date" class="form-control" id="data_treinamento" name="data_treinamento" value="<?= htmlspecialchars($concurso['data_treinamento'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="hora_treinamento" class="form-label">Horário do Treinamento</label>
                                    <input type="time" class="form-control" id="hora_treinamento" name="hora_treinamento" value="<?= htmlspecialchars($concurso['hora_treinamento'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="tipo_treinamento" class="form-label">Tipo de Treinamento</label>
                                    <select class="form-select" id="tipo_treinamento" name="tipo_treinamento" onchange="toggleTreinamentoCampos()">
                                        <option value="presencial" <?= ($concurso['tipo_treinamento'] ?? 'presencial') === 'presencial' ? 'selected' : '' ?>>Presencial</option>
                                        <option value="online" <?= ($concurso['tipo_treinamento'] ?? '') === 'online' ? 'selected' : '' ?>>Online</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="link_treinamento" class="form-label">Link do Treinamento (se online)</label>
                                    <input type="url" class="form-control" id="link_treinamento" name="link_treinamento" value="<?= htmlspecialchars($concurso['link_treinamento'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="local_treinamento" class="form-label">Local do Treinamento (se presencial)</label>
                                    <input type="text" class="form-control" id="local_treinamento" name="local_treinamento" value="<?= htmlspecialchars($concurso['local_treinamento'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="link_material_fiscal" class="form-label">Link do Material/Manual do Fiscal</label>
                            <input type="url" class="form-control" id="link_material_fiscal" name="link_material_fiscal" value="<?= htmlspecialchars($concurso['link_material_fiscal'] ?? '') ?>">
                        </div>
                        <script>
                        function toggleTreinamentoCampos() {
                            var tipo = document.getElementById('tipo_treinamento').value;
                            document.getElementById('link_treinamento').disabled = (tipo !== 'online');
                            document.getElementById('local_treinamento').disabled = (tipo !== 'presencial');
                        }
                        document.addEventListener('DOMContentLoaded', function() {
                            toggleTreinamentoCampos();
                        });
                        </script>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="concursos.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 