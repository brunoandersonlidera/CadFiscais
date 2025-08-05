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
        try {
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
                    SELECT e.nome as escola_nome, endereco as escola_endereco, s.nome as sala_nome
                    FROM alocacoes_fiscais a
                    LEFT JOIN escolas e ON a.escola_id = e.id
                    LEFT JOIN salas s ON a.sala_id = s.id
                    WHERE a.fiscal_id = ? AND a.status = 'ativo'
                    LIMIT 1
                ");
                $stmt->execute([$fiscal['id']]);
                $alocacao = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($alocacao) {
                    $alocacao['nome'] = $fiscal['nome'];
                    if (!$alocacao['escola_nome'] && !$alocacao['sala_nome']) {
                        $mensagem = 'Fiscal encontrado, mas ainda não alocado em escola/sala.';
                    }
                } else {
                    $mensagem = 'Fiscal encontrado, mas ainda não alocado em escola/sala.';
                }
            }
        } catch (Exception $e) {
            $mensagem = 'Erro ao consultar o banco de dados: ' . htmlspecialchars($e->getMessage());
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
                    <form method="POST" id="consultaForm">
                        <div class="mb-3">
                            <label for="cpf" class="form-label">Informe seu CPF</label>
                            <input type="text" class="form-control" id="cpf" name="cpf" maxlength="14" placeholder="000.000.000-00" value="<?= htmlspecialchars($cpf) ?>" required>
                            <small class="form-text text-muted">Digite apenas números ou no formato 000.000.000-00</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Consultar
                        </button>
                        <button type="button" class="btn btn-secondary w-100 mt-2" onclick="document.getElementById('consultaForm').reset(); document.getElementById('cpf').focus();">Nova Consulta</button>
                    </form>
                    <?php if ($mensagem): ?>
                        <div class="alert alert-warning mt-3 alert-dismissible fade show" id="mensagem" role="alert">
                            <?= htmlspecialchars($mensagem) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php elseif ($alocacao): ?>
                        <div class="alert alert-success mt-3 alert-dismissible fade show" id="resultado" role="alert">
                            <strong>Nome:</strong> <?= htmlspecialchars($alocacao['nome']) ?><br>
                            <strong>Escola:</strong> <?= htmlspecialchars($alocacao['escola_nome'] ?? 'N/A') ?><br>
                            <strong>Endereço:</strong> <?= htmlspecialchars($alocacao['escola_endereco'] ?? 'N/A') ?><br>
                            <strong>Sala:</strong> <?= htmlspecialchars($alocacao['sala_nome'] ?? 'N/A') ?><br>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Página consulta_local.php carregada');

    // Impedir manipulação automática dos elementos .alert
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.classList.remove('fade'); // Remover comportamento de fade automático
        console.log('Comportamento de fade removido do alerta:', alert.id);
    });

    // Adicionar máscara ao campo CPF usando IMask
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        const mask = IMask(cpfInput, {
            mask: '000.000.000-00',
            lazy: true, // Máscara só aparece enquanto digita
            placeholderChar: '_',
            overwrite: true
        });

        // Validação de CPF no lado do cliente
        document.getElementById('consultaForm').addEventListener('submit', function(event) {
            const cpf = cpfInput.value.replace(/\D/g, '');
            if (!validateCPF(cpf)) {
                event.preventDefault();
                Swal.fire({
                    title: 'Erro',
                    text: 'CPF inválido. Por favor, verifique o número digitado.',
                    icon: 'error'
                });
                console.log('CPF inválido:', cpf);
            } else {
                console.log('CPF válido:', cpf);
            }
        });

        // Log de eventos de digitação para depuração
        cpfInput.addEventListener('input', function() {
            console.log('Entrada no campo CPF:', cpfInput.value);
        });
    }

    // Monitorar tentativas de recarregamento
    window.addEventListener('beforeunload', function(event) {
        console.log('Tentativa de recarregamento detectada');
    });
});
</script>

<?php include '../includes/footer.php'; ?>