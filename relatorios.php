<?php
require_once 'config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    redirect('login.php');
}

$message = '';
$error = '';

// Processar filtros
$filtros = [
    'concurso_id' => $_GET['concurso_id'] ?? '',
    'escola_id' => $_GET['escola_id'] ?? '',
    'sala_id' => $_GET['sala_id'] ?? '',
    'tipo_fiscal' => $_GET['tipo_fiscal'] ?? '',
    'status' => $_GET['status'] ?? '',
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? ''
];

// Buscar dados para filtros
$concursos = [];
$escolas = [];
$salas = [];

try {
    $db = getDB();
    if ($db) {
        // Buscar concursos ativos
        $stmt = $db->query("SELECT id, titulo FROM concursos WHERE status = 'ativo' ORDER BY titulo");
        $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar escolas
        $stmt = $db->query("SELECT id, nome FROM escolas ORDER BY nome");
        $escolas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar salas
        $stmt = $db->query("SELECT id, nome, escola_id FROM salas ORDER BY nome");
        $salas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error = 'Erro ao carregar dados: ' . $e->getMessage();
}

// Processar geração de relatório
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar_relatorio'])) {
    try {
        $tipo_relatorio = $_POST['tipo_relatorio'];
        
        switch ($tipo_relatorio) {
            case 'lista_fiscais':
                gerarRelatorioFiscais($filtros);
                break;
            case 'lista_presenca':
                gerarListaPresenca($filtros);
                break;
            case 'lista_pagamentos':
                gerarListaPagamentos($filtros);
                break;
            default:
                throw new Exception("Tipo de relatório inválido");
        }
    } catch (Exception $e) {
        $error = 'Erro ao gerar relatório: ' . $e->getMessage();
    }
}

function gerarRelatorioFiscais($filtros) {
    $db = getDB();
    if (!$db) {
        throw new Exception("Erro de conexão com banco de dados");
    }
    
    // Construir query com filtros
    $sql = "SELECT f.*, c.titulo as concurso_titulo, e.nome as escola_nome, s.nome as sala_nome
            FROM fiscais f
            LEFT JOIN concursos c ON f.concurso_id = c.id
            LEFT JOIN escolas e ON c.escola_id = e.id
            LEFT JOIN salas s ON c.sala_id = s.id
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($filtros['concurso_id'])) {
        $sql .= " AND f.concurso_id = ?";
        $params[] = $filtros['concurso_id'];
    }
    
    if (!empty($filtros['escola_id'])) {
        $sql .= " AND c.escola_id = ?";
        $params[] = $filtros['escola_id'];
    }
    
    if (!empty($filtros['sala_id'])) {
        $sql .= " AND c.sala_id = ?";
        $params[] = $filtros['sala_id'];
    }
    
    if (!empty($filtros['status'])) {
        $sql .= " AND f.status = ?";
        $params[] = $filtros['status'];
    }
    
    if (!empty($filtros['data_inicio'])) {
        $sql .= " AND DATE(f.created_at) >= ?";
        $params[] = $filtros['data_inicio'];
    }
    
    if (!empty($filtros['data_fim'])) {
        $sql .= " AND DATE(f.created_at) <= ?";
        $params[] = $filtros['data_fim'];
    }
    
    $sql .= " ORDER BY f.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Gerar PDF
    require_once 'tcpdf/tcpdf.php';
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('Sistema IDH');
    $pdf->SetAuthor('IDH');
    $pdf->SetTitle('Relatório de Fiscais');
    
    $pdf->AddPage();
    
    // Cabeçalho
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Relatório de Fiscais - IDH', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Informações do relatório
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Data de geração: ' . date('d/m/Y H:i:s'), 0, 1);
    $pdf->Cell(0, 6, 'Total de fiscais: ' . count($fiscais), 0, 1);
    $pdf->Ln(5);
    
    // Cabeçalho da tabela
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(50, 7, 'Nome', 1, 0, 'L', true);
    $pdf->Cell(30, 7, 'CPF', 1, 0, 'L', true);
    $pdf->Cell(40, 7, 'Celular', 1, 0, 'L', true);
    $pdf->Cell(40, 7, 'Concurso', 1, 0, 'L', true);
    $pdf->Cell(25, 7, 'Status', 1, 1, 'L', true);
    
    // Dados
    $pdf->SetFont('helvetica', '', 8);
    foreach ($fiscais as $fiscal) {
        $pdf->Cell(50, 6, substr($fiscal['nome'], 0, 20), 1, 0, 'L');
        $pdf->Cell(30, 6, $fiscal['cpf'], 1, 0, 'L');
        $pdf->Cell(40, 6, $fiscal['celular'], 1, 0, 'L');
        $pdf->Cell(40, 6, substr($fiscal['concurso_titulo'], 0, 15), 1, 0, 'L');
        $pdf->Cell(25, 6, ucfirst($fiscal['status']), 1, 1, 'L');
    }
    
    $pdf->Output('relatorio_fiscais.pdf', 'D');
    exit;
}

