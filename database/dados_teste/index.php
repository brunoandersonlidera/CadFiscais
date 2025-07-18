<?php
require_once '../config.php';

// Verificar se usuário está logado e é admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$mensagem = '';
$tipo_mensagem = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    try {
        switch ($acao) {
            case 'gerar_concurso':
                require_once 'gerar_concurso.php';
                $mensagem = 'Concurso fictício gerado com sucesso!';
                $tipo_mensagem = 'success';
                break;
                
            case 'gerar_escolas':
                require_once 'gerar_escolas.php';
                $mensagem = 'Escolas fictícias geradas com sucesso!';
                $tipo_mensagem = 'success';
                break;
                
            case 'gerar_salas':
                require_once 'gerar_salas.php';
                $mensagem = 'Salas fictícias geradas com sucesso!';
                $tipo_mensagem = 'success';
                break;
                
            case 'gerar_fiscais':
                require_once 'gerar_fiscais.php';
                $mensagem = 'Fiscais fictícios gerados com sucesso!';
                $tipo_mensagem = 'success';
                break;
                
            case 'gerar_todos':
                require_once 'gerar_concurso.php';
                require_once 'gerar_escolas.php';
                require_once 'gerar_salas.php';
                require_once 'gerar_fiscais.php';
                $mensagem = 'Todos os dados fictícios foram gerados com sucesso!';
                $tipo_mensagem = 'success';
                break;
                
            default:
                $mensagem = 'Ação inválida!';
                $tipo_mensagem = 'danger';
        }
    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// Obter estatísticas
$concursos = getConcursosAtivos();
$escolas = getEscolasFromCSV(1); // Assumindo concurso ID 1
$salas = getSalasFromCSV(1); // Assumindo escola ID 1
$fiscais = getFiscaisFromCSV();

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-database"></i> 
                        Gerador de Dados Fictícios para Testes
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($mensagem): ?>
                        <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-<?= $tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                            <?= htmlspecialchars($mensagem) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Atenção!</h5>
                        <p>Este gerador cria dados fictícios para testes. Os dados gerados incluem:</p>
                        <ul>
                            <li><strong>1 Concurso:</strong> Concurso Público Municipal 2025</li>
                            <li><strong>5 Escolas:</strong> Escolas municipais fictícias</li>
                            <li><strong>25 Salas:</strong> 5 salas por escola (3 salas + 1 corredor + 1 portaria)</li>
                            <li><strong>50 Fiscais:</strong> 2 fiscais por sala/corredor/portaria (todos aprovados)</li>
                        </ul>
                        <p><strong>⚠️ Importante:</strong> Este gerador pode sobrescrever dados existentes!</p>
                    </div>

                    <!-- Estatísticas Atuais -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h5><i class="fas fa-graduation-cap"></i></h5>
                                    <h4><?= count($concursos) ?></h4>
                                    <small>Concursos</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5><i class="fas fa-school"></i></h5>
                                    <h4><?= count($escolas) ?></h4>
                                    <small>Escolas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h5><i class="fas fa-door-open"></i></h5>
                                    <h4><?= count($salas) ?></h4>
                                    <small>Salas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h5><i class="fas fa-users"></i></h5>
                                    <h4><?= count($fiscais) ?></h4>
                                    <small>Fiscais</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Opções de Geração -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Gerar Individualmente</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" class="mb-3">
                                        <input type="hidden" name="acao" value="gerar_concurso">
                                        <button type="submit" class="btn btn-primary btn-block w-100 mb-2">
                                            <i class="fas fa-graduation-cap"></i> Gerar Concurso
                                        </button>
                                    </form>
                                    
                                    <form method="POST" class="mb-3">
                                        <input type="hidden" name="acao" value="gerar_escolas">
                                        <button type="submit" class="btn btn-success btn-block w-100 mb-2">
                                            <i class="fas fa-school"></i> Gerar Escolas
                                        </button>
                                    </form>
                                    
                                    <form method="POST" class="mb-3">
                                        <input type="hidden" name="acao" value="gerar_salas">
                                        <button type="submit" class="btn btn-info btn-block w-100 mb-2">
                                            <i class="fas fa-door-open"></i> Gerar Salas
                                        </button>
                                    </form>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="acao" value="gerar_fiscais">
                                        <button type="submit" class="btn btn-warning btn-block w-100">
                                            <i class="fas fa-users"></i> Gerar Fiscais
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-magic"></i> Gerar Todos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle"></i> Geração Completa</h6>
                                        <p>Esta opção irá gerar todos os dados fictícios de uma vez:</p>
                                        <ul class="mb-0">
                                            <li>1 Concurso</li>
                                            <li>5 Escolas</li>
                                            <li>25 Salas</li>
                                            <li>50 Fiscais</li>
                                        </ul>
                                    </div>
                                    
                                    <form method="POST">
                                        <input type="hidden" name="acao" value="gerar_todos">
                                        <button type="submit" class="btn btn-danger btn-lg btn-block w-100">
                                            <i class="fas fa-rocket"></i> Gerar Todos os Dados
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Links Úteis -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-link"></i> Links Úteis</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <a href="../admin/concursos.php" class="btn btn-outline-primary btn-sm w-100 mb-2">
                                                <i class="fas fa-graduation-cap"></i> Ver Concursos
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="../admin/escolas.php" class="btn btn-outline-success btn-sm w-100 mb-2">
                                                <i class="fas fa-school"></i> Ver Escolas
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="../admin/salas.php" class="btn btn-outline-info btn-sm w-100 mb-2">
                                                <i class="fas fa-door-open"></i> Ver Salas
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="../admin/fiscais.php" class="btn btn-outline-warning btn-sm w-100 mb-2">
                                                <i class="fas fa-users"></i> Ver Fiscais
                                            </a>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <a href="../admin/alocar_fiscais.php" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                                                <i class="fas fa-map-marker-alt"></i> Alocar Fiscais
                                            </a>
                                        </div>
                                        <div class="col-md-6">
                                            <a href="../admin/dashboard.php" class="btn btn-outline-dark btn-sm w-100 mb-2">
                                                <i class="fas fa-tachometer-alt"></i> Dashboard
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 