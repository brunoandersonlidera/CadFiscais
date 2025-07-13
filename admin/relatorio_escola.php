<?php
require_once '../config.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('../login.php');
}

$db = getDB();

// Parâmetros da URL
$escola_id = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : 0;
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

// Validar parâmetros
if (!$escola_id || !$tipo) {
    setMessage('Parâmetros inválidos', 'error');
    redirect('relatorios.php');
}

// Buscar dados da escola
try {
    $stmt = $db->prepare("SELECT * FROM escolas WHERE id = ?");
    $stmt->execute([$escola_id]);
    $escola = $stmt->fetch();
    
    if (!$escola) {
        setMessage('Escola não encontrada', 'error');
        redirect('relatorios.php');
    }
} catch (Exception $e) {
    logActivity('Erro ao buscar escola: ' . $e->getMessage(), 'ERROR');
    setMessage('Erro ao buscar dados da escola', 'error');
    redirect('relatorios.php');
}

// Buscar concursos ativos
$concursos = [];
try {
    $stmt = $db->query("SELECT id, titulo FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC");
    $concursos = $stmt->fetchAll();
} catch (Exception $e) {
    logActivity('Erro ao buscar concursos: ' . $e->getMessage(), 'ERROR');
}

$pageTitle = 'Relatório por Escola - ' . $escola['nome'];
include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">
                <i class="fas fa-school me-2"></i>
                Relatório por Escola
            </h1>
            <a href="relatorios.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Voltar
            </a>
        </div>
    </div>
</div>

<!-- Informações da Escola -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Informações da Escola
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nome:</strong> <?= htmlspecialchars($escola['nome']) ?></p>
                        <p><strong>Endereço:</strong> <?= htmlspecialchars($escola['endereco']) ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?= $escola['status'] == 'ativo' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($escola['status']) ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Tipo de Relatório:</strong> 
                            <span class="badge bg-info"><?= ucfirst($tipo) ?></span>
                        </p>
                        <p><strong>Concurso:</strong> 
                            <select id="concurso_filter" class="form-select form-select-sm d-inline-block w-auto">
                                <option value="">Todos os concursos</option>
                                <?php foreach ($concursos as $concurso): ?>
                                <option value="<?= $concurso['id'] ?>"><?= htmlspecialchars($concurso['titulo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Gerar relatório baseado no tipo
switch ($tipo) {
    case 'fiscais':
        gerarRelatorioFiscaisEscola($db, $escola_id, $escola);
        break;
    case 'presenca':
        gerarRelatorioPresencaEscola($db, $escola_id, $escola);
        break;
    case 'salas':
        gerarRelatorioSalasEscola($db, $escola_id, $escola);
        break;
    default:
        echo '<div class="alert alert-warning">Tipo de relatório não reconhecido</div>';
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtro por concurso
    const concursoFilter = document.getElementById('concurso_filter');
    if (concursoFilter) {
        concursoFilter.addEventListener('change', function() {
            const concursoId = this.value;
            const currentUrl = new URL(window.location);
            
            if (concursoId) {
                currentUrl.searchParams.set('concurso_id', concursoId);
            } else {
                currentUrl.searchParams.delete('concurso_id');
            }
            
            window.location.href = currentUrl.toString();
        });
    }
});
</script>

<?php
function gerarRelatorioFiscaisEscola($db, $escola_id, $escola) {
    $concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : 0;
    
    try {
        $sql = "
            SELECT f.*, af.concurso_id, c.titulo as concurso_titulo,
                   s.nome as sala_nome, s.capacidade
            FROM fiscais f
            INNER JOIN alocacoes_fiscais af ON f.id = af.fiscal_id
            INNER JOIN salas s ON af.sala_id = s.id
            INNER JOIN concursos c ON af.concurso_id = c.id
            WHERE s.escola_id = ?
        ";
        $params = [$escola_id];
        
        if ($concurso_id) {
            $sql .= " AND af.concurso_id = ?";
            $params[] = $concurso_id;
        }
        
        $sql .= " ORDER BY c.data_prova DESC, f.nome";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $fiscais = $stmt->fetchAll();
        ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>
                            Fiscais Alocados - <?= htmlspecialchars($escola['nome']) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Concurso</th>
                                        <th>Fiscal</th>
                                        <th>CPF</th>
                                        <th>Telefone</th>
                                        <th>Sala</th>
                                        <th>Capacidade</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fiscais as $fiscal): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($fiscal['concurso_titulo']) ?></td>
                                        <td><?= htmlspecialchars($fiscal['nome']) ?></td>
                                        <td><?= htmlspecialchars($fiscal['cpf']) ?></td>
                                        <td><?= htmlspecialchars($fiscal['telefone']) ?></td>
                                        <td><?= htmlspecialchars($fiscal['sala_nome']) ?></td>
                                        <td><?= $fiscal['capacidade'] ?></td>
                                        <td>
                                            <span class="badge bg-<?= $fiscal['status'] == 'aprovado' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($fiscal['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if (empty($fiscais)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Nenhum fiscal alocado nesta escola.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } catch (Exception $e) {
        logActivity('Erro ao gerar relatório de fiscais da escola: ' . $e->getMessage(), 'ERROR');
        echo '<div class="alert alert-danger">Erro ao gerar relatório de fiscais da escola</div>';
    }
}