function gerarListaPresenca($filtros) {
    $db = getDB();
    if (!$db) {
        throw new Exception("Erro de conexão com banco de dados");
    }
    
    // Construir query
    $sql = "SELECT f.*, c.titulo as concurso_titulo, c.data_prova, e.nome as escola_nome, s.nome as sala_nome
            FROM fiscais f
            LEFT JOIN concursos c ON f.concurso_id = c.id
            LEFT JOIN escolas e ON c.escola_id = e.id
            LEFT JOIN salas s ON c.sala_id = s.id
            WHERE f.status = 'aprovado'";
    
    $params = [];
    
    if (!empty($filtros['concurso_id'])) {
        $sql .= " AND f.concurso_id = ?";
        $params[] = $filtros['concurso_id'];
    }
    
    $sql .= " ORDER BY f.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Gerar PDF
    require_once 'tcpdf/tcpdf.php';
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('Sistema IDH');
    $pdf->SetAuthor('IDH');
    $pdf->SetTitle('Lista de Presença');
    
    $pdf->AddPage();
    
    // Cabeçalho
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Lista de Presença - Fiscais', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Informações
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Data: ' . date('d/m/Y'), 0, 1);
    $pdf->Cell(0, 6, 'Total de fiscais: ' . count($fiscais), 0, 1);
    $pdf->Ln(5);
    
    // Cabeçalho da tabela
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(60, 7, 'Nome do Fiscal', 1, 0, 'L', true);
    $pdf->Cell(30, 7, 'CPF', 1, 0, 'L', true);
    $pdf->Cell(40, 7, 'Concurso', 1, 0, 'L', true);
    $pdf->Cell(30, 7, 'Escola', 1, 0, 'L', true);
    $pdf->Cell(30, 7, 'Assinatura', 1, 1, 'L', true);
    
    // Dados
    $pdf->SetFont('helvetica', '', 8);
    foreach ($fiscais as $fiscal) {
        $pdf->Cell(60, 8, $fiscal['nome'], 1, 0, 'L');
        $pdf->Cell(30, 8, $fiscal['cpf'], 1, 0, 'L');
        $pdf->Cell(40, 8, substr($fiscal['concurso_titulo'], 0, 15), 1, 0, 'L');
        $pdf->Cell(30, 8, substr($fiscal['escola_nome'], 0, 12), 1, 0, 'L');
        $pdf->Cell(30, 8, '', 1, 1, 'L'); // Espaço para assinatura
    }
    
    $pdf->Output('lista_presenca.pdf', 'D');
    exit;
}

