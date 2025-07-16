<?php
require_once '../config.php';

if (!isAdmin()) {
    redirect('../login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = getDB();

if (!$id) {
    setMessage('ID da alocação não fornecido', 'error');
    redirect('alocar_fiscal.php');
}

// Buscar alocação
$stmt = $db->prepare('SELECT * FROM alocacoes_fiscais WHERE id = ?');
$stmt->execute([$id]);
$alocacao = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$alocacao) {
    setMessage('Alocação não encontrada', 'error');
    redirect('alocar_fiscal.php');
}

$fiscal_id = $alocacao['fiscal_id'];

// Buscar escolas e salas
$escolas = $db->query('SELECT * FROM escolas WHERE status = "ativo" ORDER BY nome')->fetchAll();
$salas = $db->query('SELECT * FROM salas WHERE status = "ativo" ORDER BY nome')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $escola_id = (int)($_POST['escola_id'] ?? 0);
    $sala_id = (int)($_POST['sala_id'] ?? 0);
    $tipo_alocacao = trim($_POST['tipo_alocacao'] ?? 'sala');
    $observacoes = trim($_POST['observacoes'] ?? '');
    $data_alocacao = $_POST['data_alocacao'] ?? '';
    $horario_alocacao = $_POST['horario_alocacao'] ?? '';

    if (!$escola_id || !$sala_id || !$data_alocacao || !$horario_alocacao) {
        setMessage('Todos os campos obrigatórios devem ser preenchidos', 'error');
    } else {
        $stmt = $db->prepare('UPDATE alocacoes_fiscais SET escola_id=?, sala_id=?, tipo_alocacao=?, observacoes=?, data_alocacao=?, horario_alocacao=? WHERE id=?');
        $stmt->execute([$escola_id, $sala_id, $tipo_alocacao, $observacoes, $data_alocacao, $horario_alocacao, $id]);
        setMessage('Alocação atualizada com sucesso!', 'success');
        redirect('alocar_fiscal.php?id=' . $fiscal_id);
    }
}

$pageTitle = 'Editar Alocação';
include '../includes/header.php';
?>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Alocação</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="escola_id" class="form-label">Escola *</label>
                                <select class="form-select" id="escola_id" name="escola_id" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($escolas as $escola): ?>
                                        <option value="<?= $escola['id'] ?>" <?= $escola['id'] == $alocacao['escola_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($escola['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="sala_id" class="form-label">Sala *</label>
                                <select class="form-select" id="sala_id" name="sala_id" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($salas as $sala): ?>
                                        <option value="<?= $sala['id'] ?>" <?= $sala['id'] == $alocacao['sala_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($sala['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tipo_alocacao" class="form-label">Tipo de Alocação</label>
                                <select class="form-select" id="tipo_alocacao" name="tipo_alocacao">
                                    <option value="sala" <?= $alocacao['tipo_alocacao'] == 'sala' ? 'selected' : '' ?>>Sala de Aula</option>
                                    <option value="corredor" <?= $alocacao['tipo_alocacao'] == 'corredor' ? 'selected' : '' ?>>Corredor</option>
                                    <option value="entrada" <?= $alocacao['tipo_alocacao'] == 'entrada' ? 'selected' : '' ?>>Entrada/Saída</option>
                                    <option value="banheiro" <?= $alocacao['tipo_alocacao'] == 'banheiro' ? 'selected' : '' ?>>Banheiro</option>
                                    <option value="outro" <?= $alocacao['tipo_alocacao'] == 'outro' ? 'selected' : '' ?>>Outro</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="observacoes" class="form-label">Observações</label>
                                <input type="text" class="form-control" id="observacoes" name="observacoes" value="<?= htmlspecialchars($alocacao['observacoes'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="data_alocacao" class="form-label">Data da Alocação *</label>
                                <input type="date" class="form-control" id="data_alocacao" name="data_alocacao" value="<?= htmlspecialchars($alocacao['data_alocacao']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="horario_alocacao" class="form-label">Horário *</label>
                                <input type="time" class="form-control" id="horario_alocacao" name="horario_alocacao" value="<?= htmlspecialchars($alocacao['horario_alocacao']) ?>" required>
                            </div>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="alocar_fiscal.php?id=<?= $fiscal_id ?>" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left me-2"></i>Voltar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?> 