function gerarRelatorioPresencaEscola($db, $escola_id, $escola) {
    $concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : 0;
    
    try {
        $sql = "
            SELECT f.nome as fiscal_nome, f.cpf as fiscal_cpf,
                   s.nome as sala_nome, c.titulo as concurso_titulo,
                   c.data_prova
            FROM alocacoes_fiscais af
            INNER JOIN fiscais f ON af.fiscal_id = f.id
            INNER JOIN salas s ON af.sala_id = s.id
            INNER JOIN concursos c ON af.concurso_id = c.id
            WHERE s.escola_id = ? AND af.status = 'ativo'
        ";
        $params = [$escola_id];
        
        if ($concurso_id) {
            $sql .= " AND af.concurso_id = ?";
            $params[] = $concurso_id;
        }
        
        $sql .= " ORDER BY c.data_prova DESC, s.nome, f.nome";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $fiscais = $stmt->fetchAll();
        ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>
                            Lista de Presença - <?= htmlspecialchars($escola['nome']) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Concurso</th>
                                        <th>Data Prova</th>
                                        <th>Sala</th>
                                        <th>Fiscal</th>
                                        <th>CPF</th>
                                        <th>Assinatura</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fiscais as $fiscal): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($fiscal['concurso_titulo']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($fiscal['data_prova'])) ?></td>
                                        <td><?= htmlspecialchars($fiscal['sala_nome']) ?></td>
                                        <td><?= htmlspecialchars($fiscal['fiscal_nome']) ?></td>
                                        <td><?= htmlspecialchars($fiscal['fiscal_cpf']) ?></td>
                                        <td style="height: 50px; border: 1px solid #ccc;"></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if (empty($fiscais)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Nenhum fiscal alocado nesta escola.
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="lista_presenca.php?escola_id=<?= $escola_id ?>&concurso_id=<?= $concurso_id ?>" class="btn btn-warning">
                                <i class="fas fa-print me-2"></i>
                                Imprimir Lista
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } catch (Exception $e) {
        logActivity('Erro ao gerar relatório de presença da escola: ' . $e->getMessage(), 'ERROR');
        echo '<div class="alert alert-danger">Erro ao gerar relatório de presença da escola</div>';
    }
}

function gerarRelatorioSalasEscola($db, $escola_id, $escola) {
    try {
        $stmt = $db->prepare("
            SELECT s.*, 
                   COUNT(af.id) as fiscais_alocados,
                   GROUP_CONCAT(f.nome SEPARATOR ', ') as fiscais_nomes
            FROM salas s
            LEFT JOIN alocacoes_fiscais af ON s.id = af.sala_id AND af.status = 'ativo'
            LEFT JOIN fiscais f ON af.fiscal_id = f.id
            WHERE s.escola_id = ?
            GROUP BY s.id
            ORDER BY s.nome
        ");
        $stmt->execute([$escola_id]);
        $salas = $stmt->fetchAll();
        ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-door-open me-2"></i>
                            Salas e Capacidades - <?= htmlspecialchars($escola['nome']) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Sala</th>
                                        <th>Capacidade</th>
                                        <th>Fiscais Alocados</th>
                                        <th>Fiscais</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($salas as $sala): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($sala['nome']) ?></td>
                                        <td><?= $sala['capacidade'] ?></td>
                                        <td>
                                            <span class="badge bg-<?= $sala['fiscais_alocados'] > 0 ? 'success' : 'secondary' ?>">
                                                <?= $sala['fiscais_alocados'] ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($sala['fiscais_nomes'] ?: '-') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $sala['status'] == 'ativo' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($sala['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if (empty($salas)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Nenhuma sala cadastrada nesta escola.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } catch (Exception $e) {
        logActivity('Erro ao gerar relatório de salas da escola: ' . $e->getMessage(), 'ERROR');
        echo '<div class="alert alert-danger">Erro ao gerar relatório de salas da escola</div>';
    }
}
?>

<?php include '../includes/footer.php'; ?> 