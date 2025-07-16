<?php
require_once '../config.php';
require_once '../includes/pdf_base.php';

// Verificar se tem permissão para relatórios
if (!isLoggedIn() || !temPermissaoPresenca()) {
    redirect('../login.php');
}

$db = getDB();
$relatorio = [];

// Filtros
$concurso_id = isset($_GET['concurso_id']) ? (int)$_GET['concurso_id'] : null;
$escola_id = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : null;
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

try {
    $sql = "
        SELECT 
            f.id,
            f.nome,
            f.cpf,
            f.celular,
            c.titulo as concurso_titulo,
            e.nome as escola_nome,
            s.nome as sala_nome,
            a.data_alocacao,
            a.horario_alocacao,
            a.tipo_alocacao,
            pt.status as presente_treinamento,
            pt.observacoes as obs_treinamento,
            pp.status as presente_prova,
            pp.observacoes as obs_prova
        FROM fiscais f
        LEFT JOIN concursos c ON f.concurso_id = c.id
        LEFT JOIN alocacoes_fiscais a ON f.id = a.fiscal_id AND a.status = 'ativo'
        LEFT JOIN escolas e ON a.escola_id = e.id
        LEFT JOIN salas s ON a.sala_id = s.id
        LEFT JOIN presenca pt ON f.id = pt.fiscal_id AND pt.concurso_id = f.concurso_id AND pt.tipo_presenca = 'treinamento'
        LEFT JOIN presenca pp ON f.id = pp.fiscal_id AND pp.concurso_id = f.concurso_id AND pp.tipo_presenca = 'prova'
        WHERE f.status = 'aprovado'
    ";
    $params = [];
    
    if ($concurso_id) {
        $sql .= " AND f.concurso_id = ?";
        $params[] = $concurso_id;
    }
    
    if ($escola_id) {
        $sql .= " AND a.escola_id = ?";
        $params[] = $escola_id;
    }
    
    $sql .= " ORDER BY e.nome, s.nome, f.nome";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $relatorio = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    logActivity('Erro ao buscar dados para relatório: ' . $e->getMessage(), 'ERROR');
}

// Buscar dados do concurso
$concurso = null;
if ($concurso_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM concursos WHERE id = ?");
        $stmt->execute([$concurso_id]);
        $concurso = $stmt->fetch();
    } catch (Exception $e) {
        logActivity('Erro ao buscar concurso: ' . $e->getMessage(), 'ERROR');
    }
} else {
    // Se não especificado, buscar o concurso ativo mais recente
    try {
        $stmt = $db->query("SELECT * FROM concursos WHERE status = 'ativo' ORDER BY data_prova DESC LIMIT 1");
        $concurso = $stmt->fetch();
    } catch (Exception $e) {
        logActivity('Erro ao buscar concurso ativo: ' . $e->getMessage(), 'ERROR');
    }
}

// Configurar PDF
$instituto_nome = getConfig('instituto_nome', 'Instituto Dignidade Humana');
$instituto_logo = __DIR__ . '/../logos/instituto.png';
$instituto_info = getConfig('info_institucional', 'Instituto Dignidade Humana\nEndereço: ...\nContato: ...');

$pdf = new PDFInstituto('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->setInstitutoData($instituto_nome, $instituto_logo, $instituto_info);
$pdf->AddPage();
$pdf->Ln(18); // Espaço extra após o cabeçalho

// Informações do concurso centralizadas
if ($concurso) {
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 8, $concurso['orgao'] . ' - ' . $concurso['cidade'] . ' - ' . $concurso['estado'], 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, $concurso['titulo'] . ' - ' . $concurso['numero_concurso'] . '/' . $concurso['ano_concurso'], 0, 1, 'C');
    $pdf->Ln(8);
}

// Título do relatório
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'RELATÓRIO DE COMPARECIMENTO', 0, 1, 'C');
$pdf->Ln(5);

// Estatísticas
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 8, 'ESTATÍSTICAS:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);

$total_fiscais = count($relatorio);
$presentes_treinamento = count(array_filter($relatorio, function($r) { return $r['presente_treinamento'] == 'presente'; }));
$presentes_prova = count(array_filter($relatorio, function($r) { return $r['presente_prova'] == 'presente'; }));
$presentes_ambos = count(array_filter($relatorio, function($r) { return $r['presente_treinamento'] == 'presente' && $r['presente_prova'] == 'presente'; }));

