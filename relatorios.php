<?php
ob_start();
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
        $stmt = $db->query("SELECT id, titulo, numero_concurso, ano_concurso, orgao, cidade, estado, logo_orgao FROM concursos WHERE status = 'ativo' ORDER BY titulo");
        $concursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar escolas
        $stmt = $db->query("SELECT id, nome, concurso_id FROM escolas ORDER BY nome");
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

function obterDadosConcurso($concurso_id) {
    $db = getDB();
    if (!$db) {
        throw new Exception("Erro de conexão com banco de dados");
    }
    
    $stmt = $db->prepare("SELECT * FROM concursos WHERE id = ?");
    $stmt->execute([$concurso_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obterDadosEscola($escola_id) {
    $db = getDB();
    if (!$db) {
        throw new Exception("Erro de conexão com banco de dados");
    }
    
    $stmt = $db->prepare("SELECT * FROM escolas WHERE id = ?");
    $stmt->execute([$escola_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function gerarRelatorioFiscais($filtros) {
    $db = getDB();
    if (!$db) {
        throw new Exception("Erro de conexão com banco de dados");
    }
    
    // Obter dados do concurso
    $concurso = null;
    if (!empty($filtros['concurso_id'])) {
        $concurso = obterDadosConcurso($filtros['concurso_id']);
    }
    
    // Buscar escolas do concurso
    $escolas = [];
    if (!empty($filtros['concurso_id'])) {
        $stmt = $db->prepare("SELECT * FROM escolas WHERE concurso_id = ? AND status = 'ativo' ORDER BY nome");
        $stmt->execute([$filtros['concurso_id']]);
        $escolas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Gerar PDF
    require_once 'tcpdf/tcpdf.php';
    
    // Limpar qualquer saída anterior
    ob_clean();
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('Sistema IDH');
    $pdf->SetAuthor('IDH');
    $pdf->SetTitle('Relatório de Fiscais');
    
    // Para cada escola, gerar uma página
    foreach ($escolas as $escola) {
        $pdf->AddPage();
        
        // Cabeçalho com logo
        if ($concurso && $concurso['logo_orgao'] && file_exists($concurso['logo_orgao'])) {
            $pdf->Image($concurso['logo_orgao'], 10, 10, 30);
        }
        
        // Título do relatório
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Relatório de Fiscais', 0, 1, 'C');
        
        // Informações do concurso
        if ($concurso) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, $concurso['titulo'], 0, 1, 'C');
            
            $pdf->SetFont('helvetica', '', 10);
            if ($concurso['numero_concurso'] && $concurso['ano_concurso']) {
                $pdf->Cell(0, 6, 'Concurso: ' . $concurso['numero_concurso'] . '/' . $concurso['ano_concurso'], 0, 1, 'C');
            }
            $pdf->Cell(0, 6, 'Órgão: ' . $concurso['orgao'], 0, 1, 'C');
        }
        
        $pdf->Ln(5);
        
        // Informações da escola
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Escola: ' . $escola['nome'], 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'Endereço: ' . $escola['endereco'], 0, 1, 'L');
        if ($escola['telefone']) {
            $pdf->Cell(0, 6, 'Telefone: ' . $escola['telefone'], 0, 1, 'L');
        }
        
        $pdf->Ln(5);
        
        // Buscar fiscais da escola
        $stmt = $db->prepare("
            SELECT f.*, s.nome as sala_nome
            FROM fiscais f
            LEFT JOIN alocacoes_fiscais af ON f.id = af.fiscal_id
            LEFT JOIN salas s ON af.sala_id = s.id
            WHERE f.concurso_id = ? AND s.escola_id = ? AND f.status = 'aprovado'
            ORDER BY f.nome
        ");
        $stmt->execute([$filtros['concurso_id'], $escola['id']]);
        $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Cabeçalho da tabela
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(60, 7, 'Nome', 1, 0, 'L', true);
        $pdf->Cell(30, 7, 'CPF', 1, 0, 'L', true);
        $pdf->Cell(40, 7, 'Celular', 1, 0, 'L', true);
        $pdf->Cell(30, 7, 'Sala', 1, 0, 'L', true);
        $pdf->Cell(30, 7, 'Status', 1, 1, 'L', true);
        
        // Dados
        $pdf->SetFont('helvetica', '', 8);
        foreach ($fiscais as $fiscal) {
            $pdf->Cell(60, 6, substr($fiscal['nome'], 0, 25), 1, 0, 'L');
            $pdf->Cell(30, 6, $fiscal['cpf'], 1, 0, 'L');
            $pdf->Cell(40, 6, $fiscal['celular'], 1, 0, 'L');
            $pdf->Cell(30, 6, substr($fiscal['sala_nome'], 0, 12), 1, 0, 'L');
            $pdf->Cell(30, 6, ucfirst($fiscal['status']), 1, 1, 'L');
        }
        
        $pdf->Ln(10);
        
        // Espaço para assinaturas
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Assinaturas:', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 9);
        if ($escola['coordenador_idh']) {
            $pdf->Cell(0, 6, 'Coordenador IDH: ' . $escola['coordenador_idh'], 0, 1, 'L');
        }
        if ($escola['coordenador_comissao']) {
            $pdf->Cell(0, 6, 'Coordenador da Comissão: ' . $escola['coordenador_comissao'], 0, 1, 'L');
        }
        
        $pdf->Ln(10);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(5);
        $pdf->Cell(95, 8, 'Coordenador IDH', 0, 0, 'C');
        $pdf->Cell(95, 8, 'Coordenador da Comissão', 0, 1, 'C');
    }
    
    $pdf->Output('relatorio_fiscais.pdf', 'D');
    exit;
}

function gerarListaPresenca($filtros) {
    $db = getDB();
    if (!$db) {
        throw new Exception("Erro de conexão com banco de dados");
    }
    
    // Obter dados do concurso
    $concurso = null;
    if (!empty($filtros['concurso_id'])) {
        $concurso = obterDadosConcurso($filtros['concurso_id']);
    }
    
    // Buscar escolas do concurso
    $escolas = [];
    if (!empty($filtros['concurso_id'])) {
        $stmt = $db->prepare("SELECT * FROM escolas WHERE concurso_id = ? AND status = 'ativo' ORDER BY nome");
        $stmt->execute([$filtros['concurso_id']]);
        $escolas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Gerar PDF
    require_once 'tcpdf/tcpdf.php';
    
    // Limpar qualquer saída anterior
    ob_clean();
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('Sistema IDH');
    $pdf->SetAuthor('IDH');
    $pdf->SetTitle('Lista de Presença');
    
    // Para cada escola, gerar uma página
    foreach ($escolas as $escola) {
        $pdf->AddPage();
        
        // Cabeçalho com logo
        if ($concurso && $concurso['logo_orgao'] && file_exists($concurso['logo_orgao'])) {
            $pdf->Image($concurso['logo_orgao'], 10, 10, 30);
        }
        
        // Título do relatório
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Lista de Presença - Fiscais', 0, 1, 'C');
        
        // Informações do concurso
        if ($concurso) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, $concurso['titulo'], 0, 1, 'C');
            
            $pdf->SetFont('helvetica', '', 10);
            if ($concurso['numero_concurso'] && $concurso['ano_concurso']) {
                $pdf->Cell(0, 6, 'Concurso: ' . $concurso['numero_concurso'] . '/' . $concurso['ano_concurso'], 0, 1, 'C');
            }
            $pdf->Cell(0, 6, 'Órgão: ' . $concurso['orgao'], 0, 1, 'C');
        }
        
        $pdf->Ln(5);
        
        // Informações da escola
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Escola: ' . $escola['nome'], 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'Endereço: ' . $escola['endereco'], 0, 1, 'L');
        if ($escola['telefone']) {
            $pdf->Cell(0, 6, 'Telefone: ' . $escola['telefone'], 0, 1, 'L');
        }
        
        $pdf->Ln(5);
        
        // Buscar fiscais da escola
        $stmt = $db->prepare("
            SELECT f.*, s.nome as sala_nome
            FROM fiscais f
            LEFT JOIN alocacoes_fiscais af ON f.id = af.fiscal_id
            LEFT JOIN salas s ON af.sala_id = s.id
            WHERE f.concurso_id = ? AND s.escola_id = ? AND f.status = 'aprovado'
            ORDER BY f.nome
        ");
        $stmt->execute([$filtros['concurso_id'], $escola['id']]);
        $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Cabeçalho da tabela
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(60, 7, 'Nome do Fiscal', 1, 0, 'L', true);
        $pdf->Cell(30, 7, 'CPF', 1, 0, 'L', true);
        $pdf->Cell(30, 7, 'Sala', 1, 0, 'L', true);
        $pdf->Cell(30, 7, 'Presença', 1, 0, 'L', true);
        $pdf->Cell(40, 7, 'Assinatura', 1, 1, 'L', true);
        
        // Dados
        $pdf->SetFont('helvetica', '', 8);
        foreach ($fiscais as $fiscal) {
            $pdf->Cell(60, 8, $fiscal['nome'], 1, 0, 'L');
            $pdf->Cell(30, 8, $fiscal['cpf'], 1, 0, 'L');
            $pdf->Cell(30, 8, substr($fiscal['sala_nome'], 0, 12), 1, 0, 'L');
            $pdf->Cell(30, 8, '□ Presente □ Ausente', 1, 0, 'L');
            $pdf->Cell(40, 8, '', 1, 1, 'L'); // Espaço para assinatura
        }
        
        $pdf->Ln(10);
        
        // Espaço para assinaturas
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Assinaturas:', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 9);
        if ($escola['coordenador_idh']) {
            $pdf->Cell(0, 6, 'Coordenador IDH: ' . $escola['coordenador_idh'], 0, 1, 'L');
        }
        if ($escola['coordenador_comissao']) {
            $pdf->Cell(0, 6, 'Coordenador da Comissão: ' . $escola['coordenador_comissao'], 0, 1, 'L');
        }
        
        $pdf->Ln(10);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(5);
        $pdf->Cell(95, 8, 'Coordenador IDH', 0, 0, 'C');
        $pdf->Cell(95, 8, 'Coordenador da Comissão', 0, 1, 'C');
    }
    
    $pdf->Output('lista_presenca.pdf', 'D');
    exit;
}

function gerarListaPagamentos($filtros) {
    $db = getDB();
    if (!$db) {
        throw new Exception("Erro de conexão com banco de dados");
    }
    
    // Obter dados do concurso
    $concurso = null;
    if (!empty($filtros['concurso_id'])) {
        $concurso = obterDadosConcurso($filtros['concurso_id']);
    }
    
    // Buscar escolas do concurso
    $escolas = [];
    if (!empty($filtros['concurso_id'])) {
        $stmt = $db->prepare("SELECT * FROM escolas WHERE concurso_id = ? AND status = 'ativo' ORDER BY nome");
        $stmt->execute([$filtros['concurso_id']]);
        $escolas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Gerar PDF
    require_once 'tcpdf/tcpdf.php';
    
    // Limpar qualquer saída anterior
    ob_clean();
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('Sistema IDH');
    $pdf->SetAuthor('IDH');
    $pdf->SetTitle('Lista de Pagamentos');
    
    // Para cada escola, gerar uma página
    foreach ($escolas as $escola) {
        $pdf->AddPage();
        
        // Cabeçalho com logo
        if ($concurso && $concurso['logo_orgao'] && file_exists($concurso['logo_orgao'])) {
            $pdf->Image($concurso['logo_orgao'], 10, 10, 30);
        }
        
        // Título do relatório
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Lista de Pagamentos - Fiscais', 0, 1, 'C');
        
        // Informações do concurso
        if ($concurso) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, $concurso['titulo'], 0, 1, 'C');
            
            $pdf->SetFont('helvetica', '', 10);
            if ($concurso['numero_concurso'] && $concurso['ano_concurso']) {
                $pdf->Cell(0, 6, 'Concurso: ' . $concurso['numero_concurso'] . '/' . $concurso['ano_concurso'], 0, 1, 'C');
            }
            $pdf->Cell(0, 6, 'Órgão: ' . $concurso['orgao'], 0, 1, 'C');
            $pdf->Cell(0, 6, 'Valor: R$ ' . number_format($concurso['valor_pagamento'], 2, ',', '.'), 0, 1, 'C');
        }
        
        $pdf->Ln(5);
        
        // Informações da escola
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Escola: ' . $escola['nome'], 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'Endereço: ' . $escola['endereco'], 0, 1, 'L');
        if ($escola['telefone']) {
            $pdf->Cell(0, 6, 'Telefone: ' . $escola['telefone'], 0, 1, 'L');
        }
        
        $pdf->Ln(5);
        
        // Buscar fiscais da escola
        $stmt = $db->prepare("
            SELECT f.*, s.nome as sala_nome
            FROM fiscais f
            LEFT JOIN alocacoes_fiscais af ON f.id = af.fiscal_id
            LEFT JOIN salas s ON af.sala_id = s.id
            WHERE f.concurso_id = ? AND s.escola_id = ? AND f.status = 'aprovado'
            ORDER BY f.nome
        ");
        $stmt->execute([$filtros['concurso_id'], $escola['id']]);
        $fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Cabeçalho da tabela
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(60, 7, 'Nome do Fiscal', 1, 0, 'L', true);
        $pdf->Cell(30, 7, 'CPF', 1, 0, 'L', true);
        $pdf->Cell(30, 7, 'Sala', 1, 0, 'L', true);
        $pdf->Cell(30, 7, 'Valor', 1, 0, 'L', true);
        $pdf->Cell(40, 7, 'Assinatura', 1, 1, 'L', true);
        
        // Dados
        $pdf->SetFont('helvetica', '', 8);
        foreach ($fiscais as $fiscal) {
            $pdf->Cell(60, 8, $fiscal['nome'], 1, 0, 'L');
            $pdf->Cell(30, 8, $fiscal['cpf'], 1, 0, 'L');
            $pdf->Cell(30, 8, substr($fiscal['sala_nome'], 0, 12), 1, 0, 'L');
            $pdf->Cell(30, 8, 'R$ ' . number_format($concurso['valor_pagamento'], 2, ',', '.'), 1, 0, 'L');
            $pdf->Cell(40, 8, '', 1, 1, 'L'); // Espaço para assinatura
        }
        
        $pdf->Ln(10);
        
        // Espaço para assinaturas
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Assinaturas:', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 9);
        if ($escola['coordenador_idh']) {
            $pdf->Cell(0, 6, 'Coordenador IDH: ' . $escola['coordenador_idh'], 0, 1, 'L');
        }
        if ($escola['coordenador_comissao']) {
            $pdf->Cell(0, 6, 'Coordenador da Comissão: ' . $escola['coordenador_comissao'], 0, 1, 'L');
        }
        
        $pdf->Ln(10);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(5);
        $pdf->Cell(95, 8, 'Coordenador IDH', 0, 0, 'C');
        $pdf->Cell(95, 8, 'Coordenador da Comissão', 0, 1, 'C');
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
                                    <select id="concurso_id" name="concurso_id" class="form-control" required>
                                        <option value="">Selecione um concurso</option>
                                        <?php foreach ($concursos as $concurso): ?>
                                            <option value="<?= $concurso['id'] ?>" 
                                                    <?= $filtros['concurso_id'] == $concurso['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($concurso['titulo']) ?> <?= htmlspecialchars($concurso['numero_concurso']) ?>/<?= htmlspecialchars($concurso['ano_concurso']) ?> da <?= htmlspecialchars($concurso['orgao']) ?> de <?= htmlspecialchars($concurso['cidade']) ?>/<?= htmlspecialchars($concurso['estado']) ?>
                                                <?php if ($concurso['numero_concurso'] && $concurso['ano_concurso']): ?>
                                                    (<?= $concurso['numero_concurso'] ?>/<?= $concurso['ano_concurso'] ?>)
                                                <?php endif; ?>
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
                        <p>Relatório completo com todos os fiscais por escola, incluindo dados pessoais e status.</p>
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
        // Atualizar escolas baseado no concurso selecionado
        const concursoSelect = document.getElementById('concurso_id');
        const escolaSelect = document.getElementById('escola_id');
        
        concursoSelect.addEventListener('change', function() {
            const concursoId = this.value;
            
            // Limpar opções de escola
            escolaSelect.innerHTML = '<option value="">Todas as escolas</option>';
            
            if (concursoId) {
                // Filtrar escolas do concurso selecionado
                const escolas = <?= json_encode($escolas) ?>;
                escolas.forEach(function(escola) {
                    if (escola.concurso_id == concursoId) {
                        const option = document.createElement('option');
                        option.value = escola.id;
                        option.textContent = escola.nome;
                        escolaSelect.appendChild(option);
                    }
                });
            }
        });
    });
    </script>
</body>
</html> 