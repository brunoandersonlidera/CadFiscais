<?php
require_once 'config.php';

// Verificar se foi passado um ID
$fiscal_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$fiscal_id) {
    header('Location: index.php');
    exit;
}

// Buscar dados do fiscal
$fiscal = null;
$concurso = null;

$db = getDB();
if ($db) {
    try {
        $stmt = $db->prepare("
            SELECT f.*, c.titulo as concurso_titulo, c.orgao, c.data_prova, c.horario_inicio, c.horario_fim
            FROM fiscais f
            LEFT JOIN concursos c ON f.concurso_id = c.id
            WHERE f.id = ?
        ");
        $stmt->execute([$fiscal_id]);
        $fiscal = $stmt->fetch();
        
        if ($fiscal) {
            $concurso = [
                'titulo' => $fiscal['concurso_titulo'],
                'orgao' => $fiscal['orgao'],
                'data_prova' => $fiscal['data_prova'],
                'horario_inicio' => $fiscal['horario_inicio'],
                'horario_fim' => $fiscal['horario_fim']
            ];
        }
    } catch (Exception $e) {
        logActivity('Erro ao buscar fiscal: ' . $e->getMessage(), 'ERROR');
    }
}

$pageTitle = 'Cadastro Realizado com Sucesso';
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-check-circle"></i> 
                        Cadastro Realizado com Sucesso!
                    </h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-check fa-3x text-success mb-3"></i>
                        <h5>Parabéns! Seu cadastro foi realizado com sucesso.</h5>
                        <p class="text-muted">Você será contatado em breve com mais informações.</p>
                    </div>

                    <?php if ($fiscal): ?>
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Dados do Cadastro</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Nome:</strong> <?= htmlspecialchars($fiscal['nome']) ?></p>
                                <p><strong>CPF:</strong> <?= htmlspecialchars($fiscal['cpf']) ?></p>
                                <p><strong>E-mail:</strong> <?= htmlspecialchars($fiscal['email']) ?></p>
                                <p><strong>Telefone:</strong> <?= htmlspecialchars($fiscal['ddi']) ?> <?= htmlspecialchars($fiscal['celular']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Concurso:</strong> <?= htmlspecialchars($concurso['titulo']) ?></p>
                                <p><strong>Órgão:</strong> <?= htmlspecialchars($concurso['orgao']) ?></p>
                                <p><strong>Data da Prova:</strong> <?= date('d/m/Y', strtotime($concurso['data_prova'])) ?></p>
                                <p><strong>Horário:</strong> <?= $concurso['horario_inicio'] ?> às <?= $concurso['horario_fim'] ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> Próximos Passos</h6>
                        <ul class="mb-0">
                            <li>Você receberá um e-mail de confirmação</li>
                            <li>Mantenha seu telefone disponível para contato</li>
                            <li>Fique atento às informações sobre o concurso</li>
                            <li>Em caso de dúvidas, entre em contato conosco</li>
                        </ul>
                    </div>

                    <div class="text-center">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home"></i> Voltar ao Início
                        </a>
                        <a href="cadastro.php" class="btn btn-outline-secondary">
                            <i class="fas fa-user-plus"></i> Novo Cadastro
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 