function gerarListaPagamentos($filtros) {
    $db = getDB();
    if (!$db) {
        throw new Exception("Erro de conexão com banco de dados");
    }
    
    // Construir query
    $sql = "SELECT f.*, c.titulo as concurso_titulo, c.valor_pagamento
            FROM fiscais f
            LEFT JOIN concursos c ON f.concurso_id = c.id
            WHERE f.status = 'aprovado'";
    
    $params = [];
    
    if (!empty($filtros['concurso_id'])) {
        $sql .= " AND f.concurso_id = ?";
        $params[] = $filtros['concurso_id'];
    }
    
    $sql .= " ORDER BY f.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Gerar PDF
    require_once 'tcpdf/tcpdf.php';
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('Sistema IDH');
    $pdf->SetAuthor('IDH');
    $pdf->SetTitle('Lista de Pagamentos');
    
    $pdf->AddPage();
    
    // Cabeçalho
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Lista de Pagamentos - Fiscais', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Informações
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Data: ' . date('d/m/Y'), 0, 1);
    $pdf->Cell(0, 6, 'Total de fiscais: ' . count($fiscais), 0, 1);
    $pdf->Ln(5);
    
    // Cabeçalho da tabela
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(60, 7, 'Nome do Fiscal', 1, 0, 'L', true);
    $pdf->Cell(30, 7, 'CPF', 1, 0, 'L', true);
    $pdf->Cell(40, 7, 'Concurso', 1, 0, 'L', true);
    $pdf->Cell(30, 7, 'Valor', 1, 0, 'L', true);
    $pdf->Cell(30, 7, 'Assinatura', 1, 1, 'L', true);
    
    // Dados
    $pdf->SetFont('helvetica', '', 8);
    foreach ($fiscais as $fiscal) {
        $pdf->Cell(60, 8, $fiscal['nome'], 1, 0, 'L');
        $pdf->Cell(30, 8, $fiscal['cpf'], 1, 0, 'L');
        $pdf->Cell(40, 8, substr($fiscal['concurso_titulo'], 0, 15), 1, 0, 'L');
        $pdf->Cell(30, 8, 'R$ ' . number_format($fiscal['valor_pagamento'], 2, ',', '.'), 1, 0, 'L');
        $pdf->Cell(30, 8, '', 1, 1, 'L'); // Espaço para assinatura
    }
    
    $pdf->Output('lista_pagamentos.pdf', 'D');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Sistema IDH</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Relatórios</h1>
            <p>Sistema de Cadastro de Fiscais - IDH</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <h2>Gerar Relatórios</h2>
                    
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="concurso_id">Concurso:</label>
                                    <select id="concurso_id" name="concurso_id" class="form-control">
                                        <option value="">Todos os concursos</option>
                                        <?php foreach ($concursos as $concurso): ?>
                                            <option value="<?= $concurso['id'] ?>" 
                                                    <?= $filtros['concurso_id'] == $concurso['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($concurso['titulo']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="escola_id">Escola:</label>
                                    <select id="escola_id" name="escola_id" class="form-control">
                                        <option value="">Todas as escolas</option>
                                        <?php foreach ($escolas as $escola): ?>
                                            <option value="<?= $escola['id'] ?>" 
                                                    <?= $filtros['escola_id'] == $escola['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($escola['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sala_id">Sala:</label>
                                    <select id="sala_id" name="sala_id" class="form-control">
                                        <option value="">Todas as salas</option>
                                        <?php foreach ($salas as $sala): ?>
                                            <option value="<?= $sala['id'] ?>" 
                                                    <?= $filtros['sala_id'] == $sala['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($sala['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Status:</label>
                                    <select id="status" name="status" class="form-control">
                                        <option value="">Todos os status</option>
                                        <option value="pendente" <?= $filtros['status'] == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                                        <option value="aprovado" <?= $filtros['status'] == 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                                        <option value="reprovado" <?= $filtros['status'] == 'reprovado' ? 'selected' : '' ?>>Reprovado</option>
                                        <option value="cancelado" <?= $filtros['status'] == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="data_inicio">Data de Início:</label>
                                    <input type="date" id="data_inicio" name="data_inicio" class="form-control" 
                                           value="<?= htmlspecialchars($filtros['data_inicio']) ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="data_fim">Data de Fim:</label>
                                    <input type="date" id="data_fim" name="data_fim" class="form-control" 
                                           value="<?= htmlspecialchars($filtros['data_fim']) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                            <a href="relatorios.php" class="btn btn-secondary">Limpar Filtros</a>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <h3>Gerar Relatório</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="tipo_relatorio">Tipo de Relatório:</label>
                            <select id="tipo_relatorio" name="tipo_relatorio" class="form-control" required>
                                <option value="">Selecione o tipo</option>
                                <option value="lista_fiscais">Lista de Fiscais</option>
                                <option value="lista_presenca">Lista de Presença</option>
                                <option value="lista_pagamentos">Lista de Pagamentos</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="gerar_relatorio" class="btn btn-success">
                                📄 Gerar PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <h3>📋 Tipos de Relatório</h3>
                    
                    <div class="info-box">
                        <h4>📊 Lista de Fiscais</h4>
                        <p>Relatório completo com todos os fiscais cadastrados, incluindo dados pessoais e status.</p>
                    </div>
                    
                    <div class="info-box">
                        <h4>✅ Lista de Presença</h4>
                        <p>Lista para controle de presença dos fiscais no dia da prova, com espaço para assinatura.</p>
                    </div>
                    
                    <div class="info-box">
                        <h4>💰 Lista de Pagamentos</h4>
                        <p>Lista para controle de pagamentos aos fiscais, com valores e espaço para assinatura.</p>
                    </div>
                </div>
                
                <div class="card">
                    <h3>🔧 Ações</h3>
                    <a href="admin/dashboard.php" class="btn btn-secondary btn-block">Painel Administrativo</a>
                    <a href="index.php" class="btn btn-primary btn-block">Voltar ao Sistema</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Atualizar salas baseado na escola selecionada
        const escolaSelect = document.getElementById('escola_id');
        const salaSelect = document.getElementById('sala_id');
        
        escolaSelect.addEventListener('change', function() {
            const escolaId = this.value;
            
            // Limpar opções de sala
            salaSelect.innerHTML = '<option value="">Todas as salas</option>';
            
            if (escolaId) {
                // Filtrar salas da escola selecionada
                const salas = <?= json_encode($salas) ?>;
                salas.forEach(function(sala) {
                    if (sala.escola_id == escolaId) {
                        const option = document.createElement('option');
                        option.value = sala.id;
                        option.textContent = sala.nome;
                        salaSelect.appendChild(option);
                    }
                });
            }
        });
    });
    </script>
</body>
</html> 