$pdf->Cell(60, 6, 'Total de Fiscais:', 0, 0, 'L');
$pdf->Cell(30, 6, $total_fiscais, 0, 1, 'L');

$pdf->Cell(60, 6, 'Presentes Treinamento:', 0, 0, 'L');
$pdf->Cell(30, 6, $presentes_treinamento, 0, 1, 'L');

$pdf->Cell(60, 6, 'Presentes Prova:', 0, 0, 'L');
$pdf->Cell(30, 6, $presentes_prova, 0, 1, 'L');

$pdf->Cell(60, 6, 'Presentes Ambos:', 0, 0, 'L');
$pdf->Cell(30, 6, $presentes_ambos, 0, 1, 'L');

$pdf->Ln(10);

// Cabeçalho da tabela
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(50, 7, 'Nome', 1, 0, 'L', true);
$pdf->Cell(25, 7, 'CPF', 1, 0, 'L', true);
$pdf->Cell(40, 7, 'Escola', 1, 0, 'L', true);
$pdf->Cell(25, 7, 'Sala', 1, 0, 'L', true);
$pdf->Cell(25, 7, 'Data Aloc.', 1, 0, 'L', true);
$pdf->Cell(25, 7, 'Treinamento', 1, 0, 'L', true);
$pdf->Cell(25, 7, 'Prova', 1, 0, 'L', true);
$pdf->Cell(25, 7, 'Status', 1, 1, 'L', true);

// Dados
$pdf->SetFont('helvetica', '', 7);
foreach ($relatorio as $fiscal) {
    $pdf->Cell(50, 6, substr($fiscal['nome'], 0, 20), 1, 0, 'L');
    $pdf->Cell(25, 6, formatCPF($fiscal['cpf']), 1, 0, 'L');
    $pdf->Cell(40, 6, substr($fiscal['escola_nome'] ?? 'Não alocado', 0, 18), 1, 0, 'L');
    $pdf->Cell(25, 6, substr($fiscal['sala_nome'] ?? 'Não alocado', 0, 12), 1, 0, 'L');
    $pdf->Cell(25, 6, $fiscal['data_alocacao'] ? date('d/m/Y', strtotime($fiscal['data_alocacao'])) : 'N/A', 1, 0, 'L');
    
    // Status treinamento
    $treinamento = $fiscal['presente_treinamento'] ?? '';
    if ($treinamento == 'presente') {
        $pdf->Cell(25, 6, 'Presente', 1, 0, 'L');
    } elseif ($treinamento == 'ausente') {
        $pdf->Cell(25, 6, 'Ausente', 1, 0, 'L');
    } elseif ($treinamento == 'justificado') {
        $pdf->Cell(25, 6, 'Justificado', 1, 0, 'L');
    } else {
        $pdf->Cell(25, 6, 'N/A', 1, 0, 'L');
    }
    
    // Status prova
    $prova = $fiscal['presente_prova'] ?? '';
    if ($prova == 'presente') {
        $pdf->Cell(25, 6, 'Presente', 1, 0, 'L');
    } elseif ($prova == 'ausente') {
        $pdf->Cell(25, 6, 'Ausente', 1, 0, 'L');
    } elseif ($prova == 'justificado') {
        $pdf->Cell(25, 6, 'Justificado', 1, 0, 'L');
    } else {
        $pdf->Cell(25, 6, 'N/A', 1, 0, 'L');
    }
    
    // Status geral
    if ($treinamento === 'presente' && $prova === 'presente') {
        $pdf->Cell(25, 6, 'Completo', 1, 1, 'L');
    } elseif ($treinamento === 'presente' || $prova === 'presente') {
        $pdf->Cell(25, 6, 'Parcial', 1, 1, 'L');
    } elseif ($treinamento === 'ausente' || $prova === 'ausente') {
        $pdf->Cell(25, 6, 'Ausente', 1, 1, 'L');
    } else {
        $pdf->Cell(25, 6, 'N/A', 1, 1, 'L');
    }
}

$pdf->Output('relatorio_comparecimento.pdf', 'D');
exit;
?> 