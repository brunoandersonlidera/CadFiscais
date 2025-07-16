<?php
require_once '../config.php';

$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : 0;
$cpf = '';
$mensagem = '';
$alocacao = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    if (strlen($cpf) < 11) {
        $mensagem = 'CPF inválido.';
    } else {
        $db = getDB();
        // Buscar fiscal pelo CPF e concurso
        $stmt = $db->prepare("SELECT id, nome FROM fiscais WHERE cpf = ? AND concurso_id = ? AND status = 'aprovado' LIMIT 1");
        $stmt->execute([$cpf, $concurso_id]);
        $fiscal = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fiscal) {
            $mensagem = 'Fiscal não encontrado ou não aprovado neste concurso.';
        } else {
            // Buscar alocação ativa
            $stmt = $db->prepare("
                SELECT e.nome as escola_nome, s.nome as sala_nome
                FROM alocacoes_fiscais a
                LEFT JOIN escolas e ON a.escola_id = e.id
                LEFT JOIN salas s ON a.sala_id = s.id
                WHERE a.fiscal_id = ? AND a.status = 'ativo'
                LIMIT 1
            ");
            $stmt->execute([$fiscal['id']]);
            $alocacao = $stmt->fetch(PDO::FETCH_ASSOC);
            $alocacao['nome'] = $fiscal['nome'];
            if (!$alocacao['escola_nome'] && !$alocacao['sala_nome']) {
                $mensagem = 'Fiscal encontrado, mas ainda não alocado em escola/sala.';
            }
        }
    }
}

$pageTitle = 'Consulta de Local do Fiscal';
include '../includes/header.php';
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-search me-2"></i>Consulta de Local do Fiscal</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="cpf" class="form-label">Informe seu CPF</label>
                            <input type="text" class="form-control" id="cpf" name="cpf" maxlength="14" placeholder="000.000.000-00" value="<?= htmlspecialchars($cpf) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Consultar
                        </button>
                    </form>
                    <?php if ($mensagem): ?>
                        <div class="alert alert-warning mt-3"> <?= htmlspecialchars($mensagem) ?> </div>
                    <?php elseif ($alocacao): ?>
                        <div class="alert alert-success mt-3">
                            <strong>Nome:</strong> <?= htmlspecialchars($alocacao['nome']) ?><br>
                            <strong>Escola:</strong> <?= htmlspecialchars($alocacao['escola_nome'] ?? 'N/A') ?><br>
                            <strong>Sala:</strong> <?= htmlspecialchars($alocacao['sala_nome'] ?? 'N/A') ?><br>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?> 